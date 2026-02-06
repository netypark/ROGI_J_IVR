# CrossCall IVR 시스템 수정 히스토리
## 날짜: 2026-02-05

---

## 1. Q700 전화벨 안울림 문제 수정

### 증상
- Q700에 전화가 오면 8000 내선이 울려야 하는데 벨이 울리지 않음
- Queue 로그에 RINGNOANSWER 기록은 있으나 실제 전화기 벨 안울림

### 원인 분석
`/etc/asterisk/extensions_custom.conf`의 `[from-internal]` 컨텍스트에 `_X.` 패턴이 priority 1-3을 사용하고 있었으나, 정상 extension 처리로 이동하는 `Goto`가 없었음.

이로 인해:
1. `_X.` 패턴이 priority 1-3 실행
2. ext-local 8000의 `Gosub(macro-exten-vm)` (priority 3) 건너뜀
3. bad-number 컨텍스트의 `Wait(1)` → `Congestion(20)` 실행
4. 전화벨 안울림

### 수정 내용
**파일**: `/etc/asterisk/extensions_custom.conf`

```asterisk
[from-internal]
; VQ_MAXWAIT 설정 - Queue의 maxwait를 DIAL_TIMEOUT으로 제어
; DIAL_TIMEOUT이 설정되어 있으면 VQ_MAXWAIT로 전달하여 Queue에서 사용
; 2026-02-05: Goto 추가 - priority 1-3 후 정상 extension 처리로 이동
exten => _X.,1,NoOp(Setting MOH and VQ_MAXWAIT for extension ${EXTEN}, __DIAL_TIMEOUT=${__DIAL_TIMEOUT})
 same => n,ExecIf($["${__DIAL_TIMEOUT}" != ""]?Set(__VQ_MAXWAIT=${__DIAL_TIMEOUT}))
 same => n,ExecIf($["${MOH_CLASS}" != ""]?Set(CHANNEL(musicclass)=${MOH_CLASS}))
 same => n,Goto(from-internal-xfer,${EXTEN},1)  ; <-- 추가됨
```

---

## 2. Q900 타임아웃 15초 설정 문제 수정

### 증상
- Q700은 10초 후 타임아웃 → Q900으로 전환 정상
- Q900은 15초 후 retry, 다시 15초... 무한 반복
- 시나리오의 10초 타임아웃이 적용 안됨

### 원인 분석
`/etc/asterisk/queues_additional.conf`에서 Q900의 `timeout=15` 설정:
- Q700: `timeout=0` (무한 - 시나리오 타임아웃 적용)
- Q900: `timeout=15` (15초 후 retry)

Queue의 member timeout이 15초로 설정되어 있어 15초마다 retry하며 시나리오 타임아웃(10초)을 무시함.

### 수정 내용
**파일**: `/etc/asterisk/queues_additional.conf`

```ini
; 변경 전
[900]
timeout=15

; 변경 후
[900]
timeout=0
```

**주의**: 이 파일은 FreePBX에서 자동 생성됨. FreePBX GUI에서도 수정 필요.

---

## 3. __DIAL_TIMEOUT 상속 문제 수정 (핵심 버그)

### 증상
- Q700 → 약 10초 후 Q900으로 전환 (정상처럼 보임)
- Q900 → 20초 후에도 NOANSWER 트리거 안됨
- getQStep2.php가 Q900에서 호출되지 않음

### 원인 분석
Asterisk 로그 분석 결과:
```
transfer-dialout-custom: EXTEN=900, __DIAL_TIMEOUT=    ← 비어있음!
Set("...", "DIAL_TIMEOUT=30")                          ← 기본값 30초 적용
```

**근본 원인**: `/var/www/html/admin/modules/arsauth/Arsauth.class.php`의 `F_CALL_TRANSFER()` 함수에서:

```php
$this->AGI_OBJECT->set_variable('__DIAL_TIMEOUT', $dial_timeout);
```

AGI의 `SET VARIABLE` 명령은 `__` prefix를 통한 채널 변수 상속을 제대로 처리하지 않음.
- 변수가 현재 AGI 채널에만 설정됨
- `exec_dial`로 생성되는 Local 자식 채널로 상속되지 않음

### 수정 내용
**파일**: `/var/www/html/admin/modules/arsauth/Arsauth.class.php`
**라인**: 약 3754-3761

```php
// 변경 전
$this->AGI_OBJECT->set_variable('__DIAL_TIMEOUT', $dial_timeout);
$this->LOG_DEBUG(sprintf("F_CALL_TRANSFER: Set __DIAL_TIMEOUT=%s (RING_WAIT=%s)", $dial_timeout, $ARGS_COPY->RING_WAIT ?? 'NULL'));

// 변경 후
// 2026-02-05 FIX: AGI set_variable 대신 Asterisk Set() 사용
// AGI의 SET VARIABLE 명령은 __prefix를 통한 상속이 제대로 작동하지 않음
// exec('Set', ...) 사용 시 Asterisk의 Set() 애플리케이션이 상속 처리함
$this->AGI_OBJECT->exec('Set', "__DIAL_TIMEOUT=$dial_timeout");
$this->LOG_DEBUG(sprintf("F_CALL_TRANSFER: Set __DIAL_TIMEOUT=%s via exec('Set') (RING_WAIT=%s)", $dial_timeout, $ARGS_COPY->RING_WAIT ?? 'NULL'));
```

### 기술적 설명
- AGI `SET VARIABLE` 명령: 현재 채널에만 변수 설정, 상속 안됨
- Asterisk `Set()` 애플리케이션: `__` prefix 정상 처리, 자식 채널로 상속됨
- `exec('Set', ...)`: AGI에서 Asterisk Set() 애플리케이션 직접 호출

---

## 4. 시나리오 분석 결과

### WAIT_TM (다이얼 타임아웃) 설정 로직

**GET_Q_STEP1 (첫 번째 다이얼)**:
- GET_CC_SEQ: `WAIT_TM = TR_TIME_FIRST`
- GET_CC_SMY: `WAIT_TM = TR_TIME_FIRST`
- GET_CC_DIR: `WAIT_TM = TR_TIME_FIRST`

**GET_Q_STEP2 (후속 다이얼)**:
- GET_CC_SEQ: `WAIT_TM = TR_TIME_SECOND`
- GET_CC_SMY + FIND_MYQ='Y': `WAIT_TM = TR_TIME_FIRST`
- GET_CC_SMY (fallback): `WAIT_TM = TR_TIME_SECOND`
- GET_CC_DIR: `WAIT_TM = TR_TIME_SECOND`

### DB 기본값
- `TR_TIME_FIRST` (ring_wait_time_my): 기본 15초
- `TR_TIME_SECOND` (ring_wait_time_transfer): 기본 10초

---

## 5. 관련 파일 목록

### 수정된 파일
| 파일 | 수정 내용 |
|------|----------|
| `/etc/asterisk/extensions_custom.conf` | from-internal _X. 패턴에 Goto 추가 |
| `/etc/asterisk/queues_additional.conf` | Q900 timeout=15 → timeout=0 |
| `/var/www/html/admin/modules/arsauth/Arsauth.class.php` | __DIAL_TIMEOUT 상속 수정 |

### 참조된 파일
| 파일 | 용도 |
|------|------|
| `/home/asterisk/WEB/J_IVR/LOGI.SCN` | IVR 시나리오 정의 |
| `/home/asterisk/WEB/J_IVR/getQStep2.php` | 후속 Q 라우팅 로직 |
| `/home/asterisk/WEB/J_IVR/pbx_config.php` | PBX 설정 (LOGI.T_PBX 테이블 사용) |
| `/home/asterisk/WEB/J_IVR/checkCrossCallPrefix.php` | 크로스콜 수신 처리 |
| `/home/asterisk/WEB/J_IVR/saveCallHistory.php` | 통화 이력 저장 |
| `/var/log/asterisk/queue_log` | 큐 이벤트 로그 |
| `/var/log/asterisk/full` | Asterisk 전체 로그 |

---

## 6. 동기화 스크립트

### sync_scenario_to_b.sh
B장비(121.254.239.50:9999)로 LOGI.SCN 동기화

### sync_scenario_all.sh
A/B 모든 장비에 LOGI.SCN 동기화

---

## 7. 테스트 방법

### Q700 → Q900 타임아웃 테스트
1. 07089982240으로 테스트 전화
2. Q700 진입 확인 (queue_log)
3. 10초 후 Q900으로 전환 확인
4. Q900 진입 확인
5. 10초 후 getQStep2 호출 확인

### 확인 명령어
```bash
# Queue 로그 실시간 확인
tail -f /var/log/asterisk/queue_log

# Asterisk 로그에서 DIAL_TIMEOUT 확인
grep -i "DIAL_TIMEOUT" /var/log/asterisk/full | tail -20

# 시나리오 동기화
cd /home/asterisk/WEB/J_IVR
./sync_scenario_all.sh
```

---

## 8. 추가 참고사항

### Queue 로그 이벤트 해석
- `ENTERQUEUE`: 큐 진입
- `RINGNOANSWER|1000`: 1초간 벨 후 무응답 (member timeout)
- `RINGCANCELED|9929`: 약 10초 후 발신자가 취소 (또는 maxwait 도달)
- `ABANDON|1|1|10`: 큐에서 10초 대기 후 포기

### 채널 변수 상속 규칙 (Asterisk)
- `__VAR`: 모든 자식 채널로 상속 (double underscore)
- `_VAR`: 직계 자식 채널에만 상속 (single underscore)
- `VAR`: 상속 안됨 (no underscore)

---

## 9. Q700, Q800, Q900 상담원 가용 설정

### 증상
- Q700, Q800, Q900 상담원이 모두 통화 불가 상태
- T_Q_EXTENSION 테이블에서 is_status=0으로 설정됨
- 크로스콜 테스트 불가

### 원인 분석
LOGI DB T_Q_EXTENSION 테이블에서 모든 상담원의 is_status=0, call_status=NULL

가용 상담원 조건: `is_status = 1 AND call_status = 0`

### 수정 내용
**DB**: LOGI.T_Q_EXTENSION 및 LOGI.T_EXTENSION

```sql
-- T_Q_EXTENSION 가용 설정
UPDATE T_Q_EXTENSION SET is_status = '1', call_status = 0 WHERE q_num = 700 AND ext_number = '7000';
UPDATE T_Q_EXTENSION SET is_status = '1', call_status = 0 WHERE q_num = 800 AND ext_number = '8000';
UPDATE T_Q_EXTENSION SET is_status = '1', call_status = 0 WHERE q_num = 900 AND ext_number = '1003';

-- T_EXTENSION 가용 설정
UPDATE T_EXTENSION SET is_status = '1', call_status = 0 WHERE ext_number = '7000';
UPDATE T_EXTENSION SET is_status = '1', call_status = 0 WHERE ext_number = '8000';
UPDATE T_EXTENSION SET is_status = '1', call_status = 0 WHERE ext_number = '1003';
```

### 수정 결과
| Q번호 | 내선번호 | is_status | call_status | 상태 |
|-------|----------|-----------|-------------|------|
| Q700  | 7000     | 1         | 0           | 가용 |
| Q800  | 8000     | 1         | 0           | 가용 |
| Q900  | 1003     | 1         | 0           | 가용 |

---

## 작성자 정보
- 날짜: 2026-02-05
- 환경: Rocky Linux 9.6, Asterisk 20.17.0, PHP 8.2.30
- 모델: Claude Opus 4.5 (claude-opus-4-5-20251101)
