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
    <title>Проверка целостности таблицы PFR_LOCATIONS</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    require_once 'connections/MAXDB76.php';
	include 'functions/pfr_checks.php';
    include 'functions/tbsm.php';
    include 'functions/user_roles.php';

	// PFR_LOCATIONS fields for output
	$pfr_locations_field = array (  "ID" => '',
									"PFR_ID_TORG" => '',
									"PFR_NAZN" => '',
									"NODE" => '',
									"PFR_OBJECT" => '',
									"PFR_OBJECTSERVER" => '',
									"SUBCATEGORY" => '',
									"AGENT_NODE" => '',
									"SITFILTER" => '', 
									"PFR_KE_TORS" => '',
									"SERVICE_NAME" => '',	
								 );							 
	
	// log file					 
	$log_file = 'logs/integrity_check.log';

    $results = [ ];				// array for endpoint childs
    $diff_only = false;
    $output = "";

    // top header
    $title = "Проверка целостности таблицы PFR_LOCATIONS";
    $links = array ("");
    require 'functions/header_1.php';

    // web form button was pressed
    if (isset($_POST['sendChange']))
        switch ($_POST['sendChange']) {
            case 'Удалить отмеченное':
                if (isset($_POST['chkbx'])) {
                    $aCheckBoxesArray = array_filter($_POST['chkbx']);
                    $id_set = "('".implode("', '", array_keys($aCheckBoxesArray))."')";

                    // backup selected records
                    file_put_contents($log_file, date('d.m.Y H:i:s')."\n\n", FILE_APPEND | LOCK_EX);
                    $sel_delete = "select * from DB2INST1.PFR_LOCATIONS where ID in $id_set";
                    $stmt_delete = db2_prepare($connection_TBSM, $sel_delete);
                    $result_delete = db2_execute($stmt_delete);
                    while ($row = db2_fetch_array($stmt_delete)) {
                        foreach ($row as $r)
                            file_put_contents($log_file, $r."\t", FILE_APPEND | LOCK_EX);
                        file_put_contents($log_file, "\n", FILE_APPEND | LOCK_EX);
                    }
                    file_put_contents($log_file, "\n------------------------------------------------\n", FILE_APPEND | LOCK_EX);

                    // delete selected records
                    $sel_delete = "delete from DB2INST1.PFR_LOCATIONS where ID in $id_set";
                    $stmt_delete = db2_prepare($connection_TBSM, $sel_delete);
                    $result_delete = db2_execute($stmt_delete);
                    echo "<br>Из таблицы PFR_LOCATIONS удалены записи с ID: ".$id_set;
                    echo "<br><br><u>Всего</u>: ".count($aCheckBoxesArray)."<br><br>";
                    echo "Удалённые записи, при необходимости, могут быть восстановлены из файла логов ".$log_file;
                }
                else
                    echo "<br><br>Ничего не выбрано.";
                echo "<br><br><br><a href=\"/pfr_other/integrity_check.php\">Вернуться в меню типов проверки...</a>";
                break;
            case 'Привести PFR_LOCATION в соответствие с ТОРС':
                break;
            case 'Показать только различия':
                $diff_only = true;
                break;
            case 'Показать все записи':
                $diff_only = false;
                break;
            default:
                break;
        }

    // POST emulation
    if (isset($_GET['service']) and $_GET['service'] == 'PFR_ASTP_INTEGRATION_MITS') {
        $_POST['sendRequest'] = 'Выполнить проверку';
        $_POST['NODE'] = '';
        $_POST['SERVICE'] = 'PFR_ASTP_INTEGRATION_MITS';
        $_POST['check'] = 'region_codes_PFR_LOCATIONS_and_TORS';
        $diff_only = isset($_GET['diff']) ? $_GET['diff'] : false;
    }

    // request from integrity_check.php was received
    if (isset($_POST['sendRequest'])) {
        $templ = $_POST['NODE'];
        $service = $_POST['SERVICE'];
        $n = array_search($_POST['check'], array_column($check_types, 'name'));

        $output = $check_types[$n]['display']."<br>";
        $output = $output.$check_types[$n]['comment']."<br>";
        if ($check_types[$n]['name'] == 'region_codes_PFR_LOCATIONS_and_TORS')
            $output = $output."Показаны данные для сервиса ".$service.($diff_only ? " <font color='red'>(только различия)</font>" : '');
        else
            $output = $output."Для поля NODE применён фильтр по шаблону: ".$templ;

        // top header informational message output
        require 'functions/header_2.php';
        ?>

        <br>
        <form action="<?php echo $_SERVER['PHP_SELF'].((isset($_GET['service']) and $_GET['service'] == 'PFR_ASTP_INTEGRATION_MITS') ? '?service=PFR_ASTP_INTEGRATION_MITS&diff='.!$diff_only : ''); ?>" method="post" id="formId2">
            <?php
            if ($check_types[$n]['name'] == 'region_codes_PFR_LOCATIONS_and_TORS') {
                ?><input type="submit" class="btn_blue" name="sendChange" value="Привести PFR_LOCATION в соответствие с ТОРС" disabled/><?php
                if (isset($_GET['service']) and $_GET['service'] == 'PFR_ASTP_INTEGRATION_MITS') {
                    ?><input type="submit" class="btn_blue" name="sendChange"
                             value="<?php echo $diff_only ? 'Показать все записи' : 'Показать только различия' ?>" /><?php ;
                }
            }
            else {
                ?><input type="submit" class="btn_blue" name="sendChange" value="Удалить отмеченное" /><?php  ;
            }
            echo "<br><br>";

            // function call
            switch ($check_types[$n]['name']) {
                case 'duplicate_PFR_OBJECTSERVER':
                    $check_types[$n]['function']('NODE', 'PFR_OBJECTSERVER');
                    break;
                case 'duplicate_SERVICE_NAME':
                    $check_types[$n]['function']('NODE', 'SERVICE_NAME');
                    break;
                case 'PFR_LOCATIONS_TBSM_TEMS':
                    $check_types[$n]['function']();
                    break;
                case 'region_codes_PFR_LOCATIONS_and_TORS':
                    $check_types[$n]['function']($diff_only);
                    break;
                default:
                    break;
            }

            echo "<br>";
            if ($check_types[$n]['name'] == 'region_codes_PFR_LOCATIONS_and_TORS') {
                ?><input type="submit" class="btn_blue" name="sendChange" value="Привести PFR_LOCATION в соответствие с ТОРС" disabled/><?php
                if (isset($_GET['service']) and $_GET['service'] == 'PFR_ASTP_INTEGRATION_MITS') {
                    ?><input type="submit" class="btn_blue" name="sendChange"
                             value="<?php echo $diff_only ? 'Показать все записи' : 'Показать только различия' ?>" /><?php ;
                }
            }
            else {
                ?><input type="submit" class="btn_blue" name="sendChange" value="Удалить отмеченное" /><?php  ;
            }
         ?></form><?php
    }

    // database connections close
    db2_close($connection_TBSM);

	// --------------------------------------------------------------------------------------------------------------------------------------------------
	
	function duplicate($field1, $field2) {
		
		$conn = $GLOBALS['connection_TBSM'];
		$node = $GLOBALS['templ'];
		$pfr_locations_field = $GLOBALS['pfr_locations_field'];
		
		$sel = "SELECT distinct a1.ID, a1.PFR_FO, a1.PFR_ID_FO, a1.PFR_TORG, a1.PFR_ID_TORG, a1.PFR_NAZN, a1.NODE, a1.PFR_OBJECT, a1.PFR_OBJECTSERVER, a1.SUBCATEGORY, a1.AGENT_NODE, a1.SITFILTER, a1.PFR_KE_TORS, a1.SERVICE_NAME
				FROM DB2INST1.PFR_LOCATIONS a1, DB2INST1.PFR_LOCATIONS a2
				WHERE a1.ID <> a2.ID and a1.NODE like '$node' and a1.$field1 = a2.$field1 and a1.$field2 = a2.$field2
				ORDER BY a1.$field1, a1.$field2, a1.ID ASC";
		$stmt = db2_prepare($conn, $sel);
		$result = db2_execute($stmt);		

		// web page output							
		$row_count = 0;
		$FIELD1 = '';
		$FIELD2 = '';
		$bg_color = "LavenderBlush";
		
		echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
			echo "<tr>";
				echo "<th><image src=\"images/delete.png\"></th>";
				foreach ($pfr_locations_field as $item => $val)
					echo "<th>".(($item == $field1 or $item == $field2)?"<i>":"").$item.(($item == $field1 or $item == $field2)?"</i>":"")."</th>";
			echo "</tr>";
		
			while ($row = db2_fetch_assoc($stmt)) {
				// new duplicates group
				if ($row[$field1] != $FIELD1 or $row[$field2] != $FIELD2) {
					$FIELD1 = $row[$field1];
					$FIELD2 = $row[$field2];
					$row_count = $row_count + 1;
					$bg_color = ($bg_color == "Lavender" ? "WhiteSmoke" : "Lavender");
					$new_block = true;
				}	
				else 
					$new_block = false;
				
				// table output
				echo "<tr>";
					echo "<td bgcolor=\"".$bg_color."\">";
						?> <input type="checkbox" name="chkbx[<?php echo $row['ID']; ?>]"/> <?php
					echo "</td>";
					foreach ($pfr_locations_field as $item => $val) {
						$difference = (!$new_block and $item != 'ID' and $row[$item] != $pfr_locations_field[$item]);
						echo "<td bgcolor=\"".($difference?'LightPink':$bg_color)."\">".(($item == $field1 or $item == $field2)?"<i>":"").$row[$item].(($item == $field1 or $item == $field2)?"</i>":"")."</td>";
					}
				echo "</tr>";
				
				// save current string for compare on next step
				foreach ($pfr_locations_field as $item => $val)
					$pfr_locations_field[$item] = $row[$item];
			}
			// total number of records
			echo "<tr>";
				echo "<th colspan=0>";
					echo "Количество дубликатов: ".$row_count;
				echo "</th>";
			echo "</tr>";
		echo "</table>";
	}

    // --------------------------------------------------------------------------------------------------------------------------------------------------

	function three_in_one() {
		
		$conn = $GLOBALS['connection_TBSM'];
		$node = $GLOBALS['templ'];
		$pfr_locations_field = $GLOBALS['pfr_locations_field'];
		
		$sel = "SELECT distinct NODE, ID, PFR_ID_TORG, PFR_NAZN, PFR_OBJECT, PFR_OBJECTSERVER, SUBCATEGORY, AGENT_NODE, SITFILTER, PFR_KE_TORS, SERVICE_NAME, SERVICEINSTANCENAME, NODEL, REGION
				FROM DB2INST1.PFR_LOCATIONS
				FULL OUTER JOIN TBSMBASE.SERVICEINSTANCE
				ON SERVICE_NAME = SERVICEINSTANCENAME
				FULL OUTER JOIN DB2INST1.PFR_TEMS_TOBJACCL
				ON upper(NODE) = upper(NODEL) and (substr(PFR_OBJECTSERVER, 4) = REGION or (PFR_OBJECTSERVER = 'NCOMS' and REGION = '101'))
				WHERE (NODE IS NULL OR NODEL IS NULL) AND (NODE LIKE '$node' OR NODEL LIKE '$node')
				ORDER BY PFR_OBJECTSERVER, NODE, REGION, NODEL";
		$stmt = db2_prepare($conn, $sel);
		$result = db2_execute($stmt);		

		// web page output							
		$row_count = 0;
		echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
			echo "<tr>";
				echo "<th colspan=12>Таблица PFR_LOCATIONS</th>";
				echo "<th>TBSM</th>";
				echo "<th colspan=0>TEMS</th>";
			echo "</tr>";
			echo "<tr>";
				echo "<th><image src=\"images/delete.png\"></th>";
				foreach ($pfr_locations_field as $item => $val)
					echo "<th>".$item."</th>";
				echo "<th>Service Instance</th>";
				echo "<th>Node</th>";
				echo "<th>Region</th>";
			echo "</tr>";
		
			while ($row = db2_fetch_assoc($stmt)) {
				$row_count = $row_count + 1;
				echo "<tr>";
					echo "<td bgcolor=\"LavenderBlush\">";
						?> <input type="checkbox" name="chkbx[<?php echo $row['ID']; ?>]"/> <?php
					echo "</td>";
					foreach ($pfr_locations_field as $item => $val)
						echo "<td bgcolor=\"LavenderBlush\">".$row[$item]."</td>";
					echo "<td bgcolor=\"PaleTurquoise\">".$row['SERVICEINSTANCENAME']."</td>";
					echo "<td bgcolor=\"PaleGoldenRod\">".$row['NODEL']."</td>";
					echo "<td bgcolor=\"PaleGoldenRod\">".$row['REGION']."</td>";
				echo "</tr>";
			}
			// total number of records
			echo "<tr>";
				echo "<th colspan=0>";
					echo "Количество строк в выборке: ".$row_count;
				echo "</th>";
			echo "</tr>";
		echo "</table>";
	}

    // --------------------------------------------------------------------------------------------------------------------------------------------------

    function region_codes($diff_only) {

        $conn = $GLOBALS['connection_TBSM'];
        $conn_SCCD = $GLOBALS['connection_SCCD'];
        $service = $GLOBALS['service'];

        $level = 0;					// iteration level
        $path_history = [ ];		// array for history tree from parents to childs
        $results = [ ];				// array for endpoint childs
        $checked = false;

        // service name check
        $sel = "SELECT SERVICEINSTANCEID, DISPLAYNAME
				FROM TBSMBASE.SERVICEINSTANCE
				WHERE SERVICEINSTANCENAME = '$service'";
        $stmt = db2_prepare($conn, $sel);
        $result = db2_execute($stmt);
        $row = db2_fetch_assoc($stmt);
        if (empty($row)) {
            echo "Указанное имя (" . $service . ") не найдено в сервисном дереве мониторинга!";
            return;
        }

        // recursive function call to find all child services
        ext_tree($row['SERVICEINSTANCEID'], $conn, $level, $path_history);
        $services = array_unique(array_column($results, 'service'));
        sort($services);

        // services array fill
        foreach ($services as $value) {
            list($name, $displayname) = explode(' (', $value);
            $serv_arr[substr($displayname, 0, strlen($displayname) - 1)] = $name;
        }

        // KE search in PFR_LOCATION
        $sel = "SELECT ID, PFR_ID_TORG, PFR_TORG, NODE, PFR_KE_TORS, SERVICE_NAME
				FROM DB2INST1.PFR_LOCATIONS
				WHERE SERVICE_NAME in ('".implode("', '", $serv_arr)."')";
        $stmt = db2_prepare($conn, $sel);
        $result = db2_execute($stmt);

        // summary array fill
        while ($row = db2_fetch_assoc($stmt))
            $summ_arr [] = array (
                'ID' => $row['ID'],
                'PFR_ID_TORG' => $row['PFR_ID_TORG'],
                'PFR_TORG' => $row['PFR_TORG'],
                'NODE' => $row['NODE'],
                'PFR_KE_TORS' => $row['PFR_KE_TORS'],
                'SERVICE_NAME' => $row['SERVICE_NAME'],
                'DISPLAY_NAME' => array_search($row['SERVICE_NAME'], $serv_arr),
                'TORS_REG_CODE' => '',
                'TORS_REG_NAME' => '',
            );

        // unique KE
        $ke_arr = array_unique(array_column($summ_arr, 'PFR_KE_TORS'));

        // KE search in MAXIMO
        $sel_SCCD = "SELECT CI.CINAME, CI.ASSETLOCSITEID, SITE.DESCRIPTION
                    FROM MAXIMO.CI CI
                    LEFT JOIN MAXIMO.SITE SITE 
                    ON CI.ASSETLOCSITEID = SITE.SITEID
                    WHERE CI.CINAME IN ('".implode("', '", $ke_arr)."')";
        $stmt_SCCD = db2_prepare($conn_SCCD, $sel_SCCD);
        $result_SCCD = db2_execute($stmt_SCCD);

        // summary array extra fill
        while ($row_SCCD = db2_fetch_assoc($stmt_SCCD))
            foreach ($summ_arr as &$rec)
                if ($rec['PFR_KE_TORS'] == $row_SCCD['CINAME']) {
                    $rec['TORS_REG_CODE'] = $row_SCCD['ASSETLOCSITEID'];
                    $rec['TORS_REG_NAME'] = str_replace(chr(194).chr(160), chr(32), $row_SCCD['DESCRIPTION']);
                }

        // array sort
        foreach ($summ_arr as $key => $row)
            $col_KE[$key] = $row['PFR_KE_TORS'];
        array_multisort($col_KE, SORT_ASC, $summ_arr);

        // web page output
        $row_count = 0;
        echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
            echo "<tr>";
                echo "<th rowspan='2'></th>";
                echo "<th rowspan=2>KЭ</th>";
                echo "<th colspan=2>ТОРС</th>";
                echo "<th colspan=0>Мониторинг</th>";
            echo "</tr>";
            echo "<tr>";
                echo "<th>Код ОПФР</th>";
                echo "<th>Наименование</th>";
                echo "<th>PFR_ID_TORG</th>";
                echo "<th>PFR_TORG</th>";
                echo "<th>Узел</th>";
                echo "<th>Сервис</th>";
            echo "</tr>";

            foreach ($summ_arr as $rec) {
                if (!$diff_only or strcmp($rec['TORS_REG_CODE'], $rec['PFR_ID_TORG']) or strcmp($rec['TORS_REG_NAME'], $rec['PFR_TORG'])) {
                    $row_count = $row_count + 1;
                    echo "<tr>";
                        echo "<td>";
                            $checked = (!empty($rec['TORS_REG_CODE']) and (strcmp($rec['TORS_REG_CODE'], $rec['PFR_ID_TORG']) or strcmp($rec['TORS_REG_NAME'], $rec['PFR_TORG'])));
                            ?><input type="checkbox"
                                     name="chkbx[<?php echo $rec['ID']; ?>]" <?php echo $checked ? '' : 'disabled'; ?>/> <?php
                        echo "</td>";
                        echo "<td>" . $rec['PFR_KE_TORS'] . "</td>";
                        echo "<td>" . $rec['TORS_REG_CODE'] . "</td>";
                        echo "<td>" . $rec['TORS_REG_NAME'] . "</td>";
                        echo "<td " . (strcmp($rec['TORS_REG_CODE'], $rec['PFR_ID_TORG']) ? "class='red_message'" : '') . ">" . $rec['PFR_ID_TORG'] . "</td>";
                        echo "<td " . (strcmp($rec['TORS_REG_NAME'], $rec['PFR_TORG']) ? "class='red_message'" : '') . ">" . $rec['PFR_TORG'] . "</td>";
                        echo "<td>" . $rec['NODE'] . "</td>";
                        echo "<td>" . $rec['SERVICE_NAME'] . "</td>";
                    echo "</tr>";
                }
            }
            // total number of records
            echo "<tr>";
                echo "<th colspan=0>";
                    echo "Количество строк в выборке: ".$row_count;
                echo "</th>";
            echo "</tr>";
        echo "</table>";
    }

    ?>
</body>
</html>
