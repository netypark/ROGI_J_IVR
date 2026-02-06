<?php

include 'plog.php';

	function get_company( $DID, $CID, $START_TIME, $CALL_ID ) 
	{ 
		SLOG( sprintf( '[GET_COMPANY %s:%s] START ===================================================================================================================', $DID, $CID ) );

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
	
                $RESULT = new stdClass();

		$RESULT->get_company		= "fail";
                        
		$RESULT->company_id		= 0;
		$RESULT->company_didnumber	= '';
		$RESULT->company_level		= 2;
		$RESULT->parent_id		= 0;
		$RESULT->is_active		= 'Y';
		$RESULT->master_id		= 0;
		$RESULT->use_dnd		= 'N';
		$RESULT->use_did_route		= 'N';
		$RESULT->use_cid_route		= 'N';
		$RESULT->use_db_route		= 'N';
		$RESULT->use_ars		= 'N';
		$RESULT->use_crosscall		= 'N';
		$RESULT->action			= '';
		$RESULT->to_number		= '';
		$RESULT->wait_time		= 50;
		$RESULT->dbr_to_extension 	= '';
		$RESULT->vac_ment_dir		= 'vac';
		$RESULT->info_ment_dir		= 'inf';
		$RESULT->noinput_ment_dir	= 'nip';
		$RESULT->wronginput_ment_dir	= 'wip';
		$RESULT->error_ment_dir		= 'err';
		$RESULT->allocQ			= '';
		$RESULT->holiday		= 'N';
		$RESULT->holiday_ment		= 'holi';
		$RESULT->c_level  	    	= '';

		$RESULT->is_re_call_use		= 'N';
		$RESULT->re_call_time		= 0;
		$RESULT->re_call_time_type	= 'S';
		$RESULT->re_call_center_1	= 'F';
		$RESULT->re_call_center_2	= 'M';
		$RESULT->re_call_use_alba_q	= 'N';
		$RESULT->re_call_alba_q_time	= 10;
		$RESULT->re_call_alba_q		= '';

		$RESULT->tr_count   		= 0;
		$RESULT->tr_option 	  	= 'N';
		$RESULT->tr_time_first   	= 10;
		$RESULT->tr_time_second	 	= 10;
		$RESULT->tr_company_list	= '';
		$RESULT->tr_did_list		= '';
		$RESULT->tr_q_list      	= '';
		$RESULT->tr_pbx_id_list     = '';

		$RESULT->re_call_q 		= '';
		$RESULT->re_call_ext 		= '';
                $RESULT->tr_next_q      	= '';

                $RESULT->emergency      	= 'N';

		if (!$conn) 
		{
			$filename = "/home/asterisk/emergency_info.dat";
			$file = fopen($filename, "r");  // 파일을 읽기 모드("r")로 엽니다.

			if ($file) 
			{
				// 파일 크기만큼 읽기
				//$content = fread($file, filesize($filename));

				while (($line = fgets($file)) !== false) 
				{
					$company_id = '';
					$master_id = '';
					$did_num = '';
					$emg_num = '';

					sscanf($line, "%s %s %s %s", $company_id, $master_id, $did_num, $emg_num );

					// 추출한 데이터를 출력합니다.
					//echo "Name: $name, Age: $age\n";
					SLOG( sprintf( '[GET_COMPANY %s:%s] %s %s %s %s', $DID, $CID, $company_id, $master_id, $did_num, $emg_num ) ); 

					if( $DID == $did_num )
					{
						$RESULT->emergency = 'Y';
						$RESULT->tr_next_q = $emg_num;
					}
				}
			    
				// 파일을 닫습니다.
				fclose($file);
			}
			else 
			{
				SLOG( sprintf( '[GET_COMPANY %s:%s] %s ', $DID, $CID, 'file open failed' ) ); 
			}
		}
		else
		{

			$sql = "select company_id, did_company_id, did_number, use_dnd, use_transfer, use_cid_route, use_db_route, is_use, COALESCE(NULLIF(to_number, ''), '0') AS result from T_DID_RANGE where did_number=? limit 1;";

			$USE_TRANSFER='N';
			$IS_USE='N';
			$DR_TO_EXTEN      = "0";
			error_log($sql);
			// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
			$sql_log = str_replace('?', "'".$DID."'", $sql);
			SLOG( sprintf( '[GET_COMPANY %s:%s] %s', $DID, $CID, $sql_log ) );

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("s", $DID);
			$stmt->execute();
			$res = $stmt->get_result();

			if( $row = $res->fetch_array(MYSQLI_NUM) )
			{       
				$RESULT->use_dnd		= $row[3];
				$RESULT->use_cid_route		= $row[5];
				$RESULT->use_db_route		= $row[6];

				$USE_TRANSFER			= $row[4];
				$IS_USE				= $row[7];

				$RESULT->to_number		= $row[8];
			}
			$count= $res->num_rows ;
			SLOG( sprintf( '[GET_COMPANY %s:%s] get DID_RANGE USE_DND:%s USE_TRANSFER:%s USE_CID_ROUTE:%s USE_DB_ROUTE:%s IS_USE:%s TO_NUMBER:[%s]', 
						$DID, $CID, $RESULT->use_dnd, $row[4], $RESULT->use_cid_route, $RESULT->use_db_route, $row[7],  $RESULT->to_number ) );

			if( $RESULT->use_dnd == 'Y' )
			{
				$RESULT->use_dnd		= 'Y';
			}
			else if( $USE_TRANSFER == 'Y' )
			{
				//if( $RESULT->to_number != '0' && $RESULT->to_number !='' ) $RESULT->use_did_route	= 'Y';
				$RESULT->use_did_route	= 'Y';
				//SLOG( sprintf( '[GET_COMPANY %s:%s] @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@ ', $DID, $CID ) ); 
			}
			else if( $IS_USE == 'N' && ( $RESULT->use_db_route == 'Y' || $RESULT->use_cid_route == 'Y' ) )
			{
				$SIDO	='';
				if( $RESULT->use_cid_route == 'Y' )
				{

					if(substr($CID, 0, 2) == '02' || substr($CID, 0, 2) == '03' || substr($CID, 0, 2) == '04' || substr($CID, 0, 2) == '05' || substr($CID, 0, 2) == '06' )
					{
						if( substr($CID, 0, 2) == '02' )
						{
							$SIDO = substr($CID, 0, 2);
							$SIDO = '0'.$SIDO;
						}	
						else 
						{
							$SIDO = substr($CID, 0, 3);
						}	
					}
				}

				if( $SIDO == '' )
				{
					//SLOG( sprintf( '[GET_CID_ROT %s:%s] NOT FOUND LOCATION CID(02~06X) %s', $DID, $CID, $CID ) );
					if( $RESULT->use_db_route == 'Y' )
					{
						//$sql = "select d.to_extension from T_LOCATION_CID AS l INNER JOIN T_DB_ROUTING AS d on l.si_do = d.si_do where d.from_did='$DID' and l.cid='$CID' and d.kind='C' order by create_datetime limit 1;";
						$sql = "SELECT d.to_extension, d.si_gun_gu FROM T_LOCATION_CID AS l INNER JOIN T_DB_ROUTING AS d ON l.si_do = d.si_do AND IF(l.si_gun_gu IS NULL, 0, l.si_gun_gu) = d.si_gun_gu WHERE d.from_did='$DID' AND l.cid='$CID' AND d.kind='D'  ORDER BY d.create_datetime LIMIT 1;";
					}
				}
				else
				{
					$sql = "select to_extension from T_DB_ROUTING where from_did='$DID' and si_do='$SIDO' and kind='C' order by create_datetime limit 1;";
				}

				error_log($sql);
				SLOG( sprintf( '[GET_DB_ROUT %s:%s] %s', $DID, $CID, $sql ) );

				$res = mysqli_query($conn, $sql);

				SLOG( sprintf( '[GET_DB_ROUT %s:%s] get T_CC_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
				if( $row = mysqli_fetch_array($res) )
				{
					$DR_TO_EXTEN       = $row[0];
				}

				$count= mysqli_num_rows($res) ;

				$RESULT->dbr_to_extension = $DR_TO_EXTEN;
			}

			if( $DR_TO_EXTEN == '0' )
			{
				$sql = "select company_id,did_number, parent_id, is_active, master_id, use_did_route, use_db_route, use_ars, use_crosscall, action, to_number, wait_time, vac_ment_dir, info_ment_dir, noinput_ment_dir, wronginput_ment_dir, error_ment_dir, use_queue, is_re_call_use, re_call_time, re_call_time_type, re_call_use_alba_q, re_call_center_1, re_call_center_2, re_call_alba_q_time, re_call_alba_q, use_cid_route, company_level from T_COMPANY where did_number=? and company_level=2 limit 1;";

				error_log($sql);
				// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
				$sql_log = str_replace('?', "'".$DID."'", $sql);
				SLOG( sprintf( '[GET_COMPANY %s:%s] %s', $DID, $CID, $sql_log ) );

				$stmt = $conn->prepare($sql);
				$stmt->bind_param("s", $DID);
				$stmt->execute();
				$res = $stmt->get_result();

				SLOG( sprintf( '[GET_COMPANY %s:%s] get T_COMPANY ==================================================================================================', $DID, $CID ) );
				if( $row = $res->fetch_array(MYSQLI_NUM) )
				{
	       
					SLOG( sprintf( '[GET_COMPANY %s:%s] ID    :%13.13s | DIDNUM:%13.13s | P_ID  :%13.13s | IS_ACT:%13.13s | MA_ID :%13.13s |',
								$DID, $CID, $row[0], $row[1], $row[2], $row[3], $row[4] ) );

					SLOG( sprintf( '[GET_COMPANY %s:%s] UDIDRT:%13.13s | UCIDRT:%13.13s | UDBRT :%13.13s | USEARS:%13.13s | UCROS :%13.13s |',
								$DID, $CID, $RESULT->use_did_route, $RESULT->use_cid_route, $RESULT->use_db_route, $row[7], $row[8] ) );

					SLOG( sprintf( '[GET_COMPANY %s:%s] TONUM :%13.13s | WAITTM:%13.13s | VACDIR:%13.13s | INFMT :%13.13s | NOPDIR:%13.13s |',
								$DID, $CID, $row[10], $row[11], $row[12], $row[13], $row[14] ) );

					SLOG( sprintf( '[GET_COMPANY %s:%s] WRPDIR:%13.13s | ERRDIR:%13.13s | Q_GRP :%13.13s | RECUSE:%13.13s | RCALTM:%13.13s |',
								$DID, $CID, $row[15], $row[16], $row[17], $row[18], $row[19] ) );

					SLOG( sprintf( '[GET_COMPANY %s:%s] RCATMT:%13.13s | RCAUAL:%13.13s | RCCEN1:%13.13s | RCCEN2:%13.13s | RCAQTM:%13.13s |',
								$DID, $CID, $row[20], $row[21], $row[22], $row[23], $row[24] ) );

					SLOG( sprintf( '[GET_COMPANY %s:%s] RCALBQ:%13.13s | ACTION:%13.13s | CLEVEL:%13.13s |',
								$DID, $CID, $row[25], $row[9], $row[27] ) );


					$RESULT->get_company		= "success";
					
					$RESULT->company_id		= $row[0];
					$RESULT->company_didnumber	= $row[1];
					$RESULT->parent_id		= $row[2];
					$RESULT->is_active		= $row[3];
					$RESULT->master_id		= $row[4];
					//$RESULT->use_did_route		= $row[5];
					//$RESULT->use_db_route		= $row[6];
					$RESULT->use_ars		= $row[7];
					$RESULT->use_crosscall		= $row[8];
					$RESULT->action			= $row[9];
					$RESULT->to_number		= $row[10];
					$RESULT->wait_time		= $row[11];
					$RESULT->vac_ment_dir		= $row[12];
					$RESULT->info_ment_dir		= $row[13];
					$RESULT->noinput_ment_dir	= $row[14];
					$RESULT->wronginput_ment_dir	= $row[15];
					$RESULT->error_ment_dir		= $row[16];
					$RESULT->allocQ			= $row[17];
					$RESULT->is_re_call_use		= $row[18];
					$RESULT->re_call_time		= $row[19];
					$RESULT->re_call_time_type	= $row[20];
					$RESULT->re_call_use_alba_q	= $row[21];
					$RESULT->re_call_center_1	= $row[22];
					$RESULT->re_call_center_2	= $row[23];
					$RESULT->re_call_alba_q_time	= $row[24];
					$RESULT->re_call_alba_q		= $row[25];
					//$RESULT->use_cid_route		= $row[26];
					$RESULT->company_level		= $row[27];

					$RESULT->tr_next_q      	= $row[17];
				}

				$count= $res->num_rows ;

				SLOG( sprintf( '[GET_COMPANY %s:%s] ================================================================================================================', $DID, $CID ) );

				if( $RESULT->get_company == "success" )
				{

	/***
					if( $RESULT->re_call_use_alba_q == 'Y' )
					{
						$GAP = '-'.$RESULT->re_call_alba_q_time.' minutes';     

						$thirty_minutes_ago = strtotime($GAP);  

						$YYYYMM=date('Ym', $thirty_minutes_ago);
						$TABLE='T_CALL_HISTORY_'.$YYYYMM;
						
						//$sql = "select h.q_group_num, h.userphone  from $TABLE as h inner join T_Q_EXTENSION as q on h.q_group_num = q.q_num inner join T_EXTENSION as e on q.ext_number = e.ext_number where ( h.master_id=$RESULT->master_id or h.company_id=$RESULT->company_id) h.answer_time != '0' and ( h.caller='$CID' or h.called='$CID' ) and h.end_time BETWEEN DATE_SUB(NOW(), INTERVAL $RECALL_TIME MINUTE ) AND NOW() order by h.end_time desc limit 1;";

						$sql = "select h.q_group_num, h.userphone from $TABLE as h where ( master_id=$RESULT->master_id or company_id=$RESULT->company_id) and h.answer_time != '0' and h.answer_time != '' and ( h.caller='$CID' or h.called='$CID' ) and h.end_time BETWEEN DATE_SUB(NOW(), INTERVAL $RESULT->re_call_alba_q_time MINUTE ) AND NOW() order by h.end_time desc limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );

						$res = mysqli_query($conn, $sql);
						
						if( $row = mysqli_fetch_array($res) )
						{
							$RESULT->re_call_q 	= $row[0];
							$RESULT->re_call_ext 	= $row[1];

							SLOG( sprintf( '[GET_ALBA_RC %s:%s] %s %s', $DID, $CID, $row[0], $row[1] ) );

							$RESULT->re_call        = 'Y';
							$RESULT->re_call_q_find = 'Y';

							SLOG( sprintf( '[GET_RE_CALL %s:%s] RE Q:%s RE EXT:%s', $DID, $CID, $row[0], $row[1] ) );
						}
						$count= mysqli_num_rows($res);

						if ( $RESULT->re_call_q_find == 'Y' )
						{
							error_log($sql);
							SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );
							$RESULT->tr_next_q = $RESULT->allocQ;
						}
						else
						{
							$FIND_TRANQ='N';
							SLOG( sprintf( '[GET_RE_CALL %s:%s] NOT FOUND RECALL', $DID, $CID ) );
							$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$RESULT->re_call_alba_q' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

							error_log($sql);
							SLOG( sprintf( '[RECALL_TRNQ %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

							if( $row = mysqli_fetch_array($res) )
							{
								$RESULT->tr_next_q    	= $row[0];
								$FIND_TRANQ='Y';

								SLOG( sprintf( '[RECALL_TRNQ %s:%s] NEXTQ(TRANQ):%s ', $DID, $CID, $row[0] ) );
							}
							$count= mysqli_num_rows($res);
							if( $FIND_TRANQ == 'N' )
							{
								$RESULT->tr_next_q    	= $RESULT->allocQ;

								SLOG( sprintf( '[RECALL_SMYQ %s:%s] NEXTQ(MYQ):%s', $DID, $CID, $RESULT->tr_next_q ) );
							}
						}
					}
	***/
				
					//$sql = "select c_level, start_datetime, end_datetime from T_CUSTOMER where company_id=$RESULT->company_id and c_phone='$CID' and NOW() between date_format(start_datetime, '%Y-%m-%d %H:%i:%s') AND date_format(end_datetime, '%Y-%m-%d %H:%i:%s') limit 1;";
					$sql = "select c_level, start_datetime, end_datetime from T_CUSTOMER where ( company_id=? or master_id=?) and c_phone=? and NOW() between date_format(start_datetime, '%Y-%m-%d %H:%i:%s') AND date_format(end_datetime, '%Y-%m-%d %H:%i:%s') limit 1;";
					$sql = "select c_level, start_datetime, end_datetime from T_CUSTOMER where ( ( c_level = 'V' AND company_id = ? AND c_phone=? ) or ( c_level = 'B' AND master_id = ? AND c_phone=? ) ) and NOW() between date_format(start_datetime, '%Y-%m-%d %H:%i:%s') AND date_format(end_datetime, '%Y-%m-%d %H:%i:%s') limit 1;";

					error_log($sql);
					// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
					$sql_log = preg_replace('/\?/', $RESULT->company_id, $sql, 1);
					$sql_log = preg_replace('/\?/', "'".$CID."'", $sql_log, 1);
					$sql_log = preg_replace('/\?/', $RESULT->master_id, $sql_log, 1);
					$sql_log = preg_replace('/\?/', "'".$CID."'", $sql_log, 1);
					SLOG( sprintf( '[GET_CUSTOMR %s:%s] %s', $DID, $CID, $sql_log ) );

					$DR_TO_EXTEN_S      = "0";

					$RESULT->get_customer		= "fail";
					// PHP 8.2 Fix: Added error handling for prepared statement and corrected bind_param
					$stmt = $conn->prepare($sql);
					if ($stmt === false) {
						SLOG( sprintf( '[GET_CUSTOMR %s:%s] prepare() failed: %s', $DID, $CID, $conn->error ) );
					} else {
						// PHP 8.2 Fix: Corrected bind_param type string from "issis" to "isis" (4 parameters, not 5)
						$stmt->bind_param("isis", $RESULT->company_id, $CID, $RESULT->master_id, $CID);
						$stmt->execute();
						$res = $stmt->get_result();
				       
					SLOG( sprintf( '[GET_CUSTOMR %s:%s] get CUSTOMER ==================================================================================', $DID, $CID ) );
					if( $row = $res->fetch_array(MYSQLI_NUM) )
					{
						$RESULT->get_customer		= "success";

						$RESULT->c_level		= $row[0];

						SLOG( sprintf( '[GET_CUSTOMR %s:%s] LEVEL :%13.13s | START :%20.20s | END   :%20.20s',
									$DID, $CID, $row[0], $row[1], $row[2] ) );
					}
					// PHP 8.2 Fix: Added null check for $res
					$count = ($res !== false) ? $res->num_rows : 0;
					// Close prepared statement
					if ($stmt !== false) $stmt->close();
					} // End of T_CUSTOMER prepare check
					if( $RESULT->c_level == 'V' )
					{
						$sql = "INSERT INTO T_VIP_COUNT ( company_id, master_id, vip_cnt, mod_datetime ) VALUES ( ?, ?, 1, now() ) ON DUPLICATE KEY UPDATE vip_cnt = vip_cnt + 1, mod_datetime = NOW();";

						// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
						$sql_log = preg_replace('/\?/', $RESULT->company_id, $sql, 1);
						$sql_log = preg_replace('/\?/', $RESULT->master_id, $sql_log, 1);
						SLOG( sprintf( '[SAV_VIPCNT %s:%s] %s', $DID, $CID, $sql_log ) );

						// PHP 8.2 Fix: Added error handling for VIP_COUNT prepared statement
						$stmt = $conn->prepare($sql);
						if ($stmt !== false) {
							$stmt->bind_param("ii", $RESULT->company_id, $RESULT->master_id);
							$stmt->execute();
							$stmt->close();
						} else {
							SLOG( sprintf( '[SAV_VIPCNT %s:%s] prepare() failed: %s', $DID, $CID, $conn->error ) );
						}

						$sql = "update T_COMPANY set monitering_vip_info=?, monitering_vip_datetime=now() where company_id=? and master_id=? and company_level=2;";

						// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
						$sql_log = preg_replace('/\?/', "'".$CID."'", $sql, 1);
						$sql_log = preg_replace('/\?/', $RESULT->company_id, $sql_log, 1);
						$sql_log = preg_replace('/\?/', $RESULT->master_id, $sql_log, 1);
						SLOG( sprintf( '[UP_COMPANY %s:%s] %s', $DID, $CID, $sql_log ) );

						// PHP 8.2 Fix: Added error handling for UPDATE T_COMPANY prepared statement
						$stmt = $conn->prepare($sql);
						if ($stmt !== false) {
							$stmt->bind_param("sii", $CID, $RESULT->company_id, $RESULT->master_id);
							$stmt->execute();
							$stmt->close();
						} else {
							SLOG( sprintf( '[UP_COMPANY %s:%s] prepare() failed: %s', $DID, $CID, $conn->error ) );
						}
					}
					else if( $RESULT->c_level == 'B' )
					{
					}

					SLOG( sprintf( '[GET_CUSTOMR %s:%s] ===============================================================================================', $DID, $CID ) );

					if( $RESULT->use_db_route == 'Y' || $RESULT->use_cid_route == 'Y'  )
					{
						$sql    = '';
						$SIDO	='';
						if( $RESULT->use_cid_route == 'Y' )
						{
							if(substr($CID, 0, 2) == '02' || substr($CID, 0, 2) == '03' || substr($CID, 0, 2) == '04' || substr($CID, 0, 2) == '05' || substr($CID, 0, 2) == '06' )
							{
								if( substr($CID, 0, 2) == '02' )
								{
									$SIDO = substr($CID, 0, 2);
									$SIDO = '0'.$SIDO;
								}	
								else 
								{
									$SIDO = substr($CID, 0, 3);
								}	
								SLOG( sprintf( '[GET_CID_ROT %s:%s] FOUND LOCATION CID(02~06X) %s', $DID, $CID, $CID ) );
							}
						}
						if( $SIDO == '' )
						{
							SLOG( sprintf( '[GET_CID_ROT %s:%s] NOT FOUND LOCATION CID(02~06X) %s', $DID, $CID, $CID ) );

							$RESULT->use_cid_route = 'N';
							if( $RESULT->use_db_route == 'Y' )
							{
								//$sql = "select d.to_extension from T_LOCATION_CID AS l INNER JOIN T_DB_ROUTING AS d on l.si_do = d.si_do where d.from_did='$DID' and l.cid='$CID' and d.kind='C' order by create_datetime limit 1;";
								$sql = "SELECT d.to_extension, d.si_gun_gu FROM T_LOCATION_CID AS l INNER JOIN T_DB_ROUTING AS d ON l.si_do = d.si_do AND IF(l.si_gun_gu IS NULL, 0, l.si_gun_gu) = d.si_gun_gu WHERE d.from_did=? AND l.cid=? AND d.kind='D'  ORDER BY d.create_datetime LIMIT 1;";
								error_log($sql);
								// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
								$sql_log = preg_replace('/\?/', "'".$DID."'", $sql, 1);
								$sql_log = preg_replace('/\?/', "'".$CID."'", $sql_log, 1);
								SLOG( sprintf( '[GET_DB_ROUT %s:%s] %s', $DID, $CID, $sql_log ) );

								$stmt = $conn->prepare($sql);
								$stmt->bind_param("ss", $DID, $CID);
								$stmt->execute();
								$res = $stmt->get_result();

								//SLOG( sprintf( '[GET_DB_ROUT %s:%s] get  ============================================================================================', $DID, $CID ) );
								if( $row = $res->fetch_array(MYSQLI_NUM) )
								{
									$DR_TO_EXTEN_S       = $row[0];
								}

								$count= $res->num_rows ;
							}
						}
						else
						{
							if( $RESULT->use_cid_route == 'Y' )
							{
								$sql = "select to_extension from T_DB_ROUTING where company_id=? and from_did=? and si_do=? and kind='C' order by create_datetime limit 1;";
								error_log($sql);
								// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
								$sql_log = preg_replace('/\?/', $RESULT->company_id, $sql, 1);
								$sql_log = preg_replace('/\?/', "'".$DID."'", $sql_log, 1);
								$sql_log = preg_replace('/\?/', "'".$SIDO."'", $sql_log, 1);
								SLOG( sprintf( '[GET_CID_ROT %s:%s] %s', $DID, $CID, $sql_log ) );

								$stmt = $conn->prepare($sql);
								$stmt->bind_param("iss", $RESULT->company_id, $DID, $SIDO);
								$stmt->execute();
								$res = $stmt->get_result();

								if( $row = $res->fetch_array(MYSQLI_NUM) )
								{
									$DR_TO_EXTEN_S       = $row[0];
								}

								$count= $res->num_rows ;
							}
						}


						$RESULT->dbr_to_extension = $DR_TO_EXTEN_S;
					}

					if( $DR_TO_EXTEN_S == '0' )
					{
						$RESULT->use_cid_route = 'N';
						$RESULT->use_db_route = 'N';

						if( $RESULT->use_ars == 'Y' )
						{
						
								$sql = "select h_memt_dir from T_HOLIDAY where h_company_id=? and DATE_FORMAT(curdate(), '%m%d') >= h_startdate and DATE_FORMAT(curdate(), '%m%d') <= h_enddate limit 1;";

								error_log($sql);
								// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
								$sql_log = str_replace('?', $RESULT->company_id, $sql);
								SLOG( sprintf( '[GET_HOLIDAY %s:%s] %s', $DID, $CID, $sql_log ) );

								$stmt = $conn->prepare($sql);
								$stmt->bind_param("i", $RESULT->company_id);
								$stmt->execute();
								$res = $stmt->get_result();

								if( $row = $res->fetch_array(MYSQLI_NUM) )
							{
								error_log('T_HOLIDAY :' );
								error_log($row[0]);

								SLOG( sprintf( '[GET_HOLIDAY %s:%s] T_HOLIDAY : NAME:%s', $DID, $CID, $row[0] ) );

								$RESULT->holiday      = 'Y';
								$RESULT->holiday_ment = $row[0];
							}

							if( $RESULT->holiday      != 'Y' )
							{

								$sql = "select w_is_use, w_starttime1, w_endtime1, w_starttime2, w_endtime2, w_ment_dir, w_meal1_is_use, w_meal1_starttime, w_meal1_endtime, w_meal1_ment_dir, w_end_ment_dir, w_use_callback from T_WORKDAY_INFO where w_company_id=$RESULT->company_id and w_kind = DAYOFWEEK(curdate()) and w_is_use='Y' limit 1;";
								error_log($sql);
								SLOG( sprintf( '[GET_WKD_INF %s:%s] %s', $DID, $CID, $sql ) );

								$W_USE          = "0";
								$W_START1       = "0";
								$W_END1         = "0";
								$W_START2       = "0";
								$W_END2         = "0";
								$W_MENT         = "0";

								$M_USE          = "0";
								$M_START        = "0";
								$M_END          = "0";
								$M_MENT         = "0";

								$E_MENT         = "0";
								$CALLBACK       = "0";

								$res = mysqli_query($conn, $sql);

								SLOG( sprintf( '[GET_WKD_INF %s:%s] get T_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
								if( $row = mysqli_fetch_array($res) )
								{

									$W_USE          = $row[0];
									$W_START1       = $row[1];
									$W_END1         = $row[2];
									$W_START2       = $row[3];
									$W_END2         = $row[4];
									$W_MENT         = $row[5];

									$M_USE          = $row[6];
									$M_START        = $row[7];
									$M_END          = $row[8];
									$M_MENT         = $row[9];

									$E_MENT         = $row[10];
									$CALLBACK       = $row[11];
								}

								$count= mysqli_num_rows($res) ;

								if( $count == 0 )
								{
									$RESULT->is_workcondition       = 'VAC';
								}
								else
								{
									if( $W_USE == 'Y' )
									{
										$RESULT->is_workcondition       = 'END';
										$RESULT->is_callback            = $CALLBACK;
										$RESULT->ment_info              = $E_MENT;

										date_default_timezone_set('Asia/Seoul');

										$HOUR =date('H', time());
										$MIN =date('i', time());
										$Ttime = $HOUR.$MIN;

										if(( $M_USE == 'Y' ) && ( $Ttime >  $M_START  &&  $Ttime  < $M_END ) )
										{
											$RESULT->is_workcondition       = 'LUNCH';
											$RESULT->ment_info              = $M_MENT;
											error_log('LUNCH : '.$Ttime );
											SLOG( sprintf( '[GET_WKD_CND %s:%s] now lunch time : %s', $DID, $CID, $Ttime ) );
										}
										else if( $Ttime >=  $W_START1 && $Ttime  <= $W_END1 )
										{
											$RESULT->is_workcondition       = 'WORK';
											$RESULT->ment_info              = $W_MENT;
											error_log('WORK : '.$Ttime );
											SLOG( sprintf( '[GET_WKD_CND %s:%s] 1 now work time : %s', $DID, $CID, $Ttime ) );
										}
										else
										{
											error_log('END : '.$Ttime );
											SLOG( sprintf( '[GET_WKD_CND %s:%s] 1 now end time : %s', $DID, $CID, $Ttime ) );
										}
									}
									else
									{
										$RESULT->is_workcondition       = 'VAC';
										$RESULT->is_callback            = $CALLBACK;
									}

									$sql = "select w_starttime2, w_endtime2 from T_WORKDAY_INFO where w_company_id=$RESULT->company_id and w_kind = DAYOFWEEK(DATE_SUB(curdate(), INTERVAL 1 DAY)) limit 1;";

									error_log($sql);
									SLOG( sprintf( '[GET_WKD_INF %s:%s] %s', $DID, $CID, $sql ) );

									$W_START2       = "0";
									$W_END2       = "0";

									$res = mysqli_query($conn, $sql);

									SLOG( sprintf( '[GET_WKD_INF %s:%s] get T_WORKDAY_INFO yester but tomorrow ========================================================================', $DID, $CID ) );
									if( $row = mysqli_fetch_array($res) )
									{

										$W_START2       = $row[0];
										$W_END2         = $row[1];
									}

									$count= mysqli_num_rows($res) ;

									date_default_timezone_set('Asia/Seoul');

									$HOUR =date('H', time());
									$MIN =date('i', time());
									$Ttime = $HOUR.$MIN;

									if( $Ttime >=  $W_START2 && $Ttime <  $W_END2 )
									{
										$RESULT->is_workcondition       = 'WORK';
										$RESULT->ment_info              = $W_MENT;
										error_log('WORK : '.$Ttime );
										SLOG( sprintf( '[GET_WKD_CND %s:%s] 2 now work time : %s', $DID, $CID, $Ttime ) );
									}
										else
									{
										error_log('END : '.$Ttime );
										SLOG( sprintf( '[GET_WKD_CND %s:%s] 2 now end time : %s', $DID, $CID, $Ttime ) );
									}
								}
							}
						}
						else if( $RESULT->use_crosscall == 'Y' )
						{
							$sql = "select cw_is_use, cw_starttime1, cw_endtime1, cw_starttime2, cw_endtime2, cw_peak_is_use, cw_peak_starttime1, cw_peak_endtime1, cw_peak_starttime2, cw_peak_endtime2 from T_CC_WORKDAY_INFO where cw_company_id=$RESULT->company_id and cw_kind = DAYOFWEEK(curdate()) and cw_is_use='Y' limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CWK_INF %s:%s] %s', $DID, $CID, $sql ) );

							$CC_W_USE          = "0";
							$CC_W_START1       = "0";
							$CC_W_END1         = "0";
							$CC_W_START2       = "0";
							$CC_W_END2         = "0";

							$CC_P_USE          = "0";
							$CC_P_START1       = "0";
							$CC_P_END1         = "0";
							$CC_P_START2       = "0";
							$CC_P_END2         = "0";

							$res = mysqli_query($conn, $sql);

							SLOG( sprintf( '[GET_CWK_INF %s:%s] get T_CC_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
							if( $row = mysqli_fetch_array($res) )
							{
								$CC_W_USE          = $row[0];
								$CC_W_START1       = $row[1];
								$CC_W_END1         = $row[2];
								$CC_W_START2       = $row[3];
								$CC_W_END2         = $row[4];

								$CC_P_USE          = $row[5];
								$CC_P_START1       = $row[6];
								$CC_P_END1         = $row[7];
								$CC_P_START2       = $row[8];
								$CC_P_END2         = $row[9];
							}

							$count= mysqli_num_rows($res) ;

							if( $CC_W_USE == 'Y' )
							{
								$RESULT->is_cc_workcondition       = 'END';

								date_default_timezone_set('Asia/Seoul');

								$HOUR =date('H', time());
								$MIN =date('i', time());
								$Ttime = $HOUR.$MIN;

								if( $CC_P_USE == 'Y' )
								{
									if( $Ttime >=  $CC_P_START1  &&  $Ttime  < $CC_P_END1 )
									{
										$RESULT->is_cc_workcondition       = 'PEAK';
										error_log('PEAKTIME : '.$Ttime );
										SLOG( sprintf( '[GET_CWI_CD %s:%s] 1 now peak time : %s', $DID, $CID, $Ttime ) );
									}

									$sql = "select cw_peak_starttime2, cw_peak_endtime2 from T_CC_WORKDAY_INFO where cw_company_id=$RESULT->company_id and cw_kind = DAYOFWEEK(DATE_SUB(curdate(), INTERVAL 1 DAY)) limit 1;";

									error_log($sql);
									SLOG( sprintf( '[GET_CWK_INF %s:%s] %s', $DID, $CID, $sql ) );

									$CC_P_START2		= "0";
									$CC_P_END2		= "0";

									$res = mysqli_query($conn, $sql);

									SLOG( sprintf( '[GET_CWK_INF %s:%s] get T_CC_WORKDAY_INFO peak yester but tomorrow ================================================================', $DID, $CID ) );
									if( $row = mysqli_fetch_array($res) )
									{

										$CC_P_START2       = $row[0];
										$CC_P_END2         = $row[1];
									}

									$count= mysqli_num_rows($res) ;

									date_default_timezone_set('Asia/Seoul');

									$HOUR =date('H', time());
									$MIN =date('i', time());
									$Ttime = $HOUR.$MIN;

									if( $Ttime >=  $CC_P_START2 && $Ttime <  $CC_P_END2 )
									{
										$RESULT->is_cc_workcondition       = 'PEAK';
										error_log('PEAKTIME : '.$Ttime );
										SLOG( sprintf( '[GET_CWI_CD %s:%s] 2 now peak time : %s', $DID, $CID, $Ttime ) );
									}
									else
									{
										error_log('END : '.$Ttime );
										SLOG( sprintf( '[GET_WKD_CND %s:%s] now end time : %s', $DID, $CID, $Ttime ) );
									}
								}

								if( $RESULT->is_cc_workcondition != 'PEAK' )
								{
									if( $Ttime >=  $CC_W_START1 && $Ttime  < $CC_W_END1 )
									{
										$RESULT->is_cc_workcondition       = 'WORK';
										error_log('END : '.$Ttime );
										SLOG( sprintf( '[GET_CWI_CD %s:%s] 1 now cc work time : %s', $DID, $CID, $Ttime ) );
									}
									else
									{
										error_log('WORK : '.$Ttime );
										SLOG( sprintf( '[GET_CWI_CD %s:%s] 1 now cc end time : %s', $DID, $CID, $Ttime ) );
									}

									$sql = "select cw_starttime2, cw_endtime2 from T_CC_WORKDAY_INFO where cw_company_id=$RESULT->company_id and cw_kind = DAYOFWEEK(DATE_SUB(curdate(), INTERVAL 1 DAY)) limit 1;";

									error_log($sql);
									SLOG( sprintf( '[GET_CWK_INF %s:%s] %s', $DID, $CID, $sql ) );

									$CC_W_START2       = "0";
									$CC_W_END2       = "0";

									$res = mysqli_query($conn, $sql);

									SLOG( sprintf( '[GET_CWK_INF %s:%s] get T_CC_WORKDAY_INFO yester but tomorrow =====================================================================', $DID, $CID ) );
									if( $row = mysqli_fetch_array($res) )
									{

										$CC_W_START2       = $row[0];
										$CC_W_END2         = $row[1];
									}

									$count= mysqli_num_rows($res) ;

									date_default_timezone_set('Asia/Seoul');

									$HOUR =date('H', time());
									$MIN =date('i', time());
									$Ttime = $HOUR.$MIN;

									if( $Ttime >=  $CC_W_START2 && $Ttime <  $CC_W_END2 )
									{
										$RESULT->is_cc_workcondition       = 'WORK';
										error_log('WORK : '.$Ttime );
										SLOG( sprintf( '[GET_WKD_CND %s:%s] 2 now cc work time : %s', $DID, $CID, $Ttime ) );
									}
									else
									{
										error_log('END : '.$Ttime );
										SLOG( sprintf( '[GET_WKD_CND %s:%s] 2 now cc end time : %s', $DID, $CID, $Ttime ) );
									}
								}

								$INDEX=0;

								$TR_COMPANY_LIST = array();
								$TR_Q_LIST = array();
								$TR_DID_LIST = array();
								$TR_PBX_ID_LIST = array();

								if( $RESULT->is_cc_workcondition == 'WORK' )
								{

									//$sql = "SELECT s.receive_option, s.ring_wait_time_my, s.ring_wait_time_transfer, t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, t.transfer_id FROM T_MY_TRANSFER_CALL AS t INNER JOIN T_SET_MY AS s on s.company_id = t.company_id WHERE t.company_id = $RESULT->company_id ORDER BY t.transfer_order_num;";
                                    //$sql = "SELECT s.receive_option, s.ring_wait_time_my, s.ring_wait_time_transfer, t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, t.transfer_id, c.pbx_id FROM T_MY_TRANSFER_CALL AS t INNER JOIN T_SET_MY AS s on s.company_id = t.company_id INNER JOIN ( SELECT company_id, pbx_id FROM T_COMPANY WHERE company_id = $RESULT->company_id ORDER BY id ASC LIMIT 1) c WHERE t.company_id = $RESULT->company_id ORDER BY t.transfer_order_num;";
                                    $companyId = (int)$RESULT->company_id;

                                    $sql = <<<SQL
                                    SELECT
                                        s.receive_option,
                                        s.ring_wait_time_my,
                                        s.ring_wait_time_transfer,
                                        t.transfer_company_id,
                                        t.transfer_q_number,
                                        t.transfer_did_number,
                                        t.transfer_order_num,
                                        t.transfer_id,
                                        c.pbx_id
                                    FROM T_MY_TRANSFER_CALL AS t
                                    INNER JOIN T_SET_MY AS s
                                        ON s.company_id = t.company_id
                                    INNER JOIN (
                                        SELECT tc.company_id, tc.pbx_id
                                        FROM T_COMPANY tc
                                        INNER JOIN (
                                            SELECT company_id, MIN(id) AS min_id
                                            FROM T_COMPANY
                                            GROUP BY company_id
                                        ) x
                                            ON x.company_id = tc.company_id
                                           AND x.min_id = tc.id
                                    ) AS c
                                        ON c.company_id = t.transfer_company_id
                                    WHERE t.company_id = {$companyId}
                                    ORDER BY t.transfer_order_num
                                    SQL;

								}
								else if( $RESULT->is_cc_workcondition == 'PEAK' )
								{

									//$sql = "SELECT s.recall_transfer_time, s.recall_transfer_time, s.ring_wait_time_transfer, t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, t.transfer_id FROM T_SEQUENCE_TRANSFER_CALL AS t INNER JOIN T_SET_SEQUENCE AS s on s.company_id = t.company_id WHERE t.company_id = $RESULT->company_id ORDER BY t.transfer_order_num;";
                                    //$sql = "SELECT s.recall_transfer_time, s.recall_transfer_time, s.ring_wait_time_transfer, t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, t.transfer_id, c.pbx_id FROM T_SEQUENCE_TRANSFER_CALL AS t INNER JOIN T_SET_SEQUENCE AS s on s.company_id = t.company_id INNER JOIN ( SELECT company_id, pbx_id FROM T_COMPANY WHERE company_id = $RESULT->company_id ORDER BY id ASC LIMIT 1) c WHERE t.company_id = $RESULT->company_id ORDER BY t.transfer_order_num;";

                                    $companyId = (int)$RESULT->company_id;

                                    // PEAK: T_SET_SEQUENCE - 컬럼 순서를 WORK(T_SET_MY)와 동일하게 맞춤
                                    $sql = <<<SQL
                                    SELECT
                                        s.receive_option1 AS receive_option,
                                        s.recall_transfer_time AS ring_wait_time_my,
                                        s.ring_wait_time_transfer,
                                        t.transfer_company_id,
                                        t.transfer_q_number,
                                        t.transfer_did_number,
                                        t.transfer_order_num,
                                        t.transfer_id,
                                        c.pbx_id
                                    FROM T_SEQUENCE_TRANSFER_CALL AS t
                                    INNER JOIN T_SET_SEQUENCE AS s
                                        ON s.company_id = t.company_id
                                    INNER JOIN (
                                        SELECT tc.company_id, tc.pbx_id
                                        FROM T_COMPANY tc
                                        INNER JOIN (
                                            SELECT company_id, MIN(id) AS min_id
                                            FROM T_COMPANY
                                            GROUP BY company_id
                                        ) x
                                            ON x.company_id = tc.company_id
                                           AND x.min_id = tc.id
                                    ) AS c
                                        ON c.company_id = t.transfer_company_id

                                    WHERE t.company_id = {$companyId}
                                    ORDER BY t.transfer_order_num
                                    SQL;

								}
								else
								{
									//$sql = "SELECT s.receive_option, (select ring_wait_time_my from T_SET_MY where company_id=$RESULT->company_id order by id asc limit 1) as _MY, s.ring_wait_time_transfer, t.transfer_company_id, t.transfer_q_number, t.transfer_did_number, t.transfer_order_num, t.transfer_id FROM T_DIRECT_TRANSFER_CALL AS t INNER JOIN T_SET_DIRECT AS s on s.company_id = t.company_id WHERE t.company_id = $RESULT->company_id ORDER BY t.transfer_order_num;";

                                    $companyId = (int)$RESULT->company_id;

                                    $sql = <<<SQL
                                    SELECT
                                        s.receive_option,
                                        my.ring_wait_time_my AS _MY,
                                        s.ring_wait_time_transfer,
                                        t.transfer_company_id,
                                        t.transfer_q_number,
                                        t.transfer_did_number,
                                        t.transfer_order_num,
                                        t.transfer_id,
                                        c.pbx_id
                                    FROM T_DIRECT_TRANSFER_CALL AS t

                                    INNER JOIN T_SET_DIRECT AS s
                                        ON s.company_id = t.company_id

                                    /* MY 설정 (최초 1건) */
                                    INNER JOIN (
                                        SELECT company_id, ring_wait_time_my
                                        FROM T_SET_MY
                                        WHERE company_id = {$companyId}
                                        ORDER BY id ASC
                                        LIMIT 1
                                    ) AS my
                                        ON my.company_id = t.company_id

                                    /* transfer_company_id 기준 PBX */
                                    INNER JOIN (
                                        SELECT tc.company_id, tc.pbx_id
                                        FROM T_COMPANY tc
                                        INNER JOIN (
                                            SELECT company_id, MIN(id) AS min_id
                                            FROM T_COMPANY
                                            GROUP BY company_id
                                        ) x
                                            ON x.company_id = tc.company_id
                                           AND x.min_id = tc.id
                                    ) AS c
                                        ON c.company_id = t.transfer_company_id

                                    WHERE t.company_id = {$companyId}
                                    ORDER BY t.transfer_order_num
                                    SQL;
								}

								error_log($sql);
								SLOG( sprintf( '[GET_MY_QNUM %s:%s] %s', $DID, $CID, $sql ) );

								$res = mysqli_query($conn, $sql);

                                if ($res === false) {
                                    error_log('[MYSQL ERROR] ' . mysqli_error($conn));
                                    SLOG( sprintf( '[GET_MY_QNUM %s:%s] %s', $DID, $CID, 'MYSQL ERROR' ) );
                                    return;
                                }
								while( $row = mysqli_fetch_array($res) )
								{
									//error_log($row[0]);

									array_push( $TR_COMPANY_LIST, $row[3] );
									array_push( $TR_Q_LIST      , $row[4] );
									array_push( $TR_DID_LIST    , $row[5] );
									array_push( $TR_PBX_ID_LIST , $row[8] );

									$RESULT->tr_company_list= $TR_COMPANY_LIST;
									$RESULT->tr_q_list      = $TR_Q_LIST;
									$RESULT->tr_did_list    = $TR_DID_LIST;
									$RESULT->tr_pbx_id_list = $TR_PBX_ID_LIST;

									$RESULT->tr_count        = count($TR_Q_LIST);

									if( $INDEX == 0 )
									{
										$RESULT->tr_option 	  	= $row[0];
										$RESULT->tr_time_first   	= $row[1];
										$RESULT->tr_time_second	 	= $row[2];
									}

									$INDEX++;
							
									SLOG( sprintf( '[GET_MY_QNUM %s:%s] OPTION:%s TIME_FIRST:%s TRORDER:%s ', $DID, $CID, $row[0], $row[1], $row[2] ) );
								}
							}
							else
							{
								if( $CC_W_USE == 'N' )
								{
									$RESULT->is_cc_workcondition	= 'NSQ';
									$RESULT->tr_next_q    		= $RESULT->allocQ;
								}
								else
								{
									$RESULT->is_cc_workcondition	= 'VAC';
									$RESULT->tr_next_q    		= $RESULT->allocQ;
								}
							}
						}

						// PHP 8.2 Fix: Changed to prepared statement
						$sql = "update T_COMPANY set call_update_time=now() where company_id=?;";

						error_log($sql);
						// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
						$sql_log = str_replace('?', $RESULT->company_id, $sql);
						SLOG( sprintf( '[UPD_CALL_TM %s:%s] %s', $DID, $CID, $sql_log ) );

						// PHP 8.2 Fix: Added error handling for update query
						$stmt_upd = $conn->prepare($sql);
						if ($stmt_upd !== false) {
							$stmt_upd->bind_param("i", $RESULT->company_id);
							$stmt_upd->execute();
							$count = $stmt_upd->affected_rows;
							$stmt_upd->close();
						} else {
							SLOG( sprintf( '[UPD_CALL_TM %s:%s] prepare() failed: %s', $DID, $CID, $conn->error ) );
							$count = 0;
						}
					}
				}
		
			}
		}
		// PHP 8.2 Fix: Check if connection exists before closing
		if ($conn && !mysqli_connect_errno()) {
			mysqli_close($conn);
		}
		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	// PHP 8.2 Fix: Ensure error_log doesn't interfere with JSON response
	@error_log(sprintf('getCompany_new.php [%s]', print_r($args, true)));


	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

// PHP 8.2 Fix: Improved error logging
@error_log('+++++++++++++getCompany_new.php args->JSON_REQUEST ');
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

@error_log(sprintf('+++++++++++++getCompany_new.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'GET_COMPANY') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_company( $JSON_REQUEST->DID,
													$JSON_REQUEST->CID, 
													$JSON_REQUEST->START_TIME,
													$JSON_REQUEST->CALL_ID );
				}
				
				} else {
					$JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
					$JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ATTRIBUTE REQ REQUIRED!";
				}
			} else {
				$JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
				$JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ARGUMENT JSON_REQUEST IS NOT VALID STRING OF JSON OBJECT";
			}
        } else {

			$JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
			$JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ARGUMENT JSON_REQUEST NOT DEFINED";
        }

//      error_log(sprintf('api.php RESULT : [%s]', print_r($JSON_API_RESULT, true)));

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
				// PHP 8.2 Fix: Clean output buffer before sending JSON
				if (ob_get_level()) ob_clean();
                header("Content-Type: application/json; charset=utf-8");
                echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			}
		} else {
		// PHP 8.2 Fix: Clean output buffer before sending JSON
		if (ob_get_level()) ob_clean();
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

?>
