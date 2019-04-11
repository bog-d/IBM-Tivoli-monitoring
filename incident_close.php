<?php
/*
	by GDV
	2019 RedSys
*/
header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
        <title>Проверка и закрытие инцидентов в СТП</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/incident_close.js"></script>
</head>
<body>
<?php
include 'functions/user_roles.php';

$ael_inc_import = [];
$reason_arr = array (
    '0' => 'Свой текст...',
    '1' => 'В связи с технологическими работами на КЭ',
);

// top header
$title = "Проверка и закрытие инцидентов в СТП";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
require 'functions/header_2.php';

if (isset($_POST['incident'])) {
    $ael_inc_import = array_filter(explode('%2C', $_POST['incident']));
    asort($ael_inc_import);
}

echo "<form id='inclose' method='post'>";
echo "<table border='0' cellspacing='40'>";
    echo "<tr>";
        echo "<td>";
            echo "<h4>Список инцидентов</h4>";
            echo "<textarea id='value' name='value' placeholder='Введите здесь номера инцидентов, разделённые пробелами, запятыми, точками с запятой или переносами строк...' cols='60' rows='10' autofocus>";
                foreach ($ael_inc_import as $el)
                    echo $el.PHP_EOL;
            echo "</textarea><br><br>";
        echo "</td>";
        echo "<td valign='bottom'>";
            echo "<input type='button' class='btn_admin' id='check' value='Проверить статус' title='Проверить статус всех инцидентов из списка' ".($acs_role == 'admin' ? '' : "disabled='disabled'").">&emsp;&emsp;";
        echo "</td>";
        echo "<td valign='top' rowspan='0'>";
            echo "<div id='output_area'></div>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td>";
            echo "<h4>Варианты причин закрытия</h4>";
            echo "<select id='variant' name='variant'>";
                foreach ($reason_arr as $key => $value)
                    echo "<option value='{$key}'>{$value}</option>";
            echo "</select><br><br>";
            echo "<div id='textarea'>";
                echo "<textarea id='text' name='text' placeholder='Введите здесь причину закрытия инцидентов...' cols='60' rows='10'></textarea><br><br><br>";
            echo "</div>";
        echo "</td>";
        echo "<td valign='top'>";
            echo "<img src='images/delete.png' title='Внимание! Нажатие этой кнопки может привести к неприятным последствиям!' hspace='5'>
                  <input type='button' class='btn_admin' id='close' value='Закрыть' title='Закрыть все инциденты из списка' ".($acs_role == 'admin' ? '' : "disabled='disabled'").">
                  <img src='images/delete.png' title='Внимание! Нажатие этой кнопки может привести к неприятным последствиям!' hspace='5'>";
        echo "</td>";
    echo "</tr>";
echo "</table>";
echo "</form>";
?>
</body>
</html>
