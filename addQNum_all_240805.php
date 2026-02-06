<?php

include 'plog.php';

	function addQNum( ) 
	{ 
		WLOG( sprintf( '[IPPBXSET %s] %s', '-----------', 'START =======================================================================================================' ) );
		
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

		$EXT_CNT = 0; 
		$Q_CNT = 0; 
		$QNUM	 	= array(); 
		$EXTENSION 	= array(); 
		$ORDER 		= array(); 
		$QE_ID 		= array(); 
		$MASTER_EXT_ID 	= array(); 
		$RINGO = 0;;

		$QUEUE	 	= array(); 
		$HUNT_TYPE 	= array(); 
		$HUNT_TIME	= array(); 
		$IS_OTHER_Q	= array(); 
		$OTHER_Q_NUM	= array(); 
		$MASTER_ID 	= array(); 

		

		$sql = "select q_num, ext_number, call_order, q_ext_id, master_id from T_Q_EXTENSION order by q_num, call_order;";

		error_log($sql);
		WLOG( sprintf( '[FIND_EXT %s] %s', '-----------', $sql ) );

		$res = mysqli_query($conn_db, $sql);

		while( $row = mysqli_fetch_array($res) )
		{
			array_push( $QNUM, $row[0] );
			array_push( $EXTENSION, $row[1] );
			array_push( $ORDER, $row[2] );
			array_push( $QE_ID, $row[3] );
			array_push( $MASTER_EXT_ID, $row[4] );

			$EXT_CNT        = count($EXTENSION);

			WLOG( sprintf( '[FIND_EXT %s] Find Q %s EXTENSION %s ORDER %s COUNT %s', $row[4], $row[0], $row[1], $row[2], $EXT_CNT ) );
		}

		$sql = "select q_num, hunt_type, hunt_time, is_other_q, other_q_num, master_id from T_QUEUE order by q_num;";

		error_log($sql);
		WLOG( sprintf( '[FIND_QUE %s] %s', '-----------', $sql ) );

		$res = mysqli_query($conn_db, $sql);

		while( $row = mysqli_fetch_array($res) )
		{
			array_push( $QUEUE	, $row[0] );
			array_push( $HUNT_TYPE	, $row[1] );
			array_push( $HUNT_TIME	, $row[2] );
			array_push( $IS_OTHER_Q	, $row[3] );
			array_push( $OTHER_Q_NUM, $row[4] );
			array_push( $MASTER_ID  , $row[5] );

			$Q_CNT        = count($QUEUE);

			WLOG( sprintf( '[FIND_QUE %s] Find Q %s HUNT_TYPE %s HUNT_TIME %s IS_OTHER_Q %s OTHER_Q_NUM %s COUNT %s', 
				$row[5], $row[0], $row[1], $row[2], $row[3], $row[4], $Q_CNT ) );
		}

		mysqli_close ( $conn );

		usleep(1000);

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
			'logisoft123',
			'asterisk',
			'3306');

		for( $i=0; $i<$Q_CNT; $i++ )
		{
			$DEST_Q = '';
			$STRATEGY = '';

			if( $IS_OTHER_Q[$i] == 'Y' ) 	$DEST_Q = 'ext-queues,'.$OTHER_Q_NUM[$i].',1';
			else 				$DEST_Q = 'app-blackhole,hangup,1';

			//E:균등분배, A:모두 울림, I:입력순서, R:무작위, P:인입비율 default=E
			if( $HUNT_TYPE[$i] == 'E' )		$STRATEGY = 'rrmemory'; 
			else if( $HUNT_TYPE[$i] == 'A' )	$STRATEGY = 'ringall';
			else if( $HUNT_TYPE[$i] == 'I' )	$STRATEGY = 'linear';
			else if( $HUNT_TYPE[$i] == 'R' )	$STRATEGY = 'random';
			else if( $HUNT_TYPE[$i] == 'P' )	$STRATEGY = 'rrordered';
			else				$STRATEGY = 'rrmemory';

			$sql = "select extension from queues_config where extension='$QUEUE[$i]' limit 1";

			error_log($sql);
			WLOG( sprintf( '[FIND_QUE %s:%s] %s', $MASTER_ID[$i], $QUEUE[$i], $sql ) );

			$res = mysqli_query($conn, $sql);

			if( $row = mysqli_fetch_array($res) )
			{
				error_log( sprintf('FIND_QUE [%s] Aleady exist..! -> Update', $QUEUE[$i]) );
				WLOG( sprintf( '[FIND_QUE %s:%s] Q NUM Aleady exist..! -> Update', $MASTER_ID[$i], $QUEUE[$i] ) );

				$Q_ID = $row[0];

				$RESULT = "SUCCESS:Update";

				$sql = "update queues_config set dest='$DEST_Q' where extension='$QUEUE[$i]';update queues_details set data='$STRATEGY' where keyword='strategy' and id='$QUEUE[$i]';update queues_details set data='$HUNT_TIME[$i]' where keyword='timeout' and id='$QUEUE[$i]';";

				error_log($sql);
				WLOG( sprintf( '[UPDT_QUE %s:%s] %s', $MASTER_ID[$i], $QUEUE[$i], $sql ) );

				$res = mysqli_multi_query($conn, $sql);

			}
			else
			{
				$sql = "insert into queues_config (extension,descr,grppre,alertinfo,ringing,maxwait,password,ivr_id,dest,cwignore,queuewait,use_queue_context,togglehint,qnoanswer,callconfirm,callback_id) values('$QUEUE[$i]','$QUEUE[$i]','','','0','','','none','$DEST_Q','0','0','0','0','1','0','none');insert into queues_details ( id, keyword, data ) values ( $QUEUE[$i], 'announce-frequency', '0' ), ( $QUEUE[$i], 'announce-holdtime' , 'no'), ( $QUEUE[$i], 'announce-position' , 'no'), ( $QUEUE[$i], 'answered_elsewhere', '0' ), ( $QUEUE[$i], 'autofill', 'no'), ( $QUEUE[$i], 'autopause', 'no'), ( $QUEUE[$i], 'autopausebusy' , 'no'), ( $QUEUE[$i], 'autopausedelay', '0' ), ( $QUEUE[$i], 'autopauseunavail', 'no'), ( $QUEUE[$i], 'cron_random', 'false'), ( $QUEUE[$i], 'cron_schedule', 'never'), ( $QUEUE[$i], 'eventmemberstatus', '0'), ( $QUEUE[$i], 'eventwhencalled', '0'), ( $QUEUE[$i], 'joinempty', 'yes'), ( $QUEUE[$i], 'leavewhenempty', 'no'), ( $QUEUE[$i], 'maxlen', '0'), ( $QUEUE[$i], 'memberdelay', '0'), ( $QUEUE[$i], 'min-announce-frequency', '15'), ( $QUEUE[$i], 'monitor-join', 'yes'), ( $QUEUE[$i], 'penaltymemberslimit', '0' ), ( $QUEUE[$i], 'periodic-announce-frequency', '0'), ( $QUEUE[$i], 'queue-callswaiting', 'silence/1'), ( $QUEUE[$i], 'queue-thankyou', ''), ( $QUEUE[$i], 'queue-thereare', 'silence/1'), ( $QUEUE[$i], 'queue-youarenext', 'silence/1'), ( $QUEUE[$i], 'recording', 'dontcare'), ( $QUEUE[$i], 'reportholdtime', 'no'), ( $QUEUE[$i], 'retry', 'none'), ( $QUEUE[$i], 'ringinuse', 'no'), ( $QUEUE[$i], 'rvol_mode', 'dontcare'), ( $QUEUE[$i], 'rvolume', '0'), ( $QUEUE[$i], 'servicelevel', '60'), ( $QUEUE[$i], 'skip_joinannounce', ''), ( $QUEUE[$i], 'strategy', '$STRATEGY'), ( $QUEUE[$i], 'timeout', '$HUNT_TIME[$i]'), ( $QUEUE[$i], 'timeoutpriority', 'app'), ( $QUEUE[$i], 'timeoutrestart', 'no'), ( $QUEUE[$i], 'weight', '0'), ( $QUEUE[$i], 'wrapuptime', '0');";

				error_log($sql);
				WLOG( sprintf( '[ADD_QUEU %s:%s] %s', $MASTER_ID[$i], $QUEUE[$i], $sql ) );

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
		}

                $IN_LIST = '';
                $MI_EXT_LIST = '';

                for( $i=0; $i<$EXT_CNT; $i++ )
                {
                        $IN_LIST 	= $IN_LIST."'".$QUEUE[$i]."'".",";
                        $MI_EXT_LIST 	= $MI_EXT_LIST."'".$MASTER_EXT_ID[$i]."'".",";
                }

                $NEWLIST = rtrim($IN_LIST, ", ");
                $MIEXTLIST = rtrim($MI_EXT_LIST, ", ");
                //error_log($NEWLIST);
                //WLOG( sprintf( '[ADD_QUEU ----:---] %s %s', $MIEXTLIST, $NEWLIST ) );

                $sql = "delete from queues_details where keyword = 'member' and id in ($NEWLIST) ;delete from queues_details where keyword = 'memberdelay' and id in ($NEWLIST);";
                error_log($sql);
                WLOG( sprintf( '[ADD_QUEU ----:---] %s %s', $MIEXTLIST, $sql ) );
                //$res = mysqli_multi_query($conn, $sql);

/***
		for( $i=0; $i<$EXT_CNT; $i++ )
		{
			$sql = "delete from queues_details where id='$QNUM[$i]' and keyword = 'member';delete from queues_details where id='$QNUM[$i]' and keyword = 'memberdelay';";
			error_log($sql);
			WLOG( sprintf( '[MOD_QUEU %s:%s] %s', $MASTER_EXT_ID[$i], $QUEUE[$i], $sql ) );

			$res = mysqli_multi_query($conn, $sql);

			mysqli_close ( $conn );

			usleep(1000);

			$conn = mysqli_connect(
				'127.0.0.1',
				'root',
				'logisoft123',
				'asterisk',
				'3306');

		}
***/

		mysqli_close ( $conn );

		usleep(1000);

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
			'logisoft123',
			'asterisk',
			'3306');

		$UPDATE_T_QE='';
                $sql='';
                for( $i=0; $i<$EXT_CNT; $i++ )
                {       
                        $ORD_VAL = $ORDER[$i]-1;
                        $DATA = 'Local/'.$EXTENSION[$i].'@from-queue/n,'.$ORD_VAL;
                        $sql = $sql."insert into queues_details (id,keyword,data,flags) values ('$QNUM[$i]','member','$DATA',$ORD_VAL);";
			$UPDATE_T_QE =  $UPDATE_T_QE."'".$QE_ID[$i]."'".",";
                }
		$NEWLIST = rtrim($UPDATE_T_QE, ", ");

                error_log($sql);
                WLOG( sprintf( '[ADD_QUEU ----:---] %s %s', $MIEXTLIST, $sql ) );

                //$res = mysqli_multi_query($conn, $sql);

/***
		$UPDATE_T_QE='';

		for( $i=0; $i<$EXT_CNT; $i++ )
		{
			$ORD_VAL = $ORDER[$i]-1; 
			$DATA = 'Local/'.$EXTENSION[$i].'@from-queue/n,'.$ORD_VAL;
			$sql = "insert into queues_details (id,keyword,data,flags) values ('$QNUM[$i]','member','$DATA',$ORD_VAL);";
			error_log($sql);
			WLOG( sprintf( '[MOD_QUEU %s:%s] %s', $MASTER_EXT_ID[$i], $QUEUE[$i], $sql ) );

			$res = mysqli_multi_query($conn, $sql);

			$UPDATE_T_QE =  $UPDATE_T_QE."'".$QE_ID[$i]."'".",";
			usleep(1000);
		}

		$NEWLIST = rtrim($UPDATE_T_QE, ", ");
		WLOG( sprintf( '[MOD_QUEU %s] %s',  '-----------', $NEWLIST ) );
***/

		mysqli_close ( $conn );

		usleep(1000);

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
			'logisoft123',
			'asterisk',
			'3306');

		$MI_Q_LIST='';
		$UPDATE_T_QUEUE='';
		$sql='';
		for( $i=0; $i<$Q_CNT; $i++ )
		{
			$sql = $sql."insert into queues_details (id,keyword,data,flags) values ('$QUEUE[$i]','memberdelay','0','0');";
			$UPDATE_T_QUEUE =  $UPDATE_T_QUEUE."'".$QUEUE[$i]."'".",";
			$MI_Q_LIST =  $MI_Q_LIST."'".$MASTER_ID[$i]."'".",";
		}
		$NEWLIST2 = rtrim($UPDATE_T_QUEUE, ", ");
		$MIQLIST = rtrim($MI_Q_LIST, ", ");
		error_log($sql);
		WLOG( sprintf( '[ADD_QUEU ----:---] %s %s', $MI_Q_LIST, $sql ) );
		//$res = mysqli_multi_query($conn, $sql);

/***
		$UPDATE_T_QUEUE='';

		for( $i=0; $i<$Q_CNT; $i++ )
		{
			$sql = "insert into queues_details (id,keyword,data,flags) values ('$QUEUE[$i]','memberdelay','0','0');";
			error_log($sql);
			WLOG( sprintf( '[ADD_QUEU %s:%s] %s', $MASTER_ID[$i], $QUEUE[$i], $sql ) );

			$res = mysqli_query($conn, $sql);

			$UPDATE_T_QUEUE =  $UPDATE_T_QUEUE."'".$QUEUE[$i]."'".",";

			usleep(1000);
		}

		$NEWLIST2 = rtrim($UPDATE_T_QUEUE, ", ");
		WLOG( sprintf( '[ADD_QUEU %s] %s',  '-----------', $NEWLIST2 ) );
***/

		$sql = "update T_Q_EXTENSION set is_send_pbx='Y', request_pbx_datetime=now() where q_ext_id in ( $NEWLIST );update T_QUEUE set is_send_pbx='Y', request_pbx_datetime=now() where q_num in ( $NEWLIST2 );";

		WLOG( sprintf( '[ADD_QUEU %s] %s',  '-----------', $sql ) );

		//$res = mysqli_multi_query($conn_db, $sql);

		mysqli_close ( $conn_db );

		usleep(1000);

                $conn_db = mysqli_connect(
                  '121.254.239.50',
                  'nautes',
                  'Nautes12@$',
                  'LOGI',
                  '3306');

		$RINGO_Q	= array();
		$RINGO_NAME	= array();
		$RINGO_CID	= array();
		$RINGO_MAD	= array();
		$RINGO_CNT	= 0;

		$RCI_LIST	='';
		$RMI_LIST	='';

		//$sql = "select use_queue, ringo from T_COMPANY where is_send_pbx='N' and ringo !='' order by is_use_q;";
		$sql = "select use_queue, ringo, company_id, master_id from T_COMPANY where company_level=2 and use_queue !='' order by use_queue;";

		error_log($sql);
		WLOG( sprintf( '[FIND_RNG %s] %s', '-----------', $sql ) );

		$res = mysqli_multi_query($conn_db, $sql);

		while( $row = mysqli_fetch_array($res) )
		{
			array_push( $RINGO_Q, $row[0] );
			array_push( $RINGO_NAME, $row[1] );
			array_push( $RINGO_CID, $row[2] );
			array_push( $RINGO_MAD, $row[3] );

			$RINGO_CNT        = count($RINGO_Q);

			WLOG( sprintf( '[FIND_RNG %s:%s] Find Q %s RINGO %s COUNT %s', $row[3], $row[2], $row[0], $row[1], $RINGO_CNT ) );
		}

		$sql='';
		for( $i=0; $i<$RINGO_CNT; $i++ )
		{
			$sql = $sql."delete from queues_details where id='$RINGO_Q[$i]' and keyword = 'music';";
			$RCI_LIST =  $RCI_LIST."'".$[RINGO_CID$i]."'".",";
			$RMI_LIST =  $RMI_LIST."'".$[RINGO_MAD$i]."'".",";
		}
		$RCILIST = rtrim($RCI_LIST, ", ");
		$RMILIST = rtrim($RMI_LIST, ", ");

		error_log($sql);
		WLOG( sprintf( '[QUE_RING ----:---] %s %s %s', $RMILIST, $RCILIST,  $sql ) );
		//$res = mysqli_multi_query($conn, $sql);

/***
		for( $i=0; $i<$RINGO_CNT; $i++ )
		{
			$sql = "delete from queues_details where id='$RINGO_Q[$i]' and keyword = 'music';";
			error_log($sql);
			WLOG( sprintf( '[QUE_RING %s:%s] %s', $RINGO_MAD[$i], $RINGO_CID[i],  $sql ) );

			$res = mysqli_query($conn, $sql);
		}
***/

		mysqli_close ( $conn );

		usleep(1000);

		$conn = mysqli_connect(
			'127.0.0.1',
			'root',
			'logisoft123',
			'asterisk',
			'3306');

		$sql='';
		for( $i=0; $i<$RINGO_CNT; $i++ )
		{
			if( $RINGO_NAME[$i] != '' )
			{
				$sql = $sql."insert into queues_details (id,keyword,data,flags) values ('$RINGO_Q[$i]','music','$RINGO_NAME[$i]','0');";
			}
		}
		error_log($sql);
		WLOG( sprintf( '[QUE_RING ----:---] %s %s %s', $RMILIST, $RCILIST,  $sql ) );

		//$res = mysqli_multi_query($conn, $sql);

/**
		for( $i=0; $i<$RINGO_CNT; $i++ )
		{
			if( $RINGO_NAME[$i] != '' )
			{
				$sql = "insert into queues_details (id,keyword,data,flags) values ('$RINGO_Q[$i]','music','$RINGO_NAME[$i]','0');";
				error_log($sql);
				WLOG( sprintf( '[QUE_RING %s:%s] %s', $RINGO_MAD[$i], $RINGO_CID[i],  $sql ) );

				$res = mysqli_query($conn, $sql);
			}
		}
**/

		mysqli_close ( $conn );
		mysqli_close ( $conn_db );

		system( "fwconsole reload", $result );

		WLOG( sprintf( '[IPPBXSET %s] %s', '-----------', 'fwconsole reload' ) );
	

		WLOG( sprintf( '[IPPBXSET %s] %s', '-----------', 'END   =======================================================================================================' ) );

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

		error_log(sprintf('call addQNum_all.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));
		WLOG( sprintf( '[IPPBXSET CALL] insKeyNum.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST ) );

		if (is_object($JSON_REQUEST)) {

			$JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
			if (isset($JSON_REQUEST->REQ)) {
				if ($JSON_REQUEST->REQ == 'ADD_Q') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE    = addQNum( );
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
