<?php
      function WORK_CONDITION_CHECK ()
        {
                $RESULT ="";
                $HOLIDAY =0;

		$JSON_RESULT = new \stdClass();

                $WORK_CONDITION = 'WORK_SERVICE';
                $WORK_FLAG = 'WORK_IN_DOING';
                $HOLIDAY_MENT ="";

                $conn = mysqli_connect( '127.0.0.1', 'ivr', 'ivr123!', 'vars','3306');

                if( $conn )
                        error_log( "connect OK" );
                else
                        error_log ("connect NOT OK" );

                date_default_timezone_set('Asia/Seoul');

                $Tdate =date('Y/m/d', time());
                $Sdate =date('Y-m-d', time());
                $Ttime =date('H:i', time());

                $sql = "SELECT dirname,fname  from holiday where dates = '$Tdate' order by dates desc limit 1 ; ";

                $result = mysqli_query($conn,$sql );

                if( $row=mysqli_fetch_row($result) )
                {
                        error_log ( sprintf("@@@@@@file %s   %s \n", $row[0] ,$row[1] ) );

                        $HOLIDAY =1 ;
                        $WORK_CONDITION = 'CUSTOM_NO_WORK_DAY';
                        //$HOLIDAY_MENT = $row[0] . $row[1] ;
                        $HOLIDAY_MENT = $row[1] ;

                        error_log ("@@@@@@@@@@@ NO_WORK_DAY" );
                } else {
                       // work day
                        $yoil = array("su","mo","tu","we","th","fr","sa");
                        $today_yoil = $yoil[date('w', strtotime($Sdate))];

			$WORK_WEEK = strtoupper( $today_yoil );

                        $sql2 = sprintf("select %s ,gg from  daily_work  where checks = 'true' ; ", $today_yoil ) ;


                        $result = mysqli_query($conn,$sql2 );

                        if( $row=mysqli_fetch_row($result) )
                        {
                                $SWORK_TIME = substr( $row[0],0, 5 );
                                $EWORK_TIME = substr( $row[0],6, 5 );

                                $SLUNCH_TIME = substr( $row[1],0, 5 );
                                $ELUNCH_TIME = substr( $row[1],6, 5 );

                                if ( $Ttime >  $SLUNCH_TIME  &&  $Ttime  < $ELUNCH_TIME  ) {
                                        $WORK_CONDITION = 'LUNCH_TIME';
                                }

                                if ( $Ttime < $SWORK_TIME ) {
                                        $WORK_CONDITION = 'WORK_OVER';
                                        $WORK_FLAG = 'WORK_BEFORE';
                                }

                                if ( $Ttime > $EWORK_TIME ) {
                                        $WORK_CONDITION = 'WORK_OVER';
                                        $WORK_FLAG = 'WORK_AFTER';
                                }
                        }

                        error_log (sprintf ("@@@@@@@@@@@@@@@@ week %s, work time %s - %s  %s  %s \n", $today_yoil,  $SWORK_TIME , $EWORK_TIME , $WORK_CONDITION ,  $WORK_FLAG  ) );

                }



                mysqli_close ( $conn );
		$JSON_RESULT->WORK_CONDITION = $WORK_CONDITION;
		$JSON_RESULT->WORK_FLAG = $WORK_FLAG;
		$JSON_RESULT->HOLIDAY_MENT = $HOLIDAY_MENT;
		$JSON_RESULT->WORK_WEEK = $WORK_WEEK;



                return $JSON_RESULT;


        }

	
       $RAW_POST_DATA = file_get_contents("php://input");

        $args = new stdClass();
        if (strlen($RAW_POST_DATA) > 0) {
                $args->JSON_REQUEST = $RAW_POST_DATA;
        } else {
                $args = json_decode(json_encode($_REQUEST), FALSE);
        }

        error_log(sprintf('CALLBACK_LIST.php [%s]', print_r($args, true)));


        $JSON_API_RESULT = new stdClass();

        $JSON_API_RESULT->JSON_REQUEST          = null;
        $JSON_API_RESULT->JSON_RESULT           = new stdClass();

        $JSON_API_RESULT->JSON_RESULT->CODE             = 200;

//        $JSON_API_RESULT->JSON_RESULT->MESSAGE  = "0";

        if (isset($args->JSON_REQUEST)) {

                $JSON_REQUEST = json_decode($args->JSON_REQUEST);
                if (!is_object($JSON_REQUEST)) {
                        $JSON_REQUEST = json_decode(stripslashes(base64_decode($args->JSON_REQUEST)));
                }

error_log(sprintf('+++++++++++++ WORK_CHECK.php args->JSON_REQUEST [%s]', $args->JSON_REQUEST));

                if (is_object($JSON_REQUEST)) {

                        $JSON_API_RESULT->JSON_REQUEST = $JSON_REQUEST;
                        if (isset($JSON_REQUEST->REQ)) {
                                if ($JSON_REQUEST->REQ == 'WORK_CONDITION_CHECK') 
				{

//                                           $JSON_API_RESULT->JSON_RESULT       = WORK_CONDITION_CHECK();
                                           $JSON_API_RESULT       = WORK_CONDITION_CHECK();

error_log(sprintf('$JSON_API_RESULT->JSON_RESULT = WORK_CONDITION_CHECK()  => RESULT : [%s]', json_encode($JSON_API_RESULT)));
                                        
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

	error_log(sprintf(' RESULT : [%s]', json_encode($JSON_API_RESULT)));
//    error_log(sprintf('api.php RESULT : [%s]', print_r($JSON_API_RESULT, true)));
	error_log (sprintf (" !!!!!!!!!!!!!!!!!!RETURN   \n" ) ); 

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
