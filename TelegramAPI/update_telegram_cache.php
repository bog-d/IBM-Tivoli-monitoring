<?php
/*
	by GDV
	2018 - RedSys
*/ 
	header('Content-Type: text/html;charset=UTF-8');
	
	$temp_file = "telegram.tmp";
	$cache_file = "telegram.cache";

	require_once '../connections/WHFED.php';

	// last record detect
	$sel = "select max(WRITETIME)as MAX_WRITETIME from PFR_TELEGRAM_HISTORY";
	$stmt = db2_prepare($connection_WHFED, $sel);
	$result = db2_execute($stmt);
	$row = db2_fetch_assoc($stmt);
	$max_writetime = $row['MAX_WRITETIME'];

	// records for cache select and export
	$sel = "select WRITETIME, MESSAGE from PFR_TELEGRAM_HISTORY where WRITETIME <='$max_writetime' and UPLOADTIME is null";
	$stmt = db2_prepare($connection_WHFED, $sel);
	$result = db2_execute($stmt);

	file_put_contents($temp_file, '', LOCK_EX);
	while($row = db2_fetch_assoc($stmt))
		file_put_contents($temp_file, $row['MESSAGE']."\n", FILE_APPEND | LOCK_EX);
	rename($temp_file, $cache_file);

	// records for cache update
	// $sel = "update PFR_TELEGRAM_HISTORY set UPLOADTIME = '".date('Y-m-d H:i:s')."' where WRITETIME <='$max_writetime' and UPLOADTIME is null";
	// $stmt = db2_prepare($connection_WHFED, $sel);
	// $result = db2_execute($stmt);

	// database connection close
	db2_close($connection_WHFED);

?>
