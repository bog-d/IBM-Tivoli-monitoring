<?php
$sql_file = '../SCCD_trigger.sql'; 	// sql command file
$log_file_sql = '../logs/SCCD_sql.log'; // log file of sql queries
$report_busy_file = '../SCCD_trigger.rep'; // report update busy by another user info file
$undeleted = '../SCCD_trigger.sav';    // undeleted services with several parents
$log_regions = '../logs/SCCD_regions_check.log';   // log with situations download problem regions

// log to file and DB
// return empty string if success, error description if error
function log_write ($service, $output) {
    $log_file = '../logs/SCCD_trigger.log'; // log file
    $return_str = '';

    $connection_TBSM = $GLOBALS['connection_TBSM'];
    $connection_SCCD = $GLOBALS['connection_SCCD'];

    $acs_user = isset($_COOKIE['acsuser']) ? $_COOKIE['acsuser'] : 'Ошибка идентификации';
    $comment = isset($_COOKIE["comment"]) ? $_COOKIE["comment"] : '';
    $incident_write = isset($_COOKIE["incident"]) ? $_COOKIE["incident"] : '';

    $return_str = (file_put_contents($log_file, date('d.m.Y H:i:s') . "\t{$service}\t{$acs_user}\t{$output}\t{$comment}\t{$incident_write}\n", FILE_APPEND | LOCK_EX) ? '' :
                   'Ошибка записи в лог-файл {$log_file}. ');
    $sel_ins = "insert into DB2INST1.PFR_ACTIONS_LOG (SERVICE_NAME, DISPLAY_NAME, USER, OPERATION, DESCRIPTION, TIMESTAMP, INCIDENT, INITIATOR)
                            values ('{$service}', '', '{$acs_user}', '{$output}', '{$comment}', CURRENT TIMESTAMP, '{$incident_write}', '')";
    $stmt_ins = db2_prepare($connection_TBSM, $sel_ins);
    $return_str .= (db2_execute($stmt_ins) ? '' : 'Ошибка записи в лог-таблицу PFR_ACTIONS_LOG. ');

    return $return_str;
}
