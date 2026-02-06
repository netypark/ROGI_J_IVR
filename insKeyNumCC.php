<?php

include 'plog.php';

	function ins_keynum( $TR_NUM, $TR_PAS, $TR_SVR_IP, $TR_SVR_PORT, $TR_DOM, $TR_REG ) 
	{ 
		WLOG( sprintf( '[INS_KEYNUM %s] START =======================================================================================================', $TR_NUM ) );
		
		$RESULT = "SUCCESS:Insert";

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
                  	'mycat123',
                  	'asterisk',
                  	'3306');

                $sql = "";

		$sql = "select trunkid from trunks where channelid='$TR_NUM' limit 1";

		error_log($sql);
		WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

                $res = mysqli_query($conn, $sql);

		if( $row = mysqli_fetch_array($res) )
		{
			error_log( sprintf('KEY [%s] Aleady exist..! -> Update', $TR_NUM) );
			WLOG( sprintf( '[INS_KEYNUM %s] KEY Aleady exist..! -> Update', $TR_NUM ) );

			$TR_ID = $row[0];

			/**
			$sql = "update pjsip set data='sip:${TR_NUM}@${TR_DOM}' where id=$TR_ID and keyword='client_uri';";
			$sql = "update pjsip set data='$TR_PAS' where id=$TR_ID and keyword='secret';";
			$sql = "update pjsip set data='$TR_DOM' where id=$TR_ID and keyword='from_domain';";
			$sql = "update pjsip set data='$TR_SVR_IP' where id=$TR_ID and keyword='sip_server';";
			$sql = "update pjsip set data='$TR_SVR_PORT' where id=$TR_ID and keyword='sip_server_port';";
			$sql = "update pjsip set data='off' where id=$TR_ID and keyword='disabletrunk';";
			$sql = "update pjsip set data='on' where id=$TR_ID and keyword='disabletrunk';";
			**/

			if( $TR_REG == 'on' || $TR_REG == 'ON' )
			{
				$sql = "update trunks set disabled='off' where trunkid=$TR_ID;update pjsip set data='sip:${TR_NUM}@${TR_DOM}' where id=$TR_ID and keyword='client_uri';update pjsip set data='$TR_PAS' where id=$TR_ID and keyword='secret';update pjsip set data='$TR_DOM' where id=$TR_ID and keyword='from_domain';update pjsip set data='$TR_SVR_IP' where id=$TR_ID and keyword='sip_server';update pjsip set data='$TR_SVR_PORT' where id=$TR_ID and keyword='sip_server_port';update pjsip set data='off' where id=$TR_ID and keyword='disabletrunk';";
			}
			else
			{
				$sql = "update trunks set disabled='on' where trunkid=$TR_ID;update pjsip set data='sip:${TR_NUM}@${TR_DOM}' where id=$TR_ID and keyword='client_uri';update pjsip set data='$TR_PAS' where id=$TR_ID and keyword='secret';update pjsip set data='$TR_DOM' where id=$TR_ID and keyword='from_domain';update pjsip set data='$TR_SVR_IP' where id=$TR_ID and keyword='sip_server';update pjsip set data='$TR_SVR_PORT' where id=$TR_ID and keyword='sip_server_port';update pjsip set data='on' where id=$TR_ID and keyword='disabletrunk';";
			}
			error_log($sql);
			WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

			$res = mysqli_multi_query($conn, $sql);

			$RESULT = "SUCCESS:Update";
			//mysqli_close ( $conn );
		}
		else
		{
			$OUT_ROUTE_NAME = 'out_'.$TR_NUM;
			$TR_ID = 0;

			$sql = "select ifnull(max(trunkid)+1, 1) from trunks;";

			error_log($sql);
			WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) )
			{
				error_log( sprintf('MAX [%s] ..!', $row[0]) );
				WLOG( sprintf( '[INS_KEYNUM %s] MAX %s', $TR_NUM, $row[0] ) );

				$TR_ID = $row[0];
			
				$sql = "insert into pjsip ( id, keyword, data ) values ( $TR_ID, 'aor_contact'              , ''                        ), ( $TR_ID, 'aors'                     , ''                        ), ( $TR_ID, 'auth_rejection_permanent' , 'on'                      ), ( $TR_ID, 'auth_username'            , '$TR_NUM'                    ), ( $TR_ID, 'authentication'           , 'outbound'                ), ( $TR_ID, 'client_uri'               , 'sip:${TR_NUM}@${TR_DOM}' ), ( $TR_ID, 'codecs'                   , 'ulaw,alaw'               ), ( $TR_ID, 'contact_user'             , '$TR_NUM'                 ), ( $TR_ID, 'context'                  , 'from-pstn'               ), ( $TR_ID, 'dialopts'                 , ''                        ), ( $TR_ID, 'dialoutopts_cb'           , 'sys'                     ), ( $TR_ID, 'direct_media'             , 'no'                      ), ( $TR_ID, 'disabletrunk'             , 'on'                      ), ( $TR_ID, 'dtmfmode'                 , 'auto'                    ), ( $TR_ID, 'expiration'               , '1800'                      ), ( $TR_ID, 'extdisplay'               , 'OUT_${TR_ID}'            ), ( $TR_ID, 'failtrunk_enable'         , '0'                       ), ( $TR_ID, 'fatal_retry_interval'     , '30'                      ), ( $TR_ID, 'fax_detect'               , 'no'                      ), ( $TR_ID, 'forbidden_retry_interval' , '30'                      ), ( $TR_ID, 'force_rport'              , 'yes'                     ), ( $TR_ID, 'from_domain'              , '$TR_DOM'                 ), ( $TR_ID, 'from_user'                , '$TR_NUM'                 ), ( $TR_ID, 'hcid'                     , 'on'                      ), ( $TR_ID, 'identify_by'              , 'default'                 ), ( $TR_ID, 'inband_progress'          , 'no'                      ), ( $TR_ID, 'language'                 , ''                        ), ( $TR_ID, 'match'                    , ''                        ), ( $TR_ID, 'max_retries'              , '10000'                   ), ( $TR_ID, 'maxchans'                 , '10'                      ), ( $TR_ID, 'media_address'            , ''                        ), ( $TR_ID, 'media_encryption'         , 'no'                      ), ( $TR_ID, 'message_context'          , ''                        ), ( $TR_ID, 'npanxx'                   , ''                        ), ( $TR_ID, 'outbound_proxy'           , ''                        ), ( $TR_ID, 'peerdetails'              , ''                        ), ( $TR_ID, 'qualify_frequency'        , '1800'                      ), ( $TR_ID, 'register'                 , ''                        ), ( $TR_ID, 'registration'             , 'send'                    ), ( $TR_ID, 'retry_interval'           , '60'                      ), ( $TR_ID, 'rewrite_contact'          , 'yes'                     ), ( $TR_ID, 'rtp_symmetric'            , 'yes'                     ), ( $TR_ID, 'secret'                   , '$TR_PAS'                 ), ( $TR_ID, 'send_connected_line'      , 'false'                   ), ( $TR_ID, 'sendrpid'                 , 'yes'                     ), ( $TR_ID, 'server_uri'               , ''                        ), ( $TR_ID, 'sip_server'               , '$TR_SVR_IP'              ), ( $TR_ID, 'sip_server_port'          , '$TR_SVR_PORT'            ), ( $TR_ID, 'support_path'             , 'no'                      ), ( $TR_ID, 'sv_channelid'             , '$TR_NUM'                 ), ( $TR_ID, 'sv_trunk_name'            , '$TR_NUM'                 ), ( $TR_ID, 'sv_usercontext'           , ''                        ), ( $TR_ID, 't38_udptl'                , 'no'                      ), ( $TR_ID, 't38_udptl_ec'             , 'none'                    ), ( $TR_ID, 't38_udptl_maxdatagram'    , ''                        ), ( $TR_ID, 't38_udptl_nat'            , 'no'                      ), ( $TR_ID, 'transport'                , '0.0.0.0-udp'             ), ( $TR_ID, 'trunk_name'               , '$TR_NUM'                 ), ( $TR_ID, 'trust_id_outbound'        , 'yes'                     ), ( $TR_ID, 'trust_rpid'               , 'no'                      ), ( $TR_ID, 'user_eq_phone'            , 'no'                      ), ( $TR_ID, 'userconfig'               , ''                        ), ( $TR_ID, 'username'                 , '$TR_NUM'                 );";

				error_log($sql);
				WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );


				$res = mysqli_query($conn, $sql);

				if( $TR_REG == 'on' || $TR_REG == 'ON' )
				{
					$sql = "insert into trunks ( trunkid, tech, channelid, name, outcid, keepcid, maxchans, failscript, dialoutprefix, usercontext, provider, disabled ) values ( $TR_ID, 'pjsip', '$TR_NUM', '$TR_NUM', '$TR_NUM', 'off', '10', '', '', '', '', 'off' );update pjsip set data='off' where id=$TR_ID and keyword='disabletrunk';insert into arsauth_call_in_scenario ( TIME_UPDATE, CALL_FROM, CALL_TO, FORWARD_FROM, FORWARD_TO, CALL_IN_VALID, STR_JSON_ARGS ) values ( now(), '', '$TR_NUM', '', '', DATE_ADD(NOW(), INTERVAL 100 YEAR), '{ 
    \"CALL_ARGS\": { 
        \"CALL_SCENARIO\": \"NEW_SCN\", 
        \"CALL_IN_ANSWER\" : true 
    } 
}' );";

					error_log($sql);
					WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

					$res = mysqli_multi_query($conn, $sql);
				}
				else
				{
					$sql = "insert into trunks ( trunkid, tech, channelid, name, outcid, keepcid, maxchans, failscript, dialoutprefix, usercontext, provider, disabled ) values ( $TR_ID, 'pjsip', '$TR_NUM', '$TR_NUM', '$TR_NUM', 'off', '10', '', '', '', '', 'on' );insert into trunks_reg_side ( trunkid, name, side ) values ( '$TR_ID', '$TR_NUM', 'A' );insert into arsauth_call_in_scenario ( TIME_UPDATE, CALL_FROM, CALL_TO, FORWARD_FROM, FORWARD_TO, CALL_IN_VALID, STR_JSON_ARGS ) values ( now(), '', '$TR_NUM', '', '', DATE_ADD(NOW(), INTERVAL 100 YEAR), '{ \
    \"CALL_ARGS\": { 
        \"CALL_SCENARIO\": \"NEW_SCN\", 
        \"CALL_IN_ANSWER\" : true 
    }
}' );";
					error_log($sql);
					WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

					$res = mysqli_multi_query($conn, $sql);
				}
			}
		}

		mysqli_close ( $conn );


		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
                  	'mycat123',
                  	'asterisk',
                  	'3306');

		$sql = "select route_id from outbound_routes where name='OUT_NS';";

		error_log($sql);
		WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );
	
		$is_bind = 0;

		$res = mysqli_query($conn, $sql);

		//while( $row = mysqli_fetch_array($res) )
		if( $row=mysqli_fetch_row($res) )
		{
			error_log( sprintf('route_id [%s] ..!', $row[0]) );
			WLOG( sprintf( '[INS_KEYNUM %s] route_id %s ..!', $TR_NUM, $row[0] ) );

			$ROUTE_ID = $row[0];
			$is_bind = 1;
		}
		else
		{
			error_log( sprintf('route_id is NULL ..!' ) );
			WLOG( sprintf( '[INS_KEYNUM %s] route_id is NULL ..!', $TR_NUM ) );
		}

		if( $is_bind == 1 )
		{
			/**
			$sql = "select ifnull(max(match_pattern_prefix)+1, 1) from outbound_route_patterns;";

			error_log($sql);
			WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );
			$PREFIX = 0;
	
			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) )
			{
				error_log( sprintf('prerfix [%s] ..!', $row[0]) );
				WLOG( sprintf( '[INS_KEYNUM %s] prefix [%s] ..!', $TR_NUM, $row[0] ) );

				$PREFIX = $row[0];
			}

			if( $PREFIX == '1' )
			{
				$sql = "insert into outbound_route_patterns ( route_id, match_pattern_prefix, match_pattern_pass ) values ( $ROUTE_ID, '8000', '.' );";

				error_log($sql);
				WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

				$res = mysqli_multi_query($conn, $sql);
			}
			else
			{
				$sql = "insert into outbound_route_patterns ( route_id, match_pattern_prefix, match_pattern_pass ) values ( $ROUTE_ID, '$PREFIX', '.' );";

				error_log($sql);
				WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

				$res = mysqli_multi_query($conn, $sql);
			}

			$sql = "select ifnull(min(seq+1), 0) from outbound_route_sequence where (seq+1) not in ( select seq from outbound_route_sequence );";

			error_log($sql);
			WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );
			$OUT_SEQ = 0;
	
			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) )
			{
				error_log( sprintf('out_seq [%s] ..!', $row[0]) );
				WLOG( sprintf( '[INS_KEYNUM %s] out_seq [%s] ..!', $TR_NUM, $row[0] ) );

				$OUT_SEQ = $row[0];

				$sql = "insert into outbound_route_sequence ( route_id, seq ) values ( $ROUTE_ID, $OUT_SEQ );insert into outbound_route_trunks ( route_id, trunk_id, seq ) values ( $ROUTE_ID, $TR_ID, 0 );";
				error_log($sql);
				WLOG( sprintf( '[INS_KEYNUM %s] %s', $TR_NUM, $sql ) );

				$res = mysqli_multi_query($conn, $sql);
			}

		}

		mysqli_close ( $conn );

		system( "fwconsole reload", $result );
		WLOG( sprintf( '[INS_KEYNUM %s] fwconsole reload', $TR_NUM ) );
		

		WLOG( sprintf( '[INS_KEYNUM %s] END   =======================================================================================================', $TR_NUM ) );

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

		error_log(sprintf('call insKeyNum.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		SLOG( sprintf( '[INS_KEYNUM CALL] insKeyNum.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'INS_TRK') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE    = ins_keynum( $JSON_REQUEST->TR_NUM,
												$JSON_REQUEST->TR_PAS, 
												$JSON_REQUEST->TR_SVR_IP, 
												$JSON_REQUEST->TR_SVR_PORT, 
												$JSON_REQUEST->TR_DOM, 
												$JSON_REQUEST->TR_REG );
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
