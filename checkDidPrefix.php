<?php
/**
 * checkDidPrefix.php
 *
 * MODIFICATION HISTORY:
 * =====================
 * Date: 2026-01-28
 * Author: System Migration
 * Version: 1.0.0
 * Changes: Initial creation for DID Prefix routing
 *   - DID 앞 4자리(prefix)를 추출하여 T_DID_PREFIX 테이블에서 매칭 조회
 *   - 매칭 시 target_type, target_number 반환
 *   - PHP 8.2 호환성 적용
 *
 * Environment: Rocky Linux 9.6, PHP 8.2.30, Asterisk 20.17.0
 *
 * REQUEST:
 *   POST /API/checkDidPrefix.php
 *   Content-Type: application/json
 *   {"REQ":"CHECK_DID_PREFIX", "DID":"07089982240", "CID":"01012345678"}
 *
 * RESPONSE:
 *   {
 *     "JSON_REQUEST": {...},
 *     "JSON_RESULT": {
 *       "CODE": "TRY_CALL_PROCEDURE",
 *       "MESSAGE": {
 *         "MATCHED": "Y",
 *         "DID_PREFIX": "0708",
 *         "TARGET_TYPE": "Q",
 *         "TARGET_NUMBER": "900",
 *         "DESCRIPTION": "0708 prefix -> 900번 큐"
 *       }
 *     }
 *   }
 */

include 'plog.php';

function check_did_prefix($DID, $CID)
{
    SLOG(sprintf('[CHECK_PREFIX %s:%s] START =======================================================================================================', $DID, $CID));

    $RESULT = new stdClass();

    // PHP 8.2 Fix: Initialize all result properties
    $RESULT->MATCHED = 'N';
    $RESULT->DID_PREFIX = '';
    $RESULT->TARGET_TYPE = '';
    $RESULT->TARGET_NUMBER = '';
    $RESULT->TARGET_SCENARIO = '';
    $RESULT->DESCRIPTION = '';

    // DID 앞 4자리 추출
    $DID_PREFIX = substr($DID, 0, 4);
    $RESULT->DID_PREFIX = $DID_PREFIX;

    SLOG(sprintf('[CHECK_PREFIX %s:%s] Extracted prefix: %s', $DID, $CID, $DID_PREFIX));

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
        SLOG(sprintf('[CHECK_PREFIX %s:%s] DB Connection failed: %s', $DID, $CID, mysqli_connect_error()));
        return $RESULT;
    }

    // PHP 8.2 Fix: Set character encoding
    if ($conn) {
        mysqli_query($conn, "SET SESSION character_set_connection=utf8;");
        mysqli_query($conn, "SET SESSION character_set_results=utf8;");
        mysqli_query($conn, "SET SESSION character_set_client=utf8;");
    }

    // T_DID_PREFIX 테이블에서 매칭 조회
    $sql = "SELECT target_type, target_number, target_scenario, description
            FROM T_DID_PREFIX
            WHERE did_prefix=? AND is_active='Y'
            ORDER BY priority
            LIMIT 1;";

    // PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
    $sql_log = str_replace('?', "'" . $DID_PREFIX . "'", $sql);
    SLOG(sprintf('[CHECK_PREFIX %s:%s] %s', $DID, $CID, $sql_log));

    // PHP 8.2 Fix: Prepared statement with error handling
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        SLOG(sprintf('[CHECK_PREFIX %s:%s] prepare() failed: %s', $DID, $CID, $conn->error));
        mysqli_close($conn);
        return $RESULT;
    }

    $stmt->bind_param("s", $DID_PREFIX);
    $stmt->execute();
    $res = $stmt->get_result();

    // PHP 8.2 Fix: Check result before fetch
    if ($res && ($row = $res->fetch_array(MYSQLI_ASSOC))) {
        $RESULT->MATCHED = 'Y';
        $RESULT->TARGET_TYPE = $row['target_type'] ?? '';
        $RESULT->TARGET_NUMBER = $row['target_number'] ?? '';
        $RESULT->TARGET_SCENARIO = $row['target_scenario'] ?? '';
        $RESULT->DESCRIPTION = $row['description'] ?? '';

        SLOG(sprintf('[CHECK_PREFIX %s:%s] MATCHED! Type:%s Number:%s Desc:%s',
            $DID, $CID, $RESULT->TARGET_TYPE, $RESULT->TARGET_NUMBER, $RESULT->DESCRIPTION));
    } else {
        SLOG(sprintf('[CHECK_PREFIX %s:%s] NOT MATCHED for prefix: %s', $DID, $CID, $DID_PREFIX));
    }

    // PHP 8.2 Fix: Close statement
    if ($stmt !== false) {
        $stmt->close();
    }

    // PHP 8.2 Fix: Close connection safely
    if ($conn) {
        mysqli_close($conn);
    }

    SLOG(sprintf('[CHECK_PREFIX %s:%s] END =======================================================================================================', $DID, $CID));

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
            if ($JSON_REQUEST->REQ == 'CHECK_DID_PREFIX') {
                $JSON_API_RESULT->JSON_RESULT->CODE = "TRY_CALL_PROCEDURE";

                // PHP 8.2 Fix: Use null coalescing operator for missing properties
                $DID = $JSON_REQUEST->DID ?? '';
                $CID = $JSON_REQUEST->CID ?? '';

                $JSON_API_RESULT->JSON_RESULT->MESSAGE = check_did_prefix($DID, $CID);
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
