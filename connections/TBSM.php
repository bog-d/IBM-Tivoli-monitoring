<?php

// TBSM database connection options
$database_TBSM = '';
$user_TBSM = '';
$password_TBSM = '';
$hostname_TBSM = '';
$port_TBSM = 50000;
$time_start = microtime(true);
$connection_TBSM = db2_connect("DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database_TBSM;HOSTNAME=$hostname_TBSM;PORT=$port_TBSM;PROTOCOL=TCPIP;UID=$user_TBSM;PWD=$password_TBSM;", '', '');
if (!$connection_TBSM)
    exit("Database TBSM connection failed!");
$time_end = microtime(true);
$time_TBSM = round(($time_end - $time_start)*1000);
