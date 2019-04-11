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
    <title>Просмотр резервной копии данных настройки отправки инцидентов</title>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    include 'functions/tbsm.php';

    $level = 0;					// iteration level
    $path_history = [ ];		// array for history tree from parents to childs
    $results = [ ];				// array for endpoint childs
    $services = [ ];			// sorted and unique array for endpoint childs

    $table_N1_titles = array (
        "Код индикатора",
        "Тип агента",
        "Расположение агента",
        "Сервер объектов",
        "Регион",
        "Узел",
        "Объект мониторинга",
        "Ссылка на КЭ",
        "Фильтр по ситуациям",
        "Назначение",
        "Отправка инцидентов",
    );

	// *********************************************************** WEB FORM PREPARE & HANDLE ***********************************************************

	// get script parameters
	$NODEID = isset($_GET["ServiceName"]) ? $_GET["ServiceName"] : "";
    if (empty($NODEID))
        exit("ServiceName is null!");

    // connection to databases
    if (!$connection_TBSM)
        exit("Database TBSM connection failed.");

    // find all child services
    $sel = "SELECT SERVICEINSTANCEID, DISPLAYNAME FROM TBSMBASE.SERVICEINSTANCE WHERE SERVICEINSTANCENAME = '$NODEID'";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    $row = db2_fetch_assoc($stmt);

    echo "<h3>Резервная копия настроек отправки инцидентов по подсистеме {$NODEID} ({$row['DISPLAYNAME']})</h3>";

    // recursive function call
    ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, $level, $path_history);
    $services = array_unique(array_column($results, 'service'));
    sort($services);

    echo "<table border='1' cellspacing='0' cellpadding='5'>";
    echo "<tr>";
    foreach ($table_N1_titles as $n => $title)
        echo "<th>{$title}</th>";
    echo "</tr>";

    $row_count = 0;
    foreach ($services as $value) {
        // record(s) selection from PFR_LOCATIONS table
        $sel = "SELECT ID, SERVICE_NAME, SUBCATEGORY, (CASE WHEN AGENT_NODE = '' THEN NODE ELSE AGENT_NODE END) AS AGENT_PLACE, PFR_OBJECTSERVER, PFR_ID_TORG, NODE, PFR_OBJECT, PFR_KE_TORS, SITFILTER, PFR_NAZN, INCIDENT_SEND
                    FROM DB2INST1.PFR_LOCATIONS 
                    WHERE SERVICE_NAME = '$value'
                    ORDER BY SUBCATEGORY, (CASE WHEN AGENT_NODE = '' THEN NODE ELSE AGENT_NODE END) ASC";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        while ($row = db2_fetch_assoc($stmt)) {
            echo "<tr>";
                $sel_snap = "SELECT INCIDENT_SEND
                        FROM DB2INST1.PFR_MAINTENANCE_SNAPSHOTS 
                        WHERE SERVICE_NAME = '$NODEID' and LOC_ID = {$row['ID']}";
                $stmt_snap = db2_prepare($connection_TBSM, $sel_snap);
                $result_snap = db2_execute($stmt_snap);
                $row_snap = db2_fetch_assoc($stmt_snap);

                foreach ($row as $key => $cell)
                    switch ($key) {
                        case 'ID':
                            break;
                        case 'INCIDENT_SEND':
                            $cell = empty($row_snap) ? '' : $row_snap['INCIDENT_SEND'];
                            echo "<td class='".($cell == 1 ? 'green_status' : ($cell == -1 ? 'blue_status' : 'red_status'))."'>".($cell == 1 ? 'включена' : 'отключена')."</td>";
                            break;
                        default:
                            echo "<td>{$cell}</td>";
                            break;
                    }
                $row_count++;
            echo "</tr>";
        }
    }

    // total number of records
    echo "<tr>";
    echo "<td colspan='0'>Количество строк в выборке: {$row_count}</td>";
    echo "</tr>";
    echo "</table>";

    // database connection close
    db2_close($connection_TBSM);

	?>
</body>
</html>
