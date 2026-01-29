# CrossCall Device Detection - Change Log
## Date: 2026-01-29

### Overview
A장비/B장비 간 크로스콜 자동 감지 및 다이얼 번호 생성 기능 추가

### Modified Files

#### 1. getQExtCCDid_new.php (v1.1.0 -> v1.3.0)
- **v1.1.0**: pbx_id 조회 기능 추가 (tr_next_q_pbx_id)
- **v1.2.0**: 크로스콜 자동 감지 및 다이얼 번호 생성
  - `my_q_pbx_id`: 현재 Q(MYQ)의 장비 ID
  - `tr_next_q_pbx_id`: 다음 Q의 장비 ID
  - `is_crosscall_required`: 크로스콜 필요 여부 ('Y'/'N')
  - `crosscall_dial_number`: 크로스콜 다이얼 번호 (99+DID+TARGET_Q+ORIGIN_Q)
- **v1.3.0**: IP 기반 PBX 설정 지원 (T_PBX_CONFIG)
  - `include_once 'pbx_config.php'` 추가
  - `get_my_pbx_config($conn)` 함수로 자기 서버 IP 기반 pbx_id 조회
  - `get_target_pbx_prefix($conn, $pbx_id)` 함수로 대상 장비의 동적 prefix 조회
  - 크로스콜 다이얼 번호: 대상 장비별 prefix 사용 (91, 92 등)
  - `my_server_ip`: 자기 서버 IP 주소 반환

#### 2. LOGI.SCN
- GET_Q_EXT_TR_DID 스텝에서 새로운 필드 수신
  - IS_CROSSCALL_REQ, CROSSCALL_DIAL_NUM 변수 추가
- GET_Q_STEP2 스텝에서 새로운 필드 수신
  - IS_CROSSCALL_REQ, CROSSCALL_DIAL_NUM 변수 추가
- AGENT_TRN 설정 시 크로스콜 여부에 따라 번호 결정
  - 같은 장비: TR_NEXT_Q (예: 700)
  - 다른 장비: CROSSCALL_DIAL_NUM (예: 9907089984200800700)
- GET_CC_SEQ, GET_CC_SMY, GET_CC_DIR 모든 타입에 크로스콜 지원

#### 3. checkCrossCallPrefix.php (v1.1.0 -> v1.3.0)
- **v1.1.0**: 크로스콜 수신 시나리오 기본 처리
- **v1.2.0**: 회사 정보 및 QList 조회 기능 추가
  - 크로스콜 수신 시 원본 DID로 회사 정보 조회
  - `get_company_info_for_crosscall()`: 회사 정보 및 QList 조회 함수
  - `get_pbx_id_for_q_cc()`: Q 번호에 대한 pbx_id 조회 헬퍼 함수
  - 반환 필드 추가:
    - `company_id`, `master_id`, `allocQ`: 회사 정보
    - `tr_q_list`, `tr_did_list`: Q 리스트 및 DID 리스트
    - `tr_order`: TARGET_Q의 QList 내 위치 (1-based)
    - `tr_count`, `tr_option`, `tr_time_first`, `tr_time_second`: 전송 설정
    - `next_q`, `next_did`, `next_q_pbx_id`: 다음 Q 정보
    - `is_next_crosscall`: 다음 Q가 다른 장비인지 여부
    - `next_crosscall_dial`: 반환 크로스콜 다이얼 번호 (88+DID+ORIGIN_Q)
- **v1.3.0**: IP 기반 동적 prefix 파싱 지원 (T_PBX_CONFIG)
  - `include_once 'pbx_config.php'` 추가
  - `parse_crosscall_did($conn, $did)` 함수로 모든 prefix 동적 파싱
  - `get_my_pbx_config($conn)` 함수로 자기 서버 IP 기반 pbx_id 조회
  - 크로스콜 prefix: 90(A장비), 91(B장비), 92(C장비)
  - 반환 prefix: 80(A장비), 81(B장비), 82(C장비)
  - `source_pbx_id`: 크로스콜 송신 장비의 pbx_id

#### 4. checkDidPrefix.php (v1.0.0)
- DID Prefix 기반 라우팅

#### 5. getQStep2.php (v1.0.0 -> v1.4.0)
- **v1.0.0**: PHP 8.2.30 호환성 업데이트
- **v1.1.0**: 크로스콜 자동 감지 및 다이얼 번호 생성
  - `get_pbx_id_for_q_step2()`: Q 번호에 대한 pbx_id 조회 헬퍼 함수
  - `my_q_pbx_id`: 현재 Q(MYQ)의 장비 ID
  - `tr_next_q_pbx_id`: 다음 Q의 장비 ID
  - `is_crosscall_required`: 크로스콜 필요 여부 ('Y'/'N')
  - `crosscall_dial_number`: 크로스콜 다이얼 번호 (99+DID+TARGET_Q+ORIGIN_Q)
  - 함수 종료 전 MYQ와 TR_NEXT_Q의 pbx_id 비교하여 크로스콜 결정
- **v1.2.0**: 크로스콜 수신 시나리오 지원 (반환 크로스콜)
  - 새로운 입력 파라미터:
    - `CC_ORIGIN_Q`: 원래 출발지 Q (A장비)
    - `CC_ORIGINAL_DID`: 원본 DID
    - `IS_CROSSCALL_INCOMING`: 크로스콜 수신 여부 ('Y'/'N')
  - 새로운 반환 필드:
    - `is_return_crosscall`: 반환 크로스콜 필요 여부 ('Y'/'N')
    - `return_crosscall_dial`: 반환 크로스콜 다이얼 번호 (88+DID+ORIGIN_Q)
    - `cc_origin_q`: 원래 출발지 Q
    - `cc_original_did`: 원본 DID
  - 크로스콜 수신 상태(IS_CROSSCALL_INCOMING='Y')에서 다음 Q가 다른 장비일 때:
    - 99 prefix 대신 88 prefix로 반환 크로스콜 생성
    - return_crosscall_dial = 88 + ORIGINAL_DID + ORIGIN_Q
- **v1.3.0**: Fallback-to-MYQ 케이스 처리 (크로스콜 수신 상태)
  - 문제: 크로스콜 수신 상태(B장비)에서 모든 Q가 무응답일 때 MYQ로 fallback하면
    - MYQ = B장비의 Q (예: 800)
    - my_q_pbx_id == tr_next_q_pbx_id (둘 다 B장비)
    - 크로스콜이 트리거되지 않아 B장비에 머물러 있음 (문제!)
  - 해결: Fallback 감지 시 tr_next_q를 CC_ORIGIN_Q로 교체
    - tr_next_q = CC_ORIGIN_Q (원래 A장비의 Q)
    - my_q_pbx_id != tr_next_q_pbx_id (B장비 ≠ A장비)
    - return crosscall이 트리거되어 원래 A장비로 복귀
- **v1.4.0**: IP 기반 PBX 설정 지원 (T_PBX_CONFIG)
  - `include_once 'pbx_config.php'` 추가
  - `get_my_pbx_config($conn)` 함수로 자기 서버 IP 기반 pbx_id 조회
  - `get_target_pbx_prefix($conn, $pbx_id)` 함수로 대상 장비의 동적 prefix 조회
  - 크로스콜/반환 크로스콜 다이얼 번호: 대상 장비별 prefix 사용
  - `my_server_ip`: 자기 서버 IP 주소 반환

#### 6. pbx_config.php (NEW - v1.0.0)
- IP 기반 PBX 설정 관리 헬퍼 함수
- `get_my_server_ip()`: 자기 서버 IP 조회
- `get_my_pbx_config($conn)`: IP 기반 자기 PBX 설정 조회 (캐시 지원)
- `get_my_pbx_id($conn)`: 자기 pbx_id 조회
- `get_my_crosscall_prefix($conn)`: 자기 crosscall prefix 조회
- `get_my_return_prefix($conn)`: 자기 return prefix 조회
- `get_target_pbx_prefix($conn, $pbx_id)`: 대상 장비의 prefix 조회
- `get_pbx_id_for_queue($conn, $q_num)`: Q번호로 pbx_id 조회
- `check_crosscall_required($conn, $target_q)`: 크로스콜 필요 여부 확인
- `generate_crosscall_dial($conn, $did, $target_q, $origin_q, $target_pbx_id)`: 크로스콜 다이얼 번호 생성
- `generate_return_crosscall_dial($conn, $did, $origin_q, $origin_pbx_id)`: 반환 크로스콜 다이얼 번호 생성
- `parse_crosscall_did($conn, $did)`: 수신 DID 파싱 (모든 prefix 동적 감지)
- `reset_pbx_config_cache()`: 캐시 초기화 (테스트용)

#### 7. sql/create_t_pbx_config.sql (NEW)
- T_PBX_CONFIG 테이블 생성 스크립트
- 초기 데이터 (A장비, B장비, C장비)

### Database Tables
- T_PBX: 장비 정보 (pbx_id=1: A장비, pbx_id=2: B장비)
- T_COMPANY: pbx_id 컬럼으로 장비 매핑
- T_CROSSCALL_CONFIG: (구) 크로스콜 설정 (prefix, q_length, return_prefix, origin_q_length)
- **T_PBX_CONFIG (NEW)**: IP 기반 PBX 장비 설정
  - `pbx_id`: 장비 그룹 ID (1=A장비, 2=B장비, 3=C장비)
  - `server_ip`: 서버 IP 주소 (Primary/Secondary)
  - `crosscall_prefix`: 크로스콜 발신 prefix (90, 91, 92)
  - `return_prefix`: 반환 크로스콜 prefix (80, 81, 82)
  - `q_length`, `origin_q_length`: Q번호 자릿수
  - `is_active`: 활성화 여부

### Call Flow

#### 1. 크로스콜 발신 (A장비 → B장비)
```
1. 일반 호 수신 (DID: 07089984200)
2. getCompany_new.php에서 crosscall 설정 확인 (USE_CROSSCALL='Y')
3. getQExtCCDid_new.php 호출하여 Q List 조회
4. get_my_pbx_config()로 자기 서버 IP 기반 pbx_id 조회 (A장비 = 1)
5. TR_NEXT_Q(800)의 pbx_id=2 비교 → 다르므로 is_crosscall_required='Y'
6. get_target_pbx_prefix(2)로 B장비의 prefix 조회 → 91
7. crosscall_dial_number = 91 + 07089984200 + 800 + 700
8. LOGI.SCN에서 AGENT_TRN = 9107089984200800700 으로 Dial
9. B장비에서 수신 시 checkCrossCallPrefix.php로 파싱
```

#### 2. 크로스콜 수신 후 처리 (B장비)
```
1. B장비에서 9107089984200800700 수신
2. checkCrossCallPrefix.php에서 parse_crosscall_did() 호출
3. T_PBX_CONFIG에서 모든 active prefix 조회하여 동적 파싱
4. 91 prefix 매칭 → type='crosscall', source_pbx_id=2
   - ORIGINAL_DID: 07089984200
   - TARGET_Q: 800
   - ORIGIN_Q: 700
5. 회사 정보 및 QList 조회 (get_company_info_for_crosscall)
6. TARGET_Q(800)의 QList 내 위치(TR_ORDER) 계산
7. 800번 Q로 DIAL 시도
```

#### 3. 무응답 시 다음 Q 처리 (getQStep2.php)
```
1. 800번 Q 무응답 시 getQStep2.php 호출
   - IS_CROSSCALL_INCOMING='Y'
   - CC_ORIGIN_Q='700'
   - CC_ORIGINAL_DID='07089984200'
2. get_my_pbx_config()로 자기 서버 IP 기반 pbx_id 조회 (B장비 = 2)
3. 다음 Q(900)의 pbx_id 확인 (A장비 = 1)
4. 같은 장비(B)일 경우: is_return_crosscall='N', 일반 다이얼
5. 다른 장비(A)일 경우: is_return_crosscall='Y'
   - get_target_pbx_prefix(1)로 A장비의 return_prefix 조회 → 80
   - return_crosscall_dial = 80 + 07089984200 + 700
6. A장비에서 8007089984200700 수신 시 ORIGIN_Q(700)로 복귀
```

#### 4. 모든 Q 무응답 시 Fallback 처리 (v1.3.0/v1.4.0)
```
[시나리오] B장비에서 모든 Q가 무응답, MYQ로 fallback 발생

[수정 전 문제점]
1. B장비에서 Q800, Q900 모두 무응답
2. 코드가 tr_next_q = MYQ(800)로 설정 (fallback)
3. my_q_pbx_id(2) == tr_next_q_pbx_id(2) → 같은 장비
4. 크로스콜 트리거 안됨 → B장비에 계속 머물러 있음

[수정 후 동작]
1. B장비에서 Q800, Q900 모두 무응답
2. 코드가 tr_next_q = MYQ(800)로 설정 (fallback)
3. v1.3.0 로직: IS_CROSSCALL_INCOMING='Y' && tr_next_q == MYQ 감지
4. tr_next_q를 CC_ORIGIN_Q(700)로 교체
5. get_my_pbx_config()로 자기 pbx_id=2 (IP 기반)
6. my_q_pbx_id(2) != tr_next_q_pbx_id(1) → 다른 장비
7. get_target_pbx_prefix(1)로 A장비의 return_prefix 조회 → 80
8. is_return_crosscall='Y', return_crosscall_dial='8007089984200700'
9. A장비에서 8007089984200700 수신 → 원래 Q(700)로 복귀
```

### Backup Files (Before Changes)
- getQExtCCDid_new.php
- LOGI.SCN
- checkCrossCallPrefix.php
- checkDidPrefix.php
- getQStep2.php
