<?php
/*
 * Modification History
 * =====================
 * Date: 2026-01-29
 * Author: System Migration
 * Version: 1.3.0
 * Changes: IP-based PBX detection with T_PBX_CONFIG table
 * - v1.1.0: Added pbx_id support (tr_next_q_pbx_id)
 * - v1.2.0: Added crosscall auto-detection (Q 기반)
 * - v1.3.0: IP 기반 자기 장비 식별 (T_PBX_CONFIG 테이블 사용)
 *   - 서버 IP로 자기 pbx_id 조회 (이중화 지원)
 *   - 장비별 crosscall prefix 동적 조회 (90, 91, 92 등)
 *   - pbx_config.php 헬퍼 함수 사용
 * Backup: /home/asterisk/WEB/J_IVR/backup_20260129/
 *
 * Date: 2026-01-20
 * Changes: Updated for PHP 8.2.30 compatibility
 * - Added error handling for all mysqli_query() calls with SLOG error logging
 * - Added null/false checks before mysqli_fetch_array() and mysqli_num_rows()
 * - Added error handling for all $conn->prepare() calls (if applicable)
 * - Added proper result validation before fetch operations
 * - Added conditional mysqli_close() with connection state check
 * - All modifications marked with "// PHP 8.2 Fix:" comments
 * Backup: /home/asterisk/WEB/J_IVR/getQExtCCDid_new.php.bak.20260120
 */

include 'plog.php';
include_once 'pbx_config.php';  // 2026-01-29 v1.3.0: IP 기반 PBX 설정
include_once 'crosscall_link.php';  // 2026-02-02: CrossCall Original Linkedid 연결

	// 2026-01-29: Helper function to get pbx_id for a given Q number
	function get_pbx_id_for_q($conn, $q_num) {
		if (empty($q_num) || $q_num == '0') {
			return '0';
		}
		$sql = "SELECT c.pbx_id
				FROM T_QUEUE AS q
				INNER JOIN T_COMPANY AS c ON q.master_id = c.master_id AND c.company_level = 0
				WHERE q.q_num = '$q_num'
				LIMIT 1";
		$res = mysqli_query($conn, $sql);
		if ($res && ($row = mysqli_fetch_array($res))) {
			return $row[0] ?? '0';
		}
		return '0';
	}

	function get_q_exten_cc_did( 	$COMPANY_ID, $DID, $CID, $TYPE, $OPTION, $MYQ, $QLIST, $TRCOUNT, $TRORDER, $RECALL_USE, $RECALL_TIME,
					$RECALL_OPT1, $RECALL_OPT2, $RECALL_CNT1, $RECALL_CNT2, $RECALL_USE_AQ, $RECALL_AQ_TIME, $RECALL_ALBAQ,
					$MASTER_ID, $DIDLIST, $PBXIDLIST, $LINKEDID = '', $CALL_ID = '' )
	{
		SLOG( sprintf( '[GET_QE_CC_D %s:%s] START =======================================================================================================', $DID, $CID ) );

                $conn = mysqli_connect(
                  '121.254.239.50',
                  'nautes',
                  'Nautes12@$',
                  'LOGI',
                  '3306');
/**
                $conn = mysqli_connect(
                  '118.67.142.108',
                  'root',
                  'Nautes12@$',
                  'LOGI',
                  '13306');
**/

		$sql = "";

		$RESULT = new stdClass();

		$RESULT->re_call_q 	= '0';
		$RESULT->re_call_ext 	= '';
		$RESULT->re_call_ext_st	= '';

		$RESULT->re_call 	= 'N';
		$RESULT->re_call_q_find = 'N';
		$RESULT->my_q_find 	= 'N';

                $RESULT->tr_next_q      = '0';
                $RESULT->tr_next_did    = '0';
                $RESULT->tr_next_q_pbx_id = '0';  // 2026-01-29: Added for crosscall device detection

		// 2026-01-29 v1.2.0: Crosscall auto-detection fields
		$RESULT->my_q_pbx_id = '0';              // MYQ의 장비 ID
		$RESULT->is_crosscall_required = 'N';   // 크로스콜 필요 여부
		$RESULT->crosscall_dial_number = '';    // 크로스콜 다이얼 번호

		//if( $RECALL_USE_AQ == 'Y' && ( $TYPE == 'GET_CC_SMY' || $TYPE == 'GET_CC_SEQ' ))
		if( $RECALL_USE_AQ == 'Y' && $TYPE == 'GET_ALBA_Q' )
		{
			/* ALBAQ 사용시에는 Crosscall 시간에만 작동한다. 다른 업체로 안넘기고 albaQ 또는 사무실 근무자로 넘긴다. */

			$GAP = '-'.$RECALL_AQ_TIME.' minutes';

			$thirty_minutes_ago = strtotime($GAP);

			$YYYYMM=date('Ym', $thirty_minutes_ago);
			$TABLE='T_CALL_HISTORY_'.$YYYYMM;

			//$sql = "select h.q_group_num, h.userphone  from $TABLE as h inner join T_Q_EXTENSION as q on h.q_group_num = q.q_num inner join T_EXTENSION as e on q.ext_number = e.ext_number where ( h.master_id=$RESULT->master_id or h.company_id=$RESULT->company_id) h.answer_time != '0' and ( h.caller='$CID' or h.called='$CID' ) and h.end_time BETWEEN DATE_SUB(NOW(), INTERVAL $RECALL_TIME MINUTE ) AND NOW() order by h.end_time desc limit 1;";

			$sql = "select h.q_group_num, h.userphone from $TABLE as h where ( master_id=$MASTER_ID or company_id=$COMPANY_ID) and h.answer_time != '0' and h.answer_time != '' and ( h.caller='$CID' or h.called='$CID' ) and h.end_time BETWEEN DATE_SUB(NOW(), INTERVAL $RECALL_AQ_TIME MINUTE ) AND NOW() order by h.end_time desc limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );

			// PHP 8.2 Fix: Add error handling for mysqli_query
			$res = mysqli_query($conn, $sql);
			if ($res === false) {
				SLOG( sprintf( '[GET_RE_CALL %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
			}

			// PHP 8.2 Fix: Check result before mysqli_fetch_array
			if( $res && ($row = mysqli_fetch_array($res)) )
			{
				$RESULT->re_call_q      = $row[0];
				$RESULT->re_call_ext    = $row[1];

				SLOG( sprintf( '[GET_ALBA_RC %s:%s] %s %s', $DID, $CID, $row[0], $row[1] ) );

				$RESULT->re_call        = 'Y';
				$RESULT->re_call_q_find = 'Y';

				SLOG( sprintf( '[GET_RE_CALL %s:%s] RE Q:%s RE EXT:%s', $DID, $CID, $row[0], $row[1] ) );
			}
			// PHP 8.2 Fix: Check result before mysqli_num_rows
			$count = ($res !== false) ? mysqli_num_rows($res) : 0;

			if ( $RESULT->re_call_q_find == 'Y' ) // 재수신 콜 -> 회사근무자 Q
			{
				error_log($sql);
				SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );
				$RESULT->tr_next_q = $MYQ;
				$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
				$RESULT->tr_next_did = $DID;
			}
			else // 신규콜 -> ALBAQ
			{
				$FIND_TRANQ='N';
				SLOG( sprintf( '[GET_RE_CALL %s:%s] NOT FOUND RECALL', $DID, $CID ) );
				$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$RECALL_ALBAQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

				error_log($sql);
				SLOG( sprintf( '[RECALL_TRNQ %s:%s] %s', $DID, $CID, $sql ) );
				// PHP 8.2 Fix: Add error handling for mysqli_query
				$res = mysqli_query($conn, $sql);
				if ($res === false) {
					SLOG( sprintf( '[RECALL_TRNQ %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
				}

				// PHP 8.2 Fix: Check result before mysqli_fetch_array
				if( $res && ($row = mysqli_fetch_array($res)) ) // ALBAQ가 전화를 받을수 있다.
				{
					$RESULT->tr_next_q      = $row[0];
					$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $row[0]);  // 2026-01-29: Added pbx_id
					$RESULT->tr_next_did 	= $DID;
					$FIND_TRANQ='Y';

					SLOG( sprintf( '[RECALL_TRNQ %s:%s] NEXTQ(TRANQ):%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_q_pbx_id ) );
				}
				// PHP 8.2 Fix: Check result before mysqli_num_rows
				$count = ($res !== false) ? mysqli_num_rows($res) : 0;
				if( $FIND_TRANQ == 'N' ) // ALBAQ가 전화를 받지 못한 상태이면 -> 회사 근무자 Q
				{
					$RESULT->tr_next_q      = $MYQ;
					$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
					$RESULT->tr_next_did 	= $DID;

					SLOG( sprintf( '[RECALL_SMYQ %s:%s] NEXTQ(MYQ):%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
				}
			}
		}
		else if( $RECALL_USE_AQ == 'Y' && $TYPE == 'GET_ALBA_Q_NOUSE' ) // ALBAQ를 이용하나 크로스콜 설정시간이 운용/peak 타임이 아닐때 회사 Q로 넘김 
		{
		}
		else
		{
			if( $RECALL_USE == 'Y' ) // 재수신콜 처리
			{
				if( ( $RECALL_OPT1 == 'S' && $TYPE == 'GET_CC_SEQ' ) || ( $RECALL_OPT1 == 'M' && ( $TYPE == 'GET_CC_SMY' || $TYPE == 'GET_CC_SEQ' ) ) )
				{
					$GAP = '-'.$RECALL_TIME.' minutes';

					$thirty_minutes_ago = strtotime($GAP);	

					$YYYYMM=date('Ym', $thirty_minutes_ago);
					$TABLE='T_CALL_HISTORY_'.$YYYYMM;

					//SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $TABLE ) );

					$sql = "select h.q_group_num, h.userphone from $TABLE as h inner join T_Q_EXTENSION as q on h.q_group_num = q.q_num inner join T_EXTENSION as e on q.ext_number = e.ext_number where h.answer_time != '0' and ( h.caller='$CID' or h.called='$CID' ) and h.end_time BETWEEN DATE_SUB(NOW(), INTERVAL $RECALL_TIME MINUTE ) AND NOW() order by h.end_time desc limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );

					// PHP 8.2 Fix: Add error handling for mysqli_query
					$res = mysqli_query($conn, $sql);
					if ($res === false) {
						SLOG( sprintf( '[GET_RE_CALL %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
					}

					// PHP 8.2 Fix: Check result before mysqli_fetch_array
					if( $res && ($row = mysqli_fetch_array($res)) ) 
					{
						$RESULT->re_call_q 	= $row[0];
						$RESULT->re_call_ext 	= $row[1];
						//$RESULT->re_call_ext_st	= $row[2];

						SLOG( sprintf( '[GET_RE_CALL %s:%s] %s %s %s', $DID, $CID, $row[2], $row[1],$row[0] ) );

						$RESULT->re_call 	= 'Y';
						$RESULT->re_call_q_find = 'Y';

						SLOG( sprintf( '[GET_RE_CALL %s:%s] RE Q:%s RE EXT:%s', $DID, $CID, $row[0], $row[1] ) );
					}
					// PHP 8.2 Fix: Check result before mysqli_num_rows
					$count = ($res !== false) ? mysqli_num_rows($res) : 0;

					if ( $RESULT->re_call_q_find == 'Y' )
					{
						$sql = "select call_status from T_EXTENSION where ext_number='$RESULT->re_call_ext' limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );

						// PHP 8.2 Fix: Add error handling for mysqli_query
						$res = mysqli_query($conn, $sql);
						if ($res === false) {
							SLOG( sprintf( '[GET_RE_CALL %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
						}

						// PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) ) 
						{
							$RESULT->re_call_ext_st	= $row[0];
							SLOG( sprintf( '[GET_RE_CALL %s:%s] EXT STATE:%s', $DID, $CID, $row[0] ) );
						}
					}
					else
					{
						SLOG( sprintf( '[GET_RE_CALL %s:%s] NOT FOUND RECALL', $DID, $CID ) );
					}
				}
			}
			if( $RESULT->re_call_q_find == 'Y' )
			{
				if( $RECALL_CNT1 == 'F' )
				{
					$FIND_TRANQ='N';
					if( $RESULT->re_call_ext_st == '0' )
					{
						$RESULT->tr_next_q = $RESULT->re_call_ext;
						$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $RESULT->re_call_ext);  // 2026-01-29: Added pbx_id
						SLOG( sprintf( '[RECALL_TRNQ %s:%s] NEXT EXTEN:%s PBX_ID:%s', $DID, $CID, $RESULT->re_call_ext, $RESULT->tr_next_q_pbx_id ) );
					}
					else
					{
						$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$RESULT->re_call_q' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

						error_log($sql);
						SLOG( sprintf( '[RECALL_TRNQ %s:%s] %s', $DID, $CID, $sql ) );
						// PHP 8.2 Fix: Add error handling for mysqli_query
						$res = mysqli_query($conn, $sql);
						if ($res === false) {
							SLOG( sprintf( '[RECALL_TRNQ %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
						}

						// PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							$RESULT->tr_next_q    	= $row[0];
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $row[0]);  // 2026-01-29: Added pbx_id

							$FIND_TRANQ='Y';

							SLOG( sprintf( '[RECALL_TRNQ %s:%s] NEXTQ(TRANQ):%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_q_pbx_id ) );
						}
						// PHP 8.2 Fix: Check result before mysqli_num_rows
						$count = ($res !== false) ? mysqli_num_rows($res) : 0;
						if( $FIND_TRANQ == 'N' )
						{
							if( $RECALL_CNT2 == 'F' ) // 최초 착신콜센터에 받을 사람이 없으면서 2번째 option이 최초착신콜센터라면 무조건 최초 착신콜센터로 던진다.
							{
								$RESULT->tr_next_q    	= $RESULT->re_call_q;
								$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $RESULT->re_call_q);  // 2026-01-29: Added pbx_id
								//$RESULT->tr_next_did 	= $DID;

								SLOG( sprintf( '[RECALL_SMYQ %s:%s] OP1:F-OP2:F cannot connect both NEXTQ(TRNQ):%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
							}
							else
							{
								$RESULT->tr_next_q    	= $MYQ;
								$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
								$RESULT->tr_next_did 	= $DID;

								SLOG( sprintf( '[RECALL_SMYQ %s:%s] NEXTQ(MYQ):%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
							}
						}
					}
				}
				else if( $RECALL_CNT1 == 'M' )
				{
					$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

					error_log($sql);
					SLOG( sprintf( '[RECALL_SMYQ %s:%s] %s', $DID, $CID, $sql ) );
					// PHP 8.2 Fix: Add error handling for mysqli_query
					$res = mysqli_query($conn, $sql);
					if ($res === false) {
						SLOG( sprintf( '[RECALL_SMYQ %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
					}

					// PHP 8.2 Fix: Check result before mysqli_fetch_array
					if( $res && ($row = mysqli_fetch_array($res)) )
					{
						$RESULT->tr_next_q    	= $row[0];
						$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $row[0]);  // 2026-01-29: Added pbx_id

						$RESULT->my_q_find	='Y';

						SLOG( sprintf( '[RECALL_SMYQ %s:%s] NEXTQ(MYQ):%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_q_pbx_id ) );
					}
					// PHP 8.2 Fix: Check result before mysqli_num_rows
					$count = ($res !== false) ? mysqli_num_rows($res) : 0;

					if( $RESULT->my_q_find == 'N' )
					{
						if( $RESULT->re_call_ext_st == '0' )
						{
							$RESULT->tr_next_q = $RESULT->re_call_ext;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $RESULT->re_call_ext);  // 2026-01-29: Added pbx_id
							SLOG( sprintf( '[RECALL_TRNQ %s:%s] NEXT EXTEN:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
						}
						else
						{
							$RESULT->tr_next_q    	= $RESULT->re_call_q;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $RESULT->re_call_q);  // 2026-01-29: Added pbx_id

							SLOG( sprintf( '[RECALL_TRNQ %s:%s] NEXTQ(TRNQ):%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
						}
						if( $RECALL_CNT2 == 'M' ) // 자사콜센터에 받을 사람이 없으면서 2번째 option이 자사콜이라면 무조건 MYQ로 던진다.
						{
							$RESULT->tr_next_q    	= $MYQ;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did 	= $DID;
							SLOG( sprintf( '[RECALL_TRNQ %s:%s] OP1:M-OP2:M cannot connect both -> NEXTQ(TRNQ):%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
						}
					}
				}
			}
			else
			{
				if( $TYPE == 'GET_CC_SMY' )
				{
					if( $OPTION == 'N' ) // 착신 안함(내콜센터로 들어옴 )
					{
						$RESULT->tr_next_q    	= $MYQ;
						$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
						$RESULT->tr_next_did 	= $DID;

						SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
					}
					else if( $OPTION == 'T' ) //순차 착신
					{
						if( $TRORDER == 0 || $TRORDER == $TRCOUNT )
						{
							$TRORDER = 1;
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );

							$RESULT->tr_next_q      = $MYQ;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did 	= $DID;
							$RESULT->tr_order_id    = 1;
							$RESULT->my_q_find      ='Y';
						}
						else
						{
						}
					}
					else //직접 착신( GET_CC_SMY )
					{
						if( $TRORDER == '0' || $TRORDER > $TRCOUNT )
						{
							$TRORDER = 1;
						}
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s %s %s', $DID, $CID, '^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^', $TRORDER, $TRCOUNT ) );

						for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] 1 %s', $DID, $CID, $TRQLIST ) );
						}
						for( $j=0; $j<$TRORDER-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] 2 %s', $DID, $CID, $TRQLIST ) );
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						// 2026-01-29: Added JOIN with T_QUEUE and T_COMPANY for pbx_id
						$sql = "select m.transfer_q_number, m.transfer_order_num, c.pbx_id from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number INNER JOIN T_QUEUE AS tq ON m.transfer_q_number=tq.q_num INNER JOIN T_COMPANY AS c ON tq.master_id=c.master_id AND c.company_level=0 where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						// PHP 8.2 Fix: Add error handling for mysqli_query
						$res = mysqli_query($conn, $sql);
						if ($res === false) {
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
						}

						// PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_next_q_pbx_id = $row[2] ?? '0';  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_next_q_pbx_id ) );
						}
						else
						{
							$NEWLIST = $QLIST[$TRORDER-1];

							$RESULT->tr_next_q    = $NEWLIST;
							$RESULT->tr_order_id  = $TRORDER;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $NEWLIST);  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] SMY CAHNNEL BUSY NEXTQ:%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id, $RESULT->tr_next_q_pbx_id ) );
						}
						// PHP 8.2 Fix: Check result before mysqli_num_rows
						$count = ($res !== false) ? mysqli_num_rows($res) : 0;
					}



				}
				else if( $TYPE == 'GET_CC_DIR')
				{
					if( $OPTION == 'N' ) // 착신 안함(내콜센터로 들어옴 ) <-- ( GET_CC_DIR 의 option)
					{
						$RESULT->tr_next_q    	= $MYQ;
						$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
						$RESULT->tr_next_did 	= $DID;

						SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_q_pbx_id ) );
					}
					else if( $OPTION == 'T' ) //시간차 착신 <-- ( GET_CC_DIR 의 option)
					{
						//if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
						//{
							$TRORDER = 1;
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );

							$RESULT->tr_next_q      = $MYQ;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did 	= $DID;
							$RESULT->tr_order_id    = 1;
							$RESULT->my_q_find      ='Y';
						//}
						//else
						//{
						//}
					}
					else //직접 착신<--( GET_CC_DIR 의 option)
					{
						if( $TRORDER > $TRCOUNT )
						{
							$TRORDER = 1;
						}
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s %s %s', $DID, $CID, '^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^', $TRORDER, $TRCOUNT ) );

						for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] 1 %s', $DID, $CID, $TRQLIST ) );
						}
						for( $j=0; $j<$TRORDER-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] 2 %s', $DID, $CID, $TRQLIST ) );
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						// 2026-01-29: Added JOIN with T_QUEUE and T_COMPANY for pbx_id
						$sql = "select m.transfer_q_number, m.transfer_order_num, c.pbx_id from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number INNER JOIN T_QUEUE AS tq ON m.transfer_q_number=tq.q_num INNER JOIN T_COMPANY AS c ON tq.master_id=c.master_id AND c.company_level=0 where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						// PHP 8.2 Fix: Add error handling for mysqli_query
						$res = mysqli_query($conn, $sql);
						if ($res === false) {
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
						}

						// PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_next_q_pbx_id = $row[2] ?? '0';  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_next_q_pbx_id ) );
						}
						else
						{
							$NEWLIST = $QLIST[$TRORDER-1];

							$RESULT->tr_next_q    = $NEWLIST;
							$RESULT->tr_order_id  = $TRORDER;
							$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $NEWLIST);  // 2026-01-29: Added pbx_id
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] SMY CAHNNEL BUSY NEXTQ:%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id, $RESULT->tr_next_q_pbx_id ) );
						}
						// PHP 8.2 Fix: Check result before mysqli_num_rows
						$count = ($res !== false) ? mysqli_num_rows($res) : 0;
					}


				}
				else if( $TYPE == 'GET_CC_SEQ' )
				{
					if( $RESULT->re_call == "N" )
					{
						if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
						{
							$TRORDER = 1;
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );

							$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
							// PHP 8.2 Fix: Add error handling for mysqli_query
							$res = mysqli_query($conn, $sql);
							if ($res === false) {
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
							}

							// PHP 8.2 Fix: Check result before mysqli_fetch_array
							if( $res && ($row = mysqli_fetch_array($res)) )
							{
								$RESULT->tr_next_q    	= $row[0];
								$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $row[0]);  // 2026-01-29: Added pbx_id
								$RESULT->tr_order_id  	= 1;
								$RESULT->tr_next_did    = $DID;
								$RESULT->my_q_find	='Y';

								SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $RESULT->tr_order_id, $RESULT->tr_next_q_pbx_id ) );
							}
							// PHP 8.2 Fix: Check result before mysqli_num_rows
							$count = ($res !== false) ? mysqli_num_rows($res) : 0;
							if( $RESULT->my_q_find == 'N' )
							{
								for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
								{
									$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
								}
								for( $j=0; $j<$TRORDER-1; $j++ )
								{
									$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
								}
								$NEWLIST = rtrim($TRQLIST, ", ");
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

								// 2026-01-29: Added JOIN with T_QUEUE and T_COMPANY for pbx_id
								$sql = "select m.transfer_q_number, m.transfer_order_num, c.pbx_id from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number INNER JOIN T_QUEUE AS tq ON m.transfer_q_number=tq.q_num INNER JOIN T_COMPANY AS c ON tq.master_id=c.master_id AND c.company_level=0 where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

								error_log($sql);
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
								// PHP 8.2 Fix: Add error handling for mysqli_query
								$res = mysqli_query($conn, $sql);
								if ($res === false) {
									SLOG( sprintf( '[GET_CC_SEQT %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
								}

								// PHP 8.2 Fix: Check result before mysqli_fetch_array
								if( $res && ($row = mysqli_fetch_array($res)) )
								{
									//error_log($row[0]);

									$RESULT->tr_next_q    = $row[0];
									$RESULT->tr_order_id  = $row[1];
									$RESULT->tr_next_q_pbx_id = $row[2] ?? '0';  // 2026-01-29: Added pbx_id
									$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
									$RESULT->tr_order_id++;

									SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(TRQ):%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_next_q_pbx_id ) );
								}
								else
								{
									$RESULT->tr_next_q    = $MYQ;
									$RESULT->tr_next_did 	= $DID;
									$RESULT->tr_order_id    = 1;
									$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id
									SLOG( sprintf( '[GET_CC_SEQT %s:%s] ALL CHANNEL BUSY -> NEXTQ(TRQ):%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id, $RESULT->tr_next_q_pbx_id ) );
								}
								// PHP 8.2 Fix: Check result before mysqli_num_rows
								$count = ($res !== false) ? mysqli_num_rows($res) : 0;
							}
						}
						else
						{
							for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
							{
								$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
							}
							for( $j=0; $j<$TRORDER-1; $j++ )
							{
								$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
							}
							$NEWLIST = rtrim($TRQLIST, ", ");
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

							// 2026-01-29: Added JOIN with T_QUEUE and T_COMPANY for pbx_id
							$sql = "select m.transfer_q_number, m.transfer_order_num, c.pbx_id from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number INNER JOIN T_QUEUE AS tq ON m.transfer_q_number=tq.q_num INNER JOIN T_COMPANY AS c ON tq.master_id=c.master_id AND c.company_level=0 where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
							// PHP 8.2 Fix: Add error handling for mysqli_query
							$res = mysqli_query($conn, $sql);
							if ($res === false) {
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
							}

							// PHP 8.2 Fix: Check result before mysqli_fetch_array
							if( $res && ($row = mysqli_fetch_array($res)) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    = $row[0];
								$RESULT->tr_order_id  = $row[1];
								$RESULT->tr_next_q_pbx_id = $row[2] ?? '0';  // 2026-01-29: Added pbx_id
								$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
								$RESULT->tr_order_id++;

								SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(TRQ):%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_next_q_pbx_id ) );
							}
							else
							{
								$RESULT->tr_next_q    = $MYQ;
								$RESULT->tr_next_did 	= $DID;
								$RESULT->tr_order_id    = 1;
								$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q($conn, $MYQ);  // 2026-01-29: Added pbx_id

								SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(TRQ):%s NEXTDID:%s TRORDER:%s PBX_ID:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id, $RESULT->tr_next_q_pbx_id ) );
							}
							// PHP 8.2 Fix: Check result before mysqli_num_rows
							$count = ($res !== false) ? mysqli_num_rows($res) : 0;
						}
					}
				}
			}
		}

		//error_log($RESULT );

		// 2026-01-29 v1.3.0: IP 기반 크로스콜 자동 감지
		// 자기 서버 IP로 자기 pbx_id 조회 (한번만, 캐시됨)
		$my_pbx_config = get_my_pbx_config($conn);
		$RESULT->my_q_pbx_id = $my_pbx_config->pbx_id;
		$RESULT->my_server_ip = $my_pbx_config->server_ip;

		SLOG( sprintf( '[GET_QE_CC_D %s:%s] MY_SERVER_IP=%s, MY_PBX_ID=%s, MY_PREFIX=%s',
			$DID, $CID, $my_pbx_config->server_ip, $my_pbx_config->pbx_id, $my_pbx_config->crosscall_prefix ) );

		// 자기 pbx_id와 다음 Q의 pbx_id 비교
		// v1.3.1: MYQ와의 비교 조건 제거 - MYQ가 다른 장비에 있을 수 있음
		if ($RESULT->my_q_pbx_id != '0' && $RESULT->tr_next_q_pbx_id != '0' &&
		    $RESULT->my_q_pbx_id != $RESULT->tr_next_q_pbx_id &&
		    $RESULT->tr_next_q != '0') {
			// 다른 장비로 전송 필요 -> 크로스콜!
			$RESULT->is_crosscall_required = 'Y';

			// 대상 장비의 prefix 조회
			$target_prefix_info = get_target_pbx_prefix($conn, $RESULT->tr_next_q_pbx_id);
			$crosscall_prefix = $target_prefix_info->crosscall_prefix ?? '99';
			$q_length = $target_prefix_info->q_length ?? 3;
			$origin_q_length = $target_prefix_info->origin_q_length ?? 3;

			// v1.4.0: 크로스콜 다이얼 번호 생성 - 통일된 형식
			// 형식: prefix + DID + TARGET_Q + NEXT_Q
			// TARGET_Q: 대상 장비에서 시도할 Q
			// NEXT_Q: TARGET_Q 실패 시 QList에서 다음 순서의 Q
			$TARGET_Q = str_pad($RESULT->tr_next_q, $q_length, '0', STR_PAD_LEFT);

			// QLIST에서 현재 Q 다음 순서의 Q 찾기
			$current_q_index = -1;
			for ($i = 0; $i < $TRCOUNT; $i++) {
				if ($QLIST[$i] == $RESULT->tr_next_q || ltrim($QLIST[$i], '0') == ltrim($RESULT->tr_next_q, '0')) {
					$current_q_index = $i;
					break;
				}
			}
			$next_q_index = ($current_q_index >= 0) ? (($current_q_index + 1) % $TRCOUNT) : 0;
			$NEXT_Q = str_pad($QLIST[$next_q_index] ?? $MYQ, $origin_q_length, '0', STR_PAD_LEFT);
			$RESULT->tr_next_next_q = $QLIST[$next_q_index] ?? $MYQ;
			$RESULT->tr_next_next_q_pbx_id = $PBXIDLIST[$next_q_index] ?? '0';

			$CROSSCALL_DID = $RESULT->tr_next_did != '0' ? $RESULT->tr_next_did : $DID;
			$RESULT->crosscall_dial_number = $crosscall_prefix . $CROSSCALL_DID . $TARGET_Q . $NEXT_Q;
			$RESULT->crosscall_prefix = $crosscall_prefix;

			SLOG( sprintf( '[GET_QE_CC_D %s:%s] CROSSCALL REQUIRED! my_pbx=%s, next_pbx=%s, prefix=%s, TARGET_Q=%s, NEXT_Q=%s, dial=%s',
				$DID, $CID, $RESULT->my_q_pbx_id, $RESULT->tr_next_q_pbx_id, $crosscall_prefix, $TARGET_Q, $NEXT_Q, $RESULT->crosscall_dial_number ) );

			// 2026-02-02: CrossCall 발신 시 Original Linkedid 저장 (key = CROSSCALL_DID + CID)
			if (!empty($LINKEDID)) {
				save_crosscall_link($conn, $CROSSCALL_DID, $CID, $LINKEDID, $CALL_ID, $COMPANY_ID);
			}
		} else {
			// 같은 장비 -> 일반 전송
			$RESULT->is_crosscall_required = 'N';
			$RESULT->crosscall_dial_number = '';
			$RESULT->crosscall_prefix = '';

			SLOG( sprintf( '[GET_QE_CC_D %s:%s] SAME DEVICE, no crosscall. my_pbx=%s, next_pbx=%s',
				$DID, $CID, $RESULT->my_q_pbx_id, $RESULT->tr_next_q_pbx_id ) );
		}

		// PHP 8.2 Fix: Check connection before closing
		if ($conn && !mysqli_connect_errno()) {
			mysqli_close($conn);
		}

		// 데이터 출력후 statement 를 해제한다

		SLOG( sprintf( '[GET_QE_CC_D %s:%s] END   =======================================================================================================', $DID, $CID ) );

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) 
	{
		$args->JSON_REQUEST = $RAW_POST_DATA;
	} 
	else 
	{
		$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE     = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	if (isset($args->JSON_REQUEST)) 
	{
		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) 
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('call getWorkCondition.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		SLOG( sprintf( '[GET_Q_EXTEN CALL] getQExt.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_Q_EXT_TR_DID') 
				{
					$JSON_API_RESULT->JSON_RESULT->CODE	= "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE	= get_q_exten_cc_did( 	$JSON_REQUEST->COMPANY_ID,
													$JSON_REQUEST->DID ,
													$JSON_REQUEST->CID ,
													$JSON_REQUEST->TYPE,
													$JSON_REQUEST->OPTION, 
													$JSON_REQUEST->MYQ, 
													$JSON_REQUEST->QLIST, 
													$JSON_REQUEST->TRCOUNT, 
													$JSON_REQUEST->TRORDER, 
													$JSON_REQUEST->RECALL_USE, 
													$JSON_REQUEST->RECALL_TIME, 
													$JSON_REQUEST->RECALL_OPT1, 
													$JSON_REQUEST->RECALL_OPT2, 
													$JSON_REQUEST->RECALL_CNT1, 
													$JSON_REQUEST->RECALL_CNT2,
													$JSON_REQUEST->RECALL_USE_AQ, 
													$JSON_REQUEST->RECALL_AQ_TIME, 
													$JSON_REQUEST->RECALL_ALBAQ,
													$JSON_REQUEST->MASTER_ID,
													$JSON_REQUEST->DIDLIST,
													$JSON_REQUEST->PBXIDLIST,
													$JSON_REQUEST->LINKEDID ?? '',
													$JSON_REQUEST->CALL_ID ?? '' );

				}
			} 
			else 
			{
				$JSON_API_RESULT->JSON_RESULT->CODE             = "ERROR";
				$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ATTRIBUTE REQ REQUIRED!";
			}
		} 
		else 
		{
			$JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
			$JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ARGUMENT JSON_REQUEST IS NOT VALID STRING OF JSON OBJECT";
		}
	} 
	else 
	{
		$JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
		$JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ARGUMENT JSON_REQUEST NOT DEFINED";
	}

	if (isset($JSON_API_RESULT->OUT_TYPE) && ($JSON_API_RESULT->OUT_TYPE == 'FILE')) {
			if (isset($JSON_API_RESULT->FILE_PATH)) {
					$filepath       = $JSON_API_RESULT->FILE_PATH;
					$filesize       = filesize($filepath);
					$path_parts     = pathinfo($filepath);
					$filename       = $path_parts['basename'];
					$extension      = $path_parts['extension'];

					header("Pragma: public");
					header("Expires: 0");
					header("Content-Type: application/octet-stream");
					header("Content-Disposition: attachment; filename=\"$filename\"");
					header("Content-Transfer-Encoding: binary");
					header("Content-Length: $filesize");

					ob_clean();
					flush();
					readfile($filepath);
			} else {
					echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT);
                }
        } else {
                echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT);
        }
	

?>
