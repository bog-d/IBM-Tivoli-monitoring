<?php
//ini_set('memory_limit', '-1');
/*
	by GDV
	2018 - RedSys
*/
// web or Excel output
$web = true;
if (isset($_GET['format']) and $_GET['format'] == 'excel') {
    require_once 'Classes/PHPExcel.php';
    $pExcel = new PHPExcel();
    $pExcel->setActiveSheetIndex(0);
    $aSheet = $pExcel->getActiveSheet();
    $aSheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
    $aSheet->getPageSetup()->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
    $aSheet->getPageMargins()->setTop(1);
    $aSheet->getPageMargins()->setRight(0.75);
    $aSheet->getPageMargins()->setLeft(0.75);
    $aSheet->getPageMargins()->setBottom(1);
    $aSheet->setTitle('Журнал событий');
    $pExcel->getDefaultStyle()->getFont()->setName('Arial');
    $pExcel->getDefaultStyle()->getFont()->setSize(8);
    $web = false;
    include 'css/excel.php';
}
else {
    header('Content-Type: text/html;charset=UTF-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
        <link href="css/style.css" type="text/css" rel="stylesheet">
        <link href="css/popup.css" type="text/css" rel="stylesheet">
        <title>Журнал событий</title>
        <script src="scripts/jquery-3.2.1.min.js"></script>
        <script src="scripts/cellSelection.min.js"></script>
        <script src="scripts/common.js"></script>
        <script src="scripts/event_history.js"></script>
    </head>
    <body>
    <?php
}

require_once 'connections/TBSM.php';
require_once 'connections/WHFED.php';
include 'functions/tbsm.php';
include 'functions/user_roles.php';

$ke_obj = array();		// array of unique PFR_KE_TORS values from PFR_LOCATIONS table
$week_days = array ('пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс', '&nbsp;&nbsp;&nbsp;&nbsp;');		// array of days of week
$months = array ('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');		// array of months
$history = 60;			// maximum days for history
$page_size = 100;		// page size (records per page)

$table_titles = array (
        "Срабатывание ситуации",
        "Номер",
        "Отделение",
        "Узел",
        "Объект",
        "КЭ",
        "Код события в ТОРС",
        "Описание",
        "Критичность",
        "Номер инцидента",
        "Класс",
        "Номер классификации",
        "Группа классификации",
        "Номер РЗ",
    ); // history table titles

$table_cells = array (
        'filter' => array (
            "time" => "",
            "number" => "",
            "pfr" => "",
            "node" => "",
            "object" => "",
            "ke" => "",
            "sit_code" => "",
            "descr" => "",
            "severity" => "",
            "incident" => "",
            "class" => "",
            "classificationid",
            "classificationgroup",
            "wonum",
        )); // history table cells

$incident_array = [];
$incident_count = 0;
$wonum_array = [];
$wonum_count = 0;

$level = 0;					// iteration level
$results = [];				// array for endpoint childs
$multi_scheme = false;	 	// selected service is container not endpoint service

// get script parameters
$NODEID = (isset($_POST["el"]) and $_POST["el"] == 'serv') ? (isset($_POST["new_value"]) ? $_POST["new_value"] : "") : (isset($_GET["ServiceName"]) ? $_GET["ServiceName"] : "");
$KE_OBJECT = (isset($_POST["el"]) and $_POST["el"] == 'ke') ? (isset($_POST["new_value"]) ? $_POST["new_value"] : "") : (isset($_GET["KE_OBJECT"]) ? $_GET["KE_OBJECT"] : '');
$INCIDENT = (isset($_POST["el"]) and $_POST["el"] == 'inc') ? (isset($_POST["new_value"]) ? $_POST["new_value"] : "") : (isset($_GET["INCIDENT"]) ? $_GET["INCIDENT"] : '');
$all_events = (empty($NODEID) and empty($KE_OBJECT) and empty($INCIDENT));
$period = isset($_GET["TimeRange"]) ? $_GET["TimeRange"] : date('Y-m-d', time());
if (isset($_COOKIE["calendar_date"]))
    $period = $_COOKIE["calendar_date"];
if (!empty($INCIDENT))
    $period = date('Y-m-d', time() - 86400 * ($history - 1));
$page = isset($_GET["Page"]) ? $_GET["Page"] : 0;
$table_cells['filter']['sit_code'] = isset($_POST["filtered_sit_code"]) ? $_POST["filtered_sit_code"] : (isset($_GET["SitCode"]) ? $_GET["SitCode"] : $table_cells['filter']['sit_code']);

// KE search
if (!empty($KE_OBJECT))
    $ke_obj[] = $KE_OBJECT;
else if (!empty($NODEID)) {
    $sel_TBSM = "SELECT SERVICEINSTANCEID FROM TBSMBASE.SERVICEINSTANCE WHERE SERVICEINSTANCENAME = '$NODEID'";
    $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
    $result_TBSM = db2_execute($stmt_TBSM);
    $row_TBSM = db2_fetch_assoc($stmt_TBSM);

    // endpoint service
    if (ext_tree($row_TBSM['SERVICEINSTANCEID'], $connection_TBSM, $level, $results))
        $multi_scheme = true;
    else
        $results[0]['service'] = $NODEID;

    $services_str = implode("', '", array_column($results, 'service'));

    $sel_TBSM = "SELECT PFR_KE_TORS FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME in ('{$services_str}')";
    $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
    $result_TBSM = db2_execute($stmt_TBSM);
    while ($row = db2_fetch_assoc($stmt_TBSM))
        $ke_obj[] = $row['PFR_KE_TORS'];

    $ke_obj = array_unique($ke_obj);
}

// events selection from DB
if (!empty($KE_OBJECT) or !empty($NODEID))
    $sql = "SELECT * FROM DB2INST1.PFR_EVENT_HISTORY WHERE FIRST_OCCURRENCE >= to_char (current date - ".$history." DAY, 'YYYY-MM-DD') AND PFR_KE_TORS is not null AND PFR_KE_TORS <> '' AND PFR_KE_TORS IN ".("('".implode("', '", $ke_obj)."')")." ORDER BY FIRST_OCCURRENCE DESC";
else if (!empty($INCIDENT))
    $sql = "SELECT * FROM DB2INST1.PFR_EVENT_HISTORY WHERE FIRST_OCCURRENCE >= to_char (current date - ".$history." DAY, 'YYYY-MM-DD') AND TTNUMBER = '$INCIDENT' ORDER BY FIRST_OCCURRENCE DESC";
else
    $sql = "SELECT * FROM DB2INST1.PFR_EVENT_HISTORY WHERE substr(FIRST_OCCURRENCE, 1, 10) = '$period' ORDER BY FIRST_OCCURRENCE DESC";

$stmt_WHFED = db2_prepare($connection_WHFED, $sql);
$result_WHFED = db2_execute($stmt_WHFED);

$table_size = 0;
while ($row = db2_fetch_assoc($stmt_WHFED)) {
    $row_time = substr($row['FIRST_OCCURRENCE'], 0, 19);
    $table_cells[] = array ( "time" => $row_time,
                             "number" => $row['SERIAL'],
                             "pfr" => $row['PFR_TORG'],
                             "node" => $row['NODE'],
                             "object" => $row['PFR_OBJECT'],
                             "ke" => $row['PFR_KE_TORS'],
                             "sit_code" => $row['PFR_SIT_NAME'],
                             "descr" => $row['DESCRIPTION'],
                             "severity" => $row['SEVERITY'],
                             "incident" => $row['TTNUMBER'],
                             "class" => $row['PFR_TSRM_CLASS'],
                             "classificationid" => $row['CLASSIFICATIONID'],
                             "classificationgroup" => $row['CLASSIFICATIONGROUP'],
                             "wonum" => $row['PFR_TSRM_WORDER'],
                            );
    if ($row_time >= $period and (empty($table_cells['filter']['sit_code']) or $table_cells['filter']['sit_code'] == $row['PFR_SIT_NAME'])) {
        $table_size++;
        if (array_search($row['TTNUMBER'], $incident_array) === false)
            $incident_array[] = $row['TTNUMBER'];
        if (array_search($row['PFR_TSRM_WORDER'], $wonum_array) === false)
            $wonum_array[] = $row['PFR_TSRM_WORDER'];
    }
}

// incident and wonum counting
$incident_count = count(array_filter($incident_array));
$wonum_count = count(array_filter($wonum_array));

// dates array form
foreach ($table_cells as $key => $row) {
    $col_time[$key] = $row['time'];
}
// time cut
function writetime_cut(&$val) {
    $val = substr($val, 0, 10);
}
array_walk($col_time, 'writetime_cut');
// dates count array
$dates_count = array_count_values(array_filter($col_time));
// unique dates array
$col_d = array_unique($col_time);
$col_date = array_filter($col_d);

// top header
$title = 'Журнал событий '.($all_events ? 'за ' : 'за период с ').substr($period, 8, 2).'.'.substr($period, 5, 2).'.'.substr($period, 0, 4).' г.'.($all_events ? '' : ' по настоящее время').($web ? '<br><br>' : ' ');
if (!empty($KE_OBJECT)) {
    $el = 'ke'; $text = $KE_OBJECT;
    $title = $title . "для КЭ $KE_OBJECT";
}
else if (!empty($NODEID)) {
    $el = 'serv'; $text = $NODEID;
    $title = $title . "для ".($multi_scheme ? "подсистемы " : "индикатора ").$NODEID;
}
else if (!empty($INCIDENT)) {
    $el = 'inc'; $text = $INCIDENT;
    $title = $title . "для инцидента $INCIDENT";
}
else {
    $el = ''; $text = '';
    $title = $title . "все записи";
}

if ($web) {
    $param = (empty($NODEID) ? "" : "ServiceName={$NODEID}&") .
            (empty($KE_OBJECT) ? "" : "KE_OBJECT={$KE_OBJECT}&") .
            (empty($INCIDENT) ? "" : "INCIDENT={$INCIDENT}&");
    $links = array (
        "<div class='update_data' ".($acs_role == 'admin' ? "" : "hidden='hidden'>").">
            <a href='http://10.103.0.60/pfr_other/event_history_new.php?{$param}' target='_blank'>Перейти к новой версии</a>
        </div>"
    );
    require 'functions/header_1.php';
    require 'functions/header_2.php';

    // redefine input parameters
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr><td>";
    ?>
    <br>
    <h4 class="table_loc_toggle" title="Кликните для показа/скрытия календаря...">Переопределить параметры поиска...</h4>
    <table class="loc_show" cellpadding="10">
        <tr>
            <td colspan="0">
                Поиск по индикатору или КЭ производится за период от выбранной даты по настоящее время.<br>
                Поиск по инциденту производится во всём доступном диапазоне дат.<br>
                Полный журнал событий выводится на одну выбранную дату.
            </td>
        </tr>
        <tr>
            <td align="center" colspan="0">
                <?php
                // calendar
                echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\" class='calendar'>";
                // months
                echo "<tr>";
                for ($i = 2; $i >= 0; $i--) {
                    echo "<th  align=\"center\" colspan=7>";
                    $today = getdate();
                    $j = $today['mon'] - 1 - $i;
                    echo $months[$j < 0 ? 12 + $j : $j] . ' ' . ($j < 0 ? $today['year'] - 1 : $today['year']);
                    echo "</th>";
                    echo "<th>";
                    echo "</th>";
                }
                echo "</tr>";
                // week days
                echo "<tr>";
                for ($i = 0; $i < 3; $i++)
                    for ($j = 0; $j < 8; $j++) {
                        echo "<th>";
                        echo "<font" . (($j == 5 or $j == 6) ? " color=\"red\">" : ">");
                        echo $week_days[$j];
                        echo "</font>";
                        echo "</th>";
                    }
                echo "</tr>";
                // days
                for ($i = 0; $i < 6; $i++) {
                    echo "<tr>";
                    for ($k = 2; $k >= 0; $k--) {
                        $k_month = ($today['mon'] - $k) <= 0 ? (12 + $today['mon'] - $k) : ($today['mon'] - $k);
                        $k_year = ($today['mon'] - $k) <= 0 ? ($today['year'] - 1) : $today['year'];
                        $k_1st = getdate(mktime(0, 0, 0, $k_month, 1, $k_year));
                        $day = $k_1st['wday'] == 0 ? 6 : ($k_1st['wday'] - 1); // 0 - пн., 1 - вт., ... 6 -вскр.
                        for ($j = 0; $j < 7; $j++) {
                            $cell = mktime(0, 0, 0, $k_month, $i * 7 + $j + 1 - $day, $k_year);
                            $c = getdate($cell);
                            $d = date('j', $cell);
                            $dd = date('Y-m-d', $cell);
                            echo "<td data-date='$dd' " . (($cell > (time() - 86400 * $history) and $cell <= time() and $k_month == $c['mon']) ? "" : " class='ignore not_active'") . (($dd == $period and $k_month == $c['mon']) ? " class='jq-cell-selected'" : "") . ">";
                            $href = $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $dd . "&SitCode=" . $table_cells['filter']['sit_code'];
                            $count = array_key_exists($dd, $dates_count) ? $dates_count[$dd] : '';
//                                        echo ($cell > (time() - 86400 * $history) and $cell <= time()) ? "<a href=\"$href\" title=\"$count\">" : "";
                            // echo ($k == 0 and $today['mday'] == $d) ? "<b>" : "";
                            echo in_array($dd, $col_date) ? "<b>" : "";
                            if ($k_month == $c['mon'])
                                echo $d;
                            echo in_array($dd, $col_date) ? "</b>" : "";
                            // echo ($k == 0 and $today['mday'] == $d) ? "</b>" : "";
//                                        echo ($cell > (time() - 86400 * $history) and $cell <= time()) ? "</a>" : "";
                            echo "</td>";
                        }
                        echo "<td>";
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
                ?>
            </td>
        </tr>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?TimeRange=<?php echo $period; ?>" method="post">
        <tr>
            <td>
                Фильтр:
                <input type="text" name="new_value" size="50" maxlength="256" value="<?php echo $text; ?>"> <br><br>
                <label><input type="radio" name="el" value="serv" <?php echo $el == 'serv' ? 'checked' : ''; ?> > индикатор (подсистема)</label>&emsp;&emsp;
                <label><input type="radio" name="el" value="ke" <?php echo $el == 'ke' ? 'checked' : ''; ?> > КЭ        </label>            &emsp;&emsp;
                <label><input type="radio" name="el" value="inc" <?php echo $el == 'inc' ? 'checked' : ''; ?> > инцидент</label>
            </td>
            <td align="right" valign="bottom">
                <input class="get_date" type="submit" value="Применить новые параметры поиска">
            </td>
        </tr>
        <tr>
            <td colspan="0">
                <?php echo "<a href=\"http://10.103.0.60{$_SERVER['PHP_SELF']}?ServiceName={$NODEID}&KE_OBJECT={$KE_OBJECT}&INCIDENT={$INCIDENT}&TimeRange={$period}&SitCode={$table_cells['filter']['sit_code']}\" title='Нажмите правую кнопку мыши и выберите \"Копировать ссылку\"'><img src='images/copy.png' hspace='5'>Ссылка с параметрами поиска</a>"; ?>
            </td>
        </tr>
        </form>
    </table>
    </td>

    <?php
    // export to Excel
    echo "<td align='right' valign='bottom'>";
    if ($table_size != 0)
        echo "<b><a href=\"{$_SERVER['PHP_SELF']}?format=excel&ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "\" title=\"Экспорт таблицы в файл MS Excel\"><img src=\"images/xls.png\" align=\"middle\" hspace=\"10\" width=\"24\" height=\"24\">Экспорт в Excel</a></b><br><br>";
    echo "</td></tr></table><br><br>";
}

// table output
if ($table_size == 0)
    echo "<br><h3 align='center'><div class='red_message'>Данные не найдены. Переопределите параметры поиска!</div></h3>";
else {
    // table summary
    if ($web) {
        echo "<table border='1' cellspacing='0' cellpadding='8'>";
        echo "<tr>";
        echo "<th colspan=0>";
        if ($page > 0) {
            echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=0\" title=\"Первая страница\">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;";
            echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=" . ($page - 1) . "\" title=\"Предыдущая страница\">&lt;</a>&nbsp;&nbsp;&nbsp;";
        }
        echo "Страница " . ($page + 1) . " из " . (ceil($table_size / $page_size)) . " (записи с " . ($page * $page_size + 1 > $table_size ? $table_size : $page * $page_size + 1) . " по " . (($page + 1) * $page_size < $table_size ? ($page + 1) * $page_size : $table_size) . " из " . $table_size . ") | Кол-во уникальных инцидентов: {$incident_count} | Кол-во уникальных РЗ: {$wonum_count}";

        if (($page + 1) * $page_size < $table_size) {
            echo "&nbsp;&nbsp;&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=" . ($page + 1) . "\" title=\"Следующая страница\">&gt;</a>";
            echo "&nbsp;&nbsp;&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=" . (ceil($table_size / $page_size) - 1) . "\" title=\"Последняя страница\">&gt;&gt;</a>";
        }
        echo "</th>";
        echo "</tr>";
    }
    else {
        $aSheet->getHeaderFooter()->setOddFooter("&CСтраница &P из &N | Кол-во уникальных инцидентов: {$incident_count} | Кол-во уникальных РЗ: {$wonum_count}");
    }

    // table titles
    if ($web) {
        echo "<tr>";
        foreach ($table_titles as $col)
            echo "<th>" . $col . "</th>";
        echo "</tr>";
    }
    else {
        foreach ($table_titles as $key => $col)
            $aSheet->setCellValueByColumnAndRow($key, 1, $col);
        $aSheet->getStyle('A1:N1')->applyFromArray($style_header);
    }

    // table filters
    if ($web) {
        echo "<tr>";
        ?>
        <form
        action="<?php echo $_SERVER['PHP_SELF']; ?>?ServiceName=<?php echo $NODEID; ?>&KE_OBJECT=<?php echo $KE_OBJECT; ?>&INCIDENT=<?php echo $INCIDENT; ?>&TimeRange=<?php echo $period; ?>"
        method="post"><?php
        echo "<td class=\"col_filter\" colspan='6'></td>";
        // Код события в ТОРС
        echo "<td class=\"col_filter\">";
        $array_sit_code = array_filter(array_unique(array_column($table_cells, 'sit_code')));
        asort($array_sit_code);
        ?>
        <table>
        <tr>
            <td>
                <select name="filtered_sit_code" size="1">
                    <option value="" <?php echo empty($table_cells['filter']['sit_code']) ? 'selected' : ''; ?>>
                        (нет фильтра)
                    </option><?php
                    foreach ($array_sit_code as $filtered_sit_code) {
                        ?>
                        <option
                        value="<?php echo $filtered_sit_code; ?>" <?php echo $filtered_sit_code == $table_cells['filter']['sit_code'] ? 'selected' : ''; ?>> <?php echo $filtered_sit_code; ?> </option><?php ;
                    }
                    ?></select>
            </td>
            <td>
                <button type="submit" value="Фильтр по полю" title="Фильтр по полю"><img
                            src="images/filter.png"></button>
            </td>
        </tr></table><?php
        echo "</td>";
        echo "<td class=\"col_filter\" colspan='0'></td>";
        ?></form><?php
        echo "</tr>";
    }

    // table data
    $i = 0;
    foreach ($table_cells as $row) {
        // first empty row or unsuitable date skip
        if ($row['time'] == '' or $row['time'] < $period)
            continue;
        // filter(s) apply
        if (empty($table_cells['filter']['sit_code']) or $table_cells['filter']['sit_code'] == $row['sit_code']) {
            $i++;
            // page breaking
            if ($web and $i < $page * $page_size + 1)
                continue;
            if ($web)
                echo "<tr>";
            $col = 0;
            foreach ($row as $key => $cell) {
                switch ($key) {
                    case "time":
                        $time_output = substr($cell, 8, 2) . '.' . substr($cell, 5, 2) . '.' . substr($cell, 0, 4) . ' ' . substr($cell, 11);
                        if ($web)
                            echo "<td>{$time_output}</td>";
                        else
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $time_output);
                        break;
                    case "severity":
                        switch ($cell) {
                            case "5":
                                $class = "red_status"; break;
                            case "4":
                            case "3":
                            case "2":
                                $class = "yellow_status"; break;
                            case "1":
                                $class = "blue_status"; break;
                            case "0":
                                $class = "green_status"; break;
                            default:
                                $class = ""; break;
                        }
                        if ($web) {
                            echo "<td class='$class'>".array_search($cell, $severity_codes)."</td>";
                        }
                        else {
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, array_search($cell, $severity_codes));
                            if (!empty($class))
                                $aSheet->getStyle('I'.($i + 1).':I'.($i + 1))->applyFromArray($$class);
                        }
                        break;
                    case "incident":
                        if ($web) {
                            echo "<td>";
                                $tt = $cell;
                                echo "<a href=\"http://10.103.0.106/maximo/ui/maximo.jsp?event=loadapp&value=incident&additionalevent=useqbe&additionaleventvalue=ticketid=$tt&datasource=NCOMS\" target=\"_blank\" title=\"Переход в СТП к инциденту\">" . $tt . "</a>";
                            echo "</td>";
                        }
                        else {
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                        }
                        break;
                    case "class":
                        $class = ($cell == "-30" or $cell == "-10" or $cell == "3" or $cell == "4") ? "blue_status" : "";
                        if ($web) {
                            echo "<td class='$class'>{$class_codes[$cell]}</td>";
                        }
                        else {
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $class_codes[$cell]);
                            if (!empty($class))
                                $aSheet->getStyle('K'.($i + 1).':K'.($i + 1))->applyFromArray($$class);
                        }
                        break;
                    case 'ke':
                        if ($web)
                            echo "<td>{$cell}<br><font size='-1'> <a href=\"http://10.103.0.60/pfr_other/SCCD_trigger.php?KE={$cell}\" target=\"blank\" title=\"Перейти в 'Настройки интеграции с СТП'\"><img src=\"images/link.png\" align=\"top\" hspace=\"5\">к Интеграции</a><br><a href=\"http://10.103.0.106/maximo/ui/login?event=loadapp&value=CI&additionalevent=useqbe&additionaleventvalue=CINAME={$cell}\" target=\"blank\" title=\"Перейти к КЭ в ТОРС\"><img src=\"images/link_gray.png\" align=\"top\" hspace=\"5\">в ТОРС</a> </font></td>";
                        else
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                        break;
                    case 'sit_code':
                        if ($web) {
                            echo "<td>" . $cell;
                            // traceroute from ISM_SERVER_ICMP_STATUS situation
                            if ($cell == 'ISM_SERVER_ICMP_STATUS' and $row['severity'] == 5 and !empty($row['incident'])) {
                                // IP detect
                                $sel_TBSM = "SELECT URL FROM DB2INST1.PFR_LOCATIONS WHERE PFR_OBJECT = '{$row['object']}' and URL is not null";
                                $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
                                $result_TBSM = db2_execute($stmt_TBSM);
                                $ip_arr = [];
                                while ($row_TBSM = db2_fetch_assoc($stmt_TBSM))
                                    $ip_arr[] = $row_TBSM['URL'];
                                $ip_arr = array_unique(array_filter($ip_arr));

                                // time range detect
                                $inc_time = mktime(substr($row['time'], 11, 2), substr($row['time'], 14, 2), substr($row['time'], 17, 2),
                                                   substr($row['time'], 5, 2), substr($row['time'], 8, 2), substr($row['time'], 0, 4));
                                $sit_time_min = '1' . date("ymdHis", $inc_time - 301);
                                $sit_time_max = '1' . date("ymdHis", $inc_time + 301);

                                // traceroute detect
                                $sel_WHFED = "SELECT * FROM DB2INST1.PFR_TRACEROUTE WHERE (HOST = '{$row['object']}' or HOST in ('".implode("', '", $ip_arr).
                                    "')) and TIMESTAMP > '{$sit_time_min}' and TIMESTAMP < '{$sit_time_max}'";
                                $stmt_WHFED = db2_prepare($connection_WHFED, $sel_WHFED);
                                $result_WHFED = db2_execute($stmt_WHFED);
                                $row_WHFED = db2_fetch_assoc($stmt_WHFED);
                                if (empty($row_WHFED))
                                    echo "<br><font size='-1'>Результаты трассировки не найдены</font>";
                                else {
                                    echo "<div class='toggle'><font size='-1'>Показать/скрыть результаты трассировки</font></div>";
                                    echo "<div class='hide'><font size='-1'>" .
                                            substr($row_WHFED['TIMESTAMP'], 5, 2) . "." . substr($row_WHFED['TIMESTAMP'], 3, 2) . ".20" .
                                            substr($row_WHFED['TIMESTAMP'], 1, 2) . " " . substr($row_WHFED['TIMESTAMP'], 7, 2) . ":" .
                                            substr($row_WHFED['TIMESTAMP'], 9, 2) . ":" . substr($row_WHFED['TIMESTAMP'], 11, 2) .
                                            " с хоста {$row_WHFED['NODE']}:<br>" . str_replace("\n", "<br>", $row_WHFED['TRACE']) . "</font></div>";
                                }
                            }
                            echo "</td>";
                        }
                        else
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                        break;
                    default:
                        if ($web)
                            echo "<td>" . $cell . "</td>";
                        else
                            $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                        break;
                }
            }
            if ($web)
                echo "</tr>";
            if ($web and $i == ($page + 1) * $page_size)
                break;
        }
    }
    // table summary
    if ($web) {
        echo "<tr>";
        echo "<th colspan=0>";
        if ($page > 0) {
            echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=0\" title=\"Первая страница\">&lt;&lt;</a>&nbsp;&nbsp;&nbsp;";
            echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=" . ($page - 1) . "\" title=\"Предыдущая страница\">&lt;</a>&nbsp;&nbsp;&nbsp;";
        }
        echo "Страница " . ($page + 1) . " из " . (ceil($table_size / $page_size)) . " (записи с " . ($page * $page_size + 1 > $table_size ? $table_size : $page * $page_size + 1) . " по " . (($page + 1) * $page_size < $table_size ? ($page + 1) * $page_size : $table_size) . " из " . $table_size . ") | Кол-во уникальных инцидентов: {$incident_count} | Кол-во уникальных РЗ: {$wonum_count}";

        if (($page + 1) * $page_size < $table_size) {
            echo "&nbsp;&nbsp;&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=" . ($page + 1) . "\" title=\"Следующая страница\">&gt;</a>";
            echo "&nbsp;&nbsp;&nbsp;<a href=\"" . $_SERVER['PHP_SELF'] . "?ServiceName=" . $NODEID . "&KE_OBJECT=" . $KE_OBJECT . "&INCIDENT=" . $INCIDENT . "&TimeRange=" . $period . "&SitCode=" . $table_cells['filter']['sit_code'] . "&Page=" . (ceil($table_size / $page_size) - 1) . "\" title=\"Последняя страница\">&gt;&gt;</a>";
        }
        echo "</th>";
        echo "</tr>";
        echo "</table>";
    }
}

if (!$web) {
    // columns width autofit
    $cellIterator = $aSheet->getRowIterator()->current()->getCellIterator();
    $cellIterator->setIterateOnlyExistingCells(true);
    foreach ($cellIterator as $cell)
        $aSheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
    // insert, format and freeze title and header rows
    $aSheet->insertNewRowBefore(1);
    $aSheet->setCellValueByColumnAndRow(0, 1, $title);
    $aSheet->mergeCells('A1:N1');
    $aSheet->getStyle('A1:A1')->applyFromArray($style_title);
    $aSheet->freezePane('A3');
    // save Excel book
    header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition:attachment;filename="event_history.xlsx"');
    $objWriter = new PHPExcel_Writer_Excel2007($pExcel);
    $objWriter->save('php://output');
}

// databases connections close
db2_close($connection_TBSM);
db2_close($connection_WHFED);

if ($web) {
	?>
    </body>
    </html>
    <?php
}
