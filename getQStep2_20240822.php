<?php

include 'plog.php';

	function get_q_step2( 	$COMPANY_ID, $DID, $CID, $TYPE, $OPTION, $MYQ, $QLIST, $TRCOUNT, $TRORDER ) 
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

		$sql = "";

		$RESULT = new stdClass();

		$RESULT->re_call_q 	= '0';
		$RESULT->re_call_ext 	= '';
		$RESULT->re_call_ext_st	= '';

		$RESULT->re_call 	= 'N';
		$RESULT->my_q_find 	= 'N';

                $RESULT->tr_next_q      = '0';

		if( $TYPE == 'GET_CC_SMY' )
		{

			if( $OPTION == 'N' ) // 착신 안함(내콜센터로 들어옴 )
			{
				$RESULT->tr_next_q    	= $MYQ;
				$RESULT->my_q_find	='Y';

				SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s', $DID, $CID, $RESULT->tr_next_q ) );
			}
			else if( $OPTION == 'T' ) //시간차 착신
			{
				if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, '#########################3' ) );

					$RESULT->tr_next_q    	= $MYQ;
					$RESULT->tr_order_id  	= 1;
					$RESULT->my_q_find	='Y';

					/**
					//$sql = "select q_num from T_Q_EXTENSION where q_num='$MYQ' and call_status=0 and is_status=1 group by q_num limit 1;";
					$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 e.is_status=1 group by q_num limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

					if( $row = mysqli_fetch_array($res) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    	= $row[0];
						$RESULT->tr_order_id  	= 1;
						$RESULT->my_q_find	='Y';

						SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
					}
					$count= mysqli_num_rows($res);
					if( $RESULT->my_q_find == 'N' )
					{
						for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$TRORDER-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
						}
						$count= mysqli_num_rows($res);
					}
					**/
				}
				else 
				{	
					if( $TRORDER == $TRCOUNT )
					{
						$NEWLIST = $QLIST[$TRORDER-1];
						$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 and m.transfer_q_number='$NEWLIST' limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s TRORDER:%s NORDER:%s', $DID, $CID, $row[0], $row[1], $RESULT->tr_order_id ) );
						}
						else
						{
							$RESULT->tr_next_q     = $MYQ;
							$RESULT->tr_order_id 	= 1;
							$RESULT->my_q_find	='Y';
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );

							/**
							$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

							if( $row = mysqli_fetch_array($res) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    	= $row[0];

								SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
							}
							**/
						}
						$count= mysqli_num_rows($res);
					}
					else
					{
						for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$TRORDER-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
						SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, '@@@@@@@@@@@@@@@@@@@@@@@@@@' ) );

						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
						}
						else
					{
							$RESULT->tr_next_q     = $MYQ;
							$RESULT->tr_order_id 	= 1;
							$RESULT->my_q_find	='Y';
							SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );
						}
						$count= mysqli_num_rows($res);
					}
				}
			}
			else //직접 착신 ( GET_CC_SMY )
			{
				if( $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
				}

				for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
				}
				for( $j=0; $j<$TRORDER-1; $j++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
				}

				$NEWLIST = rtrim($TRQLIST, ", ");
				SLOG( sprintf( '[GET_CC_SMYT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

				$sql = "select m.transfer_q_number, m.transfer_order_num from T_MY_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_CC_SMYT %s:%s] %s', $DID, $CID, $sql ) );
				$res = mysqli_query($conn, $sql);

				if( $row = mysqli_fetch_array($res) )
				{
					//error_log($row[0]);

					$RESULT->tr_next_q    = $row[0];
					$RESULT->tr_order_id  = $row[1];
					$RESULT->tr_order_id++;

					SLOG( sprintf( '[GET_CC_SMYT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
				}
				else
				{
					$NEWLIST = $QLIST[$TRORDER-1];
	
					$RESULT->tr_next_q    = $NEWLIST;
					$RESULT->tr_order_id  = $TRORDER;
					$RESULT->tr_order_id++;
                                
					SLOG( sprintf( '[GET_CC_SMYT %s:%s] SMY CAHNNEL BUSY NEXTQ:%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );
				}
				$count= mysqli_num_rows($res);
			}

		}
		if( $TYPE == 'GET_CC_DIR' )
		{
			if( $OPTION == 'N' ) // 착신 안함(내콜센터로 들어옴 )
			{
				$RESULT->tr_next_q    	= $MYQ;

				SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s', $DID, $CID, $RESULT->tr_next_q ) );
			}
			else if( $OPTION == 'T' ) //시간차 착신
			{
				if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, '#########################3' ) );

					$RESULT->tr_next_q    	= $MYQ;
					$RESULT->tr_order_id  	= 1;
					$RESULT->my_q_find	='Y';

					/***
					//$sql = "select q_num from T_Q_EXTENSION where q_num='$MYQ' and call_status=0 group by q_num limit 1;";
					$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

					if( $row = mysqli_fetch_array($res) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    	= $row[0];
						$RESULT->tr_order_id  	= 1;
						$RESULT->my_q_find	='Y';

						SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
					}
					$count= mysqli_num_rows($res);
					if( $RESULT->my_q_find == 'N' )
					{
						for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$TRORDER-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
						}
						$count= mysqli_num_rows($res);
					}
					***/
				}
				else 
				{	
					if( $TRORDER == $TRCOUNT )
					{
						$NEWLIST = $QLIST[$TRORDER-1];

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 and m.transfer_q_number='$NEWLIST' limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s TRORDER:%s NORDER:%s', $DID, $CID, $row[0], $row[1], $RESULT->tr_order_id ) );
						}
						else
						{
							$RESULT->tr_next_q     = $MYQ;
							$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";
							$RESULT->tr_order_id 	= 1;

							error_log($sql);
							SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

							if( $row = mysqli_fetch_array($res) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    	= $row[0];

								SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
							}
						}
						$count= mysqli_num_rows($res);
					}
					else
					{
						for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
						}
						for( $j=0; $j<$TRORDER-1; $j++ )
						{
							$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
						}
						$NEWLIST = rtrim($TRQLIST, ", ");
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

						$sql = "select m.transfer_q_number, m.transfer_order_num from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

						error_log($sql);
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
						SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, '@@@@@@@@@@@@@@@@@@@@@@@@@@' ) );

						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    = $row[0];
							$RESULT->tr_order_id  = $row[1];
							$RESULT->tr_order_id++;

							SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
						}
						$count= mysqli_num_rows($res);
					}
				}
			}
			else //직접 착신( GET_CC_DIR )
			{
				if( $TRORDER > $TRCOUNT )
				{
					$TRORDER = 1;
				}

				for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
				}
				for( $j=0; $j<$TRORDER-1; $j++ )
				{
					$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
				}

				$NEWLIST = rtrim($TRQLIST, ", ");
				SLOG( sprintf( '[GET_CC_DIRT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

				$sql = "select m.transfer_q_number, m.transfer_order_num from T_DIRECT_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_CC_DIRT %s:%s] %s', $DID, $CID, $sql ) );
				$res = mysqli_query($conn, $sql);

				if( $row = mysqli_fetch_array($res) )
				{
					//error_log($row[0]);

					$RESULT->tr_next_q    = $row[0];
					$RESULT->tr_order_id  = $row[1];
					$RESULT->tr_order_id++;

					SLOG( sprintf( '[GET_CC_DIRT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
				}
				else
				{
					$NEWLIST = $QLIST[$TRORDER-1];
	
					$RESULT->tr_next_q    = $NEWLIST;
					$RESULT->tr_order_id  = $TRORDER;
					$RESULT->tr_order_id++;
                                
					SLOG( sprintf( '[GET_CC_DIRT %s:%s] DIR CAHNNEL BUSY NEXTQ:%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );
				}
				$count= mysqli_num_rows($res);
			}
		}
		else if( $TYPE == 'GET_CC_SEQ' )
		{
			if( $TRORDER == 0 || $TRORDER > $TRCOUNT )
			{
				$TRORDER = 1;
				SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $MYQ ) );

                                        $sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";

				error_log($sql);
				SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
				$res = mysqli_query($conn, $sql);

				if( $row = mysqli_fetch_array($res) )
				{
					//error_log($row[0]);

					$RESULT->tr_next_q      = $row[0];
					$RESULT->tr_order_id    = 1;
					$RESULT->my_q_find      ='Y';

					SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
				}
				$count= mysqli_num_rows($res);

				if( $RESULT->my_q_find == 'N' )
				{
					//SLOG( sprintf( '[GET_CC_SEQT %s:%s] %ss', $DID, $CID, '################################' ) );
					for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
					}
					for( $j=0; $j<$TRORDER-1; $j++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
					}
					$NEWLIST = rtrim($TRQLIST, ", ");
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

					$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

					if( $row = mysqli_fetch_array($res) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    = $row[0];
						$RESULT->tr_order_id  = $row[1];
						$RESULT->tr_order_id++;

						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
					}
					else
					{
						$RESULT->tr_next_q    = $MYQ;
						$RESULT->tr_order_id 	= 1;
						SLOG( sprintf( '[GET_CC_SEQT %s:%s] ALL CAHNNEL BUSY -> NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );
					}
					$count= mysqli_num_rows($res);
				}

			}
			else 
			{	
				if( $TRORDER == $TRCOUNT )
				{
					$NEWLIST = $QLIST[$TRORDER-1];
					$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 and m.transfer_q_number='$NEWLIST' limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

					if( $row = mysqli_fetch_array($res) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    = $row[0];
						$RESULT->tr_order_id  = $row[1];
						$RESULT->tr_order_id++;

						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ:%s TRORDER:%s NORDER:%s', $DID, $CID, $row[0], $row[1], $RESULT->tr_order_id ) );
					}
					else
					{
						$SEQ_RE_CHECK = 'N';

						$RESULT->tr_next_q     = $MYQ;
						$sql = "select q.q_num from T_Q_EXTENSION AS q INNER JOIN T_EXTENSION AS e on q.ext_number = e.ext_number where q.q_num='$MYQ' and e.call_status=0 and e.is_status=1 group by q_num limit 1;";
						$RESULT->tr_order_id 	= 1;

						error_log($sql);
						SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
						$res = mysqli_query($conn, $sql);

						if( $row = mysqli_fetch_array($res) )
						{
							//error_log($row[0]);

							$RESULT->tr_next_q    	= $row[0];

							SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $row[0], $RESULT->tr_order_id ) );
						}
						else
						{
							$SEQ_RE_CHECK = 'Y';
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] SEQUENCD RE CHECK:%s', $DID, $CID, $RESULT->tr_seq_check ) );
						}
						if( $SEQ_RE_CHECK == 'Y' )
						{
							for( $i=0; $i<$TRCOUNT; $i++ )
							{
								$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
							}
							$NEWLIST = rtrim($TRQLIST, ", ");
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] RE CHECK TRQLIST:%s', $DID, $CID, $NEWLIST ) );

							$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

							error_log($sql);
							SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
							$res = mysqli_query($conn, $sql);

							if( $row = mysqli_fetch_array($res) )
							{
								//error_log($row[0]);

								$RESULT->tr_next_q    = $row[0];
								$RESULT->tr_order_id  = $row[1];
								$RESULT->tr_order_id++;

								SLOG( sprintf( '[GET_CC_SEQT %s:%s] RE CHECK NEXTQ:%s TRORDER:%s', $DID, $CID, $row[0], $row[1] ) );
							}
							else
							{
								$RESULT->tr_next_q    = $MYQ;
								$RESULT->tr_order_id 	= 1;
								SLOG( sprintf( '[GET_CC_SEQT %s:%s] RE CHECK -> ALL CAHNNEL BUSY -> NEXTQ(MYQ):%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );
							}
						}
					}
					$count= mysqli_num_rows($res);
				}
				else
				{
					for( $i=$TRORDER-1; $i<$TRCOUNT; $i++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$i]."'".",";
					}
					for( $j=0; $j<$TRORDER-1; $j++ )
					{
						$TRQLIST =  $TRQLIST."'".$QLIST[$j]."'".",";
					}
					$NEWLIST = rtrim($TRQLIST, ", ");
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] TRQLIST:%s', $DID, $CID, $NEWLIST ) );

					$sql = "select m.transfer_q_number, m.transfer_order_num from T_SEQUENCE_TRANSFER_CALL AS m INNER JOIN T_Q_EXTENSION AS e on m.transfer_q_number=e.q_num INNER JOIN T_EXTENSION AS t on e.ext_number=t.ext_number where m.company_id=$COMPANY_ID and t.call_status=0 and t.is_status=1 group by e.q_num order by field( m.transfer_q_number, $NEWLIST ) limit 1;";

					error_log($sql);
					SLOG( sprintf( '[GET_CC_SEQT %s:%s] %s', $DID, $CID, $sql ) );
					$res = mysqli_query($conn, $sql);

					if( $row = mysqli_fetch_array($res) )
					{
						//error_log($row[0]);

						$RESULT->tr_next_q    = $row[0];
						$RESULT->tr_order_id  = $row[1];
						$RESULT->tr_order_id++;

						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ:%s TRORDER:%s NORDER:%s', $DID, $CID, $row[0], $row[1], $RESULT->tr_order_id ) );
					}
					else
					{
						$RESULT->tr_next_q     = $MYQ;
						$RESULT->tr_order_id 	= 1;
						SLOG( sprintf( '[GET_CC_SEQT %s:%s] NEXTQ(TRQ):%s TRORDER:%s', $DID, $CID, $RESULT->tr_next_q, $RESULT->tr_order_id ) );
					}
					$count= mysqli_num_rows($res);
				}
			}
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
				if ($JSON_REQUEST->REQ == 'GET_Q_STEP2') 
				{
					$JSON_API_RESULT->JSON_RESULT->CODE	= "TRY_CALL_PROCEDURE";

					$JSON_API_RESULT->JSON_RESULT->MESSAGE	= get_q_step2( 	$JSON_REQUEST->COMPANY_ID,
												$JSON_REQUEST->DID ,
												$JSON_REQUEST->CID ,
												$JSON_REQUEST->TYPE,
												$JSON_REQUEST->OPTION, 
												$JSON_REQUEST->MYQ, 
												$JSON_REQUEST->QLIST, 
												$JSON_REQUEST->TRCOUNT, 
												$JSON_REQUEST->TRORDER );

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
