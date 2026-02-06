<?php

include 'plog.php';

	function get_q_exten_cc_did( $COMPANY_ID, $DID, $CID, $TYPE ) 
	{ 
		SLOG( sprintf( '[GET_QE_CC_D %s:%s] START =======================================================================================================', $DID, $CID ) );
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

		//mysqli_query($conn, "set session character_set_connection=utf8;");
		//mysqli_query($conn, "set session character_set_results=utf8;");
		//mysqli_query($conn, "set session character_set_client=utf8;");

		$sql = "";

		$RESULT = new stdClass();

		$RESULT->tr_company_id 	= "";
		$RESULT->tr_did 	= "";
		$RESULT->tr_q_num 	= "";
		$RESULT->tr_q_ext 	= "";
		$RESULT->tr_rcv_op 	= "";
		$RESULT->tr_rcv_op2 	= "";
		$RESULT->tr_rwtm 	= "";
		$RESULT->tr_rwtt 	= "";
		$RESULT->tr_rett 	= "";

		$RESULT->re_call 	= "N";
		$RESULT->re_ring_time 	= "";
		$RESULT->re_in_ring 	= "N";
		$RESULT->re_call_q 	= "";

		$sql = "select phone_hold_time, called from T_CALL_STATE where company_id=$COMPANY_ID and company_did='$DID' and caller='$CID' and end_time BETWEEN DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND NOW() order by end_time desc limit 1;";

		error_log($sql);
		SLOG( sprintf( '[GET_RE_CALL %s:%s] %s', $DID, $CID, $sql ) );

		$Q_NUM = "";
		$Q_EXT = "";

		$res = mysqli_query($conn, $sql);

		if( $row = mysqli_fetch_array($res) ) 
		{
			//error_log($row[0]);

			$RESULT->re_ring_time 	= $row[0];
			$RESULT->re_call_q 	= $row[1];
			$RESULT->re_call 	= "Y";

			SLOG( sprintf( '[GET_RE_CALL %s:%s] RING TIME:%s Q:%s', $DID, $CID, $row[0], $row[1] ) );
		}
		$count= mysqli_num_rows($res);


		if( $TYPE == 'GET_Q' )
		{
			$sql = "select g.q_group_num, e.ext_number from T_Q_GROUP g INNER JOIN T_Q_GROUP_USER AS u on g.q_group_id=u.q_group_id INNER JOIN T_Q_EXTENSION AS e on u.q_ext_id=e.ext_id where u.company_id=$COMPANY_ID and e.is_status = 1 order by g.call_order limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_Q_EXTEN %s:%s] %s', $DID, $CID, $sql ) );

			$Q_NUM = "";
			$Q_EXT = "";

			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) ) 
			{
				//error_log($row[0]);

				$RESULT->tr_q_num = $row[0];
				$RESULT->tr_q_ext = $row[1];

				SLOG( sprintf( '[GET_Q_EXTEN %s:%s] Q:%s EXTENSION:%s', $DID, $CID, $row[0], $row[1] ) );
			}
			$count= mysqli_num_rows($res);
		}
		else if(  $TYPE == 'GET_CC_SMY')
		{
			$sql = "select m.transfer_company_id, m.transfer_did_number, g.q_group_num, e.ext_number, s.receive_option, s.ring_wait_time_my, s.ring_wait_time_transfer from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_GROUP AS g on m.transfer_company_id = g.q_company_id INNER JOIN T_Q_EXTENSION AS e on e.company_id=g.q_company_id INNER JOIN T_SET_MY AS s on m.company_id = s.company_id where m.company_id=$COMPANY_ID and e.call_status=0 order by m.transfer_order_num limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );

			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) ) 
			{
				//error_log($row[0]);

				$RESULT->tr_company_id 		= $row[0];
				$RESULT->tr_did 		= $row[1];
				$RESULT->tr_q_num 		= $row[2];
				$RESULT->tr_q_ext 		= $row[3];
				$RESULT->tr_rcv_op 		= $row[4];
				$RESULT->tr_rwtm 		= $row[5];
				$RESULT->tr_rwtt 		= $row[6];

				if( $RESULT->re_call == "Y" && $RESULT->tr_rwtm > $RESULT->re_ring_time )
					$RESULT->re_in_ring = "Y";

				SLOG( sprintf( '[GET_CC_SMYT %s:%s] Trnasfer Company ID :%s TR DID:%s Q:%s EXTENSION:%s RCV_OP:%s RWTM:%s RWTT:%s', 
						$DID, $CID, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6] ) );
			}
			$count= mysqli_num_rows($res);
		}
		else if(  $TYPE == 'GET_CC_DIR')
		{
			$sql = "select m.transfer_company_id, m.transfer_did_number, g.q_group_num, e.ext_number, s.receive_option, s.ring_wait_time_my, s.ring_wait_time_transfer from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_GROUP AS g on m.transfer_company_id = g.q_company_id INNER JOIN T_Q_EXTENSION AS e on e.company_id=g.q_company_id INNER JOIN T_SET_DIRECT AS s on m.company_id = s.company_id where m.company_id=$COMPANY_ID and e.call_status=0 order by m.transfer_order_num limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );

			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) ) 
			{
				//error_log($row[0]);

				$RESULT->tr_company_id 		= $row[0];
				$RESULT->tr_did 		= $row[1];
				$RESULT->tr_q_num 		= $row[2];
				$RESULT->tr_q_ext 		= $row[3];
				$RESULT->tr_rcv_op 		= $row[4];
				$RESULT->tr_rwtm 		= $row[5];
				$RESULT->tr_rwtt 		= $row[6];

				if( $RESULT->re_call == "Y" && $RESULT->tr_rwtm > $RESULT->re_ring_time )
					$RESULT->re_in_ring = "Y";

				SLOG( sprintf( '[GET_CC_DIRT %s:%s] Trnasfer Company ID :%s TR DID:%s Q:%s EXTENSION:%s RCV_OP:%s RWTM:%s RWTT:%s', 
						$DID, $CID, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6] ) );
			}
			$count= mysqli_num_rows($res);
		}
		else if(  $TYPE == 'GET_CC_SEQ')
		{
			$sql = "select m.transfer_company_id, g.m.transfer_did_number, q_group_num, e.ext_number, s.receive_option1, s.receive_option2, s.recall_transfer_time, s.ring_wait_time_transfer from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_GROUP AS g on m.transfer_company_id = g.q_company_id INNER JOIN T_Q_EXTENSION AS e on e.company_id=g.q_company_id INNER JOIN T_SET_SEQUENCE AS s on m.company_id = s.company_id where m.company_id=$COMPANY_ID and e.call_status=0 order by m.transfer_order_num limit 1;";

			error_log($sql);
			SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );

			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) ) 
			{
				//error_log($row[0]);

				$RESULT->tr_company_id 		= $row[0];
				$RESULT->tr_did 		= $row[1];
				$RESULT->tr_q_num 		= $row[2];
				$RESULT->tr_q_ext 		= $row[3];
				$RESULT->tr_rcv_op 		= $row[4];
				$RESULT->tr_rcv_op2 		= $row[5];
				$RESULT->tr_rett 		= $row[6];
				$RESULT->tr_rwtt 		= $row[7];

				if( $RESULT->re_call == "Y" && $RESULT->tr_rwtt > $RESULT->re_ring_time )
					$RESULT->re_in_ring = "Y";

				SLOG( sprintf( '[GET_CC_SEQT %s:%s] Trnasfer Company ID :%s TR DID:%s Q:%s EXTENSION:%s RCV_OP:%s RCV_OP2:%s RETT:%s RWTT:%s', 
						$DID, $CID, $row[0], $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7] ) );
			}
			$count= mysqli_num_rows($res);
		}
		else if( $TYPE == 'GET_CC_DID' )
		{
			$sql = "select transfer_did_number, transfer_order_num from T_MY_TRANSFER_CALL where company_id=$COMPANY_ID order by transfer_order_num;";

			error_log($sql);
			SLOG( sprintf( '[GET_CC_TRAN %s:%s] %s', $DID, $CID, $sql ) );

			$TR_DID = array();

			$res = mysqli_query($conn, $sql);

			while( $row = mysqli_fetch_array($res) ) 
			{
				//error_log($row[0]);

				array_push( $TR_DID, $row[0] );

				$RESULT->tr_did = $TR_DID;
				if( $row[1] == '1' )
				{
					$RESULT->first_tr_did = $row[0];
				}
				$RESULT->tr_did_count = count($TR_DID);

				SLOG( sprintf( '[GET_CC_TRAN %s:%s] TRANSFER DID:%s', $DID, $CID, $row[0] ) );
			}

			$count= mysqli_num_rows($res);
		}

		//error_log($RESULT );

		mysqli_close ( $conn );

		// 데이터 출력후 statement 를 해제한다

		SLOG( sprintf( '[GET_QE_CC_D %s:%s] END   =======================================================================================================', $DID, $CID ) );

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

	$JSON_API_RESULT = new stdClass();

	$JSON_API_RESULT->JSON_REQUEST          = null;
	$JSON_API_RESULT->JSON_RESULT           = new stdClass();

	$JSON_API_RESULT->JSON_RESULT->CODE     = 200;
	$JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

	if (isset($args->JSON_REQUEST)) 
	{
		$JSON_REQUEST = json_decode($args->JSON_REQUEST);
		if (!is_object($JSON_REQUEST)) 
		{
			$JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
		}

		error_log(sprintf('call getWorkCondition.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		SLOG( sprintf( '[GET_Q_EXTEN CALL] getQExt.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) 
		{
			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;

			if (isset($JSON_REQUEST->REQ)) 
			{
				if ($JSON_REQUEST->REQ == 'GET_Q_EXT_TR_DID') 
				{
					$JSON_API_RESULT->JSON_RESULT->CODE	= "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE	= get_q_exten_cc_did( 	$JSON_REQUEST->COMPANY_ID,
													$JSON_REQUEST->DID ,
													$JSON_REQUEST->CID ,
													$JSON_REQUEST->TYPE );
				}
			} 
			else 
			{
				$JSON_API_RESULT->JSON_RESULT->CODE             = "ERROR";
				$JSON_API_RESULT->JSON_RESULT->MESSAGE		= "ATTRIBUTE REQ REQUIRED!";
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
