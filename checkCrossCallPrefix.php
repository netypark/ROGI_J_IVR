<?php
/**
 * checkCrossCallPrefix.php
 *
 * MODIFICATION HISTORY:
 * =====================
 * Date: 2026-01-29
 * Author: System Migration
 * Version: 1.4.0
 * Changes:
 *   v1.0.0 - Initial creation for CrossCall receiving scenario
 *   v1.1.0 - Extended format support for return crosscall
 *   v1.2.0 - Added company info and QList retrieval for crosscall
 *   v1.3.0 - IP 기반 PBX 설정 지원 (T_PBX_CONFIG 테이블)
 *     - 동적 prefix 조회 (90, 91, 92 등)
 *     - parse_crosscall_did() 함수 사용
 *     - 자기 서버 IP 기반 pbx_id 식별
 *   v1.3.1 - return_prefix 대신 crosscall_prefix 사용
 *     - 반환 크로스콜도 대상 Q의 pbx_id에 해당하는 crosscall_prefix 사용
 *     - 형식: crosscall_prefix + DID + ORIGIN_Q (3자리)
 *   v1.4.0 - 통일된 크로스콜 형식 (A/B/C 모든 장비 동일 모듈)
 *     - 형식: crosscall_prefix + DID + TARGET_Q + NEXT_Q
 *     - TARGET_Q: 대상 장비에서 시도할 Q번호
 *     - NEXT_Q: TARGET_Q 실패 시 다음에 시도할 Q번호 (QList 순서)
 *     - 반환 크로스콜도 동일한 형식 사용
 *     - generate_crosscall_dial() 함수로 통일
 *
 * Environment: Rocky Linux 9.6, PHP 8.2.30, Asterisk 20.17.0
 *
 * REQUEST:
 *   POST /API/checkCrossCallPrefix.php
 *   Content-Type: application/json
 *   {"REQ":"CHECK_CROSSCALL","DID":"9107089984200800700","CID":"01012345678"}
 *
 * RESPONSE (크로스콜인 경우 - 91 prefix):
 *   {
 *     "JSON_RESULT": {
 *       "CODE": "TRY_CALL_PROCEDURE",
 *       "MESSAGE": {
 *         "IS_CROSSCALL": "Y",
 *         "CROSSCALL_TYPE": "INCOMING",
 *         "ORIGINAL_DID": "07089984200",
 *         "TARGET_Q": "800",
 *         "NEXT_Q": "700",
 *         "TR_ORDER": 2,
 *         "TR_COUNT": 3,
 *         "TR_Q_LIST": ["700", "800", "900"],
 *         "TR_DID_LIST": ["07089984200", "07089984201", "07089984202"],
 *         "COMPANY_ID": 123,
 *         "MASTER_ID": 1,
 *         "ALLOC_Q": "700",
 *         ...
 *       }
 *     }
 *   }
 */

include 'plog.php';
include_once 'pbx_config.php';  // 2026-01-29 v1.3.0: IP 기반 PBX 설정

/**
 * Helper function to get pbx_id for a given Q number
 */
function get_pbx_id_for_q_cc($conn, $q_num) {
    if (empty($q_num) || $q_num == '0') {
        return '0';
    }
    $sql = "SELECT c.pbx_id
            FROM T_QUEUE AS q
            INNER JOIN T_COMPANY AS c ON q.master_id = c.master_id AND c.company_level = 0
            WHERE q.q_num = '$q_num'
            LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res))) {
        return $row[0] ?? '0';
    }
    return '0';
}

/**
 * Get company info and QList based on original DID
 */
function get_company_info_for_crosscall($conn, $ORIGINAL_DID, $CID, $TARGET_Q) {
    $INFO = new stdClass();

    // 기본값 초기화
    $INFO->company_id = 0;
    $INFO->master_id = 0;
    $INFO->allocQ = '';
    $INFO->use_crosscall = 'N';
    $INFO->tr_count = 0;
    $INFO->tr_option = 'N';
    $INFO->tr_time_first = 10;
    $INFO->tr_time_second = 10;
    $INFO->tr_q_list = array();
    $INFO->tr_did_list = array();
    $INFO->tr_company_list = array();
    $INFO->tr_order = 1;
    $INFO->is_cc_workcondition = 'END';
    $INFO->get_company = 'fail';

    // DID로 회사 정보 조회
    $sql = "SELECT company_id, did_company_id, did_number FROM T_DID_RANGE WHERE did_number='$ORIGINAL_DID' LIMIT 1;";
    SLOG(sprintf('[CC_GET_COMPANY %s:%s] %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    if (!$res || mysqli_num_rows($res) == 0) {
        SLOG(sprintf('[CC_GET_COMPANY %s:%s] DID not found in T_DID_RANGE', $ORIGINAL_DID, $CID));
        return $INFO;
    }

    $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
    $did_company_id = $row['did_company_id'];

    // 회사 정보 조회
    $sql = "SELECT c.company_id, c.master_id, c.use_crosscall, q.q_num as allocQ, c.pbx_id
            FROM T_COMPANY AS c
            LEFT JOIN T_QUEUE AS q ON c.company_id = q.company_id
            WHERE c.company_id = $did_company_id
            LIMIT 1;";
    SLOG(sprintf('[CC_GET_COMPANY %s:%s] %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))) {
        $INFO->company_id = $row['company_id'];
        $INFO->master_id = $row['master_id'];
        $INFO->use_crosscall = $row['use_crosscall'] ?? 'N';
        $INFO->allocQ = $row['allocQ'] ?? '';
        $INFO->my_pbx_id = $row['pbx_id'] ?? '0';
        $INFO->get_company = 'success';

        SLOG(sprintf('[CC_GET_COMPANY %s:%s] Found company_id=%s, master_id=%s, allocQ=%s',
            $ORIGINAL_DID, $CID, $INFO->company_id, $INFO->master_id, $INFO->allocQ));
    } else {
        SLOG(sprintf('[CC_GET_COMPANY %s:%s] Company not found', $ORIGINAL_DID, $CID));
        return $INFO;
    }

    // 크로스콜 근무시간 확인
    $sql = "SELECT cc_use, cc_workcondition_start, cc_workcondition_end
            FROM T_SET_MY
            WHERE company_id = {$INFO->company_id}
            ORDER BY id ASC LIMIT 1;";
    SLOG(sprintf('[CC_GET_WKCOND %s:%s] %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    $CC_W_USE = 'N';
    $CC_W_START = '0000';
    $CC_W_END = '2359';

    if ($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))) {
        $CC_W_USE = $row['cc_use'] ?? 'N';
        $CC_W_START = $row['cc_workcondition_start'] ?? '0000';
        $CC_W_END = $row['cc_workcondition_end'] ?? '2359';
    }

    // 현재 시간 확인
    date_default_timezone_set('Asia/Seoul');
    $HOUR = date('H', time());
    $MIN = date('i', time());
    $Ttime = $HOUR . $MIN;

    if ($CC_W_USE == 'Y' && $Ttime >= $CC_W_START && $Ttime < $CC_W_END) {
        $INFO->is_cc_workcondition = 'WORK';
        SLOG(sprintf('[CC_GET_WKCOND %s:%s] WORK time: %s (start=%s, end=%s)', $ORIGINAL_DID, $CID, $Ttime, $CC_W_START, $CC_W_END));
    } else {
        $INFO->is_cc_workcondition = 'END';
        SLOG(sprintf('[CC_GET_WKCOND %s:%s] END time: %s', $ORIGINAL_DID, $CID, $Ttime));
    }

    // QList 조회 (work condition에 따라)
    if ($INFO->is_cc_workcondition == 'WORK') {
        $sql = "SELECT s.receive_option, s.ring_wait_time_my, s.ring_wait_time_transfer,
                       t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num
                FROM T_MY_TRANSFER_CALL AS t
                INNER JOIN T_SET_MY AS s ON s.company_id = t.company_id
                WHERE t.company_id = {$INFO->company_id}
                ORDER BY t.transfer_order_num;";
    } else {
        // PEAK 또는 END 시간에는 DIRECT 또는 SEQUENCE 사용
        $sql = "SELECT s.receive_option,
                       (SELECT ring_wait_time_my FROM T_SET_MY WHERE company_id={$INFO->company_id} ORDER BY id ASC LIMIT 1) as _MY,
                       s.ring_wait_time_transfer,
                       t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num
                FROM T_DIRECT_TRANSFER_CALL AS t
                INNER JOIN T_SET_DIRECT AS s ON s.company_id = t.company_id
                WHERE t.company_id = {$INFO->company_id}
                ORDER BY t.transfer_order_num;";
    }

    SLOG(sprintf('[CC_GET_QLIST %s:%s] %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    $TR_Q_LIST = array();
    $TR_DID_LIST = array();
    $TR_COMPANY_LIST = array();
    $INDEX = 0;

    while ($res && ($row = mysqli_fetch_array($res, MYSQLI_NUM))) {
        array_push($TR_COMPANY_LIST, $row[3]);
        array_push($TR_Q_LIST, $row[4]);
        array_push($TR_DID_LIST, $row[5]);

        if ($INDEX == 0) {
            $INFO->tr_option = $row[0] ?? 'N';
            $INFO->tr_time_first = $row[1] ?? 10;
            $INFO->tr_time_second = $row[2] ?? 10;
        }
        $INDEX++;
    }

    $INFO->tr_q_list = $TR_Q_LIST;
    $INFO->tr_did_list = $TR_DID_LIST;
    $INFO->tr_company_list = $TR_COMPANY_LIST;
    $INFO->tr_count = count($TR_Q_LIST);

    SLOG(sprintf('[CC_GET_QLIST %s:%s] Found %d Qs: %s', $ORIGINAL_DID, $CID, $INFO->tr_count, implode(',', $TR_Q_LIST)));

    // TARGET_Q의 위치(TR_ORDER) 찾기
    $target_q_trimmed = ltrim($TARGET_Q, '0');  // 앞의 0 제거 (800 -> 800, 080 -> 80)
    $INFO->tr_order = 1;  // 기본값

    for ($i = 0; $i < count($TR_Q_LIST); $i++) {
        $q_trimmed = ltrim($TR_Q_LIST[$i], '0');
        if ($q_trimmed == $target_q_trimmed || $TR_Q_LIST[$i] == $TARGET_Q) {
            $INFO->tr_order = $i + 1;  // 1-based index
            SLOG(sprintf('[CC_GET_QLIST %s:%s] TARGET_Q=%s found at position %d', $ORIGINAL_DID, $CID, $TARGET_Q, $INFO->tr_order));
            break;
        }
    }

    return $INFO;
}

function check_crosscall_prefix($DID, $CID)
{
    SLOG(sprintf('[CHECK_CROSSCALL %s:%s] START =======================================================================================================', $DID, $CID));

    $RESULT = new stdClass();

    // PHP 8.2 Fix: Initialize all result properties
    $RESULT->IS_CROSSCALL = 'N';
    $RESULT->CROSSCALL_TYPE = '';  // INCOMING or empty (v1.4.0: 모든 크로스콜은 동일한 형식)
    $RESULT->ORIGINAL_DID = $DID;  // 기본값은 입력된 DID 그대로
    $RESULT->TARGET_Q = '';        // v1.4.0: 현재 장비에서 시도할 Q
    $RESULT->NEXT_Q = '';          // v1.4.0: TARGET_Q 다음에 시도할 Q (QList 순서)
    $RESULT->ORIGIN_Q = '';        // 하위 호환용 (NEXT_Q와 동일)
    $RESULT->RETURN_PREFIX = '';   // 하위 호환용
    $RESULT->PREFIX = '';
    $RESULT->RAW_DID = $DID;

    // 회사 정보 기본값
    $RESULT->company_id = 0;
    $RESULT->master_id = 0;
    $RESULT->allocQ = '';
    $RESULT->use_crosscall = 'N';
    $RESULT->tr_count = 0;
    $RESULT->tr_option = 'N';
    $RESULT->tr_time_first = 10;
    $RESULT->tr_time_second = 10;
    $RESULT->tr_q_list = array();
    $RESULT->tr_did_list = array();
    $RESULT->tr_order = 1;
    $RESULT->is_cc_workcondition = 'END';
    $RESULT->get_company = 'fail';

    // 다음 Q 정보 (크로스콜 처리용)
    $RESULT->next_q = '';
    $RESULT->next_did = '';
    $RESULT->next_q_pbx_id = '0';
    $RESULT->is_next_crosscall = 'N';
    $RESULT->next_crosscall_dial = '';
    $RESULT->my_pbx_id = '0';
    $RESULT->source_pbx_id = '0';  // v1.3.0: 송신 장비의 pbx_id

    // DB 연결
    $conn = mysqli_connect(
        '121.254.239.50',
        'nautes',
        'Nautes12@$',
        'LOGI',
        '3306'
    );

    // PHP 8.2 Fix: Check connection error
    if (!$conn) {
        SLOG(sprintf('[CHECK_CROSSCALL %s:%s] DB Connection failed: %s', $DID, $CID, mysqli_connect_error()));
        return $RESULT;
    }

    // PHP 8.2 Fix: Set character encoding
    mysqli_query($conn, "SET SESSION character_set_connection=utf8;");
    mysqli_query($conn, "SET SESSION character_set_results=utf8;");
    mysqli_query($conn, "SET SESSION character_set_client=utf8;");

    // v1.3.0: IP 기반 자기 PBX 설정 조회
    $my_pbx_config = get_my_pbx_config($conn);
    SLOG(sprintf('[CHECK_CROSSCALL %s:%s] My PBX: pbx_id=%s, server_ip=%s, crosscall_prefix=%s, return_prefix=%s',
        $DID, $CID, $my_pbx_config->pbx_id, $my_pbx_config->server_ip,
        $my_pbx_config->crosscall_prefix, $my_pbx_config->return_prefix));

    // v1.3.0: parse_crosscall_did()로 동적 prefix 파싱
    // v1.4.0: 통일된 형식 - 모든 크로스콜은 crosscall_prefix + DID + TARGET_Q(3) + NEXT_Q(3)
    $parsed = parse_crosscall_did($conn, $DID);

    if ($parsed !== null && $parsed['type'] === 'crosscall') {
        // 크로스콜 수신! (90/91/92 등 prefix)
        // v1.4.0 통일된 형식: prefix + DID + TARGET_Q(3) + NEXT_Q(3)
        // - TARGET_Q: 현재 장비에서 시도할 Q
        // - NEXT_Q: TARGET_Q 다음에 시도할 Q (QList 순서)
        $RESULT->IS_CROSSCALL = 'Y';
        $RESULT->CROSSCALL_TYPE = 'INCOMING';
        $RESULT->PREFIX = $parsed['prefix'];

        $RESULT->TARGET_Q = $parsed['target_q'];        // 현재 장비에서 시도할 Q
        $RESULT->NEXT_Q = $parsed['next_q'];            // TARGET_Q 다음에 시도할 Q
        $RESULT->ORIGIN_Q = $parsed['origin_q'];        // 하위 호환용 (NEXT_Q와 동일)
        $RESULT->ORIGINAL_DID = $parsed['original_did'];
        $RESULT->source_pbx_id = $parsed['source_pbx_id'];

        // v1.4.0: RETURN_PREFIX는 하위 호환용으로 유지하지만 실제로는 사용 안함
        $source_prefix_info = get_target_pbx_prefix($conn, $parsed['source_pbx_id']);
        $RESULT->RETURN_PREFIX = $source_prefix_info->crosscall_prefix ?? '99';

        SLOG(sprintf('[CHECK_CROSSCALL %s:%s] CROSSCALL INCOMING! prefix=%s, Original DID=%s, Target Q=%s, Next Q=%s, source_pbx_id=%s',
            $DID, $CID, $parsed['prefix'], $RESULT->ORIGINAL_DID, $RESULT->TARGET_Q, $RESULT->NEXT_Q, $parsed['source_pbx_id']));

        // 회사 정보 및 QList 조회
        $company_info = get_company_info_for_crosscall($conn, $RESULT->ORIGINAL_DID, $CID, $RESULT->TARGET_Q);

        $RESULT->company_id = $company_info->company_id;
        $RESULT->master_id = $company_info->master_id;
        $RESULT->allocQ = $company_info->allocQ;
        $RESULT->use_crosscall = $company_info->use_crosscall;
        $RESULT->tr_count = $company_info->tr_count;
        $RESULT->tr_option = $company_info->tr_option;
        $RESULT->tr_time_first = $company_info->tr_time_first;
        $RESULT->tr_time_second = $company_info->tr_time_second;
        $RESULT->tr_q_list = $company_info->tr_q_list;
        $RESULT->tr_did_list = $company_info->tr_did_list;
        $RESULT->tr_order = $company_info->tr_order;
        $RESULT->is_cc_workcondition = $company_info->is_cc_workcondition;
        $RESULT->get_company = $company_info->get_company;

        // 현재 TARGET_Q의 pbx_id 조회
        $RESULT->target_q_pbx_id = get_pbx_id_for_q_cc($conn, $RESULT->TARGET_Q);

        // v1.3.0: 자기 장비 pbx_id (IP 기반)
        $RESULT->my_pbx_id = $my_pbx_config->pbx_id;

        // v1.4.0: 다음 Q 정보는 이미 NEXT_Q에 포함되어 있음
        // TARGET_Q 실패 시 getQStep2.php에서 NEXT_Q를 사용하여 다음 크로스콜 생성
        $RESULT->next_q = $RESULT->NEXT_Q;
        $RESULT->next_q_pbx_id = get_pbx_id_for_q_cc($conn, $RESULT->next_q);

        // 다음 Q용 DID 찾기
        $next_q_order = -1;
        for ($i = 0; $i < count($RESULT->tr_q_list); $i++) {
            if ($RESULT->tr_q_list[$i] == $RESULT->next_q || ltrim($RESULT->tr_q_list[$i], '0') == ltrim($RESULT->next_q, '0')) {
                $next_q_order = $i;
                break;
            }
        }
        $RESULT->next_did = ($next_q_order >= 0 && isset($RESULT->tr_did_list[$next_q_order]))
            ? $RESULT->tr_did_list[$next_q_order]
            : $RESULT->ORIGINAL_DID;

        // 다음 Q가 다른 장비인지 미리 확인 (참고용)
        if ($RESULT->my_pbx_id != '0' && $RESULT->next_q_pbx_id != '0' &&
            $RESULT->my_pbx_id != $RESULT->next_q_pbx_id) {
            $RESULT->is_next_crosscall = 'Y';

            // v1.4.0: 다음 크로스콜 미리 계산 - 통일된 형식 사용
            // NEXT_Q 다음에 올 Q를 찾아서 next_crosscall_dial 생성
            $next_next_q_order = ($next_q_order + 1) % $RESULT->tr_count;
            $next_next_q = $RESULT->tr_q_list[$next_next_q_order];
            $RESULT->next_crosscall_dial = generate_crosscall_dial($conn, $RESULT->ORIGINAL_DID, $RESULT->next_q, $next_next_q, $RESULT->next_q_pbx_id);
        }

        SLOG(sprintf('[CHECK_CROSSCALL %s:%s] Next Q=%s, pbx_id=%s, my_pbx_id=%s, is_next_crosscall=%s, next_crosscall_dial=%s',
            $DID, $CID, $RESULT->next_q, $RESULT->next_q_pbx_id, $RESULT->my_pbx_id, $RESULT->is_next_crosscall, $RESULT->next_crosscall_dial));

    } else {
        // 일반 호
        SLOG(sprintf('[CHECK_CROSSCALL %s:%s] NOT a crosscall, normal call flow', $DID, $CID));
    }

    // PHP 8.2 Fix: Close connection safely
    if ($conn) {
        mysqli_close($conn);
    }

    SLOG(sprintf('[CHECK_CROSSCALL %s:%s] END =======================================================================================================', $DID, $CID));

    return $RESULT;
}

// ============================================================================
// Main API Handler
// ============================================================================

$RAW_POST_DATA = file_get_contents("php://input");

$args = new stdClass();
if (strlen($RAW_POST_DATA) > 0) {
    $args->JSON_REQUEST = $RAW_POST_DATA;
} else {
    $args = json_decode(json_encode($_REQUEST), FALSE);
}

$JSON_API_RESULT = new stdClass();
$JSON_API_RESULT->JSON_REQUEST = null;
$JSON_API_RESULT->JSON_RESULT = new stdClass();
$JSON_API_RESULT->JSON_RESULT->CODE = 200;
$JSON_API_RESULT->JSON_RESULT->MESSAGE = "0";

if (isset($args->JSON_REQUEST)) {
    $JSON_REQUEST = json_decode($args->JSON_REQUEST);
    if (!is_object($JSON_REQUEST)) {
        $JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
    }

    if (is_object($JSON_REQUEST)) {
        $JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

        if (isset($JSON_REQUEST->REQ)) {
            if ($JSON_REQUEST->REQ == 'CHECK_CROSSCALL') {
                $JSON_API_RESULT->JSON_RESULT->CODE = "TRY_CALL_PROCEDURE";

                // PHP 8.2 Fix: Use null coalescing operator
                $DID = $JSON_REQUEST->DID ?? '';
                $CID = $JSON_REQUEST->CID ?? '';

                $JSON_API_RESULT->JSON_RESULT->MESSAGE = check_crosscall_prefix($DID, $CID);
            }
        } else {
            $JSON_API_RESULT->JSON_RESULT->CODE = "ERROR";
            $JSON_API_RESULT->JSON_RESULT->MESSAGE = "ATTRIBUTE REQ REQUIRED!";
        }
    } else {
        $JSON_API_RESULT->JSON_RESULT->CODE = "ERROR";
        $JSON_API_RESULT->JSON_RESULT->MESSAGE = "ARGUMENT JSON_REQUEST IS NOT VALID STRING OF JSON OBJECT";
    }
} else {
    $JSON_API_RESULT->JSON_RESULT->CODE = "ERROR";
    $JSON_API_RESULT->JSON_RESULT->MESSAGE = "ARGUMENT JSON_REQUEST NOT DEFINED";
}

// PHP 8.2 Fix: Clean output buffer before sending JSON
if (ob_get_level()) ob_clean();
header("Content-Type: application/json; charset=utf-8");
echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

?>
