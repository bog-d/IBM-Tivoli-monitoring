<?php

// TBSM database connection options
$database_WHMSK = '';
$user_WHMSK = '';
$password_WHMSK = '';
$hostname_WHMSK = '';
$port_WHMSK = 50000;
$time_start = microtime(true);
$connection_WHMSK = db2_connect("DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database_WHMSK;HOSTNAME=$hostname_WHMSK;PORT=$port_WHMSK;PROTOCOL=TCPIP;UID=$user_WHMSK;PWD=$password_WHMSK;", '', '');
if (!$connection_WHMSK)
    exit("Database WHMSK connection failed!");
$time_end = microtime(true);
$time_WHMSK = round(($time_end - $time_start)*1000);
