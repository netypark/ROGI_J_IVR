<?php

include 'plog.php';

	function get_work_condition( $COMP_ID, $ARS_ID, $DID, $CID, $TYPE ) 
	{ 
		SLOG( sprintf( '[GET_WORK_COND %s:%s] START =======================================================================================================', $DID, $CID ) );

		$table = "T_HOLIDAY";

                $conn = mysqli_connect(
                  '14.63.83.217',
                  'root',
                  'mycat123',
                  'OPENAPI',
                  '3306');

		mysqli_query($conn, "set session character_set_connection=utf8;");
		mysqli_query($conn, "set session character_set_results=utf8;");
		mysqli_query($conn, "set session character_set_client=utf8;");

		$sql = "0";

		if( $TYPE == 'TTS' )
		{
			$sql = "select h_name from T_HOLIDAY where h_comp_id=$COMP_ID and h_ars_id = $ARS_ID and DATE_FORMAT(curdate(), '%m%d') >= h_startdate and DATE_FORMAT(curdate(), '%m%d') <= h_enddate limit 1;";
		}
		else
		{
			$sql = "select h_name from T_HOLIDAY where h_comp_id=$COMP_ID and h_ars_id = $ARS_ID and DATE_FORMAT(curdate(), '%m%d') >= h_startdate and DATE_FORMAT(curdate(), '%m%d') <= h_enddate limit 1;";
		}

		error_log($sql);
		SLOG( sprintf( '[GET_WORK_COND %s:%s] %s', $DID, $CID, $sql ) );


		$INDEX = 0;
		$RESULT = new stdClass();

		$res = mysqli_query($conn, $sql);

		if( $row = mysqli_fetch_array($res) ) 
		{
			error_log('T_HOLIDAY :' );
			error_log($row[0]);

			SLOG( sprintf( '[GET_WORK_COND %s:%s] T_HOLIDAY : NAME:%s', $DID, $CID, $row[0] ) );

			$RESULT->ment_info 	= $row[0];
		}

		$count= mysqli_num_rows($res);

		$sql = "0";

		if( $count == 0 )
		{
			if( $TYPE == 'TTS' )
			{
				$sql = "select w_is_use, w_starttime, w_endtime, w_ment, w_meal1_is_use, w_meal1_starttime, w_meal1_endtime, w_meal1_ment, w_end_ment, w_use_callback from T_WORKDAY_INFO where w_comp_id=$COMP_ID and w_ars_id = $ARS_ID and w_kind = DAYOFWEEK(curdate()) and w_is_use='Y' limit 1;"; 
			}
			else
			{	
				$sql = "select w_is_use, w_starttime, w_endtime, w_ment_dir, w_meal1_is_use, w_meal1_starttime, w_meal1_endtime, w_meal1_ment_dir, w_end_ment_dir, w_use_callback from T_WORKDAY_INFO where w_comp_id=$COMP_ID and w_ars_id = $ARS_ID and w_kind = DAYOFWEEK(curdate()) and w_is_use='Y' limit 1;"; 
			}

			error_log($sql);
			SLOG( sprintf( '[GET_WORK_COND %s:%s] %s', $DID, $CID, $sql ) );

			$W_USE  	= "0";
			$W_START 	= "0";
			$W_END 		= "0";
			$W_MENT		= "0";

			$M_USE	 	= "0";
			$M_START 	= "0";
			$M_END 		= "0";
			$M_MENT		= "0";

			$E_MENT		= "0";
			$CALLBACK	= "0";

			$res = mysqli_query($conn, $sql);

			SLOG( sprintf( '[GET_WORK_COND %s:%s] get T_WORKDAY_INFO ============================================================================================', $DID, $CID ) );

			if( $row = mysqli_fetch_array($res) ) 
			{
				/**
				error_log('T_WORKDAY_INFO :' );
				error_log($row[0]);
				error_log($row[1]);
				error_log($row[2]);
				error_log($row[3]);
				error_log($row[4]);
				error_log($row[5]);
				error_log($row[6]);
				error_log($row[7]);
				error_log($row[8]);
				error_log($row[9]);
				**/

				SLOG( sprintf( '[GET_WORK_COND %s:%s] WORK_USE :%2.2s | WORK_STIME :%5.5s | WORK_ETIME:%5.5s | WORK_MDIR:%15.15s | WORK_EMDIR:%15.15s', 
							$DID, $CID, $row[0], $row[1], $row[2], $row[3], $row[8] ) );
				SLOG( sprintf( '[GET_WORK_COND %s:%s] MEAL_USE :%2.2s | MEAL_STIME :%5.5s | MEAL_ETIME:%5.5s | MEAL_MDIR:%15.15s | CALLBACK  :%15.15s', 
							$DID, $CID, $row[4], $row[5], row[6], row[7], row[9] ) );

				$W_USE		= $row[0];
				$W_START	= $row[1];
				$W_END		= $row[2];
				$W_MENT		= $row[3];

				$M_USE		= $row[4];
				$M_START	= $row[5];
				$M_END		= $row[6];
				$M_MENT		= $row[7];

				$E_MENT		= $row[8];
				$CALLBACK	= $row[9];
			}

			$count= mysqli_num_rows($res);

			if( $count == 0 )
			{
				$RESULT->is_workcondition 	= 'VAC';
			}
			else
			{
				if( $W_USE == 'Y' )
				{
					$RESULT->is_workcondition 	= 'WORK';
					$RESULT->is_callback 		= $CALLBACK;
					$RESULT->ment_info 			= $W_MENT;

					date_default_timezone_set('Asia/Seoul');

					$HOUR =date('H', time());
					$MIN =date('i', time());
					$Ttime = $HOUR.$MIN;
					
					if(( $M_USE == 'Y' ) && ( $Ttime >  $M_START  &&  $Ttime  < $M_END ) )
					{
						$RESULT->is_workcondition 	= 'LUNCH';
						$RESULT->ment_info 		= $M_MENT;
						error_log('LUNCH : '.$Ttime );
						SLOG( sprintf( '[GET_WORK_COND %s:%s] now lunch time : %s', $DID, $CID, $Ttime ) );
					}
					if( $Ttime <  $W_START || $Ttime  > $W_END )
					{
						$RESULT->is_workcondition 	= 'END';
						$RESULT->ment_info 		= $E_MENT;
						error_log('END : '.$Ttime );
						SLOG( sprintf( '[GET_WORK_COND %s:%s] now end work time : %s', $DID, $CID, $Ttime ) );
					}
					else
						error_log('WORK : '.$Ttime );
						SLOG( sprintf( '[GET_WORK_COND %s:%s] now work time : %s', $DID, $CID, $Ttime ) );
				}
				else
				{
					$RESULT->is_workcondition 	= 'VAC';
					$RESULT->is_callback 		= $CALLBACK;
				}
			}
		}
		else
		{	
			$RESULT->is_workcondition 	= 'HOLI';
		}

		//error_log($RESULT );

		mysqli_close ( $conn );


		// 데이터 출력후 statement 를 해제한다

		SLOG( sprintf( '[GET_WORK_COND %s:%s] END   =======================================================================================================', $DID, $CID ) );

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

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	if (isset($args->JSON_REQUEST)) 
	{
		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) 
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('call getWorkCondition.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		SLOG( sprintf( '[GET_WORK_COND CALL] getWorkCondition.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_WORK_CONDITION') 
				{
					$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_work_condition( $JSON_REQUEST->COMP_ID,
														$JSON_REQUEST->ARS_ID ,
														$JSON_REQUEST->DID ,
														$JSON_REQUEST->CID ,
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
