<?php

include 'plog.php';

	function get_work_condition( $DID, $CID ) 
	{ 
		SLOG( sprintf( '[GET_WORK_COND %s:%s] START =======================================================================================================', $DID, $CID ) );

		$table = "T_HOLIDAY";

                $conn = mysqli_connect(
                  '121.254.239.50',
                  'nautes',
                  'Nautes12@$',
                  'LOGI',
                  '3306');

		//mysqli_query($conn, "set session character_set_connection=utf8;");
		//mysqli_query($conn, "set session character_set_results=utf8;");
		//mysqli_query($conn, "set session character_set_client=utf8;");

		$sql = "0";

		$RESULT = new stdClass();

		$sql = "select h_name from T_HOLIDAY where DATE_FORMAT(curdate(), '%m%d') >= h_startdate and DATE_FORMAT(curdate(), '%m%d') <= h_enddate limit 1;";

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
			$sql = "select w_is_use, w_starttime1, w_endtime1, w_starttime2, w_endtime2, w_ment, w_meal1_is_use, w_meal1_starttime, w_meal1_endtime, w_meal1_ment, w_end_ment from T_WORKDAY_INFO where w_kind = DAYOFWEEK(curdate()) and w_is_use='Y' limit 1;"; 

			error_log($sql);
			SLOG( sprintf( '[GET_WORK_COND %s:%s] %s', $DID, $CID, $sql ) );

			$W_USE  	= "0";
			$W_START1 	= "0";
			$W_END1 	= "0";
			$W_START2 	= "0";
			$W_END2		= "0";
			$W_MENT		= "0";

			$M_USE	 	= "0";
			$M_START 	= "0";
			$M_END 		= "0";
			$M_MENT		= "0";

			$E_MENT		= "0";

			$res = mysqli_query($conn, $sql);

			SLOG( sprintf( '[GET_WORK_COND %s:%s] get T_WORKDAY_INFO ============================================================================================', $DID, $CID ) );

			if( $row = mysqli_fetch_array($res) ) 
			{
				error_log('T_WORKDAY_INFO :' );
				error_log($row[0]); // w_is_use
				error_log($row[1]); // w_starttime1
				/**
				error_log($row[2]); // w_endtime1
				error_log($row[3]); // w_starttime2
				error_log($row[4]); // w_endtime2
				error_log($row[5]); // w_ment
				error_log($row[6]); // w_meal1_is_use
				error_log($row[7]); // w_meal1_starttime
				error_log($row[8]); // w_meal1_endtime
				error_log($row[9]); // w_meal1_ment
				error_log($row[10]); // w_end_ment
				**/

				SLOG( sprintf( '[GET_WORK_COND %s:%s] WORK_USE :%2.2s| WORK_STIME1:%5.5s | WORK_ETIME2:%5.5s | WORK_STIME2:%5.5s | WORK_ETIME2:%5.5s | MEAL_STIME:%5.5s | MEAL_ETIME:%5.5s
							$DID, $CID, $row[0], $row[1], $row[2], $row[3], $row[4], $row[7], $row[8] ) );
				SLOG( sprintf( '[GET_WORK_COND %s:%s] WORK_MDIR:%15.15s | WORK_EMDIR:%15.15s'| MEAL_MDIR:%15.15s
							$DID, $CID, $row[5], $row[10], row[9] ) );

				$W_USE		= $row[0];
				$W_START1	= $row[1];
				$W_END1		= $row[2];
				$W_START2	= $row[3];
				$W_END2		= $row[4];
				$W_MENT		= $row[5];

				$M_USE		= $row[6];
				$M_START	= $row[7];
				$M_END		= $row[8];
				$M_MENT		= $row[9];

				$E_MENT		= $row[10];
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
					$RESULT->ment_info 		= $W_MENT;

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
					if( ( $Ttime <  $W_START1 || $Ttime  > $W_END2 ) || ( $Ttime <  $W_START2 || $Ttime  > $W_END2 ) )
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
				}
			}
		}
		else
		{	
			$RESULT->is_workcondition 	= 'HOLI';
		}

		//$sql = "0";

		$sql = "select a_kind, a_memt_dir from T_ANNOUNCE;"; 

		SLOG( sprintf( '[GET_WORK_COND %s:%s] get T_ANNOUNCE ============================================================================================', $DID, $CID ) );
		SLOG( sprintf( '[GET_WORK_COND %s:%s] %s', $DID, $CID, $sql ) );

		//error_log($sql);

		$res = mysqli_query($conn, $sql);

		while( $row = mysqli_fetch_array($res) )
                {
			//error_log($row[0]);
			//error_log($row[1]);

			SLOG( sprintf( '[GET_WORK_COND %s:%s] %s %s ', $DID, $CID, $row[0], $fow[1] ) );
			$KIND		= $row[0];
			$MENT		= $row[1];
			if( $KIND == '1' )
				$RESULT->ment_work 	= $MENT;
			else if( $KIND == '2' )
				$RESULT->ment_meal 	= $MENT;
			else if( $KIND == '3' )
				$RESULT->ment_end 	= $MENT;
			else if( $KIND == '4' )
				$RESULT->ment_busy 	= $MENT;
			else if( $KIND == '5' )
				$RESULT->ment_noans 	= $MENT;

		}

		$count= mysqli_num_rows($res);

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

					$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_work_condition( $JSON_REQUEST->DID ,
														$JSON_REQUEST->CID );
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
