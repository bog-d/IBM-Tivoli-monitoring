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
    <title>Установка агента мониторинга ОС</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
	<script>
        function dialog1(name,ip,os,id,itm_ip) {
			alert("Вы выбрали установку агента ОС на следующий компьютер: \n\r"+
                "\n\r"+
                "   Имя компьютера: \t\t"+name+"\n\r"+
                "   IP-адрес: \t\t\t\t"+ip+"\n\r"+
                "   ОС: \t\t\t\t\t"+os+"\n\r"+
                "\n\r"+
                "Данные для установки агента средствами BigFix: \n\r"+
                "\n\r"+
                "   ID компьютера: \t\t\t"+id+"\n\r"+
                "   Сервер мониторинга: \t"+itm_ip+"\n\r"+
                "\n\r"+
                "На следующем шаге вы сможете подтвердить или отменить это действие...");

			return confirm("После нажатия кнопки 'OK' начнётся процесс установки агента в фоновом режиме. \n\r"+
                "В таблице соответствующая строка будет помечена красным цветом до перевыгрузки данных с TEMS. \n\r"+
                "\n\r"+
                "Запустить установку агента?");
		}

//        function dialog2(f) {
//            if (confirm("После нажатия кнопки 'OK' начнётся процесс установки агента в фоновом режиме. \n\r"+
//                    "В таблице соответствующая строка будет помечена красным цветом до перевыгрузки данных с TEMS. \n\r"+
//                    "\n\r"+
//                    "Запустить установку агента?"))
//                f.submit();
//        }
	</script>
</head>
<body>
	<?php

    require_once 'connections/TBSM.php';
	include 'functions/regions.php';
    include 'functions/remote_exec.php';
    include 'functions/user_roles.php';
    include 'functions/PFR_LOCATIONS_record_form.php';

    // action buttons
    define("ACTION_ADD",                    "Добавить запись");
    define("ACTION_EDIT",                   "Редактировать запись");

    $report_busy_file = 'SCCD_trigger.rep';     // report update busy by another user info file

	$arr_BigFix = array (
        'LAST_REPORT_TIME' => 'Дата и время отчёта',
        'RELAY' => 'Сервер BigFix',
        'COMPUTER_ID' => 'ID компьютера',
        'COMPUTER_NAME' => 'Имя компьютера',
        'IP_ADDRESS' => 'IP-адрес',
        'OS' => 'ОС',
						);

	$arr_OS = array (
                      array (   'OS' => 'выбрать...',   'like' => 'XYZ',    ),
                      array (   'OS' => 'Windows',      'like' => 'Win',    ),
                      array (   'OS' => 'Linux',        'like' => 'Linux',  ),
                      array (   'OS' => 'CentOS',       'like' => 'CentOS', ),
                      array (   'OS' => 'FreeBSD',      'like' => 'FreeBSD',),
                      array (   'OS' => 'ESXi',         'like' => 'ESXi',   ),
					);
					
	$arr_region = [];
    $output = '';

    // **************************************************************************************************************************************************

    // forms parameters
    $os = isset($_POST['paramSelect']) ? $_POST['OS'] : (isset($_GET['os']) ? $_GET['os'] : '');
    $reg = isset($_POST['paramSelect']) ? $_POST['region'] : (isset($_GET['reg']) ? $_GET['reg'] : '');

    // edit locations button was pressed
    if (isset($_POST['editRecord'])) {
        $PFR_LOCATIONS_fields['ID']['VALUE'] = $_POST['editRecord'];
        if (record_form("os={$os}&reg={$reg}", 'edit'))
            exit();
        else
            $output = 'Ошибка редактирования записи в PFR_LOCATIONS!';
    }
    if (isset($_POST['sendRequest']) and $_POST['sendRequest'] == 'save') {
        $PFR_LOCATIONS_fields['ID']['VALUE'] = $_POST['ID_hidden'];
        if (record_form("os={$os}&reg={$reg}", 'save'))
            $output = "Запись сохранена в PFR_LOCATIONS";
        else
            $output = "Ошибка сохранения записи в PFR_LOCATIONS";
    }

    // user access codes load from file and check
    $acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
    if(!empty($acs))
        list($acs_user, $acs_role) = explode(';', $acs);
    $acs_form = ($acs_role == 'admin' or $acs_role == 'user');
    if($acs_form)
        $output = ($os == 'выбрать...' or $reg == 'выбрать...') ? 'Выберите в списках требуемые ОС и номер региона...' : 'Установка агента запускается кнопкой <img src="images/arrowdown.png" height="16" width="16"> в строке с именем нужного компьютера...';

    // top header
    $title = "Установка агента мониторинга ОС";
    $links = array ("");
    require 'functions/header_1.php';

    // TEMS reload data button was pressed
    if (isset($_POST['TEMSreload'])) {
        // another user actions detect
        list($flag_busy, $user_busy, $time_busy, ) = explode(';', file_get_contents($report_busy_file));
        if ($flag_busy == '1') {
            $output = "Обновление данных для отчётов уже запущено пользователем ".$user_busy." в ".date('H:i', $time_busy).". Повторите действие позднее!";
        }
        else {
            echo "<p id='to_remove'>Выгружаются данные с TEMS. Пожалуйста, подождите... <img src=\"images/inprogress.gif\" hspace=10></p>";
            ob_flush();
            flush();
            ob_end_flush();

            file_put_contents($report_busy_file, "1;".$acs_user.";".time(), LOCK_EX);
            TEMS_data_reload(array($reg), false);
            file_put_contents($report_busy_file, "0;".$acs_user.";".time(), LOCK_EX);
            $output = 'Данные с TEMS обновлены.';
        }
    }

    // agent install button was pressed
    if (isset($_POST['installAgent'])) {
        $sel_id = $_POST['installAgent'];

        $xml_file = "BigFix.xml";
        $xml_query = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                    <BES xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:noNamespaceSchemaLocation=\"BES.xsd\">
                      <SourcedFixletAction>  
                        <SourceFixlet>    
                          <Sitename>ActionSite</Sitename>    
                            <FixletID>1954</FixletID>    
                            <Action>Action1</Action>  
                        </SourceFixlet>  
                        <Target>    
                            <ComputerID>".$sel_id."</ComputerID>
                        </Target>
                        <Parameter Name=\"TEMS_HOSTNAME\">".$array_HUBs[$reg]."</Parameter>
                    </SourcedFixletAction>
                    </BES>
                    ";
        file_put_contents($xml_file, $xml_query,LOCK_EX);
        // shell_exec("curl -s -X POST --data-binary @".$xml_file." --insecure --user monitoring:tivoli \"https://10.101.237.58:52315/api/actions\"");

        $sel = "update DB2INST1.PFR_BIGFIX_COMPUTERS set DEPLOY = 1 where COMPUTER_ID = '".$sel_id."'";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        $output = "Установка агента мониторинга ОС на компьютере (ID=".$sel_id.") запущена...";
    }

    // top header informational message output
    require 'functions/header_2.php';

    // regions array fill
	$sel = "SELECT distinct (case when REGION is null then 'n/a' else REGION end) as REGION
            FROM DB2INST1.PFR_BIGFIX_COMPUTERS
            LEFT JOIN DB2INST1.PFR_BIGFIX_RELAYS
            ON RELAY = IP OR RELAY = COMPUTER OR RELAY = COMPUTER_DOMAIN
            ORDER BY REGION ASC";
	$stmt = db2_prepare($connection_TBSM, $sel);
	$result = db2_execute($stmt);
	while ($row = db2_fetch_assoc($stmt))
		$arr_region[] = $row['REGION'];
		
	// form lists and buttons
	?>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>?os=<?php echo $os;?>&reg=<?php echo $reg;?>" method="post" id="form1">
        <table cellspacing="20" width="100%">
            <tr>
                <td align="left">
                    Регион: <select size = "1" name = "region" <?php echo $acs_form ? '' : 'disabled'; ?>>
                        <option value = "выбрать...">выбрать...</option> <?php
                        foreach ($arr_region as $value) {
                            ?><option value = <?php echo $value; ?> <?php echo $value == $reg ? 'selected' : ''; ?>><?php echo $value; ?></option><?php ;
                        }
                        ?> </select>
                    &emsp;&emsp;
                    ОС: <select size = "1" name = "OS" <?php echo $acs_form ? '' : 'disabled'; ?>> <?php
                    foreach (array_column($arr_OS, 'OS') as $value) {
                        ?><option value = <?php echo $value; ?> <?php echo $value == $os ? 'selected' : ''; ?>><?php echo $value; ?></option><?php ;
                    }
                    ?> </select>
					&emsp;&emsp;
		            <input type="submit" class="btn" name="paramSelect" value="Выбрать" <?php echo $acs_form ? '' : 'disabled'; ?>/>
                </td>
            </tr>
        </table>
    <?php
	
    // table output
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        // titles
        echo "<tr>";
            echo "<th colspan=".count($arr_BigFix).">BigFix</th>";
            if($os == 'Windows')
                echo "<th rowspan=2>Установить<br>агента</th>";
            echo "<th colspan=2>";
                ?> TEMS &nbsp; <input type="submit" class="btn_blue" class="btn" name="TEMSreload" value="Обновить данные" <?php echo ($acs_form and !empty($reg)) ? '' : 'disabled'; ?>/> <?php
            echo "</th>";
        echo "<th colspan=2>PFR_LOCATIONS</th>";
        echo "<th colspan=3>TBSM</th>";
        echo "</tr>";
        echo "<tr>";
            foreach($arr_BigFix as $param => $value)
                echo "<th>".$value."</th>";
            echo "<th>Узел мониторинга</th>";
            echo "<th>Регион</th>";
            echo "<th>Запись</th>";
            echo "<th>Правка</th>";
            echo "<th>Имя индикатора</th>";
            echo "<th>Шаблон</th>";
            echo "<th>Режим обслуживания</th>";
        echo "</tr>";

        ?> </form> <?php

        // sql query
        $arr_arr = array_filter($arr_OS, function($var) { return $var['OS'] == $GLOBALS['os']; });
        $arr = reset($arr_arr);
        $sel = "select * 
                from DB2INST1.PFR_BIGFIX_COMPUTERS 
                left join (SELECT distinct NODEL, REGION FROM DB2INST1.PFR_TEMS_TOBJACCL)
                on REGION = '".$reg."' and locate(concat(upper(COMPUTER_NAME), ':'), upper(NODEL)) > 0
                left join TBSMBASE.SERVICEINSTANCE
                on upper(COMPUTER_NAME) = upper(SERVICEINSTANCENAME)
                where OS like '%".$arr['like']."%' and ".
                    ($reg == 'n/a' ?
                      "(RELAY not in (select IP from DB2INST1.PFR_BIGFIX_RELAYS) and
                       RELAY not in (select COMPUTER from DB2INST1.PFR_BIGFIX_RELAYS) and
                       RELAY not in (select COMPUTER_DOMAIN from DB2INST1.PFR_BIGFIX_RELAYS))" :
                        "(RELAY in (select IP from DB2INST1.PFR_BIGFIX_RELAYS where REGION = '".$reg."') or
                        RELAY in (select COMPUTER from DB2INST1.PFR_BIGFIX_RELAYS where REGION = '".$reg."') or
                        RELAY in (select COMPUTER_DOMAIN from DB2INST1.PFR_BIGFIX_RELAYS where REGION = '".$reg."'))")
                ." order by COMPUTER_NAME asc";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);

        // strings
        ?> <form action="<?php echo $_SERVER['PHP_SELF'];?>?os=<?php echo $os;?>&reg=<?php echo $reg;?>" method="post" id="form2"> <?php
        //        onsubmit="dialog2('form2');return false;"
        $i = 0;
        $prevID = '';
        $bg_color = "LavenderBlush";
        while ($row = db2_fetch_assoc($stmt))  {
            // mark strings with agent installation already started and NODEL is still empty
            $sel_str = (!empty($row['NODEL']) or empty($row['DEPLOY'])) ? "" : " class=\"red_message\" ";
            if ($row['COMPUTER_ID'] != $prevID)
                $bg_color = ($bg_color == "Lavender" ? "WhiteSmoke" : "Lavender");

            echo "<tr>";
                foreach($arr_BigFix as $param => $value) {
                    echo "<td bgcolor='".$bg_color."'".$sel_str.">";
                        switch($param) {
                            case 'LAST_REPORT_TIME':
                                echo substr($row[$param], 4, 18);
                                break;
                            case 'RELAY':
                                echo str_replace('.ADM.PFR.RU', '', str_replace(':52311', '', $row[$param]));
                                break;
                            default:
                                echo $row[$param];
                                break;
                        }
                    echo "</td>";
                }

                // agent install button
                if($os == 'Windows') {
                    echo "<td bgcolor='#E5E0E0' align='center'>";
                    if (empty($row['NODEL'])) {
                        ?>
                        <button type="submit" name="installAgent" value="<?php echo $row['COMPUTER_ID']; ?>"
                                title="Установить агента ОС на этот компьютер"
                                onclick="return dialog1(<?php echo "'" . $row['COMPUTER_NAME'] . "','" . $row['IP_ADDRESS'] . "','" . $row['OS'] . "','" . $row['COMPUTER_ID'] . "','" . $array_HUBs[$reg] . "'"; ?>)" <?php echo ($acs_form and empty($row['DEPLOY'])) ? '' : 'disabled'; ?> >
                            <img src="images/arrowdown.png" height="16" width="16"></button><?php ;
                    }
                    echo "</td>";
                }

                echo "<td bgcolor='".$bg_color."'" .$sel_str.">".(empty($sel_str) ? $row['NODEL'] : "Установка агента запущена...")."</td>";
                echo "<td bgcolor='".$bg_color."'" .$sel_str.">".$row['REGION']."</td>";
                echo "<td bgcolor='".$bg_color."'" .$sel_str.">";
                    if (!empty($row['NODEL'])) {
                        list($id, $status) = explode('*', record_check($connection_TBSM, $row['NODEL'], $row['REGION'], $row['IP_ADDRESS'], $row['SERVICEINSTANCENAME']));
                        switch ($status) {
                            case CHECK_ABSENT:
                                echo "<img src='images/error.png' hspace='5' align='bottom'>Нет";
                                break;
                            case CHECK_DUPLICATE:
                                echo "<img src='images/copy.png' hspace='5' align='bottom'>Дубликаты";
                                break;
                            case 0:
                                echo "<img src='images/ok.png' hspace='5' align='bottom'>ОК";
                                break;
                            default:
                                $warnings = $status;
                                $warn_array = [];
                                $i = 1;
                                while ($warnings) {
                                    if (!empty($warnings & 1)) {
                                        $a = get_defined_constants(true);
                                        $warn_array [] = array_search($i, $a['user']);
                                    }
                                    $warnings = $warnings >> 1;
                                    $i *= 2;
                                }
                                echo "<img src='images/warning.png' hspace='5' align='bottom' title='".implode("<br>", $warn_array)."'>Ошибка";
                                break;
                        }
                    }
                echo "</td>";
                // PFR_LOCATIONS actions buttons
                echo "<td bgcolor='#E5E0E0' align='center'>";
                    if (!empty($row['NODEL'])) {
                        if ($status >= 0) {
                            ?>
                            <button type="submit" name="editRecord"
                                    value="<?php echo $id; ?>"
                                    title="<?php echo ACTION_EDIT; ?>""><img src="images/edit.png"></button><?php ;
                        }
                    }
                echo "</td>";
                echo "<td bgcolor='".$bg_color."'" . $sel_str . ">".$row['SERVICEINSTANCENAME']."</td>";
                echo "<td bgcolor='".$bg_color."'" . $sel_str . ">".str_replace(':Standard', '', $row['SERVICESLANAME'])."</td>";
                echo "<td bgcolor='".$bg_color."'" . $sel_str . ">".($row['TIMEWINDOWNAME'] == 'unprepared' ? 'включён' : '')."</td>";

                $prevID = $row['COMPUTER_ID'];
            echo "</tr>";
            $i++;
        }

        ?> </form> <?php

        // total
        echo "<tr>";
            echo "<th colspan=0>Всего строк: ".$i."</th>";
        echo "</tr>";
    echo "</table><br><br>";

    // database connections close
    db2_close($connection_TBSM);

    ?>
</body>
</html>