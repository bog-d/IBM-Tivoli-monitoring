<?php
/*
	by GDV
	2016 - RedSys
*/ 
	header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
	<link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Поиск элемента в сервисном дереве мониторинга</title>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    require_once 'connections/WHFED.php';

	$level = 1;				// iteration level
	$results = 0;			// number of results found
	$path_history = [ ];	// array for history tree from children to parents

    $form_show = true;
	$pfrf_only = true;
	$in_PFRF = false;
	
	// selection elements
	$sv = "SERVICEINSTANCENAME";
	$eq = " = ";
	$ad = "";
	$sort = "";
	$f_s = "";
	$f_e = "";
	
	$NODEID = '';
	$KE = '';
	
	// connection to TBSM database
    if (!$connection_TBSM)
        exit("Database TBSM connection failed.");

    // set cookie if search button was pressed
    if (isset($_POST['sendRequest']) and $_POST['sendRequest'] == 'Найти') {
        setcookie('last_search', $_POST['ObjectName'], time() + 60 * 60 * 24 * 365);
        setcookie('container', $_POST['container'], time() + 60 * 60 * 24 * 365);
        setcookie('object', $_POST['object'], time() + 60 * 60 * 24 * 365);
        setcookie('search', $_POST['search'], time() + 60 * 60 * 24 * 365);
        setcookie('registry', $_POST['registry'], time() + 60 * 60 * 24 * 365);
    }

    // top header
    echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"10\" class=\"page_title\">";
        echo "<tr>";
            echo "<td width=\"20%\" align=\"left\">";
            echo "</td>";
            echo "<td align=\"center\">";
                echo "<h3>Поиск элемента в сервисном дереве мониторинга</h3>";
            echo "</td>";
            echo "<td width=\"25%\" align=\"right\">";
            echo "</td>";
        echo "</tr>";
    echo "</table>";

    ?> <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="formId"> <?php

    // web form button was pressed or script was called with parameters
    if (isset($_POST['sendRequest']) or isset($_GET['quick'])) {
        // script was called with parameters
        if (isset($_GET['quick'])) {
            $pfrf_only = false;
            $NODEID = $_GET['quick'];
        }

        // exact string search
        if (isset($_POST['sendRequest']) and $_POST['sendRequest'] == 'Найти') {
            // container for search
            $pfrf_only = ($_POST['container'] == 'pfrf');

            // ',' to '.' replace and trim
            $_POST["ObjectName"] = strtr($_POST["ObjectName"], ",", ".");
            $_POST["ObjectName"] = trim ($_POST["ObjectName"]);

            if ($_POST['object'] == 'ip') {                        // IP-address resolve
                $NODEID = ip2server($_POST["ObjectName"]);
                if (!empty($NODEID))
                    echo "IP-адресу " . $_POST["ObjectName"] . " соответствует сервер " . $NODEID . " (по данным агента).<br><br>";
            }
            else if ($_POST['object'] == 'ke')                    // KE
                $KE = $_POST["ObjectName"];
            else {                                                // Service Name or Display Name
                $NODEID = $_POST["ObjectName"];
                $ip = server2ip($NODEID);
            }
        } // list choice in case of substring search
        else if (!isset($_GET['quick'])) {
            $NODEID = $_POST["ListItem"];
            $pfrf_only = ($_COOKIE["container"] == 'pfrf');
        }

        // selection elements change for special search options
        if (isset($_POST['sendRequest']) and $_POST['sendRequest'] == 'Найти') {
            if ($_POST['object'] == 'display') {
                $sv = "DISPLAYNAME";
            }
            if ($_POST['search'] == 'sub' and $_POST['object'] != 'ip') {
                $eq = " like ";
                $ad = "%";
                $sort = " ORDER BY " . $sv . " ASC";
            }
            if ($_POST['registry'] == 'no') {
                $f_s = "upper(";
                $f_e = ")";
            }
        }

        // TBSM tables selection
        if (empty($KE)) {
            $sel = "SELECT SERVICEINSTANCEID, SERVICEINSTANCENAME, DISPLAYNAME FROM TBSMBASE.SERVICEINSTANCE WHERE " . $f_s . $sv . $f_e . $eq . $f_s . "'" . $ad . $NODEID . $ad . "'" . $f_e . $sort;
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
        } else {
            $sel = "SELECT SERVICEINSTANCEID, SERVICEINSTANCENAME, DISPLAYNAME FROM TBSMBASE.SERVICEINSTANCE WHERE upper(SERVICEINSTANCENAME) in 
                    (SELECT DISTINCT upper(SERVICE_NAME) FROM DB2INST1.PFR_LOCATIONS WHERE " . $f_s . "PFR_KE_TORS" . $f_e . $eq . $f_s . "'" . $ad . $KE . $ad . "'" . $f_e . ") " . $sort;
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
        }

        // additional choice show for substring search option
        if (isset($_POST['sendRequest']) and $_POST['sendRequest'] == 'Найти' and (($_POST['search'] == 'sub' and $_POST['object'] != 'ip') or $_POST['object'] == 'ke')) {
            echo "<u>Выберите один из найденных элементов, удовлетворяющих критериям поиска</u>: <br \><br \>";
            $count = 0;
            while ($row = db2_fetch_assoc($stmt)) {
                // check is element in PFRF
                $in_PFRF = false;
                in_case($row['SERVICEINSTANCEID']);
                if ($in_PFRF or !$pfrf_only) {
                    echo "<label><input type='radio' name='ListItem' value='{$row['SERVICEINSTANCENAME']}' required >{$row['DISPLAYNAME']} ({$row['SERVICEINSTANCENAME']})</label><br>";
                    $count++;
                }
            }
            if ($count == 0)
                echo "В сервисном дереве мониторинга по данному запросу ничего не найдено.";
            else {
                $form_show = false;
                echo "<br>Всего найдено вариантов: ".$count."<br><br>";
                echo "<input type='submit' name='sendRequest' value='Назад' onclick='javascript:history.back()'>&emsp;";
                echo "<input type='submit' name='sendRequest' value='Далее'>";
            }
        }
        // search results show
        else {
            if ($row = db2_fetch_assoc($stmt)) {
                echo "<br>Размещение элемента <a href=\"SCCD_trigger.php?ServiceName=".$row['SERVICEINSTANCENAME']."&DisplayName=".$row['DISPLAYNAME']."\" target=\"_blank\" title=\"Открыть 'Настройки интеграции с СТП'\"><b>".$row['DISPLAYNAME']."</b> (<b>".$row['SERVICEINSTANCENAME']."</b>)</a> в ".($pfrf_only ? "ПФ РФ:" : "сервисном дереве:")."<hr><br \>";
                $path_history[0] = $row['DISPLAYNAME']." (".$row['SERVICEINSTANCENAME'].")";
                // recursive function call
                tree($row['SERVICEINSTANCEID'], $row['SERVICEINSTANCENAME'], $row['DISPLAYNAME'], $level, $path_history);
                echo "Всего найдено вариантов: {$results}<br>";
                // IP address(es) show
                echo empty($ip) ? "" : "IP-адрес(а): ".implode(', ', $ip)."<br>";
            }
            else
                echo "В сервисном дереве мониторинга по данному запросу ничего не найдено.";
        }
    }

    // main search form show
    if ($form_show) {
        // initial radiobutton values
        $init_container = isset($_COOKIE["container"]) ? $_COOKIE["container"] : 'pfrf';
        $init_object = isset($_COOKIE["object"]) ? $_COOKIE["object"] : 'display';
        $init_search = isset($_COOKIE["search"]) ? $_COOKIE["search"] : 'sub';
        $init_registry = isset($_COOKIE["registry"]) ? $_COOKIE["registry"] : 'no';

        ?>
        <br \><br \><br \>
        <table align="center" border="1">
            <tr>
                <th colspan=2 align="center">
                    Объект и критерии поиска
                </th>
            </tr>
            <tr>
                <td>
                    <table cellspacing="10">
                        <tr>
                            <td align="center" colspan="4">
                                &nbsp;&nbsp;&nbsp;&nbsp;<br>
                                Что искать: &nbsp;&nbsp;
                                <input type="text" name="ObjectName" maxlength="256" size="80" required autofocus="true" value="<?php echo isset($_POST['ObjectName']) ? $_POST['ObjectName'] : (isset($_COOKIE["last_search"]) ? $_COOKIE["last_search"] : ''); ?>">
                                &nbsp;&nbsp;
                                <input type="submit" class="btn" name="sendRequest" value="Найти">
                                &nbsp;&nbsp;&nbsp;&nbsp;<br><br>
                            </td>
                        </tr>
                        <tr>
                            <td align="center" colspan="4">
                                <b>К Р И Т Е Р И И&nbsp;&nbsp;&nbsp;&nbsp;П О И С К А</b>
                            </td>
                        </tr>
                        <tr>
                            <td width="25%">
                                &nbsp;&nbsp;<u>Контейнер</u>:
                            </td>
                            <td width="25%">
                                &nbsp;&nbsp;<u>Объект поиска</u>:
                            </td>
                            <td width="25%">
                                &nbsp;&nbsp;<u>Совпадение</u>:
                            </td>
                            <td width="25%">
                                &nbsp;&nbsp;<u>Регистр букв</u>:
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="radio" name="container" value="pfrf" <?php echo $init_container == "pfrf" ? 'checked' : ''; ?>/>&nbsp;ПФ РФ</label>
                            </td>
                            <td>
                                <label><input type="radio" name="object" value="service" <?php echo $init_object == "service" ? 'checked' : ''; ?>/>&nbsp;имя услуги</label>
                            </td>
                            <td>
                                <label><input type="radio" name="search" value="full" <?php echo $init_search == "full" ? 'checked' : ''; ?>/>&nbsp;точное</label>
                            </td>
                            <td>
                                <label><input type="radio" name="registry" value="no" <?php echo $init_registry == "no" ? 'checked' : ''; ?>/>&nbsp;без учёта регистра</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label><input type="radio" name="container" value="all" <?php echo $init_container == "all" ? 'checked' : ''; ?>/>&nbsp;всё дерево</label>
                            </td>
                            <td>
                                <label><input type="radio" name="object" value="display" <?php echo $init_object == "display" ? 'checked' : ''; ?> />&nbsp;выводимое имя</label>
                            </td>
                            <td>
                                <label><input type="radio" name="search" value="sub" <?php echo $init_search == "sub" ? 'checked' : ''; ?> />&nbsp;подстрока</label>
                            </td>
                            <td>
                                <label><input type="radio" name="registry" value="yes" <?php echo $init_registry == "yes" ? 'checked' : ''; ?>/>&nbsp;с учётом регистра</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <td>
                                <label><input type="radio" name="object" value="ip" <?php echo $init_object == "ip" ? 'checked' : ''; ?>/>&nbsp;IP-адрес сервера*</label>
                            </td>
                        </tr>
                        <tr>
                            <td>
                            </td>
                            <td>
                                <label><input type="radio" name="object" value="ke" <?php echo $init_object == "ke" ? 'checked' : ''; ?>/>&nbsp;КЭ**</label>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" align="left">
                                * <font size="smaller">результат не гарантирован, т.к.соответствие IP => Service Name определяется на основании имеющихся данных от агентов ОС</font>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" align="left">
                                ** <font size="smaller">результат зависит от корректности данных в таблице PFR_LOCATIONS</font>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <?php
    }

    ?></form><?php

    // database connection close
    db2_close($connection_TBSM);

	// ---------------------------------------------------------------------------------------------------------------- //
	
	function tree($child_id, $child_name, $child_display, $lev, $path) {
		
		// rows selection from TBSM tables
		$sel = "SELECT SERVICEINSTANCEID, SERVICEINSTANCENAME, DISPLAYNAME
				FROM TBSMBASE.SERVICEINSTANCE, TBSMBASE.SERVICEINSTANCERELATIONSHIP
				WHERE CHILDINSTANCEKEY = '$child_id' AND SERVICEINSTANCEID = PARENTINSTANCEKEY";
		$stmt = db2_prepare($GLOBALS['connection_TBSM'], $sel);
		$result = db2_execute($stmt);
		
		$values = 0;
		while ($row = db2_fetch_assoc($stmt)) {
			$values++;
			$path[$lev] = $row['DISPLAYNAME']." (".$row['SERVICEINSTANCENAME'].")";
			// recursive function call
			tree($row['SERVICEINSTANCEID'], $row['SERVICEINSTANCENAME'], $row['DISPLAYNAME'], $lev+1, $path);
		}
		
		// root element reached
		if ( $values == 0) {
			$l = 0;
			$n = count($path);
			foreach (array_reverse($path) as $value) {
				if (++$l == 1) {								// root element from path array
					if ($value != "Пенсионный фонд Российской Федерации (00PFRF)" and $GLOBALS['pfrf_only']) {
						$l--;
						break;
					}
					else
						echo "1. ".$value."<br \>";
				}
				else {										// child element from path array
					echo $l.". ";
					for ($i=1; $i < $l; $i++)
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
					$green = !(strpos($value, "Зеленая зона") === FALSE);
					echo ($l == $n ? "<b>" : "").($green ? "<font color=\"Green\">" : "").$value.($green ? "</font>" : "").($l == $n ? "</b>" : "")."<br \>";
				}
			}
			if ($l !=0) {
				echo "<br \><hr><br \>";
				$GLOBALS['results']++;
			}
		}
		
		return;
	}
		
	// ---------------------------------------------------------------------------------------------------------------- //
	
	function in_case($id) {
		
		// rows selection from TBSM tables
		$sel = "SELECT SERVICEINSTANCEID
				FROM TBSMBASE.SERVICEINSTANCE, TBSMBASE.SERVICEINSTANCERELATIONSHIP
				WHERE CHILDINSTANCEKEY = '$id' AND SERVICEINSTANCEID = PARENTINSTANCEKEY";
		$stmt = db2_prepare($GLOBALS['connection_TBSM'], $sel);
		$result = db2_execute($stmt);
		
		$values = 0;
		while ($row = db2_fetch_assoc($stmt)) {
			$values++;
			// recursive function call
			in_case($row['SERVICEINSTANCEID']);
		}
		
		// root element reached
		if ( $values == 0 and $id == '144') 
			$GLOBALS['in_PFRF'] = true;
		
		return;
	}
	
	// ---------------------------------------------------------------------------------------------------------------- //
	
	function ip2server($ip) {
		// Linux servers
		$sel = "SELECT SYSTEM_NAME FROM DB2INST1.LINUX_IP_ADDRESS WHERE IP_ADDRESS = '$ip'";
		$stmt = db2_prepare($GLOBALS['connection_WHFED'], $sel);
		$result = db2_execute($stmt);
		$row = db2_fetch_assoc($stmt); 
		if (!empty($row['SYSTEM_NAME']))					
			return str_replace(':LZ', '', substr(strrchr($row['SYSTEM_NAME'], '*'), 1 ));
		
		// Windows servers
		$sel = "SELECT SERVER_NAME FROM DB2INST1.NT_SYSTEM WHERE NETWORK_ADDRESS = '$ip'";
		$stmt = db2_prepare($GLOBALS['connection_WHFED'], $sel);
		$result = db2_execute($stmt);
		$row = db2_fetch_assoc($stmt); 
		if (!empty($row['SERVER_NAME']))					
			return str_replace(':NT', '', str_replace('Primary:', '', substr(strrchr($row['SERVER_NAME'], '*'), 1 )));
	
		// i5/OS servers
		$sel = "SELECT ORIGINNODE FROM DB2INST1.I5OS_TCPIP_HOST WHERE INTERNET_ADDRESS = '$ip'";
		$stmt = db2_prepare($GLOBALS['connection_WHFED'], $sel);
		$result = db2_execute($stmt);
		$row = db2_fetch_assoc($stmt); 
		if (!empty($row['ORIGINNODE']))					
			return str_replace(':KA4', '', substr(strrchr($row['ORIGINNODE'], '*'), 1 ));

		return '';
	}

    // ---------------------------------------------------------------------------------------------------------------- //

    function server2ip($server) {
        $res_arr = array();

        // Linux servers
        $sel = "SELECT distinct IP_ADDRESS FROM DB2INST1.LINUX_IP_ADDRESS WHERE IP_ADDRESS <> '127.0.0.1' AND IP_ADDRESS NOT LIKE '%::%' AND upper(SYSTEM_NAME) like upper('*___*{$server}:LZ') order by IP_ADDRESS asc";
        $stmt = db2_prepare($GLOBALS['connection_WHFED'], $sel);
        $result = db2_execute($stmt);
        while ($row = db2_fetch_assoc($stmt))
            $res_arr[] = $row['IP_ADDRESS'];
        if (!empty($res_arr))
            return $res_arr;

        // Windows servers
        $sel = "SELECT distinct NETWORK_ADDRESS FROM DB2INST1.NT_SYSTEM WHERE NETWORK_ADDRESS <> '127.0.0.1' AND NETWORK_ADDRESS NOT LIKE '%::%' AND upper(SERVER_NAME) like upper('*___*Primary:{$server}:NT') order by NETWORK_ADDRESS asc";
        $stmt = db2_prepare($GLOBALS['connection_WHFED'], $sel);
        $result = db2_execute($stmt);
        while ($row = db2_fetch_assoc($stmt))
            $res_arr[] = $row['NETWORK_ADDRESS'];
        if (!empty($res_arr))
            return $res_arr;

        // i5/OS servers
        $sel = "SELECT distinct INTERNET_ADDRESS FROM DB2INST1.I5OS_TCPIP_HOST WHERE INTERNET_ADDRESS <> '127.0.0.1' AND INTERNET_ADDRESS NOT LIKE '%::%' AND upper(ORIGINNODE) like upper('*___*{$server}:KA4') order by INTERNET_ADDRESS asc";
        $stmt = db2_prepare($GLOBALS['connection_WHFED'], $sel);
        $result = db2_execute($stmt);
        while ($row = db2_fetch_assoc($stmt))
            $res_arr[] = $row['INTERNET_ADDRESS'];
        if (!empty($res_arr))
            return $res_arr;

        return $res_arr;
    }

    ?>
</body>
</html>




