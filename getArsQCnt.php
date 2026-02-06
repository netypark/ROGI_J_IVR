<?php

	function get_ars_count( $DID ) 
	{ 

		//$RESULT = "0";

		//$table = "T_ARS_CONFIG";

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

		$sql = "select count(ID) from T_XSI_CALL_ONLINE where SUBSTRING_INDEX(xsi_userId,'@',1)='$DID' and xsi_eventType!='xsi:CallReleasedEvent' and ( xsi_transferNum is null or xsi_transferNum='' );";

		error_log($sql);

		$RESULT = new stdClass();

		$res = mysqli_query($conn, $sql);

		if( $row = mysqli_fetch_array($res) ) 
		{
			error_log('Get ARS count :' );
			error_log($row[0]);
			$RESULT->ars_cnt		= $row[0];
		}

		//error_log($RESULT );

		mysqli_close ( $conn );

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

	error_log(sprintf('getArsQCnt.php [%s]', print_r($args, true)));


	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	error_log('+++++++++++++getArsQCnt.php args->JSON_REQUEST ');

	if (isset($args->JSON_REQUEST)) 
	{
		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) 
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('+++++++++++++getArsQCnt.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_ARS_COUNT') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_ars_count( $JSON_REQUEST->DID );
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
