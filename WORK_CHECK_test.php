<?php

	function HOLIDAY_CHECK ()
	{
		$RESULT ="";
		$HOLIDAY =0;
		
		$WORK_CONDITION = 'WORK_SERVICE';
		$WORK_FLAG = 'WORK_IN_DOING';
		$HOLIDAY_MENT ="";

                $conn = mysqli_connect(
                  'localhost',
                  'ivr',
                  'ivr123!',
                  'vars',
		  '3307');


                 if( $conn )
                        echo "connect OK";
                else
                        echo "connect NOT OK";


                echo  '<br>';

                date_default_timezone_set('Asia/Seoul');

                $Tdate =date('Y/m/d', time());
                $Ttime =date('H:i', time());


                $sql = "SELECT dirname,fname  from holiday where dates = '$Tdate' ; ";



                $result = mysqli_query($conn,$sql );
                echo   $sql ;


                // $total = mysqli_num_rows($qresult);

                if( $row=mysqli_fetch_row($result) )
                {
                        printf("file %s   %s \n", $row[0] ,$row[1] );

			$HOLIDAY =1 ;
			$WORK_CONDITION = 'NO_WORK_DAY';
			$HOLIDAY_MENT = $row[0] . $row[1] ;

                } else {
	

			// work day
			$yoil = array("su","mo","tu","we","th","fr","sa");
			$today_yoil = $yoil[date('w', strtotime($Tdate))];

			echo '---------------';
			echo $today_yoil;
			echo  '<br>';

			$sql2 = sprintf("select %s ,gg from  daily_work  where checks = 'true' ; ", $today_yoil ) ;


			$result = mysqli_query($conn,$sql2 );

			echo   $sql2 ;

			if( $row=mysqli_fetch_row($result) )
 			{
			  	printf(" work time %s    lunch %s \n", $row[0], $row[1]  );
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

			printf (" week %s, work time %s - %s  %s  %s \n", $today_yoil,  $SWORK_TIME , $EWORK_TIME , $WORK_CONDITION ,  $WORK_FLAG  );
			
		}



                mysqli_close ( $conn );


                return $RESULT;


	}
	HOLIDAY_CHECK();	

?>
