<?php
header('Content-Type: application/json');

if (!isset($_POST))
    exit();

require_once('../connections/WHMSK.php');
require_once('../connections/WHFED.php');
require_once('../functions/utime.php');

$serial = $_POST['serial'];
$graph = $_POST['graph'];

// get data from AEL
$first_occurrence_str = '2019-04-15 13:59:00';
$node = 'SL10100008126i:LZ';
$object = 'SL10100008126I';
$ke = 'SL10100008126I';
$sit_code = 'LZ_DISK_SPACE_LOW';
$incidents = array (
    '27836293',
);

// get data from TEMS
$frequency = 300; // seconds
$checks = 1;

// datetime conversion
$first_occurrence_utime = utime($first_occurrence_str);
$start_sit_utime = $first_occurrence_utime - $frequency * $checks;
$start_graph_utime = $start_sit_utime - 3600;
$end_graph_utime = $start_sit_utime + 3600;
$start_sit_str = date('Y-m-d H:i:s', $start_sit_utime);

// get data from SCCD
$pause = 60; // seconds
$inc_create_utime = $first_occurrence_utime + $pause;
$inc_create_str = date('Y-m-d H:i:s', $inc_create_utime);

// get WH select
$start_time_db2 = '1'.date('ymdHis', $start_graph_utime);
$end_time_db2 = '1'.date('ymdHis', $end_graph_utime);
$wh_select = "select \"Timestamp\" as T, \"Disk_Used_Percent\" as VALUE
                from UMSK.\"KLZ_Disk\"
                where \"Timestamp\" >= '{$start_time_db2}' and \"Timestamp\" < '{$end_time_db2}' and \"System_Name\" = '{$node}' and \"Mount_Point\" = '/'
                order by \"Timestamp\" asc";

// operative graph
if ($graph == 'operative') {
    // metrics
    $stmt = db2_prepare($connection_WHMSK, $wh_select);
    $result = db2_execute($stmt);

    $start_written = $occurrence_written = false;
    while ($row = db2_fetch_assoc($stmt)) {
        $data[] = array(
            'time' => '20' . substr($row['T'], 1, 2) . '-' . substr($row['T'], 3, 2) . '-' . substr($row['T'], 5, 2) . ' ' .
                substr($row['T'], 7, 2) . ':' . substr($row['T'], 9, 2) . ':' . substr($row['T'], 11, 2),
            'value' => $row['VALUE'],
        );
    }
    db2_close($connection_WHMSK);

    // ajax return
    echo json_encode(array(
        'metrics' => $data,
        'start_sit' => $start_sit_str,
        'first_occurrence' => $first_occurrence_str,
        'inc_create' => $inc_create_str,
    ));
    exit();
}

// historical graph
if ($graph == 'historical') {
    $sqlQuery = "select substr(WRITETIME, 1, 10) as D, SEVERITY as SEVERITY, count(SEVERITY) as N
                    from DB2INST1.PFR_EVENT_HISTORY 
                    where WRITETIME > TO_CHAR(CURRENT_DATE -3 MONTHS,'YYYY-MM-DD') and PFR_OBJECT = '{$object}' and PFR_SIT_NAME = '{$sit_code}'
                    group by substr(WRITETIME, 1, 10), SEVERITY
                    order by substr(WRITETIME, 1, 10) asc";
    $stmt = db2_prepare($connection_WHFED, $sqlQuery);
    $result = db2_execute($stmt);

    while ($row = db2_fetch_assoc($stmt))
        $data[] = $row;

    db2_close($connection_WHFED);
    echo json_encode($data);
    exit();
}

