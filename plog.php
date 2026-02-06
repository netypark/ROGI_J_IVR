<?php

set_time_limit(0);
error_reporting(0);
date_default_timezone_set('Asia/Seoul');

        function SLOG($STR_LOG) {
                $LOG_FILE = sprintf(
                        "%s/%s/%s/%s/%s/%s.LOG",
                        '/home/asterisk/logs',
                        'SCN_PHP',
                        date("Y"),
                        date("m"),
                        date("d"),
                        date("Y_m_d").'_H'.date("H")
                );
                if(!file_exists($LOG_FILE)) mkdir(dirname($LOG_FILE),0777,true);

                $fp = fopen($LOG_FILE, 'a');
                fwrite($fp, sprintf("%s : %s\n", date("Y-m-d H:i:s.").gettimeofday()["usec"], $STR_LOG));
                fclose($fp);
        }

        function WLOG($STR_LOG) {
                $LOG_FILE = sprintf(
                        "%s/%s/%s/%s/%s.LOG",
                        '/home/asterisk/logs',
                        'WEB_PHP',
                        date("Y"),
                        date("m"),
                        //date("d"),
                        date("Y_m_d")
                );
                if(!file_exists($LOG_FILE)) mkdir(dirname($LOG_FILE),0777,true);

                $fp = fopen($LOG_FILE, 'a');
                fwrite($fp, sprintf("%s : %s\n", date("Y-m-d H:i:s.").gettimeofday()["usec"], $STR_LOG));
                fclose($fp);
        }
?>
