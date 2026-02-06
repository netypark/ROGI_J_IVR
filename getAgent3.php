<?php

	function get_agent( $COMP_ID, $ARS_ID, $SCN_ID, $ROUTING ) 
	{ 

		$RESULT = "0";

		$table = "t_agent";

		$conn = mysqli_connect(
                  '127.0.0.1',
                  'openapi',
                  '@Open123@',
                  'OPENAPI',
		  '3306');

		$sql = "";

		$sql = "select b.PHONE_ID, c.IP_PHONE, b.CALL_ORDER, a.LATEST_CALL_PID from T_GROUP_ARS a inner join T_GROUP_USER b inner join T_PHONE c on a.GROUP_ID = b.GROUP_ID and b.PHONE_ID = c.PHONE_ID where a.ARS_ID=$ARS_ID and a.COMP_ID=$COMP_ID and a.SCN_ID=$SCN_ID order by b.CALL_ORDER;";

		error_log($sql);

		$res = mysqli_query($conn, $sql);

		$nIdx=0;
		$PHONE_ID[] = new stdClass();
		$IP_PHONE[] = new stdClass();
		$CALL_ORDER[] = new stdClass();
		$LASTPID=0;

		while( $row = mysqli_fetch_array($res) ) 
		{
			error_log($row[0]);
			error_log($row[1]);
			error_log($row[2]);
			error_log($row[3]);

			$PHONE_ID[$nIdx]   = $row[0];
			$IP_PHONE[$nIdx]   = $row[1];
			$CALL_ORDER[$nIdx++] = $row[2];
			$LAST_PID	    = $row[3];
		}

		$count= mysqli_num_rows($res) ;

		mysqli_close ( $conn );

		//error_log('++++++'.$LAST_PID );

		for( $i=0; $i<$nIdx; $i++ )
		{
			if( $PHONE_ID[$i] == $LAST_PID )
				break;
		}	
		//error_log('======'.$CALL_ORDER[$i] );
		$LAST_NUM = $CALL_ORDER[$i];

		$ORDER = ($LAST_NUM-1)%$nIdx;

		$STR_NUM="";
		for( $i=0; $i<$nIdx; $i++ )
		{
			$j=(($ORDER+$CALL_ORDER[$i])%$nIdx)+1;
			$IDX=$j-1;
			$STR_NUM = $STR_NUM."'".$IP_PHONE[$IDX]."'".",";
		}

		$NEW_STR = rtrim($STR_NUM, ", ");

		$conn = mysqli_connect(
                  '210.120.112.61',
                  'OPENAPI_XSI',
                  'OPENAPI_XSI',
                  'OPENAPI_XSI',
                  '4306');

		$sql = "select PHONE_NO from T_PHONE_LIST where HOOK_STATUS = 'On-Hook' and PHONE_NO in ($NEW_STR) order by field( PHONE_NO, $NEW_STR ) limit 1;";
		error_log($sql);

		$res = mysqli_query($conn, $sql);

		$row = mysqli_fetch_array($res);

		$RESULT = new stdClass();

                $RESULT->PHONE		= $row[0];

		mysqli_close ( $conn );

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	error_log(sprintf('updateMonTbl.php [%s]', print_r($args, true)));


	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

error_log('+++++++++++++updateMonTbl.php args->JSON_REQUEST ');
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

error_log(sprintf('+++++++++++++updateMonTbl.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'GET_AGENT') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_agent( 
																				$JSON_REQUEST->COMP_ID, 
																				$JSON_REQUEST->ARS_ID ,
																				$JSON_REQUEST->SCN_ID ,
																				$JSON_REQUEST->ROUTING );
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
