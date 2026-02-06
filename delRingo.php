<?php

include 'plog.php';

	function delRingo( $MASTER_ID, $FILE ) 
	{ 
		WLOG( sprintf( '[DEL_RING %s] START =======================================================================================================', $MASTER_ID ) );
		
		$RESULT = "SUCCESS:Insert\n\n";

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
                  	'logisoft123',
                  	'asterisk',
                  	'3306');

                $conn_db = mysqli_connect(
                  '121.254.239.50',
                  'nautes',
                  'Nautes12@$',
                  'LOGI',
                  '3306');

		$DIR = '/var/lib/asterisk/moh/'.$FILE;

		if (!file_exists($DIR)) 
		{
			$RESULT = "FAIL:NotExistDir\n\n";
			return $RESULT; // 디렉토리가 존재하지 않음
		}

/**
		if (is_file($DIR) || is_link($DIR)) 
		{
			return unlink($DIR); // 파일 또는 심볼릭 링크 삭제
		}
**/

		$items = array_diff(scandir($DIR), ['.', '..']);

		foreach ($items as $item) 
		{
			$path = $DIR . DIRECTORY_SEPARATOR . $item;
			if (is_dir($path)) 
			{
				removeDirectory($path); // 서브디렉토리 삭제
				WLOG( sprintf( '[DEL_RING %s] %s sub dir delete', $MASTER_ID, $path ) );
			} 
			else 
			{
				unlink($path); // 파일 삭제
				WLOG( sprintf( '[DEL_RING %s] %s delete', $MASTER_ID, $path ) );
			}
		}

		$bRet = rmdir($DIR);
		if( $bRet ) 
		{
			WLOG( sprintf( '[DEL_RING %s] %s delete', $MASTER_ID, $DIR ) );
		}
		else
		{
			$RESULT = "FAIL:DonotDelDir\n\n";
			return $RESULT; // 디렉토리가 존재하지 않음
		}


                $sql = "";

		$FIND_MUSIC	= 'N';	

		//$sql = "select category from music where category='$FILE';";
		$sql = "delete from music where category='$FILE';";

		error_log($sql);
		WLOG( sprintf( '[DEL_RING %s] %s', $MASTER_ID, $sql ) );

		$res = mysqli_query($conn, $sql);

		if( $row = mysqli_fetch_array($res) )
		{
			$FIND_MUSIC	= 'Y';	
			WLOG( sprintf( '[FID_RING %s] Find %s Aleady existence', $MASTER_ID, $row[0] ) );
		}


		mysqli_close ( $conn );
		mysqli_close ( $conn_db );

		WLOG( sprintf( '[DEL_RING %s] END   =======================================================================================================', $MASTER_ID ) );

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();

	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	error_log(sprintf('insKeyNum.php [%s]', print_r($args, true)));

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('call delRingo.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		WLOG( sprintf( '[DEL_RING CALL] addRingo.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'DEL_RINGO') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE    = delRingo( $JSON_REQUEST->MASTER_ID,
												$JSON_REQUEST->FILE);
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
