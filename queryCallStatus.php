<?php
/**
 * Modification History:
 * Date: 2026-01-20
 * - Updated for PHP 8.2 compatibility
 * - Changed DB connection IP from 121.254.239.50 to 121.254.239.50
 * - Reference backup: queryCallStatus.php.backup_20260120_152951
 */

include 'plog.php';

	function update_callStatus( $QRY_TYPE, $COMPANY_ID, $COMPANY_DID, $TR_COMPANY_ID, $TR_COMPANY_DID, $CALLER, $CALLED, $ANSWER_TIME, $CALL_ID, $VIP ) 
	{ 

		SLOG( sprintf( '[UP_CALL_STA %s:%s] START ===================================================================================================================', $CALLER, $CALLED ) );
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

		if( $QRY_TYPE == 'UPDATE_R' )
		{
			if( $VIP == 'V' )
			{
				$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', called='$CALLED', ring_time=now(), call_state='R' where call_id='$CALL_ID';update T_Q_GROUP set phone_vip_cnt=phone_vip_cnt+1 where q_company_id='$COMPANY_ID' and q_group_num='$CALLED';";
			}
			else
			{
				$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', called='$CALLED', ring_time=now(), call_state='R' where call_id='$CALL_ID';";
			}
		}
		else if( $QRY_TYPE == 'UPDATE_C' )
		{
			$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', answer_time='$ANSWER_TIME', call_state='C', called='$CALLED' where call_id='$CALL_ID';";
		}
		else 
		{
			if( empty($ANSWER_TIME))
			{
				if( $VIP == 'V' )
				{
					$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', end_time=now(), call_state='E', called='$CALLED', phone_hold_time=TIMESTAMPDIFF(SECOND, ring_time, now()) where call_id='$CALL_ID';update T_Q_GROUP set phone_vip_cnt=phone_vip_cnt-1 where q_company_id='$COMPANY_ID' and q_group_num='$CALLED';";
				}
				else
				{
					$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', end_time=now(), call_state='E', called='$CALLED', phone_hold_time=TIMESTAMPDIFF(SECOND, ring_time, now()) where call_id='$CALL_ID';";
				}
			}
			else 
			{
				if( $VIP == 'V' )
				{
					$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', end_time=now(), call_state='E', called='$CALLED', phone_hold_time=TIMESTAMPDIFF(SECOND, ring_time, answer_time) where call_id='$CALL_ID';update T_Q_GROUP set phone_vip_cnt=phone_vip_cnt-1 where q_company_id='$COMPANY_ID' and q_group_num='$CALLED';";
				}
				else
				{
					$sql = "update T_CALL_STATE set transfer_company_id='$TR_COMPANY_ID', transfer_company_did='$TR_COMPANY_DID', end_time=now(), call_state='E', called='$CALLED', phone_hold_time=TIMESTAMPDIFF(SECOND, ring_time, answer_time) where call_id='$CALL_ID';";
				}
			}
		}

		error_log($sql);

		$res = mysqli_multi_query($conn, $sql);

		//$count= mysqli_num_rows($res) ;
		SLOG( sprintf( '[UP_CALL_STA %s:%s] %s', $CALLER, $CALLED, $sql ) );

		mysqli_close ( $conn );

		SLOG( sprintf( '[UP_CALL_STA %s:%s] END   ===================================================================================================================', $CALLER, $CALLED ) );
		
		//if( $count == '1' )
			$RESULT='200 OK';
	//	else
	//		$RESULT='NOT OK';

		return $RESULT;
	}


	$RAW_POST_DATA = file_get_contents("php://input");

	// PHP 8.2 Fix: Ensure stdClass properties are properly initialized
	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0)
	{
			$args->JSON_REQUEST = $RAW_POST_DATA;
	}
	else
	{
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	error_log(sprintf('updateCallOnLine.php [%s]', print_r($args, true)));

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	error_log('+++++++++++++updateCallStatus.php args->JSON_REQUEST ');

	// PHP 8.2 Fix: Added null coalescing for safer property access
	if (isset($args->JSON_REQUEST))
	{

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST))
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('+++++++++++++updateCallStatus.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		// PHP 8.2 Fix: Ensure proper object type checking and property access
		if (is_object($JSON_REQUEST))
		{

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ))
			{
				if ($JSON_REQUEST->REQ == 'CALL_STATUS')
				{
					$JSON_API_RESULT->JSON_RESULT->CODE			= "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE      = update_callStatus	(
													$JSON_REQUEST->QRY_TYPE ?? '',
													$JSON_REQUEST->COMPANY_ID ?? '',
													$JSON_REQUEST->COMPANY_DID ?? '',
													$JSON_REQUEST->TR_COMPANY_ID ?? '',
													$JSON_REQUEST->TR_COMPANY_DID ?? '',
													$JSON_REQUEST->CALLER ?? '',
													$JSON_REQUEST->CALLED ?? '',
													$JSON_REQUEST->ANSWER_TIME ?? '',
													$JSON_REQUEST->CALL_ID ?? '',
													$JSON_REQUEST->VIP ?? ''
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
