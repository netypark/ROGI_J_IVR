<?php

	function get_key_stat( $TRUNK ) 
	{ 

		$RESULT = "SUCCESS";

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
                  	'mycat123',
                  	'asterisk',
                  	'3306');

                $sql = "";

		$PDATA = json_encode( $TRUNK );
		//error_log($DATA);

		$DATA = json_decode($PDATA, true);
		//$DATA = $TRUNK;

		//error_log( print_r( $DATA, true ) );

		//$VAL = array_values( $DATA );

		//error_log( sprintf( '%s', print_r( $VAL, true ) ) );

		$sql_s = "";
		foreach ($DATA as $key=> $DATA1) 
		{
		    //error_log( $key. ' : ' );
		    //error_log( 'DATA:'. $DATA['NUM']  );
		    //error_log( sprintf('DATA:%s', print_r( $DATA1 ,true) ) );
		    $VAL = print_r( $DATA1, true );

		    //$VAL2 = strstr( $VAL, "=>" );
		    sscanf( $VAL, "%s %s %s %s %s ", $TEMP1, $TEMP2, $TEMP3, $TEMP4, $TR_NUM );
		    //error_log( $TR_NUM );
		    //$sql_s = $sql_s.'\''.$TR_NUM.'\', ';

		    $sql .= "select case when disabled='off' then 'on' else 'off' end as state from trunks where channelid = '$TR_NUM';";
		}

	
		//$TMP_STR = rtrim($sql_s, ", ");
	        //error_log( $TMP_STR );

		//$sql = "select disabled from trunks where channelid in ( $TMP_STR ) order by field( channelid, $TMP_STR );";
		error_log( $sql );


		$resstr = "";
		mysqli_multi_query($conn, $sql);
		do {
		    /* store the result set in PHP */
		    if ($result = mysqli_store_result($conn)) {
			if ($row = mysqli_fetch_row($result)) {
			    error_log($row[0]);
			    $resstr .= $row[0] .':';
			}
			else
			{
			    $resstr .= 'null:';
			}
		    }

		    /* print divider */
		/**
		    if (mysqli_more_results($conn)) {
			error_log('-----------------');
		    }
		**/

		} while (mysqli_next_result($conn));

		$TEMP_STR = rtrim($resstr, ":");
		error_log($TEMP_STR);

		$RESULT = $TEMP_STR;

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();

	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	//error_log(sprintf('updateMonTbl.php [%s]', print_r($args, true)));


	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE             = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	//error_log('+++++++++++++make_directory.php args->JSON_REQUEST ');
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('call insKeyNum.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'GET_TRK') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE    = get_key_stat( $JSON_REQUEST->TRUNK );
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
