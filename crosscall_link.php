<?php
/**
 * crosscall_link.php
 *
 * CrossCall 발신/수신 시 Original Call ID 연결을 위한 함수
 *
 * v1.0.0 - 2026-02-02 - Initial creation
 * v1.1.0 - 2026-02-02 - Changed key strategy to use original_did + cid for consistent lookup
 * v1.1.1 - 2026-02-02 - Added graceful error handling for missing table
 *
 * 사용:
 *   - 크로스콜 발신 시: save_crosscall_link($conn, $original_did, $cid, $linkedid, $call_id, $company_id)
 *   - 크로스콜 수신 시: get_crosscall_link($conn, $original_did, $cid)
 */

include_once 'plog.php';

/**
 * 크로스콜 발신 시 링크 정보 저장
 * @param mysqli $conn DB 연결
 * @param string $original_did 원본 DID 번호 (크로스콜 대상 DID)
 * @param string $cid 발신자 번호 (CallerID)
 * @param string $linkedid Asterisk linkedid
 * @param string $call_id 시스템 call_id (optional)
 * @param int $company_id 회사 ID (optional)
 * @return bool 성공 여부
 */
function save_crosscall_link($conn, $original_did, $cid, $linkedid, $call_id = '', $company_id = 0) {
    // 테이블이 없어도 크로스콜 기능에 영향 없도록 try-catch로 감싸기
    try {
        if (empty($original_did) || empty($cid) || empty($linkedid)) {
            SLOG(sprintf('[CROSSCALL_LINK] save: skipped (empty params) did=%s, cid=%s, linkedid=%s',
                $original_did, $cid, $linkedid));
            return false;
        }

        if (!$conn || mysqli_connect_errno()) {
            SLOG(sprintf('[CROSSCALL_LINK] save: skipped (no connection)'));
            return false;
        }

        $original_did = mysqli_real_escape_string($conn, $original_did);
        $cid = mysqli_real_escape_string($conn, $cid);
        $linkedid = mysqli_real_escape_string($conn, $linkedid);
        $call_id = mysqli_real_escape_string($conn, $call_id);
        $company_id = intval($company_id);

        // 키: original_did + cid (동일 발신자가 같은 번호로 건 콜 추적)
        $link_key = $original_did . '_' . $cid;

        // 기존 레코드 있으면 삭제 (동일 키)
        $sql_delete = "DELETE FROM T_CROSSCALL_LINK WHERE link_key = '$link_key'";
        @mysqli_query($conn, $sql_delete);  // Suppress error if table doesn't exist

        $sql = "INSERT INTO T_CROSSCALL_LINK (link_key, crosscall_did, original_linkedid, original_call_id, cid, company_id)
                VALUES ('$link_key', '$original_did', '$linkedid', '$call_id', '$cid', $company_id)";

        SLOG(sprintf('[CROSSCALL_LINK] save: %s', $sql));

        $result = @mysqli_query($conn, $sql);  // Suppress error if table doesn't exist
        if ($result) {
            SLOG(sprintf('[CROSSCALL_LINK] Saved: link_key=%s, did=%s, cid=%s, linkedid=%s',
                $link_key, $original_did, $cid, $linkedid));
            return true;
        } else {
            $error = mysqli_error($conn);
            if (strpos($error, "doesn't exist") !== false) {
                SLOG(sprintf('[CROSSCALL_LINK] Save skipped: T_CROSSCALL_LINK table not yet created'));
            } else if (!empty($error)) {
                SLOG(sprintf('[CROSSCALL_LINK] Save failed: %s', $error));
            }
            return false;
        }
    } catch (Exception $e) {
        SLOG(sprintf('[CROSSCALL_LINK] save exception: %s', $e->getMessage()));
        return false;
    }
}

/**
 * 크로스콜 수신 시 원본 링크 정보 조회
 * @param mysqli $conn DB 연결
 * @param string $original_did 원본 DID 번호 (크로스콜 대상 DID)
 * @param string $cid 발신자 번호 (CallerID)
 * @return object|null {original_linkedid, original_call_id, cid, company_id} 또는 null
 */
function get_crosscall_link($conn, $original_did, $cid) {
    // 테이블이 없어도 크로스콜 기능에 영향 없도록 try-catch로 감싸기
    try {
        if (empty($original_did) || empty($cid)) {
            SLOG(sprintf('[CROSSCALL_LINK] get: skipped (empty params) did=%s, cid=%s',
                $original_did, $cid));
            return null;
        }

        if (!$conn || mysqli_connect_errno()) {
            SLOG(sprintf('[CROSSCALL_LINK] get: skipped (no connection)'));
            return null;
        }

        $original_did = mysqli_real_escape_string($conn, $original_did);
        $cid = mysqli_real_escape_string($conn, $cid);

        // 키: original_did + cid
        $link_key = $original_did . '_' . $cid;

        $sql = "SELECT original_linkedid, original_call_id, cid, company_id
                FROM T_CROSSCALL_LINK
                WHERE link_key = '$link_key'
                ORDER BY created_at DESC LIMIT 1";

        SLOG(sprintf('[CROSSCALL_LINK] get: link_key=%s', $link_key));

        $result = @mysqli_query($conn, $sql);  // Suppress error if table doesn't exist
        if ($result && ($row = mysqli_fetch_object($result))) {
            SLOG(sprintf('[CROSSCALL_LINK] Found: link_key=%s, linkedid=%s, call_id=%s',
                $link_key, $row->original_linkedid, $row->original_call_id));
            return $row;
        }

        // Check if error was due to missing table
        $error = mysqli_error($conn);
        if (strpos($error, "doesn't exist") !== false) {
            SLOG(sprintf('[CROSSCALL_LINK] get: T_CROSSCALL_LINK table not yet created'));
        } else {
            SLOG(sprintf('[CROSSCALL_LINK] Not found: link_key=%s', $link_key));
        }
        return null;
    } catch (Exception $e) {
        SLOG(sprintf('[CROSSCALL_LINK] get exception: %s', $e->getMessage()));
        return null;
    }
}

/**
 * 오래된 링크 정보 정리 (24시간 이상)
 * @param mysqli $conn DB 연결
 * @return int 삭제된 행 수
 */
function cleanup_crosscall_link($conn) {
    try {
        if (!$conn || mysqli_connect_errno()) {
            return 0;
        }

        $sql = "DELETE FROM T_CROSSCALL_LINK WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        @mysqli_query($conn, $sql);
        $affected = mysqli_affected_rows($conn);
        if ($affected > 0) {
            SLOG(sprintf('[CROSSCALL_LINK] Cleanup: deleted %d old records', $affected));
        }
        return $affected;
    } catch (Exception $e) {
        return 0;
    }
}
?>
