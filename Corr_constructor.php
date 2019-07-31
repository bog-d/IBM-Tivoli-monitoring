<?php
/*
	by GDV
	2019 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Конструктор корреляционных цепочек мониторинга</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/corr_constructor.js"></script>
</head>
<body>
<?php
require_once 'connections/TBSM.php';
include 'functions/user_roles.php';

$mode ='view';
$new_chain_name = '';
$chain_id = 0;
$output = '';
$error = false;

/******************************************************************************************************************/

// chain delete from DB
if (isset($_POST["del_btn"])) {
    $sel = "delete from DB2INST1.PFR_CORRELATION_DESCRIPTION where PFR_CORRELATION_CHAIN_ID = {$_POST['chain_id']}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    if (!$error and !$result)
        $error = true;

    $sel = "delete from DB2INST1.PFR_CORRELATIONS where PFR_CORRELATION_CHAIN_ID = {$_POST['chain_id']}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    if (!$error and !$result)
        $error = true;

    $output = $error ? "При удалении цепочки с PFR_CORRELATION_CHAIN_ID = {$_POST['chain_id']} произошла ошибка!" :
                        "Цепочка с PFR_CORRELATION_CHAIN_ID = {$_POST['chain_id']} успешно удалена.";
}

// chain save to DB
if (isset($_POST["save_btn"])) {
    ;
}

// new chain
if (isset($_POST["next_btn"])) {
    $new_chain_name = $_POST["chain_inp"];
    $mode = 'new';
}

// edit chain
if (isset($_POST["edit_btn"])) {
    $chain_id = $_POST["chain_id"];
    $mode = 'edit';
}

// top header
$title = "Конструктор корреляционных цепочек мониторинга";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
require 'functions/header_2.php';

// list of chains
$sel = "select d.PFR_CORRELATION_CHAIN_ID, d.PFR_CORRELATION_CHAIN_DESCRIPTION, c.PFR_KE_TORS, c.PFR_SIT_NAME
        from PFR_CORRELATION_DESCRIPTION d, PFR_CORRELATIONS c
        where d.PFR_CORRELATION_CHAIN_ID = c.PFR_CORRELATION_CHAIN_ID and c.PFR_CORRELATION_EVENT_TYPE = 'm'";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);

echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='formSelect'>";
    echo "<br><br><table class='gantt' cellpadding='10' align='center'>";
        echo "<tr class='first'>";
            echo "<td>Список корреляционных цепочек:</td>";
            echo "<td><input name='chain_search' type='text' size='20' maxlength='32' autofocus placeholder='поиск по подстроке' disabled></td>";
        echo "</tr>";
        echo "<tr class='first'>";
            echo "<td valign='top'>";
                echo "<table cellpadding='10' border='1'>";
                    echo "<tr>";
                        echo "<th>Имя</th>";
                        echo "<th>КЭ корневого события</th>";
                        echo "<th>Код корневого события</th>";
                    echo "</tr>";

                    while ($row = db2_fetch_assoc($stmt)) {
                        echo "<tr class='rec_ch pointer' chain_id_sel='{$row['PFR_CORRELATION_CHAIN_ID']}'>";
                            echo "<td class='chain_descr'>{$row['PFR_CORRELATION_CHAIN_DESCRIPTION']}</td>";
                            echo "<td>{$row['PFR_KE_TORS']}</td>";
                            echo "<td>{$row['PFR_SIT_NAME']}</td>";
                        echo "</tr>";
                    }
                echo "</table>";
            echo "</td>";
            echo "<td valign='top'>";
                echo "<button type='button' class='btn_blue' name='new_btn' value='Создать новую' title='Создать цепочку' ".($acs_form ? '' : 'disabled')."><img src='images/new.png'>&emsp;Создать новую </button><br><br>";
                echo "<button type='submit' class='btn_blue' name='edit_btn' value='Редактировать' title='Редактировать цепочку' onclick='return edit_confirm()' ".($acs_form ? '' : 'disabled')."><img src='images/edit.png'>&emsp;Редактировать </button><br><br>";
                echo "<button type='submit' class='btn_blue' name='del_btn' value='Удалить' title='Удалить цепочку' onclick='return del_confirm()' ".($acs_form ? '' : 'disabled')."><img src='images/delete.png'>&emsp;Удалить &emsp;&emsp;&emsp;&nbsp;</button>";
                echo "<input id='chain_id' name='chain_id' type='text' value='' hidden>";
            echo "</td>";
        echo "</tr>";
        echo "<tr class='next' hidden>";
            echo "<td colspan='0'>";
                echo "Описание новой корреляционной цепочки: <input name='chain_inp' type='text' size='40' maxlength='256'>";
            echo "</td>";
        echo "</tr>";
        echo "<tr class='next' hidden>";
            echo "<td colspan='0' align='center'>";
                echo "<button type='submit' class='btn_blue' name='next_btn' value='Продолжить' onclick='return next_confirm()' title='Перейти к редактированию цепочки' ".($acs_form ? '' : 'disabled')."><img src='images/ok.png'> Продолжить... </button>";
                echo "&emsp;<button type='submit' class='btn_blue' name='cancel_btn' value='Отменить' title='Отменить и вернуться' ".($acs_form ? '' : 'disabled')."><img src='images/details_close.png' height='16' width='16'> Отменить</button>";
            echo "</td>";
        echo "</tr>";
    echo "</table><br><br><hr><br>";
echo "</form>";

// selected chain
echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='formEdit'>";
if ($mode == 'new')
    echo"<h3 align='center'>Добавление новой цепочки  \"{$new_chain_name}\"";
else if ($mode == 'edit') {
    $sel = "select PFR_CORRELATION_CHAIN_DESCRIPTION
            from PFR_CORRELATION_DESCRIPTION
            where PFR_CORRELATION_CHAIN_ID = {$chain_id}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    $row = db2_fetch_assoc($stmt);
    echo "<h3 align='center'>Редактирование цепочки \"{$row['PFR_CORRELATION_CHAIN_DESCRIPTION']}\"";
}

if ($mode != 'view') {
    echo "&emsp;<button type='submit' class='btn_blue' name='save_btn' value='Сохранить' title='Сохранить цепочку' " . ($acs_form ? '' : 'disabled') . " onclick=\"return confirm('Сохранить цепочку в БД?..')\"><img src='images/events.png' height='16' width='16'> Сохранить</button>";
    echo "&emsp;<button type='submit' class='btn_blue' name='cancel_btn' value='Отменить' title='Отменить и вернуться' " . ($acs_form ? '' : 'disabled') . " onclick=\"return confirm('Если были сделаны изменения, они будут утеряны!')\"><img src='images/details_close.png' height='16' width='16'> Отменить</button></h3>";
}

echo "<table border='1' cellpadding='10' align='center'>";
    echo "<tr class='rec_hide' id='title'>";
        echo "<th>Имя КЭ</th>";
        echo "<th>Событие</th>";
        echo "<th>Тип события</th>";
    echo "</tr>";

    // view mode
    if ($mode == 'view') {
        $sel = "select PFR_CORRELATION_CHAIN_ID, PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE
                from PFR_CORRELATIONS
                order by PFR_CORRELATION_EVENT_TYPE, ID asc";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        while ($row = db2_fetch_assoc($stmt)) {
            echo "<tr class='rec_hide chain' chain_id_show='{$row['PFR_CORRELATION_CHAIN_ID']}'>";
                echo "<td>{$row['PFR_KE_TORS']}</td>";
                echo "<td>{$row['PFR_SIT_NAME']}</td>";
                echo "<td align='center'>{$row['PFR_CORRELATION_EVENT_TYPE']}</td>";
            echo "</tr>";
        }
    }
    // edit or new mode
    else {
        $sel = "select PFR_CORRELATION_CHAIN_ID, PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE
                from PFR_CORRELATIONS
                where PFR_CORRELATION_CHAIN_ID = {$chain_id}
                order by PFR_CORRELATION_EVENT_TYPE, ID asc";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        while ($row = db2_fetch_assoc($stmt)) {
            echo "<tr class='rec_hide chain' chain_id_show='{$row['PFR_CORRELATION_CHAIN_ID']}'>";
                echo "<td>{$row['PFR_KE_TORS']}</td>";
                echo "<td>{$row['PFR_SIT_NAME']}</td>";
                echo "<td align='center'>{$row['PFR_CORRELATION_EVENT_TYPE']}</td>";
            echo "</tr>";
        }
    }
echo "</table>";
echo "</form>";

// database connection close
db2_close($connection_TBSM);

?>
</body>
</html>
