-- LOGI Database Schema
-- Generated: 2026-02-06 01:55:40
-- Encoding: UTF-8

-- ========================================
-- Table: T_PBX_CONFIG
-- ========================================
CREATE TABLE `T_PBX_CONFIG` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `pbx_id` int(11) NOT NULL COMMENT '장비 그룹 ID (1=A장비, 2=B장비, 3=C장비)',
  `pbx_name` varchar(50) DEFAULT NULL COMMENT '장비 이름',
  `server_ip` varchar(15) NOT NULL COMMENT '서버 IP 주소',
  `server_name` varchar(50) DEFAULT NULL COMMENT '서버 이름 (Primary/Secondary)',
  `crosscall_prefix` varchar(10) NOT NULL COMMENT '크로스콜 발신 prefix',
  `q_length` int(11) DEFAULT '3' COMMENT 'Q번호 자릿수',
  `origin_q_length` int(11) DEFAULT '3' COMMENT 'Origin Q번호 자릿수',
  `is_active` char(1) DEFAULT 'Y' COMMENT '활성화 여부',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`),
  UNIQUE KEY `uk_server_ip` (`server_ip`),
  KEY `idx_pbx_id` (`pbx_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='PBX 장비 설정 (IP 기반 이중화 지원)';

-- ========================================
-- Table: T_PBX
-- ========================================
CREATE TABLE `T_PBX` (
  `pbx_id` int(20) NOT NULL AUTO_INCREMENT COMMENT '교환기id-직접입력',
  `pbx_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '교환기구분하기위한 이름',
  `pbx_ip1` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '교환기ip1',
  `pbx_ip2` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '교환기ip2',
  `pbx_vip` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'VIP (Service IP)',
  `crosscall_prefix` varchar(10) COLLATE utf8_unicode_ci DEFAULT '99',
  `q_length` int(11) DEFAULT '3',
  `origin_q_length` int(11) DEFAULT '3' COMMENT 'Origin Q번호 자릿수 (NEXT_Q 패딩용)',
  `description` text COLLATE utf8_unicode_ci,
  `create_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mod_datetime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pbx_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='PBX(교환기) 정보 관리 테이블';

-- ========================================
-- Table: T_COMPANY
-- ========================================
CREATE TABLE `T_COMPANY` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `company_name` varchar(64) DEFAULT NULL COMMENT '업체이름',
  `pbx_id` int(20) NOT NULL DEFAULT '1' COMMENT '연결된 교환기 ID',
  `company_level` int(1) NOT NULL COMMENT '업체레벨(0:IPPBX메인,1:메인,2:지사)',
  `did_number` varchar(32) NOT NULL DEFAULT '' COMMENT '수신용DID번호로 업체에서사용하는 070번호(대표번호와 매핑).1업체별로 1개의 070번호만 할당',
  `call_line` varchar(32) NOT NULL DEFAULT '' COMMENT '발신번호(고객용),업체에서 고객에게 전화시 고객에게 표시되는 업체의 전화번호',
  `call_line_rider` varchar(32) NOT NULL DEFAULT '' COMMENT '상황실발신번호(기사용),업체에서 대리기사에게 전화시 대리기사에게 표시되는 업체의 전화번호',
  `reg_call_line` varchar(32) NOT NULL DEFAULT '' COMMENT '가입용 발신번호(고객용)가입용 정보,엔텔스에서는 가지고만 있고 발신번호(고객용) 설정시에 기본설정값으로 입력되도록 함',
  `reg_call_line_rider` varchar(32) NOT NULL DEFAULT '' COMMENT '가입용 발신번호(기사용)가입용 정보, 엔텔스에서는 가지고만 있고 상황실발신번호(기사용) 설정시에 기본설정값으로 입력되도록 함',
  `master_id` int(10) NOT NULL COMMENT 'IPPBX메인 업체(최상위) id',
  `parent_id` int(10) NOT NULL DEFAULT '0' COMMENT 'IPPBX메인업체인 경우는 없음(0), 메인업체이거나 지사업체인 경우에 상위 업체ID parent id(내가 2레벨이면 parent는 1레벨)',
  `main_didnumber_range` varchar(500) NOT NULL DEFAULT '' COMMENT 'IPPBX메인업체 사용070번호 대역, IPPBX메인업체일 경우에만 값이 있음, 업체의 DID번호(DIDNumber) 엔텔스에서 설정시 사용070번호 대역 안에 속해야지만 설정가능',
  `main_queue` varchar(500) DEFAULT NULL COMMENT 'IPPBX메인업체 사용Queue 리스트, 엔텔스에서 지사업체에 대한 Queue할당시에 메인업체의 "사용Queue 리스트" 있어야만 설정가능(IPPBX메인업체일 경우에만 값이 있습니다.)',
  `main_queue_line` varchar(500) DEFAULT NULL COMMENT 'IPPBX메인업체 사용Line 리스트, 엔텔스에서 지사업체에 대한 키폰Line할당시에 메인업체의 "사용Line 리스트" 있어야만 설정가능,(IPPBX메인업체일 경우에만 값이 있습니다.)',
  `use_did_route` char(1) DEFAULT 'N' COMMENT '삭제T_DID_RANGE테이블에 있음-did routing 사용 유무(DID착신), Y : 사용, N : 미사용',
  `use_cid_route` char(1) DEFAULT 'N' COMMENT '삭제T_DID_RANGE테이블에 있음-수신전환(cid값이 지역번호인 것,002,031) 사용 유무, Y : 사용, N : 미사용',
  `use_db_route` char(1) DEFAULT 'N' COMMENT '삭제T_DID_RANGE테이블에 있음-지역라우팅(DB routing-010) 사용 유무, Y : 사용, N : 미사용',
  `to_number` varchar(32) DEFAULT NULL COMMENT 'did 접속번호 입입시 routing 할 DID 착신번호',
  `main_account_id` varchar(32) DEFAULT '' COMMENT 'IPPBX메인업체 포탈사용 ID, IPPBX메인업체일 경우에만 값이 있음',
  `main_account_pw` varchar(512) DEFAULT '' COMMENT 'IPPBX메인업체 포탈사용 PW, IPPBX메인업체일 경우에만 값이 있음',
  `is_active` char(1) DEFAULT 'Y' COMMENT '사용 유무 ''Y'' 사용, ''N'' 미사용',
  `use_queue` varchar(16) DEFAULT '' COMMENT '사용하는 큐(할당된 큐그룹)',
  `is_re_call_use` char(1) DEFAULT 'N' COMMENT '리콜 사용 유무 : ''Y'' : 사용  ''N''사용 안함',
  `is_api_send` char(1) DEFAULT 'Y' COMMENT 'api로 업체 수정 내용 보내기(N:api보내지않았음,Y:보냈거나보낼 필요 없는 상태,F:보낸 후 적용실패=>실패일 경우 웹에서 확인 후 재 발송 요청할 수 있음)',
  `request_api_login_id` varchar(50) DEFAULT '' COMMENT 'api로 업체 수정 요청한 login id',
  `request_api_datetime` datetime DEFAULT NULL COMMENT 'api 업체 수정 내용 요청 일시(웹(수동적용)에서 N으로 바꿀떄의 시간)',
  `send_api_datetime` datetime DEFAULT NULL COMMENT 'api로 업체 수정 내용 보낸 일시(API데몬이 로지API로 요청할떄의 시간)',
  `read_api_datetime` datetime DEFAULT NULL COMMENT 'api로 업체 수정 결과 받은 일시(로지로부터 응답값을 받은 시간)',
  `result_api` varchar(500) DEFAULT NULL COMMENT 'api 보낸 결과내용',
  `re_call_time` int(11) DEFAULT '0' COMMENT '재수신 기준 시간 설정( 설정시간안에 오면 재수신 )',
  `re_call_time_type` char(1) DEFAULT 'S' COMMENT 'S:순차착신시간, M:내콜센터운영시간',
  `re_call_center_1` char(1) DEFAULT 'F' COMMENT 're_call_type=C일때 F:최초착신콜센터상담내선-최초착신콜센터,M:자사콜센타',
  `re_call_center_2` char(1) DEFAULT 'M' COMMENT 're_call_type=C일때 F:최초착신콜센터상담내선-최초착신콜센터,M:자사콜센타',
  `re_call_use_alba_q` char(1) DEFAULT 'N' COMMENT '그룹설정메뉴에서 값넣어 줌=>Y:알바Q사용, N:알바Q사용안함',
  `re_call_alba_q` varchar(16) DEFAULT '' COMMENT 'DID설정에서 값 넣어 줌=>알바 그룹 Q넘버',
  `re_call_alba_q_time` int(11) DEFAULT '10' COMMENT 'DID설정에서 값 넣어 줌=>알바큐재수신시간',
  `use_time_group_rout` char(1) DEFAULT 'N' COMMENT '시간대별 그룹 라우팅 사용 유무 Y : 사용, N : 미사용',
  `use_logout_mode` char(1) DEFAULT 'N' COMMENT '로그아웃모드 사용으로 설정하면 전화가 안감',
  `use_holiday` char(1) DEFAULT 'N' COMMENT '휴일 체크 사용 유무, Y : 사용, N : 미사용',
  `use_worktime` char(1) DEFAULT 'Y' COMMENT '업무 시간 사용 유무, Y : 사용, N : 미사용',
  `use_ars` char(1) DEFAULT 'N' COMMENT 'ARS 사용 유무, Y : 사용, N : 미사용',
  `use_crosscall` char(1) DEFAULT 'N' COMMENT '크로스 콜 사용 유무, Y : 사용, N : 미사용',
  `monitering_kind` char(1) DEFAULT 'C' COMMENT '모니터링종류(C:콜현황,E콜현황&내선상태)',
  `monitering_announce` varchar(512) DEFAULT '' COMMENT '모티터링에 보여주는 공지내용',
  `monitering_vip_kind` char(1) DEFAULT 'R' COMMENT '모티터링에서 VIP 정보 읽음 여부(R:VIP정보 읽음, W:VIP정보적음)',
  `monitering_vip_datetime` varchar(19) DEFAULT '' COMMENT 'VIP 콜 들어온 시간',
  `monitering_vip_info` varchar(64) DEFAULT '' COMMENT '모니터링에 5초간 보여주는 VIP 정보',
  `monitering_view_sum_call` char(1) DEFAULT 'Y' COMMENT '누적콜(Y:보임, N:안보임)',
  `action` varchar(2) DEFAULT '' COMMENT 'reserve',
  `skill_routing` char(1) NOT NULL DEFAULT '1' COMMENT 'reserve',
  `is_call_wait` char(1) NOT NULL DEFAULT 'N' COMMENT 'reserve',
  `next_dtmf_len` int(11) NOT NULL DEFAULT '0' COMMENT '입력 받을 DTMF length',
  `pscn` varchar(11) DEFAULT NULL,
  `wait_time` int(11) NOT NULL DEFAULT '20' COMMENT '상담원 연결 시간',
  `vac_ment` text COMMENT '시간설정이 안되었을 때 play할 음원',
  `vac_ment_dir` varchar(64) DEFAULT NULL COMMENT '시간설정이 안되었을 때 play할 음원 path',
  `info_ment` text COMMENT '초기 회사 안내 멘트 play할 음원',
  `info_ment_dir` varchar(64) DEFAULT NULL COMMENT '초기 회사 안내 멘트 play할 음원 path',
  `noinput_ment` text COMMENT 'dtmf 미입력시 play할 음원',
  `noinput_ment_dir` varchar(64) DEFAULT 'nip' COMMENT 'dtmf 미입력시 play할 음원 path',
  `wronginput_ment` text COMMENT 'dtmf 오입력시 play할 음원',
  `wronginput_ment_dir` varchar(64) DEFAULT 'wip' COMMENT 'dtmf 오입력시 play할 음원 path',
  `error_ment` text COMMENT 'dtmf 입력 오류 초과시 play할 음원',
  `error_ment_dir` varchar(64) DEFAULT 'err' COMMENT 'dtmf 입력 오류 초과시 play할 음원 path',
  `emergency_number` varchar(32) DEFAULT NULL COMMENT 'PBX나DB장애시 콜 연결하는 전화번호',
  `call_update_time` varchar(20) DEFAULT NULL COMMENT '인입된 call event가 발생한 시간, call 발생할때 마다 update 됨.',
  `max_idle_time` int(11) DEFAULT NULL COMMENT '설정 된 시간값 동안 call이 없을때 알람을 띄워서 표시해줘야함. ( 분 )',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `did_route_description` text COMMENT 'DID 착신 설명',
  `ringo` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3122 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_QUEUE
-- ========================================
CREATE TABLE `T_QUEUE` (
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '상위(master) 업체id : Q를 관리만하며 하위 레벨 업체에 Q를 할당해준다',
  `q_num` varchar(20) NOT NULL DEFAULT '0' COMMENT 'Q 넘버=>IPBPX적용',
  `q_name` varchar(50) DEFAULT NULL COMMENT 'Q 이름=>IPBPX적용',
  `is_use` char(1) NOT NULL DEFAULT 'Y' COMMENT '업체에서 사용 유무(N:미사용,Y:사용)',
  `is_alba_q` char(1) DEFAULT 'N' COMMENT '알바Q 사용 플래그 ''Y'' 알바Q, ''N'' 일반Q',
  `hunt_type` char(1) NOT NULL DEFAULT 'E' COMMENT '호 분배 방식 -> E:균등분배, A:모두 울림, I:입력순서, R:무작위, P:인입비율 default=E',
  `hunt_time` int(11) NOT NULL DEFAULT '20' COMMENT '분배 대기 시간',
  `is_other_q` char(1) NOT NULL DEFAULT 'N' COMMENT '대기시간 이후 Y:다른 Q그룹으로 넘김, N:넘기지 않음 default=N',
  `other_q_num` varchar(20) DEFAULT NULL COMMENT '대기시간 이후(호 연결 실패시) 다른 Q 그룹으로 넘길 Q번호',
  `is_send_pbx` char(1) NOT NULL DEFAULT 'Y' COMMENT 'N:내용수정후 적용 안했음,Y:교환기 적용완료,F:교환기적용실패 default=Y',
  `request_pbx_login_id` varchar(20) DEFAULT NULL COMMENT 'ipPBX 변경 요청한 login_id',
  `request_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX 변경내용 적용 요청 일시-수동적용시만 사용',
  `send_pbx_datetime` datetime DEFAULT NULL COMMENT 'ippbx에 보낸 일시',
  `send_pbx_count` int(1) DEFAULT '0' COMMENT 'ippbx에 보낸 횟수(실패일 경우 3회까지 재전송)',
  `read_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX 수정 결과 받은 일시',
  `result_pbx` varchar(500) DEFAULT NULL COMMENT 'ipPBX 수정 결과 내용',
  `phone_avaliable_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 REGISTER완료 후 전화를 받을 수 있는 상태 갯수',
  `phone_login_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 REGISTER완료 갯수 ( 전화 중이던 아니던 REGI가완료된 단말 수 )',
  `phone_caller_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 REGISTER완료후 통화중인 단말 갯수',
  `phone_wait_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension 대기콜 수 ( Ring이 울리고있는중)',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension 단말의 Rinig(Hold) 울리는 시간(초)',
  `create_datetime` datetime DEFAULT NULL COMMENT 'Q 생성 일자',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자 로그인 id',
  PRIMARY KEY (`q_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_Q_EXTENSION
-- ========================================
CREATE TABLE `T_Q_EXTENSION` (
  `q_ext_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '업체 master_id',
  `q_num` int(11) DEFAULT NULL COMMENT 'Q  number=>IPBPX적용',
  `ext_number` varchar(20) NOT NULL DEFAULT '' COMMENT 'extension 번호=>IPBPX적용',
  `call_order` int(11) NOT NULL DEFAULT '1' COMMENT '여러 개의 Extention(내선)을 가질 경우  콜 수신되는 우선 순위 번호',
  `re_call_order` int(11) NOT NULL DEFAULT '1' COMMENT '재 수신 시 여러 개의 Extention(내선)을 가질 경우 콜 수신되는 우선 순위 번호',
  `is_send_pbx` char(1) DEFAULT 'Y' COMMENT 'Q에 Extention 연결 후 ipPbx에 N:적용 안했음,Y:교환기 적용완료,F:교환기적용실패 default=Y',
  `request_pbx_login_id` varchar(20) DEFAULT NULL COMMENT 'ipPBX 변경 요청한 login_id',
  `request_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX 변경내용 적용 요청 일시',
  `send_pbx_datetime` datetime DEFAULT NULL COMMENT 'ippbx에 보낸 일시',
  `send_pbx_count` int(1) DEFAULT '0' COMMENT 'ippbx에 보낸 횟수(실패일 경우 3회까지 재전송)',
  `read_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX 수정 결과 받은 일시',
  `result_pbx` varchar(500) DEFAULT NULL COMMENT 'ipPBX 수정 결과 내용',
  `create_datetime` datetime NOT NULL,
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  `company_id` bigint(20) NOT NULL COMMENT '삭제 - 업체id',
  `company_did_number` varchar(20) DEFAULT NULL COMMENT '삭제 - 업체 did 번호',
  `agent_id` varchar(12) DEFAULT NULL COMMENT '삭제 - 상담원id',
  `agent_name` varchar(20) DEFAULT NULL COMMENT '삭제 - 상담원명',
  `call_status` int(11) DEFAULT NULL COMMENT '삭제 - 0 - Idle - Related device(s) are in an idle state.\n1 - InUse - Related device(s) are in active calls but may take more calls.\n2 - Busy - Related device(s) are in active calls and may not take any more calls.\n4 - Unavailable - Related device(s) are not reachable.\n8 - Ringing - Related device(s) are currently ringing.\n9 - InUse&Ringing - Related device(s) are currently ringing and in active calls.\n16 - Hold - Related device(s) are currently on hold.\n17 - InUse&Hold - Related device(s) are currently on hold삭제 -  and in active calls.',
  `mute_onoff` int(11) DEFAULT NULL COMMENT '삭제 - 0 : OFF\n1 : ON',
  `hold_onoff` int(11) DEFAULT NULL COMMENT '삭제 - 0 : OFF\n1 : ON',
  `call_inout` char(1) DEFAULT NULL COMMENT '삭제 - 0 : IDLE\n1 : 인바운드\n2: 아웃바운드',
  `ring_time` int(11) DEFAULT NULL COMMENT '삭제 - ring 시간(초)',
  `mod_status` int(1) DEFAULT '0' COMMENT '삭제 - 웹창에서 내선 상태 변경시 사용 O:미사용모드, 1:전화수신모드(로그인),2:휴식중모드, 4:로그아웃모드',
  `is_status` char(1) DEFAULT '0' COMMENT '삭제 - extension 상태 O:미사용모드, 1:전화수신모드(로그인), 2:휴식중모드,3:다른작업모드,4:로그아웃모드',
  `is_rec_status` char(1) DEFAULT '0' COMMENT '삭제 - nRecordPlay 상택:0:UNANSWER_RECPLAY_MODE, 1:ANSWER_RECPLAY_MODE',
  `is_mute_status` char(1) DEFAULT '0' COMMENT '삭제 - nMute상태:0:UNMUTE_MODE,1:MUTE_MODE',
  `is_btransfer_stauts` char(1) DEFAULT '0' COMMENT '삭제 - nBlindTransfer상태:0:UNBLINDTRANSFER_MODE , 1:BLINDTRANSFER_MODE',
  `extension_kind` char(1) DEFAULT 'A' COMMENT '삭제 - A ; 내선 사용\nS : 상황실 번호',
  `cid_number` varchar(20) DEFAULT NULL COMMENT '삭제 - 발신번호',
  `order_cnt` int(11) DEFAULT '0',
  PRIMARY KEY (`q_ext_id`),
  UNIQUE KEY `unique_values` (`master_id`,`q_num`,`ext_number`)
) ENGINE=InnoDB AUTO_INCREMENT=763 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_Q_COMPANY
-- ========================================
CREATE TABLE `T_Q_COMPANY` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Q 업체 id',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '상위(master) 업체id : Q 그룹을 관리만하며 하위 레벨 업체에 Q 를 할당해준다. 그 하위 업체가 q_company_id 값이다.',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Q할당된 업체',
  `q_num` varchar(20) NOT NULL COMMENT 'Q 넘버',
  `did_number` varchar(20) NOT NULL DEFAULT '' COMMENT '업체 did번호',
  `is_use` char(1) DEFAULT 'Y' COMMENT '업체에서 사용 유무(N:미사용,Y:사용)',
  `create_datetime` datetime DEFAULT NULL COMMENT 'Q 그룹 생성 일자',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Q 그룹 수정 일자',
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자 로그인 id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_values` (`company_id`,`did_number`,`q_num`)
) ENGINE=InnoDB AUTO_INCREMENT=7806 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_Q_GROUP
-- ========================================
CREATE TABLE `T_Q_GROUP` (
  `q_group_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Q 그룹 id',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '상위(master) 업체id : Q 그룹을 관리만하며 하위 레벨 업체에 Q 를 할당해준다. 그 하위 업체가 q_company_id 값이다.',
  `q_group_num` varchar(20) NOT NULL COMMENT 'Q 그룹 넘버',
  `q_group_name` varchar(50) DEFAULT NULL COMMENT 'Q 그룹 이름',
  `company_id` int(11) DEFAULT '0' COMMENT '삭제해야할 필드',
  `q_company_id` int(11) DEFAULT '0' COMMENT '삭제해야할 필드',
  `q_company_did_num` varchar(20) DEFAULT NULL COMMENT '삭제해야할 필드',
  `is_use` char(1) DEFAULT 'Y' COMMENT '사용 유무(N:미사용,Y:사용)',
  `call_order` int(11) DEFAULT '1' COMMENT '여러개의 Q 그룹 넘버를 가질 경우 우선 순위 번호',
  `re_call_order` int(11) DEFAULT '1' COMMENT '재 수신 시 여러개의 Q 그룹 넘버를 가질 경우 우선 순위 번호',
  `q_group_level` int(11) DEFAULT NULL COMMENT 'level \\\\n1 : 알바 / 재택 근무자 그룹\\\\n2 : 고용된 상담원 / 사무실 상담원 그룹',
  `phone_avaliable_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 REGISTER완료 후 전화를 받을 수 있는 상태 갯수',
  `phone_login_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 REGISTER완료 갯수 ( 전화 중이던 아니던 REGI가완료된 단말 수 )',
  `phone_caller_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 REGISTER완료후 통화중인 단말 갯수',
  `phone_wait_cnt` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension 대기콜 수 ( Ring이 울리고있는중)',
  `phone_vip_cnt` int(11) DEFAULT '0' COMMENT 'VIP 대기 콜수',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension 단말의 Rinig(Hold) 울리는 시간(초)',
  `phone_talk_time` int(11) DEFAULT NULL COMMENT 'Q 소속된 extension단말이 연결된 통화 시간(초)',
  `hunt_type` char(1) DEFAULT NULL COMMENT '호 분배 방식 -> E:균등분배,  A:모두 울림,  I:입력순서,  R:무작위,  P:인입비율',
  `hunt_time` int(11) DEFAULT NULL COMMENT '분배 대기 시간',
  `other_q_group_num` varchar(20) DEFAULT NULL COMMENT '호 연결 실패시 다른 Q 그룹으로 넘김',
  `try_datetime` datetime DEFAULT NULL COMMENT 'Q 연결 시도 시간',
  `create_datetime` datetime DEFAULT NULL COMMENT 'Q 그룹 생성 일자',
  `mod_datetime` datetime NOT NULL COMMENT 'Q 그룹 수정 일자',
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자 로그인 id',
  PRIMARY KEY (`q_group_id`,`mod_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_EXTENSION
-- ========================================
CREATE TABLE `T_EXTENSION` (
  `ext_id` int(11) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '업체 master_id',
  `ext_number` varchar(20) NOT NULL COMMENT 'extension 번호, XX->00~99, X-> 0~9',
  `agent_id` varchar(12) DEFAULT NULL COMMENT '상담원id',
  `agent_name` varchar(20) DEFAULT NULL COMMENT '상담원명',
  `call_status` int(11) DEFAULT NULL COMMENT '100-휴식중. 101-LogOut. 0 - Idle - Related device(s) are in an idle state.\n1 - InUse - Related device(s) are in active calls but may take more calls.\n2 - Busy - Related device(s) are in active calls and may not take any more calls.\n4 - Unavailable - Related device(s) are not reachable.\n8 - Ringing - Related device(s) are currently ringing.\n9 - InUse&Ringing - Related device(s) are currently ringing and in active calls.\n16 - Hold - Related device(s) are currently on hold.\n17 - InUse&Hold - Related device(s) are currently on hold and in active calls.',
  `mute_onoff` int(11) DEFAULT NULL COMMENT '0 : OFF\n1 : ON',
  `hold_onoff` int(11) DEFAULT NULL COMMENT '0 : OFF\n1 : ON',
  `call_inout` char(1) DEFAULT NULL COMMENT '0 : IDLE\n1 : 인바운드\n2: 아웃바운드',
  `order_cnt` int(11) DEFAULT '1' COMMENT '우선순위 Default=1',
  `ring_time` int(11) DEFAULT NULL COMMENT 'ring 시간(초)',
  `mod_status` int(1) DEFAULT '-1' COMMENT '웹창에서 내선 상태 변경시 사용 -1:상태변경요청없음, O:미사용모드, 1:전화수신모드(로그인),2:휴식중모드, 4:로그아웃모드',
  `is_status` char(1) DEFAULT '0' COMMENT 'extension 상태 O:미사용모드, 1:전화수신모드(로그인), 2:휴식중모드,3:다른작업모드,4:로그아웃모드',
  `is_rec_status` char(1) DEFAULT '0' COMMENT 'nRecordPlay 상택:0:UNANSWER_RECPLAY_MODE, 1:ANSWER_RECPLAY_MODE',
  `is_mute_status` char(1) DEFAULT '0' COMMENT 'nMute상태:0:UNMUTE_MODE,1:MUTE_MODE',
  `is_btransfer_stauts` char(1) DEFAULT '0' COMMENT 'nBlindTransfer상태:0:UNBLINDTRANSFER_MODE , 1:BLINDTRANSFER_MODE',
  `extension_kind` char(1) DEFAULT 'A' COMMENT 'A ; 내선 사용\nS : 상황실 번호',
  `cid_number` varchar(20) DEFAULT NULL COMMENT '발신번호',
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  `create_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'YYYY-MM-DD HH:NN:SS',
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT '삭제 - 업체id',
  `ext_company_id` int(10) DEFAULT '0' COMMENT '삭제 - extension 번호 사용업체id',
  `is_use` char(1) DEFAULT 'N' COMMENT '삭제 - 업체에서 사용 유무(N:미사용,Y:사용)',
  PRIMARY KEY (`ext_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5326 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_CUSTOMER
-- ========================================
CREATE TABLE `T_CUSTOMER` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT '자동증가',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '마스타id:c_level=B(거부고객)일경우 master_id에 값넣고 comp_id=0값으로하여 동일한 master_id일 경우 모두 거부고객으로 함',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '지사 id',
  `c_phone` varchar(20) DEFAULT '' COMMENT '고객전화번호',
  `c_name` varchar(20) DEFAULT NULL COMMENT '고객이름',
  `c_level` char(1) NOT NULL DEFAULT 'V' COMMENT '고객 level V:VIP, B:BlackList',
  `save_kind` char(1) DEFAULT 'A' COMMENT '등록방법(A:자동저장,M:수동저장)',
  `duration_day` int(11) DEFAULT '0' COMMENT '유지,지속일(VIP나 거부고객 지속일자)',
  `start_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD hh:mm:ss 등록일시',
  `end_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD hh:mm:ss VIP나 블랙리스트 등록해제 일시',
  `create_datetime` char(19) DEFAULT NULL COMMENT '등록일시 YYYY-MM-DD hh:mm:ss',
  `blocking_time` int(11) DEFAULT '0' COMMENT '차단지속시간(분)',
  `b_stime` char(5) DEFAULT NULL COMMENT '블랙리스트 작동 시간 hh:mm',
  `b_etime` char(5) DEFAULT NULL COMMENT '블랙리스트 해제 시간 hh:mm',
  `b_reg_user_id` int(11) DEFAULT '0' COMMENT '블랙리스트 등록한 사람 사원코드',
  `b_reg_user_name` varchar(20) DEFAULT 'NULL' COMMENT '블랙리스트 등록한 사람',
  `b_memo` text COMMENT '블랙리스트 등록이유',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB AUTO_INCREMENT=5377 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_MY_TRANSFER_CALL
-- ========================================
CREATE TABLE `T_MY_TRANSFER_CALL` (
  `transfer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL COMMENT '업체id',
  `transfer_company_id` bigint(20) NOT NULL COMMENT 'transfer 업체 ID',
  `transfer_did_number` varchar(20) NOT NULL COMMENT '업체 DID 번호',
  `transfer_q_number` varchar(20) DEFAULT NULL COMMENT 'transfer 업체 Q',
  `transfer_company_name` varchar(20) NOT NULL COMMENT '업체 이름',
  `transfer_order_num` int(1) NOT NULL DEFAULT '1' COMMENT '착신 순서',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36686 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SEQUENCE_TRANSFER_CALL
-- ========================================
CREATE TABLE `T_SEQUENCE_TRANSFER_CALL` (
  `transfer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL COMMENT '업체id',
  `transfer_company_id` bigint(20) NOT NULL COMMENT 'transfer 업체 ID',
  `transfer_did_number` varchar(20) NOT NULL COMMENT '업체 DID 번호',
  `transfer_q_number` varchar(20) NOT NULL COMMENT 'transfer 업체 Q',
  `transfer_company_name` varchar(20) NOT NULL COMMENT '업체 이름',
  `transfer_order_num` int(1) NOT NULL DEFAULT '1' COMMENT '착신 순서',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_DIRECT_TRANSFER_CALL
-- ========================================
CREATE TABLE `T_DIRECT_TRANSFER_CALL` (
  `transfer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL COMMENT '업체id',
  `transfer_company_id` bigint(20) NOT NULL COMMENT 'transfer 업체 ID',
  `transfer_did_number` varchar(20) NOT NULL COMMENT '업체 DID 번호',
  `transfer_q_number` varchar(20) NOT NULL COMMENT 'transfer 업체 Q',
  `transfer_company_name` varchar(20) NOT NULL COMMENT '업체 이름',
  `transfer_order_num` int(1) NOT NULL DEFAULT '1' COMMENT '착신 순서',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=790 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SET_MY
-- ========================================
CREATE TABLE `T_SET_MY` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `receive_option` char(1) NOT NULL DEFAULT 'T' COMMENT '착신옵션->T(Time):시간차착신,  D(Direct):직접착신,  N(No):착신안함',
  `ring_wait_time_my` int(3) NOT NULL DEFAULT '10' COMMENT '링대기시간-내콜센타',
  `ring_wait_time_transfer` int(3) NOT NULL DEFAULT '10' COMMENT '링대기시간-다음콜센타로 착신',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36591 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SET_SEQUENCE
-- ========================================
CREATE TABLE `T_SET_SEQUENCE` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `receive_option1` char(1) NOT NULL DEFAULT 'T' COMMENT '재수신옵션1:H(알바/재택상담원), A(최초상담원), M(My Callcent)',
  `receive_option2` char(1) NOT NULL DEFAULT 'T' COMMENT '재수신옵션2:재수신옵션1:H(알바/재택상담원), A(최초상담원), M(My Callcent)',
  `recall_transfer_time` int(3) NOT NULL DEFAULT '10' COMMENT '재수신시 강제 착신 시간(분)',
  `ring_wait_time_transfer` int(3) NOT NULL DEFAULT '10' COMMENT '링대기시간-다음콜센타로 착신',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SET_DIRECT
-- ========================================
CREATE TABLE `T_SET_DIRECT` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `receive_option` char(1) NOT NULL DEFAULT 'T' COMMENT '착신옵션:T(Time).시간차착신,D(Direct).직접착신,N(No).착신안함',
  `ring_wait_time_my` int(3) NOT NULL DEFAULT '10' COMMENT '링대기시간-내콜센타',
  `ring_wait_time_transfer` int(3) NOT NULL DEFAULT '10' COMMENT '링대기시간-다음콜센타로 착신',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=759 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_CC_WORKDAY_INFO
-- ========================================
CREATE TABLE `T_CC_WORKDAY_INFO` (
  `cw_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cw_company_id` bigint(20) NOT NULL COMMENT '업체 ID',
  `cw_kind` char(1) DEFAULT '0' COMMENT '요일, 1:일요일, 2:월요일, 3:화요일....',
  `cw_is_use` char(1) DEFAULT 'N' COMMENT '사용 유무 : ''Y'', ''N''',
  `cw_starttime1` char(4) DEFAULT '0000' COMMENT '시작시간',
  `cw_endtime1` char(4) DEFAULT '0000' COMMENT '종료시간',
  `cw_starttime2` char(4) DEFAULT '0000' COMMENT '시작시간(다음날)',
  `cw_endtime2` char(4) DEFAULT '0000' COMMENT '종료시간(다음날)',
  `cw_peak_is_use` char(1) DEFAULT 'N' COMMENT '피크타임 사용 유무 : ''Y'', ''N''',
  `cw_peak_starttime1` char(4) DEFAULT '0000' COMMENT '피크 타임 시작시간',
  `cw_peak_endtime1` char(4) DEFAULT '0000' COMMENT '피크 타임 종료시간',
  `cw_peak_starttime2` char(4) DEFAULT '0000' COMMENT '피크 타임 시작시간(다음날)',
  `cw_peak_endtime2` char(4) DEFAULT '0000' COMMENT '피크 타임 종료시간(다음날)',
  `cw_memo` text COMMENT '설명',
  `CREATE_DATETIME` datetime DEFAULT NULL,
  `MOD_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`cw_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3739 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_WORKDAY_INFO
-- ========================================
CREATE TABLE `T_WORKDAY_INFO` (
  `w_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `w_company_id` bigint(20) NOT NULL COMMENT '업체 ID',
  `w_end_ment` text COMMENT '업무 종료 멘트',
  `w_end_ment_cv` char(1) DEFAULT NULL,
  `w_end_ment_dir` varchar(64) DEFAULT NULL COMMENT '업무 종료 멘트 저장 위치',
  `w_end_ment_type` char(1) DEFAULT NULL,
  `w_endtime1` char(4) DEFAULT NULL COMMENT '업무 종료 시간',
  `w_endtime2` char(4) DEFAULT NULL COMMENT '업부 종료 시간 (다음날)',
  `w_is_use` char(1) DEFAULT NULL,
  `w_kind` char(1) DEFAULT NULL COMMENT '요일, 1:일요일, 2:월요일, 3:화요일....',
  `w_meal1_endtime` char(4) DEFAULT NULL,
  `w_meal1_is_use` char(1) DEFAULT NULL,
  `w_meal1_ment` text,
  `w_meal1_ment_cv` char(1) DEFAULT NULL,
  `w_meal1_ment_dir` varchar(64) DEFAULT NULL,
  `w_meal1_ment_type` char(1) DEFAULT NULL,
  `w_meal1_starttime` char(4) DEFAULT NULL,
  `w_meal2_endtime` char(4) DEFAULT NULL,
  `w_meal2_is_use` char(1) DEFAULT NULL,
  `w_meal2_ment` text,
  `w_meal2_ment_cv` char(1) DEFAULT NULL,
  `w_meal2_ment_dir` varchar(64) DEFAULT NULL,
  `w_meal2_ment_type` char(1) DEFAULT NULL,
  `w_meal2_starttime` char(4) DEFAULT NULL,
  `w_ment` text,
  `w_ment_cv` char(1) DEFAULT NULL,
  `w_ment_dir` varchar(64) DEFAULT NULL,
  `w_ment_type` char(1) DEFAULT NULL,
  `w_starttime1` char(4) DEFAULT NULL,
  `w_starttime2` char(4) DEFAULT NULL,
  `w_use_callback` char(1) DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `create_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`w_id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_HOLIDAY
-- ========================================
CREATE TABLE `T_HOLIDAY` (
  `h_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `CREATE_DATETIME` datetime DEFAULT NULL,
  `h_company_id` bigint(20) NOT NULL,
  `h_enddate` char(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `h_kind` varchar(1) COLLATE utf8_unicode_ci NOT NULL,
  `h_memo` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `h_memt` mediumtext COLLATE utf8_unicode_ci,
  `h_memt_dir` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `h_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `h_startdate` char(4) COLLATE utf8_unicode_ci DEFAULT NULL,
  `MOD_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`h_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_DID_PREFIX
-- ========================================
CREATE TABLE `T_DID_PREFIX` (
  `prefix_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `did_prefix` varchar(10) NOT NULL COMMENT 'DID 앞 자리',
  `prefix_length` int(2) DEFAULT '4' COMMENT 'prefix 길이',
  `target_type` varchar(10) NOT NULL DEFAULT 'Q' COMMENT '라우팅 타입',
  `target_number` varchar(20) NOT NULL COMMENT '연결할 번호',
  `target_scenario` varchar(50) DEFAULT NULL COMMENT '연결할 시나리오명',
  `master_id` int(11) DEFAULT '0' COMMENT '마스터 회사 ID',
  `company_id` int(10) DEFAULT NULL COMMENT '회사 ID',
  `priority` int(3) DEFAULT '100' COMMENT '우선순위',
  `description` varchar(100) DEFAULT NULL COMMENT '설명',
  `is_active` char(1) DEFAULT 'Y' COMMENT '활성화 여부',
  `create_datetime` char(19) DEFAULT NULL COMMENT '생성일시',
  `mod_datetime` char(19) DEFAULT NULL COMMENT '수정일시',
  `create_user` varchar(64) DEFAULT NULL COMMENT '생성자',
  `mod_user` varchar(64) DEFAULT NULL COMMENT '수정자',
  PRIMARY KEY (`prefix_id`),
  KEY `idx_prefix` (`did_prefix`),
  KEY `idx_active` (`is_active`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='DID Prefix 기반 라우팅 설정';

-- ========================================
-- Table: T_DID_RANGE
-- ========================================
CREATE TABLE `T_DID_RANGE` (
  `did_id` int(11) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '업체master_id',
  `company_id` int(10) NOT NULL COMMENT '업체id',
  `did_number` varchar(20) NOT NULL COMMENT 'did 번호, XX->00~99, X-> 0~9',
  `did_company_id` int(10) DEFAULT '0' COMMENT '삭제 - did 번호 사용업체id(연동지사)',
  `is_use` char(1) DEFAULT 'N' COMMENT '업체에서 사용 유무(N:미사용,Y:사용)',
  `use_transfer` char(1) DEFAULT 'N' COMMENT 'DID착신 사용유무(Y:사용, N:미사용)',
  `use_dnd` char(1) DEFAULT 'N' COMMENT '수신거부(DND) 사용유무(Y:사용, N:미사용)',
  `dnd_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `use_cid_route` char(1) DEFAULT 'N' COMMENT '수신전환(cid값이 지역번호인 것,002,031) 사용 유무(Y:사용,N:미사용)',
  `use_db_route` char(1) DEFAULT 'N' COMMENT '지역라우팅(DB routing-010) 사용 유무(Y:사용,N:미사용)',
  `to_number` varchar(20) DEFAULT '' COMMENT '전환번호',
  `did_memo` text COMMENT 'did 착신 설명',
  `dnd_memo` text COMMENT '수신거부 설명',
  `create_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `mod_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`did_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10442 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_DB_ROUTING
-- ========================================
CREATE TABLE `T_DB_ROUTING` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) DEFAULT NULL,
  `kind` char(1) COLLATE utf8_unicode_ci DEFAULT 'D' COMMENT 'C:cid(수신전환-지역번호002,031...), D:지역라우팅(DB라우팅,010...)',
  `from_did` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '최초did 번호(업체대표번호)',
  `si_do` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'T_LOCATION 고객의 지역번호(시도, 예:031)',
  `si_gun_gu` int(11) DEFAULT '0' COMMENT 'T_LOCATION 지역코드(시군구)',
  `to_extension` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '전환(넘길) 전화번호',
  `description` text COLLATE utf8_unicode_ci,
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_LOCATION
-- ========================================
CREATE TABLE `T_LOCATION` (
  `si_do` varchar(3) COLLATE utf8_unicode_ci NOT NULL COMMENT '지역코드 시도',
  `si_gun_gu` int(11) NOT NULL COMMENT '지역코드 시군구',
  `seq` int(11) NOT NULL COMMENT '보여주는 순번',
  `si_do_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '시도명(예:서울시,경기도)',
  `si_gun_gu_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '시군구명(예:강남구,수원시)',
  `mod_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`si_do`,`si_gun_gu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_LOCATION_CID
-- ========================================
CREATE TABLE `T_LOCATION_CID` (
  `cid` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT '고객번호-해당 테이블 정보는 로지에서 입려함',
  `si_do` varchar(3) COLLATE utf8_unicode_ci NOT NULL COMMENT '지역코드 시도',
  `si_gun_gu` int(11) NOT NULL COMMENT '지역코드 시군구',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `memo` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_ARS_INFO
-- ========================================
CREATE TABLE `T_ARS_INFO` (
  `ARS_ID` int(11) NOT NULL AUTO_INCREMENT,
  `ARS_INSERT_CNT` int(11) NOT NULL,
  `ARS_PRIVATE_IP` varchar(20) DEFAULT NULL,
  `ARS_PUBLIC_IP` varchar(20) DEFAULT NULL,
  `FTP_ID` varchar(20) DEFAULT NULL,
  `FTP_PORT` int(4) DEFAULT NULL,
  `FTP_PWD` varchar(20) DEFAULT NULL,
  `IS_USE` char(1) NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`ARS_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_ARS_SCENARIO
-- ========================================
CREATE TABLE `T_ARS_SCENARIO` (
  `s_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `CREATE_DATETIME` datetime DEFAULT NULL,
  `s_action` varchar(2) NOT NULL COMMENT 'DTMF 입력시 해야할 Action : X : Ment Play & DTMF 수집 , C : 상담그룹 연결, T : 멘트 플레이후 종료',
  `s_ars_id` bigint(20) NOT NULL,
  `s_comp_id` bigint(20) NOT NULL COMMENT '업체 id',
  `s_dtmf` varchar(4) NOT NULL COMMENT 'DTMF 입력 값',
  `s_level` bigint(20) NOT NULL COMMENT '시나리오 level',
  `s_level_detail` varchar(10) DEFAULT NULL,
  `s_ment` text COMMENT '플레이할 멘트 내용',
  `s_ment_cv` char(1) DEFAULT NULL,
  `s_ment_dir` varchar(64) DEFAULT NULL COMMENT '플레이한 멘트 path',
  `s_ment_type` char(1) DEFAULT NULL,
  `s_next_dtmf_len` int(11) DEFAULT NULL COMMENT '입력 받을 DTMF 길이',
  `s_next_level` int(11) NOT NULL COMMENT '다음으로 진행해야할 시나리오 level',
  `s_pid` bigint(20) DEFAULT NULL COMMENT '시나리오 parent s_id 값',
  `MOD_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_GROUP_ARS
-- ========================================
CREATE TABLE `T_GROUP_ARS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL COMMENT '그룹명',
  `company_id` int(10) DEFAULT NULL COMMENT '업체ID',
  `memo` text,
  `scn_id` int(10) DEFAULT NULL COMMENT 'T_ARS_SCENAIO의 s_id(구 scn_id) 값',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `try_datetime` datetime DEFAULT NULL COMMENT '호 연결 시도 시간',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_GROUP_ARS_LIST
-- ========================================
CREATE TABLE `T_GROUP_ARS_LIST` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_ars_id` int(10) DEFAULT NULL,
  `company_id` int(10) DEFAULT NULL,
  `q_group_id` int(10) DEFAULT NULL,
  `scn_id` int(10) DEFAULT NULL,
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=124 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_GROUP_ARS_SCENARIO
-- ========================================
CREATE TABLE `T_GROUP_ARS_SCENARIO` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(11) DEFAULT NULL,
  `group_ars_id` int(11) DEFAULT NULL,
  `scn_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_CROSSCALL_CONFIG
-- ========================================
CREATE TABLE `T_CROSSCALL_CONFIG` (
  `config_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `prefix` varchar(10) NOT NULL DEFAULT '99' COMMENT '크로스콜 prefix (고정)',
  `q_length` int(2) NOT NULL DEFAULT '3' COMMENT 'Q번호 길이 (뒤에서 추출)',
  `return_prefix` varchar(10) DEFAULT '88' COMMENT '반환 크로스콜 prefix',
  `origin_q_length` int(2) DEFAULT '3' COMMENT '원래 Q번호 길이',
  `description` varchar(100) DEFAULT NULL COMMENT '설명',
  `is_active` char(1) DEFAULT 'Y' COMMENT '활성화 여부',
  `create_datetime` char(19) DEFAULT NULL COMMENT '생성일시',
  `mod_datetime` char(19) DEFAULT NULL COMMENT '수정일시',
  PRIMARY KEY (`config_id`),
  KEY `idx_prefix` (`prefix`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='크로스콜 수신 설정';

-- ========================================
-- Table: T_CROSSCALL_LINK
-- ========================================
CREATE TABLE `T_CROSSCALL_LINK` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `link_key` varchar(50) NOT NULL COMMENT '조회 키 (original_did_cid 형식)',
  `crosscall_did` varchar(50) NOT NULL COMMENT '크로스콜 대상 DID (07089984200)',
  `original_linkedid` varchar(50) NOT NULL COMMENT '원본 통화의 Asterisk linkedid',
  `original_call_id` varchar(50) DEFAULT '' COMMENT '원본 통화의 call_id (시스템 생성)',
  `cid` varchar(20) DEFAULT '' COMMENT '발신자 번호',
  `company_id` int(11) DEFAULT '0' COMMENT '회사 ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `idx_link_key` (`link_key`),
  KEY `idx_crosscall_did` (`crosscall_did`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COMMENT='크로스콜 Original Call ID 연결 테이블';

-- ========================================
-- Table: T_VIP_SET
-- ========================================
CREATE TABLE `T_VIP_SET` (
  `vip_set_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL COMMENT '해당번호 소속 회사ID',
  `is_use` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y' COMMENT '사용유무(Y:사용,N:미사용)',
  `did_number` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '대표번호',
  `order_period_day` int(2) NOT NULL DEFAULT '1' COMMENT 'VIP지정용 오더회수를 카운트 할 기간(Day)',
  `order_count` int(2) NOT NULL DEFAULT '1' COMMENT 'VIP지정용 기간내 오더 횟수',
  `vip_set_memo` text COLLATE utf8_unicode_ci COMMENT 'vip set 설명',
  `mod_user` int(10) DEFAULT '0' COMMENT 'vip 설정 추가,수정한 user_id',
  `mod_datetime` char(19) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `user_id` int(11) DEFAULT NULL,
  `vip_policy_order_count` int(1) DEFAULT NULL,
  `vip_policy_period_days` varchar(512) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`vip_set_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_VIP_COUNT
-- ========================================
CREATE TABLE `T_VIP_COUNT` (
  `company_id` int(11) NOT NULL COMMENT '업체 ID',
  `master_id` int(11) DEFAULT '0' COMMENT '마스터 id',
  `vip_cnt` int(11) DEFAULT '0' COMMENT 'vip 카운트',
  `mod_datetime` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '업데이트 시간',
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_RECALL_OPTION
-- ========================================
CREATE TABLE `T_RECALL_OPTION` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `company_id` int(11) NOT NULL COMMENT '업체id',
  `is_use` char(1) DEFAULT 'N' COMMENT '사용 유무 : ''Y'' : 사용  ''N''사용 안함',
  `re_call_time` int(11) DEFAULT '0' COMMENT '재수신 기준 시간 설정( 설정시간안에 오면 재수신 )',
  `re_call_time_type` char(1) DEFAULT 'S' COMMENT 'S:순차착신시간, M:내콜센터운영시간',
  `re_call_type` char(1) DEFAULT 'C' COMMENT 'C:착신콜센터기준, Q:상담원그룹기준',
  `re_call_center_1` char(1) DEFAULT 'F' COMMENT 're_call_type=C일때 F:최초착신콜센터상담내선,M:자사콜센타,C:최초착신콜센터',
  `re_call_center_2` char(1) DEFAULT 'M' COMMENT 're_call_type=C일때 F:최초착신콜센터상담내선,M:자사콜센타,C:최초착신콜센터',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_TIME_GROUP_ROUTING
-- ========================================
CREATE TABLE `T_TIME_GROUP_ROUTING` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `master_id` bigint(20) DEFAULT NULL,
  `company_id` bigint(20) DEFAULT NULL,
  `is_use` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y' COMMENT '사용유무',
  `time_group_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT '시간대별그룹명',
  `q_num` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT ' 동일한 master_id인 T_QUEUE의 선택된 q_num',
  `start_time` char(4) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'HHMM',
  `end_time` char(4) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'HHMM',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_CALL_STATE
-- ========================================
CREATE TABLE `T_CALL_STATE` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT '삭제-Auto increment',
  `company_id` int(11) NOT NULL COMMENT '업체id',
  `company_did` varchar(20) NOT NULL COMMENT '업체 did 번호',
  `transfer_company_id` int(11) NOT NULL COMMENT 'transfer 했던 업체id',
  `transfer_company_did` varchar(20) DEFAULT NULL COMMENT '크로스콜에서 다른 업체로 연결할때 해당 업체 did 번호',
  `caller` varchar(20) NOT NULL COMMENT '발신 번호',
  `called` varchar(20) NOT NULL COMMENT '착신 번호',
  `start_time` varchar(20) DEFAULT NULL COMMENT '통화 시작 시간',
  `ring_time` varchar(20) DEFAULT NULL COMMENT 'ring 울리기 시작한 시간',
  `answer_time` varchar(20) DEFAULT NULL COMMENT '통화 연결 시간',
  `end_time` varchar(20) DEFAULT NULL COMMENT '통화 종료 시간',
  `call_id` varchar(64) DEFAULT NULL COMMENT 'call id',
  `call_state` char(1) DEFAULT NULL COMMENT 'A : 시나리오 진행(호가 아직 안넘어갔음), R: Rding 중, C:호 연결',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'hold ( ring ) 울리는 시간',
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_MENU
-- ========================================
CREATE TABLE `T_MENU` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT '0',
  `use_level` int(11) NOT NULL COMMENT 'Menu Level (-1:super(로지,나우테스), 0:main, 1:sub)',
  `menu_name` varchar(255) NOT NULL,
  `is_active` char(1) DEFAULT 'Y' COMMENT '메뉴 활성화 유무 (Y: Yes, N: No)',
  `use_select_compnay` char(1) DEFAULT 'Y' COMMENT '업체선택 사용Y, 미사용N',
  `view_name` varchar(255) DEFAULT '' COMMENT '메뉴선택시 보여지는 화면이름',
  `description` text COMMENT '메뉴설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`menu_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `T_MENU_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `T_MENU` (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_PHONE_AUTH
-- ========================================
CREATE TABLE `T_PHONE_AUTH` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `auth_date` datetime DEFAULT NULL,
  `jsession` varchar(20) DEFAULT NULL,
  `phone_auth_key` varchar(20) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB AUTO_INCREMENT=6162 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_USER_LIST_API
-- ========================================
CREATE TABLE `T_USER_LIST_API` (
  `user_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) DEFAULT NULL,
  `company_level` int(1) DEFAULT NULL COMMENT '업체레벨(-1:Super관리자(로지,나우테스),0:IPPBX메인,1:메인,2:지사업체)',
  `user_level` varchar(255) DEFAULT 'ROLE_ADMIN',
  `login_id` varchar(50) DEFAULT NULL,
  `login_pwd` varchar(200) DEFAULT NULL COMMENT 't_compnay main_account_pw값, MD5 Hash생성+ByteBase64처리',
  `login_pwd2` varchar(300) DEFAULT NULL,
  `ars_pwd` varchar(12) DEFAULT NULL COMMENT 'ARS인증번호(ARS에서 값 넣어줌,로그인시확인)',
  `user_name` varchar(255) DEFAULT NULL,
  `user_phone` varchar(12) DEFAULT NULL,
  `login_datetime` datetime DEFAULT NULL,
  `user_mail` varchar(100) DEFAULT NULL,
  `auto_close_count` int(11) DEFAULT '0',
  `blacklist_time` datetime DEFAULT NULL,
  `create_datetime` datetime DEFAULT NULL,
  `is_auto_close` char(1) DEFAULT 'N',
  `is_blacklist` char(1) DEFAULT NULL,
  `is_popup_mode` char(1) DEFAULT NULL,
  `memo` varchar(255) DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `ws_bind_status` char(1) DEFAULT NULL,
  `ws_connection_id` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_UPLOAD_FILE
-- ========================================
CREATE TABLE `T_UPLOAD_FILE` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `master_id` int(10) NOT NULL DEFAULT '0' COMMENT 'master_id',
  `file_kind` char(1) NOT NULL DEFAULT 'R' COMMENT 'R:RingGo, A:ARS',
  `file_dir` varchar(64) NOT NULL DEFAULT '' COMMENT '파일 저장 위치',
  `file_name` varchar(32) NOT NULL DEFAULT '' COMMENT '파일명',
  `memo` text COMMENT '설명',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '수정자로그인id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_MODIFY_HISTORY
-- ========================================
CREATE TABLE `T_MODIFY_HISTORY` (
  `seq` int(20) NOT NULL AUTO_INCREMENT COMMENT 'WEB에서 내용 변경시 입력',
  `company_id` int(20) NOT NULL COMMENT '업체 id',
  `login_id` varchar(50) NOT NULL COMMENT '웹 로그인 ID(수정작업한 ID)',
  `memu_name` varchar(50) NOT NULL COMMENT '변경 작업한 메뉴명',
  `button_name` varchar(50) NOT NULL COMMENT '변경 작업한 버튼명',
  `is_apply` char(1) DEFAULT 'N' COMMENT 'Y:적용,N:미적용',
  `mod_memo` text COMMENT '변경 작업 내용',
  `mod_datetime` char(19) DEFAULT NULL COMMENT '변경 작업 일시 YYYY-MM-DD HH:NN:SS',
  `memo` text COMMENT '변경내용',
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_WORK_HISTORY
-- ========================================
CREATE TABLE `T_WORK_HISTORY` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '작업자명',
  `work_content` text COMMENT '작업내용',
  `work_date` varchar(20) NOT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `user_level` varchar(255) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `company_name` varchar(20) NOT NULL,
  `work_type` varchar(20) NOT NULL COMMENT '메뉴명',
  `work_option` varchar(20) NOT NULL COMMENT '실행버튼명:검색,추가,수정,삭제,IPPBX적용..',
  PRIMARY KEY (`seq`),
  KEY `work_date` (`work_date`)
) ENGINE=InnoDB AUTO_INCREMENT=3833 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: refresh_token
-- ========================================
CREATE TABLE `refresh_token` (
  `jkey` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `jvalue` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`jkey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_CALL_HISTORY_202602
-- ========================================
CREATE TABLE `T_CALL_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `q_group_num` varchar(20) DEFAULT NULL,
  `userid` varchar(20) NOT NULL,
  `userphone` varchar(20) NOT NULL,
  `user_name` varchar(30) DEFAULT NULL,
  `caller` varchar(20) NOT NULL,
  `called` varchar(20) NOT NULL,
  `call_direction` char(1) DEFAULT NULL,
  `call_id` varchar(128) DEFAULT NULL,
  `start_time` varchar(20) DEFAULT NULL,
  `start_time_long` int(11) DEFAULT NULL,
  `answer_time` varchar(20) DEFAULT NULL,
  `answer_time_long` int(11) DEFAULT NULL,
  `end_time` varchar(20) DEFAULT NULL,
  `end_time_long` int(11) DEFAULT NULL,
  `call_result` char(1) DEFAULT NULL,
  `phone_hold_time` int(11) DEFAULT NULL,
  `checks` char(1) DEFAULT 'N',
  PRIMARY KEY (`seq`),
  KEY `start_time` (`start_time`),
  KEY `end_time` (`end_time`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_STATISTICS_202602
-- ========================================
CREATE TABLE `T_STATISTICS_202602` (
  `company_id` int(11) NOT NULL COMMENT '업체id',
  `s_date` varchar(20) NOT NULL COMMENT 'YYYY-MM-DD',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '업체 master_id',
  `called_cnt_fail` int(11) DEFAULT NULL COMMENT '착신 부재중 카운트 , answer_time=0인 값',
  `called_cnt_succ` int(11) DEFAULT NULL COMMENT '착신 부재중 카운트 , answer_time=0이 아닌 값',
  `called_time` int(11) DEFAULT NULL COMMENT '착신 통화  분수',
  `caller_cnt_fail` int(11) DEFAULT NULL COMMENT '발신 부재중 카운트 , answer_time=0인 값',
  `caller_cnt_succ` int(11) DEFAULT NULL COMMENT '발신 부재중카운트 , answer_time=0이 아닌 값',
  `caller_time` int(11) DEFAULT NULL COMMENT 'COMMENT 발신 통화분수',
  `all_call_time` int(11) DEFAULT NULL COMMENT '링시간부터~응답까지 시간',
  `login_cnt` int(11) DEFAULT '0',
  `logout_cnt` int(11) DEFAULT '0',
  `sleep_cnt` int(11) DEFAULT '0',
  `call_note` varchar(10) DEFAULT NULL,
  `login_id` varchar(50) NOT NULL,
  `user_phone` varchar(50) NOT NULL DEFAULT '',
  `user_name` varchar(30) DEFAULT '' COMMENT '상담원명',
  PRIMARY KEY (`company_id`,`s_date`,`master_id`,`user_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_AG_STATUS_HISTORY_202602
-- ========================================
CREATE TABLE `T_AG_STATUS_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `master_id` int(11) NOT NULL,
  `user_name` varchar(20) DEFAULT '' COMMENT '사용자 로그인 이름',
  `user_extension` varchar(20) DEFAULT '' COMMENT '사용자 내선 전화번호',
  `user_status` char(1) DEFAULT '' COMMENT 'I:로그인, O:로그아웃, P:휴식중',
  `save_time` datetime DEFAULT NULL COMMENT '상담원 상태 변경 저장 시간',
  `checks` char(1) DEFAULT 'N',
  PRIMARY KEY (`seq`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='상담원 상태 history 월별 table';

-- ========================================
-- Table: T_DAY_LOGIN_HISTORY_202602
-- ========================================
CREATE TABLE `T_DAY_LOGIN_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `master_id` int(11) NOT NULL,
  `user_name` varchar(20) DEFAULT '' COMMENT '사용자 로그인 이름',
  `user_extension` varchar(20) DEFAULT '' COMMENT '사용자 내선 전화번호',
  `login_time` datetime DEFAULT NULL COMMENT '상담원 로그인 시간',
  `logout_time` datetime DEFAULT NULL COMMENT '상담원 로그아웃 시간',
  `work_time` int(11) DEFAULT '0' COMMENT '상담원 근무 시간',
  `incall_tot_cnt` int(11) DEFAULT '0' COMMENT '상담원 총 수신콜 갯수',
  `noans_call_cnt` int(11) DEFAULT '0' COMMENT '상담원 부재중 콜 갯수',
  `user_ip` varchar(20) DEFAULT '' COMMENT '사용자 로그인 IP',
  PRIMARY KEY (`seq`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='상담원 로그인 history 월별 table';

-- ========================================
-- Table: T_SVCC_HISTORY_202602
-- ========================================
CREATE TABLE `T_SVCC_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `company_id` int(11) NOT NULL,
  `company_did` varchar(20) NOT NULL COMMENT '사용자 로그인 ID',
  `caller` varchar(20) NOT NULL COMMENT '발신 번호',
  `called` varchar(20) NOT NULL COMMENT '착신 번호',
  `call_direction` char(1) DEFAULT NULL COMMENT '''O'' 아웃바운드, ''I'' 인바운드',
  `svc_type` char(1) DEFAULT NULL COMMENT '1 : DID 기준 착신,2 : 수신전환( DB Routing ),3 : 크로스콜 미 운영 시간 착신,4 : 크로스콜 운영 시간 착신,5 : 크로스콜 피크 시간 착신,6:크로스콜 미설정(Q로호전달),7:ARS콜,8:VIP콜,9:Blackist콜',
  `start_time` varchar(20) DEFAULT NULL COMMENT '통화 시작 시간',
  `ring_time` varchar(20) DEFAULT NULL COMMENT 'ring 울리기 시작한 시간',
  `answer_time` varchar(20) DEFAULT NULL COMMENT '통화 연결 시간',
  `end_time` varchar(20) DEFAULT NULL COMMENT '통화 종료 시간',
  `call_id` varchar(64) DEFAULT NULL COMMENT 'call id',
  `call_result` char(1) DEFAULT NULL COMMENT '통화 종료 result''N'' 정상 종료''F'' 응답 없음''B'' Busy''C'' Cancel''A'' Abnormal',
  `checks` char(1) DEFAULT 'N',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'hold ( ring ) 울리는 시간',
  `master_id` int(11) DEFAULT '0',
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COMMENT='service call history 월별 table';

-- ========================================
-- Table: T_SVCC_STATISTICS_202602
-- ========================================
CREATE TABLE `T_SVCC_STATISTICS_202602` (
  `company_id` int(11) NOT NULL,
  `company_did` varchar(20) NOT NULL,
  `s_date` varchar(20) NOT NULL,
  `svc_did_rt_cnt` int(11) DEFAULT NULL,
  `svc_db_rt_cnt` int(11) DEFAULT NULL,
  `svc_cc_end_cnt` int(11) DEFAULT NULL,
  `svc_cc_work_cnt` int(11) DEFAULT NULL,
  `svc_cc_peak_cnt` int(11) DEFAULT NULL,
  `svc_cc_q_cnt` int(11) DEFAULT NULL,
  PRIMARY KEY (`company_id`,`s_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

