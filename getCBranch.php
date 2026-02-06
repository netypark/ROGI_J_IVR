<?php

include 'plog.php';

	function get_branch_info( $DID, $CID, $TYPE ) 
	{ 
		SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] START =======================================================================================================', $DID, $CID ) );

		$table = "T_BRANCH";

                $conn = mysqli_connect(
                  '118.67.142.108',
                  'root',
                  'Nautes12@$',
                  'LOGI',
                  '13306');


		//mysqli_query($conn, "set session character_set_connection=utf8;");
		//mysqli_query($conn, "set session character_set_results=utf8;");
		//mysqli_query($conn, "set session character_set_client=utf8;");

		$sql = "0";

		if( $TYPE == 'TTS' )
		{
			$sql = "select tb_comp_id, tb_id, tb_use_did_route, tb_use_db_route, tb_use_holiday, tb_use_worktime, tb_use_ars, tb_use_crosscall, tb_action, tb_to_number, tb_next_dtmf_len, tb_info_ment, tb_noinput_ment, tb_wronginput_ment, tb_error_ment, tb_vac_ment, tb_skill_routing, tb_wait_time  from T_BRANCH where tb_did_key='$DID';";
		}
		else
		{
			$sql = "select tb_comp_id, tb_id, tb_use_did_route, tb_use_db_route, tb_use_holiday, tb_use_worktime, tb_use_ars, tb_use_crosscall, tb_action, tb_to_number, tb_next_dtmf_len, tb_info_ment_dir, tb_noinput_ment_dir, tb_wronginput_ment_dir, tb_error_ment_dir, tb_vac_ment_dir, tb_skill_routing, tb_wait_time  from T_BRANCH where tb_did_key='$DID';";
		}

		error_log($sql);
		SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] %s', $DID, $CID, $sql ) );

		$COMP_ID = "0";
		$DID_ARS_ID = "0";

		$RESULT = new stdClass();

		$res = mysqli_query($conn, $sql);

		SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] get T_BRANCH ============================================================================================', $DID, $CID ) );
		if( $row = mysqli_fetch_array($res) ) 
		{
			SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] ID  :%5.5s | COMP :%5.5s | UDIDRT:%2.2s | UDBRT   :%2.2s | UHOLI:%2.2s | UWORK  :%2.2s', 
						$DID, $CID, $row[1], $row[0], $row[2], $row[3], $row[4], $row[5] ) );
			SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] UARS:%5.5s | UCC  :%5.5s | ACTION:%2.2s | DTMFLEN :%2.2s', 
						$DID, $CID, $row[6], $row[7], $row[8], $row[10] ) );
			SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] TON :%11.11s', 
						$DID, $CID, $row[9] ) );
/**
			SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] NINPUT:%10.10s | WINPUT:%11.11s | ERROR   :%10.10s | VAC:%2.2s | SROUTING:%10.10s | WTIME:%10.10s, 
						$DID, $CID, $row[12], $row[13], $row[14], $row[15], $row[16], $row[17] ) );
**/

			$RESULT->comp_id    		= $row[0];
			$COMP_ID 			= $row[0];
			$RESULT->br_did_id     		= $row[1];
			$DID_ARS_ID 			= $row[1];
			$RESULT->use_did_rt    		= $row[2];
			$RESULT->use_db_rt     		= $row[3];
			$RESULT->use_holiday   		= $row[4];
			$RESULT->use_worktime  		= $row[5];
			$RESULT->use_ars     		= $row[6];
			$RESULT->use_cc     		= $row[7];
			$RESULT->action     		= $row[8];
			$RESULT->to_number     		= $row[9];
			$RESULT->dtmf_len 		= $row[10];
			$RESULT->info_ment  	 	= $row[11];
			$RESULT->noinput_ment   	= $row[12];
			$RESULT->wronginput_ment	= $row[13];
			$RESULT->error_ment 	  	= $row[14];
			$RESULT->vac_ment  	 	= $row[15];
			$RESULT->skill_routing 	 	= $row[16];
			$RESULT->wait_time 	 	= $row[17];
		}

		$count= mysqli_num_rows($res) ;

		//error_log($RESULT );

		$sql = "select elect comp_id, level, startdate, enddate, stime, etime from T_CUSTOMER_LEVEL where tcl_comp_id = $COMP_ID and tcl_key='$CID';";
		
		error_log($sql);
		SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] %s', $DID, $CID, $sql ) );

		$res = mysqli_query($conn, $sql);

		SLOG( sprintf( '[GET_CUSTOM_LEVEL %s:%s] get T_BRANCH ============================================================================================', $DID, $CID ) );
		if( $row = mysqli_fetch_array($res) ) 
		{
			$RESULT->level			= $row[1];
			$RESULT->black_startdate	= $row[2];
			$RESULT->black_enddate		= $row[3];
			$RESULT->black_starttime	= $row[4];
			$RESULT->black_endtime		= $row[5];
		}

		$RESULT->holiday      = 'N';

		$sql = "select h_name from T_HOLIDAY where h_comp_id=$COMP_ID and h_ars_id = $DID_ARS_ID and DATE_FORMAT(curdate(), '%m%d') >= h_startdate and DATE_FORMAT(curdate(), '%m%d') <= h_enddate limit 1;";

                error_log($sql);
                SLOG( sprintf( '[GET_HOLIDAY %s:%s] %s', $DID, $CID, $sql ) );

                $res = mysqli_query($conn, $sql);

                if( $row = mysqli_fetch_array($res) )
                {
                        error_log('T_HOLIDAY :' );
                        error_log($row[0]);             
                        
                        SLOG( sprintf( '[GET_HOLIDAY %s:%s] T_HOLIDAY : NAME:%s', $DID, $CID, $row[0] ) );
                        
                        $RESULT->holiday      = 'Y';
                }
                
		$sql = "select w_is_use, w_starttime, w_endtime, w_ment_dir, w_meal1_is_use, w_meal1_starttime, w_meal1_endtime, w_meal1_ment_dir, w_end_ment_dir, w_use_callback from T_WORKDAY_INFO where w_comp_id=$COMP_ID and w_ars_id = $DID_ARS_ID and w_kind = DAYOFWEEK(curdate()) and w_is_use='Y' limit 1;";

		error_log($sql);
		SLOG( sprintf( '[GET_WORKDAY_INFO %s:%s] %s', $DID, $CID, $sql ) );

		$W_USE          = "0";
		$W_START        = "0";
		$W_END          = "0";
		$W_MENT         = "0";
	
		$M_USE          = "0";
		$M_START        = "0";
		$M_END          = "0";
		$M_MENT         = "0";
	
		$E_MENT         = "0";
		$CALLBACK       = "0";

		$res = mysqli_query($conn, $sql);

		SLOG( sprintf( '[GET_WORKDAY_INFO %s:%s] get T_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
		if( $row = mysqli_fetch_array($res) ) 
		{

			$W_USE          = $row[0];
			$W_START        = $row[1];
			$W_END          = $row[2];
			$W_MENT         = $row[3];

			$M_USE          = $row[4];
			$M_START        = $row[5];
			$M_END          = $row[6];
			$M_MENT         = $row[7];
		
			$E_MENT         = $row[8];
			$CALLBACK       = $row[9];
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
				$RESULT->is_workcondition       = 'WORK';
				$RESULT->is_callback            = $CALLBACK;
				$RESULT->ment_info                      = $W_MENT;

				date_default_timezone_set('Asia/Seoul');

				$HOUR =date('H', time());
				$MIN =date('i', time());
				$Ttime = $HOUR.$MIN;

				if(( $M_USE == 'Y' ) && ( $Ttime >  $M_START  &&  $Ttime  < $M_END ) )
				{
					$RESULT->is_workcondition       = 'LUNCH';
					$RESULT->ment_info              = $M_MENT;
					error_log('LUNCH : '.$Ttime );
					SLOG( sprintf( '[GET_WORK_COND %s:%s] now lunch time : %s', $DID, $CID, $Ttime ) );
				}
				if( $Ttime <  $W_START || $Ttime  > $W_END )
				{
					$RESULT->is_workcondition       = 'END';
					$RESULT->ment_info              = $E_MENT;
					error_log('END : '.$Ttime );
					SLOG( sprintf( '[GET_WORK_COND %s:%s] now end work time : %s', $DID, $CID, $Ttime ) );
				}
				else
					error_log('WORK : '.$Ttime );
					SLOG( sprintf( '[GET_WORK_COND %s:%s] now work time : %s', $DID, $CID, $Ttime ) );
			}
			else
			{
				$RESULT->is_workcondition       = 'VAC';
				$RESULT->is_callback            = $CALLBACK;
			}
		}

		$sql = "select cw_is_use, cw_starttime, cw_endtime, cw_peak_is_use, cw_peak_starttime, cw_peak_endtime from T_CC_WORKDAY_INFO where cw_comp_id=$COMP_ID and cw_ars_id = $DID_ARS_ID and cw_kind = DAYOFWEEK(curdate()) and cw_is_use='Y' limit 1;";

		error_log($sql);
		SLOG( sprintf( '[GET_CC_WORKDAY_INFO %s:%s] %s', $DID, $CID, $sql ) );

                $CC_W_USE          = "0";
                $CC_W_START        = "0";
                $CC_W_END          = "0";

                $CC_P_USE          = "0";
                $CC_P_START        = "0";
                $CC_P_END          = "0";

		$res = mysqli_query($conn, $sql);

		SLOG( sprintf( '[GET_CC_WORKDAY_INFO %s:%s] get T_CC_WORKDAY_INFO ============================================================================================', $DID, $CID ) );
		if( $row = mysqli_fetch_array($res) ) 
		{
                        $CC_W_USE          = $row[0];
                        $CC_W_START        = $row[1];
                        $CC_W_END          = $row[2];

                        $CC_P_USE          = $row[3];
                        $CC_P_START        = $row[4];
                        $CC_P_END          = $row[5];
		}

		if( $CC_W_USE == 'Y' )
		{
			$RESULT->is_cc_workcondition       = 'WORK';

			date_default_timezone_set('Asia/Seoul');

			$HOUR =date('H', time());
			$MIN =date('i', time());
			$Ttime = $HOUR.$MIN;

			if(( $CC_P_USE == 'Y' ) && ( $Ttime >  $CC_P_START  &&  $Ttime  < $CC_P_END ) )
			{
				$RESULT->is_cc_workcondition       = 'PEAK';
				error_log('PEAKTIME : '.$Ttime );
				SLOG( sprintf( '[GET_CC_WORKDAY_INFO %s:%s] now peak time : %s', $DID, $CID, $Ttime ) );
			}
			if( $Ttime <  $W_START || $Ttime  > $W_END )
			{
				$RESULT->is_cc_workcondition       = 'END';
				error_log('END : '.$Ttime );
				SLOG( sprintf( '[GET_CC_WORKDAY_INFOWORK_COND %s:%s] now end work time : %s', $DID, $CID, $Ttime ) );
			}
			else
				error_log('WORK : '.$Ttime );
				SLOG( sprintf( '[GET_CC_WORKDAY_INFOWORK_COND %s:%s] now work time : %s', $DID, $CID, $Ttime ) );
		}
		else
		{
			$RESULT->is_cc_workcondition       = 'VAC';
		}

		mysqli_close ( $conn );


		SLOG( sprintf( '[GET_CUSTOM_BRANCH %s:%s] END   =======================================================================================================', $DID, $CID ) );
		return $RESULT;
		//return success;
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

	error_log(sprintf('getArsCfg.php [%s]\n', print_r($args, true)));

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	if (isset($args->JSON_REQUEST)) 
	{
		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) 
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('call getArsCfg.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		SLOG( sprintf( '[GET_CUSTOM_BRANCH CALL] getArsCfg.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_CUSTOM_BRANCH') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_branch_info( 	$JSON_REQUEST->DID,
														$JSON_REQUEST->CID,
														$JSON_REQUEST->TYPE );
				}

			} 
			else 
			{
				$JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
				$JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ATTRIBUTE REQ REQUIRED!";
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
