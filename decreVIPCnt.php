<?php
/**
 * Modification History
 * --------------------
 * Date: 2026-01-20
 * Reference backup: decreVIPCnt.php.backup_20260120_152951
 * Changes: PHP 8.2 compatibility fixes
 *          - Updated DB connection IP to 121.254.239.50
 *          - Fixed dynamic properties usage
 */

include 'plog.php';

	function decrement_vip_cnt( $DID, $CID, $MASTER_ID, $COMPANY_ID ) 
	{ 
		SLOG( sprintf( '[DEC_VIPCT %s:%s] START =======================================================================================================', $DID, $CID ) );

		$RESULT = new stdClass();

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

                $sql = "";


		$sql = "UPDATE T_VIP_COUNT SET vip_cnt = GREATEST(vip_cnt - 1, 0) WHERE company_id = $COMPANY_ID and master_id = $MASTER_ID;update T_COMPANY set monitering_vip_info='' where company_id=$COMPANY_ID and master_id=$MASTER_ID and company_level=2;";

		
		error_log($sql);
		SLOG( sprintf( '[UPDATE_VIP %s:%s] %s', $DID, $CID, $sql ) );

		$res = mysqli_multi_query($conn, $sql);

		mysqli_close ( $conn );

		SLOG( sprintf( '[DEC_VIPCT %s:%s] END  ==============================================================================================', $DID, $CID ) );
		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	// PHP 8.2 Fix: Initialize stdClass properly to avoid dynamic property warnings
	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
			if (!is_object($args)) {
				$args = new stdClass();
			}
	}

	//error_log(sprintf('updateMonTbl.php [%s]', print_r($args, true)));

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

//error_log('+++++++++++++updateMonTbl.php args->JSON_REQUEST ');
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

//error_log(sprintf('+++++++++++++updateMonTbl.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'DEC_VIP_CNT') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = decrement_vip_cnt( 	$JSON_REQUEST->DID ,
														$JSON_REQUEST->CID ,
														$JSON_REQUEST->MASTER_ID,
														$JSON_REQUEST->COMPANY_ID );
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
