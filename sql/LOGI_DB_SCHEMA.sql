-- ========================================
-- Table: T_PBX_CONFIG
-- ========================================
CREATE TABLE `T_PBX_CONFIG` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `pbx_id` int(11) NOT NULL COMMENT '?? ?? ID (1=A??, 2=B??, 3=C??)',
  `pbx_name` varchar(50) DEFAULT NULL COMMENT '?? ??',
  `server_ip` varchar(15) NOT NULL COMMENT '?? IP ??',
  `server_name` varchar(50) DEFAULT NULL COMMENT '?? ?? (Primary/Secondary)',
  `crosscall_prefix` varchar(10) NOT NULL COMMENT '???? ?? prefix',
  `q_length` int(11) DEFAULT '3' COMMENT 'Q?? ???',
  `origin_q_length` int(11) DEFAULT '3' COMMENT 'Origin Q?? ???',
  `is_active` char(1) DEFAULT 'Y' COMMENT '??? ??',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`),
  UNIQUE KEY `uk_server_ip` (`server_ip`),
  KEY `idx_pbx_id` (`pbx_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COMMENT='PBX ?? ?? (IP ?? ??? ??)';

-- ========================================
-- Table: T_PBX
-- ========================================
CREATE TABLE `T_PBX` (
  `pbx_id` int(20) NOT NULL AUTO_INCREMENT COMMENT '???id-????',
  `pbx_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '????????? ??',
  `pbx_ip1` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '???ip1',
  `pbx_ip2` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '???ip2',
  `pbx_vip` varchar(16) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'VIP (Service IP)',
  `crosscall_prefix` varchar(10) COLLATE utf8_unicode_ci DEFAULT '99',
  `q_length` int(11) DEFAULT '3',
  `origin_q_length` int(11) DEFAULT '3' COMMENT 'Origin Q?? ??? (NEXT_Q ???)',
  `description` text COLLATE utf8_unicode_ci,
  `create_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mod_datetime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`pbx_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='PBX(???) ?? ?? ???';

-- ========================================
-- Table: T_COMPANY
-- ========================================
CREATE TABLE `T_COMPANY` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `company_name` varchar(64) DEFAULT NULL COMMENT '????',
  `pbx_id` int(20) NOT NULL DEFAULT '1' COMMENT '??? ??? ID',
  `company_level` int(1) NOT NULL COMMENT '????(0:IPPBX??,1:??,2:??)',
  `did_number` varchar(32) NOT NULL DEFAULT '' COMMENT '???DID??? ???????? 070??(????? ??).1???? 1?? 070??? ??',
  `call_line` varchar(32) NOT NULL DEFAULT '' COMMENT '????(???),???? ???? ??? ???? ???? ??? ????',
  `call_line_rider` varchar(32) NOT NULL DEFAULT '' COMMENT '???????(???),???? ?????? ??? ?????? ???? ??? ????',
  `reg_call_line` varchar(32) NOT NULL DEFAULT '' COMMENT '??? ????(???)??? ??,?????? ???? ?? ????(???) ???? ??????? ????? ?',
  `reg_call_line_rider` varchar(32) NOT NULL DEFAULT '' COMMENT '??? ????(???)??? ??, ?????? ???? ?? ???????(???) ???? ??????? ????? ?',
  `master_id` int(10) NOT NULL COMMENT 'IPPBX?? ??(???) id',
  `parent_id` int(10) NOT NULL DEFAULT '0' COMMENT 'IPPBX????? ??? ??(0), ??????? ????? ??? ?? ??ID parent id(?? 2???? parent? 1??)',
  `main_didnumber_range` varchar(500) NOT NULL DEFAULT '' COMMENT 'IPPBX???? ??070?? ??, IPPBX????? ???? ?? ??, ??? DID??(DIDNumber) ????? ??? ??070?? ?? ?? ????? ????',
  `main_queue` varchar(500) DEFAULT NULL COMMENT 'IPPBX???? ??Queue ???, ????? ????? ?? Queue???? ????? "??Queue ???" ???? ????(IPPBX????? ???? ?? ????.)',
  `main_queue_line` varchar(500) DEFAULT NULL COMMENT 'IPPBX???? ??Line ???, ????? ????? ?? ??Line???? ????? "??Line ???" ???? ????,(IPPBX????? ???? ?? ????.)',
  `use_did_route` char(1) DEFAULT 'N' COMMENT '??T_DID_RANGE???? ??-did routing ?? ??(DID??), Y : ??, N : ???',
  `use_cid_route` char(1) DEFAULT 'N' COMMENT '??T_DID_RANGE???? ??-????(cid?? ????? ?,002,031) ?? ??, Y : ??, N : ???',
  `use_db_route` char(1) DEFAULT 'N' COMMENT '??T_DID_RANGE???? ??-?????(DB routing-010) ?? ??, Y : ??, N : ???',
  `to_number` varchar(32) DEFAULT NULL COMMENT 'did ???? ??? routing ? DID ????',
  `main_account_id` varchar(32) DEFAULT '' COMMENT 'IPPBX???? ???? ID, IPPBX????? ???? ?? ??',
  `main_account_pw` varchar(512) DEFAULT '' COMMENT 'IPPBX???? ???? PW, IPPBX????? ???? ?? ??',
  `is_active` char(1) DEFAULT 'Y' COMMENT '?? ?? ''Y'' ??, ''N'' ???',
  `use_queue` varchar(16) DEFAULT '' COMMENT '???? ?(??? ???)',
  `is_re_call_use` char(1) DEFAULT 'N' COMMENT '?? ?? ?? : ''Y'' : ??  ''N''?? ??',
  `is_api_send` char(1) DEFAULT 'Y' COMMENT 'api? ?? ?? ?? ???(N:api??????,Y:?????? ?? ?? ??,F:?? ? ????=>??? ?? ??? ?? ? ? ?? ??? ? ??)',
  `request_api_login_id` varchar(50) DEFAULT '' COMMENT 'api? ?? ?? ??? login id',
  `request_api_datetime` datetime DEFAULT NULL COMMENT 'api ?? ?? ?? ?? ??(?(????)?? N?? ???? ??)',
  `send_api_datetime` datetime DEFAULT NULL COMMENT 'api? ?? ?? ?? ?? ??(API??? ??API? ????? ??)',
  `read_api_datetime` datetime DEFAULT NULL COMMENT 'api? ?? ?? ?? ?? ??(????? ???? ?? ??)',
  `result_api` varchar(500) DEFAULT NULL COMMENT 'api ?? ????',
  `re_call_time` int(11) DEFAULT '0' COMMENT '??? ?? ?? ??( ?????? ?? ??? )',
  `re_call_time_type` char(1) DEFAULT 'S' COMMENT 'S:??????, M:????????',
  `re_call_center_1` char(1) DEFAULT 'F' COMMENT 're_call_type=C?? F:???????????-???????,M:?????',
  `re_call_center_2` char(1) DEFAULT 'M' COMMENT 're_call_type=C?? F:???????????-???????,M:?????',
  `re_call_use_alba_q` char(1) DEFAULT 'N' COMMENT '???????? ??? ?=>Y:??Q??, N:??Q????',
  `re_call_alba_q` varchar(16) DEFAULT '' COMMENT 'DID???? ? ?? ?=>?? ?? Q??',
  `re_call_alba_q_time` int(11) DEFAULT '10' COMMENT 'DID???? ? ?? ?=>????????',
  `use_time_group_rout` char(1) DEFAULT 'N' COMMENT '???? ?? ??? ?? ?? Y : ??, N : ???',
  `use_logout_mode` char(1) DEFAULT 'N' COMMENT '?????? ???? ???? ??? ??',
  `use_holiday` char(1) DEFAULT 'N' COMMENT '?? ?? ?? ??, Y : ??, N : ???',
  `use_worktime` char(1) DEFAULT 'Y' COMMENT '?? ?? ?? ??, Y : ??, N : ???',
  `use_ars` char(1) DEFAULT 'N' COMMENT 'ARS ?? ??, Y : ??, N : ???',
  `use_crosscall` char(1) DEFAULT 'N' COMMENT '??? ? ?? ??, Y : ??, N : ???',
  `monitering_kind` char(1) DEFAULT 'C' COMMENT '??????(C:???,E???&????)',
  `monitering_announce` varchar(512) DEFAULT '' COMMENT '????? ???? ????',
  `monitering_vip_kind` char(1) DEFAULT 'R' COMMENT '?????? VIP ?? ?? ??(R:VIP?? ??, W:VIP????)',
  `monitering_vip_datetime` varchar(19) DEFAULT '' COMMENT 'VIP ? ??? ??',
  `monitering_vip_info` varchar(64) DEFAULT '' COMMENT '????? 5?? ???? VIP ??',
  `monitering_view_sum_call` char(1) DEFAULT 'Y' COMMENT '???(Y:??, N:???)',
  `action` varchar(2) DEFAULT '' COMMENT 'reserve',
  `skill_routing` char(1) NOT NULL DEFAULT '1' COMMENT 'reserve',
  `is_call_wait` char(1) NOT NULL DEFAULT 'N' COMMENT 'reserve',
  `next_dtmf_len` int(11) NOT NULL DEFAULT '0' COMMENT '?? ?? DTMF length',
  `pscn` varchar(11) DEFAULT NULL,
  `wait_time` int(11) NOT NULL DEFAULT '20' COMMENT '??? ?? ??',
  `vac_ment` text COMMENT '????? ???? ? play? ??',
  `vac_ment_dir` varchar(64) DEFAULT NULL COMMENT '????? ???? ? play? ?? path',
  `info_ment` text COMMENT '?? ?? ?? ?? play? ??',
  `info_ment_dir` varchar(64) DEFAULT NULL COMMENT '?? ?? ?? ?? play? ?? path',
  `noinput_ment` text COMMENT 'dtmf ???? play? ??',
  `noinput_ment_dir` varchar(64) DEFAULT 'nip' COMMENT 'dtmf ???? play? ?? path',
  `wronginput_ment` text COMMENT 'dtmf ???? play? ??',
  `wronginput_ment_dir` varchar(64) DEFAULT 'wip' COMMENT 'dtmf ???? play? ?? path',
  `error_ment` text COMMENT 'dtmf ?? ?? ??? play? ??',
  `error_ment_dir` varchar(64) DEFAULT 'err' COMMENT 'dtmf ?? ?? ??? play? ?? path',
  `emergency_number` varchar(32) DEFAULT NULL COMMENT 'PBX?DB??? ? ???? ????',
  `call_update_time` varchar(20) DEFAULT NULL COMMENT '??? call event? ??? ??, call ???? ?? update ?.',
  `max_idle_time` int(11) DEFAULT NULL COMMENT '?? ? ??? ?? call? ??? ??? ??? ??????. ( ? )',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `did_route_description` text COMMENT 'DID ?? ??',
  `ringo` varchar(128) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3122 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_QUEUE
-- ========================================
CREATE TABLE `T_QUEUE` (
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '??(master) ??id : Q? ????? ?? ?? ??? Q? ?????',
  `q_num` varchar(20) NOT NULL DEFAULT '0' COMMENT 'Q ??=>IPBPX??',
  `q_name` varchar(50) DEFAULT NULL COMMENT 'Q ??=>IPBPX??',
  `is_use` char(1) NOT NULL DEFAULT 'Y' COMMENT '???? ?? ??(N:???,Y:??)',
  `is_alba_q` char(1) DEFAULT 'N' COMMENT '??Q ?? ??? ''Y'' ??Q, ''N'' ??Q',
  `hunt_type` char(1) NOT NULL DEFAULT 'E' COMMENT '? ?? ?? -> E:????, A:?? ??, I:????, R:???, P:???? default=E',
  `hunt_time` int(11) NOT NULL DEFAULT '20' COMMENT '?? ?? ??',
  `is_other_q` char(1) NOT NULL DEFAULT 'N' COMMENT '???? ?? Y:?? Q???? ??, N:??? ?? default=N',
  `other_q_num` varchar(20) DEFAULT NULL COMMENT '???? ??(? ?? ???) ?? Q ???? ?? Q??',
  `is_send_pbx` char(1) NOT NULL DEFAULT 'Y' COMMENT 'N:????? ?? ???,Y:??? ????,F:??????? default=Y',
  `request_pbx_login_id` varchar(20) DEFAULT NULL COMMENT 'ipPBX ?? ??? login_id',
  `request_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX ???? ?? ?? ??-?????? ??',
  `send_pbx_datetime` datetime DEFAULT NULL COMMENT 'ippbx? ?? ??',
  `send_pbx_count` int(1) DEFAULT '0' COMMENT 'ippbx? ?? ??(??? ?? 3??? ???)',
  `read_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX ?? ?? ?? ??',
  `result_pbx` varchar(500) DEFAULT NULL COMMENT 'ipPBX ?? ?? ??',
  `phone_avaliable_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? REGISTER?? ? ??? ?? ? ?? ?? ??',
  `phone_login_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? REGISTER?? ?? ( ?? ??? ??? REGI???? ?? ? )',
  `phone_caller_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? REGISTER??? ???? ?? ??',
  `phone_wait_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension ??? ? ( Ring? ??????)',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'Q ??? extension ??? Rinig(Hold) ??? ??(?)',
  `create_datetime` datetime DEFAULT NULL COMMENT 'Q ?? ??',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '????',
  `login_id` varchar(64) DEFAULT NULL COMMENT '??? ??? id',
  PRIMARY KEY (`q_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_Q_EXTENSION
-- ========================================
CREATE TABLE `T_Q_EXTENSION` (
  `q_ext_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '?? master_id',
  `q_num` int(11) DEFAULT NULL COMMENT 'Q  number=>IPBPX??',
  `ext_number` varchar(20) NOT NULL DEFAULT '' COMMENT 'extension ??=>IPBPX??',
  `call_order` int(11) NOT NULL DEFAULT '1' COMMENT '?? ?? Extention(??)? ?? ??  ? ???? ?? ?? ??',
  `re_call_order` int(11) NOT NULL DEFAULT '1' COMMENT '? ?? ? ?? ?? Extention(??)? ?? ?? ? ???? ?? ?? ??',
  `is_send_pbx` char(1) DEFAULT 'Y' COMMENT 'Q? Extention ?? ? ipPbx? N:?? ???,Y:??? ????,F:??????? default=Y',
  `request_pbx_login_id` varchar(20) DEFAULT NULL COMMENT 'ipPBX ?? ??? login_id',
  `request_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX ???? ?? ?? ??',
  `send_pbx_datetime` datetime DEFAULT NULL COMMENT 'ippbx? ?? ??',
  `send_pbx_count` int(1) DEFAULT '0' COMMENT 'ippbx? ?? ??(??? ?? 3??? ???)',
  `read_pbx_datetime` datetime DEFAULT NULL COMMENT 'ipPBX ?? ?? ?? ??',
  `result_pbx` varchar(500) DEFAULT NULL COMMENT 'ipPBX ?? ?? ??',
  `create_datetime` datetime NOT NULL,
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  `company_id` bigint(20) NOT NULL COMMENT '?? - ??id',
  `company_did_number` varchar(20) DEFAULT NULL COMMENT '?? - ?? did ??',
  `agent_id` varchar(12) DEFAULT NULL COMMENT '?? - ???id',
  `agent_name` varchar(20) DEFAULT NULL COMMENT '?? - ????',
  `call_status` int(11) DEFAULT NULL COMMENT '?? - 0 - Idle - Related device(s) are in an idle state.\n1 - InUse - Related device(s) are in active calls but may take more calls.\n2 - Busy - Related device(s) are in active calls and may not take any more calls.\n4 - Unavailable - Related device(s) are not reachable.\n8 - Ringing - Related device(s) are currently ringing.\n9 - InUse&Ringing - Related device(s) are currently ringing and in active calls.\n16 - Hold - Related device(s) are currently on hold.\n17 - InUse&Hold - Related device(s) are currently on hold?? -  and in active calls.',
  `mute_onoff` int(11) DEFAULT NULL COMMENT '?? - 0 : OFF\n1 : ON',
  `hold_onoff` int(11) DEFAULT NULL COMMENT '?? - 0 : OFF\n1 : ON',
  `call_inout` char(1) DEFAULT NULL COMMENT '?? - 0 : IDLE\n1 : ????\n2: ?????',
  `ring_time` int(11) DEFAULT NULL COMMENT '?? - ring ??(?)',
  `mod_status` int(1) DEFAULT '0' COMMENT '?? - ???? ?? ?? ??? ?? O:?????, 1:??????(???),2:?????, 4:??????',
  `is_status` char(1) DEFAULT '0' COMMENT '?? - extension ?? O:?????, 1:??????(???), 2:?????,3:??????,4:??????',
  `is_rec_status` char(1) DEFAULT '0' COMMENT '?? - nRecordPlay ??:0:UNANSWER_RECPLAY_MODE, 1:ANSWER_RECPLAY_MODE',
  `is_mute_status` char(1) DEFAULT '0' COMMENT '?? - nMute??:0:UNMUTE_MODE,1:MUTE_MODE',
  `is_btransfer_stauts` char(1) DEFAULT '0' COMMENT '?? - nBlindTransfer??:0:UNBLINDTRANSFER_MODE , 1:BLINDTRANSFER_MODE',
  `extension_kind` char(1) DEFAULT 'A' COMMENT '?? - A ; ?? ??\nS : ??? ??',
  `cid_number` varchar(20) DEFAULT NULL COMMENT '?? - ????',
  `order_cnt` int(11) DEFAULT '0',
  PRIMARY KEY (`q_ext_id`),
  UNIQUE KEY `unique_values` (`master_id`,`q_num`,`ext_number`)
) ENGINE=InnoDB AUTO_INCREMENT=763 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_Q_COMPANY
-- ========================================
CREATE TABLE `T_Q_COMPANY` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Q ?? id',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '??(master) ??id : Q ??? ????? ?? ?? ??? Q ? ?????. ? ?? ??? q_company_id ???.',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT 'Q??? ??',
  `q_num` varchar(20) NOT NULL COMMENT 'Q ??',
  `did_number` varchar(20) NOT NULL DEFAULT '' COMMENT '?? did??',
  `is_use` char(1) DEFAULT 'Y' COMMENT '???? ?? ??(N:???,Y:??)',
  `create_datetime` datetime DEFAULT NULL COMMENT 'Q ?? ?? ??',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Q ?? ?? ??',
  `login_id` varchar(64) DEFAULT NULL COMMENT '??? ??? id',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_values` (`company_id`,`did_number`,`q_num`)
) ENGINE=InnoDB AUTO_INCREMENT=7806 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_Q_GROUP
-- ========================================
CREATE TABLE `T_Q_GROUP` (
  `q_group_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Q ?? id',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '??(master) ??id : Q ??? ????? ?? ?? ??? Q ? ?????. ? ?? ??? q_company_id ???.',
  `q_group_num` varchar(20) NOT NULL COMMENT 'Q ?? ??',
  `q_group_name` varchar(50) DEFAULT NULL COMMENT 'Q ?? ??',
  `company_id` int(11) DEFAULT '0' COMMENT '????? ??',
  `q_company_id` int(11) DEFAULT '0' COMMENT '????? ??',
  `q_company_did_num` varchar(20) DEFAULT NULL COMMENT '????? ??',
  `is_use` char(1) DEFAULT 'Y' COMMENT '?? ??(N:???,Y:??)',
  `call_order` int(11) DEFAULT '1' COMMENT '???? Q ?? ??? ?? ?? ?? ?? ??',
  `re_call_order` int(11) DEFAULT '1' COMMENT '? ?? ? ???? Q ?? ??? ?? ?? ?? ?? ??',
  `q_group_level` int(11) DEFAULT NULL COMMENT 'level \\\\n1 : ?? / ?? ??? ??\\\\n2 : ??? ??? / ??? ??? ??',
  `phone_avaliable_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? REGISTER?? ? ??? ?? ? ?? ?? ??',
  `phone_login_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? REGISTER?? ?? ( ?? ??? ??? REGI???? ?? ? )',
  `phone_caller_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? REGISTER??? ???? ?? ??',
  `phone_wait_cnt` int(11) DEFAULT NULL COMMENT 'Q ??? extension ??? ? ( Ring? ??????)',
  `phone_vip_cnt` int(11) DEFAULT '0' COMMENT 'VIP ?? ??',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'Q ??? extension ??? Rinig(Hold) ??? ??(?)',
  `phone_talk_time` int(11) DEFAULT NULL COMMENT 'Q ??? extension??? ??? ?? ??(?)',
  `hunt_type` char(1) DEFAULT NULL COMMENT '? ?? ?? -> E:????,  A:?? ??,  I:????,  R:???,  P:????',
  `hunt_time` int(11) DEFAULT NULL COMMENT '?? ?? ??',
  `other_q_group_num` varchar(20) DEFAULT NULL COMMENT '? ?? ??? ?? Q ???? ??',
  `try_datetime` datetime DEFAULT NULL COMMENT 'Q ?? ?? ??',
  `create_datetime` datetime DEFAULT NULL COMMENT 'Q ?? ?? ??',
  `mod_datetime` datetime NOT NULL COMMENT 'Q ?? ?? ??',
  `login_id` varchar(64) DEFAULT NULL COMMENT '??? ??? id',
  PRIMARY KEY (`q_group_id`,`mod_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_EXTENSION
-- ========================================
CREATE TABLE `T_EXTENSION` (
  `ext_id` int(11) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '?? master_id',
  `ext_number` varchar(20) NOT NULL COMMENT 'extension ??, XX->00~99, X-> 0~9',
  `agent_id` varchar(12) DEFAULT NULL COMMENT '???id',
  `agent_name` varchar(20) DEFAULT NULL COMMENT '????',
  `call_status` int(11) DEFAULT NULL COMMENT '100-???. 101-LogOut. 0 - Idle - Related device(s) are in an idle state.\n1 - InUse - Related device(s) are in active calls but may take more calls.\n2 - Busy - Related device(s) are in active calls and may not take any more calls.\n4 - Unavailable - Related device(s) are not reachable.\n8 - Ringing - Related device(s) are currently ringing.\n9 - InUse&Ringing - Related device(s) are currently ringing and in active calls.\n16 - Hold - Related device(s) are currently on hold.\n17 - InUse&Hold - Related device(s) are currently on hold and in active calls.',
  `mute_onoff` int(11) DEFAULT NULL COMMENT '0 : OFF\n1 : ON',
  `hold_onoff` int(11) DEFAULT NULL COMMENT '0 : OFF\n1 : ON',
  `call_inout` char(1) DEFAULT NULL COMMENT '0 : IDLE\n1 : ????\n2: ?????',
  `order_cnt` int(11) DEFAULT '1' COMMENT '???? Default=1',
  `ring_time` int(11) DEFAULT NULL COMMENT 'ring ??(?)',
  `mod_status` int(1) DEFAULT '-1' COMMENT '???? ?? ?? ??? ?? -1:????????, O:?????, 1:??????(???),2:?????, 4:??????',
  `is_status` char(1) DEFAULT '0' COMMENT 'extension ?? O:?????, 1:??????(???), 2:?????,3:??????,4:??????',
  `is_rec_status` char(1) DEFAULT '0' COMMENT 'nRecordPlay ??:0:UNANSWER_RECPLAY_MODE, 1:ANSWER_RECPLAY_MODE',
  `is_mute_status` char(1) DEFAULT '0' COMMENT 'nMute??:0:UNMUTE_MODE,1:MUTE_MODE',
  `is_btransfer_stauts` char(1) DEFAULT '0' COMMENT 'nBlindTransfer??:0:UNBLINDTRANSFER_MODE , 1:BLINDTRANSFER_MODE',
  `extension_kind` char(1) DEFAULT 'A' COMMENT 'A ; ?? ??\nS : ??? ??',
  `cid_number` varchar(20) DEFAULT NULL COMMENT '????',
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  `create_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'YYYY-MM-DD HH:NN:SS',
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT '?? - ??id',
  `ext_company_id` int(10) DEFAULT '0' COMMENT '?? - extension ?? ????id',
  `is_use` char(1) DEFAULT 'N' COMMENT '?? - ???? ?? ??(N:???,Y:??)',
  PRIMARY KEY (`ext_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5326 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_CUSTOMER
-- ========================================
CREATE TABLE `T_CUSTOMER` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT '????',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '???id:c_level=B(????)??? master_id? ??? comp_id=0????? ??? master_id? ?? ?? ?????? ?',
  `company_id` int(11) NOT NULL DEFAULT '0' COMMENT '?? id',
  `c_phone` varchar(20) DEFAULT '' COMMENT '??????',
  `c_name` varchar(20) DEFAULT NULL COMMENT '????',
  `c_level` char(1) NOT NULL DEFAULT 'V' COMMENT '?? level V:VIP, B:BlackList',
  `save_kind` char(1) DEFAULT 'A' COMMENT '????(A:????,M:????)',
  `duration_day` int(11) DEFAULT '0' COMMENT '??,???(VIP? ???? ????)',
  `start_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD hh:mm:ss ????',
  `end_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD hh:mm:ss VIP? ????? ???? ??',
  `create_datetime` char(19) DEFAULT NULL COMMENT '???? YYYY-MM-DD hh:mm:ss',
  `blocking_time` int(11) DEFAULT '0' COMMENT '??????(?)',
  `b_stime` char(5) DEFAULT NULL COMMENT '????? ?? ?? hh:mm',
  `b_etime` char(5) DEFAULT NULL COMMENT '????? ?? ?? hh:mm',
  `b_reg_user_id` int(11) DEFAULT '0' COMMENT '????? ??? ?? ????',
  `b_reg_user_name` varchar(20) DEFAULT 'NULL' COMMENT '????? ??? ??',
  `b_memo` text COMMENT '????? ????',
  `mod_datetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB AUTO_INCREMENT=5377 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_MY_TRANSFER_CALL
-- ========================================
CREATE TABLE `T_MY_TRANSFER_CALL` (
  `transfer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL COMMENT '??id',
  `transfer_company_id` bigint(20) NOT NULL COMMENT 'transfer ?? ID',
  `transfer_did_number` varchar(20) NOT NULL COMMENT '?? DID ??',
  `transfer_q_number` varchar(20) DEFAULT NULL COMMENT 'transfer ?? Q',
  `transfer_company_name` varchar(20) NOT NULL COMMENT '?? ??',
  `transfer_order_num` int(1) NOT NULL DEFAULT '1' COMMENT '?? ??',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36686 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SEQUENCE_TRANSFER_CALL
-- ========================================
CREATE TABLE `T_SEQUENCE_TRANSFER_CALL` (
  `transfer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL COMMENT '??id',
  `transfer_company_id` bigint(20) NOT NULL COMMENT 'transfer ?? ID',
  `transfer_did_number` varchar(20) NOT NULL COMMENT '?? DID ??',
  `transfer_q_number` varchar(20) NOT NULL COMMENT 'transfer ?? Q',
  `transfer_company_name` varchar(20) NOT NULL COMMENT '?? ??',
  `transfer_order_num` int(1) NOT NULL DEFAULT '1' COMMENT '?? ??',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_DIRECT_TRANSFER_CALL
-- ========================================
CREATE TABLE `T_DIRECT_TRANSFER_CALL` (
  `transfer_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) NOT NULL COMMENT '??id',
  `transfer_company_id` bigint(20) NOT NULL COMMENT 'transfer ?? ID',
  `transfer_did_number` varchar(20) NOT NULL COMMENT '?? DID ??',
  `transfer_q_number` varchar(20) NOT NULL COMMENT 'transfer ?? Q',
  `transfer_company_name` varchar(20) NOT NULL COMMENT '?? ??',
  `transfer_order_num` int(1) NOT NULL DEFAULT '1' COMMENT '?? ??',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`transfer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=790 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SET_MY
-- ========================================
CREATE TABLE `T_SET_MY` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `receive_option` char(1) NOT NULL DEFAULT 'T' COMMENT '????->T(Time):?????,  D(Direct):????,  N(No):????',
  `ring_wait_time_my` int(3) NOT NULL DEFAULT '10' COMMENT '?????-????',
  `ring_wait_time_transfer` int(3) NOT NULL DEFAULT '10' COMMENT '?????-?????? ??',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=36591 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SET_SEQUENCE
-- ========================================
CREATE TABLE `T_SET_SEQUENCE` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `receive_option1` char(1) NOT NULL DEFAULT 'T' COMMENT '?????1:H(??/?????), A(?????), M(My Callcent)',
  `receive_option2` char(1) NOT NULL DEFAULT 'T' COMMENT '?????2:?????1:H(??/?????), A(?????), M(My Callcent)',
  `recall_transfer_time` int(3) NOT NULL DEFAULT '10' COMMENT '???? ?? ?? ??(?)',
  `ring_wait_time_transfer` int(3) NOT NULL DEFAULT '10' COMMENT '?????-?????? ??',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_SET_DIRECT
-- ========================================
CREATE TABLE `T_SET_DIRECT` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL DEFAULT '0' COMMENT 'company id',
  `receive_option` char(1) NOT NULL DEFAULT 'T' COMMENT '????:T(Time).?????,D(Direct).????,N(No).????',
  `ring_wait_time_my` int(3) NOT NULL DEFAULT '10' COMMENT '?????-????',
  `ring_wait_time_transfer` int(3) NOT NULL DEFAULT '10' COMMENT '?????-?????? ??',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=759 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_CC_WORKDAY_INFO
-- ========================================
CREATE TABLE `T_CC_WORKDAY_INFO` (
  `cw_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `cw_company_id` bigint(20) NOT NULL COMMENT '?? ID',
  `cw_kind` char(1) DEFAULT '0' COMMENT '??, 1:???, 2:???, 3:???....',
  `cw_is_use` char(1) DEFAULT 'N' COMMENT '?? ?? : ''Y'', ''N''',
  `cw_starttime1` char(4) DEFAULT '0000' COMMENT '????',
  `cw_endtime1` char(4) DEFAULT '0000' COMMENT '????',
  `cw_starttime2` char(4) DEFAULT '0000' COMMENT '????(???)',
  `cw_endtime2` char(4) DEFAULT '0000' COMMENT '????(???)',
  `cw_peak_is_use` char(1) DEFAULT 'N' COMMENT '???? ?? ?? : ''Y'', ''N''',
  `cw_peak_starttime1` char(4) DEFAULT '0000' COMMENT '?? ?? ????',
  `cw_peak_endtime1` char(4) DEFAULT '0000' COMMENT '?? ?? ????',
  `cw_peak_starttime2` char(4) DEFAULT '0000' COMMENT '?? ?? ????(???)',
  `cw_peak_endtime2` char(4) DEFAULT '0000' COMMENT '?? ?? ????(???)',
  `cw_memo` text COMMENT '??',
  `CREATE_DATETIME` datetime DEFAULT NULL,
  `MOD_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`cw_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3739 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_WORKDAY_INFO
-- ========================================
CREATE TABLE `T_WORKDAY_INFO` (
  `w_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `w_company_id` bigint(20) NOT NULL COMMENT '?? ID',
  `w_end_ment` text COMMENT '?? ?? ??',
  `w_end_ment_cv` char(1) DEFAULT NULL,
  `w_end_ment_dir` varchar(64) DEFAULT NULL COMMENT '?? ?? ?? ?? ??',
  `w_end_ment_type` char(1) DEFAULT NULL,
  `w_endtime1` char(4) DEFAULT NULL COMMENT '?? ?? ??',
  `w_endtime2` char(4) DEFAULT NULL COMMENT '?? ?? ?? (???)',
  `w_is_use` char(1) DEFAULT NULL,
  `w_kind` char(1) DEFAULT NULL COMMENT '??, 1:???, 2:???, 3:???....',
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
  `did_prefix` varchar(10) NOT NULL COMMENT 'DID ? ??',
  `prefix_length` int(2) DEFAULT '4' COMMENT 'prefix ??',
  `target_type` varchar(10) NOT NULL DEFAULT 'Q' COMMENT '??? ??',
  `target_number` varchar(20) NOT NULL COMMENT '??? ??',
  `target_scenario` varchar(50) DEFAULT NULL COMMENT '??? ?????',
  `master_id` int(11) DEFAULT '0' COMMENT '??? ?? ID',
  `company_id` int(10) DEFAULT NULL COMMENT '?? ID',
  `priority` int(3) DEFAULT '100' COMMENT '????',
  `description` varchar(100) DEFAULT NULL COMMENT '??',
  `is_active` char(1) DEFAULT 'Y' COMMENT '??? ??',
  `create_datetime` char(19) DEFAULT NULL COMMENT '????',
  `mod_datetime` char(19) DEFAULT NULL COMMENT '????',
  `create_user` varchar(64) DEFAULT NULL COMMENT '???',
  `mod_user` varchar(64) DEFAULT NULL COMMENT '???',
  PRIMARY KEY (`prefix_id`),
  KEY `idx_prefix` (`did_prefix`),
  KEY `idx_active` (`is_active`),
  KEY `idx_company` (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COMMENT='DID Prefix ?? ??? ??';

-- ========================================
-- Table: T_DID_RANGE
-- ========================================
CREATE TABLE `T_DID_RANGE` (
  `did_id` int(11) NOT NULL AUTO_INCREMENT,
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '??master_id',
  `company_id` int(10) NOT NULL COMMENT '??id',
  `did_number` varchar(20) NOT NULL COMMENT 'did ??, XX->00~99, X-> 0~9',
  `did_company_id` int(10) DEFAULT '0' COMMENT '?? - did ?? ????id(????)',
  `is_use` char(1) DEFAULT 'N' COMMENT '???? ?? ??(N:???,Y:??)',
  `use_transfer` char(1) DEFAULT 'N' COMMENT 'DID?? ????(Y:??, N:???)',
  `use_dnd` char(1) DEFAULT 'N' COMMENT '????(DND) ????(Y:??, N:???)',
  `dnd_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `use_cid_route` char(1) DEFAULT 'N' COMMENT '????(cid?? ????? ?,002,031) ?? ??(Y:??,N:???)',
  `use_db_route` char(1) DEFAULT 'N' COMMENT '?????(DB routing-010) ?? ??(Y:??,N:???)',
  `to_number` varchar(20) DEFAULT '' COMMENT '????',
  `did_memo` text COMMENT 'did ?? ??',
  `dnd_memo` text COMMENT '???? ??',
  `create_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `mod_datetime` char(19) DEFAULT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`did_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10442 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_DB_ROUTING
-- ========================================
CREATE TABLE `T_DB_ROUTING` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) DEFAULT NULL,
  `kind` char(1) COLLATE utf8_unicode_ci DEFAULT 'D' COMMENT 'C:cid(????-????002,031...), D:?????(DB???,010...)',
  `from_did` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '??did ??(??????)',
  `si_do` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'T_LOCATION ??? ????(??, ?:031)',
  `si_gun_gu` int(11) DEFAULT '0' COMMENT 'T_LOCATION ????(???)',
  `to_extension` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '??(??) ????',
  `description` text COLLATE utf8_unicode_ci,
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_LOCATION
-- ========================================
CREATE TABLE `T_LOCATION` (
  `si_do` varchar(3) COLLATE utf8_unicode_ci NOT NULL COMMENT '???? ??',
  `si_gun_gu` int(11) NOT NULL COMMENT '???? ???',
  `seq` int(11) NOT NULL COMMENT '???? ??',
  `si_do_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '???(?:???,???)',
  `si_gun_gu_name` varchar(32) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '????(?:???,???)',
  `mod_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`si_do`,`si_gun_gu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_LOCATION_CID
-- ========================================
CREATE TABLE `T_LOCATION_CID` (
  `cid` varchar(32) COLLATE utf8_unicode_ci NOT NULL COMMENT '????-?? ??? ??? ???? ???',
  `si_do` varchar(3) COLLATE utf8_unicode_ci NOT NULL COMMENT '???? ??',
  `si_gun_gu` int(11) NOT NULL COMMENT '???? ???',
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
  `s_action` varchar(2) NOT NULL COMMENT 'DTMF ??? ??? Action : X : Ment Play & DTMF ?? , C : ???? ??, T : ?? ???? ??',
  `s_ars_id` bigint(20) NOT NULL,
  `s_comp_id` bigint(20) NOT NULL COMMENT '?? id',
  `s_dtmf` varchar(4) NOT NULL COMMENT 'DTMF ?? ?',
  `s_level` bigint(20) NOT NULL COMMENT '???? level',
  `s_level_detail` varchar(10) DEFAULT NULL,
  `s_ment` text COMMENT '???? ?? ??',
  `s_ment_cv` char(1) DEFAULT NULL,
  `s_ment_dir` varchar(64) DEFAULT NULL COMMENT '???? ?? path',
  `s_ment_type` char(1) DEFAULT NULL,
  `s_next_dtmf_len` int(11) DEFAULT NULL COMMENT '?? ?? DTMF ??',
  `s_next_level` int(11) NOT NULL COMMENT '???? ????? ???? level',
  `s_pid` bigint(20) DEFAULT NULL COMMENT '???? parent s_id ?',
  `MOD_DATETIME` datetime DEFAULT NULL,
  PRIMARY KEY (`s_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_GROUP_ARS
-- ========================================
CREATE TABLE `T_GROUP_ARS` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL COMMENT '???',
  `company_id` int(10) DEFAULT NULL COMMENT '??ID',
  `memo` text,
  `scn_id` int(10) DEFAULT NULL COMMENT 'T_ARS_SCENAIO? s_id(? scn_id) ?',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `try_datetime` datetime DEFAULT NULL COMMENT '? ?? ?? ??',
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
  `prefix` varchar(10) NOT NULL DEFAULT '99' COMMENT '???? prefix (??)',
  `q_length` int(2) NOT NULL DEFAULT '3' COMMENT 'Q?? ?? (??? ??)',
  `return_prefix` varchar(10) DEFAULT '88' COMMENT '?? ???? prefix',
  `origin_q_length` int(2) DEFAULT '3' COMMENT '?? Q?? ??',
  `description` varchar(100) DEFAULT NULL COMMENT '??',
  `is_active` char(1) DEFAULT 'Y' COMMENT '??? ??',
  `create_datetime` char(19) DEFAULT NULL COMMENT '????',
  `mod_datetime` char(19) DEFAULT NULL COMMENT '????',
  PRIMARY KEY (`config_id`),
  KEY `idx_prefix` (`prefix`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='???? ?? ??';

-- ========================================
-- Table: T_CROSSCALL_LINK
-- ========================================
CREATE TABLE `T_CROSSCALL_LINK` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `link_key` varchar(50) NOT NULL COMMENT '?? ? (original_did_cid ??)',
  `crosscall_did` varchar(50) NOT NULL COMMENT '???? ?? DID (07089984200)',
  `original_linkedid` varchar(50) NOT NULL COMMENT '?? ??? Asterisk linkedid',
  `original_call_id` varchar(50) DEFAULT '' COMMENT '?? ??? call_id (??? ??)',
  `cid` varchar(20) DEFAULT '' COMMENT '??? ??',
  `company_id` int(11) DEFAULT '0' COMMENT '?? ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '?? ??',
  PRIMARY KEY (`seq`),
  UNIQUE KEY `idx_link_key` (`link_key`),
  KEY `idx_crosscall_did` (`crosscall_did`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COMMENT='???? Original Call ID ?? ???';

-- ========================================
-- Table: T_VIP_SET
-- ========================================
CREATE TABLE `T_VIP_SET` (
  `vip_set_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` int(10) NOT NULL COMMENT '???? ?? ??ID',
  `is_use` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y' COMMENT '????(Y:??,N:???)',
  `did_number` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '????',
  `order_period_day` int(2) NOT NULL DEFAULT '1' COMMENT 'VIP??? ????? ??? ? ??(Day)',
  `order_count` int(2) NOT NULL DEFAULT '1' COMMENT 'VIP??? ??? ?? ??',
  `vip_set_memo` text COLLATE utf8_unicode_ci COMMENT 'vip set ??',
  `mod_user` int(10) DEFAULT '0' COMMENT 'vip ?? ??,??? user_id',
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
  `company_id` int(11) NOT NULL COMMENT '?? ID',
  `master_id` int(11) DEFAULT '0' COMMENT '??? id',
  `vip_cnt` int(11) DEFAULT '0' COMMENT 'vip ???',
  `mod_datetime` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '???? ??',
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_RECALL_OPTION
-- ========================================
CREATE TABLE `T_RECALL_OPTION` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `company_id` int(11) NOT NULL COMMENT '??id',
  `is_use` char(1) DEFAULT 'N' COMMENT '?? ?? : ''Y'' : ??  ''N''?? ??',
  `re_call_time` int(11) DEFAULT '0' COMMENT '??? ?? ?? ??( ?????? ?? ??? )',
  `re_call_time_type` char(1) DEFAULT 'S' COMMENT 'S:??????, M:????????',
  `re_call_type` char(1) DEFAULT 'C' COMMENT 'C:???????, Q:???????',
  `re_call_center_1` char(1) DEFAULT 'F' COMMENT 're_call_type=C?? F:???????????,M:?????,C:???????',
  `re_call_center_2` char(1) DEFAULT 'M' COMMENT 're_call_type=C?? F:???????????,M:?????,C:???????',
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
  `is_use` char(1) COLLATE utf8_unicode_ci DEFAULT 'Y' COMMENT '????',
  `time_group_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT '???????',
  `q_num` varchar(20) COLLATE utf8_unicode_ci NOT NULL COMMENT ' ??? master_id? T_QUEUE? ??? q_num',
  `start_time` char(4) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'HHMM',
  `end_time` char(4) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'HHMM',
  `create_datetime` datetime DEFAULT NULL,
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ========================================
-- Table: T_CALL_STATE
-- ========================================
CREATE TABLE `T_CALL_STATE` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT '??-Auto increment',
  `company_id` int(11) NOT NULL COMMENT '??id',
  `company_did` varchar(20) NOT NULL COMMENT '?? did ??',
  `transfer_company_id` int(11) NOT NULL COMMENT 'transfer ?? ??id',
  `transfer_company_did` varchar(20) DEFAULT NULL COMMENT '?????? ?? ??? ???? ?? ?? did ??',
  `caller` varchar(20) NOT NULL COMMENT '?? ??',
  `called` varchar(20) NOT NULL COMMENT '?? ??',
  `start_time` varchar(20) DEFAULT NULL COMMENT '?? ?? ??',
  `ring_time` varchar(20) DEFAULT NULL COMMENT 'ring ??? ??? ??',
  `answer_time` varchar(20) DEFAULT NULL COMMENT '?? ?? ??',
  `end_time` varchar(20) DEFAULT NULL COMMENT '?? ?? ??',
  `call_id` varchar(64) DEFAULT NULL COMMENT 'call id',
  `call_state` char(1) DEFAULT NULL COMMENT 'A : ???? ??(?? ?? ?????), R: Rding ?, C:? ??',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'hold ( ring ) ??? ??',
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_MENU
-- ========================================
CREATE TABLE `T_MENU` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT '0',
  `use_level` int(11) NOT NULL COMMENT 'Menu Level (-1:super(??,????), 0:main, 1:sub)',
  `menu_name` varchar(255) NOT NULL,
  `is_active` char(1) DEFAULT 'Y' COMMENT '?? ??? ?? (Y: Yes, N: No)',
  `use_select_compnay` char(1) DEFAULT 'Y' COMMENT '???? ??Y, ???N',
  `view_name` varchar(255) DEFAULT '' COMMENT '????? ???? ????',
  `description` text COMMENT '????',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
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
  `company_level` int(1) DEFAULT NULL COMMENT '????(-1:Super???(??,????),0:IPPBX??,1:??,2:????)',
  `user_level` varchar(255) DEFAULT 'ROLE_ADMIN',
  `login_id` varchar(50) DEFAULT NULL,
  `login_pwd` varchar(200) DEFAULT NULL COMMENT 't_compnay main_account_pw?, MD5 Hash??+ByteBase64??',
  `login_pwd2` varchar(300) DEFAULT NULL,
  `ars_pwd` varchar(12) DEFAULT NULL COMMENT 'ARS????(ARS?? ? ???,??????)',
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
  `file_dir` varchar(64) NOT NULL DEFAULT '' COMMENT '?? ?? ??',
  `file_name` varchar(32) NOT NULL DEFAULT '' COMMENT '???',
  `memo` text COMMENT '??',
  `mod_datetime` datetime DEFAULT NULL,
  `login_id` varchar(64) DEFAULT NULL COMMENT '??????id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_MODIFY_HISTORY
-- ========================================
CREATE TABLE `T_MODIFY_HISTORY` (
  `seq` int(20) NOT NULL AUTO_INCREMENT COMMENT 'WEB?? ?? ??? ??',
  `company_id` int(20) NOT NULL COMMENT '?? id',
  `login_id` varchar(50) NOT NULL COMMENT '? ??? ID(????? ID)',
  `memu_name` varchar(50) NOT NULL COMMENT '?? ??? ???',
  `button_name` varchar(50) NOT NULL COMMENT '?? ??? ???',
  `is_apply` char(1) DEFAULT 'N' COMMENT 'Y:??,N:???',
  `mod_memo` text COMMENT '?? ?? ??',
  `mod_datetime` char(19) DEFAULT NULL COMMENT '?? ?? ?? YYYY-MM-DD HH:NN:SS',
  `memo` text COMMENT '????',
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_WORK_HISTORY
-- ========================================
CREATE TABLE `T_WORK_HISTORY` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '????',
  `work_content` text COMMENT '????',
  `work_date` varchar(20) NOT NULL COMMENT 'YYYY-MM-DD HH:NN:SS',
  `user_level` varchar(255) NOT NULL,
  `company_id` int(11) DEFAULT NULL,
  `company_name` varchar(20) NOT NULL,
  `work_type` varchar(20) NOT NULL COMMENT '???',
  `work_option` varchar(20) NOT NULL COMMENT '?????:??,??,??,??,IPPBX??..',
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
  `company_id` int(11) NOT NULL COMMENT '??id',
  `s_date` varchar(20) NOT NULL COMMENT 'YYYY-MM-DD',
  `master_id` int(11) NOT NULL DEFAULT '0' COMMENT '?? master_id',
  `called_cnt_fail` int(11) DEFAULT NULL COMMENT '?? ??? ??? , answer_time=0? ?',
  `called_cnt_succ` int(11) DEFAULT NULL COMMENT '?? ??? ??? , answer_time=0? ?? ?',
  `called_time` int(11) DEFAULT NULL COMMENT '?? ??  ??',
  `caller_cnt_fail` int(11) DEFAULT NULL COMMENT '?? ??? ??? , answer_time=0? ?',
  `caller_cnt_succ` int(11) DEFAULT NULL COMMENT '?? ?????? , answer_time=0? ?? ?',
  `caller_time` int(11) DEFAULT NULL COMMENT 'COMMENT ?? ????',
  `all_call_time` int(11) DEFAULT NULL COMMENT '?????~???? ??',
  `login_cnt` int(11) DEFAULT '0',
  `logout_cnt` int(11) DEFAULT '0',
  `sleep_cnt` int(11) DEFAULT '0',
  `call_note` varchar(10) DEFAULT NULL,
  `login_id` varchar(50) NOT NULL,
  `user_phone` varchar(50) NOT NULL DEFAULT '',
  `user_name` varchar(30) DEFAULT '' COMMENT '????',
  PRIMARY KEY (`company_id`,`s_date`,`master_id`,`user_phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ========================================
-- Table: T_AG_STATUS_HISTORY_202602
-- ========================================
CREATE TABLE `T_AG_STATUS_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `master_id` int(11) NOT NULL,
  `user_name` varchar(20) DEFAULT '' COMMENT '??? ??? ??',
  `user_extension` varchar(20) DEFAULT '' COMMENT '??? ?? ????',
  `user_status` char(1) DEFAULT '' COMMENT 'I:???, O:????, P:???',
  `save_time` datetime DEFAULT NULL COMMENT '??? ?? ?? ?? ??',
  `checks` char(1) DEFAULT 'N',
  PRIMARY KEY (`seq`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='??? ?? history ?? table';

-- ========================================
-- Table: T_DAY_LOGIN_HISTORY_202602
-- ========================================
CREATE TABLE `T_DAY_LOGIN_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `master_id` int(11) NOT NULL,
  `user_name` varchar(20) DEFAULT '' COMMENT '??? ??? ??',
  `user_extension` varchar(20) DEFAULT '' COMMENT '??? ?? ????',
  `login_time` datetime DEFAULT NULL COMMENT '??? ??? ??',
  `logout_time` datetime DEFAULT NULL COMMENT '??? ???? ??',
  `work_time` int(11) DEFAULT '0' COMMENT '??? ?? ??',
  `incall_tot_cnt` int(11) DEFAULT '0' COMMENT '??? ? ??? ??',
  `noans_call_cnt` int(11) DEFAULT '0' COMMENT '??? ??? ? ??',
  `user_ip` varchar(20) DEFAULT '' COMMENT '??? ??? IP',
  PRIMARY KEY (`seq`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='??? ??? history ?? table';

-- ========================================
-- Table: T_SVCC_HISTORY_202602
-- ========================================
CREATE TABLE `T_SVCC_HISTORY_202602` (
  `seq` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Auto increment',
  `company_id` int(11) NOT NULL,
  `company_did` varchar(20) NOT NULL COMMENT '??? ??? ID',
  `caller` varchar(20) NOT NULL COMMENT '?? ??',
  `called` varchar(20) NOT NULL COMMENT '?? ??',
  `call_direction` char(1) DEFAULT NULL COMMENT '''O'' ?????, ''I'' ????',
  `svc_type` char(1) DEFAULT NULL COMMENT '1 : DID ?? ??,2 : ????( DB Routing ),3 : ???? ? ?? ?? ??,4 : ???? ?? ?? ??,5 : ???? ?? ?? ??,6:???? ???(Q????),7:ARS?,8:VIP?,9:Blackist?',
  `start_time` varchar(20) DEFAULT NULL COMMENT '?? ?? ??',
  `ring_time` varchar(20) DEFAULT NULL COMMENT 'ring ??? ??? ??',
  `answer_time` varchar(20) DEFAULT NULL COMMENT '?? ?? ??',
  `end_time` varchar(20) DEFAULT NULL COMMENT '?? ?? ??',
  `call_id` varchar(64) DEFAULT NULL COMMENT 'call id',
  `call_result` char(1) DEFAULT NULL COMMENT '?? ?? result''N'' ?? ??''F'' ?? ??''B'' Busy''C'' Cancel''A'' Abnormal',
  `checks` char(1) DEFAULT 'N',
  `phone_hold_time` int(11) DEFAULT NULL COMMENT 'hold ( ring ) ??? ??',
  `master_id` int(11) DEFAULT '0',
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COMMENT='service call history ?? table';

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

