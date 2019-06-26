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
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Конструктор ситуаций мониторинга</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/sits_constructor.js"></script>
</head>
<body>
<!--<script>document.write('<script src="http://' + (location.host || 'localhost').split(':')[0] + ':35729/livereload.js?snipver=1"></' + 'script>')</script>-->
<?php
require_once 'connections/TBSM.php';
include 'functions/user_roles.php';

// maximum value of the counter
const MAX_COUNT = 20;

// situations description templates
$items = array (
    "@ITMDISPLAYITEM" => "@ITMDISPLAYITEM",
    "@NODEALIAS" => "@NODEALIAS",
    "@PFR_KE_TORS" => "@PFR_KE_TORS",
    "@PFR_MESSAGE_TEXT" => "@PFR_MESSAGE_TEXT",
    "@PFR_NAZN" => "@PFR_NAZN",
    "@SUMMARY" => "@SUMMARY",
    "текстовая строка" => "",
);

$sit_name_base = '';
$sit_name_new = '';
$mode ='view';
$output = '';
$error = false;

// situation's decription
$sit_arr = [];

// there is template in a string
$templ = null;

/******************************************************************************************************************/

// situation delete from DB
if (isset($_POST["del_btn"])) {
    $mode = 'delete';

    $sel = "delete from DB2INST1.PFR_SITS_CONSTRUCTOR where PFR_SIT_NAME = '{$_POST["sits_list"]}'";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
}

// situation save to DB
if (isset($_POST["save_btn"])) {
    for ($critical = 5; $critical >= 0; $critical-=5)
        for ($i = 1; $i <= MAX_COUNT; $i++) {
            // is there the string in the table?
            $sel = "select count(*) as N from DB2INST1.PFR_SITS_CONSTRUCTOR where PFR_SIT_NAME = '{$_GET['Sit_name']}' and SEVERITY = {$critical} and POSITION = {$i}";
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
            $row = db2_fetch_assoc($stmt);

            // filled value - update or insert to table
            if (!empty($_POST["templ_{$i}_{$critical}"])) {
                $str = ($_POST["templ_{$i}_{$critical}"] == 'текстовая строка' ? $_POST["str_{$i}_{$critical}"] : $_POST["templ_{$i}_{$critical}"]);
                if ($_GET["mode"] == 'edit' and $row['N'] > 0)
                    $sel = "update DB2INST1.PFR_SITS_CONSTRUCTOR set STRING = '{$str}' where PFR_SIT_NAME = '{$_GET['Sit_name']}' and SEVERITY = {$critical} and POSITION = {$i}";
                else
                    $sel = "insert into DB2INST1.PFR_SITS_CONSTRUCTOR (PFR_SIT_NAME, STRING, SEVERITY, POSITION) values ('{$_GET['Sit_name']}', '{$str}', {$critical}, {$i})";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
            }
            // empty value - delete from table (if present)
            else if ($row['N'] > 0) {
                $sel = "delete from DB2INST1.PFR_SITS_CONSTRUCTOR where PFR_SIT_NAME = '{$_GET['Sit_name']}' and SEVERITY = {$critical} and POSITION = {$i}";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
            }
            if (!$error and !$result)
                $error = true;
        }
    if ($error)
        $output = "При сохранении ситуации \"{$_GET['Sit_name']}\" произошла ошибка!";
    else
        $output = "Ситуация \"{$_GET['Sit_name']}\" успешно сохранена.";
}

// top header
$title = "Конструктор ситуаций мониторинга";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
require 'functions/header_2.php';

// get script parameters
$sit_name_base = isset($_POST["sits_list"]) ? $_POST["sits_list"] : (isset($_GET["Sit_base"]) ? $_GET["Sit_base"] : '');
$sit_name_new = isset($_POST["sit_inp"]) ? $_POST["sit_inp"] : (isset($_GET["Sit_new"]) ? $_GET["Sit_new"] : '');

echo "<form action='{$_SERVER['PHP_SELF']}?Sit_base={$sit_name_base}&Sit_new={$sit_name_new}' method='post' id='formSelect'>";
echo "<br><br><table class='gantt' cellpadding='10' align='center'>";
    echo "<tr class='first'>";
        echo "<td>Список имеющихся ситуаций:</td>";
        echo "<td><input name='sit_search' type='text' size='20' maxlength='32' autofocus placeholder='поиск по подстроке'></td>";
    echo "</tr>";
    echo "<tr class='first'>";
        echo "<td valign='top'>";
            echo "<select size = '13' name = 'sits_list'>";
                $sel = "SELECT DISTINCT PFR_SIT_NAME FROM DB2INST1.PFR_SITS_CONSTRUCTOR ORDER BY PFR_SIT_NAME ASC";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                while ($row = db2_fetch_assoc($stmt))
                    echo "<option value ='{$row['PFR_SIT_NAME']}' " . ($row['PFR_SIT_NAME'] == $sit_name_base ? 'selected' : '') . ">{$row['PFR_SIT_NAME']}</option>";
            echo "</select>";
        echo "</td>";
        echo "<td valign='center'>";
            echo "<button type='button' class='btn_blue' name='new_btn' value='Создать новую' title='Создать новую ситуацию с нуля' ".($acs_form ? '' : 'disabled')."><img src='images/new.png'>&emsp;Создать новую </button><br><br>";
            echo "<button type='button' class='btn_blue' name='edit_btn' value='Редактировать' title='Редактировать выбранную ситуацию' ".($acs_form ? '' : 'disabled')."><img src='images/edit.png'>&emsp;Редактировать </button><br><br>";
            echo "<button type='button' class='btn_blue' name='clone_btn' value='Клонировать' title='Создать новую ситуацию на основе выбранной' ".($acs_form ? '' : 'disabled')."><img src='images/copy.png'>&emsp;Клонировать &emsp;</button><br><br>";
            echo "<button type='submit' class='btn_blue' name='del_btn' value='Удалить' title='Удалить ситуацию' ".($acs_form ? '' : 'disabled')."><img src='images/delete.png'>&emsp;Удалить &emsp;&emsp;&emsp;&nbsp;</button>";
        echo "</td>";
    echo "</tr>";
    echo "<tr class='next' hidden>";
        echo "<td colspan='0'>Имя для новой ситуации: <input name='sit_inp' type='text' size='40' maxlength='256'></td>";
    echo "</tr>";
    echo "<tr class='next' hidden>";
        echo "<td colspan='0' align='center'><button type='button' class='btn_blue' name='next_btn' value='Продолжить' title='Перейти к редактированию ситуации' ".($acs_form ? '' : 'disabled')."><img src='images/ok.png'> Продолжить... </button></td>";
    echo "</tr>";
echo "</table><br><br><hr><br>";
echo "</form>";

if ($mode != 'delete' and (!isset($_GET["mode"]) or $_GET["mode"] != 'view')) {
    if (empty($sit_name_base) and !empty($sit_name_new))
        $mode = 'new';
    else if (!empty($sit_name_base) and empty($sit_name_new))
        $mode = 'edit';
    else if (!empty($sit_name_base) and !empty($sit_name_new))
        $mode = 'clone';
}
if ($mode != 'delete' and $mode != 'view') {
    echo "<form action='{$_SERVER['PHP_SELF']}?mode={$mode}&Sit_name=".($mode == 'edit' ? $sit_name_base : $sit_name_new)."' method='post' id='formEdit'>";
    if ($mode == 'new')
        echo"<h3 align='center'>Добавление новой ситуации \"{$sit_name_new}\"";
    else {
        if ($mode == 'edit')
            echo "<h3 align='center'>Редактирование ситуации \"{$sit_name_base}\"";
        if ($mode == 'clone')
            echo "<h3 align='center'>Клонирование ситуации \"{$sit_name_base}\" в \"{$sit_name_new}\"";

        $sel = "SELECT * FROM DB2INST1.PFR_SITS_CONSTRUCTOR WHERE PFR_SIT_NAME = '{$sit_name_base}'";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
        while ($row = db2_fetch_assoc($stmt))
            $sit_arr[$row['POSITION']][$row['SEVERITY']] = $row['STRING'];
    }
    echo "&emsp;<button type='submit' class='btn_blue' name='save_btn' value='Сохранить' title='Сохранить ситуацию' ".($acs_form ? '' : 'disabled')." onclick=\"return confirm('Сохранить описание ситуации в БД?..')\"><img src='images/events.png' height='16' width='16'> Сохранить</button>";
    echo "&emsp;<button type='submit' class='btn_blue' name='cancel_btn' value='Отменить' title='Отменить и вернуться' ".($acs_form ? '' : 'disabled')." onclick=\"return confirm('Если были сделаны изменения, они будут утеряны!')\"><img src='images/details_close.png' height='16' width='16'> Отменить</button></h3>";

    echo "<table border='1' cellpadding='10' align='center'>";
        echo "<tr>";
            echo "<th rowspan='2'>№ поля</th>";
            echo "<th class='red'>Шаблон описания при срабатывании ситуации</th>";
            echo "<th class='green'>Шаблон описания при закрытии ситуации</th>";
        echo "</tr>";

        echo "<tr>";
            for ($critical = 5; $critical >= 0; $critical-=5) {
                echo "<td class='page_title_dark'>";
                    echo "<table cellpadding='0' cellspacing='0'>";
                        echo "<tr>";
                            for ($i = 1; $i <= MAX_COUNT; $i++) {
                                echo "<td nowrap class='test_{$i}_{$critical}'>";
                                    if ($mode == 'edit' or $mode == 'clone')
                                        echo isset($sit_arr[$i][$critical]) ? str_replace(' ', "&nbsp;",$sit_arr[$i][$critical]) : '';
                                echo "</td>";
                            }
                        echo "</tr>";
                    echo "</table>";
                echo "</td>";
            }
        echo "</tr>";

        for ($i = 1; $i <= MAX_COUNT; $i++) {
            echo "<tr>";
                echo "<td align='center'>{$i}</td>";
                for ($critical = 5; $critical >= 0; $critical-=5) {
                    echo "<td nowrap>";
                        unset($templ);
                        if (array_multi_key_exists(array($i, $critical), $sit_arr))
                            $templ = (strpos($sit_arr[$i][$critical], "@") === 0);
                        echo "<select size = '1' name = 'templ_{$i}_{$critical}'>";
                            echo "<option value ='' " . (($mode == 'new' or !array_multi_key_exists(array($i, $critical), $sit_arr)) ? 'selected' : '') . ">---</option>";
                            foreach ($items as $key => $item)
                                echo "<option value ='{$key}' " . ((isset($templ) and ($sit_arr[$i][$critical] == $item or (!$templ and empty($item)))) ? 'selected' : '') . ">{$key}</option>";
                        echo "</select>";
                        echo "&emsp; <input name='str_{$i}_{$critical}' type='text' class='str_{$i}_{$critical}' size='60' maxlength='256' value='" . ((isset($templ) and !$templ) ? $sit_arr[$i][$critical] : '') . "' " . ((!isset($templ) or $templ) ? 'hidden' : '') . ">";
                    echo "</td>";
                }
            echo "</tr>";
        }
    echo "</table>";
    echo "</form>";
}
else if ($mode == 'view') {
    $sel = "SELECT * FROM DB2INST1.PFR_SITS_CONSTRUCTOR ORDER BY PFR_SIT_NAME asc, SEVERITY desc, POSITION asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    while ($row = db2_fetch_assoc($stmt))
        $sit_arr[$row['PFR_SIT_NAME']][$row['SEVERITY']][$row['POSITION']] = $row['STRING'];

    echo "<table border='1' cellpadding='10' align='center'>";
        echo "<tr id='title'>";
            echo "<th class='red'>Шаблон описания при срабатывании ситуации</th>";
            echo "<th class='green'>Шаблон описания при закрытии ситуации</th>";
        echo "</tr>";

        foreach($sit_arr as $sit_name => $arr1) {
            echo "<tr class='rec_hide' id='".strtolower($sit_name)."'>";
                foreach($arr1 as $sev => $arr2) {
                    echo "<td class='page_title_dark'>";
                        echo "<table cellpadding='0' cellspacing='0'>";
                            echo "<tr>";
                                foreach($arr2 as $pos => $str) {
                                    echo "<td nowrap>";
                                        echo str_replace(' ', "&nbsp;", $str);
                                    echo "</td>";
                                }
                            echo "</tr>";
                        echo "</table>";
                    echo "</td>";
                }
            echo "</tr>";
        }
    echo "</table>";
}

// database connection close
db2_close($connection_TBSM);

/******************************************************************************************************************/

// функция проверки наличия элемента с указанными индексами в многомерном массиве
function array_multi_key_exists( $arrNeedles, $arrHaystack) {
    $Needle = array_shift($arrNeedles);
    if(count($arrNeedles) == 0)
        return(array_key_exists($Needle, $arrHaystack));
    else {
        if(!array_key_exists($Needle, $arrHaystack))
            return false;
        else
            return array_multi_key_exists($arrNeedles, $arrHaystack[$Needle]);
    }
}

?>
</body>
</html>
