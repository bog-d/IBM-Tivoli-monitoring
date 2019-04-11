<?php
/*
	by GDV
	2018 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');
?>
    <!DOCTYPE html>
    <html>
    <head>
        <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    </head>
    <body>
<?php
// common functions
require_once 'connections/TBSM.php';
include 'functions/tbsm.php';

$log_file = implode('/', explode('/', $_SERVER['SCRIPT_FILENAME'], -1))."/logs/incident_send_status.log";
$level = 0;					                    // iteration level
$path_history = [ ];		                    // array for history tree from parents to childs
$results = [ ];				                    // array for endpoint childs
$inc_result = null;                             // incident send result status for service

$updated = 0;
$inserted = 0;
$deleted = 0;

// initial time
$time = time();
file_put_contents($log_file, date('d.m.Y H:i:s', $time)."\n\n", FILE_APPEND | LOCK_EX);

// all services with template = PFR_SERVICE:Standard
$sel = "SELECT SERVICEINSTANCEID, SERVICEINSTANCENAME FROM TBSMBASE.SERVICEINSTANCE WHERE SERVICESLANAME = 'PFR_SERVICE:Standard'";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);

while ($row = db2_fetch_assoc($stmt)) {
    $level = 0;
    $path_history = [ ];
    $results = [ ];
    $inc_result = null;

    // tmp service or no child services
    if (strpos($row['SERVICEINSTANCENAME'], 'tmp') === 0 or !ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, $level, $path_history))
        $results[0]['service'] = $row['SERVICEINSTANCENAME'];

    // for each child service
    foreach ($results as $item) {
        // incident send status check
        $sel_loc = "SELECT INCIDENT_SEND FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME = '{$item['service']}'";
        $stmt_loc = db2_prepare($connection_TBSM, $sel_loc);
        $result_loc = db2_execute($stmt_loc);

        // for each location record
        while ($row_loc = db2_fetch_assoc($stmt_loc)) {
            if(!isset($inc_result))
                $inc_result = $row_loc['INCIDENT_SEND'];
            else {
                if($row_loc['INCIDENT_SEND'] != $inc_result) {
                    $inc_result = 0;
                    break 2;
                }
            }
        }
    }

    // no one incident send record
    if (!isset($inc_result))
        $inc_result = -200;

    // write into DB
    $sel_exists = "SELECT SERVICE_NAME FROM DB2INST1.PFR_MAINTSTATUS_4SERVICES WHERE SERVICE_NAME = '{$row['SERVICEINSTANCENAME']}'";
    $stmt_exists = db2_prepare($connection_TBSM, $sel_exists);
    $result_exists = db2_execute($stmt_exists);
    if (empty(db2_fetch_assoc($stmt_exists))) {
        $ins = "INSERT INTO DB2INST1.PFR_MAINTSTATUS_4SERVICES (SERVICE_NAME, INCIDENT_SEND, TIMESTAMP) VALUES ('{$row['SERVICEINSTANCENAME']}', {$inc_result}, {$time})";
        $inserted++;
    }
    else {
        $ins = "UPDATE DB2INST1.PFR_MAINTSTATUS_4SERVICES SET INCIDENT_SEND = {$inc_result}, TIMESTAMP = {$time} WHERE SERVICE_NAME = '{$row['SERVICEINSTANCENAME']}'";
        $updated++;
    }
    $stmt_ins = db2_prepare($connection_TBSM, $ins);
    $result_ins = db2_execute($stmt_ins);
}

// clear obsolete records
$del = "DELETE FROM DB2INST1.PFR_MAINTSTATUS_4SERVICES WHERE TIMESTAMP < {$time}";
$stmt_del = db2_prepare($connection_TBSM, $del);
$result_del = db2_execute($stmt_del);
$deleted = db2_num_rows($stmt_del);

// database connection close
db2_close($connection_TBSM);

// log output
file_put_contents($log_file, "Добавлено записей: {$inserted}\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "Обновлено записей: {$updated}\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "Удалено записей: {$deleted}\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "\nВремя работы скрипта: ".(time() - $time)." c\n\n", FILE_APPEND | LOCK_EX);

?>
    </body>
</html>

<!--
Запрос для получения статуса отправки инцидентов для заданного сервиса (в примере - PTK_SSOPR_ENV):

WITH
REC (CHILDINSTANCEKEY)
AS (
SELECT CHILDINSTANCEKEY
FROM TBSMBASE.SERVICEINSTANCERELATIONSHIP, TBSMBASE.SERVICEINSTANCE
WHERE SERVICEINSTANCENAME = 'PTK_SSOPR_ENV' AND SERVICEINSTANCEID = CHILDINSTANCEKEY
UNION ALL
SELECT REL.CHILDINSTANCEKEY
FROM TBSMBASE.SERVICEINSTANCERELATIONSHIP REL, REC
WHERE REC.CHILDINSTANCEKEY = REL.PARENTINSTANCEKEY
)
SELECT (CASE sum(INCIDENT_SEND)/count(INCIDENT_SEND) WHEN 1 THEN 1 WHEN -1 THEN -1 ELSE 0 END) AS INCIDENT_SEND_RES
FROM REC, TBSMBASE.SERVICEINSTANCE
INNER JOIN DB2INST1.PFR_LOCATIONS
ON SERVICEINSTANCENAME = SERVICE_NAME
WHERE REC.CHILDINSTANCEKEY = SERVICEINSTANCEID

Результат:
•	1 – отправка всех инцидентов включена
•	0 – отправка инцидентов включена/отключена частично
•	-1 – отправка всех инцидентов отключена
-->