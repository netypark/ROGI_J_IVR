<?php
/**
 * Modification History:
 * Date: 2026-01-20
 * Description: Fixed for PHP 8.2 compatibility
 * Reference backup: checkDidRange.php.backup_20260120_152951
 */

include 'plog.php';

	function check_did( $CHECK_DID )
	{
		// PHP 8.2 Fix: Initialize variables before use
		$DID = $CHECK_DID ?? 'UNKNOWN';
		$CID = '';

		SLOG( sprintf( '[CHECK_DID %s:%s] START =======================================================================================================', $DID, $CID ) );

		$RESULT = new stdClass();

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

/****
		$time1 = strtotime('2024-06-26 08:31:00');
		$time2 = strtotime('2024-06-26 10:30:00');

		$diffInSeconds = $time2 - $time1;

		// 시간, 분, 초 단위로 변환
		$hours = floor($diffInSeconds / 3600);
		$minutes = floor(($diffInSeconds % 3600) / 60);
		$seconds = $diffInSeconds % 60;

		SLOG( sprintf( '[GET_AGENT %s:%s] %d', $DID, $CID, $diffInSeconds  ) );

***/

		// PHP 8.2 Fix: Use prepared statements to prevent SQL injection
		$sql = "select did_number from T_DID_RANGE where did_number=? limit 1;";

		error_log($sql);
		// PHP 8.2 Fix: 바인딩 파라미터 값을 SQL에 직접 표시
		$sql_log = str_replace('?', "'".$CHECK_DID."'", $sql);
		SLOG( sprintf( '[CHECK_DID %s:%s] %s', $DID, $CID, $sql_log ) );

		$stmt = mysqli_prepare($conn, $sql);
		mysqli_stmt_bind_param($stmt, 's', $CHECK_DID);
		mysqli_stmt_execute($stmt);
		$res = mysqli_stmt_get_result($stmt);

		$RESULT->IS_PREFIX	= 'N';

		if( $row = mysqli_fetch_array($res) )
		{
			error_log($row[0]);
			SLOG( sprintf( '[CHECK_DID %s:%s] Find %s', $DID, $CID, $CHECK_DID ) );
			$RESULT->IS_PREFIX	= 'Y';
		}

		// PHP 8.2 Fix: Close statement before closing connection
		mysqli_stmt_close($stmt);
		mysqli_close ( $conn );

		SLOG( sprintf( '[CHECK_DID %s:%s] END  ==============================================================================================', $DID, $CID ) );

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	// PHP 8.2 Fix: Ensure print_r returns string properly
	error_log('checkDidRange.php [' . print_r($args, true) . ']');

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	error_log('+++++++++++++checkDidRange.php args->JSON_REQUEST ');
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

//error_log(sprintf('+++++++++++++updateMonTbl.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'CHECK_DID') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = check_did( $JSON_REQUEST->CHECK_DID );
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
