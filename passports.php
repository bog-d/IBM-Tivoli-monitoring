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
    <title>Общий список Паспортов и Протоколов Мониторинга</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/passports.js"></script>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';

	$base_new_passport_dir = "/usr/local/apache2/htdocs/pfr_other/Passports/";
    $base_new_passport_page = "http://10.103.0.60/pfr_other/Passports/";
    $base_arch_passport_page = "http://10.103.0.60/pfr_other/Passports/Archive/ALL/";
	$base_arch_survey_page = "http://10.103.0.60/pfr_other/Surveys/Archive/Last/";
	
	$PageStatusFlag = 0; 	//  1 - паспорт найден 							-> будет осуществлён редирект на загрузку
							//  0 - в адресе не задан параметр ServiceName 	-> будет открыт общий список паспортов
							// -1 - паспорт не найден 						-> будет открыт общий список паспортов 
							// -2 - паспорт найден, но скрыт					-> будет открыт общий список паспортов 	
							
    $table_arr = array ( array (
        "Регион" => '',
        "Среда" => '',
        "!count" => 0,
        "Подсистема" => '',
        "!pass_link" => '',
        "Паспорт" => '',
        "Версия Паспорта" => '',
        "!proc_link" => '',
        "Протокол" => '',
    ));

    // get script parameters
    $ServiceName = isset($_GET["ServiceName"]) ? $_GET["ServiceName"] : '';
    $DocType = isset($_GET["DocType"]) ? $_GET["DocType"] : 'passport';

    // show the last version of passport if exists
    if (!empty($ServiceName)) {
        // sql select from DB passport tables
        $sql = "select code.PTK, code.REGION, code.ENVIRONMENT, ver.PASS_VERSION, ver.PASS_DATE, ver.PASS_FILE
                from DB2INST1.PFR_PASSPORT_SERVICES as serv
                left join PFR_PASSPORT_CODING as code
                on serv.FILE_CODE = code.FILE_CODE
                left join PFR_PASSPORT_VERSIONS as ver
                on serv.FILE_CODE = ver.FILE_CODE
                where serv.SERVICE_NAME = '{$ServiceName}'
                order by ver.PASS_VERSION desc";
        $stmt = db2_prepare($connection_TBSM, $sql);
        $result = db2_execute($stmt);
        $row = db2_fetch_assoc($stmt);
        if (!empty($row)) {
            $pass_file_name = "Паспорт_{$row['PTK']}_{$row['REGION']}_{$row['ENVIRONMENT']}_версия_{$row['PASS_VERSION']}_от_{$row['PASS_DATE']}.{$row['PASS_FILE']}";
            if (file_exists($base_new_passport_dir.$pass_file_name)) {
                header("Location: ".$base_new_passport_page.$pass_file_name);
                die();
            }
        }
    }

    // sql select from DB passport tables for all passports and protocols
    $sql = "select code.REGION, code.ENVIRONMENT, serv.SERVICE_NAME, base.DISPLAYNAME, serv.FILE_CODE, code.PTK, code.PASS_DISPLAY_NAME, code.PROC_DISPLAY_NAME, ver.PASS_VERSION, ver.PASS_DATE, ver.PASS_FILE, ver.PROC_DATE, ver.PROC_FILE
            from DB2INST1.PFR_PASSPORT_SERVICES as serv
            join TBSMBASE.SERVICEINSTANCE base
            on serv.SERVICE_NAME = base.SERVICEINSTANCENAME
            left join PFR_PASSPORT_CODING as code
            on serv.FILE_CODE = code.FILE_CODE
            left join PFR_PASSPORT_VERSIONS as ver
            on serv.FILE_CODE = ver.FILE_CODE
            where serv.SERVICE_NAME not like '*REFINE*%'
            order by code.REGION desc, code.ENVIRONMENT asc, base.DISPLAYNAME asc, serv.FILE_CODE asc, code.PASS_DISPLAY_NAME asc, serv.SERVICE_NAME asc, ver.PASS_VERSION desc";
    $stmt = db2_prepare($connection_TBSM, $sql);
    $result = db2_execute($stmt);

    // top header
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"10\" bgcolor=\"#E5E0E0\">";
        echo "<tr>";
            echo "<td width=\"20%\" align=\"left\">";
            echo "</td>";
                echo "<td align=\"center\">";
                echo "<h3>";
                    echo "Общий список Паспортов и Протоколов Мониторинга";
                echo "</h3>";
            echo "</td>";
            echo "<td width=\"25%\" align=\"right\">";
            echo "</td>";
        echo "</tr>";
    echo "</table>";
    echo "<br \>";

    $file_code = $service_name = '';
    $i = 0;
    while ($row = db2_fetch_assoc($stmt)) {
        if (strcmp($row['FILE_CODE'], $file_code) != 0) {
            $file_code = $row['FILE_CODE'];
            $service_name = $row['SERVICE_NAME'];

            $table_arr[++$i] = array (
                "Регион" => $row['REGION'],
                "Среда" => $row['ENVIRONMENT'],
                "!count" => 1,
                "Подсистема" => array( array('ServiceName' => $row['SERVICE_NAME'], 'DisplayName' => $row['DISPLAYNAME'])),
                "!pass_link" => "http://10.103.0.60/pfr_other/Passports/Паспорт_".$row['PTK']."_".$row['REGION']."_".$row['ENVIRONMENT']."_версия_".$row['PASS_VERSION']."_от_".$row['PASS_DATE'].".".$row['PASS_FILE'],
                "Паспорт" => $row['PASS_DISPLAY_NAME'],
                "Версия Паспорта" => $row['PASS_VERSION']." от ".$row['PASS_DATE'],
                "!proc_link" => empty($row['PROC_FILE']) ? '' : "http://10.103.0.60/pfr_other/Passports/Протокол_".$row['PTK']."_".$row['REGION']."_".$row['ENVIRONMENT']."_версия_".$row['PASS_VERSION']."_от_".$row['PROC_DATE'].".".$row['PROC_FILE'],
                "Протокол" => empty($row['PROC_FILE']) ? '' : $row['PROC_DISPLAY_NAME'],
            );
        }
        else {
            if (strcmp($row['SERVICE_NAME'], $service_name) != 0) {
                $service_name = $row['SERVICE_NAME'];
                $table_arr[$i]['!count']++;
                $table_arr[$i]['Подсистема'][] = array('ServiceName' => $row['SERVICE_NAME'], 'DisplayName' => $row['DISPLAYNAME']);
            }
            if (empty($table_arr[$i]['Протокол']) and !empty($row['PROC_FILE'])) {
                $table_arr[$i]['!proc_link'] ="http://10.103.0.60/pfr_other/Passports/Протокол_".$row['PTK']."_".$row['REGION']."_".$row['ENVIRONMENT']."_версия_".$row['PASS_VERSION']."_от_".$row['PROC_DATE'].".".$row['PROC_FILE'];
                $table_arr[$i]['Протокол'] = $row['PROC_DISPLAY_NAME'];
            }
            continue;
        }
    }

    echo "<br>Для перехода к просмотру всех имеющихся версий Паспортов и Протоколов кликните по ссылке в столбце \"Подсистема\".<br><br>";
    echo "Для загрузки последней имеющейся версии Паспорта кликните по ссылке в столбце \"Паспорт\".<br>";
    echo "Для загрузки последней имеющейся версии Протокола кликните по ссылке в столбце \"Протокол\".<br><br>";

    echo "<table class=\"content\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        $i = 0;
        foreach ($table_arr as $rec) {
            echo "<tr>";
                if ($i++ == 0) {
                    foreach ($rec as $title => $value) {
                        if (strpos($title, '!') === false)
                            echo "<th>" . $title . "</th>";
                    }
                }
                else {
                    $href = '';
                    foreach ($rec as $title => $value)
                        switch ($title) {
                            case '!count':
                                $count = $value;
                                break;
                            case '!pass_link':
                            case '!proc_link':
                                $href = $value;
                                break;
                            case 'Подсистема':
                                echo "<td>";
                                    if ($count > 1) {
                                        echo "<table><tr><td>";
                                            echo "<div class='toggle' id='toggle_{$value[0]['ServiceName']}'><img src='images/details_open.png' title='Показать'></div>";
                                            echo "<div class='toggle hide' id='toggle_{$value[0]['ServiceName']}'><img src='images/details_close.png' title='Скрыть'></div>";
                                        echo "</td>";
                                        echo "<td>";
                                          echo "Кол-во подсистем: {$count} ";
                                        echo "</td>";
                                        echo "</tr><tr>";
                                        echo "<td colspan='0'>";
                                            echo "<div class='hide' id='content_{$value[0]['ServiceName']}'>";
                                            foreach ($value as $v) {
                                                    $href = "http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName=".$v['ServiceName'];
                                                    echo "<a href='$href' target='_blank' title='Перейти к просмотру всех версий по этой подсистеме'>{$v['DisplayName']}</a><br>";
                                                }
                                            echo "</div>";
                                        echo "</td></tr></table>";
                                    }
                                    else {
                                        $href = "http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName=".$value[0]['ServiceName'];
                                        echo "<a href='$href' target='_blank' title='Перейти к просмотру всех версий по этой подсистеме'>{$value[0]['DisplayName']}</a>";
                                    }
                                echo "</td>";
                                break;
                            case 'Паспорт':
                            case 'Протокол':
                                echo "<td><a href='$href' target='_blank' title='Загрузить последнюю версию ".$title."а'>$value</a></td>";
                                break;
                            default:
                                echo "<td>$value</td>";
                                break;
                        }
                }
            echo "</tr>";
        }
    echo "</table>";
    echo "<br><br><hr><br><br>";


    // ********************************************************************************************************************************

    // old part


    // если передан параметр ServiceName
    if (!empty($ServiceName)) {
        $sql = "SELECT * FROM DB2INST1.PFR_LINK4PASSPORT WHERE PFR_SERVICE_ID = '$ServiceName' and ".($DocType == 'passport' ? 'LINK4PASSPORT' : 'LINK4SURVEY')." <> ''";
        $stmt = db2_prepare($connection_TBSM, $sql);
        $result = db2_execute($stmt);
        $row = db2_fetch_assoc($stmt);

        if (empty($row['PFR_SERVICE_ID']))
            $PageStatusFlag = -1;
        else
            $PageStatusFlag = $row['VISIBILITY'] == 'Y' ? 1 : -2;
    }

    // вывод данных в зависимости от статуса PageStatusFlag
    if ($PageStatusFlag == 1) {
        // редирект на загрузку
        $URL = $DocType == 'passport' ? $base_arch_passport_page.$row['LINK4PASSPORT'] : $base_arch_survey_page.$row['LINK4SURVEY'];
        header("Location: ".$URL);
        die();
    }
    else {
        // top header
        echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"10\" bgcolor=\"#E5E0E0\">";
            echo "<tr>";
                echo "<td width=\"20%\" align=\"left\">";
                echo "</td>";
                echo "<td align=\"center\">";
                        echo "<h3>";
                            echo "Общий список паспортов и опросных листов Мониторинга до 2015 г.";
                        echo "</h3>";
                echo "</td>";
                echo "<td width=\"25%\" align=\"right\">";
                echo "</td>";
            echo "</tr>";
        echo "</table>";
        echo "<br \>";

        if ($PageStatusFlag < 0) {
            // вывод сообщения о том, что паспорт или опросный лист не найден
            echo "<div class=\"warning\">";
                echo "<span>";
                    echo ($DocType == 'passport' ? 'Паспорт' : 'Опросный лист')." для <b style=\"color:orange\">".$ServiceName."</b> (идентификатор ПТК в сервисной модели Мониторинга) ".($PageStatusFlag == -1 ? 'не найден.' : 'скрыт.');
                    if ($PageStatusFlag == -1)
                        echo "<br \>Возможно, не настроена ссылка на ".($DocType == 'passport' ? 'паспорт' : 'опросный лист')." в БД TBSM DB2INST1.PFR_LINK4PASSPORT, либо ".($DocType == 'passport' ? 'паспорт' : 'опросный лист')." ещё не сформирован.";
                echo "</span>";
            echo "</div>";
            echo "<br \>";
        }

        // вывод общего списка
        $sql = "SELECT DISTINCT LINK4PASSPORT, PASSPORT_DISPLAY_NAME, LINK4SURVEY, SURVEY_DISPLAY_NAME FROM DB2INST1.PFR_LINK4PASSPORT WHERE VISIBILITY = 'Y'";
        $stmt = db2_prepare($connection_TBSM, $sql);
        $result = db2_execute($stmt);
        $row_count = 0;
        echo "<table class=\"content\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
            echo "<tr>";
                echo "<th>";
                    echo "Паспорт";
                echo "</th>";
                echo "<th>";
                    echo "Опросный лист";
                echo "</th>";
            echo "</tr>";
            while ($row = db2_fetch_assoc($stmt)) {
                $row_count ++;
                echo "<tr>";
                    echo "<td>";
                        $LINK4PASSPORT=$row['LINK4PASSPORT'];
                        $URL=$base_arch_passport_page.$LINK4PASSPORT;
                        echo (empty($row['LINK4PASSPORT']) or empty($row['PASSPORT_DISPLAY_NAME'])) ? "Отсутствует или не задан" : "<a href=\"".$URL."\">".$row['PASSPORT_DISPLAY_NAME']."</a>";
                    echo "</td>";
                    echo "<td>";
                        $LINK4SURVEY=$row['LINK4SURVEY'];
                        $URL=$base_arch_survey_page.$LINK4SURVEY;
                        echo (empty($row['LINK4SURVEY']) or empty($row['SURVEY_DISPLAY_NAME'])) ? "Отсутствует или не задан" : "<a href=\"".$URL."\">".$row['SURVEY_DISPLAY_NAME']."</a>";
                    echo "</td>";
                echo "</tr>";
            }
        echo "</table>";
        echo "<br \>";
    }

    // database connections close
    db2_close($connection_TBSM);

	?>
</body>
</html>




