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
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <link  href="css/modal-form.css" type="text/css" rel="stylesheet">
    <title>PFR_LOCATIONS management</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
</head>
<body>
<?php
// common functions
require_once 'connections/TBSM.php';
include 'functions/remote_exec.php';
include 'functions/PFR_LOCATIONS_record_form.php';
include 'functions/regions.php';
include 'functions/user_roles.php';

// select buttons
define("SELECT_TEMPLATE",       'Выбрать шаблон');
define("SELECT_SERVICE",        'Выбрать сервис');
define("SELECT_TEMS",           'Выбрать TEMS');
define("SELECT_HOST",           'Выбрать хост');
define("SELECT_MQ_MANAGER",     'Выбрать менеджер MQ');

// action buttons
define("ACTION_ADD",                    "Добавить запись");
define("ACTION_EDIT",                   "Редактировать запись");
define("ACTION_CLONE",                  "Клонировать запись");
define("ACTION_REMOVE",                 "Удалить запись");
define("ACTION_REMOVE_SELECTED",        "Удалить отмеченные записи");
define("ACTION_ADD_CHANNEL",            "Добавить запись для канала");
define("ACTION_ADD_CHANNELS_SELECTED",  "Добавить записи для отмеченных каналов");

// channels list output fields from PFR_LOCATIONS
$table_titles = array ("ID", "NODE", "PFR_KE_TORS", "SERVICE_NAME");
// saved parameters array
$param_array = [];
// checkboxes array
$aCheckBoxesArray = [];
// show input form for single string or entire table
$only_input_form = 0;
// top header informational message
$output = "";
// SOAP output file
$res_file = "logs/PFR_LOCATIONS_channels.log";
// SOAP output file in XML format
$res_xml = "logs/PFR_LOCATIONS_channels.xml";

/******************************************************************************************************************/

// connection to TBSM database
if (!$connection_TBSM)
    exit("Database TBSM connection failed.");

// PFR_LOCATIONS output fields to string for queries
$table_titles_string = "";
foreach($table_titles as $rec)
    $table_titles_string = $table_titles_string.$rec.", ";
$table_titles_string = rtrim($table_titles_string, ", ");

// get script GET parameters
$template = isset($_GET['template']) ? $_GET['template'] : '';
$service = isset($_GET['service']) ? $_GET['service'] : '';
$tems = isset($_GET['tems']) ? $_GET['tems'] : '';
$host = isset($_GET['host']) ? $_GET['host'] : '';
$mq = isset($_GET['mq']) ? $_GET['mq'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

// get script POST parameters
$action = isset($_POST['formId']['sendRequest']) ? $_POST['formId']['sendRequest'] : '';
$template = $action == SELECT_TEMPLATE ? $_POST['formId']['template'] : $template;
$mask = isset($_POST['formId']['mask']) ? $_POST['formId']['mask'] : '';
$service = $action == SELECT_SERVICE ? $_POST['formId']['service'] : $service;
$tems = $action == SELECT_TEMS ? $_POST['formId']['tems'] : $tems;
$host = $action == SELECT_HOST ? $_POST['formId']['host'] : $host;
$mq = $action == SELECT_MQ_MANAGER ? $_POST['formId']['mq'] : $mq;

// query for PFR_LOCATIONS table fields and their properties
$sel = "SELECT NAME, COLTYPE, LENGTH, DEFAULT FROM SYSIBM.SYSCOLUMNS where TBNAME = 'PFR_LOCATIONS' order by COLNO asc";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);
while($row = db2_fetch_assoc($stmt))
    $table_fields[] = array ('NAME' => trim($row['NAME']),
        'COLTYPE' => trim($row['COLTYPE']),
        'LENGTH' => trim($row['LENGTH']),
        'DEFAULT' => trim(str_replace("'", "", $row['DEFAULT'])),
        'VALUE' => '',
    );

// any web-form button was pressed
if (!empty($action)) {
    // table string button
    if (strpos($action, ':')) {
        list($command, $value) = explode(':', $action);
        $channel = $command == ACTION_ADD_CHANNEL ? $value : '';
        $id = ($command == ACTION_EDIT or $command == ACTION_CLONE or $command == ACTION_REMOVE) ? $value : '';
    }
else
        $command = $action;

    // command interpreter
    switch ($command) {
        case SELECT_TEMS:
            $host = '';
            $mq = '';
            break;
        case SELECT_HOST:
            $mq = '';
            break;
        case SELECT_TEMPLATE:
            $service = '';
            break;
        case 'Очистить фильтр':
            $mask = '';
            break;
        /* case ACTION_ADD:
            // new record fields
            foreach ($table_fields as &$field) {
                $pfr_id_torg = $tems == 'tems-main' ? '101' : substr($tems, 3,3);
                $pfr_torg = substr($array_regions[$pfr_id_torg], strpos($array_regions[$pfr_id_torg], '*') ? strpos($array_regions[$pfr_id_torg], '*') + 2 : 0);
                $pfr_id_fo = pfr_id_fo ($pfr_id_torg);
                $pfr_fo = pfr_fo ($pfr_id_fo);

                switch ($field['NAME']) {
                    case 'PFR_FO':
                        $field['VALUE'] = $pfr_fo; break;
                    case 'PFR_ID_FO':
                        $field['VALUE'] = $pfr_id_fo; break;
                    case 'PFR_TORG':
                        $field['VALUE'] = $pfr_torg; break;
                    case 'PFR_ID_TORG':
                        $field['VALUE'] = $pfr_id_torg; break;
                    case 'NODE':
                        $field['VALUE'] = $mq.":".$host.":MQ:".$channel; break;
                    case 'PFR_OBJECT':
                        $field['VALUE'] = $host; break;
                    case 'SERVICE_NAME':
                        $field['VALUE'] = $service; break;
                    default:
                        break;
                }
            }
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id, '', REC_ARRAY_INSERT);
            $output = "Запись добавлена в PFR_LOCATIONS.";
            break; */
        case ACTION_ADD_CHANNEL:
        case ACTION_ADD_CHANNELS_SELECTED:
            if ($command == ACTION_ADD_CHANNEL) {
                $aCheckBoxesArray[$channel . "*"] = "";
                $output = "Запись добавлена в PFR_LOCATIONS.";
            }
            else {
                if (isset($_POST['chkbx'])) {
                    $aCheckBoxesArray = array_filter($_POST['chkbx']);
                    $output = "Отмеченные записи добавлены в PFR_LOCATIONS.";
                }
                else
                    $output = "Не отмечено ни одной записи!";
            }

            foreach ($aCheckBoxesArray as $key => $str) {
                list($ch_sel, $i) = explode('*', $key);
                if (!empty($ch_sel)) {
                    // new record fields
                    foreach ($table_fields as &$field) {
                        $pfr_id_torg = array_key_exists ($tems, $array_exeptions) ? $array_exeptions[$tems]["PFR_ID_TORG"] : substr($tems, 3, 3);
                        $pfr_torg = substr($array_regions[$pfr_id_torg], strpos($array_regions[$pfr_id_torg], '*') ? strpos($array_regions[$pfr_id_torg], '*') + 2 : 0);
                        $pfr_id_fo = pfr_id_fo($pfr_id_torg);
                        $pfr_fo = pfr_fo($pfr_id_fo);
                        $pfr_objectserver = array_key_exists ($tems, $array_exeptions) ? $array_exeptions[$tems]["PFR_OBJECTSERVER"] : "NCO".$pfr_id_torg;

                        switch ($field['NAME']) {
                            case 'PFR_FO':
                                $field['VALUE'] = $pfr_fo;
                                break;
                            case 'PFR_ID_FO':
                                $field['VALUE'] = $pfr_id_fo;
                                break;
                            case 'PFR_TORG':
                                $field['VALUE'] = $pfr_torg;
                                break;
                            case 'PFR_ID_TORG':
                                $field['VALUE'] = $pfr_id_torg;
                                break;
                            case 'NODE':
                                $field['VALUE'] = $mq . ":" . $host . ":MQ:" . $ch_sel;
                                break;
                            case 'PFR_OBJECT':
                                $field['VALUE'] = $host;
                                break;
                            case 'PFR_OBJECTSERVER':
                                $field['VALUE'] = $pfr_objectserver;
                                break;
                            case 'SERVICE_NAME':
                                $field['VALUE'] = $service;
                                break;
                            default:
                                break;
                        }
                    }
                    $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF'] . "?tems=" . $tems . "&host=" . $host . "&mq=" . $mq . "&template=" . $template . "&service=" . $service . "&id=" . $id . "#" . $id, '', REC_ARRAY_INSERT);
                }
            }
            break;
        case ACTION_ADD:
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id . "#" . $id, 'Добавление новой записи...', FORM_for_ADD);
            break;
        case BTN_ADD:
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id, '', REC_FORM_INSERT);
            $output = "Запись добавлена в PFR_LOCATIONS.";
            break;
        case ACTION_EDIT:
            $table_fields[0]['VALUE'] = $id;
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id . "#" . $id, 'Редактирование записи...', FORM_for_EDIT);
            break;
        case ACTION_CLONE:
            $table_fields[0]['VALUE'] = $id;
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id . "#" . $id, 'Добавление новой записи на основе имеющейся...', FORM_for_CLONE);
            break;
        case BTN_SAVE:
            $table_fields[0]['VALUE'] = $id;
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id . "#" . $id, '', REC_UPDATE);
            $output = "Запись в PFR_LOCATIONS с ID = ".$id." изменена.";
            break;
        case ACTION_REMOVE:
            $table_fields[0]['VALUE'] = $id;
            $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF']."?tems=".$tems."&host=".$host."&mq=".$mq."&template=".$template."&service=".$service."&id=".$id . "#" . $id, '', REC_DELETE);
            $output = "Запись c ID = " . $id . " удалена из PFR_LOCATIONS.";
            break;
        case ACTION_REMOVE_SELECTED:
            if (isset($_POST['chkbx'])) {
                $aCheckBoxesArray = array_filter($_POST['chkbx']);
                foreach ($aCheckBoxesArray as $key => $str) {
                    list($c, $id_sel) = explode('*', $key);
                    if (!empty($id_sel)) {
                        $table_fields[0]['VALUE'] = $id_sel;
                        $only_input_form = record_form($connection_TBSM, $table_fields, $_SERVER['PHP_SELF'] . "?tems=" . $tems . "&host=" . $host . "&mq=" . $mq . "&template=" . $template . "&service=" . $service . "&id=" . $id . "#" . $id, '', REC_DELETE);
                    }
                }
                $output = "Отмеченные записи удалены из PFR_LOCATIONS.";
            }
            else
                $output = "Не отмечено ни одной записи!";
            break;
        default:
            break;
    }
}

// the single record web-form display only
if ($only_input_form == 1)
    exit;
if ($only_input_form == -1)
    $output = 'Ошибка выполнения запроса к БД!';

// top header
$title = "Управление записями в таблице PFR_LOCATIONS";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
require 'functions/header_2.php';

?>
<!-- web-form begin -->
<br><br><form action="<?php echo $_SERVER['PHP_SELF']; ?>?tems=<?php echo $tems; ?>&host=<?php echo $host; ?>&mq=<?php echo $mq; ?>&template=<?php echo $template; ?>&service=<?php echo $service; ?>#<?php echo $id; ?>" method="post" id="formId">

    <u>Выберите из TBSM шаблон и сервис:</u><br><br>
    <!-- template select -->
    <?php
    $sel = "select distinct SERVICESLANAME from TBSMBASE.SERVICEINSTANCE";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    ?>
    Шаблон:
    <select size = "1" name = "formId[template]">
        <?php
        while ($row = db2_fetch_assoc($stmt)) {
            list($temp, $rest) = explode(':', $row['SERVICESLANAME'], 2);
            ?><option value = <?php echo $row['SERVICESLANAME']; ?> <?php echo empty(strcmp($row['SERVICESLANAME'], $template)) ? ' selected ' : ''; ?>><?php echo $temp ?></option><?php ;
        }
        ?>
    </select>
    <input type="submit" class="btn_blue" name="formId[sendRequest]" value="Выбрать шаблон" />
    <?php
    if(!empty($template))
        echo "&#10004;";
    echo "<br><br>";

    // service select
    if (!empty($template)) {
        ?>
        Фильтр по сервису:
        <input type="text" name="formId[mask]" size="20" maxlength="32" value="<?php echo $mask; ?>">
        <input type="submit" class="btn_blue" name="formId[sendRequest]" value="<?php echo empty($mask) ? "Применить фильтр" : "Очистить фильтр"; ?>"/>
        &nbsp;(опционально)<br><br>
        <?php

        $sel = "select SERVICEINSTANCENAME from TBSMBASE.SERVICEINSTANCE where SERVICESLANAME = '$template' and SERVICEINSTANCENAME like '%$mask%'";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
        ?>
        Сервис:
        <select size="1" name="formId[service]">
            <?php
            while ($row = db2_fetch_assoc($stmt)) {
                ?>
                <option value = <?php echo $row['SERVICEINSTANCENAME']; ?><?php echo empty(strcmp($row['SERVICEINSTANCENAME'], $service)) ? ' selected ' : ''; ?>><?php echo $row['SERVICEINSTANCENAME']; ?></option><?php ;
            }
            ?>
        </select>
        <input type="submit" class="btn_blue" name="formId[sendRequest]" value="Выбрать сервис"/>
        <?php
        if(!empty($service))
            echo "&#10004;";
    }
    echo "<br><br>";

    // if service already selected
    if (!empty($service)) {
        // query for PFR_LOCATIONS records filtered by service name
        $sel = "select ID, PFR_ID_TORG, PFR_NAZN, NODE, PFR_OBJECT, PFR_OBJECTSERVER, INCIDENT_SEND, SUBCATEGORY, AGENT_NODE, SITFILTER, PFR_KE_TORS, SERVICE_NAME
        from DB2INST1.PFR_LOCATIONS
        where SERVICE_NAME = '$service'  
        order by ID asc";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        // second PFR_LOCATIONS table web-page output
        echo "<br><br>Записи из PFR_LOCATIONS, отфильтрованные по имени выбранного сервиса (<b>".$service."</b>):<br><br>";
        echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
        foreach ($table_titles as $cell)
            echo "<th>" . $cell . "</th>";
        echo "<th>Действия с записями</th>";
        echo "</tr>";

        $row_count = 0;
        while ($row = db2_fetch_assoc($stmt)) {
            $row_count = $row_count + 1;
            echo "<tr>";
            foreach ($table_titles as $cell)
                echo "<td " . ($cell == 'SERVICE_NAME' ? "class=\"calendar_fill\"" : "") . ">" . $row[$cell] . "</td>";
            // operations buttons
            echo "<td>";
            ?>
            <button type="submit" name="formId[sendRequest]" value="<?php echo ACTION_ADD; ?>"
                    title="<?php echo ACTION_ADD; ?>"">
            <img src="images/new.png"></button>
            <button type="submit" name="formId[sendRequest]" value="<?php echo ACTION_EDIT . ":" . $row['ID']; ?>"
                    title="<?php echo ACTION_EDIT; ?>""><img src="images/edit.png"></button>
            <button type="submit" name="formId[sendRequest]" value="<?php echo ACTION_CLONE . ":" . $row['ID']; ?>"
                    title="<?php echo ACTION_CLONE; ?>""><img src="images/copy.png"></button>
        <button type="submit" name="formId[sendRequest]" value="<?php echo ACTION_REMOVE . ":" . $row['ID']; ?>"
                title="<?php echo ACTION_REMOVE; ?>" onclick="return confirm('Вы действительно хотите удалить?')">
                <img
                        src="images/delete.png"></button><?php
            echo "</td>";
            echo "</tr>";
        }
        echo "<tr>";
        echo "<td colspan=0>Количество строк в выборке: " . $row_count . "</td>";
        echo "</tr>";
        echo "</table>";

        // MQ channels
        echo "<br><br><hr><br><br>";
        echo "<u>Для добавления каналов MQ выберите TEMS, хост и менеджер MQ:</u><br><br>";
        // TEMS select
        $sel = "select distinct PFR_OBJECT FROM DB2INST1.PFR_LOCATIONS where SERVICE_NAME like 'ITM%' or SERVICE_NAME = 'tems-main'";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
        ?>
        TEMS:
        <select size="1" name="formId[tems]">
            <?php
            while ($row = db2_fetch_assoc($stmt)) {
                ?>
                <option value = <?php echo $row['PFR_OBJECT']; ?><?php echo empty(strcmp($row['PFR_OBJECT'], $tems)) ? ' selected ' : ''; ?>><?php echo $row['PFR_OBJECT'] ?></option><?php ;
            }
            ?>
        </select>
        <input type="submit" class="btn_blue" name="formId[sendRequest]" value="Выбрать TEMS"/>
        <?php
        if (!empty($tems))
            echo "&#10004;";
        echo "<br><br>";

        // host select
        if ($action == SELECT_TEMS) {
            // query for nodes, MQ managers and channels on selected TEMS
            $pass = array_key_exists($tems, $array_exeptions) ? $array_exeptions[$tems]["root_password"] : "...";
            $hub = array_key_exists($tems, $array_exeptions) ? $array_exeptions[$tems]["HUB"] : 'HUB' . substr($tems, 3, 3);
            remote_exec($tems, 22, 'root', $pass, "echo http://" . $tems . ":1920///cms/soap > /tmp/URLS.txt", '', false);
            remote_exec($tems, 22, 'root', $pass, "echo \"<CT_Get><userid>sysadmin</userid><password>" . (array_key_exists($tems, $array_exeptions) ? $array_exeptions[$tems]["sysadmin_password"] : "...") . "</password><object>Channel_Summary</object><attribute>Host_Name</attribute><attribute>MQ_Manager_Name</attribute><attribute>Channel_Name</attribute></CT_Get>\" > /tmp/SOAPREQ.txt", '', false);
            remote_exec($tems, 22, 'root', $pass, ". /opt/IBM/ITM/config/" . $tems . "_ms_" . $hub . ".config; /opt/IBM/ITM/lx8266/ms/bin/kshsoap /tmp/SOAPREQ.txt /tmp/URLS.txt", $res_file, false);

            // XML prepare
            fclose(fopen($res_xml, 'w'));
            $xml_content = false;
            foreach (file($res_file) as $line) {
                if (strpos($line, "<DATA>") !== false)
                    $xml_content = true;
                if ($xml_content)
                    file_put_contents($res_xml, $line, FILE_APPEND);
                if (strpos($line, "</DATA>") !== false)
                    $xml_content = false;
            }
        }
        if (!empty($tems)) {
            // XML parsing
            $xmlStr = file_get_contents($res_xml);
            $xmlObj = simplexml_load_string($xmlStr);
            $arrXml = objectsIntoArray($xmlObj);

            ?>
            Хост:
            <select size="1" name="formId[host]"><?php
                $hosts_arr = array_unique(array_column($arrXml['ROW'], 'Host_Name'));
                asort($hosts_arr);
                foreach ($hosts_arr as $h) {
                    ?>
                    <option
                    value= <?php echo $h; ?><?php echo empty(strcmp($h, $host)) ? ' selected ' : ''; ?>><?php echo $h; ?></option><?php ;
                }
                ?>
            </select>
            <input type="submit" class="btn_blue" name="formId[sendRequest]" value="Выбрать хост"/>
            <?php
            if (!empty($host))
                echo "&#10004;";
            echo "<br><br>";
        }

        // MQ manager select
        if (!empty($host)) {
            ?>
            Менеджер MQ:
            <select size="1" name="formId[mq]">
                <?php
                $mq_arr = array_unique(array_column(array_filter($arrXml['ROW'], function ($rec) {
                    return $rec['Host_Name'] == $GLOBALS['host'];
                }), 'MQ_Manager_Name'));
                asort($mq_arr);
                foreach ($mq_arr as $m) {
                    ?>
                    <option value = <?php echo $m; ?><?php echo empty(strcmp($m, $mq)) ? ' selected ' : ''; ?>><?php echo $m; ?></option><?php ;
                }
                ?>
            </select>
            <input type="submit" class="btn_blue" name="formId[sendRequest]" value="Выбрать менеджер MQ"/>
            <?php
            if (!empty($mq))
                echo "&#10004;";
            echo "<br><br>";
        }

        // work table output
        if (!empty($mq) and !empty($service)) {
            $channel_arr = array_unique(array_column(array_filter($arrXml['ROW'], function ($rec) {
                return $rec['Host_Name'] == $GLOBALS['host'] and $rec['MQ_Manager_Name'] == $GLOBALS['mq'];
            }), 'Channel_Name'));
            asort($channel_arr);

            // existing channels in PFR_LOCATIONS
            foreach ($channel_arr as $ch) {
                $node = trim($mq . ":" . $host . ":MQ:" . $ch);
                $sel = "select count(*) as CNT from DB2INST1.PFR_LOCATIONS where NODE = '$node'";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                $row = db2_fetch_assoc($stmt);
                $count = intval($row['CNT']);

                $sel = "select $table_titles_string from DB2INST1.PFR_LOCATIONS where NODE = '$node'";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                if ($count >= 1)
                    while ($row = db2_fetch_assoc($stmt))
                        $table_records[] = array(
                            'channel' => $ch,
                            'ID' => $row['ID'],
                            'NODE' => $row['NODE'],
                            'PFR_KE_TORS' => $row['PFR_KE_TORS'],
                            'SERVICE_NAME' => $row['SERVICE_NAME'],
                            'count' => $count,
                        );
                else
                    $table_records[] = array(
                        'channel' => $ch,
                        'ID' => '',
                        'NODE' => '',
                        'PFR_KE_TORS' => '',
                        'SERVICE_NAME' => '',
                        'count' => $count,
                    );
            }

            // non-existing channels in PFR_LOCATIONS
            if (!empty($table_records) and !empty(array_filter(array_column($table_records, 'ID')))) {
                $list = "";
                foreach ($table_records as $rec)
                    if (!empty($rec['ID']))
                        $list = $list . "'" . $rec['ID'] . "', ";
                $list = rtrim($list, ", ");
                $node = trim($mq . ":" . $host . ":MQ:%");
                $sel = "select $table_titles_string from DB2INST1.PFR_LOCATIONS where NODE like '$node' and ID not in ($list)";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);

                while ($row = db2_fetch_assoc($stmt))
                    $table_records[] = array(
                        'channel' => '',
                        'ID' => $row['ID'],
                        'NODE' => $row['NODE'],
                        'PFR_KE_TORS' => $row['PFR_KE_TORS'],
                        'SERVICE_NAME' => $row['SERVICE_NAME'],
                        'count' => -1,
                    );
            }

            // channels web-form table
            if (!empty($table_records)) {
                echo "<br><br>Список каналов:<br><br>";
                echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
                echo "<tr>";
                echo "<th>";
                ?><!--<button title="Инвертировать выделение">&#10004;</button>--><?php
                echo "</th>";
                echo "<th>Имя канала в TEMS</th>";
                foreach ($table_titles as $field)
                    echo "<th>$field</th>";
                echo "<th>Действия с записями</th>";
                echo "</tr>";

                foreach ($table_records as $rec) {
                    echo "<tr>";
                    // checkbox
                    echo "<td>";
                    ?><a name="<?php echo $rec['ID']; ?>"></a>
                    <input type="checkbox" name="chkbx[<?php echo $rec['channel'] . '*' . $rec['ID']; ?>]"/><?php
                    echo "</td>";

                    // channel name and PFR_LOCATIONS fields
                    echo "<td " . ($rec['count'] < 0 ? "bgcolor='#ffb6c1'" : "") . ">" . $rec['channel'] . "</td>";
                    foreach ($table_titles as $field)
                        echo "<td " . (($rec['count'] == 0 or $rec['count'] > 1) ? "bgcolor='#ffb6c1'" : "") . ">" . $rec[$field] . "</td>";

                    // command buttons
                    echo "<td align='center'>";
                    if ($rec['count'] == 0) {
                        ?>
                    <button type="submit" name="formId[sendRequest]"
                            value="<?php echo ACTION_ADD_CHANNEL . ":" . $rec['channel']; ?>"
                            title="<?php echo ACTION_ADD_CHANNEL; ?>"><img src="images/new.png"></button><?php ;
                    } else {
                        ?>
                        <button type="submit" name="formId[sendRequest]"
                                value="<?php echo ACTION_EDIT . ":" . $rec['ID']; ?>"
                                title="<?php echo ACTION_EDIT; ?>""><img src="images/edit.png"></button>
                        <button type="submit" name="formId[sendRequest]"
                                value="<?php echo ACTION_CLONE . ":" . $rec['ID']; ?>"
                                title="<?php echo ACTION_CLONE; ?>""><img src="images/copy.png"></button>
                    <button type="submit" name="formId[sendRequest]"
                            value="<?php echo ACTION_REMOVE . ":" . $rec['ID']; ?>"
                            title="<?php echo ACTION_REMOVE; ?>"
                            onclick="return confirm('Вы действительно хотите удалить?')"><img
                                    src="images/delete.png"></button><?php ;
                    }
                    echo "</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td colspan=0 align='center'>";
                ?>
                <button type="submit" name="formId[sendRequest]" value="<?php echo ACTION_ADD_CHANNELS_SELECTED; ?>"
                        title="Будут добавлены только отмеченные записи со значком добавления!"><img
                            src="images/new.png"> <?php echo ACTION_ADD_CHANNELS_SELECTED; ?>
                </button>
                &emsp;&emsp;
                <button type="submit" name="formId[sendRequest]" value="<?php echo ACTION_REMOVE_SELECTED; ?>"
                        title="Будут удалены только отмеченные записи со значком удаления!"><img
                            src="images/delete.png"> <?php echo ACTION_REMOVE_SELECTED; ?>
                </button>
                <?php
                echo "</td>";
                echo "</tr>";
                echo "</table><br><br>";
            }
        }
    }
    ?>
    <!-- web-form end -->
</form>
<?php

// database connection close
db2_close($connection_TBSM);

/******************************************************************************************************************/

function objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
    $arrData = array();

    // if input is object, convert into array
    if (is_object($arrObjData)) {
        $arrObjData = get_object_vars($arrObjData);
    }

    if (is_array($arrObjData)) {
        foreach ($arrObjData as $index => $value) {
            if (is_object($value) || is_array($value)) {
                $value = objectsIntoArray($value, $arrSkipIndices); // recursive call
            }
            if (in_array($index, $arrSkipIndices)) {
                continue;
            }
            $arrData[$index] = $value;
        }
    }
    return $arrData;
}

?>
</body>
</html>
