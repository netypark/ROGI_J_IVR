<?php

	function ORACLE_SAVE_CALLBACK($CUST_PHONE_NO) {
		$conn = oci_connect('CTI', 'acti20130908', '165.244.255.134:3001/EMNGSFSP', 'UTF8');
		//$query = "SELECT to_char(sysdate, 'YYYY-MM-DD HH24:MI:SS') AS Now FROM dual";

		$query = 'BEGIN GSFS.CN_CTI_CALLBACK (:SVC_NAME, :PHONE_NO, :RESULT); END;';
		$stid = oci_parse($conn, $query) or die('oci parse error: '.oci_error($conn));
		
		oci_bind_by_name($stid,':SVC_NAME',$SVC_NAME,32);
		oci_bind_by_name($stid,':PHONE_NO',$PHONE_NO,32);
		oci_bind_by_name($stid,':RESULT',$ORACLE_RESULT,32);

		// Assign a value to the input
		$SVC_NAME = 'LGELF';
		$PHONE_NO = $CUST_PHONE_NO;
		$ORACLE_RESULT = '';
		
		if(oci_execute($stid) === false) {
			$RESULT = print_r(oci_error($stid), true);
//			die("oci query error [ $query ] message : " . print_r(oci_error($stid), true));
		} else {
			if ($ORACLE_RESULT == 1) {
				$RESULT = "ORACLE_SAVE_CALLBACK => SUCCESS";
			} else {
				$RESULT = $ORACLE_RESULT;
			}
		}
//		oci_fetch_all($stid, $arr, null, null, OCI_FETCHSTATEMENT_BY_ROW);
		oci_close($conn);
		
		return $RESULT;
	}
	
	
	$RAW_POST_DATA = file_get_contents("php://input");
	
	$args = new stdClass();
	if (strlen($RAW_POST_DATA) > 0) {
		$args->JSON_REQUEST = $RAW_POST_DATA;
	} else {
		$args = json_decode(json_encode($_REQUEST), FALSE);
	}
	
	error_log(sprintf('SAVE_CALLBACK.php [%s]', print_r($args, true)));

	
	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST		= null;
	$JSON_API_RESULT->JSON_RESULT		= new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE		= 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE	= "OK";
	
	if (isset($args->JSON_REQUEST)) {

		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) {
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

error_log(sprintf('SAVE_CALLBACK.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		
		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'DUMMY') {
				} else if ($JSON_REQUEST->REQ == 'SAVE_CALLBACK') {
					if (isset($JSON_REQUEST->CUST_PHONE_NO)) {
						$JSON_API_RESULT->JSON_RESULT->CODE			= "TRY_CALL_PROCEDURE";
						$JSON_API_RESULT->JSON_RESULT->MESSAGE		= ORACLE_SAVE_CALLBACK($JSON_REQUEST->CUST_PHONE_NO);
					} else {
						$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
						$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ATTRIBUTE CUST_PHONE_NO REQUIRED!";
					}
				} else {
					$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
					$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "WE DON'T KNOW HOW TO PROCESS $JSON_REQUEST->REQ ";
				}
			} else {
				$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
				$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ATTRIBUTE REQ REQUIRED!";
			}
		} else {
			$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
			$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ARGUMENT JSON_REQUEST IS NOT VALID STRING OF JSON OBJECT";
		}
	} else {
		$JSON_API_RESULT->JSON_RESULT->CODE			= "ERROR";
		$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ARGUMENT JSON_REQUEST NOT DEFINED";
	}

//	error_log(sprintf('api.php RESULT : [%s]', print_r($JSON_API_RESULT, true)));

	if (isset($JSON_API_RESULT->OUT_TYPE) && ($JSON_API_RESULT->OUT_TYPE == 'FILE')) {
		if (isset($JSON_API_RESULT->FILE_PATH)) {
			$filepath	= $JSON_API_RESULT->FILE_PATH;
			$filesize	= filesize($filepath);
			$path_parts	= pathinfo($filepath);
			$filename	= $path_parts['basename'];
			$extension	= $path_parts['extension'];
			 
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