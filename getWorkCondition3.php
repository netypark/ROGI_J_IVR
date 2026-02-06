<?php

include 'plog.php';

	function get_work_condition( $DID, $CID ) 
	{ 
		SLOG( sprintf( '[GET_WORK_COND %s:%s] START =======================================================================================================', $DID, $CID ) );

		$table = "T_HOLIDAY";

                $conn = mysqli_connect(
                  '121.254.239.43',
                  'kcl',
                  '@kcl123@',
                  'KCL',
                  '3306');

		//mysqli_query($conn, "set session character_set_connection=utf8;");
		//mysqli_query($conn, "set session character_set_results=utf8;");
		//mysqli_query($conn, "set session character_set_client=utf8;");

		$sql = "0";

		$RESULT = new stdClass();

		$sql = "select w_is_use, w_starttime from T_WORKDAY_INFO;"; 

		SLOG( sprintf( '[GET_WORK_COND %s:%s] get T_ANNOUNCE ============================================================================================', $DID, $CID ) );
		SLOG( sprintf( '[GET_WORK_COND %s:%s] %s', $DID, $CID, $sql ) );

		error_log($sql);

		$res = mysqli_query($conn, $sql);

		while( $row = mysqli_fetch_array($res) )
                {
			error_log($sql);
			error_log($row[0]);
			error_log($row[1]);

			$RESULT->is_workcondition 	= 'vac';

			/**
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

			**/
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
