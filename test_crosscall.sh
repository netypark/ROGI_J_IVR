#!/bin/bash
# ============================================================================
# CrossCall Test Script
# Date: 2026-01-29
# Description: Test crosscall functionality with curl
# ============================================================================

API_HOST="http://localhost/API"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}CrossCall Test Suite${NC}"
echo -e "${BLUE}============================================================================${NC}"

# Function to make API call and extract result
test_api() {
    local test_name="$1"
    local endpoint="$2"
    local json_data="$3"

    echo ""
    echo -e "${YELLOW}=== TEST: $test_name ===${NC}"
    echo -e "Endpoint: $endpoint"
    echo -e "Request: $json_data"
    echo ""

    response=$(curl -s -X POST "$API_HOST/$endpoint" \
        -H "Content-Type: application/json" \
        -d "$json_data")

    echo -e "${GREEN}Response:${NC}"
    echo "$response" | python3 -m json.tool 2>/dev/null || echo "$response"
    echo ""

    # Return the response for further processing
    echo "$response"
}

# ============================================================================
# Test 1: getQExtCCDid_new.php - Normal call on A장비 (pbx_id=1)
# Expected: MYQ=700, TR_NEXT_Q=800 (B장비), is_crosscall_required=Y
# ============================================================================
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 1: getQExtCCDid_new.php - Crosscall Detection (A장비 -> B장비)${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON1=$(cat <<'EOF'
{
    "REQ": "GET_Q_EXT_TR_DID",
    "COMPANY_ID": 7360,
    "DID": "07089984200",
    "CID": "01012345678",
    "TYPE": "GET_CC_SEQ",
    "OPTION": "D",
    "MYQ": "700",
    "QLIST": ["700", "800", "900"],
    "TRCOUNT": 3,
    "TRORDER": 1,
    "RECALL_USE": "N",
    "RECALL_TIME": 30,
    "RECALL_OPT1": "M",
    "RECALL_OPT2": "F",
    "RECALL_CNT1": "M",
    "RECALL_CNT2": "F",
    "RECALL_USE_AQ": "N",
    "RECALL_AQ_TIME": 10,
    "RECALL_ALBAQ": "",
    "MASTER_ID": 7360,
    "DIDLIST": ["07089984200", "07089984200", "07089984200"]
}
EOF
)

curl -s -X POST "$API_HOST/getQExtCCDid_new.php" \
    -H "Content-Type: application/json" \
    -d "$JSON1" | python3 -m json.tool

# ============================================================================
# Test 2: getQStep2.php - Normal scenario (same device)
# Expected: MYQ=700, TR_NEXT_Q=700 (same device), is_crosscall_required=N
# ============================================================================
echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 2: getQStep2.php - Same Device (No Crosscall)${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON2=$(cat <<'EOF'
{
    "REQ": "GET_Q_STEP2",
    "COMPANY_ID": 7360,
    "DID": "07089984200",
    "CID": "01012345678",
    "TYPE": "GET_CC_SEQ",
    "OPTION": "D",
    "MYQ": "700",
    "QLIST": ["700", "800", "900"],
    "TRCOUNT": 3,
    "TRORDER": 1,
    "DIDLIST": ["07089984200", "07089984200", "07089984200"]
}
EOF
)

curl -s -X POST "$API_HOST/getQStep2.php" \
    -H "Content-Type: application/json" \
    -d "$JSON2" | python3 -m json.tool

# ============================================================================
# Test 3: getQStep2.php - Crosscall Incoming Scenario (B장비에서 수신)
# Expected: is_return_crosscall=Y if next Q is on A장비
# ============================================================================
echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 3: getQStep2.php - Crosscall Incoming (Return to A장비)${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON3=$(cat <<'EOF'
{
    "REQ": "GET_Q_STEP2",
    "COMPANY_ID": 7360,
    "DID": "07089984200",
    "CID": "01012345678",
    "TYPE": "GET_CC_SEQ",
    "OPTION": "D",
    "MYQ": "800",
    "QLIST": ["700", "800", "900"],
    "TRCOUNT": 3,
    "TRORDER": 2,
    "DIDLIST": ["07089984200", "07089984200", "07089984200"],
    "IS_CROSSCALL_INCOMING": "Y",
    "CC_ORIGIN_Q": "700",
    "CC_ORIGINAL_DID": "07089984200"
}
EOF
)

curl -s -X POST "$API_HOST/getQStep2.php" \
    -H "Content-Type: application/json" \
    -d "$JSON3" | python3 -m json.tool

# ============================================================================
# Test 4: checkCrossCallPrefix.php - 99 Prefix (Incoming Crosscall)
# Expected: Parse 99 prefix, get company info, QList
# ============================================================================
echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 4: checkCrossCallPrefix.php - Incoming Crosscall (99 prefix)${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON4=$(cat <<'EOF'
{
    "REQ": "CHECK_CROSSCALL",
    "DID": "9907089984200800700",
    "CID": "01012345678"
}
EOF
)

curl -s -X POST "$API_HOST/checkCrossCallPrefix.php" \
    -H "Content-Type: application/json" \
    -d "$JSON4" | python3 -m json.tool

# ============================================================================
# Test 5: checkCrossCallPrefix.php - 88 Prefix (Return Crosscall)
# Expected: Parse 88 prefix, return to origin Q
# ============================================================================
echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 5: checkCrossCallPrefix.php - Return Crosscall (88 prefix)${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON5=$(cat <<'EOF'
{
    "REQ": "CHECK_CROSSCALL",
    "DID": "8807089984200700",
    "CID": "01012345678"
}
EOF
)

curl -s -X POST "$API_HOST/checkCrossCallPrefix.php" \
    -H "Content-Type: application/json" \
    -d "$JSON5" | python3 -m json.tool

# ============================================================================
# Test 6: checkDidPrefix.php - Normal DID Prefix Check
# ============================================================================
echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 6: checkDidPrefix.php - Normal DID Prefix Check${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON6=$(cat <<'EOF'
{
    "REQ": "CHECK_DID_PREFIX",
    "DID": "07089984200",
    "CID": "01012345678"
}
EOF
)

curl -s -X POST "$API_HOST/checkDidPrefix.php" \
    -H "Content-Type: application/json" \
    -d "$JSON6" | python3 -m json.tool

# ============================================================================
# Test 7: getQStep2.php - Fallback to MYQ in Crosscall Incoming (v1.3.0)
# Expected: All channels busy on B장비, fallback should trigger return crosscall
# ============================================================================
echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${BLUE}TEST 7: getQStep2.php - Fallback Scenario (B장비에서 모든 Q 무응답)${NC}"
echo -e "${BLUE}============================================================================${NC}"

JSON7=$(cat <<'EOF'
{
    "REQ": "GET_Q_STEP2",
    "COMPANY_ID": 7360,
    "DID": "07089984200",
    "CID": "01012345678",
    "TYPE": "GET_CC_SEQ",
    "OPTION": "D",
    "MYQ": "800",
    "QLIST": ["700", "800", "900"],
    "TRCOUNT": 3,
    "TRORDER": 3,
    "DIDLIST": ["07089984200", "07089984200", "07089984200"],
    "IS_CROSSCALL_INCOMING": "Y",
    "CC_ORIGIN_Q": "700",
    "CC_ORIGINAL_DID": "07089984200"
}
EOF
)

echo -e "${YELLOW}Request: Crosscall incoming on B장비(Q800), TRORDER=3 (last Q), all busy${NC}"
echo -e "${YELLOW}Expected: is_return_crosscall=Y, return_crosscall_dial=8807089984200700${NC}"
echo ""

curl -s -X POST "$API_HOST/getQStep2.php" \
    -H "Content-Type: application/json" \
    -d "$JSON7" | python3 -m json.tool

echo ""
echo -e "${BLUE}============================================================================${NC}"
echo -e "${GREEN}All Tests Completed!${NC}"
echo -e "${BLUE}============================================================================${NC}"
