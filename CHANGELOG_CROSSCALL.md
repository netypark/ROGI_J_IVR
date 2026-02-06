
## 2026-01-31 - CrossCall 크로스콜 자동 감지 완료

### 수정 파일
1. **getCompany_new.php**
   - SQL 변수명 오류 수정 ($company_id → $companyId)
   - PEAK, DIRECT 조건에서 서브쿼리 수정
   - tr_pbx_id_list 반환 추가

2. **getQExtCCDid_new.php** (v1.3.1 → v1.4.0)
   - MYQ 비교 조건 제거 (MYQ가 다른 장비에 있을 수 있음)
   - NEXT_Q 로직 추가 (TARGET_Q 실패 시 다음 Q)
   - tr_next_next_q, tr_next_next_q_pbx_id 필드 추가

3. **getQStep2.php** (v1.5.0 → v1.5.1)
   - MYQ 비교 조건 제거
   - 동일한 크로스콜 형식 적용

4. **LOGI.SCN**
   - GET_COMPANY: TR_PBX_ID_LIST 변수 저장 추가
   - GET_Q_STEP2: PBXIDLIST 파라미터 추가

### 크로스콜 다이얼 형식
```
prefix + DID + TARGET_Q + NEXT_Q
예: 9107089984200800900
    91 = B장비 prefix
    07089984200 = DID
    800 = TARGET_Q
    900 = NEXT_Q
```

### 테스트 결과
- getCompany_new.php: QLIST + PBXIDLIST 정상 반환 ✓
- getQExtCCDid_new.php: 첫 Q 크로스콜 감지 ✓
- getQStep2.php: 다음 Q 크로스콜 감지 ✓

