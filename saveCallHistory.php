<?php
/**
 * Modification History
 * --------------------
 * Date: 2026-01-20
 * Reference backup: saveCallHistory.php.backup_20260120_152951
 * Changes: Updated for PHP 8.2 compatibility
 *          - Changed DB connection IP from 121.254.239.50 to 121.254.239.50
 *          - Added proper error checking for mysqli connections
 *          - Fixed dynamic property usage
 */

include 'plog.php';

	function save_callHistory( $COMPANY_ID, $COMPANY_DID, $CALLER, $CALLED, $CALL_DIRECTION, $SVC_TYPE,
				$START_TIME, $RING_TIME, $ANSWER_TIME, $END_TIME, $CALL_ID, $CALL_RESULT, $MASTER_ID, $C_LEVEL, $DEC_VIP,
				$ORIGINAL_LINKEDID = '' )
	{ 

                // PHP 8.2 Fix: Updated IP address to 121.254.239.50
                $conn = mysqli_connect(
                  '121.254.239.50',
                  'nautes',
                  'Nautes12@$',
                  'LOGI',
                  '3306');

                // PHP 8.2 Fix: Check connection error
                if (!$conn) {
                    error_log("Database connection failed: " . mysqli_connect_error());
                    return "Connection Error";
                }

/**
                $conn = mysqli_connect(
                  '118.67.142.108',
                  'root',
                  'Nautes12@$',
                  'LOGI',
                  '13306');

**/
		$RESULT = "Not OK";

		$YYYY = substr($START_TIME, 0, 4);
		$MM = substr($START_TIME, 5, 2);

		$TABLE = 'T_SVCC_HISTORY_'.$YYYY.$MM;
	
		if( $SVC_TYPE != '0' )
		{

			SLOG( sprintf( '[SAVE_CALLHS %s:%s] START ===================================================================================================================', $COMPANY_DID, $CALLER ) );

			// 2026-02-02: original_linkedid 컬럼 추가 (크로스콜 시 원본 통화 연결용)
			$columns = "company_id, company_did, caller, called, call_direction, svc_type, start_time, ring_time, answer_time, end_time, call_id, call_result, phone_hold_time, master_id";
			$values = "$COMPANY_ID, '$COMPANY_DID', '$CALLER', '$CALLED', '$CALL_DIRECTION', '$SVC_TYPE', '$START_TIME', '$RING_TIME', '$ANSWER_TIME', '$END_TIME', '$CALL_ID', '$CALL_RESULT', TIMESTAMPDIFF(SECOND, '$RING_TIME', '$ANSWER_TIME'), $MASTER_ID";
			if (!empty($ORIGINAL_LINKEDID)) {
				$columns .= ", original_linkedid";
				$values .= ", '$ORIGINAL_LINKEDID'";
			}
			$sql = "insert into $TABLE ( $columns ) values ( $values );";

			error_log($sql);
			SLOG( sprintf( '[SAVE_CALLHS %s:%s] %s', $COMPANY_DID, $CALLER, $sql ) );

			$res = mysqli_query($conn, $sql);

			if( $C_LEVEL == 'V' && $DEC_VIP == 'N' )
			{
				$sql = "UPDATE T_VIP_COUNT SET vip_cnt = GREATEST(vip_cnt - 1, 0) WHERE company_id = $COMPANY_ID and master_id = $MASTER_ID;update T_COMPANY set monitering_vip_info='' where company_id=$COMPANY_ID and master_id=$MASTER_ID and company_level=2;";

				error_log($sql);
				SLOG( sprintf( '[SAVE_CALLHS %s:%s] %s', $COMPANY_DID, $CALLER, $sql ) );

				$res = mysqli_multi_query($conn, $sql);
			}

			//$count= mysqli_num_rows($res) ;
			$RESULT = "200 OK";

			SLOG( sprintf( '[SAVE_CALLHS %s:%s] END   ===================================================================================================================', $COMPANY_DID, $CALLER ) );

		}

		mysqli_close ( $conn );


		// 데이터 출력후 statement 를 해제한다

		return $RESULT;
	}


	$RAW_POST_DATA = file_get_contents("php://input");

	// PHP 8.2 Fix: Initialize stdClass properly to avoid dynamic property warnings
	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0)
	{
			$args->JSON_REQUEST = $RAW_POST_DATA;
	}
	else
	{
			// PHP 8.2 Fix: Use FALSE constant (already correct, but ensuring compatibility)
			$args = json_decode(json_encode($_REQUEST), false);
	}

	error_log(sprintf('save_callHistory.php [%s]', print_r($args, true)));

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	//error_log('+++++++++++++save_callHistory.php args->JSON_REQUEST ');

	if (isset($args->JSON_REQUEST)) 
	{

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) 
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		//error_log(sprintf('+++++++++++++save_callHistory.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) 
		{

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'SAVE_CALL') 
				{
					$JSON_API_RESULT->JSON_RESULT->CODE			= "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE      = save_callHistory(	
													$JSON_REQUEST->COMPANY_ID,
													$JSON_REQUEST->COMPANY_DID,
													$JSON_REQUEST->CALLER,
													$JSON_REQUEST->CALLED,
													$JSON_REQUEST->CALL_DIRECTION,
													$JSON_REQUEST->SVC_TYPE,
													$JSON_REQUEST->START_TIME,
													$JSON_REQUEST->RING_TIME,
													$JSON_REQUEST->ANSWER_TIME,
													$JSON_REQUEST->END_TIME,
													$JSON_REQUEST->CALL_ID,
													$JSON_REQUEST->CALL_RESULT,
													$JSON_REQUEST->MASTER_ID,
													$JSON_REQUEST->C_LEVEL,
													$JSON_REQUEST->DEC_VIP,
													$JSON_REQUEST->ORIGINAL_LINKEDID ?? ''
												   );
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
