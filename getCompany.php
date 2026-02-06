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

		$sql = "select company_id, company_name, company_level, did_number,  did_number, call_line, call_line_rider, reg_call_line, reg_call_line_rider, parent_id, main_didnumber_range, main_account_id, main_account_pw, is_active, master_id, use_did_route, use_db_route, use_holiday, use_worktime, use_ars, use_crosscall, action, to_number, skill_routing, is_call_wait, next_dtmf_len, pscn, wait_time, vac_ment, vac_ment_dir, info_ment, info_ment_dir, noinput_ment, noinput_ment_dir, wronginput_ment, wronginput_ment_dir, error_ment, error_ment_dir, create_datetime, mod_datetime, did_route_description from T_COMPANY where did_number=? limit 1;";

		error_log($sql);
		SLOG( sprintf( '[GET_COMPANY %s:%s] %s', $DID, $CID, $sql ) );
                $RESULT = new stdClass();

		$RESULT->get_company	= "fail";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $DID);
        $stmt->execute();
        $res = $stmt->get_result();
               
                SLOG( sprintf( '[GET_COMPANY %s:%s] get T_COMPANY ==================================================================================================', $DID, $CID ) );
                if( $row = $res->fetch_array(MYSQLI_NUM) )
                {       
                        SLOG( sprintf( '[GET_COMPANY %s:%s] ID    :%13.13s | LEVEL :%13.13s | CDNUM :%13.13s | DNUM  :%13.13s | NAME  :%13.13s',
                                                $DID, $CID, $row[0], $row[2], $row[3], $row[4], $row[1] ) );

                        SLOG( sprintf( '[GET_COMPANY %s:%s] CLINE :%13.13s | CLRIDR:%13.13s | RCLIN :%13.13s | RCLRDR:%13.13s | MDRANG:%13.13s',
                                                $DID, $CID, $row[5], $row[6], $row[7], $row[8], $row[10] ) );


                        SLOG( sprintf( '[GET_COMPANY %s:%s] PARID :%13.13s | ISACT :%13.13s | MASID :%13.13s | UDIDRT:%13.13s | UDBRT :%13.13s',
                                                $DID, $CID, $row[9], $row[13], $row[14], $row[15], $row[16] ) );

                        SLOG( sprintf( '[GET_COMPANY %s:%s] UHOLI :%13.13s | UWORK :%13.13s | UARS  :%13.13s | UCROS :%13.13s | ACTION:%13.13s',
                                                $DID, $CID, $row[17], $row[18], $row[19], $row[20], $row[21]  ) );

                        SLOG( sprintf( '[GET_COMPANY %s:%s] TONUM :%13.13s | SKIRT :%13.13s | CWAIT :%13.13s | NDTMFL:%13.13s | PSCN  :%13.13s',
                                                $DID, $CID, $row[22], $row[23], $row[24], $row[25], $row[26]  ) );

                        SLOG( sprintf( '[GET_COMPANY %s:%s] WAITTM:%13.13s | VACMT :%13.13s | VACDIR:%13.13s | INFMT :%13.13s | INFDIR:%13.13s',
                                                $DID, $CID, $row[27], $row[28], $row[29], $row[30], $row[31] ) );

                        SLOG( sprintf( '[GET_COMPANY %s:%s] NOPMT :%13.13s | NOPDIR:%13.13s | WRPMT :%13.13s | WRPDIR:%13.13s | ERRMT :%13.13s',
                                                $DID, $CID, $row[32], $row[33], $row[34], $row[35], $row[36] ) );

                        SLOG( sprintf( '[GET_COMPANY %s:%s] ERRDIR:%13.13s |',
                                                $DID, $CID, $row[37] ) );

                        //SLOG( sprintf( '[GET_COMPANY %s:%s] ERRDIR:%13.13s | C:%19.19s| M:%19.19s|',
                                                //$DID, $CID, $row[37], $row[38], $row[39] ) );


			$RESULT->get_company		= "success";
                        
                        $RESULT->company_id		= $row[0];
                        $RESULT->company_name		= $row[1];
                        $RESULT->company_level		= $row[2];
                        $RESULT->company_didnumber	= $row[3];
                        $RESULT->did_number		= $row[4];
                        $RESULT->call_line		= $row[5];
                        $RESULT->call_line_rider	= $row[6];
                        $RESULT->reg_call_line		= $row[7];
                        $RESULT->reg_call_line_rider	= $row[8];
                        $RESULT->parent_id		= $row[9];
                        $RESULT->main_didnumber_range	= $row[10];
                        $RESULT->main_account_id	= $row[11];
                        $RESULT->main_account_pw	= $row[12];
                        $RESULT->is_active		= $row[13];
                        $RESULT->master_id		= $row[14];
                        $RESULT->use_did_route		= $row[15];
                        $RESULT->use_db_route		= $row[16];
                        $RESULT->use_holiday		= $row[17];
                        $RESULT->use_worktime		= $row[18];
                        $RESULT->use_ars		= $row[19];
                        $RESULT->use_crosscall		= $row[20];
                        $RESULT->action			= $row[21];
                        $RESULT->to_number		= $row[22];
                        $RESULT->skill_routing		= $row[23];
                        $RESULT->is_call_wait		= $row[24];
                        $RESULT->next_dtmf_len		= $row[25];
                        $RESULT->pscn			= $row[26];
                        $RESULT->wait_time		= $row[27];
                        $RESULT->vac_ment		= $row[28];
                        $RESULT->vac_ment_dir		= $row[29];
                        $RESULT->info_ment		= $row[30];
                        $RESULT->info_ment_dir		= $row[31];
                        $RESULT->noinput_ment		= $row[32];
                        $RESULT->noinput_ment_dir	= $row[33];
                        $RESULT->wronginput_ment	= $row[34];
                        $RESULT->wronginput_ment_dir	= $row[35];
                        $RESULT->error_ment		= $row[36];
                        $RESULT->error_ment_dir		= $row[37];
                        //$RESULT->create_datetime	= $row[38];
                        //$RESULT->mod_datetime		= $row[39];
                        //$RESULT->did_route_description	= $row[40];
                }

                $count= $res->num_rows ;

                SLOG( sprintf( '[GET_COMPANY %s:%s] ================================================================================================================', $DID, $CID ) );

		if( $RESULT->get_company == "success" )
		{
			$sql = "select c_phone, c_level, start_datetime, end_datetime, blocking_time, b_stime, b_etime, b_reg_user_id, b_reg_user_name from T_CUSTOMER where company_id=? and c_phone=? limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_CUSTOMR %s:%s] %s', $DID, $CID, $sql ) );


			$RESULT->get_customer		= "fail";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param("is", $RESULT->company_id, $CID);
			$stmt->execute();
			$res = $stmt->get_result();
		       
			SLOG( sprintf( '[GET_CUSTOMR %s:%s] get CUSTOMER ==================================================================================', $DID, $CID ) );
			if( $row = $res->fetch_array(MYSQLI_NUM) )
			{
				$RESULT->get_customer		= "success";

				$RESULT->c_phone		= $row[0];
				$RESULT->c_level		= $row[1];
				$RESULT->b_start_datetime	= $row[2];
				$RESULT->b_end_datetime		= $row[3];
				$RESULT->blocking_time		= $row[4];
				$RESULT->b_stime		= $row[5];
				$RESULT->b_etime		= $row[6];

				if( $RESULT->b_stime > $RESULT->b_etime )
				{
					$RESULT->b_stime1 = $row[5];
					$RESULT->b_etime1 = '2400';
					$RESULT->b_stime2 = '0000';
					$RESULT->b_etime2 = $row[6];
				}
				else
				{
					$RESULT->b_stime1 = $row[5];
					$RESULT->b_etime1 = $row[6];
					$RESULT->b_stime2 = $row[5];
					$RESULT->b_etime2 = $row[6];
				}
				//$RESULT->b_reg_user_id		= $row[7];
				//$RESULT->b_reg_user_name	= $row[8];

				SLOG( sprintf( '[GET_CUSTOMR %s:%s] LEVEL :%13.13s | START :%20.20s | END   :%20.20s', 
							$DID, $CID, $row[1], $row[2], $row[3] ) );
				SLOG( sprintf( '[GET_CUSTOMR %s:%s] BLCKTM:%13.13s | BSTART:%20.20s | BEND  :%20.20s',
							$DID, $CID, $row[4], $row[5], $row[6] ) );
			}       
			$count= $res->num_rows ;

			SLOG( sprintf( '[GET_CUSTOMR %s:%s] ===============================================================================================', $DID, $CID ) );

			$RESULT->holiday      = 'N';
			$RESULT->holiday_ment = 'Y';

			$sql = "select h_memt_dir from T_HOLIDAY where h_company_id=? and DATE_FORMAT(curdate(), '%m%d') >= h_startdate and DATE_FORMAT(curdate(), '%m%d') <= h_enddate limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_HOLIDAY %s:%s] %s', $DID, $CID, $sql ) );

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


			$sql = "select w_is_use, w_starttime1, w_endtime1, w_starttime2, w_endtime2, w_ment_dir, w_meal1_is_use, w_meal1_starttime, w_meal1_endtime, w_meal1_ment_dir, w_end_ment_dir, w_use_callback from T_WORKDAY_INFO where w_company_id=? and w_kind = DAYOFWEEK(curdate()) and w_is_use='Y' limit 1;";
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

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i", $RESULT->company_id);
			$stmt->execute();
			$res = $stmt->get_result();

			SLOG( sprintf( '[GET_WKD_INF %s:%s] get T_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
			if( $row = $res->fetch_array(MYSQLI_NUM) )
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

			$count= $res->num_rows ;

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

				$sql = "select w_starttime2, w_endtime2 from T_WORKDAY_INFO where w_company_id=? and w_kind = DAYOFWEEK(DATE_SUB(curdate(), INTERVAL 1 DAY)) limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_WKD_INF %s:%s] %s', $DID, $CID, $sql ) );

				$W_START2       = "0";
				$W_END2       = "0";

				$stmt = $conn->prepare($sql);
				$stmt->bind_param("i", $RESULT->company_id);
				$stmt->execute();
				$res = $stmt->get_result();

				SLOG( sprintf( '[GET_WKD_INF %s:%s] get T_WORKDAY_INFO yester but tomorrow ========================================================================', $DID, $CID ) );
				if( $row = $res->fetch_array(MYSQLI_NUM) )
				{

					$W_START2       = $row[0];
					$W_END2         = $row[1];
				}

				$count= $res->num_rows ;

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

			$sql = "select cw_is_use, cw_starttime1, cw_endtime1, cw_starttime2, cw_endtime2, cw_peak_is_use, cw_peak_starttime1, cw_peak_endtime1, cw_peak_starttime2, cw_peak_endtime2 from T_CC_WORKDAY_INFO where cw_company_id=? and cw_kind = DAYOFWEEK(curdate()) and cw_is_use='Y' limit 1;";

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

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i", $RESULT->company_id);
			$stmt->execute();
			$res = $stmt->get_result();

			SLOG( sprintf( '[GET_CWK_INF %s:%s] get T_CC_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
			if( $row = $res->fetch_array(MYSQLI_NUM) )
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

			$count= $res->num_rows ;

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

					$sql = "select cw_peak_starttime2, cw_peak_endtime2 from T_CC_WORKDAY_INFO where cw_company_id=? and cw_kind = DAYOFWEEK(DATE_SUB(curdate(), INTERVAL 1 DAY)) limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CWK_INF %s:%s] %s', $DID, $CID, $sql ) );

					$CC_P_START2		= "0";
					$CC_P_END2		= "0";

					$stmt = $conn->prepare($sql);
					$stmt->bind_param("i", $RESULT->company_id);
					$stmt->execute();
					$res = $stmt->get_result();

					SLOG( sprintf( '[GET_CWK_INF %s:%s] get T_CC_WORKDAY_INFO peak yester but tomorrow ================================================================', $DID, $CID ) );
					if( $row = $res->fetch_array(MYSQLI_NUM) )
					{

						$CC_P_START2       = $row[0];
						$CC_P_END2         = $row[1];
					}

					$count= $res->num_rows ;

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

					$sql = "select cw_starttime2, cw_endtime2 from T_CC_WORKDAY_INFO where cw_company_id=? and cw_kind = DAYOFWEEK(DATE_SUB(curdate(), INTERVAL 1 DAY)) limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CWK_INF %s:%s] %s', $DID, $CID, $sql ) );

					$CC_W_START2       = "0";
					$CC_W_END2       = "0";

					$stmt = $conn->prepare($sql);
					$stmt->bind_param("i", $RESULT->company_id);
					$stmt->execute();
					$res = $stmt->get_result();

					SLOG( sprintf( '[GET_CWK_INF %s:%s] get T_CC_WORKDAY_INFO yester but tomorrow =====================================================================', $DID, $CID ) );
					if( $row = $res->fetch_array(MYSQLI_NUM) )
					{

						$CC_W_START2       = $row[0];
						$CC_W_END2         = $row[1];
					}

					$count= $res->num_rows ;

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
			}
			else
			{
				$RESULT->is_cc_workcondition       = 'VAC';
			}


			$sql = "select location, to_extension from T_DB_ROUTING where company_id=? and from_did=? and from_cid=? order by create_datetime limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_DB_ROUT %s:%s] %s', $DID, $CID, $sql ) );

			$DR_LOCATION      = "0";
			$DR_TO_EXTEN      = "0";

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("iss", $RESULT->company_id, $DID, $CID);
			$stmt->execute();
			$res = $stmt->get_result();

			SLOG( sprintf( '[GET_DB_ROUT %s:%s] get T_CC_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
			if( $row = $res->fetch_array(MYSQLI_NUM) )
			{
				$DR_LOCATION       = $row[0];
				$DR_TO_EXTEN       = $row[1];
			}

			$count= $res->num_rows ;

			$RESULT->dbr_location     = $DR_LOCATION;
			$RESULT->dbr_to_extension = $DR_TO_EXTEN;

/***
			$sql = "insert into T_CALL_STATE ( company_id, company_did, caller, called, start_time, answer_time, call_id, call_state ) values ( ?, ?, ?, ?, ?, ?, ?, ? );";

			error_log($sql);
			SLOG( sprintf( '[INS_CALL_ST %s:%s] %s', $DID, $CID, $sql ) );

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("isssssss", $RESULT->company_id, $DID, $CID, $DID, $START_TIME, '', $CALL_ID, 'A');
			$stmt->execute();

			$count= $stmt->affected_rows ;
***/

			$sql = "update T_COMPANY set call_update_time=now() where company_id=?;";

			error_log($sql);
			SLOG( sprintf( '[UPD_CALL_TM %s:%s] %s', $DID, $CID, $sql ) );

			$stmt = $conn->prepare($sql);
			$stmt->bind_param("i", $RESULT->company_id);
			$stmt->execute();

			$count= $stmt->affected_rows ;
		}

		mysqli_close ( $conn );

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	error_log(sprintf('makedir.php [%s]', print_r($args, true)));


	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

error_log('+++++++++++++get_custom_branch.php args->JSON_REQUEST ');
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

error_log(sprintf('+++++++++++++get_custom_branch.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

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
				echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT);
			}
		} else {
		echo json_encode($JSON_API_RESULT, JSON_PRETTY_PRINT);
	}

?>
