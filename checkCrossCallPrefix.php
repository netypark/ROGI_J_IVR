<?php
/**
 * checkCrossCallPrefix.php
 *
 * MODIFICATION HISTORY:
 * =====================
 * Date: 2026-02-02
 * Author: System Migration
 * Version: 1.6.3
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
 *   v1.6.0 - PEAK 시간대 상담원 가용 상태 체크
 *     - check_agent_available() 함수 추가
 *     - find_available_q_in_peak() 함수 추가
 *     - PEAK 시간에 TARGET_Q 상담원 체크
 *     - 가용 상담원 없으면 alternate_q 반환
 *   v1.6.1 - 상담원 쿼리 로그 형식 변경
 *     - getQStep2.php와 동일한 쿼리 형식 사용 (COUNT(*) 제거)
 *     - SELECT q.q_num ... group by q_num limit 1 형식
 *     - 로그 형식: [AGENT_CHECK DID:CID] 쿼리
 *   v1.6.2 - find_available_q_in_peak() 쿼리 변경
 *     - getQStep2.php GET_CC_SEQ와 동일한 쿼리 사용
 *     - T_SEQUENCE_TRANSFER_CALL + NEWLIST 형식
 *     - company_id 기반 조회
 *   v1.6.3 - 시간대별 상담원 조회 (PEAK/WORK/END 모두 지원)
 *     - find_available_q_in_qlist() 함수로 통합
 *     - PEAK: T_SEQUENCE_TRANSFER_CALL (GET_CC_SEQ)
 *     - WORK: T_MY_TRANSFER_CALL (GET_CC_SMY)
 *     - END:  T_DIRECT_TRANSFER_CALL (GET_CC_DIR)
 *     - getQStep2.php와 동일한 NEWLIST 순서 적용
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
include_once 'crosscall_link.php';  // 2026-02-02: CrossCall Original Linkedid 연결

/**
 * v1.6.1: 단일 Q 상담원 상태 체크 (TARGET_Q 용)
 * getQStep2.php와 동일한 쿼리 형식 사용
 * @param mysqli $conn DB 연결
 * @param string $q_num 체크할 Q 번호
 * @param string $DID DID (로그용)
 * @param string $CID CID (로그용)
 * @return bool 가용 상담원 있으면 true
 */
function check_agent_available($conn, $q_num, $DID = '', $CID = '') {
    if (empty($q_num) || $q_num == '0') {
        SLOG(sprintf('[AGENT_CHECK %s:%s] Q=%s is empty or 0, skipping', $DID, $CID, $q_num));
        return false;
    }

    // getQStep2.php GET_CC_SEQ MYQ 체크와 동일한 쿼리
    $sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='" . mysqli_real_escape_string($conn, $q_num) . "' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

    SLOG(sprintf('[AGENT_CHECK %s:%s] %s', $DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res))) {
        SLOG(sprintf('[AGENT_CHECK %s:%s] Q=%s has available agent (found q_num=%s)', $DID, $CID, $q_num, $row[0]));
        return true;
    }
    SLOG(sprintf('[AGENT_CHECK %s:%s] Q=%s no available agent', $DID, $CID, $q_num));
    return false;
}

/**
 * v1.6.3: 시간대별 QList에서 가용 Q 찾기
 * getQStep2.php와 동일한 쿼리 형식 사용 (NEWLIST)
 * - PEAK: T_SEQUENCE_TRANSFER_CALL
 * - WORK: T_MY_TRANSFER_CALL
 * - END:  T_DIRECT_TRANSFER_CALL
 * @param mysqli $conn DB 연결
 * @param int $company_id 회사 ID
 * @param array $tr_q_list Q 목록
 * @param int $start_order 시작 순서 (1-based)
 * @param string $workcondition 근무 상태 (PEAK/WORK/END)
 * @param string $DID DID (로그용)
 * @param string $CID CID (로그용)
 * @return array|null ['q' => Q번호, 'order' => 순서] 또는 null
 */
function find_available_q_in_qlist($conn, $company_id, $tr_q_list, $start_order = 1, $workcondition = 'PEAK', $DID = '', $CID = '') {
    $TRCOUNT = count($tr_q_list);
    if ($TRCOUNT == 0) {
        SLOG(sprintf('[FIND_AVAIL_Q %s:%s] QList is empty, returning null', $DID, $CID));
        return null;
    }

    // start_order가 범위를 벗어나면 1로 초기화
    if ($start_order <= 0 || $start_order > $TRCOUNT) {
        $start_order = 1;
    }

    // getQStep2.php와 동일: NEWLIST 생성 (start_order 위치부터 순환)
    $TRQLIST = '';
    for ($i = $start_order - 1; $i < $TRCOUNT; $i++) {
        $TRQLIST .= "'" . mysqli_real_escape_string($conn, $tr_q_list[$i]) . "',";
    }
    for ($j = 0; $j < $start_order - 1; $j++) {
        $TRQLIST .= "'" . mysqli_real_escape_string($conn, $tr_q_list[$j]) . "',";
    }
    $NEWLIST = rtrim($TRQLIST, ", ");

    SLOG(sprintf('[FIND_AVAIL_Q %s:%s] workcondition=%s, TRQLIST:%s', $DID, $CID, $workcondition, $NEWLIST));

    // 시간대별 테이블 선택 (getQStep2.php와 동일)
    switch ($workcondition) {
        case 'PEAK':
            // GET_CC_SEQ: T_SEQUENCE_TRANSFER_CALL
            $table = 'T_SEQUENCE_TRANSFER_CALL';
            break;
        case 'WORK':
            // GET_CC_SMY: T_MY_TRANSFER_CALL
            $table = 'T_MY_TRANSFER_CALL';
            break;
        case 'END':
        default:
            // GET_CC_DIR: T_DIRECT_TRANSFER_CALL
            $table = 'T_DIRECT_TRANSFER_CALL';
            break;
    }

    // getQStep2.php와 동일한 쿼리
    $sql = "select m.transfer_q_number, m.transfer_order_num from {$table} AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$company_id and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

    SLOG(sprintf('[FIND_AVAIL_Q %s:%s] %s', $DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res))) {
        $found_q = $row[0];
        $found_order = intval($row[1]);
        SLOG(sprintf('[FIND_AVAIL_Q %s:%s] Found available Q=%s at order %d (table=%s)', $DID, $CID, $found_q, $found_order, $table));
        return ['q' => $found_q, 'order' => $found_order];
    }

    SLOG(sprintf('[FIND_AVAIL_Q %s:%s] No available Q found, company_id=%d, table=%s', $DID, $CID, $company_id, $table));
    return null;
}

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
 * v1.5.0: 크로스콜 시나리오에서는 T_DID_RANGE 대신 T_COMPANY에서 직접 조회
 *         크로스콜은 원래 DID의 QList를 기반으로 동작하므로 T_COMPANY.did_number로 조회
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

    // v1.5.0: 크로스콜 시나리오 - T_COMPANY에서 직접 DID로 조회
    // 크로스콜은 원래 DID(07089982240)가 주최자이므로 T_DID_RANGE가 필요 없음
    $sql = "SELECT c.company_id, c.master_id, q.q_num as allocQ, c.pbx_id
            FROM T_COMPANY AS c
            LEFT JOIN T_QUEUE AS q ON c.master_id = q.master_id
            WHERE c.did_number = '" . mysqli_real_escape_string($conn, $ORIGINAL_DID) . "'
            LIMIT 1;";
    SLOG(sprintf('[CC_GET_COMPANY %s:%s] Direct T_COMPANY lookup: %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    $company_found = false;

    if ($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))) {
        $INFO->company_id = $row['company_id'];
        $INFO->master_id = $row['master_id'];
        $INFO->use_crosscall = 'Y';  // 크로스콜로 들어왔으므로 무조건 Y
        $INFO->allocQ = $row['allocQ'] ?? '';
        $INFO->my_pbx_id = $row['pbx_id'] ?? '0';
        $INFO->get_company = 'success';
        $company_found = true;

        SLOG(sprintf('[CC_GET_COMPANY %s:%s] Found in T_COMPANY: company_id=%s, master_id=%s, allocQ=%s',
            $ORIGINAL_DID, $CID, $INFO->company_id, $INFO->master_id, $INFO->allocQ));
    }

    // T_COMPANY에서 못찾으면 T_DID_RANGE fallback
    if (!$company_found) {
        $sql = "SELECT company_id, did_company_id, did_number FROM T_DID_RANGE WHERE did_number='" . mysqli_real_escape_string($conn, $ORIGINAL_DID) . "' LIMIT 1;";
        SLOG(sprintf('[CC_GET_COMPANY %s:%s] Fallback to T_DID_RANGE: %s', $ORIGINAL_DID, $CID, $sql));

        $res = mysqli_query($conn, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_array($res, MYSQLI_ASSOC);
            $did_company_id = (!empty($row['did_company_id']) && $row['did_company_id'] != '0')
                ? $row['did_company_id']
                : $row['company_id'];

            $sql = "SELECT c.company_id, c.master_id, q.q_num as allocQ, c.pbx_id
                    FROM T_COMPANY AS c
                    LEFT JOIN T_QUEUE AS q ON c.master_id = q.master_id
                    WHERE c.company_id = $did_company_id
                    LIMIT 1;";
            $res = mysqli_query($conn, $sql);
            if ($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))) {
                $INFO->company_id = $row['company_id'];
                $INFO->master_id = $row['master_id'];
                $INFO->use_crosscall = 'Y';
                $INFO->allocQ = $row['allocQ'] ?? '';
                $INFO->my_pbx_id = $row['pbx_id'] ?? '0';
                $INFO->get_company = 'success';
                $company_found = true;

                SLOG(sprintf('[CC_GET_COMPANY %s:%s] Found via T_DID_RANGE: company_id=%s',
                    $ORIGINAL_DID, $CID, $INFO->company_id));
            }
        }
    }

    if (!$company_found) {
        SLOG(sprintf('[CC_GET_COMPANY %s:%s] Company not found in T_COMPANY or T_DID_RANGE', $ORIGINAL_DID, $CID));
        return $INFO;
    }

    // v1.6.0: 크로스콜 근무시간 확인 - T_CC_WORKDAY_INFO 테이블 사용
    $CC_W_USE = 'N';
    $CC_W_START1 = '0000';
    $CC_W_END1 = '2359';
    $CC_W_START2 = '0000';
    $CC_W_END2 = '0000';
    $CC_P_USE = 'N';
    $CC_P_START1 = '0000';
    $CC_P_END1 = '0000';

    // T_CC_WORKDAY_INFO에서 오늘 요일에 해당하는 근무시간 조회
    $sql = "SELECT cw_is_use, cw_starttime1, cw_endtime1, cw_starttime2, cw_endtime2,
                   cw_peak_is_use, cw_peak_starttime1, cw_peak_endtime1
            FROM T_CC_WORKDAY_INFO
            WHERE cw_company_id = {$INFO->company_id}
              AND cw_kind = DAYOFWEEK(CURDATE())
              AND cw_is_use = 'Y'
            LIMIT 1;";
    SLOG(sprintf('[CC_GET_WKCOND %s:%s] %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res, MYSQLI_ASSOC))) {
        $CC_W_USE = $row['cw_is_use'] ?? 'N';
        $CC_W_START1 = $row['cw_starttime1'] ?? '0000';
        $CC_W_END1 = $row['cw_endtime1'] ?? '2359';
        $CC_W_START2 = $row['cw_starttime2'] ?? '0000';
        $CC_W_END2 = $row['cw_endtime2'] ?? '0000';
        $CC_P_USE = $row['cw_peak_is_use'] ?? 'N';
        $CC_P_START1 = $row['cw_peak_starttime1'] ?? '0000';
        $CC_P_END1 = $row['cw_peak_endtime1'] ?? '0000';
        SLOG(sprintf('[CC_GET_WKCOND %s:%s] Found: use=%s, work1=%s-%s, work2=%s-%s, peak=%s(%s-%s)',
            $ORIGINAL_DID, $CID, $CC_W_USE, $CC_W_START1, $CC_W_END1, $CC_W_START2, $CC_W_END2, $CC_P_USE, $CC_P_START1, $CC_P_END1));
    } else {
        SLOG(sprintf('[CC_GET_WKCOND %s:%s] No T_CC_WORKDAY_INFO record found for company_id=%s, dayofweek=%s',
            $ORIGINAL_DID, $CID, $INFO->company_id, date('w') + 1));
    }

    // 현재 시간 확인
    date_default_timezone_set('Asia/Seoul');
    $HOUR = date('H', time());
    $MIN = date('i', time());
    $Ttime = $HOUR . $MIN;

    // 근무시간 판정: END -> PEAK -> WORK 순서로 체크
    $INFO->is_cc_workcondition = 'END';

    if ($CC_W_USE == 'Y') {
        // PEAK 시간 체크
        if ($CC_P_USE == 'Y' && $Ttime >= $CC_P_START1 && $Ttime < $CC_P_END1) {
            $INFO->is_cc_workcondition = 'PEAK';
            SLOG(sprintf('[CC_GET_WKCOND %s:%s] PEAK time: %s (peak=%s-%s)', $ORIGINAL_DID, $CID, $Ttime, $CC_P_START1, $CC_P_END1));
        }
        // WORK 시간 체크 (시간대1)
        else if ($Ttime >= $CC_W_START1 && $Ttime < $CC_W_END1) {
            $INFO->is_cc_workcondition = 'WORK';
            SLOG(sprintf('[CC_GET_WKCOND %s:%s] WORK time (slot1): %s (work=%s-%s)', $ORIGINAL_DID, $CID, $Ttime, $CC_W_START1, $CC_W_END1));
        }
        // WORK 시간 체크 (시간대2 - 야간근무)
        else if ($CC_W_START2 != '0000' && $CC_W_END2 != '0000' && $Ttime >= $CC_W_START2 && $Ttime < $CC_W_END2) {
            $INFO->is_cc_workcondition = 'WORK';
            SLOG(sprintf('[CC_GET_WKCOND %s:%s] WORK time (slot2): %s (work=%s-%s)', $ORIGINAL_DID, $CID, $Ttime, $CC_W_START2, $CC_W_END2));
        }
        else {
            SLOG(sprintf('[CC_GET_WKCOND %s:%s] END time: %s (not in work hours)', $ORIGINAL_DID, $CID, $Ttime));
        }
    } else {
        SLOG(sprintf('[CC_GET_WKCOND %s:%s] END time: %s (cw_is_use=N or not found)', $ORIGINAL_DID, $CID, $Ttime));
    }

    // QList 조회 (work condition에 따라) - pbx_id 포함
    // transfer_company_id 기준으로 T_COMPANY에서 pbx_id 조회
    $pbx_join = "LEFT JOIN (
                    SELECT tc.company_id, tc.pbx_id
                    FROM T_COMPANY tc
                    INNER JOIN (SELECT company_id, MIN(id) AS min_id FROM T_COMPANY GROUP BY company_id) x
                    ON x.company_id = tc.company_id AND x.min_id = tc.id
                 ) AS c ON c.company_id = t.transfer_company_id";

    if ($INFO->is_cc_workcondition == 'WORK') {
        $sql = "SELECT s.receive_option, s.ring_wait_time_my, s.ring_wait_time_transfer,
                       t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, c.pbx_id
                FROM T_MY_TRANSFER_CALL AS t
                LEFT JOIN T_SET_MY AS s ON s.company_id = t.company_id
                {$pbx_join}
                WHERE t.company_id = {$INFO->company_id}
                ORDER BY t.transfer_order_num;";
    } elseif ($INFO->is_cc_workcondition == 'PEAK') {
        // PEAK 시간에는 T_SEQUENCE_TRANSFER_CALL 사용
        $sql = "SELECT s.receive_option1 AS receive_option,
                       s.recall_transfer_time AS ring_wait_time_my,
                       s.ring_wait_time_transfer,
                       t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, c.pbx_id
                FROM T_SEQUENCE_TRANSFER_CALL AS t
                INNER JOIN T_SET_SEQUENCE AS s ON s.company_id = t.company_id
                {$pbx_join}
                WHERE t.company_id = {$INFO->company_id}
                ORDER BY t.transfer_order_num;";
    } else {
        // END 시간에는 T_DIRECT_TRANSFER_CALL 사용
        $sql = "SELECT s.receive_option,
                       (SELECT ring_wait_time_my FROM T_SET_MY WHERE company_id={$INFO->company_id} ORDER BY id ASC LIMIT 1) as _MY,
                       s.ring_wait_time_transfer,
                       t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, c.pbx_id
                FROM T_DIRECT_TRANSFER_CALL AS t
                LEFT JOIN T_SET_DIRECT AS s ON s.company_id = t.company_id
                {$pbx_join}
                WHERE t.company_id = {$INFO->company_id}
                ORDER BY t.transfer_order_num;";
    }

    SLOG(sprintf('[CC_GET_QLIST %s:%s] %s', $ORIGINAL_DID, $CID, $sql));

    $res = mysqli_query($conn, $sql);
    $TR_Q_LIST = array();
    $TR_DID_LIST = array();
    $TR_COMPANY_LIST = array();
    $TR_PBX_ID_LIST = array();
    $INDEX = 0;

    while ($res && ($row = mysqli_fetch_array($res, MYSQLI_NUM))) {
        array_push($TR_COMPANY_LIST, $row[3]);
        array_push($TR_Q_LIST, $row[4]);
        array_push($TR_DID_LIST, $row[5]);
        array_push($TR_PBX_ID_LIST, $row[7] ?? '1');  // pbx_id (default: 1)

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
    $INFO->tr_pbx_id_list = $TR_PBX_ID_LIST;
    $INFO->tr_count = count($TR_Q_LIST);

    SLOG(sprintf('[CC_GET_QLIST %s:%s] Found %d Qs: %s', $ORIGINAL_DID, $CID, $INFO->tr_count, implode(',', $TR_Q_LIST)));

    // TARGET_Q의 위치(TR_ORDER) 찾기
    $target_q_trimmed = ltrim($TARGET_Q, '0');  // 앞의 0 제거 (800 -> 800, 080 -> 80)
    $INFO->tr_order = 1;  // 기본값

    for ($i = 0; $i < count($TR_Q_LIST); $i++) {
        $q_trimmed = ltrim($TR_Q_LIST[$i], '0');
        if ($q_trimmed == $target_q_trimmed || $TR_Q_LIST[$i] == $TARGET_Q) {
            // v1.7.2: TARGET_Q로 REAL_DIAL_AGENT 후 noanswer 시 다음 Q로 진행하기 위해
            // tr_order를 현재 위치 + 1로 설정 (Q900=3이면 tr_order=4)
            // 이렇게 하면 noanswer 시 getQStep2.php에서 4 > 3 → 1로 순환하여 Q700 선택
            $INFO->tr_order = $i + 2;  // 1-based index + 1 (다음 순서)
            SLOG(sprintf('[CC_GET_QLIST %s:%s] TARGET_Q=%s found at position %d, tr_order set to %d (next position)',
                $ORIGINAL_DID, $CID, $TARGET_Q, $i + 1, $INFO->tr_order));
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
    $RESULT->tr_pbx_id_list = array();
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

    // v1.6.0: PEAK 시간 상담원 가용 체크 결과
    $RESULT->target_agent_available = 'N';
    $RESULT->alternate_q = '';
    $RESULT->alternate_q_order = 0;

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
    SLOG(sprintf('[CHECK_CROSSCALL %s:%s] My PBX: pbx_id=%s, server_ip=%s, crosscall_prefix=%s',
        $DID, $CID, $my_pbx_config->pbx_id, $my_pbx_config->server_ip,
        $my_pbx_config->crosscall_prefix));

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

        // 2026-02-02: Original Linkedid 조회 (key = ORIGINAL_DID + CID)
        $crosscall_link = get_crosscall_link($conn, $RESULT->ORIGINAL_DID, $CID);
        $RESULT->original_linkedid = $crosscall_link ? $crosscall_link->original_linkedid : '';
        $RESULT->original_call_id = $crosscall_link ? $crosscall_link->original_call_id : '';
        SLOG(sprintf('[CHECK_CROSSCALL %s:%s] Original Linkedid lookup: key=%s_%s, linkedid=%s, call_id=%s',
            $DID, $CID, $RESULT->ORIGINAL_DID, $CID, $RESULT->original_linkedid, $RESULT->original_call_id));

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
        $RESULT->tr_pbx_id_list = $company_info->tr_pbx_id_list ?? array();
        $RESULT->tr_order = $company_info->tr_order;
        $RESULT->is_cc_workcondition = $company_info->is_cc_workcondition;
        $RESULT->get_company = $company_info->get_company;

        // 현재 TARGET_Q의 pbx_id 조회
        $RESULT->target_q_pbx_id = get_pbx_id_for_q_cc($conn, $RESULT->TARGET_Q);

        // v1.5.1: 크로스콜 수신 시, IP 기반 lookup 대신 target_q_pbx_id 사용
        // 이유: localhost(127.0.0.1)가 T_PBX_CONFIG에서 다른 장비로 매핑될 수 있음
        // 크로스콜 prefix가 이미 대상 장비를 식별하므로 target_q_pbx_id가 신뢰할 수 있음
        // TARGET_Q는 현재 장비에서 처리할 Q이므로 target_q_pbx_id = 현재 장비의 pbx_id
        if ($RESULT->target_q_pbx_id != '0') {
            $RESULT->my_pbx_id = $RESULT->target_q_pbx_id;
            SLOG(sprintf('[CHECK_CROSSCALL %s:%s] CROSSCALL: using target_q_pbx_id=%s as my_pbx_id (IP-based was %s)',
                $DID, $CID, $RESULT->my_pbx_id, $my_pbx_config->pbx_id));
        } else {
            $RESULT->my_pbx_id = $my_pbx_config->pbx_id;
        }

        // v1.6.3: 시간대별 상담원 가용 상태 체크 (PEAK/WORK/END)
        // getQStep2.php와 동일하게 가용 상담원이 있는 Q를 우선 선택
        $RESULT->target_agent_available = 'N';
        $RESULT->alternate_q = '';
        $RESULT->alternate_q_order = 0;

        // PEAK, WORK, END 모두 상담원 상태 체크
        if (in_array($RESULT->is_cc_workcondition, ['PEAK', 'WORK', 'END'])) {
            SLOG(sprintf('[CHECK_CROSSCALL %s:%s] %s time - checking agent availability for TARGET_Q=%s',
                $DID, $CID, $RESULT->is_cc_workcondition, $RESULT->TARGET_Q));

            // v1.7.3: 단일 쿼리로 TARGET_Q와 alternate Q 동시 검색
            // tr_order-1은 TARGET_Q의 위치 (tr_order는 다음 순서이므로 -1)
            // ORDER BY FIELD로 TARGET_Q부터 순서대로 검색하여 첫 번째 가용 Q 반환
            $target_q_position = $RESULT->tr_order - 1;  // TARGET_Q 위치 (1-based)
            if ($target_q_position <= 0) $target_q_position = count($RESULT->tr_q_list);

            $found_q = find_available_q_in_qlist(
                $conn,
                $RESULT->company_id,
                $RESULT->tr_q_list,
                $target_q_position,  // TARGET_Q 위치부터 검색
                $RESULT->is_cc_workcondition,
                $DID,
                $CID
            );

            if ($found_q !== null) {
                $found_q_trimmed = ltrim($found_q['q'], '0');
                $target_q_trimmed = ltrim($RESULT->TARGET_Q, '0');

                if ($found_q_trimmed == $target_q_trimmed || $found_q['q'] == $RESULT->TARGET_Q) {
                    // TARGET_Q가 가용 → 정상 진행
                    $RESULT->target_agent_available = 'Y';
                    SLOG(sprintf('[CHECK_CROSSCALL %s:%s] %s: TARGET_Q=%s has available agents, tr_order=%d (single query)',
                        $DID, $CID, $RESULT->is_cc_workcondition, $RESULT->TARGET_Q, $RESULT->tr_order));
                } else {
                    // TARGET_Q 가용 없음, 다른 Q 발견
                    $RESULT->alternate_q = $found_q['q'];
                    $RESULT->alternate_q_order = $found_q['order'];
                    SLOG(sprintf('[CHECK_CROSSCALL %s:%s] %s: TARGET_Q=%s NO agents, alternate Q=%s, tr_order=%d (single query)',
                        $DID, $CID, $RESULT->is_cc_workcondition, $RESULT->TARGET_Q, $RESULT->alternate_q, $RESULT->tr_order));
                }
            } else {
                // 전체 Q 중 가용 없음
                SLOG(sprintf('[CHECK_CROSSCALL %s:%s] %s: No available Q in my_pbx_id=%s, tr_order=%d, will try TARGET_Q anyway or crosscall',
                    $DID, $CID, $RESULT->is_cc_workcondition, $RESULT->my_pbx_id, $RESULT->tr_order));
            }
        }

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
            // PHP 8.2 Fix: tr_count가 0인 경우 division by zero 방지
            if ($RESULT->tr_count > 0 && $next_q_order >= 0) {
                $next_next_q_order = ($next_q_order + 1) % $RESULT->tr_count;
                $next_next_q = $RESULT->tr_q_list[$next_next_q_order] ?? $RESULT->NEXT_Q;
                $RESULT->next_crosscall_dial = generate_crosscall_dial($conn, $RESULT->ORIGINAL_DID, $RESULT->next_q, $next_next_q, $RESULT->next_q_pbx_id);
            }
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
