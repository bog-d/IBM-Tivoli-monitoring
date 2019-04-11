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
    <meta http-equiv="Refresh" content="<?=$delay?>" />
    <title>Работы по обслуживанию - интеграция Мониторинга с СТП</title>
</head>
<body>
<?php
// common functions
require_once 'connections/MAXDB76.php';
require_once 'connections/TBSM.php';
include 'functions/utime.php';

// -------------------------------------------------------------------------------------------------------------------

$ext_log_file = "/usr/local/apache2/htdocs/pfr_other/logs/extended/maintenance.log";
$ext_log_write = false;     // писать ли расширенные логи
$table_cells = [];
$concurrence_count = [];
$concurrence_data = [];
$concurrence_times = [];
$maintenance = [];

// -------------------------------------------------------------------------------------------------------------------

// current time
$current_timestamp = time();
$current_minute = date('d.m.Y H:i', $current_timestamp);


// script test mode
$test_mode = isset($_GET['test_time']);
$current_minute = $test_mode ? $_GET['test_time'] : $current_minute;


if ($test_mode)
    output($test_mode, "!!! *** ТЕСТОВЫЙ ПРОГОН СКРИПТА *** !!!\n");

output($test_mode, "====================================================\n");
output($test_mode, "{$current_minute}\n\n");

// record(s) selection from MAXIMO tables
$sel = "SELECT CI.CINAME, 
               CI.ASSETLOCSITEID, 
               C.NOTAVAILABLE, 
               C.STARTTIMECI, 
               C.ENDTIMECI, 
               C.FACTSTARTTIMECI, 
               C.FACTENDTIMECI, 
               B.STATUS,
               B.STATUSDATE,
               B.WORKTYPE, 
               C.BLACKOUTNUM
            FROM MAXIMO.PMCHGBOCI AS C 
            LEFT JOIN MAXIMO.PMCHGBLACKOUT AS B 
            ON C.BLACKOUTNUM = B.BLACKOUTNUM
            LEFT JOIN MAXIMO.CI AS CI
            ON C.CINUM = CI.CINUM AND C.STARTTIMECI IS NOT NULL
            LEFT JOIN MAXIMO.SYNONYMDOMAIN AS S
            ON S.MAXVALUE = CI.ENVIRONID AND S.DOMAINID = 'CIENVIRONSTAND' AND S.DEFAULTS = '1'
            WHERE C.NOTAVAILABLE = 1 AND CI.CINUM IS NOT NULL AND CI.ASSETLOCSITEID IS NOT NULL AND C.ISTEMPLATE = 0 AND
                  B.STATUS IN ('ACTIVE', 'INPROG', 'EXPIRED', 'REG', 'CANC') AND
                  B.WORKTYPE IN ('PLAN', 'UNPLAN', 'ZEMERGENCY') AND  
                  (B.STATUS <> 'REG' or B.WORKTYPE <> 'PLAN')
            ORDER BY CI.ASSETLOCSITEID, CI.CINAME ASC";
$stmt_SCCD = db2_prepare($connection_SCCD, $sel);
$result = db2_execute($stmt_SCCD);

if (!$test_mode and $ext_log_write) {
    file_put_contents($ext_log_file, "{$current_minute}\n\n", FILE_APPEND | LOCK_EX);
    file_put_contents($ext_log_file, "Результат выполнения запроса к БД: \n\n", FILE_APPEND | LOCK_EX);
}

while ($row = db2_fetch_assoc($stmt_SCCD)) {
    if (!$test_mode and $ext_log_write) {
        foreach ($row as $key => $value)
            file_put_contents($ext_log_file, "$key : $value\n", FILE_APPEND | LOCK_EX);
        file_put_contents($ext_log_file, "\n", FILE_APPEND | LOCK_EX);
    }

    if ($row['WORKTYPE'] != 'PLAN' or $row['STATUS'] == 'INPROG' or $row['STATUS'] == 'EXPIRED')
        $start_time = $row['FACTSTARTTIMECI'];
    else
        $start_time = $row['STARTTIMECI'];

    if ($row['STATUS'] != 'CANC' and ($row['WORKTYPE'] != 'PLAN' or $row['STATUS'] == 'INPROG' or $row['STATUS'] == 'EXPIRED'))
        $end_time = $row['FACTENDTIMECI'];
    else if ($row['STATUS'] == 'ACTIVE' and $row['WORKTYPE'] == 'PLAN')
        $end_time = $row['ENDTIMECI'];
    else
        $end_time = $row['STATUSDATE'];

    // table fill by not passed works
    $start_timestamp = empty($start_time) ? 0 : utime($start_time);
    $end_timestamp = empty($end_time) ? 1802649600 : utime($end_time);
    if ($row['STATUS'] == 'CANC')
        $end_timestamp += 60;
    if ($test_mode or $end_timestamp == 0 or ($end_timestamp - $end_timestamp % 60) >= ($current_timestamp - $current_timestamp % 60))
        $table_cells[] = array(
            "ciname" => $row['CINAME'],
            "start" => $start_timestamp,
            "end" => $end_timestamp,
        );
}
            // ************************************************ DEBUG ******************************************
            foreach ($table_cells as $item) {
                $debug_arr_1[] = array(
                    "ciname" => $item['ciname'],
                    "start" => is_null($item['start']) ? '' : date('d.m.Y H:i:s', $item['start']),
                    "end" => is_null($item['end']) ? '' : date('d.m.Y H:i:s', $item['end']),
                );
            }
            // *************************************************************************************************

if (!$test_mode and $ext_log_write) {
    file_put_contents($ext_log_file, "\n\n\nМассив актуальных ТР до склеивания: \n\n", FILE_APPEND | LOCK_EX);
    file_put_contents($ext_log_file, print_r($debug_arr_1, true), FILE_APPEND | LOCK_EX);
}

// count the number of works for each ciname and "gluing"
$concurrence_count = array_count_values(array_column($table_cells, 'ciname'));
foreach ($concurrence_count as $ciname => $count) {
    // treatment of cinames with more than 1 work
    if ($count > 1) {
        unset($concurrence_data);
        // temporary array fill and unset those elements from source array
        foreach ($table_cells as $key => $row)
            if ($row["ciname"] == $ciname) {
                $concurrence_data[] = array(
                        "start" => $row["start"],
                        "end" => $row["end"],
                        "inter" => false,
                    );
                unset($table_cells[$key]);
            }
        // external function for "gluing"
        $concurrence_times = search_concurrences('combination', $concurrence_data);
        // insert "glued" elements into source array
        foreach($concurrence_times as $value)
            $table_cells[] = array(
                "ciname" => $ciname,
                "start" => $value["start"],
                "end" => $value["end"],
            );
    }
}

            // ************************************************ DEBUG ******************************************
            foreach ($table_cells as $item) {
                $debug_arr_2[] = array(
                    "ciname" => $item['ciname'],
                    "start" => is_null($item['start']) ? '' : date('d.m.Y H:i:s', $item['start']),
                    "end" => is_null($item['end']) ? '' : date('d.m.Y H:i:s', $item['end']),
                );
            }
            // *************************************************************************************************

// group by time
foreach ($table_cells as $item) {
    if (!array_key_exists($item['start'], $maintenance))
        $maintenance[$item['start']]['start'][] = $item['ciname'];
    else if (!array_key_exists('start', $maintenance[$item['start']]) or !in_array($item['ciname'], $maintenance[$item['start']]['start']))
        $maintenance[$item['start']]['start'][] = $item['ciname'];

    if (!array_key_exists($item['end'], $maintenance))
        $maintenance[$item['end']]['end'][] = $item['ciname'];
    else if (!array_key_exists('end', $maintenance[$item['end']]) or !in_array($item['ciname'], $maintenance[$item['end']]['end']))
        $maintenance[$item['end']]['end'][] = $item['ciname'];
}

            // ************************************************ DEBUG ******************************************
            foreach ($maintenance as $key => $item) {
                $debug_arr_3[date('d.m.Y H:i:s', $key)] = $item;
            }
            // *************************************************************************************************

if (!$test_mode and $ext_log_write) {
    file_put_contents($ext_log_file, "\n\n\nМассив актуальных ТР после склеивания: \n\n", FILE_APPEND | LOCK_EX);
    file_put_contents($ext_log_file, print_r($debug_arr_3, true), FILE_APPEND | LOCK_EX);
}

// works check for execute
foreach ($maintenance as $utime => $work) {
    // maintenance work without finish
    if ($utime == 0)
        continue;

    $work_minute = date('d.m.Y H:i', $utime);
    // to execute
    if ($work_minute == $current_minute) {
        if (!$test_mode and $ext_log_write)
            file_put_contents($ext_log_file, "\n\nАКТИВНОЕ СОБЫТИЕ В {$work_minute}", FILE_APPEND | LOCK_EX);

        // start or end
        $turn = key($work) == 'start' ? -1 : 1;
        $ke_str = "('".implode("', '", $work[key($work)])."')";

        output($test_mode, ($turn == -1 ? 'Начало ' : 'Завершение ')."ТР для следующих КЭ: {$ke_str}\n\n");
        output($test_mode,"Найденные записи из PFR_LOCATIONS:\n\n");
        output($test_mode,"ID\t\tPFR_KE_TORS\t\tSERVICE_NAME\n");
        output($test_mode,"__\t\t___________\t\t____________\n");

        // SERVICE_NAME by PFR_KE_TORS selection from PFR_LOCATIONS table
        $sel = "SELECT ID, PFR_KE_TORS, SERVICE_NAME FROM DB2INST1.PFR_LOCATIONS WHERE PFR_KE_TORS in {$ke_str} ORDER BY ID ASC";
        $stmt_TBSM = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt_TBSM);
        while ($row = db2_fetch_assoc($stmt_TBSM)) {
            output($test_mode, "{$row['ID']}\t{$row['PFR_KE_TORS']}\t" . (strlen($row['PFR_KE_TORS']) < 12 ? "\t" : "") . "{$row['SERVICE_NAME']}\n");

            // write to PFR_ACTIONS_LOG
            if (!$test_mode) {
                $sel_ins = "INSERT INTO DB2INST1.PFR_ACTIONS_LOG (SERVICE_NAME, DISPLAY_NAME, USER, OPERATION, DESCRIPTION, TIMESTAMP, INCIDENT, INITIATOR) 
                        VALUES ('{$row['SERVICE_NAME']}', '', 'СТП/ТОРС', 'Отправка инцидентов ".($turn == -1 ? "отключена" : "включена")."', 'Запланированная ТР', CURRENT TIMESTAMP, '', '')";
                $stmt_ins = db2_prepare($connection_TBSM, $sel_ins);
                $result_ins = db2_execute($stmt_ins);
            }
        }

        // incident send activate/deactivate
        if (!$test_mode) {
            $sel_upd = "UPDATE DB2INST1.PFR_LOCATIONS SET INCIDENT_SEND = {$turn} WHERE PFR_KE_TORS in {$ke_str}";
            $stmt_upd = db2_prepare($connection_TBSM, $sel_upd);
            $result_upd = db2_execute($stmt_upd);
        }
        else
            $result_upd = true;

        output($test_mode,"\n".($result_upd ? "Отправка инцидентов по указанным записям ".($turn == -1 ? "отключена" : "включена") : "Ошибка при ".($turn == -1 ? "отключении " : "включении ")." инцидентов по указанным записям").".\n");
        output($test_mode,"----------------------------------------------------\n\n");
    }
}

db2_close($connection_TBSM);
db2_close($connection_SCCD);
output($test_mode, "Время выполнения скрипта: ".(time() - $current_timestamp)." c \n");
if (!$test_mode and $ext_log_write)
    file_put_contents($ext_log_file, "\n----------------------------------------------------\n\n", FILE_APPEND | LOCK_EX);

/*
            // ************************************************ DEBUG ******************************************
            if ($test_mode) {
                echo "<br><br><table cellpadding='50'><tr>";
                echo "<td valign='top'>Массив исходных данных:";
                echo "<pre>"; print_r($debug_arr_1); echo "</pre></td>";
                echo "<td valign='top'>Массив данных после склейки:";
                echo "<pre>"; print_r($debug_arr_2); echo "</pre></td>";
                echo "<td valign='top'>Массив данных временных интервалов:";
                echo "<pre>"; print_r($debug_arr_3); echo "</pre></td>";
                echo "</tr></table>";
            }
            // *************************************************************************************************
*/
// -------------------------------------------------------------------------------------------------------------------

// function for output to log file or to screen
function output($mode, $message) {
    $log_file = '/usr/local/apache2/htdocs/pfr_other/logs/maintenance.log';

    if ($mode) {
        $message = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $message);
        $message = str_replace("\n", "<br>", $message);
        echo "{$message}";
    }
    else
        file_put_contents($log_file, $message, FILE_APPEND | LOCK_EX);
}

?>
</body>
</html>

<!--
Тип работ	Статус работ	Начало	Окончание

PLAN	    INPROG	        FACTSTARTTIMECI	    FACTENDTIMECI
            ACTIVE	        STARTTIMECI	        ENDTIMECI
            REG	            не анализируется	не анализируется
            EXPIRED	        FACTSTARTTIMECI	    FACTENDTIMECI
            CANC	        STARTTIMECI	        STATUSDATE + 60 с
UNPLAN
ZEMERGENCY  INPROG	        FACTSTARTTIMECI	    FACTENDTIMECI
            ACTIVE	        FACTSTARTTIMECI	    FACTENDTIMECI
            REG	            FACTSTARTTIMECI	    FACTENDTIMECI
            EXPIRED	        FACTSTARTTIMECI	    FACTENDTIMECI
            CANC	        FACTSTARTTIMECI	    STATUSDATE + 60 с
-->