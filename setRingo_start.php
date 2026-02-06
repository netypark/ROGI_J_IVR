<?php

include 'plog.php';

	function setRingo_s( $MASTER_ID, $COMPANY_ID ) 
	{ 
		WLOG( sprintf( '[SET_RING_S %s:%s] %s', $MASTER_ID, $COMPANY_ID, 'START =======================================================================================================' ) );
		
		$RESULT = "SUCCESS:Insert";

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

/***
                $conn_db = mysqli_connect(
                  '118.67.142.108',
                  'root',
                  'Nautes12@$',
                  'LOGI',
                  '13306');
***/

                $sql = "";

		$RINGO_Q        = array();
		$RINGO_NAME     = array();
		$RINGO_DID      = array();
		$RINGO_CNT      = 0;

		//$sql = "select use_queue, ringo, company_id, master_id from T_COMPANY where is_send_pbx='N' and ringo !='' and company_level=2 order by use_queue;";
		$sql = "select use_queue, ringo, did_number from T_COMPANY where company_id=$COMPANY_ID and company_level=2 and master_id=$MASTER_ID order by use_queue;";

		error_log($sql);
		WLOG( sprintf( '[SET_RING_S %s:%s] %s', $MASTER_ID, $COMPANY_ID, $sql ) );

		$res = mysqli_query($conn_db, $sql);

		while( $row = mysqli_fetch_array($res) )
		{
			array_push( $RINGO_Q   , $row[0] );
			array_push( $RINGO_NAME, $row[1] );
			array_push( $RINGO_DID , $row[2] );

			$RINGO_CNT        = count($RINGO_Q);

			WLOG( sprintf( '[SET_RING_S %s:%s] Find DID %s Find Q %s RINGO %s COUNT %s', $MASTER_ID, $COMPANY_ID, $row[2], $row[0], $row[1], $EXT_CNT ) );
		}

		for( $i=0; $i<$RINGO_CNT; $i++ )
		{
			$sql = "delete from queues_details where id='$RINGO_Q[$i]' and keyword = 'music';delete from moh_settings where did_number='$RINGO_DID[$i]'";
			error_log($sql);
			WLOG( sprintf( '[SET_RING_S %s:%s] %s', $MASTER_ID, $COMPANY_ID, $sql ) );

			$res = mysqli_multi_query($conn, $sql);
		}

		mysqli_close ( $conn );

		usleep(1000);

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
			'logisoft123',
			'asterisk',
			'3306');

		for( $i=0; $i<$RINGO_CNT; $i++ )
		{
			if( $RINGO_NAME[$i] != '' )
			{
				$sql = "insert into queues_details (id,keyword,data,flags) values ('$RINGO_Q[$i]','music','$RINGO_NAME[$i]','0');insert into moh_settings ( did_number, moh_class ) values ( '$RINGO_DID[$i]', '$RINGO_NAME[$i]' );";
				error_log($sql);
				WLOG( sprintf( '[SET_RING_S %s:%s] %s', $MASTER_ID, $COMPANY_ID,  $sql ) );

				$res = mysqli_multi_query($conn, $sql);
			}
		}

		#system( "fwconsole reload", $result );

		#WLOG( sprintf( '[SET_RING_S %s:%s] %s', $MASTER_ID, $COMPANY_ID, 'fwconsole reload' ) );


		mysqli_close ( $conn );
		mysqli_close ( $conn_db );

		WLOG( sprintf( '[SET_RING_S %s:%s] %s', $MASTER_ID, $COMPANY_ID, 'END   =======================================================================================================' ) );

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

		error_log(sprintf('call setRingo_start.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		WLOG( sprintf( '[SET_RING_S CALL] setRingo_start.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'SET_RINGO') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE    = setRingo_s( $JSON_REQUEST->MASTER_ID, 
												$JSON_REQUEST->COMPANY_ID);
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
