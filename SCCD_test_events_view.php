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
	<title>Просмотр журнала отправки тестовых событий</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
	<style>
		select {
			width: 300px;
		}
	</style>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    include 'functions/utime.php';
    include 'functions/user_roles.php';

    const NO_FILTER = "(нет фильтра)";

	$filters = [];
    $filters_string = "";
	$titles = array(
        "Дата и время&nbsp;&darr;" => "TIMESTAMP",
        "Код индикатора" => "SERVICE_NAME",
        "Ответственное лицо" => "USER",
        "Содержание операции" => "OPERATION",
        "Примечание" => "DESCRIPTION",
    );

    // -------------------------------------------------------------------------------------------------------------------

    // get script parameters
    $container = isset($_GET["container"]) ? $_GET["container"] : '';
    $service = isset($_GET["service"]) ? $_GET["service"] : '';
    if (!isset($_POST['clear_filters']))
        $filters = isset($_POST['apply_filters']) ? $_POST['filters'] : ( isset($_GET["filters"]) ? unserialize($_GET["filters"]) : $filters);

    // top header
    $title = "Просмотр журнала отправки тестовых событий";
    $links = array ("");
    require 'functions/header_1.php';

    // top header informational message output
    require 'functions/header_2.php';

	// logs output
    echo "<form action='{$_SERVER['PHP_SELF']}?container={$container}&service={$service}&filters=".serialize($filters)."' method='post' id='formId'>";
    echo "<p align='center'>";
        echo "<button name='apply_filters'><img src='images/filter.png' hspace='10' align='top'>применить выбранные фильтры</button>&emsp;&emsp;&emsp;";
        echo "<button name='clear_filters'><img src='images/delete.png' hspace='10' align='top'>очистить все фильтры</button>";
    echo "</p>";
	echo "<table width=\"100%\" border=\"1\" cellspacing=\"0\" cellpadding=\"8\">";
		
		// table titles
		echo "<tr>";
		    foreach ($titles as $title => $index)
    			echo "<th>{$title}</th>";
		echo "</tr>";
		
		// table filters
        echo "<tr>";
            foreach ($titles as $index) {
                $sel = "SELECT distinct ".($index == "TIMESTAMP" ? "date(TIMESTAMP) as TIMESTAMP" : "\"{$index}\"")." FROM DB2INST1.PFR_TEST_EVENTS_LOG WHERE SERVICE_NAME = '{$service}' ORDER BY \"{$index}\" ".($index == "TIMESTAMP" ? "DESC" : "ASC");
                $stmt_TBSM = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt_TBSM);

                echo "<td class='col_filter'>";
                    $no_filter = empty($filters) ? true : ($filters[$index] == NO_FILTER ? true : false);
                    echo "<select name='filters[$index]' size='1'>";
                        echo "<option value='".NO_FILTER."' ".($no_filter ? 'selected' : '').">".NO_FILTER."</option>";
                        while ($row = db2_fetch_assoc($stmt_TBSM)) {
                            $val = $row[$index];
                            if ($index == "TIMESTAMP")
                                $date_form = substr ($val, 8 ,2).'.'.substr ($val, 5 ,2).'.'.substr ($val, 0 ,4);
                            echo "<option value='{$val}' " . (($val == $filters[$index] and !$no_filter) ? 'selected' : '') . ">" . ($index == "TIMESTAMP" ? $date_form : $val) . "</option>";
                        }
                    echo "</select>";
                echo "</td>";
            }
        echo "</tr>";

        // table data with filters applied
        if (!empty($filters))
            foreach ($titles as $index)
                 if ($filters[$index] != NO_FILTER)
                     $filters_string .= "AND ".($index == "TIMESTAMP" ? "date(TIMESTAMP)" : "\"{$index}\"")." = '{$filters[$index]}'";

        $sel = "SELECT * FROM DB2INST1.PFR_TEST_EVENTS_LOG WHERE SERVICE_NAME = '{$service}' {$filters_string} ORDER BY TIMESTAMP DESC";
        $stmt_TBSM = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt_TBSM);
        while ($row = db2_fetch_assoc($stmt_TBSM)) {
            echo "<tr>";
                foreach ($titles as $index)
                    echo "<td>".($index == "TIMESTAMP" ? date("d.m.Y H:i:s", utime($row[$index])) : $row[$index])."</td>";
            echo "</tr>";
		}
	echo "</table>";
    echo "</form>";

    // database connections close
    db2_close($connection_TBSM);

	?>
</body>
</html>
