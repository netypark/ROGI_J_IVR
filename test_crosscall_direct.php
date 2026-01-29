<?php
/**
 * Direct CrossCall Test Script
 * Tests the crosscall functions directly without HTTP
 *
 * v1.1.0 - IP 기반 PBX 설정 테스트 추가 (T_PBX_CONFIG)
 * v1.2.0 - 통일된 크로스콜 형식 테스트 (TARGET_Q + NEXT_Q)
 * v1.3.0 - return_prefix 컬럼 삭제 반영
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'plog.php';
include_once 'pbx_config.php';

echo "============================================================================\n";
echo "CrossCall Direct Test Suite (v1.3.0 - return_prefix removed)\n";
echo "============================================================================\n\n";

// Database connection
$conn = mysqli_connect("121.254.239.50", "nautes", "Nautes12@$", "LOGI", "3306");
if (!$conn) {
    echo "DB connection failed: " . mysqli_connect_error() . "\n";
    exit;
}

mysqli_query($conn, "SET SESSION character_set_connection=utf8;");
mysqli_query($conn, "SET SESSION character_set_results=utf8;");
mysqli_query($conn, "SET SESSION character_set_client=utf8;");

// Helper function
function get_pbx_id_for_q($conn, $q_num) {
    if (empty($q_num) || $q_num == "0") {
        return "0";
    }
    $sql = "SELECT c.pbx_id FROM T_QUEUE AS q INNER JOIN T_COMPANY AS c ON q.master_id = c.master_id AND c.company_level = 0 WHERE q.q_num = '$q_num' LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && ($row = mysqli_fetch_array($res))) {
        return $row[0] ?? "0";
    }
    return "0";
}

// ============================================================================
// Test 1: T_PBX_CONFIG Table Check
// ============================================================================
echo "=== TEST 1: T_PBX_CONFIG Table Check ===\n";

$sql = "SELECT seq, pbx_id, pbx_name, server_ip, server_name, crosscall_prefix
        FROM T_PBX_CONFIG
        WHERE is_active = 'Y'
        ORDER BY pbx_id, seq";
$res = mysqli_query($conn, $sql);

if (!$res) {
    echo "ERROR: T_PBX_CONFIG table not found or query failed.\n";
    echo "Please run: mysql -u nautes -p LOGI < sql/create_t_pbx_config.sql\n\n";
} else {
    $count = mysqli_num_rows($res);
    echo "Found $count active PBX configurations:\n";
    echo str_repeat("-", 70) . "\n";
    printf("%-6s %-10s %-18s %-20s %-8s\n",
        "pbx_id", "pbx_name", "server_ip", "server_name", "prefix");
    echo str_repeat("-", 70) . "\n";

    while ($row = mysqli_fetch_assoc($res)) {
        printf("%-6s %-10s %-18s %-20s %-8s\n",
            $row['pbx_id'], $row['pbx_name'], $row['server_ip'],
            $row['server_name'], $row['crosscall_prefix']);
    }
    echo "\n";
}

// ============================================================================
// Test 2: IP-based My PBX Detection (pbx_config.php functions)
// ============================================================================
echo "=== TEST 2: IP-based My PBX Detection ===\n";

// Reset cache for testing
reset_pbx_config_cache();

$my_ip = get_my_server_ip();
echo "My Server IP: $my_ip\n";

$my_config = get_my_pbx_config($conn);
echo "My PBX Config:\n";
echo "  - pbx_id: " . $my_config->pbx_id . "\n";
echo "  - pbx_name: " . $my_config->pbx_name . "\n";
echo "  - server_name: " . $my_config->server_name . "\n";
echo "  - crosscall_prefix: " . $my_config->crosscall_prefix . "\n";
echo "  - return_prefix: " . $my_config->return_prefix . "\n";
echo "\n";

// ============================================================================
// Test 3: Target PBX Prefix Lookup
// ============================================================================
echo "=== TEST 3: Target PBX Prefix Lookup ===\n";

echo "Prefix configuration by pbx_id:\n";
for ($i = 1; $i <= 3; $i++) {
    $prefix_info = get_target_pbx_prefix($conn, $i);
    echo "  pbx_id=$i: crosscall_prefix=" . $prefix_info->crosscall_prefix .
         ", return_prefix=" . $prefix_info->return_prefix . "\n";
}
echo "\n";

// ============================================================================
// Test 4: Queue pbx_id Mapping
// ============================================================================
echo "=== TEST 4: Queue pbx_id Mapping ===\n";
echo "Q700 pbx_id: " . get_pbx_id_for_q($conn, "700") . " (Expected: 1 = A장비)\n";
echo "Q800 pbx_id: " . get_pbx_id_for_q($conn, "800") . " (Expected: 2 = B장비)\n";
echo "Q900 pbx_id: " . get_pbx_id_for_q($conn, "900") . " (Expected: 1 = A장비)\n\n";

// ============================================================================
// Test 5: Dynamic Crosscall Dial Generation (v1.2.0 통일된 형식)
// ============================================================================
echo "=== TEST 5: Dynamic Crosscall Dial Generation (Unified Format) ===\n";

$DID = "07089984200";
$TARGET_Q = "800";  // B장비에서 시도할 Q
$NEXT_Q = "700";    // TARGET_Q 다음에 시도할 Q (QList 순서)
$TARGET_PBX_ID = 2;

$crosscall_dial = generate_crosscall_dial($conn, $DID, $TARGET_Q, $NEXT_Q, $TARGET_PBX_ID);
echo "A장비 -> B장비(Q800) 크로스콜 (통일된 형식):\n";
echo "  DID: $DID\n";
echo "  TARGET_Q: $TARGET_Q (B장비에서 시도할 Q)\n";
echo "  NEXT_Q: $NEXT_Q (TARGET_Q 다음에 시도할 Q)\n";
echo "  Generated crosscall_dial: $crosscall_dial\n";
echo "  Expected format: 91 + $DID + 800 + 700 = 91${DID}800700\n\n";

// ============================================================================
// Test 6: Crosscall from B장비 to C장비 (같은 통일된 형식)
// ============================================================================
echo "=== TEST 6: Crosscall B장비 -> C장비 (Unified Format) ===\n";

$TARGET_Q2 = "700";  // C장비에서 시도할 Q
$NEXT_Q2 = "900";    // TARGET_Q2 다음에 시도할 Q (QList 순서)
$TARGET_PBX_ID2 = 3; // C장비

$crosscall_dial2 = generate_crosscall_dial($conn, $DID, $TARGET_Q2, $NEXT_Q2, $TARGET_PBX_ID2);
echo "B장비 -> C장비(Q700) 크로스콜 (통일된 형식):\n";
echo "  DID: $DID\n";
echo "  TARGET_Q: $TARGET_Q2 (C장비에서 시도할 Q)\n";
echo "  NEXT_Q: $NEXT_Q2 (TARGET_Q2 다음에 시도할 Q)\n";
echo "  Generated crosscall_dial: $crosscall_dial2\n";
echo "  Expected format: 92 + $DID + 700 + 900 = 92${DID}700900\n\n";

// ============================================================================
// Test 7: parse_crosscall_did() - Unified Format Parsing (v1.2.0)
// ============================================================================
echo "=== TEST 7: parse_crosscall_did() - Unified Format Parsing ===\n";
echo "(v1.2.0: 모든 크로스콜은 통일된 형식 - prefix + DID + TARGET_Q(3) + NEXT_Q(3))\n";

$test_dids = [
    "9007089984200800700" => "A장비 crosscall (prefix 90, TARGET=800, NEXT=700)",
    "9107089984200800700" => "B장비 crosscall (prefix 91, TARGET=800, NEXT=700)",
    "9207089984200700900" => "C장비 crosscall (prefix 92, TARGET=700, NEXT=900)",
    "9007089984200700800" => "A장비 crosscall (prefix 90, TARGET=700, NEXT=800)",
    "9107089984200900700" => "B장비 crosscall (prefix 91, TARGET=900, NEXT=700)",
    "07089984200"         => "일반 호 (no prefix)",
];

foreach ($test_dids as $did => $desc) {
    echo "\nTest DID: $did ($desc)\n";
    $parsed = parse_crosscall_did($conn, $did);

    if ($parsed === null) {
        echo "  Result: NOT a crosscall (normal call)\n";
    } else {
        echo "  Result: " . strtoupper($parsed['type']) . "\n";
        echo "  - prefix: " . $parsed['prefix'] . "\n";
        echo "  - original_did: " . $parsed['original_did'] . "\n";
        echo "  - target_q: " . $parsed['target_q'] . " (현재 장비에서 시도할 Q)\n";
        echo "  - next_q: " . $parsed['next_q'] . " (다음에 시도할 Q)\n";
        echo "  - source_pbx_id: " . $parsed['source_pbx_id'] . "\n";
    }
}
echo "\n";

// ============================================================================
// Test 8: check_crosscall_required() function
// ============================================================================
echo "=== TEST 8: check_crosscall_required() function ===\n";

$test_q_nums = ["800", "700", "900"];
echo "Testing crosscall requirement for various TARGET_Q:\n\n";

foreach ($test_q_nums as $target_q) {
    $result = check_crosscall_required($conn, $target_q);
    echo "TARGET_Q: $target_q\n";
    echo "  my_pbx_id: " . $result['my_pbx_id'] . "\n";
    echo "  target_pbx_id: " . $result['target_pbx_id'] . "\n";
    echo "  is_crosscall: " . ($result['is_crosscall'] ? "YES" : "NO") . "\n";
    if ($result['is_crosscall'] && $result['target_prefix']) {
        echo "  target_prefix: " . $result['target_prefix']->crosscall_prefix . "\n";
    }
    echo "\n";
}

// ============================================================================
// Test 9: End-to-End Crosscall Flow (v1.2.0 통일된 형식)
// ============================================================================
echo "=== TEST 9: End-to-End Crosscall Flow (Unified Format) ===\n";

echo "\n[시나리오] A장비 -> B장비(Q800) -> C장비(Q700) -> A장비(Q900) 순서\n";
echo "QList: 700(C) -> 800(B) -> 900(A) 순서라고 가정\n";
echo str_repeat("-", 60) . "\n";

$did = "07089984200";

// Step 1: A장비에서 B장비로 크로스콜 (통일된 형식)
echo "\n[Step 1] A장비 -> B장비(Q800) 크로스콜 발신\n";
echo "  TARGET_Q=800 (B장비에서 시도), NEXT_Q=700 (800 다음 순서)\n";
$dial1 = generate_crosscall_dial($conn, $did, "800", "700", 2);
echo "  생성된 다이얼: $dial1\n";
echo "  예상 형식: 91${did}800700\n";

// Step 2: B장비에서 수신 및 파싱
echo "\n[Step 2] B장비에서 수신 및 파싱\n";
$parsed1 = parse_crosscall_did($conn, $dial1);
if ($parsed1) {
    echo "  타입: " . $parsed1['type'] . "\n";
    echo "  원본 DID: " . $parsed1['original_did'] . "\n";
    echo "  TARGET_Q: " . $parsed1['target_q'] . " (B장비에서 시도할 Q)\n";
    echo "  NEXT_Q: " . $parsed1['next_q'] . " (다음에 시도할 Q)\n";
    echo "  => B장비는 Q800으로 다이얼 시도\n";
}

// Step 3: B장비에서 Q800 무응답 -> C장비(Q700)로 크로스콜 (통일된 형식)
echo "\n[Step 3] B장비에서 Q800 무응답 -> C장비(Q700)로 크로스콜\n";
echo "  TARGET_Q=700 (C장비에서 시도), NEXT_Q=900 (700 다음 순서)\n";
$dial2 = generate_crosscall_dial($conn, $did, "700", "900", 3);
echo "  생성된 다이얼: $dial2\n";
echo "  예상 형식: 92${did}700900\n";

// Step 4: C장비에서 수신 및 파싱
echo "\n[Step 4] C장비에서 수신 및 파싱\n";
$parsed2 = parse_crosscall_did($conn, $dial2);
if ($parsed2) {
    echo "  타입: " . $parsed2['type'] . "\n";
    echo "  원본 DID: " . $parsed2['original_did'] . "\n";
    echo "  TARGET_Q: " . $parsed2['target_q'] . " (C장비에서 시도할 Q)\n";
    echo "  NEXT_Q: " . $parsed2['next_q'] . " (다음에 시도할 Q)\n";
    echo "  => C장비는 Q700으로 다이얼 시도\n";
}

// Step 5: C장비에서 Q700 무응답 -> A장비(Q900)로 크로스콜 (통일된 형식)
echo "\n[Step 5] C장비에서 Q700 무응답 -> A장비(Q900)로 크로스콜\n";
echo "  TARGET_Q=900 (A장비에서 시도), NEXT_Q=800 (900 다음 순서, 순환)\n";
$dial3 = generate_crosscall_dial($conn, $did, "900", "800", 1);
echo "  생성된 다이얼: $dial3\n";
echo "  예상 형식: 90${did}900800\n";

// Step 6: A장비에서 수신 및 파싱
echo "\n[Step 6] A장비에서 수신 및 파싱\n";
$parsed3 = parse_crosscall_did($conn, $dial3);
if ($parsed3) {
    echo "  타입: " . $parsed3['type'] . "\n";
    echo "  원본 DID: " . $parsed3['original_did'] . "\n";
    echo "  TARGET_Q: " . $parsed3['target_q'] . " (A장비에서 시도할 Q)\n";
    echo "  NEXT_Q: " . $parsed3['next_q'] . " (다음에 시도할 Q)\n";
    echo "  => A장비는 Q900으로 다이얼 시도\n";
}

echo "\n=> 모든 장비가 동일한 통일된 형식을 사용하여 크로스콜 처리!\n";
echo "\n";

// ============================================================================
// Test 10: Fallback to MYQ in Crosscall Incoming Scenario (v1.2.0 통일된 형식)
// ============================================================================
echo "=== TEST 10: Fallback Scenario with Unified Format ===\n";

echo "\n--- Scenario: B장비(Q800)에서 모든 Q가 무응답, 다음 Q(700)가 C장비 ---\n";

$MYQ = "800";  // 현재 B장비의 Q
$NEXT_Q = "700";  // QList에서 다음 Q (C장비)
$NEXT_NEXT_Q = "900";  // 그 다음 Q (A장비)
$CC_ORIGINAL_DID = "07089984200";
$IS_CROSSCALL_INCOMING = "Y";

echo "MYQ (현재 B장비): $MYQ\n";
echo "NEXT_Q (다음 Q, C장비): $NEXT_Q\n";
echo "NEXT_NEXT_Q (그 다음 Q): $NEXT_NEXT_Q\n";
echo "IS_CROSSCALL_INCOMING: $IS_CROSSCALL_INCOMING\n";
echo "\n";

$my_pbx = get_pbx_id_for_q($conn, $MYQ);
$next_pbx = get_pbx_id_for_q($conn, $NEXT_Q);
echo "my_q_pbx_id (B장비) = $my_pbx\n";
echo "next_q_pbx_id (C장비) = $next_pbx\n";

if ($my_pbx != $next_pbx) {
    echo "\n결과: 다른 장비 -> 크로스콜 트리거!\n";
    // v1.2.0: 통일된 형식으로 크로스콜 번호 생성
    $crosscall_dial = generate_crosscall_dial($conn, $CC_ORIGINAL_DID, $NEXT_Q, $NEXT_NEXT_Q, $next_pbx);
    echo "통일된 형식 crosscall_dial = $crosscall_dial\n";
    echo "  형식: prefix + DID + TARGET_Q($NEXT_Q) + NEXT_Q($NEXT_NEXT_Q)\n";
    echo "=> C장비에서 이 번호를 수신하면 Q$NEXT_Q 시도, 실패 시 Q$NEXT_NEXT_Q 정보 사용\n";
}

echo "\n--- Scenario: 모든 장비에서 동일한 방식으로 처리 ---\n";
echo "A/B/C 장비 모두 동일한 모듈(getQStep2.php)로 처리 가능:\n";
echo "  1. parse_crosscall_did()로 TARGET_Q, NEXT_Q 추출\n";
echo "  2. TARGET_Q로 다이얼 시도\n";
echo "  3. 무응답 시 NEXT_Q의 pbx_id 확인\n";
echo "  4. 다른 장비면 generate_crosscall_dial()로 크로스콜 생성\n";
echo "\n";

mysqli_close($conn);

echo "============================================================================\n";
echo "All Tests Completed!\n";
echo "============================================================================\n";
?>
