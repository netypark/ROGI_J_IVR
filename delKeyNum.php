<?php

include 'plog.php';

	function del_keynum( $TR_NUM ) 
	{ 
		WLOG( sprintf( '[DEL_KEYNUM %s] START =======================================================================================================', $TR_NUM ) );

		$RESULT = "SUCCESS";

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
                  	'mycat123',
                  	'asterisk',
                  	'3306');

                $sql = "";

		$sql = "select trunkid from trunks where channelid='$TR_NUM';";

		error_log($sql);
		WLOG( sprintf( '[DEL_KEYNUM %s] %s', $TR_NUM, $sql ) );

                $res = mysqli_query($conn, $sql);

		if( $row = mysqli_fetch_array($res) )
		{
			$TR_ID = $row[0];
			error_log( sprintf('KEY [%s] trunk_id [%s] delete..!', $TR_NUM, $TR_ID) );
			WLOG( sprintf( '[DEL_KEYNUM %s] trunk_id [%s] delete..!', $TR_NUM, $TR_ID ) );

			// case : Cisco-Centrex 

			$sql = "delete from trunks where trunkid=$TR_ID;delete from pjsip where id=$TR_ID;delete from trunks_reg_side where trunkid = '$TR_ID';delete from arsauth_call_in_scenario where CALL_TO='$TR_NUM';";

			error_log($sql);
			WLOG( sprintf( '[DEL_KEYNUM %s] %s', $TR_NUM, $sql ) );

			$res = mysqli_multi_query($conn, $sql);

/***
			// case : C-Centrex 

			$sql = "select route_id from outbound_route_trunks where trunk_id='$TR_ID';";

			error_log($sql);
			WLOG( sprintf( '[DEL_KEYNUM %s] %s', $TR_NUM, $sql ) );

			$res = mysqli_query($conn, $sql);
			if( $row = mysqli_fetch_array($res) )
			{
				$ROUTE_ID = $row[0];
				error_log( sprintf('route id [%s] delete..!', $ROUTE_ID) );
				WLOG( sprintf( '[DEL_KEYNUM %s] route id [%s] delete..!', $TR_NUM, $ROUTE_ID ) );

				$sql = "delete from outbound_routes where route_id=$ROUTE_ID;delete from outbound_route_patterns where route_id=$ROUTE_ID;delete from outbound_route_trunks where route_id=$ROUTE_ID;delete from outbound_route_sequence where route_id=$ROUTE_ID;delete from trunks where trunkid=$TR_ID;delete from pjsip where id=$TR_ID;delete from trunks_reg_side where trunkid = '$TR_ID';delete from arsauth_call_in_scenario where CALL_TO='$TR_NUM';";

				error_log($sql);
				WLOG( sprintf( '[DEL_KEYNUM %s] %s', $TR_NUM, $sql ) );

				$res = mysqli_multi_query($conn, $sql);
			}
***/

			system( "fwconsole reload", $result );
			WLOG( sprintf( '[DEL_KEYNUM %s] fwconsole reload', $TR_NUM ) );
		}
		else
		{
			$RESULT = "FAIL";
			WLOG( sprintf( '[DEL_KEYNUM %s] not found', $TR_NUM ) );
		}

		mysqli_close ( $conn );

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
                  	'mycat123',
                  	'm_ssbc',
                  	'3306');

                $sql = "";

		$sql = "delete from ROUTE2 where r_src='$TR_NUM';update modified_table set ROUTE2=1;";

		error_log($sql);
		WLOG( sprintf( '[DEL_KEYNUM m_ssbc %s] %s', $TR_NUM, $sql ) );

		$res = mysqli_multi_query($conn, $sql);

		mysqli_close ( $conn );

		WLOG( sprintf( '[DEL_KEYNUM %s] END   =======================================================================================================', $TR_NUM ) );
		//error_log($RESULT );

		return $RESULT;
	}

	$RAW_POST_DATA = file_get_contents("php://input");

	$args = new stdClass();

	if (strlen($RAW_POST_DATA) > 0) {
			$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
			$args = json_decode(json_encode($_REQUEST), FALSE);
	}

	error_log(sprintf('del_keynum.php [%s]', print_r($args, true)));


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

		error_log(sprintf('call del_keynum.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		WLOG( sprintf( '[DEL_KEYNUM %s] call del_keynum.php args->JSON_REQUEST [%s]', $TR_NUM, $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'DEL_TRK') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE    = del_keynum( $JSON_REQUEST->TR_NUM );
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
