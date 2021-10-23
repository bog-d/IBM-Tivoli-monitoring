<?php

// MAXIMO database connection options
$database_SCCD = '';
$user_SCCD = '';
$password_SCCD = '';
$hostname_SCCD = '';
$port_SCCD = 50005;
$time_start = microtime(true);
$connection_SCCD = db2_connect("DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database_SCCD;HOSTNAME=$hostname_SCCD;PORT=$port_SCCD;PROTOCOL=TCPIP;UID=$user_SCCD;PWD=$password_SCCD;", '', '');
//if (!$connection_SCCD)
//    exit("Database MAXDB76 connection failed!");
$time_end = microtime(true);
$time_SCCD = round(($time_end - $time_start)*1000);
