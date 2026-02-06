<?php

	function VERIFICATION_CHECK($CUST_PHONE_NO, $ENTER_CODE) {

		$RESULT ='N';

               $serverName = "64.147.0.36";

                // DB커넥션 연결
                $dbconn = mssql_connect($serverName, "usaerp", "usaerp");

                $status = mssql_select_db("usaerp",$dbconn);

                if(!$status)  {
                        $errno =mssql_errno($dbconn);
                        echo (" error : $errno ");
                }


		$query = "Select TOP 1 replace(ltrim(rtrim(b.Tel)), '-', '') as value From TDANSSAreaArr_USA  a left outer join TRNSSApplication_KDP b on a.CustCd = b.CustCd Where a.ZipCd = '$ENTER_CODE' and b.Tel != '$CUST_PHONE_NO'";

                $stmt = mssql_query( $query);

                // statement 를 돌면서 필드값을 가져온다


                if( $row = mssql_fetch_row($stmt)   )
                {
			$RESULT ='Y';
                }
                mssql_close( $dbconn );


		// 데이터 출력후 statement 를 해제한다


		return $RESULT;
	}
	
       $RAW_POST_DATA = file_get_contents("php://input");

        $args = new stdClass();
        if (strlen($RAW_POST_DATA) > 0) {
                $args->JSON_REQUEST = $RAW_POST_DATA;
        } else {
                $args = json_decode(json_encode($_REQUEST), FALSE);
        }

        error_log(sprintf('VERIFICATION_CHECK.php [%s]', print_r($args, true)));


        $JSON_API_RESULT = new stdClass();

        $JSON_API_RESULT->JSON_REQUEST          = null;
        $JSON_API_RESULT->JSON_RESULT           = new stdClass();

        $JSON_API_RESULT->JSON_RESULT->CODE             = 200;
        $JSON_API_RESULT->JSON_RESULT->MESSAGE  = "OK";

        if (isset($args->JSON_REQUEST)) {

                $JSON_REQUEST = json_decode($args->JSON_REQUEST);
                if (!is_object($JSON_REQUEST)) {
                        $JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
                }

error_log(sprintf('VERIFICATION_CHECK.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

                if (is_object($JSON_REQUEST)) {

                        $JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
                        if (isset($JSON_REQUEST->REQ)) {
                                if ($JSON_REQUEST->REQ == 'DUMMY') {
                                } else if ($JSON_REQUEST->REQ == 'VERIFICATION_CHECK') {
                                        if (isset($JSON_REQUEST->CUST_PHONE_NO)) {
                                                $JSON_API_RESULT->JSON_RESULT->CODE                     = "TRY_CALL_PROCEDURE";
                                                $JSON_API_RESULT->JSON_RESULT->MESSAGE          = VERIFICATION_CHECK($JSON_REQUEST->CUST_PHONE_NO,$JSON_REQUEST->ENTER_CODE);
                                        } else {
                                                $JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
                                                $JSON_API_RESULT->JSON_RESULT->MESSAGE          = "ATTRIBUTE CUST_PHONE_NO REQUIRED!";
                                        }
                                } else {
                                        $JSON_API_RESULT->JSON_RESULT->CODE                     = "ERROR";
                                        $JSON_API_RESULT->JSON_RESULT->MESSAGE          = "WE DON'T KNOW HOW TO PROCESS $JSON_REQUEST->REQ ";
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
