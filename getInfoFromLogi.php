<?php

include 'plog.php';

	function get_agent( $COMP_ID, $ARS_ID, $SCN_ID, $ROUTING, $DID, $CID ) 
	{ 
		SLOG( sprintf( '[GET_AGENT %s:%s] START =======================================================================================================', $DID, $CID ) );

		$RESULT = new stdClass();

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

                $sql = "";

/****
		$time1 = strtotime('2024-06-26 08:31:00');
		$time2 = strtotime('2024-06-26 10:30:00');

		$diffInSeconds = $time2 - $time1;

		// 시간, 분, 초 단위로 변환
		$hours = floor($diffInSeconds / 3600);
		$minutes = floor(($diffInSeconds % 3600) / 60);
		$seconds = $diffInSeconds % 60;

		SLOG( sprintf( '[GET_AGENT %s:%s] %d', $DID, $CID, $diffInSeconds  ) );

***/

                if( $ROUTING == '0' )
                {
			$sql = "select q_group_num from T_Q_GROUP where q_company_id=$ARS_ID limit 1;";
                }
		else if( $ROUTING == '1' )
		{
			if( $SCN_ID == '0' )
			{
				//$sql = "select e.ext_number, c.q_group_num, b.q_group_id from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_GROUP AS c on b.q_group_id = c.q_group_id inner join T_Q_EXTENSION AS e on c.q_group_num = e.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and e.call_status=0 and f.scn_id=$SCN_ID and c.is_use='Y' group by b.q_group_id order by a.try_datetime;";
				$sql = "select b.q_group_id, c.q_num, c.q_num from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_EXTENSION AS c on b.q_group_id = c.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and f.scn_id=$SCN_ID group by b.q_group_id order by a.try_datetime;";
			}
			else
			{
				//$sql = "select e.ext_number, c.q_group_num, b.q_group_id from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_GROUP AS c on b.q_group_id = c.q_group_id inner join T_Q_EXTENSION AS e on c.q_group_num = e.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and e.call_status=0 and f.scn_id=$SCN_ID and c.is_use='Y' group by b.q_group_id order by a.try_datetime;";
				$sql = "select b.q_group_id, c.q_num, c.q_num from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_EXTENSION AS c on b.q_group_id = c.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and f.scn_id=$SCN_ID group by b.q_group_id order by a.try_datetime;";
			}
		}
		else if( $ROUTING == '2' )
		{
			if( $SCN_ID == '0' )
			{
				//$sql = "select e.ext_number, c.q_group_num, b.q_group_id from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_GROUP AS c on b.q_group_id = c.q_group_id inner join T_Q_EXTENSION AS e on c.q_group_num = e.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and e.call_status=0 and f.scn_id=$SCN_ID and c.is_use='Y' group by b.q_group_id order by a.try_datetime;";
				$sql = "select b.q_group_id, c.q_num, c.q_num from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_EXTENSION AS c on b.q_group_id = c.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and f.scn_id=$SCN_ID group by b.q_group_id order by a.try_datetime;";
			}
			else
			{
				//$sql = "select e.ext_number, c.q_group_num, b.q_group_id from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_GROUP AS c on b.q_group_id = c.q_group_id inner join T_Q_EXTENSION AS e on c.q_group_num = e.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and e.call_status=0 and f.scn_id=$SCN_ID and c.is_use='Y' group by b.q_group_id order by a.try_datetime;";
				$sql = "select b.q_group_id, c.q_num, c.q_num from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_EXTENSION AS c on b.q_group_id = c.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and f.scn_id=$SCN_ID group by b.q_group_id order by a.try_datetime;";
			}
		}
		else if( $ROUTING == '3' )
		{
			if( $SCN_ID == '0' )
			{
				//$sql = "select e.ext_number, c.q_group_num, b.q_group_id from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_GROUP AS c on b.q_group_id = c.q_group_id inner join T_Q_EXTENSION AS e on c.q_group_num = e.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and e.call_status=0 and f.scn_id=$SCN_ID and c.is_use='Y' group by b.q_group_id order by a.try_datetime;";
				$sql = "select b.q_group_id, c.q_num, c.q_num from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_EXTENSION AS c on b.q_group_id = c.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and f.scn_id=$SCN_ID group by b.q_group_id order by a.try_datetime;";
			}
			else
			{
				//$sql = "select e.ext_number, c.q_group_num, b.q_group_id from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_GROUP AS c on b.q_group_id = c.q_group_id inner join T_Q_EXTENSION AS e on c.q_group_num = e.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and e.call_status=0 and f.scn_id=$SCN_ID and c.is_use='Y' group by b.q_group_id order by a.try_datetime;";
				$sql = "select b.q_group_id, c.q_num, c.q_num from T_GROUP_ARS AS a inner join T_GROUP_ARS_LIST AS b on a.id = b.group_ars_id inner join T_Q_EXTENSION AS c on b.q_group_id = c.q_num inner join T_GROUP_ARS_SCENARIO AS f on f.group_ars_id = a.id  where a.company_id=$ARS_ID and f.scn_id=$SCN_ID group by b.q_group_id order by a.try_datetime;";
			}
		}

		error_log($sql);
		SLOG( sprintf( '[GET_AGENT %s:%s] %s', $DID, $CID, $sql ) );

		$res = mysqli_query($conn, $sql);

		$nIdx=0;
		$isData=0;

		$PHONE_ID[] = new stdClass();
                $IP_PHONE[] = new stdClass();
                $CALL_ORDER[] = new stdClass();
                $LASTPID=0;
		$STR_NUM="";

		$FIND_PHONE		= "";
		$RESULT->PHONE		= "";

		SLOG( sprintf( '[GET_AGENT %s:%s] get T_GROUP_USER T_GROUP_USER T_PHONE T_USER_PHONE T_USER_LIST ==============================================', $DID, $CID ) );

		if( $ROUTING == '0' )
		{
			if( $row = mysqli_fetch_array($res) ) 
			{
				error_log($row[0]);
				$isData=1;
				$FIND_PHONE	= $row[0];
				$RESULT->PHONE	= $FIND_PHONE;
			}
		}
		else if( $ROUTING == '1' || $ROUTING == '2' )
		{
			while( $row = mysqli_fetch_array($res) ) 
			{
				error_log($row[1]);
				$IP_PHONE[$nIdx++] = $row[1];
				$isData=1;
				$FIND_PHONE	= $row[1];
			}

			$count= mysqli_num_rows($res) ;

			for( $i=0; $i < $nIdx; $i++ )
			{
				$STR_NUM = $STR_NUM."'".$IP_PHONE[$i]."'".",";
			}

			$RESULT->PHONE		= $FIND_PHONE;
			SLOG( sprintf( '[GET_AGENT %s:%s] PHONE_NUMS :%s : RESULT :%s', $DID, $CID, $STR_NUM, $RESULT->PHONE ) );
		}
		else if( $ROUTING == '3' )
		{
			while( $row = mysqli_fetch_array($res) )
			{
				/**
				error_log($row[0]);
				error_log($row[1]);
				error_log($row[2]);
				error_log($row[3]);
				SLOG($row[0]);
				SLOG($row[1]);
				SLOG($row[2]);
				SLOG($row[3]);
				**/

				SLOG( sprintf( '[GET_AGENT %s:%s] PHONE_ID :%5.5s | IP_PHONE :%5.s | ORDER:%5.5s | LAST_PID:%5.5s', $DID, $CID, $row[1], $row[0], $row[2], $row[3] ) );

				$PHONE_ID[$nIdx]   = $row[0];
				$IP_PHONE[$nIdx]   = $row[1];
				$CALL_ORDER[$nIdx++] = $row[2];
				$LAST_PID           = $row[3];
				$isData=1;
				$FIND_PHONE	= $row[1];
			}

			$count= mysqli_num_rows($res) ;

		
			for( $i=0; $i<$nIdx; $i++ )
			{
				if( $PHONE_ID[$i] == $LAST_PID )
				break;
			}
			//error_log('======'.$CALL_ORDER[$i] );
			$LAST_NUM = $CALL_ORDER[$i];
			
			$ORDER = ($LAST_NUM-1)%$nIdx;
			
			for( $i=0; $i<$nIdx; $i++ )
			{
				$j=(($ORDER+$CALL_ORDER[$i])%$nIdx)+1;
				$IDX=$j-1;
				$STR_NUM = $STR_NUM."'".$IP_PHONE[$IDX]."'".",";
			}
			SLOG( sprintf( '[GET_AGENT %s:%s] PHONE_NUMS :%s', $DID, $CID, $STR_NUM ) );

			$RESULT->PHONE		= $FIND_PHONE;
		}

		if( $isData == 1 )
		{
			$sql = "update T_Q_GROUP set try_datetime=now() where q_company_id='$ARS_ID' and q_group_num='$RESULT->PHONE';";

                        error_log($sql);
                        SLOG( sprintf( '[GET_AGENT %s:%s] %s', $DID, $CID, $sql ) );
                        $res = mysqli_query($conn, $sql);
		}

		mysqli_close ( $conn );

		SLOG( sprintf( '[GET_AGENT %s:%s] END  PHONE %s ==============================================================================================', $DID, $CID, $RESULT->PHONE ) );
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
				if ($JSON_REQUEST->REQ == 'GET_AGENT') 
				{
				   	$JSON_API_RESULT->JSON_RESULT->CODE       = "TRY_CALL_PROCEDURE";

				   	$JSON_API_RESULT->JSON_RESULT->MESSAGE          = get_agent( 
												$JSON_REQUEST->COMP_ID, 
												$JSON_REQUEST->ARS_ID ,
												$JSON_REQUEST->SCN_ID ,
												$JSON_REQUEST->ROUTING,
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
