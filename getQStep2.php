<?php
/*
 * Modification History
 * =====================
 * Date: 2026-01-29
 * Author: System Migration
 * Version: 1.5.0
 * Changes: IP-based PBX detection with T_PBX_CONFIG table
 * - v1.0.0: PHP 8.2.30 compatibility
 * - v1.1.0: Added crosscall auto-detection
 * - v1.2.0: Added crosscall incoming (return) scenario support
 * - v1.3.0: Added fallback-to-MYQ handling for crosscall incoming scenario
 * - v1.4.0: IP 기반 자기 장비 식별 (T_PBX_CONFIG 테이블 사용)
 *   - 서버 IP로 자기 pbx_id 조회 (이중화 지원)
 *   - 장비별 crosscall prefix 동적 조회 (90, 91, 92 등)
 *   - pbx_config.php 헬퍼 함수 사용
 * - v1.4.1: return_prefix 대신 crosscall_prefix 사용
 *   - 반환 크로스콜도 대상 Q의 pbx_id에 해당하는 crosscall_prefix 사용
 *   - 형식: crosscall_prefix + DID + ORIGIN_Q (3자리)
 * - v1.5.0: 통일된 크로스콜 형식 (A/B/C 모든 장비 동일 모듈)
 *   - 형식: crosscall_prefix + DID + TARGET_Q + NEXT_Q
 *   - TARGET_Q: 대상 장비에서 시도할 Q번호
 *   - NEXT_Q: TARGET_Q 실패 시 다음에 시도할 Q번호 (QList 순서)
 *   - generate_crosscall_dial() 함수 사용
 * Backup: /home/asterisk/WEB/J_IVR/backup_20260129/
 *
 * Date: 2026-01-20
 * Changes: Updated for PHP 8.2.30 compatibility
 *
 * Changes Made:
 * 1. Updated DB connection IP from 121.254.239.50 to 121.254.239.50
 * 2. Added error handling for all mysqli_query() calls
 * 3. Added result validation before mysqli_fetch_array() calls
 * 4. Added result validation before mysqli_num_rows() calls
 * 5. Added error handling for all prepare() statements
 * 6. Added result validation for prepared statement results
 * 7. Added proper statement cleanup with close()
 * 8. Added safe mysqli_close() with connection validation
 *
 * Backup: getQStep2.php.backup_20260120_152951
 */

include 'plog.php';
include_once 'pbx_config.php';  // 2026-01-29 v1.4.0: IP 기반 PBX 설정
include_once 'crosscall_link.php';  // 2026-02-02: CrossCall Original Linkedid 연결

	// 2026-01-29: Helper function to get pbx_id for a given Q number
	function get_pbx_id_for_q_step2($conn, $q_num) {
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

	// v1.2.0: Added optional parameters for crosscall incoming scenario
	function get_q_step2( 	$COMPANY_ID, $DID, $CID, $TYPE, $OPTION, $MYQ, $QLIST, $TRCOUNT, $TRORDER, $DIDLIST, $PBXIDLIST,
				$CC_ORIGIN_Q = '', $CC_ORIGINAL_DID = '', $IS_CROSSCALL_INCOMING = 'N', $LINKEDID = '', $CALL_ID = '' )
	{
		SLOG( sprintf( '[GET_QE_CC_D %s:%s] START =======================================================================================================', $DID, $CID ) );
		SLOG( sprintf( '[GET_Q_STEP2 %s:%s] IS_CROSSCALL_INCOMING=%s, CC_ORIGIN_Q=%s, CC_ORIGINAL_DID=%s',
			$DID, $CID, $IS_CROSSCALL_INCOMING, $CC_ORIGIN_Q, $CC_ORIGINAL_DID ) );

                // PHP 8.2 Fix: Updated DB connection IP
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
		$RESULT->my_q_find 	= 'N';

                $RESULT->tr_next_q      = '0';
                $RESULT->tr_next_did    = '0';
                $RESULT->tr_next_q_pbx_id = '0';  // 2026-01-29: Added for crosscall device detection

		// 2026-01-29 v1.1.0: Crosscall auto-detection fields
		$RESULT->my_q_pbx_id = '0';              // MYQ의 장비 ID
		$RESULT->is_crosscall_required = 'N';   // 크로스콜 필요 여부
		$RESULT->crosscall_dial_number = '';    // 크로스콜 다이얼 번호

		// 2026-01-29 v1.2.0: Return crosscall fields (for crosscall incoming scenario)
		$RESULT->is_return_crosscall = 'N';     // 반환 크로스콜 필요 여부 (하위 호환용)
		$RESULT->return_crosscall_dial = '';    // 반환 크로스콜 다이얼 번호 (하위 호환용)
		$RESULT->cc_origin_q = $CC_ORIGIN_Q;    // 원래 출발지 Q (A장비)
		$RESULT->cc_original_did = $CC_ORIGINAL_DID;  // 원본 DID

		// 2026-01-29 v1.5.0: 통일된 크로스콜 형식 필드
		$RESULT->crosscall_target_q = '';       // TARGET_Q: 대상 장비에서 시도할 Q
		$RESULT->crosscall_next_q = '';         // NEXT_Q: TARGET_Q 다음에 시도할 Q

		if( $TYPE == 'GET_CC_SMY' )
		{

			if( $OPTION == 'N' ) // 착신 안함(내콜센터로 들어옴 )
			{
				$RESULT->tr_next_q    	= $MYQ;
				$RESULT->tr_next_did   	= $DID;
				$RESULT->my_q_find	='Y';

				SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s NEXTDID:%s', $DID, $CID, $RESULT->tr_next_q, $DID ) );
			}
			else if( $OPTION == 'T' ) //시간차 착신
			{
				if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, '#########################' ) );

					$RESULT->tr_next_q    	= $MYQ;
					$RESULT->tr_next_did   	= $DID;
					$RESULT->tr_order_id  	= 1;
					$RESULT->my_q_find	='Y';

					/**
					//$sql = "select q_num from T_Q_EXTENSION where q_num='$MYQ' and call_status=0 and is_status=1 group by q_num limit 1;";
					$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 e.is_status=1 group by q_num limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
					// PHP 8.2 Fix: Added error handling for mysqli_query
					$res = mysqli_query($conn, $sql);
					if ($res === false) {
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] Query failed: %s', $DID, $CID, mysqli_error($conn) ) );
					}

					// PHP 8.2 Fix: Check result before mysqli_fetch_array
					if( $res && ($row = mysqli_fetch_array($res)) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    	= $row[0];
						$RESULT->tr_next_did   	= $DID;
						$RESULT->tr_order_id  	= 1;
						$RESULT->my_q_find	='Y';

						SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s MYDID:%s TRORDER:%s', $DID, $CID, $row[0], $DID, $RESULT->tr_order_id ) );
					}
					// PHP 8.2 Fix: Check result before mysqli_num_rows
					$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
					if( $RESULT->my_q_find == 'N' )
					{
						// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
						$effective_trorder = $TRORDER;
						if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
							for ($k = 0; $k < $TRCOUNT; $k++) {
								if ($QLIST[$k] == $CC_ORIGIN_Q) {
									$effective_trorder = $k + 2;
									if ($effective_trorder > $TRCOUNT) $effective_trorder = 1;
									SLOG( sprintf( '[GET_CC_SMYT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
										$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
									break;
								}
							}
						}

						for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$effective_trorder-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						// PHP 8.2 Fix: Added error handling for mysqli_query
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
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1] ) );
						}
						// PHP 8.2 Fix: Check result before mysqli_num_rows
						$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
					}
					**/
				}
				else 
				{	
					if( $TRORDER == $TRCOUNT )
					{
						$NEWLIST = $QLIST[$TRORDER-1];
						$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 and m.transfer_q_number='$NEWLIST' limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

                        // PHP 8.2 Fix: Added error handling for mysqli_query
                        if ($res === false) {
                            SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                        }

                        // PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q      = $row[0];
							$RESULT->tr_order_id    = $row[1];
							$RESULT->tr_next_did    = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s NEXTTORDER:%s', 
                                            $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_order_id ) );
						}
						else
						{
							$RESULT->tr_next_q      = $MYQ;
							$RESULT->tr_next_did    = $DID;
							$RESULT->tr_order_id    = 1;
							$RESULT->my_q_find      ='Y';

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s', 
                                            $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id ) );

							/**
							$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

							if( $row = mysqli_fetch_array($res) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    	= $row[0];

								SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
							}
							**/
						}
						$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
					}
					else
					{
						// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
						$effective_trorder = $TRORDER;
						if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
							for ($k = 0; $k < $TRCOUNT; $k++) {
								if ($QLIST[$k] == $CC_ORIGIN_Q) {
									$effective_trorder = $k + 2;
									if ($effective_trorder > $TRCOUNT) $effective_trorder = 1;
									SLOG( sprintf( '[GET_CC_SMYT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
										$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
									break;
								}
							}
						}

						for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$effective_trorder-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
						$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] QList filter applied', $DID, $CID ) );

						$res = mysqli_query($conn, $sql);

                        // PHP 8.2 Fix: Added error handling for mysqli_query
                        if ($res === false) 
                        {
                            SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                        }


                        // PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s NEXTTRORDER:%s', 
                                            $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_order_id ) );
						}
						else
					{
							$RESULT->tr_next_q     = $MYQ;
							$RESULT->tr_next_did 	= $DID;
							$RESULT->tr_order_id 	= 1;
							$RESULT->my_q_find	='Y';
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s', 
                                            $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id ) );
						}
						$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
					}
				}
			}
			else //직접 착신 ( GET_CC_SMY )
			{
				if( $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
				}

				// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
				$effective_trorder = $TRORDER;
				if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
					for ($k = 0; $k < $TRCOUNT; $k++) {
						if ($QLIST[$k] == $CC_ORIGIN_Q) {
							$effective_trorder = $k + 2;
							if ($effective_trorder > $TRCOUNT) $effective_trorder = 1;
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
								$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
							break;
						}
					}
				}

				for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
				}
				for( $j=0; $j<$effective_trorder-1; $j++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
				}

				$NEWLIST = rtrim($TRQLIST, ", ");
				SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

				// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
				$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s (QList filter applied)', $DID, $CID, $sql ) );
				$res = mysqli_query($conn, $sql);

                // PHP 8.2 Fix: Added error handling for mysqli_query
                if ($res === false) 
                {
                    SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                }


                // PHP 8.2 Fix: Check result before mysqli_fetch_array
				if( $res && ($row = mysqli_fetch_array($res)) )
				{
					//error_log($row[0]);

					$RESULT->tr_next_q    = $row[0];
					$RESULT->tr_order_id  = $row[1];
					$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
					$RESULT->tr_order_id++;

					SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s NEXTTRODER:%s', 
                                    $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_order_id ) );
				}
				else
				{
					$NEWLIST = $QLIST[$TRORDER-1];
	
					$RESULT->tr_next_q    = $NEWLIST;
					$RESULT->tr_order_id  = $TRORDER;
					$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
					$RESULT->tr_order_id++;
                                
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] SMY CAHNNEL BUSY NEXTQ:%s NEXTDID:%s TRORDER:%s NEXTTRODER:%s', 
                                    $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $TRORDER, $RESULT->tr_order_id ) );
				}
				$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
			}

		}
		if( $TYPE == 'GET_CC_DIR' )
		{
			if( $OPTION == 'N' ) // 착신 안함(내콜센터로 들어옴 )
			{
				$RESULT->tr_next_q    	= $MYQ;
				$RESULT->tr_next_did 	= $DID;

				SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s NEXTDID:%s', $DID, $CID, $RESULT->tr_next_did, $RESULT->tr_next_q ) );
			}
			else if( $OPTION == 'T' ) //시간차 착신
			{
				if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, '#########################' ) );

					$RESULT->tr_next_q    	= $MYQ;
					$RESULT->tr_next_did 	= $DID;
					$RESULT->tr_order_id  	= 1;
					$RESULT->my_q_find	='Y';

					/***
					//$sql = "select q_num from T_Q_EXTENSION where q_num='$MYQ' and call_status=0 group by q_num limit 1;";
					$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

					if( $row = mysqli_fetch_array($res) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    	= $row[0];
						$RESULT->tr_next_did 	= $DID;
						$RESULT->tr_order_id  	= 1;
						$RESULT->my_q_find	='Y';

						SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $RESULT->tr_order_id ) );
					}
					$count= mysqli_num_rows($res);
					if( $RESULT->my_q_find == 'N' )
					{
						// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
						$effective_trorder = $TRORDER;
						if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
							for ($k = 0; $k < $TRCOUNT; $k++) {
								if ($QLIST[$k] == $CC_ORIGIN_Q) {
									$effective_trorder = $k + 2;
									if ($effective_trorder > $TRCOUNT) $effective_trorder = 1;
									SLOG( sprintf( '[GET_CC_DIRT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
										$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
									break;
								}
							}
						}

						for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$effective_trorder-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);
      // PHP 8.2 Fix: Added error handling for mysqli_query
      if ($res === false) {
      	SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
      }


						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
						}
						$count= mysqli_num_rows($res);
					}
					***/
				}
				else 
				{	
					if( $TRORDER == $TRCOUNT )
					{
						$NEWLIST = $QLIST[$TRORDER-1];

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 and m.transfer_q_number='$NEWLIST' limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);
                       
                        // PHP 8.2 Fix: Added error handling for mysqli_query
                        if ($res === false) 
                        {
                            SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                        }

                        // PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s TORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_order_id ) );
						}
						else
						{
							$RESULT->tr_next_q     = $MYQ;
							$RESULT->tr_next_did 	= $DID;
							$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";
							$RESULT->tr_order_id 	= 1;

							error_log($sql);
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

                            // PHP 8.2 Fix: Added error handling for mysqli_query
                            if ($res === false) 
                            {
                                SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                            }


                            // PHP 8.2 Fix: Check result before mysqli_fetch_array
							if( $res && ($row = mysqli_fetch_array($res)) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    	= $row[0];
								$RESULT->tr_next_did   	= $DID;

								SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $RESULT->tr_order_id ) );
							}
						}
						$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
					}
					else
					{
						// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
						$effective_trorder = $TRORDER;
						if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
							for ($k = 0; $k < $TRCOUNT; $k++) {
								if ($QLIST[$k] == $CC_ORIGIN_Q) {
									$effective_trorder = $k + 2;
									if ($effective_trorder > $TRCOUNT) {
										$effective_trorder = 1;
									}
									SLOG( sprintf( '[GET_CC_DIRT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
										$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
									break;
								}
							}
						}

						for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$effective_trorder-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
						$sql = "select m.transfer_q_number, m.transfer_order_num from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] QList filter applied', $DID, $CID ) );

						$res = mysqli_query($conn, $sql);
      // PHP 8.2 Fix: Added error handling for mysqli_query
      if ($res === false) {
      	SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
      }


      // PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1] ) );
						}
						$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
					}
				}
			}
			else //직접 착신( GET_CC_DIR )
			{
				if( $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
				}

				// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
				// IS_CROSSCALL_INCOMING=Y인 경우, CC_ORIGIN_Q 이후의 Q부터 시작
				$effective_trorder = $TRORDER;
				if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
					// CC_ORIGIN_Q의 위치를 찾아서 그 다음부터 시작
					for ($k = 0; $k < $TRCOUNT; $k++) {
						if ($QLIST[$k] == $CC_ORIGIN_Q) {
							// CC_ORIGIN_Q 다음 Q부터 시작 (k+2는 1-based index)
							$effective_trorder = $k + 2;
							if ($effective_trorder > $TRCOUNT) {
								$effective_trorder = 1;  // 순환
							}
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
								$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
							break;
						}
					}
				}

				for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
				}
				for( $j=0; $j<$effective_trorder-1; $j++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
				}

				$NEWLIST = rtrim($TRQLIST, ", ");
				SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

				// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
				$sql = "select m.transfer_q_number, m.transfer_order_num from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s (QList filter applied)', $DID, $CID, $sql ) );
				$res = mysqli_query($conn, $sql);
    // PHP 8.2 Fix: Added error handling for mysqli_query
    if ($res === false) {
    	SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
    }


    // PHP 8.2 Fix: Check result before mysqli_fetch_array
				if( $res && ($row = mysqli_fetch_array($res)) )
				{
					//error_log($row[0]);

					$RESULT->tr_next_q    = $row[0];
					$RESULT->tr_order_id  = $row[1];
					$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
					$RESULT->tr_order_id++;

					SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1] ) );
				}
				else
				{
					$NEWLIST = $QLIST[$TRORDER-1];
	
					$RESULT->tr_next_q    = $NEWLIST;
					$RESULT->tr_order_id  = $TRORDER;
					$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
					$RESULT->tr_order_id++;
                                
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] DIR CAHNNEL BUSY NEXTQ:%s NEXTDID:%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id ) );
				}
				$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
			}
		}
		else if( $TYPE == 'GET_CC_SEQ' )
		{
			if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
			{
				$TRORDER = 1;
				SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );

                $sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
				$res = mysqli_query($conn, $sql);

                // PHP 8.2 Fix: Added error handling for mysqli_query
                if ($res === false) 
                {
                    SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                }

                // PHP 8.2 Fix: Check result before mysqli_fetch_array
				if( $res && ($row = mysqli_fetch_array($res)) )
				{
					//error_log($row[0]);

					$RESULT->tr_next_q      = $row[0];
					$RESULT->tr_next_did  	= $DID;
					$RESULT->tr_order_id    = 1;
					$RESULT->my_q_find      ='Y';

					SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $RESULT->tr_order_id ) );
				}
				$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows

				if( $RESULT->my_q_find == 'N' )
				{
					//SLOG( sprintf( '[GET_CC_SEQT %s:%s] %ss', $DID, $CID, '################################' ) );

					// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
					// IS_CROSSCALL_INCOMING=Y인 경우, CC_ORIGIN_Q 이후의 Q부터 시작
					$effective_trorder = $TRORDER;
					if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
						// CC_ORIGIN_Q의 위치를 찾아서 그 다음부터 시작
						for ($k = 0; $k < $TRCOUNT; $k++) {
							if ($QLIST[$k] == $CC_ORIGIN_Q) {
								// CC_ORIGIN_Q 다음 Q부터 시작 (k+2는 1-based index)
								$effective_trorder = $k + 2;
								if ($effective_trorder > $TRCOUNT) {
									$effective_trorder = 1;  // 순환
								}
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
									$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
								break;
							}
						}
					}

					for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
					}
					for( $j=0; $j<$effective_trorder-1; $j++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
					}
					$NEWLIST = rtrim($TRQLIST, ", ");
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

					// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
					$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);
                    
                    // PHP 8.2 Fix: Added error handling for mysqli_query
                    if ($res === false) 
                    {
                        SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                    }

                    // PHP 8.2 Fix: Check result before mysqli_fetch_array
					if( $res && ($row = mysqli_fetch_array($res)) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    = $row[0];
						$RESULT->tr_order_id  = $row[1];
						$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
						$RESULT->tr_order_id++;

						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1] ) );
					}
					else
					{
						$RESULT->tr_next_q    = $MYQ;
						$RESULT->tr_next_did 	= $DID;
						$RESULT->tr_order_id 	= 1;
						SLOG( sprintf( '[GET_CC_SEQT %s:%s] ALL CAHNNEL BUSY -> NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id ) );
					}
					$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
				}

			}
			else 
			{	
				if( $TRORDER == $TRCOUNT )
				{
					$NEWLIST = $QLIST[$TRORDER-1];
					$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 and m.transfer_q_number='$NEWLIST' limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);
                   
                     // PHP 8.2 Fix: Added error handling for mysqli_query
                    if ($res === false) 
                    {
                        SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                    }

                    // PHP 8.2 Fix: Check result before mysqli_fetch_array
					if( $res && ($row = mysqli_fetch_array($res)) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    = $row[0];
						$RESULT->tr_order_id  = $row[1];
						$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
						$RESULT->tr_order_id++;

						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s TORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_order_id ) );
					}
					else
					{
						$SEQ_RE_CHECK = 'N';

						$RESULT->tr_next_q     = $MYQ;
						$RESULT->tr_next_did 	= $DID;
						$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";
						$RESULT->tr_order_id 	= 1;

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

                        // PHP 8.2 Fix: Added error handling for mysqli_query
                        if ($res === false) 
                        {
                            SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                        }

                        // PHP 8.2 Fix: Check result before mysqli_fetch_array
						if( $res && ($row = mysqli_fetch_array($res)) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    	= $row[0];

							SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
						}
						else
						{
							$SEQ_RE_CHECK = 'Y';
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] SEQUENCD RE CHECK:%s', $DID, $CID, $RESULT->tr_seq_check ) );
						}
						if( $SEQ_RE_CHECK == 'Y' )
						{
							for( $i=0; $i<$TRCOUNT; $i++ )
							{
								$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
							}
							$NEWLIST = rtrim($TRQLIST, ", ");
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] RE CHECK TRQLIST:%s', $DID, $CID, $NEWLIST ) );

							// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
                            $sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

                            // PHP 8.2 Fix: Added error handling for mysqli_query
                            if ($res === false) 
                            {
                                SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                            }

                            // PHP 8.2 Fix: Check result before mysqli_fetch_array
							if( $res && ($row = mysqli_fetch_array($res)) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    = $row[0];
								$RESULT->tr_order_id  = $row[1];
								$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
								$RESULT->tr_order_id++;

								SLOG( sprintf( '[GET_CC_SEQT %s:%s] RE CHECK NEXTQ:%s NEXTDID:%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1] ) );
							}
							else
							{
								$RESULT->tr_next_q    = $MYQ;
								$RESULT->tr_next_did 	= $DID;
								$RESULT->tr_order_id 	= 1;
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] RE CHECK -> ALL CAHNNEL BUSY -> NEXTQ(MYQ):%s NEXTDID:%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id ) );
							}
						}
					}
					$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
				}
				else
				{
					// v1.7.0: 크로스콜 리턴 시 이미 시도한 Q 건너뛰기
					$effective_trorder = $TRORDER;
					if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
						for ($k = 0; $k < $TRCOUNT; $k++) {
							if ($QLIST[$k] == $CC_ORIGIN_Q) {
								$effective_trorder = $k + 2;
								if ($effective_trorder > $TRCOUNT) $effective_trorder = 1;
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] CROSSCALL RETURN: Adjusting TRORDER from %s to %s (skip CC_ORIGIN_Q=%s)',
									$DID, $CID, $TRORDER, $effective_trorder, $CC_ORIGIN_Q ) );
								break;
							}
						}
					}

					for( $i=$effective_trorder-1; $i<$TRCOUNT; $i++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
					}
					for( $j=0; $j<$effective_trorder-1; $j++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
					}
					$NEWLIST = rtrim($TRQLIST, ", ");
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

					// v1.6.0: QList에 있는 Q만 조회하도록 WHERE 조건 추가
					$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and m.transfer_q_number IN ($NEWLIST) and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

                    // PHP 8.2 Fix: Added error handling for mysqli_query
                    if ($res === false) {
                        SLOG( sprintf( '[Query Error %s:%s] %s', $DID, $CID, mysqli_error($conn) ) );
                    }

                    // PHP 8.2 Fix: Check result before mysqli_fetch_array
					if( $res && ($row = mysqli_fetch_array($res)) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    = $row[0];
						$RESULT->tr_order_id  = $row[1];
						$RESULT->tr_next_did  = $DIDLIST[$RESULT->tr_order_id-1];
						$RESULT->tr_order_id++;

						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ:%s NEXTDID:%s TRORDER:%s NEXTTORDER:%s', 
                                        $DID, $CID, $row[0], $RESULT->tr_next_did, $row[1], $RESULT->tr_order_id ) );
					}
					else
					{
						$RESULT->tr_next_q     = $MYQ;
						$RESULT->tr_next_did 	= $DID;
						$RESULT->tr_order_id 	= 1;
						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(TRQ):%s NEXTDID:%s TRORDER:%s', 
                                        $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_next_did, $RESULT->tr_order_id ) );
					}
					$count= ($res !== false) ? mysqli_num_rows($res) : 0; // PHP 8.2 Fix: Check result before mysqli_num_rows
				}
			}
		}

		//error_log($RESULT );

		// 2026-01-29 v1.3.0: Handle fallback-to-MYQ case in crosscall incoming scenario
		// 크로스콜 수신 상태에서 모든 Q가 무응답이어서 MYQ로 fallback된 경우,
		// MYQ(현재 B장비)가 아닌 CC_ORIGIN_Q(원래 A장비)로 복귀해야 함
		if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q) && $RESULT->tr_next_q == $MYQ) {
			// Fallback to MYQ detected in crosscall incoming scenario
			// Replace MYQ with CC_ORIGIN_Q to trigger return crosscall
			SLOG( sprintf( '[GET_Q_STEP2 %s:%s] FALLBACK DETECTED! Replacing MYQ(%s) with CC_ORIGIN_Q(%s) for return crosscall',
				$DID, $CID, $MYQ, $CC_ORIGIN_Q ) );

			$RESULT->tr_next_q = $CC_ORIGIN_Q;
			$RESULT->tr_next_did = !empty($CC_ORIGINAL_DID) ? $CC_ORIGINAL_DID : $DID;
			$RESULT->tr_order_id = 1;
			$RESULT->my_q_find = 'N';  // Not found on current device, needs return crosscall
		}

		// 2026-01-29 v1.4.0: IP 기반 크로스콜 자동 감지
		// 자기 서버 IP로 자기 pbx_id 조회 (한번만, 캐시됨)
		$my_pbx_config = get_my_pbx_config($conn);
		$RESULT->my_q_pbx_id = $my_pbx_config->pbx_id;
		$RESULT->my_server_ip = $my_pbx_config->server_ip;

		// TR_NEXT_Q의 pbx_id 조회
		$RESULT->tr_next_q_pbx_id = get_pbx_id_for_q_step2($conn, $RESULT->tr_next_q);

		SLOG( sprintf( '[GET_Q_STEP2 %s:%s] MY_SERVER_IP=%s, MY_PBX_ID=%s',
			$DID, $CID, $my_pbx_config->server_ip, $my_pbx_config->pbx_id ) );

		// v1.5.0: QList에서 TARGET_Q 다음에 올 NEXT_Q 찾기 헬퍼 함수
		$find_next_q_in_list = function($target_q, $qlist, $trcount) {
			if (empty($qlist) || $trcount <= 0) {
				return '0';
			}
			// QList에서 target_q의 위치 찾기
			$target_idx = -1;
			for ($i = 0; $i < $trcount; $i++) {
				if ($qlist[$i] == $target_q) {
					$target_idx = $i;
					break;
				}
			}
			// 다음 Q 반환 (순환)
			if ($target_idx >= 0) {
				$next_idx = ($target_idx + 1) % $trcount;
				return $qlist[$next_idx];
			}
			// 못 찾으면 첫번째 Q 반환
			return $qlist[0];
		};

		// 자기 pbx_id와 다음 Q의 pbx_id 비교
		// v1.5.1: MYQ 비교 조건 제거 - MYQ가 다른 장비에 있을 수 있음
		if ($RESULT->my_q_pbx_id != '0' && $RESULT->tr_next_q_pbx_id != '0' &&
		    $RESULT->my_q_pbx_id != $RESULT->tr_next_q_pbx_id &&
		    $RESULT->tr_next_q != '0') {

			// v1.5.0: 통일된 크로스콜 형식 - 모든 크로스콜에 동일한 형식 사용
			// 형식: crosscall_prefix + DID + TARGET_Q + NEXT_Q
			// TARGET_Q: 대상 장비에서 시도할 Q
			// NEXT_Q: TARGET_Q 실패 시 다음에 시도할 Q (QList 순서)

			// TARGET_Q = tr_next_q (다음에 시도할 Q)
			$target_q = $RESULT->tr_next_q;

			// NEXT_Q = QList에서 TARGET_Q 다음 순서의 Q
			$next_q = $find_next_q_in_list($target_q, $QLIST, $TRCOUNT);

			// 크로스콜 DID 결정
			$crosscall_did = ($RESULT->tr_next_did != '0') ? $RESULT->tr_next_did : $DID;
			// 크로스콜 수신 상태에서는 원본 DID 사용
			if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGINAL_DID)) {
				$crosscall_did = $CC_ORIGINAL_DID;
			}

			// generate_crosscall_dial() 함수로 통일된 형식 생성
			$crosscall_dial = generate_crosscall_dial($conn, $crosscall_did, $target_q, $next_q, $RESULT->tr_next_q_pbx_id);

			// v1.5.0: 공통 필드 설정
			$RESULT->crosscall_target_q = $target_q;
			$RESULT->crosscall_next_q = $next_q;

			// 2026-01-29 v1.2.0: Check if this is a crosscall incoming scenario
			if ($IS_CROSSCALL_INCOMING == 'Y' && !empty($CC_ORIGIN_Q)) {
				// 크로스콜 수신 상태에서 다음 Q가 다른 장비 -> 크로스콜!
				// v1.5.0: 반환도 동일한 형식 사용 (is_return_crosscall 대신 is_crosscall_required='Y')
				$RESULT->is_return_crosscall = 'Y';  // 하위 호환용
				$RESULT->is_crosscall_required = 'Y';  // 크로스콜 필요

				$RESULT->crosscall_dial_number = $crosscall_dial;
				$RESULT->return_crosscall_dial = $crosscall_dial;  // 하위 호환용 (동일값)

				$target_prefix_info = get_target_pbx_prefix($conn, $RESULT->tr_next_q_pbx_id);
				$RESULT->crosscall_prefix = $target_prefix_info->crosscall_prefix ?? '99';
				$RESULT->return_prefix = $RESULT->crosscall_prefix;  // 하위 호환용

				SLOG( sprintf( '[GET_Q_STEP2 %s:%s] CROSSCALL (from incoming) REQUIRED! my_pbx=%s, target_pbx=%s, target_q=%s, next_q=%s, prefix=%s, dial=%s',
					$DID, $CID, $RESULT->my_q_pbx_id, $RESULT->tr_next_q_pbx_id, $target_q, $next_q, $RESULT->crosscall_prefix, $crosscall_dial ) );

				// 2026-02-02: CrossCall 발신 시 Original Linkedid 저장 (key = crosscall_did + CID)
				if (!empty($LINKEDID)) {
					save_crosscall_link($conn, $crosscall_did, $CID, $LINKEDID, $CALL_ID, $COMPANY_ID);
				}
			} else {
				// 일반 크로스콜 (A장비 -> B장비로 새로운 크로스콜)
				$RESULT->is_crosscall_required = 'Y';
				$RESULT->is_return_crosscall = 'N';

				$RESULT->crosscall_dial_number = $crosscall_dial;
				$RESULT->return_crosscall_dial = '';

				$target_prefix_info = get_target_pbx_prefix($conn, $RESULT->tr_next_q_pbx_id);
				$RESULT->crosscall_prefix = $target_prefix_info->crosscall_prefix ?? '99';
				$RESULT->return_prefix = '';

				SLOG( sprintf( '[GET_Q_STEP2 %s:%s] CROSSCALL REQUIRED! my_pbx=%s, target_pbx=%s, target_q=%s, next_q=%s, prefix=%s, dial=%s',
					$DID, $CID, $RESULT->my_q_pbx_id, $RESULT->tr_next_q_pbx_id, $target_q, $next_q, $RESULT->crosscall_prefix, $crosscall_dial ) );

				// 2026-02-02: CrossCall 발신 시 Original Linkedid 저장 (key = crosscall_did + CID)
				if (!empty($LINKEDID)) {
					save_crosscall_link($conn, $crosscall_did, $CID, $LINKEDID, $CALL_ID, $COMPANY_ID);
				}
			}
		} else {
			// 같은 장비 -> 일반 전송
			$RESULT->is_crosscall_required = 'N';
			$RESULT->crosscall_dial_number = '';
			$RESULT->crosscall_prefix = '';
			$RESULT->is_return_crosscall = 'N';
			$RESULT->return_crosscall_dial = '';
			$RESULT->return_prefix = '';

			SLOG( sprintf( '[GET_Q_STEP2 %s:%s] SAME DEVICE, no crosscall. my_pbx=%s, next_pbx=%s',
				$DID, $CID, $RESULT->my_q_pbx_id, $RESULT->tr_next_q_pbx_id ) );
		}

		// PHP 8.2 Fix: Safe mysqli_close with connection validation
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
				if ($JSON_REQUEST->REQ == 'GET_Q_STEP2')
				{
					$JSON_API_RESULT->JSON_RESULT->CODE	= "TRY_CALL_PROCEDURE";

					// v1.2.0: Get optional crosscall incoming parameters
					$CC_ORIGIN_Q = $JSON_REQUEST->CC_ORIGIN_Q ?? '';
					$CC_ORIGINAL_DID = $JSON_REQUEST->CC_ORIGINAL_DID ?? '';
					$IS_CROSSCALL_INCOMING = $JSON_REQUEST->IS_CROSSCALL_INCOMING ?? 'N';

					$JSON_API_RESULT->JSON_RESULT->MESSAGE	= get_q_step2( 	$JSON_REQUEST->COMPANY_ID,
												$JSON_REQUEST->DID ,
												$JSON_REQUEST->CID ,
												$JSON_REQUEST->TYPE,
												$JSON_REQUEST->OPTION,
												$JSON_REQUEST->MYQ,
												$JSON_REQUEST->QLIST,
												$JSON_REQUEST->TRCOUNT,
												$JSON_REQUEST->TRORDER,
												$JSON_REQUEST->DIDLIST,
												$JSON_REQUEST->PBXIDLIST,
												$CC_ORIGIN_Q,
												$CC_ORIGINAL_DID,
												$IS_CROSSCALL_INCOMING,
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
