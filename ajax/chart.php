<?php
header('Content-Type: application/json');

if (!isset($_POST))
    exit();

require_once('../connections/WHFED.php');
require_once('../connections/TBSM.php');
require_once('../functions/utime.php');
require_once('../functions/tbsm.php');
require_once('chart_sit_collections.php');

$scales_arr = array(
    5 => array('sec' => 300,        'axes' => 'second',     'step' => 15),      // 5 min
    4 => array('sec' => 1800,       'axes' => 'minute',     'step' => 5),       // half hour
    3 => array('sec' => 3600,       'axes' => 'minute',     'step' => 10),      // 1 hour
    2 => array('sec' => 3600*6,     'axes' => 'minute',     'step' => 30),      // 6 hours
    1 => array('sec' => 3600*12,    'axes' => 'hour',       'step' => 1),       // 12 hours
    0 => array('sec' => 3600*24,    'axes' => 'hour',       'step' => 2),       // 1 day
);

$data = [];
$scale = $_POST['scale'];
$shift = $_POST['shift'];
$serial = $_POST['serial'];

// get data from PFR_EVENT_HISTORY
$select = "select * from PFR_EVENT_HISTORY where SERIAL = {$serial} order by WRITETIME asc";
$stmt = db2_prepare($connection_WHFED, $select);
$result = db2_execute($stmt);
while ($row = db2_fetch_assoc($stmt))
    $history_arr[] = $row;
db2_close($connection_WHFED);

// if there is no such serial
if (empty($history_arr)) {
    echo json_encode(array(
        'error' => "Событие с серийным номером {$serial} не найдено.",
    ));
    exit();
}

// get info about events
$first_occurrence = utime($history_arr[0]['FIRST_OCCURRENCE']);
$last_occurrence = utime($history_arr[count($history_arr) - 1]['LAST_OCCURRENCE']);
$sit_code = $history_arr[0]['PFR_SIT_NAME'];
$region = $history_arr[0]['PFR_ID_TORG'] == '201' ? '087' : ($history_arr[0]['PFR_ID_TORG'] == '092' ? '091' : $history_arr[0]['PFR_ID_TORG']);
$region_wh = $region == '101' ? 'MSK' : $region;
$severity = array_search($history_arr[0]['SEVERITY'], $severity_codes);

// if the situation is not eligible
if (!array_key_exists($sit_code, $sits_arr)) {
    echo json_encode(array(
        'error' => "Ситуация {$sit_code} не имеет метрики для построения графика или ещё не добавлена в коллекцию.",
    ));
    exit();
}

$node = $node_WH = $history_arr[0]['NODE'];
foreach ($sits_arr[$sit_code]['replace'] as $search => $replace)
    $node_WH = str_replace($search, $replace, $node_WH);

// WH database connection options
$database_reg = "WH{$region_wh}";
$user_reg = 'db2inst1';
$password_reg = 'passw0rd';
$hostname_reg = 'tdw-main';
$port_reg = 50000;
$connection_reg = db2_connect("DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database_reg;HOSTNAME=$hostname_reg;PORT=$port_reg;PROTOCOL=TCPIP;UID=$user_reg;PWD=$password_reg;", '', '');
if (!$connection_reg) {
    echo json_encode(array(
        'error' => "Нет соединения с БД WH{$region_wh}.",
    ));
    exit();
}

// get data about situation
$select = "select distinct FORMULA, INTERVAL, COUNT from DB2INST1.PFR_TEMS_SIT_AGGR where REGION = '{$region}' and NODE = '{$node}' and SIT_CODE = '{$sit_code}' and SEVERITY = '{$severity}'";
$stmt = db2_prepare($connection_TBSM, $select);
$result = db2_execute($stmt);
$row = db2_fetch_assoc($stmt);
db2_close($connection_TBSM);

if (empty($row)) {
    echo json_encode(array(
        'error' => "Нет данных о частоте опроса ситуации {$sit_code}.",
    ));
    exit();
}
else {
    $formula = $row['FORMULA'];
    $frequency = substr($row['INTERVAL'], 0, 2)*3600 + substr($row['INTERVAL'], 2, 2)*60 + substr($row['INTERVAL'], 4, 2);
    $checks = $row['COUNT'];
}

// get data about situation close
$close_sit = 0;
foreach (array_reverse($history_arr) as $v) {
    if ($v['SEVERITY'] == 0) {
        $close_sit = utime($v['WRITETIME']);
        break;
    }
}

// get data about incident
$inc_create = 0;
foreach ($history_arr as $v) {
    if (!empty($v['TTNUMBER'])) {
        $inc_create = utime($v['WRITETIME']);
        break;
    }
}

// chart range
$start_sit = $first_occurrence - $frequency * $checks;
$last_occurrence = max($last_occurrence, $close_sit, $inc_create);
if ($scale == -1) {
    $scale = ($last_occurrence - $start_sit > 3600*24) ? 0 : 3;
}
$start_graph = $start_sit - $scales_arr[$scale]['sec'] + $shift * $scales_arr[$scale]['sec']/2;
$end_graph = $last_occurrence + $scales_arr[$scale]['sec'] + $shift * $scales_arr[$scale]['sec']/2;

// get metrics from WH
$start_time_db2 = '1'.date('ymdHis', $start_graph);
$end_time_db2 = '1'.date('ymdHis', $end_graph);
$select_wh = "select \"Timestamp\" as T, {$sits_arr[$sit_code]['metrica']} as VALUE".(isset($sits_arr[$sit_code]['metrica2']) ? ", {$sits_arr[$sit_code]['metrica2']} as VALUE2" : "")."
                from U{$region_wh}.{$sits_arr[$sit_code]['table']}
                where \"Timestamp\" >= '{$start_time_db2}' and \"Timestamp\" < '{$end_time_db2}' and {$sits_arr[$sit_code]['object']} = '{$node_WH}' {$sits_arr[$sit_code]['where']}
                order by \"Timestamp\" asc";
$stmt_wh = db2_prepare($connection_reg, $select_wh);
$result_wh = db2_execute($stmt_wh);

while ($row_wh = db2_fetch_assoc($stmt_wh)) {
    $data[] = array(
        'time' => '20' . substr($row_wh['T'], 1, 2) . '-' . substr($row_wh['T'], 3, 2) . '-' . substr($row_wh['T'], 5, 2) . ' ' .
            substr($row_wh['T'], 7, 2) . ':' . substr($row_wh['T'], 9, 2) . ':' . substr($row_wh['T'], 11, 2),
        'value' => $row_wh['VALUE'],
        'value2' => isset($row_wh['VALUE2']) ? $row_wh['VALUE2'] : null,
    );
}
db2_close($connection_reg);

// if there is no data in WH
if (empty($data)) {
    echo json_encode(array(
        'error' => "Не найдено данных по узлу {$node_WH} за период ".date('d.m.Y', $start_graph)."-".date('d.m.Y', $end_graph),
    ));
    exit();
}

// ajax return
echo json_encode(array(
    'title'             => $sits_arr[$sit_code]['title'],
    'title2'            => isset($sits_arr[$sit_code]['title2']) ? $sits_arr[$sit_code]['title2'] : null,
    'scale'             => $scale,
    'axes'              => $scales_arr[$scale]['axes'],
    'step'              => $scales_arr[$scale]['step'],
    'metrics'           => $data,
    'start_sit'         => date('Y-m-d H:i:s', $start_sit),
    'first_occurrence'  => date('Y-m-d H:i:s', $first_occurrence),
    'inc_create'        => date('Y-m-d H:i:s', $inc_create),
    'close_sit'         => date('Y-m-d H:i:s', $close_sit),
    'formula'           => $formula,
    'frequency'         => readable_time($frequency, true),
    'checks'            => $checks,
    'error'             => '',
));
exit();
