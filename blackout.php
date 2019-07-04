<?php
/*
	by GDV
	2018 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');

$ref_yes = '300';			// delay for form auto refresh
$ref_no = "∞";				// infinite delay
$delay = isset($_GET["auto_refresh"])? $_GET["auto_refresh"] : $ref_no;
$auto_refresh = ($delay == $ref_no ? false : true);
if (isset($_POST['formId']['ref'])) {
    $auto_refresh = ($_POST['formId']['ref'] == 'включить' ? true : false);
    $delay = ($auto_refresh ? $ref_yes : $ref_no);
}
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <meta http-equiv="Refresh" content="<?=$delay?>" />
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Работы по обслуживанию</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/jquery.stickytableheaders.min.js"></script>
    <script src="scripts/blackout.js"></script>
</head>
<body>
<?php
// common functions
require_once 'connections/MAXDB76.php';
include 'functions/regions.php';
include 'functions/utime.php';

// -------------------------------------------------------------------------------------------------------------------

// shift time (in hours) for operator's view
const SHIFT  = 12;
// blocks
const AIS_1 = "Автоматизированные информационные системы (основные КЭ)";
const AIS_2 = "Автоматизированные информационные системы (неосновные КЭ)";
// script work mode - operative or historical
const OPER = 'oper';
const HIST = 'hist';

// -------------------------------------------------------------------------------------------------------------------

// table titles
$title_cells = array (
        "",
        "Имя КЭ",
        "Код ОПФР",
        "Описание КЭ",
        "Среда",
        "Начало работ",
        "Окончание работ",
        "Продолжительность",
        "Описание",
        "Тип",
        "Статус",
    );

// table cells
$table_cells = array();

// identical cinames intersection time
$concurrence_data = [];
$concurrence_times = [];
$table_intersection = [];

// array of dates with works
$col_date_pre = array();
// array of unique dates with works
$col_date = array();
// array of base KE
$base_ke_arr = array();

// work types
$work_type = array (
        "PLAN" => "Плановая работа",
        "UNPLAN" => "Внеплановая работа",
        "ZEMERGENCY" => "Аварийная работа",
    );

/* REG – Зарегистрирована, показывать плановые даты начала и окончания
ACTIVE – Подтверждена, показывать плановые даты начала и окончания
NPROG - В работе, показывать фактическое начало и плановое окончание
EXPIRED – Закрыта, показывать фактические даты начало окончания */

// work status
$work_status = array (
        "REG" => "Зарегистрирована",
        "INPROG" => "В работе",
        "ACTIVE" => "Подтверждена",
        "EXPIRED" => "Закрыта",
    );

// gantt chart colors
$gantt_chart_colors = array (
        "0#PLAN" => array ("gantt_green_grad", "Плановая работа без перерыва сервиса"),
        "1#PLAN" => array ("gantt_green", "Плановая работа с перерывом сервиса"),
        "0#UNPLAN" => array ("gantt_yellow_grad", "Внеплановая работа без перерыва сервиса"),
        "1#UNPLAN" => array ("gantt_yellow", "Внеплановая работа с перерывом сервиса"),
        "0#ZEMERGENCY" => array ("gantt_red_grad", "Аварийная работа без перерыва сервиса"),
        "1#ZEMERGENCY" => array ("gantt_red", "Аварийная работа с перерывом сервиса"),
        "CLOSED" => array ("gantt_gray", "Работа завершена"),
        "CONCURRENCE" => array ("gantt_right_border", "Границы пересечения технологических работ"),
    );

// these KE are containers
$AIS_ke = "('3163','2103','2942','3357','1973','1157','3358','1165')";

// array of days of week
$week_days = array ('пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс', '&nbsp;&nbsp;&nbsp;&nbsp;');

// array of months
$months = array ('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

// -------------------------------------------------------------------------------------------------------------------

// current timestamp
$timestamp_current = time();

// get script parameters
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
if (empty($mode) or (strcmp($mode, OPER) != 0 and strcmp($mode, HIST) != 0)) {
    exit("<ul>
        <li><h3><a href='blackout.php?mode=oper'>Оперативный график</a></h3></li>&emsp;&emsp;&emsp;
        выводится информация о технологических работах за 12 ч до настоящего времени и 12 ч после<br><br><br>
        <li><h3><a href='blackout.php?mode=hist'>Исторические графики</a></h3>&emsp;&emsp;&emsp;
        выводится информация о технологических работах за выбранную в календаре дату</li></ul>");
}
$cur_reg = isset($_GET['reg']) ? $_GET['reg'] : '000';
$offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
$cur_reg = isset($_POST['formId2']['reg']) ? $_POST['filter_region'] : $cur_reg;
$filter_env = isset($_POST['filters']) ? $_POST['env'] : '';
$selected_date = isset($_GET['date_range']) ? $_GET['date_range'] : date('Y-m-d', $timestamp_current);
$today = ($selected_date == date('Y-m-d', $timestamp_current));

// current time
$date_time_array = getdate($timestamp_current);
$hours_current = $date_time_array['hours'];

// visible time range
if ($mode == HIST) {
    $year_selected = substr($selected_date, 0, 4);
    $month_selected = substr($selected_date, 5, 2);
    $day_selected = substr($selected_date, 8, 2);
    $timestamp_vis_start = mktime(0, 0, 0, $month_selected, $day_selected, $year_selected);
    $timestamp_vis_end = mktime(0, 0, 0, $month_selected, $day_selected + 1, $year_selected);
}
else {
    $timestamp_vis_start = $timestamp_current - SHIFT * 3600;
    $date_time_array = getdate($timestamp_vis_start);
    $minutes = $date_time_array['minutes'];
    $timestamp_vis_start = mktime($date_time_array['hours'], 0, 0, $date_time_array['mon'], $date_time_array['mday'], $date_time_array['year']);

    $timestamp_vis_end = $timestamp_current + SHIFT * 3600;
    $date_time_array = getdate($timestamp_vis_end);
    $minutes = $date_time_array['minutes'];
    $timestamp_vis_end = mktime($date_time_array['hours'], 0, 0, $date_time_array['mon'], $date_time_array['mday'], $date_time_array['year']);
}

// top header
echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"10\" bgcolor=\"#E5E0E0\">";
    echo "<tr>";
        echo "<td width=\"20%\" align=\"left\"></td>";
        echo "<td align=\"center\">";
            echo "<h3>";
            echo "Запланированные работы по обслуживанию КЭ<br><br>";
            echo  "отображаемый период с ".date('d.m.Y H:i', $timestamp_vis_start)." по ".date('d.m.Y H:i', $timestamp_vis_end);
            if ($mode == OPER or $today) {
                ?><table cellspacing=10>
                <tr>
                    <td>
                        автообновление <?php echo $auto_refresh ? "<u>включено</u>, интервал - ".floor($delay/60)." мин." : "выключено"; ?>
                    </td>
                    <td>
                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>?mode=<?php echo $mode;?>&reg=<?php echo $cur_reg;?>&auto_refresh=<?php echo $auto_refresh ? $ref_no : $ref_yes;?>&date_range=<?php echo $selected_date;?>&offset=<?php echo $offset;?>" method="post" id="formId">
                            <input type="submit" class="btn_blue" name="formId[ref]" value="<?php echo $auto_refresh ? "выключить" : "включить"; ?>" />
                        </form>
                    </td>
                </tr>
                </table><?php
            }
            echo "</h3>";
            // region selection
            ?><form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?>?mode=<?php echo $mode;?>&reg=<?php echo $cur_reg;?>&auto_refresh=<?php echo $auto_refresh ? $ref_yes : $ref_no;?>&date_range=<?php echo $selected_date;?>&offset=<?php echo $offset;?>" method="post" id="formId2">
                <select name="filter_region" size="1">
                    <?php
                    foreach ($array_regions as $reg_code => $reg_name) {
                        ?><option value="<?php echo $reg_code; ?>" <?php echo $reg_code == $cur_reg ? 'selected' : ''; ?>> <?php echo $reg_name; ?> </option><?php
                    }
                    ?>
                </select>
                <button type="submit" name="formId2[reg]" value="ОК" title="Выбрать регион"><img src="images/filter.png"></button>
            </form><?php
        echo "</td>";
        echo "<td width=\"25%\" align=\"right\"></td>";
    echo "</tr>";
echo "</table><br><br>";

// record(s) selection from MAXIMO tables
$sel = "SELECT 
           CI.CINAME, 
           CI.ASSETLOCSITEID, 
           CI.DESCRIPTION AS CI_DESC, 
           (case when CI.CLASSSTRUCTUREID not in $AIS_ke then C.SUBSYSTEM else
                case when C.SELECT1 = 1 then '".AIS_1."' else '".AIS_2."' end end) AS TARGETNAME, 
           C.BLACKOUTNUM,
           C.NOTAVAILABLE, 
           C.STARTTIMECI, 
           C.ENDTIMECI, 
           C.FACTSTARTTIMECI, 
           C.FACTENDTIMECI, 
           B.DESCRIPTION AS BL_DESC, 
           B.WORKTYPE AS BL_TYPE, 
           B.STATUS,
           B.PMCHGBLACKOUTID,
           B.STARTTIME,
           B.ENDTIME,
           S.DESCRIPTION AS ENVIRONMENT
        FROM MAXIMO.PMCHGBLACKOUT AS B 
        LEFT JOIN MAXIMO.PMCHGBOCI AS C 
        ON B.BLACKOUTNUM = C.BLACKOUTNUM AND C.ISTEMPLATE = 0
        LEFT JOIN MAXIMO.CI AS CI
        ON C.CINUM = CI.CINUM AND C.STARTTIMECI IS NOT NULL 
        LEFT JOIN MAXIMO.SYNONYMDOMAIN AS S
        ON S.MAXVALUE = CI.ENVIRONID AND S.DOMAINID = 'CIENVIRONSTAND' AND S.DEFAULTS = '1'
        WHERE (B.STATUS = 'ACTIVE' OR B.STATUS = 'INPROG' OR B.STATUS = 'EXPIRED' OR B.STATUS = 'REG') AND
              (C.FACTSTARTTIMECI IS NOT NULL or B.STATUS = 'ACTIVE' OR B.STATUS = 'REG' OR B.STATUS = 'INPROG')
        ORDER BY CI.ASSETLOCSITEID, CI.CINAME ASC";
$stmt_SCCD = db2_prepare($connection_SCCD, $sel);
$result = db2_execute($stmt_SCCD);

while ($row = db2_fetch_assoc($stmt_SCCD)) {
    // region filter
    if ($cur_reg != '000') {
        if ($row['ASSETLOCSITEID'] != $cur_reg)
            continue;
    }

    // work without KE or no
    if (empty($row['CINAME'])) {
        $start_time = $row['STARTTIME'];
        $end_time = $row['ENDTIME'];
    }
    else {
        $start_time = ($row['STATUS'] == 'INPROG' or $row['STATUS'] == 'EXPIRED') ? $row['FACTSTARTTIMECI'] : $row['STARTTIMECI'];
        $end_time = ($row['STATUS'] == 'EXPIRED' ? $row['FACTENDTIMECI'] : (empty($row['ENDTIMECI']) ? 0 : $row['ENDTIMECI']));
    }

    // table fill
    if ((substr($start_time, 0, 10) < $selected_date and (substr($end_time, 0, 19) > $selected_date." 00:00:00" or $end_time == 0))
        or substr($start_time, 0, 10) == $selected_date) {
        $table_cells[] = array(
            "id" => $row['PMCHGBLACKOUTID'],
            "targetname" => $row['TARGETNAME'],
            "ciname" => $row['CINAME'],
            "region" => $row['ASSETLOCSITEID'],
            "ci_desc" => $row['CI_DESC'],
            "wonum" => $row['BLACKOUTNUM'],
            "avail" => $row['NOTAVAILABLE'],
            "environment" => $row['ENVIRONMENT'],
            "start" => utime($start_time),
            "end" => $end_time == 0 ? 0 : utime($end_time),
            "start_vis" => (utime($start_time) < $timestamp_vis_start ? $timestamp_vis_start : utime($start_time)),
            "end_vis" => ($end_time == 0 or utime($end_time) > $timestamp_vis_end) ? $timestamp_vis_end : utime($end_time),
            "down_time" => "",
            "bl_desc" => $row['BL_DESC'],
            "bl_type" => $row['BL_TYPE'],
            "status" => $row['STATUS']
        );
    }

    // dates array for calendar
    $date_time_array = getdate(utime($start_time));
    for ($i = mktime(0, 0, 0, $date_time_array['mon'], $date_time_array['mday'], $date_time_array['year']);
         $i < ($end_time == 0 ? time() : utime($end_time)); $i += 86400) {
        $col_date_pre[] = date('Y-m-d', $i);
    }
}

// array sort by targetname (AIS_1 -> AIS_2 -> other -> empty)
usort ($table_cells, function ($x, $y) {
    if ($x['targetname'] == $y['targetname'])
        return strcmp($x['ciname'], $y['ciname']);
    else if ($x['targetname'] == AIS_1)
        return -1;
    else if ($y['targetname'] == AIS_1)
        return 1;
    else if ($x['targetname'] == AIS_2)
        return -1;
    else if ($y['targetname'] == AIS_2)
        return 1;
    else if ($x['targetname'] == '')
        return 1;
    else if ($y['targetname'] == '')
        return -1;
    else
        return strcmp($x['targetname'], $y['targetname']);
});

// base KE array
foreach ($table_cells as $ke) {
    if ($ke['targetname'] == AIS_1) {
        $sel = "select distinct c2.CINAME, c2.BLACKOUTNUM
                from MAXIMO.PMCHGBOCI c1
                inner join MAXIMO.PMCHGBOCI c2 
                on c2.SELECT1 = 0 and c2.PARENTCI is not null and c2.PARENTCI = c1.CINAME and c2.BLACKOUTNUM = c1.BLACKOUTNUM
                where c1.SELECT1 = 1 and c1.CINAME = '{$ke['ciname']}'";
        $stmt_SCCD = db2_prepare($connection_SCCD, $sel);
        $result = db2_execute($stmt_SCCD);

        while ($row = db2_fetch_assoc($stmt_SCCD)) {
            if (in_array($row['CINAME'], array_column($table_cells, 'ciname'))) {
                $base_ke_arr[$row['BLACKOUTNUM']][$ke['ciname']][] = $row['CINAME'];
            }
        }
    }
}

// dates array unique and filter
$col_date = array_filter(array_unique($col_date_pre));

// time intersection of identical cinames
$concurrence_count = array_count_values(array_filter(array_column($table_cells, 'ciname')));
foreach ($concurrence_count as $ciname => $count) {
    // treatment of cinames with more than 1 work
    if ($count > 1) {
        unset($concurrence_data);
        foreach ($table_cells as $key => $row)
            if ($row["ciname"] == $ciname)
                $concurrence_data[] = array("start" => $row["start"], "end" => $row["end"], "inter" => false);
        $concurrence_times = search_concurrences('intersection', $concurrence_data);

        foreach($concurrence_times as $value)
            // there is intersection
            if ($value["inter"])
                $table_intersection[] = array(
                    "ciname" => $ciname,
                    "start" => $value["start"],
                    "end" => $value["end"],
                );
    }
}

// -------------------------------------------------------------------------------------------------------------------

// calendar
if ($mode == HIST) {
    // calendar start point
    $cal_start = mktime(0, 0, 0, $month_selected + $offset, $day_selected, $year_selected);
    $date_time_array = getdate($cal_start);
    $cal_month = $date_time_array['mon'];
    $cal_year = $date_time_array['year'];

    echo "<table align=\"center\" cellpadding=\"10\" class=\"gantt\">";
    echo "<tr>";
    // previous month
    echo "<td valign=\"top\">";
    $href = "{$_SERVER['PHP_SELF']}?mode={$mode}&reg={$cur_reg}&auto_refresh=" . ($auto_refresh ? $ref_yes : $ref_no) . "&date_range={$selected_date}&offset=" . ($offset - 1);
    echo "<a href=\"$href\" title=\"Раньше\"><font size=\"+1\">&#9668</font></a>";
    echo "</td>";
    echo "<td align=\"center\">";
    echo "<table border=\"0\" cellspacing=\"0\" cellpadding=\"5\">";
    // months
    echo "<tr>";
    for ($i = 2; $i >= 0; $i--) {
        echo "<th  align=\"center\" colspan=7>";
        $j = $cal_month - 1 - $i;
        echo $months[$j < 0 ? 12 + $j : $j] . ' ' . ($j < 0 ? $cal_year - 1 : $cal_year);
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
            $k_month = ($cal_month - $k) <= 0 ? (12 + $cal_month - $k) : ($cal_month - $k);
            $k_year = ($cal_month - $k) <= 0 ? ($cal_year - 1) : $cal_year;
            $k_1st = getdate(mktime(0, 0, 0, $k_month, 1, $k_year));
            $ddd = $k_1st['wday'] == 0 ? 6 : ($k_1st['wday'] - 1); // 0 - пн., 1 - вт., ... 6 -вскр.
            for ($j = 0; $j < 7; $j++) {
                $cell = mktime(0, 0, 0, $k_month, $i * 7 + $j + 1 - $ddd, $k_year);
                $c = getdate($cell);
                $d = date('j', $cell);
                $dd = date('Y-m-d', $cell);
                echo "<td" . (($dd == $selected_date and $k_month == $c['mon']) ? "  class=\"calendar_fill\"" : "") . ">";
                $href = $_SERVER['PHP_SELF'] . "?mode={$mode}&reg=" . $cur_reg . "&auto_refresh=" . ($auto_refresh ? $ref_yes : $ref_no) . "&date_range=" . $dd . "&offset=1";
                echo "<a href=\"$href\" title=\"" . (in_array($dd, $col_date) ? "Имеются запланированные работы" : "") . "\">";
                echo $dd == date('Y-m-d', time()) ? "<i><font color=\"red\">" : "";
                echo in_array($dd, $col_date) ? "<b>" : "";
                if ($k_month == $c['mon'])
                    echo $d;
                echo in_array($dd, $col_date) ? "</b>" : "";
                echo $dd == date('Y-m-d', time()) ? "</font></i>" : "";
                echo "</a>";
                echo "</td>";
            }
            echo "<td>";
            echo "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</td>";
    echo "<td valign=\"top\">";
    $href = $_SERVER['PHP_SELF'] . "?mode={$mode}&reg=" . $cur_reg . "&auto_refresh=" . ($auto_refresh ? $ref_yes : $ref_no) . "&date_range=" . $selected_date . "&offset=" . ($offset + 1);
    echo "<a href=\"$href\" title=\"Позже\"><font size=\"+1\">&#9658</font></a>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "</td>";
    echo "<td colspan=0>";
    echo "Жирным шрифтом в календаре выделены даты с запланированными работами.<br>";
    $href = $_SERVER['PHP_SELF'] . "?mode={$mode}&reg=" . $cur_reg . "&auto_refresh=" . ($auto_refresh ? $ref_yes : $ref_no) . "&date_range=" . date('Y-m-d', time()) . "&offset=1";
    echo "Выбранная для отчёта дата отмечена заливкой. <a href=\"$href\" title=\"На сегодня...\">Перейти к сегодняшней дате</a>.<br>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "<br \>";
}

// -------------------------------------------------------------------------------------------------------------------

// PTK group
$ptk_count = 0;
$ke_count = 0;
$ptk_arr = array_unique(array_column($table_cells, 'targetname'));
asort($ptk_arr);
// add some elements to begin or end of PTK's array
if (($k = array_search(AIS_2, $ptk_arr)) !== false ) {
    unset($ptk_arr[$k]);
    array_unshift($ptk_arr, AIS_2);
}
if (($k = array_search(AIS_1, $ptk_arr)) !== false ) {
    unset($ptk_arr[$k]);
    array_unshift($ptk_arr, AIS_1);
}
if (($k = array_search('Н/Д', $ptk_arr)) !== false ) {
    unset($ptk_arr[$k]);
    $ptk_arr[] = 'Н/Д';
}
if (($k = array_search('', $ptk_arr)) !== false ) {
    unset($ptk_arr[$k]);
    $ptk_arr[] = 'КЭ без определённой зависимости';
}

$block_title_not_typed = true;
foreach ($ptk_arr as $k => $PTK) {
    // block title
    if ($PTK == AIS_1 or $PTK == AIS_2)
        echo "<h2 align='center'>{$PTK}</h2>";
    else if ($block_title_not_typed) {
        echo "<h2 align='center'>Конфигурационные элементы</h2>";
        $block_title_not_typed = false;
    }

    // count KE in PTK to show or hide the block
    $i = 0;
    foreach ($table_cells as $row)
        if ($row['targetname'] === $PTK and (empty($filter_env) or $filter_env == $row['environment']))
            $i++;
    if ($i == 0)
        continue;

    $ptk_count++;
    if ($PTK != AIS_1 and $PTK != AIS_2)
        $ke_count++;

    // PTK name output
    echo "<h4 class='blackout_PTK_toggle' id='{$k}' title='Нажмите, чтобы показать/скрыть блок этого КЭ'>&#10150;&nbsp;".
        (($PTK == AIS_1 or $PTK == AIS_2) ? substr(explode('(', $PTK)[1], 0, -1)  : $PTK)." (показать/скрыть)</h4>";
    echo "<table class='blackout_PTK_{$k}' width='100%'><tr><td>";

    // Gantt chart
    echo "<table cellpadding=\"10\" width='80%'>";
        echo "<tr>";
            // chart
            echo "<td valign=\"top\" width='80%'>";
                echo "<table class=\"gantt\" cellspacing=\"0\" cellpadding=\"2\" width='100%'>";
                    echo "<tr>";
                        echo "<th colspan = ".($PTK ==AIS_1 ? '100' : '99').">Диаграмма работ по обслуживанию</th>";
                    echo "</tr>";
                    echo "<tr>";
                        echo "<th colspan = ".($PTK ==AIS_1 ? '3' : '2')."></th>";
                            for ($i = (($mode == OPER) ? ($hours_current - SHIFT) : 0); $i < (($mode == OPER) ? ($hours_current + SHIFT) : 24); $i++) {
                                echo "<td colspan = 4 class=\"gantt gantt_font\">";
                                $pp = str_pad($i < 0 ? $i + 24 : ($i > 23 ? $i - 24 : $i), 2, "*", STR_PAD_LEFT);
                                echo "&ensp;".str_replace("*", "&ensp;", $pp)."&ensp;";
                                echo "</td>";
                            }
                        echo "<th></th>";
                    echo "</tr>";

                    foreach ($table_cells as $row) {
                        // first empty string
                        if (empty($row['ciname']))
                            continue;

                        // target PTK
                        if ($row['targetname'] === $PTK and (empty($filter_env) or $filter_env == $row['environment'])) {
                            // non-base KE
                            $base_ke = '';
                            if ($PTK == AIS_2) {
                                foreach ($base_ke_arr[$row['wonum']] as $key => $val)
                                    if (in_array($row['ciname'], $val)) {
                                        $base_ke = 'Основной КЭ: ' . $key;
                                        break;
                                    }
                            }

                            graph_output($row, true, $PTK == AIS_1, $row['id'], $base_ke);
                            // non-base KEs for the base KE
                            if ($PTK == AIS_1) {
                                foreach ($table_cells as $row_2)
                                    if (empty($filter_env) or $filter_env == $row_2['environment'])
                                        if ($row_2['targetname'] == AIS_2 and key_exists($row['ciname'], $base_ke_arr[$row['wonum']]) and
                                            in_array($row_2['ciname'], $base_ke_arr[$row['wonum']][$row['ciname']]) and $row['wonum'] == $row_2['wonum'])
                                            graph_output($row_2, false, false, $row['id'], $base_ke);
                            }
                        }
                    }
                echo "</table>";
            echo "</td>";
            // legend
            echo "<td valign=\"top\" align='right' width='20%'>";
                echo "<table>";
                    echo "<tr>";
                        echo "<td align=\"center\" colspan = 2 class=\"gantt_font\">";
                            echo "<u>Условные обозначения</u>:";
                        echo "</td>";
                    echo "</tr>";
                    foreach ($gantt_chart_colors as $value) {
                        echo "<tr>";
                            echo "<td class=\"".$value[0]."\">";
                                echo "&nbsp;&nbsp;";
                            echo "</td>";
                            echo "<td class=\"gantt_font\">";
                                echo "&nbsp;&nbsp;".$value[1];
                            echo "</td>";
                        echo "</tr>";
                    }
                echo "</table>";
            echo "</td>";
        echo "</tr>";
    echo "</table>";

    // KE table titles
    echo "<br><table class='sticky' border='1' cellspacing='0' cellpadding='5' width='80%'>";
    echo "<thead><tr>";
    foreach ($title_cells as $title_row) {
        if (($title_row != 'Код ОПФР' or $cur_reg == '000') and ($title_row != 'Среда' or $PTK ==AIS_1 or $PTK ==AIS_2) and ($title_row != '' or $PTK ==AIS_1)) {
            echo "<th>".$title_row."</th>";
        }
    }
    echo "</tr>";

    // AISs table filters
    if ($PTK == AIS_1 or $PTK ==AIS_2) {
        echo "<tr>";
        ?> <form action="<?php echo $_SERVER['PHP_SELF'];?>?mode=<?php echo $mode;?>&reg=<?php echo $cur_reg;?>&auto_refresh=<?php echo $auto_refresh ? $ref_yes : $ref_no;?>&date_range=<?php echo $selected_date;?>&offset=<?php echo $offset;?>" method="post" id="filterId"> <?php
            $colspan = ($PTK == AIS_1 ? ($cur_reg == '000' ? 4 : 3) : ($cur_reg == '000' ? 3 : 2));
            echo "<td class=\"col_filter\" colspan=\"{$colspan}\"></td>";
            echo "<td class=\"col_filter\">";
            $env_array = array_filter(array_unique(array_column(array_filter($table_cells, function($rec) { return ($rec['targetname'] == AIS_1 or $rec['targetname'] == AIS_2); }), 'environment')));
            asort($env_array);
            ?> <select name="env" size="1">
                <option value="">(нет фильтра)</option> <?php
                foreach ($env_array as $env) {
                    ?> <option value="<?php echo $env; ?>" <?php echo $env == $filter_env ? 'selected' : ''; ?>> <?php echo $env; ?> </option> <?php
                    ;
                }
                ?> </select> <?php
            echo "</td>";
            echo "<td class=\"col_filter\" colspan=0>";
            ?> <button name="filters"><img src="images/filter.png" hspace="10" align="top">применить выбранный фильтр</button> <?php
            echo "</td>";
            ?> </form> <?php
        echo "</tr>";
    }

    echo "</thead><tbody>";
    // KE output
    foreach ($table_cells as $row) {
        if ((empty($filter_env) or $filter_env == $row['environment'])) {
            if ($row['targetname'] === $PTK) {
                table_output($row, true, $PTK == AIS_1, $row['id']);
                // non-base KEs for the base KE
                if ($PTK == AIS_1) {
                    foreach ($table_cells as $row_2)
                        if (empty($filter_env) or $filter_env == $row_2['environment'])
                            if ($row_2['targetname'] == AIS_2 and key_exists($row['ciname'], $base_ke_arr[$row['wonum']]) and
                                in_array($row_2['ciname'], $base_ke_arr[$row['wonum']][$row['ciname']]) and $row['wonum'] == $row_2['wonum'])
                                table_output($row_2, false, false, $row['id']);
                }
            }
        }
    }
    echo "</tbody></table>";
    echo "<h4 class='blackout_PTK_toggle' id='{$k}' title='Нажмите, чтобы показать/скрыть блок этого КЭ'>&#10149;&nbsp;".
        (($PTK == AIS_1 or $PTK == AIS_2) ? substr(explode('(', $PTK)[1], 0, -1) : $PTK)." (показать/скрыть)</h4>";
    echo "<br><br><br>";

    echo "</td></tr></table>";
}

if (empty($ptk_count) or empty($ke_count))
    echo "Данные не найдены.";
echo "<br \>";
echo "<br \>";

// database connections close
db2_close($connection_SCCD);

/******************************************************************************************************/

function table_output($el, $show, $expand, $pmchgbociid) {
    $id = $start = '';
    $work_type = $GLOBALS['work_type'];
    $work_status = $GLOBALS['work_status'];

    echo "<tr ".(($expand or $show) ? "" : "class='nonbase_{$pmchgbociid}'").($show ? "" : " hidden").">";
        if ($expand)
            echo "<td class='expand pointer' id='{$pmchgbociid}'><img class='i{$pmchgbociid}' src='images/details_open.png' title='Развернуть' /></td>";
        else if (!$show)
            echo "<td></td>";

        foreach ($el as $key => $cell) {
            switch ($key) {
                case "id":
                    $id = $cell;
                case "wonum":
                case "targetname":
                case "subsys_exemplar":
                case "avail":
                    break;
                case "ciname":
                    if (empty($cell))
                        echo "<td>Для ТР{$id} нет затронутых КЭ</td>";
                    else
                        echo "<td><a href=\"http://10.103.0.106/maximo/ui/login?event=loadapp&value=CI&additionalevent=useqbe&additionaleventvalue=CINAME=" . $cell . "\" target=\"blank\" title=\"Перейти к КЭ в ТОРС\">" . $cell . "</a></td>";
                    break;
                case "region":
                    if ($GLOBALS['cur_reg'] == '000')
                        echo "<td>" . $cell . "</td>";
                    break;
                case "environment":
                    if ($GLOBALS['PTK'] == AIS_1 or $GLOBALS['PTK'] == AIS_2)
                        echo "<td>" . $cell . "</td>";
                    break;
                case "start":
                    $start = $cell;
                case "end":
                    $end = $cell;
                    echo "<td>" . (empty($cell) ? 'не запланировано' : date('d.m.Y H:i', $cell)) . "</td>";
                    break;
                case "start_vis":
                case "end_vis":
                    break;
                case "down_time":
                    echo "<td>";
                    if (!empty($end)) {
                        $sec = $end - $start;
                        $h = floor($sec / 3600);
                        $m = floor($sec / 60) - $h * 60;
                        echo ($h > 0 ? $h . " ч " : "") . (strlen($m) < 2 ? "0" : "") . $m . " мин";
                    } else
                        echo "---";
                    echo "</td>";
                    break;
                case "bl_type":
                    echo "<td>" . $work_type[$cell] . "</td>";
                    break;
                case "status":
                    echo "<td>" . $work_status[$cell] . "</td>";
                    break;
                default:
                    echo "<td>" . $cell . "</td>";
                    break;
            }
        }
    echo "</tr>";
}

/******************************************************************************************************/

function graph_output($el, $show, $expand, $pmchgbociid, $base_ke)
{
    echo "<tr ".(($expand or $show) ? "" : "class='nonbase_{$pmchgbociid}'").($show ? "" : " hidden").">";
        $date_time_array = getdate($el['start_vis']);
        $start_hour = $date_time_array['hours'];
        $min_today = $date_time_array['minutes'];
        $date_time_array = getdate($el['end_vis']);
        $end_hour = $date_time_array['hours'];
        $end_min = $date_time_array['minutes'];
        if ($end_hour == 0 and $end_min == 0) {            // midnight
            $end_hour = 24;
            $end_min = 0;
        }
    
        // base KE
        if ($expand)
            echo "<td class='gantt gantt_font expand pointer' id='{$pmchgbociid}'><img class='i{$pmchgbociid}' src='images/details_open.png' title='Развернуть' /></td>";
        else if (!$show)
            echo "<td></td>";

        echo "<td class=\"gantt gantt_font\">&ensp;&ensp;" .
            (!empty($base_ke) ? "<span class='pointer' title='{$base_ke}'>" : "") . $el['ciname'] . (!empty($base_ke) ? "</span>" : "") . "&ensp;&ensp;</td>";
        echo "<th class=\"gantt_font\">";
        echo($el['start'] < $el['start_vis'] ? "<a title=\"начало работ " . date('d.m.Y H:i', $el['start']) . "\">&#9668;</a>" : "");
        echo "</th>";
        for ($c_start = $GLOBALS['timestamp_vis_start']; $c_start < $GLOBALS['timestamp_vis_end']; $c_start += 15 * 60) {
            $c_end = $c_start + 15 * 60;
    
            // additional class for cell filling
            $to_fill = (($el['start'] < $c_start and ($el['end'] > $c_start or $el['end'] == 0)) or
                ($el['start'] >= $c_start and $el['start'] < $c_end));
    
            // additional class for cell borders
            $left_border = false;
            $right_border = false;
            foreach ($GLOBALS['table_intersection'] as $value) {
                if ($value['ciname'] == $el['ciname']) {
                    $left_border = ($to_fill and $value['start'] - $c_start >= 0 and $value['start'] - $c_start < 15 * 60);
                    $right_border = ($to_fill and $c_end - $value['end'] >= 0 and $c_end - $value['end'] < 15 * 60);
                }
            }
    
            // hyperlink to SCCD
            $link = "<a href='http://{$GLOBALS['hostname_SCCD']}/maximo/ui/login?event=loadapp&value=pmchgbo&additionalevent=useqbe&additionaleventvalue=PMCHGBLACKOUTID={$el['id']}&forcereload=true' title='Перейти к карточке работы в ТОРС...' target='_blank'>&nbsp;</a>";
    
            // graph table cell draw
            echo "<td class='gantt " .
                ($left_border ? "gantt_left_border " : "") .
                ($right_border ? "gantt_right_border " : "") .
                ($to_fill ? "{$GLOBALS['gantt_chart_colors'][($el['status'] == 'EXPIRED' ? 'CLOSED' : $el['avail']."#".$el['bl_type'])][0]}'>{$link}" : "'>&nbsp;") .
                "</td>";
        }
        echo "<th class=\"gantt_font\">";
        if ($el['end'] == 0)
            echo "<a title=\"окончание работ не запланировано\">&#9658;</a>";
        else if ($el['end'] > $el['end_vis'])
            echo "<a title=\"окончание работ " . date('d.m.Y H:i', $el['end']) . "\">&#9658;</a>";
        echo "</th>";
    echo "</tr>";
}
?>
</body>
</html>