<?php

include 'plog.php';

	function get_Q_agent( $COMP_ID, $ARS_ID, $DID, $CID ) 
	{ 
		SLOG( sprintf( '[GET_Q_AGENT %s:%s] START =======================================================================================================', $DID, $CID ) );

		$RESULT = new stdClass();

/***
		$conn = mysqli_connect(
                  '127.0.0.1',
                  'root',
                  'mycat123',
                  'asterisk',
                  '3306');

                $sql = "";

                $sql = "select c.match_pattern_prefix from trunks a inner join outbound_route_trunks b inner join outbound_route_patterns c on a.trunkid = b.trunk_id and b.route_id = c.route_id where a.channelid='KT_NS'";

                error_log($sql);
		SLOG( sprintf( '[GET_Q_AGENT %s:%s] %s', $DID, $CID, $sql ) );

                $DEST_PREFIx = "";

                $res = mysqli_query($conn, $sql);

                if( $row = mysqli_fetch_array($res) )
                {
                        $DEST_PREFIX = $row[0];
                }

                mysqli_close ( $conn );

                error_log('DEST_PREFIX : '.$DEST_PREFIX);
		SLOG( sprintf( '[GET_Q_AGENT %s:%s] DEST_PREFIX : %s', $DID, $CID, $DEST_PREFIX ) );

		$RESULT->PREFIX = $DEST_PREFIX;
***/
		$RESULT->Q_NUM		= "";
		$RESULT->PHONE		= "";

                $conn = mysqli_connect(
                  '118.67.142.108',
                  'root',
                  'Nautes12@$',
                  'LOGI',
                  '13306');

		$sql = "";

		$sql = "select a.q_group_num, b.q_ext_id, c.ext_number from T_Q_GROUP a inner join T_Q_GROUP_USER b inner join T_Q_EXTENSION c on a.q_group_id = b.q_group_id and b.q_ext_id = c.ext_id where a.company_id='$DID' and b.company_id='$DID' and c.is_status='I' order by a.call_order limit 1;";

		error_log($sql);
		SLOG( sprintf( '[GET_Q_AGENT %s:%s] %s', $DID, $CID, $sql ) );

		$res = mysqli_query($conn, $sql);

		$nIdx=0;
		$isData=0;

		$Q_NUM[] = new stdClass();
                $IP_PHONE[] = new stdClass();
		$STR_NUM="";

		SLOG( sprintf( '[GET_Q_AGENT %s:%s] get T_Q_GROUP T_Q_EXTENSION ==============================================', $DID, $CID ) );

		while( $row = mysqli_fetch_array($res) ) 
		{
			error_log($row[0]);
			error_log($row[1]);
			$Q_NUM[$nIdx]      = $row[0];
			$IP_PHONE[$nIdx++] = $row[1];
			$isData=1;
		}

		$count= mysqli_num_rows($res) ;


		if( $isData == 1 ) 
		{
			$RESULT->Q_NUM = $Q_NUM[0];
			$RESULT->PHONE = $IP_PHONE[0];
			SLOG( sprintf( '[GET_Q_AGENT %s:%s] Q_NUM :%s EXTENSION :%s', $DID, $CID, $Q_NUM[0], $IP_PHONE[0] ) );
		}

		//mysqli_close ( $conn );

		//error_log($nIdx);

/***
		for( $i=0; $i < $nIdx; $i++ )
		{
			$STR_NUM = $STR_NUM."'".$IP_PHONE[$i]."'".",";
		}
		SLOG( sprintf( '[GET_Q_AGENT %s:%s] PHONE_NUMS :%s', $DID, $CID, $STR_NUM ) );


		if( $isData == 0 )
		{
			$RESULT->PHONE		= "";
		}
		else 
		{
			$NEW_STR = rtrim($STR_NUM, ", ");

			//$sql = "select PHONE_NO from T_PHONE_LIST where HOOK_STATUS = 'On-Hook' and PHONE_NO in ($NEW_STR) order by field( PHONE_NO, $NEW_STR ) limit 1;";
			$sql = "select PHONE_NO from T_PHONE_LIST where ( HOOK_STATUS = 'Released' or HOOK_STATUS = 'On-Hook' ) and PHONE_NO in ($NEW_STR) order by field( PHONE_NO, $NEW_STR ) limit 1;";
			error_log($sql);
			SLOG( sprintf( '[GET_Q_AGENT %s:%s] %s', $DID, $CID, $sql ) );

			$res = mysqli_query($conn, $sql);

			$row = mysqli_fetch_array($res);

			//$RESULT->PHONE		= $DEST_PREFIX.$row[0];
			$RESULT->PHONE 		= $row[0];
			SLOG( sprintf( '[GET_Q_AGENT %s:%s] PHONE_NO : %s', $DID, $CID, $row[0] ) );

			//$sql = "select a.PHONE_ID, b.GROUP_ID from T_PHONE as a inner join T_GROUP_USER as b inner join T_GROUP_ARS c on a.IP_PHONE='$RESULT->PHONE' where c.SCN_ID=$SCN_ID;";
			$sql = "select a.PHONE_ID, b.GROUP_ID from T_PHONE as a inner join T_GROUP_ARS as b inner join T_GROUP_USER c on a.IP_PHONE='$RESULT->PHONE' where b.SCN_ID=$SCN_ID limit 1;";
			error_log($sql);
			SLOG( sprintf( '[GET_Q_AGENT %s:%s] %s', $DID, $CID, $sql ) );

			$res = mysqli_query($conn, $sql);

			$row = mysqli_fetch_array($res);

			$P_ID		= $row[0];
			$G_ID		= $row[1];

			$sql = "update T_GROUP_USER a inner join T_GROUP_ARS b inner join T_PHONE c on ( a.GROUP_ID=b.GROUP_ID ) and ( a.PHONE_ID = c.PHONE_ID ) set a.LATEST_CON_DATETIME=now(), b.LATEST_CALL_PID ='$P_ID' where b.GROUP_ID='$G_ID' and c.IP_PHONE='$RESULT->PHONE';";
			//$sql = "update T_GROUP_USER a inner join T_GROUP_ARS b inner join T_PHONE c on ( a.GROUP_ID=b.GROUP_ID ) and ( a.PHONE_ID = c.PHONE_ID ) set a.LATEST_CON_DATETIME=now(), b.LATEST_CALL_PID ='$P_ID', c.STATUS=1  where b.GROUP_ID='$G_ID' and c.IP_PHONE='$RESULT->PHONE';";

			error_log($sql);
			SLOG( sprintf( '[GET_Q_AGENT %s:%s] %s', $DID, $CID, $sql ) );
			$res = mysqli_query($conn, $sql);
		}
**/

		mysqli_close ( $conn );

		SLOG( sprintf( '[GET_Q_AGENT %s:%s] END   =======================================================================================================', $DID, $CID ) );
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
				if ($JSON_REQUEST->REQ == 'GET_Q_AGENT') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_Q_agent( 
												$JSON_REQUEST->COMP_ID, 
												$JSON_REQUEST->ARS_ID ,
												$JSON_REQUEST->DID ,
												$JSON_REQUEST->CID );
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
