#!/usr/bin/env php
<?php
/**
 * LOGI.SCN 시나리오를 arsauth_scenario DB에 업데이트하는 스크립트
 * Usage: php update_scenario_db.php
 */

// FreePBX DB 연결 정보
$dbhost = 'localhost';
$dbuser = 'freepbxuser';
$dbpass = '797c5fea036651828b56da7cf125a88a';
$dbname = 'asterisk';

// 시나리오 파일 경로
$scn_file = __DIR__ . '/LOGI.SCN';

// 시나리오 이름
$scn_name = 'LOGI_SCN';

echo "=== LOGI.SCN DB 업데이트 스크립트 ===\n\n";

// 파일 존재 확인
if (!file_exists($scn_file)) {
    die("ERROR: LOGI.SCN 파일을 찾을 수 없습니다: {$scn_file}\n");
}

// 파일 읽기
$scn_content = file_get_contents($scn_file);
if ($scn_content === false) {
    die("ERROR: LOGI.SCN 파일을 읽을 수 없습니다.\n");
}

echo "파일 크기: " . strlen($scn_content) . " bytes\n";

// JSON 유효성 검사
$json_check = json_decode($scn_content);
if ($json_check === null && json_last_error() !== JSON_ERROR_NONE) {
    die("ERROR: LOGI.SCN이 유효한 JSON 형식이 아닙니다: " . json_last_error_msg() . "\n");
}
echo "JSON 유효성: OK\n";

// DB 연결
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
if (!$conn) {
    die("ERROR: DB 연결 실패: " . mysqli_connect_error() . "\n");
}
// UTF-8 인코딩 설정
mysqli_set_charset($conn, 'utf8');
echo "DB 연결: OK (charset=utf8)\n";

// 기존 데이터 확인
$check_sql = "SELECT ID, SCENARIO_NAME, LENGTH(STR_JSON_SCENARIO) as scn_size FROM arsauth_scenario WHERE SCENARIO_NAME = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, 's', $scn_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$existing = mysqli_fetch_assoc($result);

if ($existing) {
    echo "기존 시나리오: ID={$existing['ID']}, 크기={$existing['scn_size']} bytes\n";

    // 업데이트
    $update_sql = "UPDATE arsauth_scenario SET STR_JSON_SCENARIO = ? WHERE SCENARIO_NAME = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, 'ss', $scn_content, $scn_name);

    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        echo "\n업데이트 완료: {$affected} row affected\n";
    } else {
        die("ERROR: 업데이트 실패: " . mysqli_error($conn) . "\n");
    }
} else {
    echo "기존 시나리오 없음 - 새로 삽입\n";

    // 삽입
    $insert_sql = "INSERT INTO arsauth_scenario (SCENARIO_NAME, STR_JSON_SCENARIO) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($stmt, 'ss', $scn_name, $scn_content);

    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($conn);
        echo "\n삽입 완료: ID={$new_id}\n";
    } else {
        die("ERROR: 삽입 실패: " . mysqli_error($conn) . "\n");
    }
}

// 검증
$verify_sql = "SELECT ID, SCENARIO_NAME, LENGTH(STR_JSON_SCENARIO) as scn_size FROM arsauth_scenario WHERE SCENARIO_NAME = ?";
$stmt = mysqli_prepare($conn, $verify_sql);
mysqli_stmt_bind_param($stmt, 's', $scn_name);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$verified = mysqli_fetch_assoc($result);

echo "\n=== 검증 ===\n";
echo "ID: {$verified['ID']}\n";
echo "SCENARIO_NAME: {$verified['SCENARIO_NAME']}\n";
echo "DB 크기: {$verified['scn_size']} bytes\n";
echo "파일 크기: " . strlen($scn_content) . " bytes\n";

if ($verified['scn_size'] == strlen($scn_content)) {
    echo "\n✓ 크기 일치 - 업데이트 성공!\n";
} else {
    echo "\n✗ 크기 불일치 - 확인 필요!\n";
}

mysqli_close($conn);
echo "\n완료.\n";
?>
