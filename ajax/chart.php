<?php
header('Content-Type: application/json');

if (!isset($_POST))
    exit();

require_once('../connections/WHMSK.php');
require_once('../connections/WHFED.php');
require_once('../functions/utime.php');

$scales_arr = array(
    5 => array('sec' => 300,        'axes' => 'second', 'step' => 15),      // 5 min
    4 => array('sec' => 1800,       'axes' => 'minute', 'step' => 5),       // half hour
    3 => array('sec' => 3600,       'axes' => 'minute', 'step' => 10),      // 1 hour
    2 => array('sec' => 3600*6,     'axes' => 'minute', 'step' => 30),      // 6 hours
    1 => array('sec' => 3600*12,    'axes' => 'hour',   'step' => 1),       // 12 hours
    0 => array('sec' => 3600*24,    'axes' => 'hour',   'step' => 2),       // 1 day
);

$sits_arr = array(
    'LZ_DISK_SPACE_LOW'     => array(
        'select' => "select \"Timestamp\" as T, \"Disk_Used_Percent\" as VALUE from UMSK.\"KLZ_Disk\"
                where \"Timestamp\" >= '{$start_time_db2}' and \"Timestamp\" < '{$end_time_db2}' and \"Mount_Point\" = '/' and (\"System_Name\" = '{$node}' or \"System_Name\" = '{$node_2}')
                order by \"Timestamp\" asc),
);

$data = [];
$error = '';
$graph = $_POST['graph'];
$scale =  $_POST['scale'];
$shift = $_POST['shift'];
$serial = $_POST['serial'];

// get data from PFR_EVENT_HISTORY
$select = "select * from PFR_EVENT_HISTORY where SERIAL = {$serial} order by WRITETIME asc";
$stmt = db2_prepare($connection_WHFED, $select);
$result = db2_execute($stmt);
while ($row = db2_fetch_assoc($stmt))
    $history_arr[] = $row;

if (empty($history_arr)) {
    echo json_encode(array(
        'error' => "Событие с серийным номером {$serial} не найдено!",
    ));
    exit();
}

// get data about situation
$frequency = 300; // seconds
$checks = 1;

// chart range
$first_occurrence = utime($history_arr[0]['FIRST_OCCURRENCE']);
$last_occurrence = utime($history_arr[count($history_arr) - 1]['LAST_OCCURRENCE']);
$start_sit = $first_occurrence - $frequency * $checks;
$start_graph = $start_sit - $scales_arr[$scale]['sec'] + $shift * $scales_arr[$scale]['sec']/2;
$end_graph = $last_occurrence + $scales_arr[$scale]['sec'] + $shift * $scales_arr[$scale]['sec']/2;

// get data about incident
$pause = 60; // seconds
$inc_create = $first_occurrence + $pause;



/*
$node = strtoupper($_POST['inp_node']); $node_2 = str_replace('I', 'i', $node);

// get data from AEL, TEMS, etc
$object = str_replace(':LZ', '', $node);;
$sit_code = 'LZ_DISK_SPACE_LOW';

// datetime conversion
$start_sit_str = date('Y-m-d H:i:s', $start_sit_utime);

$inc_create_str = date('Y-m-d H:i:s', $inc_create_utime);

// get WH select
$start_time_db2 = '1'.date('ymdHis', $start_graph_utime);
$end_time_db2 = '1'.date('ymdHis', $end_graph_utime);
$wh_select = "select \"Timestamp\" as T, \"Disk_Used_Percent\" as VALUE
                from UMSK.\"KLZ_Disk\"
                where \"Timestamp\" >= '{$start_time_db2}' and \"Timestamp\" < '{$end_time_db2}' and \"Mount_Point\" = '/' and (\"System_Name\" = '{$node}' or \"System_Name\" = '{$node_2}')
                order by \"Timestamp\" asc";
*/
// operative graph
if ($graph == 'operative') {
/*    // metrics
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
    db2_close($connection_WHMSK);*/

    // ajax return
    echo json_encode(array(
        'axes' => $scales_arr[$scale]['axes'],
        'step' => $scales_arr[$scale]['step'],
        'metrics' => $data,
//        'start_sit' => $start_sit_str,
//        'first_occurrence' => $first_occurrence_str,
//        'inc_create' => $inc_create_str,
        'error' => $error,
    ));
    exit();
}
