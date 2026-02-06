<?php
/**
 * Modification History
 * Date: 2026-01-20
 * Changes: Updated for PHP 8.2.30 compatibility
 * Reference backup file: getArsScn.php.backup_20260120_152951
 */

include 'plog.php';

	function get_ars_scenario( $COMP_ID, $ARS_ID, $DID, $CID, $TYPE ) 
	{ 

		SLOG( sprintf( '[GET_ARS_SCN %s:%s] START =======================================================================================================', $DID, $CID ) );

		$table = "T_ARS_SCENARIO";

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
		// PHP 8.2 Fix: Add error handling for mysqli_query
		if ($conn) {
			mysqli_query($conn, "set session character_set_connection=utf8;");
			mysqli_query($conn, "set session character_set_results=utf8;");
			mysqli_query($conn, "set session character_set_client=utf8;");
		}

		$sql = "0";

		if( $TYPE == 'TTS' )
		{
			$sql = "select s_level, s_dtmf, s_id, s_pid, s_action, s_next_level, s_next_dtmf_len, s_ment from $table where s_ars_id='$ARS_ID' order by s_level, s_pid;";
		}
		else
		{
			$sql = "select s_level, s_dtmf, s_id, s_pid, s_action, s_next_level, s_next_dtmf_len, s_ment_dir from $table where s_ars_id='$ARS_ID' order by s_level, s_pid;";
		}

		error_log($sql);
		SLOG( sprintf( '[GET_ARS_SCN %s:%s] %s', $DID, $CID, $sql ) );

		$INDEX = 0;
		$RESULT = new stdClass();
		//$JL_3 = new stdclass();
		//$JL_2 = new stdclass();

		$RESULT->SCN1 = new stdClass();
		$RESULT->SCN2 = new stdClass();

		$LEVEL = '0';
		$PSID  = '0';
		$DTMF  = '0';

		// PHP 8.2 Fix: Add error handling and null checks for mysqli_query
		$res = $conn ? mysqli_query($conn, $sql) : false;

		SLOG( sprintf( '[GET_ARS_SCN %s:%s] get T_ARS_SCENARIO ==========================================================================================', $DID, $CID ) );

		// PHP 8.2 Fix: Add result validation for mysqli_fetch_array
		while( $res && ($row = mysqli_fetch_array($res)) ) 
		{
			/**
			error_log('comp_id :' );
			error_log($row[0]);
			error_log($row[1]);
			error_log($row[2]);
			error_log($row[3]);
			error_log($row[4]);
			error_log($row[5]);
			error_log($row[6]);
			error_log($row[7]);
			**/

			if( $row[1] == '*' ) $row[1] = '10';
			else if( $row[1] == '#' ) $row[1] = '11';

			if( $LEVEL == 'L_'.$row[0] )
			{
				error_log('level : same '.$LEVEL .' : '. $row[0]);

				SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : same %s : %s', $DID, $CID, $LEVEL, $row[0] ) );

				if( $PSID == 'P_'.$row[3] )
				{
					error_log('psid : same '.$PSID .' : '. $row[3]);
					//SLOG( sprintf( '[GET_ARS_SCN %s:%s] psid : same '.$PSID .', $DID, $CID, $row[3] ) );
					SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : same %s : %s', $DID, $CID, $PSID, $row[3] ) );
				}
				else
				{
					error_log('psid : diff '.$PSID .' : '. $row[3]);
					//SLOG( sprintf( '[GET_ARS_SCN %s:%s] psid : diff '.$PSID .', $DID, $CID, $row[3] ) );
					SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : same %s : %s', $DID, $CID, $PSID, $row[3] ) );
					$PSID = 'P_'.$row[3];
					$JL_2 = new stdclass();
				}
			}
			else
			{
				error_log('level : diff '.$LEVEL .' : '. $row[0]);
				//SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : diff '.$LEVEL .', $DID, $CID, $row[0] ) );
				SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : same %s : %s', $DID, $CID, $LEVEL, $row[0] ) );
				$LEVEL = 'L_'.$row[0];
				$JL_1 = new stdclass();

				if( $PSID == 'P_'.$row[3] )
				{
					error_log('psid : same '.$PSID .' : '. $row[3]);
					//SLOG( sprintf( '[GET_ARS_SCN %s:%s] psid : same '.$PSID .', $DID, $CID, $row[3] ) );
					SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : same %s : %s', $DID, $CID, $PSID, $row[3] ) );
				}
				else
				{
					error_log('psid : diff '.$PSID .' : '. $row[3]);
					//SLOG( sprintf( '[GET_ARS_SCN %s:%s] psid : diff '.$PSID .', $DID, $CID, $row[3] ) );
					SLOG( sprintf( '[GET_ARS_SCN %s:%s] level : same %s : %s', $DID, $CID, $PSID, $row[3] ) );
					$PSID = 'P_'.$row[3];
					$JL_2 = new stdclass();
				}
			}

			$DTMF           = 'D_'.$row[1];

			$JL_3 = new stdclass();

			$JL_3->s_id 		= $row[2];
			$JL_3->s_pid 		= $row[3];
			$JL_3->s_action 	= $row[4];
			$JL_3->s_next_level 	= $row[5];
			$JL_3->s_next_dtmf_len 	= $row[6];
			$JL_3->s_ment 		= $row[7];


			$JL_2->$DTMF = $JL_3;
		
			$JL_1->$PSID = $JL_2;

			$RESULT->SCN1->$LEVEL 	= $JL_1;

			$SID = 'S_'.$row[2];

			$S_VAL = new stdclass();
			$S_VAL->s_pid 		= $row[3];

			$RESULT->SCN2->$SID = $S_VAL;

		}

		// PHP 8.2 Fix: Use ternary operator for mysqli_num_rows
		$count = $res ? mysqli_num_rows($res) : 0;

		//error_log($RESULT );

		// PHP 8.2 Fix: Add connection validation for mysqli_close
		if ($conn) {
			mysqli_close($conn);
		}

		SLOG( sprintf( '[GET_ARS_SCN %s:%s] END   =======================================================================================================', $DID, $CID ) );

		// 데이터 출력후 statement 를 해제한다


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

	error_log(sprintf('getArsScn.php [%s]', print_r($args, true)));

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

		error_log(sprintf('call getArsScn.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		SLOG( sprintf( '[GET_ARS_SCN CALL] getArsScn.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_ARS_SCENARIO') 
				{
					$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_ars_scenario( $JSON_REQUEST->COMP_ID,
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
