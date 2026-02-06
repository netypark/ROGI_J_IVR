<?php
/**
 * PBX Configuration Helper Functions
 * ===================================
 * Date: 2026-02-05
 * Version: 2.0.0
 *
 * IP 기반 PBX 장비 식별 및 크로스콜 설정 관리
 * - 이중화 서버 지원 (Primary/Secondary)
 * - 장비별 crosscall prefix 관리
 *
 * v2.0.0 변경사항:
 * - T_PBX_CONFIG 테이블 대신 T_PBX 테이블 사용
 * - IP 매칭: server_ip → pbx_ip1/pbx_ip2 (OR 조건)
 * - 다른 시스템과 공유 가능한 테이블 구조
 *
 * v1.3.0 변경사항:
 * - return_prefix 컬럼 삭제 (모든 크로스콜이 crosscall_prefix 사용)
 * - T_PBX_CONFIG 테이블 간소화
 *
 * v1.2.0 변경사항:
 * - 모든 크로스콜 통일된 형식: crosscall_prefix + DID + TARGET_Q + NEXT_Q
 * - TARGET_Q: 대상 장비에서 시도할 Q
 * - NEXT_Q: TARGET_Q 다음에 시도할 Q (QList 순서)
 * - A/B/C 모든 장비에서 동일한 모듈로 처리 가능
 */

// 캐시 변수 (세션 내 한번만 조회)
$_PBX_CONFIG_CACHE = null;

/**
 * 자기 서버 IP 조회
 * @return string 서버 IP 주소
 */
function get_my_server_ip() {
    // 방법 1: $_SERVER['SERVER_ADDR'] (Apache/Nginx) - 127.0.0.1 제외
    if (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
        return $_SERVER['SERVER_ADDR'];
    }

    // 방법 2: ip addr 명령어로 실제 네트워크 인터페이스 IP 조회 (가장 신뢰할 수 있음)
    $output = shell_exec("ip addr show 2>/dev/null | grep 'inet ' | grep -v '127.0.0.1' | head -1 | awk '{print \$2}' | cut -d'/' -f1");
    if ($output && trim($output)) {
        $ip = trim($output);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
    }

    // 방법 3: hostname -I (대체 방법)
    $output = shell_exec("hostname -I 2>/dev/null | awk '{print \$1}'");
    if ($output && trim($output)) {
        $ip = trim($output);
        if ($ip != '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }
    }

    // 방법 4: gethostbyname (CLI 환경)
    $hostname = gethostname();
    if ($hostname) {
        $ip = gethostbyname($hostname);
        if ($ip && $ip != $hostname && $ip != '127.0.0.1') {
            return $ip;
        }
    }

    // 방법 5: 특정 인터페이스 직접 조회
    foreach (['eth0', 'ens192', 'ens160', 'enp0s3'] as $iface) {
        $output = shell_exec("ip addr show $iface 2>/dev/null | grep 'inet ' | awk '{print \$2}' | cut -d'/' -f1");
        if ($output && trim($output)) {
            $ip = trim($output);
            if ($ip != '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }
    }

    return '127.0.0.1';
}

/**
 * IP로 자기 PBX 정보 조회 (캐시 사용)
 * v2.0.0: T_PBX 테이블 사용 (pbx_ip1/pbx_ip2 OR 매칭)
 * @param mysqli $conn DB 연결
 * @param string $server_ip 서버 IP (null이면 자동 감지)
 * @return object|null PBX 설정 정보
 */
function get_my_pbx_config($conn, $server_ip = null) {
    global $_PBX_CONFIG_CACHE;

    // 캐시 확인
    if ($_PBX_CONFIG_CACHE !== null) {
        return $_PBX_CONFIG_CACHE;
    }

    // IP 자동 감지
    if ($server_ip === null) {
        $server_ip = get_my_server_ip();
    }

    // v2.0.0: LOGI.T_PBX 테이블 사용, pbx_ip1/pbx_ip2/pbx_vip로 매칭
    $escaped_ip = mysqli_real_escape_string($conn, $server_ip);
    $sql = "SELECT pbx_id, pbx_name, pbx_ip1, pbx_ip2, pbx_vip, crosscall_prefix, q_length, origin_q_length
            FROM LOGI.T_PBX
            WHERE pbx_ip1 = '$escaped_ip' OR pbx_ip2 = '$escaped_ip' OR pbx_vip = '$escaped_ip'
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_object($res))) {
        // 하위 호환성: server_ip 필드 추가
        $row->server_ip = $server_ip;
        $_PBX_CONFIG_CACHE = $row;
        return $row;
    }

    // 기본값 반환 (테이블에 없는 경우)
    $default = new stdClass();
    $default->pbx_id = '0';
    $default->pbx_name = 'Unknown';
    $default->pbx_ip1 = $server_ip;
    $default->pbx_ip2 = '';
    $default->server_ip = $server_ip;  // 하위 호환성
    $default->crosscall_prefix = '99';
    $default->q_length = 3;
    $default->origin_q_length = 3;

    $_PBX_CONFIG_CACHE = $default;
    return $default;
}

/**
 * 자기 pbx_id 조회 (한번만)
 * @param mysqli $conn DB 연결
 * @return string pbx_id
 */
function get_my_pbx_id($conn) {
    $config = get_my_pbx_config($conn);
    return $config->pbx_id ?? '0';
}

/**
 * 자기 crosscall prefix 조회
 * @param mysqli $conn DB 연결
 * @return string crosscall prefix
 */
function get_my_crosscall_prefix($conn) {
    $config = get_my_pbx_config($conn);
    return $config->crosscall_prefix ?? '99';
}

/**
 * 자기 return prefix 조회 (deprecated - 하위 호환용)
 * v1.3.0: return_prefix 컬럼 삭제로 crosscall_prefix 반환
 * @deprecated Use get_my_crosscall_prefix() instead
 * @param mysqli $conn DB 연결
 * @return string crosscall prefix
 */
function get_my_return_prefix($conn) {
    // v1.3.0: return_prefix 삭제됨, crosscall_prefix 반환
    return get_my_crosscall_prefix($conn);
}

/**
 * 대상 pbx_id의 crosscall prefix 조회
 * v2.0.0: T_PBX 테이블 사용
 * @param mysqli $conn DB 연결
 * @param int $target_pbx_id 대상 pbx_id
 * @return object|null prefix 정보
 */
function get_target_pbx_prefix($conn, $target_pbx_id) {
    // v2.0.0: LOGI.T_PBX 테이블 사용
    $sql = "SELECT crosscall_prefix, q_length, origin_q_length
            FROM LOGI.T_PBX
            WHERE pbx_id = " . intval($target_pbx_id) . "
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_object($res))) {
        return $row;
    }

    // 기본값
    $default = new stdClass();
    $default->crosscall_prefix = '99';
    $default->q_length = 3;
    $default->origin_q_length = 3;
    return $default;
}

/**
 * Q번호로 pbx_id 조회 (기존 방식 유지)
 * @param mysqli $conn DB 연결
 * @param string $q_num Q번호
 * @return string pbx_id
 */
function get_pbx_id_for_queue($conn, $q_num) {
    if (empty($q_num) || $q_num == '0') {
        return '0';
    }

    $sql = "SELECT c.pbx_id
            FROM LOGI.T_QUEUE AS q
            INNER JOIN LOGI.T_COMPANY AS c ON q.master_id = c.master_id AND c.company_level = 0
            WHERE q.q_num = '" . mysqli_real_escape_string($conn, $q_num) . "'
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res))) {
        return $row[0] ?? '0';
    }
    return '0';
}

/**
 * 크로스콜 필요 여부 확인
 * @param mysqli $conn DB 연결
 * @param string $target_q_num 대상 Q번호
 * @return array [is_crosscall, my_pbx_id, target_pbx_id, target_prefix]
 */
function check_crosscall_required($conn, $target_q_num) {
    // 자기 pbx_id 조회 (IP 기반, 캐시됨)
    $my_pbx_id = get_my_pbx_id($conn);

    // 대상 Q의 pbx_id 조회
    $target_pbx_id = get_pbx_id_for_queue($conn, $target_q_num);

    // 비교
    $is_crosscall = ($my_pbx_id != '0' && $target_pbx_id != '0' && $my_pbx_id != $target_pbx_id);

    // 대상 장비의 prefix 조회
    $target_prefix = null;
    if ($is_crosscall) {
        $target_prefix = get_target_pbx_prefix($conn, $target_pbx_id);
    }

    return [
        'is_crosscall' => $is_crosscall,
        'my_pbx_id' => $my_pbx_id,
        'target_pbx_id' => $target_pbx_id,
        'target_prefix' => $target_prefix
    ];
}

/**
 * 크로스콜 다이얼 번호 생성 (통일된 형식)
 * v1.2.0: 모든 크로스콜에 동일한 형식 사용
 *
 * 형식: crosscall_prefix + DID + TARGET_Q + NEXT_Q
 * - crosscall_prefix: 대상 장비의 prefix (90, 91, 92 등)
 * - DID: 원본 전화번호
 * - TARGET_Q: 대상 장비에서 시도할 Q번호
 * - NEXT_Q: TARGET_Q 실패 시 다음에 시도할 Q번호 (QList 순서)
 *
 * @param mysqli $conn DB 연결
 * @param string $did DID 번호
 * @param string $target_q 대상 장비에서 시도할 Q번호
 * @param string $next_q TARGET_Q 다음에 시도할 Q번호 (QList 순서)
 * @param string $target_pbx_id 대상 pbx_id (없으면 target_q에서 조회)
 * @return string 크로스콜 다이얼 번호
 */
function generate_crosscall_dial($conn, $did, $target_q, $next_q, $target_pbx_id = null) {
    // 대상 pbx_id 조회
    if ($target_pbx_id === null) {
        $target_pbx_id = get_pbx_id_for_queue($conn, $target_q);
    }

    // 대상 장비의 prefix 조회
    $prefix_info = get_target_pbx_prefix($conn, $target_pbx_id);
    $prefix = $prefix_info->crosscall_prefix ?? '99';
    $q_length = $prefix_info->q_length ?? 3;
    $next_q_length = $prefix_info->origin_q_length ?? 3;

    // 다이얼 번호 생성: prefix + DID + TARGET_Q + NEXT_Q
    $target_q_pad = str_pad($target_q, $q_length, '0', STR_PAD_LEFT);
    $next_q_pad = str_pad($next_q, $next_q_length, '0', STR_PAD_LEFT);

    return $prefix . $did . $target_q_pad . $next_q_pad;
}

/**
 * 반환 크로스콜 다이얼 번호 생성 (하위 호환용 - deprecated)
 * v1.2.0: generate_crosscall_dial()을 사용하세요
 *
 * @deprecated Use generate_crosscall_dial() instead
 */
function generate_return_crosscall_dial($conn, $did, $target_q, $next_q, $target_pbx_id = null) {
    // v1.2.0: 동일한 형식 사용
    return generate_crosscall_dial($conn, $did, $target_q, $next_q, $target_pbx_id);
}

/**
 * 크로스콜 수신 번호 파싱
 * v2.0.0: T_PBX 테이블 사용
 * v1.2.0: 통일된 형식 - 항상 6자리 Q번호 (TARGET_Q + NEXT_Q)
 *
 * 형식: crosscall_prefix + DID + TARGET_Q(3) + NEXT_Q(3)
 * - TARGET_Q: 현재 장비에서 시도할 Q번호
 * - NEXT_Q: TARGET_Q 실패 시 다음에 시도할 Q번호
 *
 * @param mysqli $conn DB 연결
 * @param string $did 수신된 DID (prefix 포함)
 * @return array|null 파싱 결과 [type, prefix, original_did, target_q, next_q, source_pbx_id]
 */
function parse_crosscall_did($conn, $did) {
    // v2.0.0: LOGI.T_PBX 테이블에서 모든 prefix 조회
    $sql = "SELECT DISTINCT crosscall_prefix, q_length, origin_q_length, pbx_id
            FROM LOGI.T_PBX
            ORDER BY LENGTH(crosscall_prefix) DESC";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        return null;
    }

    while ($row = mysqli_fetch_assoc($res)) {
        $cc_prefix = $row['crosscall_prefix'];
        $q_len = intval($row['q_length']);
        $next_q_len = intval($row['origin_q_length']);

        // Crosscall prefix 체크 (예: 90, 91, 92)
        if (strpos($did, $cc_prefix) === 0) {
            $remaining = substr($did, strlen($cc_prefix));
            $total_q_len = $q_len + $next_q_len;  // 6자리 (TARGET_Q + NEXT_Q)

            // 한국 전화번호는 보통 10-12자리 (070xxxxxxxx, 01xxxxxxxxx, 02xxxxxxxx 등)
            $MIN_DID_LEN = 10;
            $MAX_DID_LEN = 12;

            // 통일된 형식: prefix + DID(10-12자리) + TARGET_Q(3) + NEXT_Q(3)
            if (strlen($remaining) > $total_q_len) {
                $next_q = substr($remaining, -$next_q_len);
                $target_q = substr($remaining, -$total_q_len, $q_len);
                $original_did = substr($remaining, 0, strlen($remaining) - $total_q_len);

                // DID가 10-12자리이고 0으로 시작하면 유효한 크로스콜
                $did_len = strlen($original_did);
                if ($did_len >= $MIN_DID_LEN && $did_len <= $MAX_DID_LEN && substr($original_did, 0, 1) === '0') {
                    return [
                        'type' => 'crosscall',
                        'prefix' => $cc_prefix,
                        'original_did' => $original_did,
                        'target_q' => ltrim($target_q, '0') ?: '0',
                        'next_q' => ltrim($next_q, '0') ?: '0',
                        'origin_q' => ltrim($next_q, '0') ?: '0',  // 하위 호환용
                        'source_pbx_id' => $row['pbx_id']
                    ];
                }
            }
        }
    }

    return null;
}

/**
 * 캐시 초기화 (테스트용)
 */
function reset_pbx_config_cache() {
    global $_PBX_CONFIG_CACHE;
    $_PBX_CONFIG_CACHE = null;
}
?>
