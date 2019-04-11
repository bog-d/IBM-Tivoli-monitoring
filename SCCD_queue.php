<?php
/*
	by GDV
	2017 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-eqRESENDuiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Очередь отправки тестовых событий</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
</head>
<body>
<?php
require_once 'connections/TBSM.php';
require_once 'connections/MAXDB76.php';
include 'functions/user_roles.php';
include 'functions/tbsm.php';
include 'functions/utime.php';

$sql_file = 'SCCD_trigger.sql'; 	// sql command file
$log_file_sql = 'logs/SCCD_sql.log'; // log file of sql queries
$log_file = 'logs/SCCD_trigger.log'; // log file

$level = 0;					// iteration level
$path_history = [ ];		// array for history tree from parents to childs
$results = [ ];				// array for endpoint childs
$pfr_ke_tors = [ ];	        // PFR_KE_TORS and PFR_ID_TORG fields from PFR_LOCATIONS table
$sit_dupl = [];

$array = [ ];
$sum_events = 0;
$bad_events = 0;
$resend = true;

$initiator = '';
$severity_codes = array ( "" => "-",
    "Fatal" => "5",
    "Critical" => "5",
    "Marginal" => "4",
    "Warning" => "2",
    "Informational" => "1",
    "Harmless" => "0",
    "Определено в Systems Director" => "5",
    "Определено в TSPC" => "5"); 		// situation severity codes for test event
$event_codes = array ( "PFR_TSPC_EIF_EVENT" => "89200",
    "PFR_SD_EIF_EVENT" => "87350" ); // test event codes for special situations

// **************************************************************************************************************************************************

$delay_in_block = isset($_POST['delay_in_block']) ? $_POST['delay_in_block'] : 10;
$delay_out_block = isset($_POST['delay_out_block']) ? $_POST['delay_out_block'] : 100;
$block_size = isset($_POST['block_size']) ? $_POST['block_size'] : 100;
$service = isset($_GET['service']) ? $_GET['service'] : '';
$service = isset($_POST['service']) ? $_POST['service'] : $service;
$event_lifetime = isset($_GET['lifetime']) ? $_GET['lifetime'] : 0;
$event_lifetime = isset($_POST['event_lifetime']) ? $_POST['event_lifetime'] : $event_lifetime;

// user access codes load from file and check
$acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
if(!empty($acs))
    list($acs_user, $acs_role) = explode(';', $acs);
$acs_form = ($acs_role == 'admin' or $acs_role == 'user');

// top header
$title = "Очередь отправки тестовых событий";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
require 'functions/header_2.php';

?> <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="formId"> <?php

    // web form button was pressed
    if (isset($_POST['actionBtn']))
        switch($_POST['actionBtn']) {
            case 'Рассчитать':
            case 'Добавить':
                // service ID
                $selSERVICEINSTANCE = "SELECT SERVICEINSTANCEID, SERVICEINSTANCENAME, DISPLAYNAME FROM TBSMBASE.SERVICEINSTANCE WHERE SERVICEINSTANCENAME = '$service'";
                $stmtSERVICEINSTANCE = db2_prepare($connection_TBSM, $selSERVICEINSTANCE);
                if (!db2_execute($stmtSERVICEINSTANCE))
                    exit ($selSERVICEINSTANCE);
                $row = db2_fetch_assoc($stmtSERVICEINSTANCE);
                if (empty($row)) {
                    ?><script>alert('Указанный сервис не найден!');</script><?php
                    break;
                }
                $service_instancename = $row['SERVICEINSTANCENAME'];
                $service_displayname = $row['DISPLAYNAME'];

                // recursive function call to find all child services
                if (!ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, $level, $path_history))
                    $results[0]['service'] = $service_instancename;

                // KE + Service array fill
                foreach ($results as $value) {
                    // record(s) selection from PFR_LOCATIONS table
                    $selPFR_LOCATIONS = "SELECT PFR_KE_TORS FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME = '".$value['service']."'";
                    $stmtPFR_LOCATIONS = db2_prepare($connection_TBSM, $selPFR_LOCATIONS);
                    if (!db2_execute($stmtPFR_LOCATIONS))
                        exit ($selPFR_LOCATIONS);
                    while ($row = db2_fetch_assoc($stmtPFR_LOCATIONS))
                        $ke[] = $row['PFR_KE_TORS']."~".$value['service'];
                }

                // duplicates remove
                $KE_Service = array_unique($ke);

                // add events to queue
                if ($_POST['actionBtn'] == 'Добавить') {
                    if (empty($event_lifetime)) {
                        ?><script>alert('Выберите или рассчитайте время жизни событий!');</script><?php
                    break;
                    }

                    $rand = rand();
                    $i = 0;
                    foreach ($KE_Service as $value) {
                        list($ke_tors, $name) = explode('~', $value);
                        // record(s) selection from PFR_TEMS_SIT_AGGR table
                        $selPFR_TEMS_SIT_AGGR = "SELECT * FROM DB2INST1.PFR_TEMS_SIT_AGGR WHERE PFR_KE_TORS = '$ke_tors' AND SERVICE_NAME = '$name' 
                                                                     UNION
                                                                     SELECT * FROM DB2INST1.PFR_TEMS_SIT_OVER WHERE PFR_KE_TORS = '$ke_tors' AND SERVICE_NAME = '$name'";
                        $stmtPFR_TEMS_SIT_AGGR = db2_prepare($connection_TBSM, $selPFR_TEMS_SIT_AGGR);
                        if (!db2_execute($stmtPFR_TEMS_SIT_AGGR))
                            exit ($selPFR_TEMS_SIT_AGGR);

                        while ($row = db2_fetch_assoc($stmtPFR_TEMS_SIT_AGGR)) {
                            // situations deduplication
                            if (in_array($ke_tors . "~" . $name . "~" . $row['SIT_NAME'] . "~" . $row['AGENT_NODE'] . "~" . $row['NODE'] . "~" . $row['SEVERITY'], $sit_dupl))
                                continue;
                            else
                                $sit_dupl [] = $ke_tors . "~" . $name . "~" . $row['SIT_NAME'] . "~" . $row['AGENT_NODE'] . "~" . $row['NODE'] . "~" . $row['SEVERITY'];

                            // sql command form
                            $event_id = $row['NODE'] . ':' . $row['SIT_CODE'] . ':' . rand(100, 999);
                            $reg_code = $row['REGION'] == '092' ? '091' : $row['REGION'];
                            $NCO = $reg_code == '101' ? 'NCOMS' : 'NCO' . $reg_code;
                            $command = "INSERT INTO alerts.status (Identifier, Severity, Class, Manager, FirstOccurrence, LastOccurrence, ExpireTime, Node, NodeAlias, Summary, pfr_description, ITMApplLabel, pfr_sit_name, ServerName, ITMDisplayItem) VALUES ('" . $event_id . "', " . $severity_codes[$row['SEVERITY']] . ", " . (array_key_exists($row['SIT_NAME'], $event_codes) ? $event_codes[$row['SIT_NAME']] : '87722') . ", 'tivoli_eif probe test event', getdate(), getdate(), " . $event_lifetime . ", '" . $row['NODE'] . "', '" . $row['NODE'] . "', 'ТЕСТ РЗ: " . str_replace(array("\"", "'"), "", $row['DESCRIPTION']) . "', 'ТЕСТ РЗ: " . str_replace(array("\"", "'"), "", $row['DESCRIPTION']) . "', '" . $row['CATEGORY'] . "', '" . $row['SIT_CODE'] . "', '" . $NCO . "', 'ТЕСТ РЗ')";

                            $selPFR_TEST_EVENTS = "INSERT INTO DB2INST1.PFR_TEST_EVENTS (SERVICE, DISPLAYNAME, USERNAME, SITUATION, EVENT_ID, LIFETIME, COMMAND, DESCRIPTION, INITIATOR, TIMESTAMP, PID, REGION, SENT_STATUS) VALUES ('" . $service . "', '" . $service_displayname . "', '" . $acs_user . "', '" . $row['SIT_NAME'] . "', '" . $event_id . "', '" . $event_lifetime . "', '" . str_replace("'", "''", $command) . "', 'Генерация тестовых РЗ по объекту " . $service . "', '" . $initiator . "', CURRENT TIMESTAMP, " . $rand . ", '" . $row['REGION'] . "', 'QUEUE')";
                            $stmtPFR_TEST_EVENTS = db2_prepare($connection_TBSM, $selPFR_TEST_EVENTS);
                            if (!db2_execute($stmtPFR_TEST_EVENTS)) {
                                echo "<br><br>Ошибка при вставке в таблицу PFR_TEST_EVENTS записи:<br>".$command."<br><br>Работа скрипта прервана!";
                                exit ($selPFR_TEST_EVENTS);
                            }
                            $i++;

                            if (empty($i % 100)) {
                                $selCommit = "commit work";
                                $stmtCommit = db2_prepare($connection_TBSM, $selCommit);
                                if (!db2_execute($stmtCommit))
                                    exit ($selCommit);
                            }
                        }
                    }
                }
                // calculate recommended event lifetime
                else {
                    $max_delay = 0;
                    foreach ($KE_Service as $value) {
                        list($ke_tors, $name) = explode('~', $value);
                        $sel = "SELECT  cl.DELAYMIN
                                        FROM (SELECT CINUM FROM MAXIMO.CI WHERE CINAME = '" . $ke_tors . "') AS ci
                                        LEFT JOIN MAXIMO.CICLASS cl
                                        ON ci.CINUM = cl.CINUM";
                        $stmt_SCCD = db2_prepare($connection_SCCD, $sel);
                        $result = db2_execute($stmt_SCCD);

                        while ($row = db2_fetch_assoc($stmt_SCCD))
                            $max_delay = $max_delay > $row['DELAYMIN'] ? $max_delay : $row['DELAYMIN'];
                    }

                    // event lifetime autofit
                    foreach ($arr_event_lifetime as $v)
                        if ($v > $max_delay * 60) {
                            $event_lifetime = $v;
                            break;
                        }
                }
                break;
            case 'Пересчитать':
                break;
            case 'Запустить отправку тестовых РЗ':
                $resend = false;
            case 'Отправить повторно ошибочные':
                // ignore user disconnect
                ignore_user_abort(true);
                set_time_limit(0);

                // progress bar holder and information
                ?>
                <div id="time_information" style="width"></div>
                <div id="progress" style="width:100%;border:1px solid #ccc;"></div>
                <div id="count_information" style="width"></div>
                <?php

                // copy commands to array
                $sel = "select * from DB2INST1.PFR_TEST_EVENTS where SENT_STATUS = '".($resend ? 'RESEND' : 'QUEUE')."' order by REGION asc";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                while ($row = db2_fetch_assoc($stmt))
                    $array[] = array (
                        'ID' => $row['ID'],
                        'SERVICE' => $row['SERVICE'],
                        'DISPLAYNAME' => $row['DISPLAYNAME'],
                        'USERNAME' => $row['USERNAME'],
                        'SITUATION' => $row['SITUATION'],
                        'EVENT_ID' => $row['EVENT_ID'],
                        'COMMAND' => $row['COMMAND'],
                        'DESCRIPTION' => $row['DESCRIPTION'],
                        'INITIATOR' => $row['INITIATOR'],
                        'SENT_STATUS' => true,
                    );

                // table records mark as SENDING
                $sel = "update DB2INST1.PFR_TEST_EVENTS set SENT_STATUS = 'SENDING' where SENT_STATUS = '".($resend ? 'RESEND' : 'QUEUE')."'";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);

                // prepare to begin
                ?><div id="progressDiv"></div><?php
                $sum_events = count($array);
                $time_begin = time();
                $time_begin_str = date('H:i d.m.y', $time_begin);
                $i = 1;

                // create file for emergency script break
                $SCCD_queue_stop = 'logs/break/SCCD_SQL_'.rand();
                file_put_contents($SCCD_queue_stop, "{$time_begin}\t{$time_begin}\t0\t{$sum_events}\t0\t{$acs_user}\n");

                // commands send
                foreach($array as &$rec) {
                    if (!ignore_user_abort())
                        ignore_user_abort(true);

                    // sql command write to file and execute
                    file_put_contents($sql_file, $rec['COMMAND'].";\ngo\n", LOCK_EX);
                    $q_string = shell_exec("/opt/IBM/tivoli/netcool/omnibus/bin/nco_sql -user root -password passw0rdt1vol1 -server NCOMS < $sql_file");
                    $q_status = empty(strcmp(trim($q_string), "(1 row affected)"));
                    $output = "Тестовое событие для ситуации ".$rec['SITUATION'].($q_status ? "" : " не ")." сгенерировано".($q_status ? " (".$rec['EVENT_ID'].")." : "!");
                    file_put_contents($log_file_sql, date('d.m.Y H:i:s')."\t".($q_status ? "Y" : "N")."\t".$rec['COMMAND'].";\n", FILE_APPEND | LOCK_EX);
                    if (!$q_status)
                        echo "<font color='red'>".$i.") ".$rec['COMMAND']."</font><br>";
                    $bad_events += $q_status ? 0 : 1;
                    $rec['SENT_STATUS'] = $q_status;

                    // log file and PFR_TEST_EVENTS_LOG table write
                    file_put_contents($log_file, date('d.m.Y H:i:s')."\t".$rec['SERVICE']." (".$rec['DISPLAYNAME'].")\t".$rec['USERNAME']."\t".$output."\t".$rec['DESCRIPTION']."\t\n", FILE_APPEND | LOCK_EX);
                    $sel = "insert into DB2INST1.PFR_TEST_EVENTS_LOG (SERVICE_NAME, DISPLAY_NAME, USER, OPERATION, DESCRIPTION, TIMESTAMP) 
                            values ('".$rec['SERVICE']."', '".$rec['DISPLAYNAME']."', '".$rec['USERNAME']."', '".$output."', '".$rec['DESCRIPTION']."', CURRENT TIMESTAMP)";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);

                    // current percent, work time, expected time
                    $percent = intval($i/$sum_events * 100)."%";
                    $time_end_in_sec = $delay_in_block * ($sum_events - $i-1) + ($delay_out_block - $delay_in_block) * (ceil(($sum_events - $i)/$block_size) - 1);
                    $time_end = time() + $time_end_in_sec;

                    // javascript for updating the progress bar and information
                    echo '<script language="javascript">
                            document.getElementById("progress").innerHTML="<div style=\"width:'.$percent.';background-color:#ddd;\">&nbsp;</div>";
                            document.getElementById("count_information").innerHTML="" +
                             "<table width=\'100%\'>" +
                              "<tr>" +
                               "<td align=\'center\'>'.$percent.' ('.$i.' из '.$sum_events.' событий отправлено)</td>" +
                              "</tr>" +
                             "</table>";
                            document.getElementById("time_information").innerHTML="" +
                             "<table width=\'100%\'>" +
                              "<tr>" +
                               "<td align=\'left\'>Начало: '.$time_begin_str.'</td>" +
                               "<td align=\'center\'>прошло '.readable_time(time() - $time_begin, true).' / '.readable_time($time_end_in_sec, true).' осталось</td>" +
                               "<td align=\'right\'>Расчётное время завершения: '.date('H:i d.m.y', $time_end).'</td>" +
                              "</tr>" +
                             "</table>";                        
                        </script>';

                    // this is for the buffer achieve the minimum size in order to flush data
                    echo str_repeat(' ',1024*64);

                    // send output to browser immediately
                    flush();

                    // statistics for stop file
                    file_put_contents($SCCD_queue_stop, "{$time_begin}\t{$time_end}\t{$i}\t{$sum_events}\t{$bad_events}\t{$acs_user}\n");

                    // delay
                    if ($i < $sum_events)
                        sleep(empty($i % $block_size) ? $delay_out_block : $delay_in_block);
                    $i++;

                    // break check
                    if (!file_exists($SCCD_queue_stop))
                        exit('<br><h2 class="red_message">Работа скрипта прервана принудительно!</h2><br>');
                }
                unset($rec);

                // worktime statistics
                $time_end = time();
                echo '<script language="javascript">
                        document.getElementById("count_information").innerHTML="Отправка '.$sum_events.' тестовых событий завершена.";
                        document.getElementById("time_information").innerHTML="" +
                         "<table width=\'100%\'>" +
                          "<tr>" +
                           "<td align=\'left\'>Начало: '.$time_begin_str.'</td>" +
                           "<td align=\'center\'>Время работы: '.readable_time($time_end - $time_begin, true).'</td>" +
                           "<td align=\'right\'>Окончание: '.date('H:i d.m.y', $time_end).'</td>" +
                          "</tr>" +
                         "</table>";
                    </script>';

                echo "<br>Количество ошибок: ".$bad_events."<br>";
                echo "<br>Отправленные sql-запросы и результаты их выполнения см. в <a href='http://10.103.0.60/pfr_other/{$log_file_sql}' target='_blank'>лог-файле</a>...<br>";

                // table records remove or mark to resend
                foreach ($array as $rec) {
                    if ($rec['SENT_STATUS'])
                        $sel = "delete from DB2INST1.PFR_TEST_EVENTS where ID = {$rec['ID']}";
                    else
                        $sel = "update DB2INST1.PFR_TEST_EVENTS set SENT_STATUS = 'RESEND' where ID = {$rec['ID']}";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);
                }

                if ($bad_events > 0)
                    echo "<p align='center'><button type='submit' name='actionBtn' value='Отправить повторно ошибочные' title='Повторно отправить запросы, на которых возникла ошибка...' <?php echo $acs_form ? '' : 'disabled'; ?>Отправить повторно ошибочные</button></p>";
                echo "<br><hr><br>";

                if (file_exists($SCCD_queue_stop))
                    unlink($SCCD_queue_stop);
                ignore_user_abort(false);
                break;
            case 'Очистить очередь':
                $sel = "delete from DB2INST1.PFR_TEST_EVENTS where SENT_STATUS = 'QUEUE'";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                break;
            case 'Прервать отмеченные отправки':
                if (isset($_POST['chk_break']))
                    foreach($_POST['chk_break'] as $key => $val)
                        unlink(__DIR__."/logs/break/{$key}");
                break;
            default:
                break;
        }

    // blocks parameters
    ?>
    <table cellspacing=5>
        <tr>
            <td> Задержка внутри блока: </td>
            <td> <input type="text" name="delay_in_block" size="10" value="<?php echo $delay_in_block; ?>" pattern="^[ 0-9]+$" required> с </td>
            <td> &emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp; Добавить в очередь тестовые события для указанного сервиса: <input type="text" name="service" size="40" value="<?php echo $service; ?>"> </td>
        </tr>
        <tr>
            <td> Задержка между блоками: </td>
            <td> <input type="text" name="delay_out_block" size="10" value="<?php echo $delay_out_block; ?>" pattern="^[ 0-9]+$" required> с </td>
            <td align="right">
                <table class="gantt" cellpadding="5">
                    <tr><td>
                        со временем жизни: <select size = "1" name = "event_lifetime" title="Время жизни тестового события"><?php
                            if ($event_lifetime == 0)
                                ?><option value = 0 selected>не выбрано</option><?php ;
                            foreach ($arr_event_lifetime as $t => $v) {
                                ?><option value = <?php echo $v; ?> <?php echo $v == $event_lifetime ? 'selected' : ''; ?>><?php echo $t; ?></option><?php ;
                            }
                            ?></select>
                        &emsp;
                        <button type="submit" name="actionBtn" value="Рассчитать" title="Рассчитать рекомендованное время жизни событий (выбор max из всех)" <?php echo $acs_form ? '' : 'disabled'; ?>>Рассчитать</button>
                    </td></tr>
                </table>
            </td>
            <!--                    <input type="text" name="event_lifetime" size="40" value="--><?php //echo $event_lifetime; ?><!--" pattern="^[ 0-9]+$" > -->
        </tr>
        <tr>
            <td> Количество событий в блоке: </td>
            <td> <input type="text" name="block_size" size="10" value="<?php echo $block_size; ?>" pattern="^[ 0-9]+$" required> </td>
            <td align="center"> <button type="submit" name="actionBtn" value="Добавить" title="Добавить в очередь тестовые события для указанного сервиса с выбранным временем жизни" <?php echo $acs_form ? '' : 'disabled'; ?>>Добавить в очередь</button> </td>
        </tr>
    </table>
    <br>
    <?php

    // queue list
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
    echo "<tr>";
    echo "<th>ID пакета</th>";
    echo "<th>Время постановки в очередь&nbsp;&uarr;</th>";
    echo "<th>Пользователь</th>";
    echo "<th>Имя сервиса</th>";
    echo "<th>Отображаемое имя</th>";
    echo "<th>Время жизни событий (с)</th>";
    echo "<th>Количество событий</th>";
    echo "</tr>";

    // record(s) selection from PFR_TEST_EVENTS table
    $sel = "select distinct PID, SERVICE, DISPLAYNAME, USERNAME, LIFETIME, max(TIMESTAMP) as TIME, count(ID) as COUNT
                from DB2INST1.PFR_TEST_EVENTS
                where SENT_STATUS = 'QUEUE'
                group by PID, SERVICE, DISPLAYNAME, USERNAME, LIFETIME
                order by TIME asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    $sum_events = 0;
    while ($row = db2_fetch_assoc($stmt)) {
        echo "<tr>";
        // ID пакета
        echo "<td>".$row['PID']."</td>";
        // Время
        echo "<td>".substr($row['TIME'], 0, 19)."</td>";
        // Пользователь
        echo "<td>".$row['USERNAME']."</td>";
        // Имя сервиса
        echo "<td>".$row['SERVICE']."</td>";
        // Отображаемое имя
        echo "<td>".$row['DISPLAYNAME']."</td>";
        // Время жизни событий
        echo "<td align=\"right\">".$row['LIFETIME']."</td>";
        // Количество событий в пакете
        echo "<td align=\"right\">";
        echo $row['COUNT'];
        $sum_events = $sum_events + $row['COUNT'];
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<br>";

    if(!empty($sum_events)) {
        ?>
        <table cellspacing=5>
            <tr>
                <td> Общее количество событий: </td>
                <td> <?php echo $sum_events; ?> </td>
                <td rowspan=0 valign="center"> <button type="submit" name="actionBtn" value="Пересчитать" title="Пересчитать время на отправку">Пересчитать</button> </td>
            </tr>
            <tr>
                <td> Количество блоков: </td>
                <td> <?php echo ceil($sum_events/$block_size); ?> </td>
            </tr>
            <tr>
                <td> Время на отправку: </td>
                <td> <?php
                    echo readable_time($delay_in_block * ($sum_events-1) + ($delay_out_block - $delay_in_block) * (ceil($sum_events/$block_size) - 1), true);
                    ?> </td>
            </tr>
        </table>
        <br>
        <button type="submit" name="actionBtn" value="Запустить отправку тестовых РЗ" onclick="return confirm('Вы действительно хотите запустить очередь на выполнение?')" title="Запустить всю очередь на отправку" <?php echo $acs_form ? '' : 'disabled'; ?>>Запустить отправку тестовых РЗ</button>
        &emsp;&emsp;&emsp;
    <button type="submit" name="actionBtn" value="Очистить очередь" onclick="return confirm('Вы действительно хотите удалить все данные из очереди?')" title="Удалить все пакеты из очереди" <?php echo $acs_form ? '' : 'disabled'; ?>>Очистить очередь</button><?php ;
    }

    // running sends
    $files = scandir(__DIR__."/logs/break", SCANDIR_SORT_NONE);
    foreach ($files as $file )
        if (strpos($file, 'SCCD_SQL_') !== false)
            $files_arr[] = $file;
    if (!empty($files_arr)) {
        echo "<br><br><br><br><u>Запущенные отправки:</u><br><br>";
        echo "<table border='1' cellspacing='0' cellpadding='10'>";
        echo "<tr>";
        echo "<th>Время начала</th>";
        echo "<th>Время окончания<br>(расчётное)</th>";
        echo "<th>Отправлено событий</th>";
        echo "<th>Инициатор</th>";
        echo "<th></th>";
        echo "</tr>";
        foreach ($files_arr as $proc) {
            list($begin, $end, $sent, $total, $err, $user, ) = explode("\t", file_get_contents(__DIR__."/logs/break/{$proc}"));
            echo "<tr>";
            echo "<td>".date('d.m.y H:i:s', $begin)."</td>";
            echo "<td>".date('d.m.y H:i:s', $end)."</td>";
            echo "<td align='center'>{$sent} из {$total} (ошибок: {$err})</td>";
            echo "<td>{$user}</td>";
            echo "<td align='center'><input type='checkbox' name='chk_break[{$proc}]' ".($acs_form ? '' : 'disabled')."/></td>";
            echo "<tr>";
        }
        echo "<tr>";
        echo "<td colspan='0' align='center'><button type='submit' name='actionBtn' value='Прервать отмеченные отправки' onclick=\"return confirm('Вы действительно хотите прервать отмеченные отправки?')\" ".($acs_form ? '' : 'disabled').">Прервать отмеченные отправки</button></td>";
        echo "</tr>";
        echo "</table>";
    }

    // database connections close
    db2_close($connection_TBSM);
    db2_close($connection_SCCD);

    ?>
</form>
</body>
</html>