<?php

// TSPC 087 database connection options
$database_TSPC_087 = 'TPCDB';
$user_TSPC_087 = 'db2inst1';
$password_TSPC_087 = 'passw0rd';
$hostname_TSPC_087 = '10.91.237.54';
$port_TSPC_087 = 50000;
$connection_TSPC_087 = db2_connect("DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database_TSPC_087;HOSTNAME=$hostname_TSPC_087;PORT=$port_TSPC_087;PROTOCOL=TCPIP;UID=$user_TSPC_087;PWD=$password_TSPC_087;", '', '');
if (!$connection_TSPC_087)
    exit("Database TPCDB connection failed!");
