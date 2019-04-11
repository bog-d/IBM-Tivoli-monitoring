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
    <title>Список остановленных ситуаций</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    require 'user_roles.php';

	// connection to TBSM database
	if (!$connection_TBSM)
        exit("Database TBSM connection failed.");

    // top header
    $title = "Список остановленных ситуаций";
    $links = array ();
    require 'functions/header_1.php';

    // top header informational message output
    require 'functions/header_2.php';

    // situations list
    echo "<br><br>Список содержит ситуации мониторинга в статусе \"Остановлена\" или \"Не назначена\", для которых установлен флаг автозапуска:<br><br>";
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Регион</th>";
            echo "<th>Сервер мониторинга</th>";
            echo "<th>Имя ситуации</th>";
            echo "<th>Код ситуации</th>";
        echo "</tr>";

        // record(s) selection from PFR_TEMS_SIT_AGGR table
        $sel = "SELECT distinct SIT_NAME, SIT_CODE, REGION, TEMS
                FROM DB2INST1.PFR_TEMS_SIT_AGGR
                where STATUS = 'P' and AUTOSTART = '*YES'
                order by REGION, TEMS, SIT_NAME asc
                ";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        $total = 0;
        while ($row = db2_fetch_assoc($stmt)) {
            echo "<tr>";
                // Регион
                echo "<td>".$row['REGION']."</td>";
                // TEMS
                echo "<td>".$row['TEMS']."</td>";
                // Имя ситуации
                echo "<td>".$row['SIT_NAME']."</td>";
                // Код ситуации
                echo "<td>".$row['SIT_CODE']."</td>";
            echo "</tr>";
            $total++;
        }

        echo "<tr>";
            echo "<th colspan=0>";
                echo "Всего: ".$total;
            echo "</th>";
        echo "</tr>";
    echo "</table>";

    // database connection close
    db2_close($connection_TBSM);

    ?>
</body>
</html>