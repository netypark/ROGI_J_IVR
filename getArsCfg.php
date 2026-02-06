<?php

include 'plog.php';

	function get_ars_config( $DID, $CID, $TYPE ) 
	{ 
		SLOG( sprintf( '[GET_ARS_CONFIG %s:%s] START =======================================================================================================', $DID, $CID ) );

		$table = "T_ARS_CONFIG";

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
			$sql = "select c_comp_id, c_id, c_action, c_next_dtmf_len, c_info_ment, c_noinput_ment, c_wronginput_ment, c_error_ment, c_vac_ment, c_skill_routing, c_wait_time, c_wait_Q_count from $table where c_key='$DID';";
		}
		else
		{
			$sql = "select c_comp_id, c_id, c_action, c_next_dtmf_len, c_info_ment_dir, c_noinput_ment_dir, c_wronginput_ment_dir, c_error_ment_dir, c_vac_ment_dir, c_skill_routing, c_wait_time, c_wait_Q_count from $table where c_key='$DID';";
		}

		error_log($sql);
		SLOG( sprintf( '[GET_ARS_CONFIG %s:%s] %s', $DID, $CID, $sql ) );


		$RESULT = new stdClass();

		$res = mysqli_query($conn, $sql);

		SLOG( sprintf( '[GET_ARS_CONFIG %s:%s] get T_ARS_CONFIG ============================================================================================', $DID, $CID ) );
		while( $row = mysqli_fetch_array($res) ) 
		{
			/**
			error_log('T_ARS_CONFIG :' );
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
			error_log($row[10]);
			error_log($row[11]);
			**/

			SLOG( sprintf( '[GET_ARS_CONFIG %s:%s] ID    :%10.10s | COMP :%10.10s | ACTION:%10.10s | NDTMFLEN:%2.2s | INFO :%10.10s | NINPUT:%10.10s', 
						$DID, $CID, $row[1], $row[0], $row[2], $row[3], $row[4], $row[5] ) );
			SLOG( sprintf( '[GET_ARS_CONFIG %s:%s] WINPUT:%10.10s | ERROR:%10.10s | VAC   :%10.10s | SROUTING:%2.2s | WTIME:%10.10s | WQCNT  :%10.10s', 
						$DID, $CID, $row[6], $row[7], $row[8], $row[9], $row[10], $row[11] ) );

			$RESULT->comp_id    		= $row[0];
			$RESULT->ars_id     		= $row[1];
			$RESULT->action     		= $row[2];
			$RESULT->dtmf_len 			= $row[3];
			$RESULT->info_ment  	 	= $row[4];
			$RESULT->noinput_ment   	= $row[5];
			$RESULT->wronginput_ment	= $row[6];
			$RESULT->error_ment 	  	= $row[7];
			$RESULT->vac_ment 	 	 	= $row[8];
			$RESULT->skill_routing 	 	= $row[9];
			$RESULT->wait_time 	 	 	= $row[10];
			$RESULT->wait_Q_cnt  	 	= $row[11];
		}

		$count= mysqli_num_rows($res) ;

		//error_log($RESULT );

		mysqli_close ( $conn );

		SLOG( sprintf( '[GET_ARS_CONFIG %s:%s] END   =======================================================================================================', $DID, $CID ) );

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
		SLOG( sprintf( '[GET_ARS_CONFIG CALL] getArsCfg.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_ARS_CONFIG') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_ars_config( 	$JSON_REQUEST->DID,
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
