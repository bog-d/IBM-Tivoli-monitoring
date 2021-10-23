<?php

// WHFED database connection options
$database_WHFED = '';
$user_WHFED = '';
$password_WHFED = '';
$hostname_WHFED = '';
$port_WHFED = 50000;
$connection_WHFED = db2_connect("DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database_WHFED;HOSTNAME=$hostname_WHFED;PORT=$port_WHFED;PROTOCOL=TCPIP;UID=$user_WHFED;PWD=$password_WHFED;", '', '');
if (!$connection_WHFED)
    exit("Database WHFED connection failed!");
