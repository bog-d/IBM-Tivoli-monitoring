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

// maximum value of the counter
const MAX_COUNT = 5;

$mode ='view';
$new_chain_name = '';
$chain_id = null;
$output = '';
$error = false;

/******************************************************************************************************************/

// chain delete from DB
if (isset($_POST["del_btn"])) {
    $chain_id = $_POST['chain_id'];

    $sel = "select PFR_CORRELATION_CHAIN_DESCRIPTION from PFR_CORRELATION_CHAIN where ID = {$chain_id}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    if (!$error and !$result)
        $error = true;
    $row = db2_fetch_assoc($stmt);
    $chain_name = $row['PFR_CORRELATION_CHAIN_DESCRIPTION'];

    $sel = "delete from PFR_CORRELATION_CHAIN where ID = {$chain_id}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    if (!$error and !$result)
        $error = true;

    $sel = "delete from PFR_CORRELATIONS where PFR_CORRELATION_CHAIN_ID = {$chain_id}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    if (!$error and !$result)
        $error = true;

    $output = $error ? "При удалении цепочки \"{$chain_name}\" произошла ошибка!" : "Цепочка \"{$chain_name}\" успешно удалена.";
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
$sel = "select d.ID, d.PFR_CORRELATION_CHAIN_DESCRIPTION, c.PFR_KE_TORS, c.PFR_SIT_NAME
        from PFR_CORRELATION_CHAIN d, PFR_CORRELATIONS c
        where d.ID = c.PFR_CORRELATION_CHAIN_ID and c.PFR_CORRELATION_EVENT_TYPE = 'm'";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);

echo "<form action='{$_SERVER['PHP_SELF']}' method='post' id='formSelect'>";
    echo "<br><br><table class='gantt' cellpadding='10' align='center'>";
        echo "<tr class='first'>";
            echo "<td>Список корреляционных цепочек:</td>";
            echo "<td>";
                echo "<input name='chain_search' type='text' size='20' maxlength='32' autofocus placeholder='поиск по подстроке'>";
            echo "</td>";
        echo "</tr>";
        echo "<tr class='first'>";
            echo "<td valign='top'>";
                echo "<table cellpadding='10' cellspacing='0' border='1'>";
                    echo "<tr>";
                        echo "<th>Имя</th>";
                        echo "<th>КЭ корневого события</th>";
                        echo "<th>Код корневого события</th>";
                    echo "</tr>";

                    while ($row = db2_fetch_assoc($stmt)) {
                        echo "<tr class='rec_ch pointer' chain_id_sel='{$row['ID']}'>";
                            echo "<td class='chain_descr'>{$row['PFR_CORRELATION_CHAIN_DESCRIPTION']}</td>";
                            echo "<td>{$row['PFR_KE_TORS']}</td>";
                            echo "<td>{$row['PFR_SIT_NAME']}</td>";
                        echo "</tr>";
                    }
                echo "</table>";
            echo "</td>";
            echo "<td valign='top'>";
                echo "<button type='button' class='btn_blue' name='new_btn' value='Создать новую' title='Создать цепочку' ".((!$acs_form or $mode == 'new' or $mode == 'edit') ? 'disabled' : '')."><img src='images/new.png'>&emsp;Создать новую </button><br><br>";
                echo "<button type='submit' class='btn_blue' name='edit_btn' value='Редактировать' title='Редактировать цепочку' onclick='return edit_confirm()' ".((!$acs_form or $mode == 'new' or $mode == 'edit') ? 'disabled' : '')."><img src='images/edit.png'>&emsp;Редактировать </button><br><br>";
                echo "<button type='submit' class='btn_blue' name='del_btn' value='Удалить' title='Удалить цепочку' onclick='return del_confirm()' ".((!$acs_form or $mode == 'new' or $mode == 'edit') ? 'disabled' : '')."><img src='images/delete.png'>&emsp;Удалить &emsp;&emsp;&emsp;&nbsp;</button>";
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
            from PFR_CORRELATION_CHAIN
            where ID = {$chain_id}";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    $row = db2_fetch_assoc($stmt);
    echo "<h3 align='center'>Редактирование цепочки \"{$row['PFR_CORRELATION_CHAIN_DESCRIPTION']}\"";
}

if ($mode != 'view') {
    echo "&emsp;<button type='submit' class='btn_blue' name='save_btn' value='Сохранить' title='Сохранить цепочку' " . ($acs_form ? '' : 'disabled') . " onclick=\"return save_confirm()\"><img src='images/events.png' height='16' width='16'> Сохранить</button>";
    echo "&emsp;<button type='submit' class='btn_blue' name='cancel_btn' value='Отменить' title='Отменить и вернуться' " . ($acs_form ? '' : 'disabled') . " onclick=\"return confirm('Если были сделаны изменения, они не будут сохранены!')\"><img src='images/details_close.png' height='16' width='16'> Отменить</button></h3>";
    echo "<input name='edit_chain_id' type='text' value='{$chain_id}' hidden>";
    echo "<input name='new_chain_name' type='text' value='{$new_chain_name}' hidden>";
}

// view mode
if ($mode == 'view') {
    $sel = "select PFR_CORRELATION_CHAIN_ID, PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE
            from PFR_CORRELATIONS
            order by PFR_CORRELATION_EVENT_TYPE, ID asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    echo "<table border='1' cellpadding='10' cellspacing='0' align='center'>";
        echo "<tr class='rec_hide' id='title'>";
            echo "<th>Имя КЭ</th>";
            echo "<th>Событие</th>";
            echo "<th>Тип события</th>";
        echo "</tr>";
        while ($row = db2_fetch_assoc($stmt)) {
            echo "<tr class='rec_hide chain' chain_id_show='{$row['PFR_CORRELATION_CHAIN_ID']}'>";
                echo "<td>{$row['PFR_KE_TORS']}</td>";
                echo "<td>{$row['PFR_SIT_NAME']}</td>";
                echo "<td align='center'>{$row['PFR_CORRELATION_EVENT_TYPE']}</td>";
            echo "</tr>";
        }
    echo "</table>";
}
// edit or new mode
else {
    // KE array from TEMS
    $sel = "select distinct PFR_KE_TORS
            from PFR_TEMS_SIT_AGGR
            where PFR_KE_TORS <> ''
            order by PFR_KE_TORS asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    while ($row = db2_fetch_assoc($stmt))
        $ke_arr[] = $row['PFR_KE_TORS'];

    // situations array from TEMS
    $sel = "select distinct SIT_CODE
            from PFR_TEMS_SIT_AGGR
            order by SIT_CODE asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    while ($row = db2_fetch_assoc($stmt))
        $sit_arr[] = $row['SIT_CODE'];

    // selected chain
    $sel = "select ID, PFR_CORRELATION_CHAIN_ID, PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE
            from PFR_CORRELATIONS
            where PFR_CORRELATION_CHAIN_ID = ".($chain_id == null ? 'null' : $chain_id)."
            order by PFR_CORRELATION_EVENT_TYPE, ID asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    echo "<table border='1' cellpadding='10' cellspacing='0' align='center'>";
        echo "<tr id='title'>";
            echo "<th>Имя КЭ</th>";
            echo "<th>Событие</th>";
            echo "<th>Тип события <b>m</b></th>";
            echo "<th><img src='images/delete.png' title='Отметить звенья для удаления'></th>";
        echo "</tr>";
        while ($row = db2_fetch_assoc($stmt)) {
            echo "<tr>";
                echo "<td>";
                    echo "<input name='input' type='text' maxlength='32' placeholder='поиск по подстроке' id='inp_ke_{$row['ID']}'><br/>";
                    echo "<select size = '1' name = 'list_ke[{$row['ID']}]' id='sel_ke_{$row['ID']}'>";
                        foreach ($ke_arr as $value)
                            echo "<option value='{$value}' ".($value == $row['PFR_KE_TORS'] ? 'selected' : '').">{$value}</option>";
                    echo "</select>";
                echo "</td>";
                echo "<td>";
                    echo "<input name='input' type='text' maxlength='32' placeholder='поиск по подстроке' id='inp_si_{$row['ID']}'><br/>";
                    echo "<select size = '1' name = 'list_si[{$row['ID']}]' id='sel_si_{$row['ID']}'>";
                        foreach ($sit_arr as $value)
                            echo "<option value='{$value}' ".($value == $row['PFR_SIT_NAME'] ? 'selected' : '').">{$value}</option>";
                    echo "</select>";
                echo "</td>";
//                echo "<td align='center'>";
//                    echo "<select name = 'type_list[{$row['ID']}]' id='sel_type_{$row['ID']}'>";
//                        echo "<option value ='m' ".($row['PFR_CORRELATION_EVENT_TYPE'] == 'm' ? 'selected' : '').">m</option>";
//                        echo "<option value ='s' ".($row['PFR_CORRELATION_EVENT_TYPE'] == 's' ? 'selected' : '').">s</option>";
//                    echo "</select>";
//                echo "</td>";
                echo "<td align='center'>";
                    echo "<input type='radio' name = 'type_list' id='sel_type_{$row['ID']}' value = '{$row['ID']}' ".($row['PFR_CORRELATION_EVENT_TYPE'] == 'm' ? 'checked' : '').">";
                echo "</td>";
                echo "<td align='center'>";
                    echo "<input type='checkbox' name='chk_del[{$row['ID']}]' id='chk_{$row['ID']}' ".($row['PFR_CORRELATION_EVENT_TYPE'] == 'm' ? 'disabled' : '').">";
                echo "</td>";
            echo "</tr>";
        }

        // new rows
        for ($i = 1; $i <= MAX_COUNT; $i++) {
            echo "<tr class='new_row new_records ".(($mode == 'new' and $i ==1) ? '' : 'rec_hide')."' id_new='{$i}'>";
                echo "<td>";
                    echo "<input name='input' type='text' size='40' maxlength='32' placeholder='поиск по подстроке' id='inp_ke_new_{$i}'><br/>";
                    echo "<select size = '1' name = 'list_ke_new[{$i}]' id='sel_ke_new_{$i}'>";
                    foreach ($ke_arr as $value)
                        echo "<option value='{$value}'>{$value}</option>";
                    echo "</select>";
                echo "</td>";
                echo "<td>";
                    echo "<input name='input' type='text' size='40' maxlength='32' placeholder='поиск по подстроке' id='inp_si_new_{$i}'><br/>";
                    echo "<select size = '1' name = 'list_si_new[{$i}]' id='sel_si_new_{$i}'>";
                    foreach ($sit_arr as $value)
                        echo "<option value='{$value}'>{$value}</option>";
                    echo "</select>";
                echo "</td>";
//                echo "<td align='center'>";
//                    echo "<select name = 'type_list_new[{$i}]' id='sel_type_new_{$i}' ".(($mode == 'new' and $i ==1) ? '' : 'disabled').">";
//                        echo "<option value ='m' ".(($mode == 'new' and $i ==1) ? 'selected' : '').">m</option>";
//                        echo "<option value ='s' ".(($mode == 'new' and $i ==1) ? '' : 'selected').">s</option>";
//                    echo "</select>";
//                echo "</td>";
                echo "<td align='center'>";
                    echo "<input type='radio' name = 'type_list' id='sel_type_new_{$i}' value = 'new_{$i}' ".(($mode == 'new' and $i ==1) ? 'checked' : 'disabled').">";
                echo "</td>";
                echo "<td align='center'>";
                    echo "<input type='checkbox' name='chk_del_new[{$i}]' id='chk_new_{$i}' ".(($mode == 'new' and $i ==1) ? 'disabled' : 'checked').">";
                echo "</td>";
            echo "</tr>";
        }
    echo "</table>";
    echo "<p align='center'><button type='button' class='btn_blue' name='add_btn' value='Добавить звено' title='Добавить звено в цепочку' " . ($acs_form ? '' : 'disabled') . "><img src='images/new.png' height='16' width='16'> Добавить звено</button></p>";
}
echo "</form>";

// database connection close
db2_close($connection_TBSM);

?>
</body>
</html>
