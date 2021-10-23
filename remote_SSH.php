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
    <link type="text/css" href="scripts/jquery-ui-1.12.1/jquery-ui.css" rel="stylesheet" />
    <title>Доступность объекта с сервера мониторинга</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/jquery-ui-1.12.1/jquery-ui.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/remote_SSH.js"></script>
</head>
<body>
<?php
require_once 'connections/TBSM.php';
include 'functions/user_roles.php';
include 'functions/regions.php';

// monitoring servers array
$servers = array (
);

// get script parameters
$NODEID = isset($_GET["ServiceName"]) ? $_GET["ServiceName"] : '';
$FROM = isset($_POST['server']) ? ($_POST['server'] == 'RTEMS' ? $_POST['rtems'] : $_POST['server']) : "";
$REGION = isset($_POST['region']) ? $_POST['region'] : "";
$RTEMS = isset($_POST['rtems']) ? $_POST['rtems'] : "";
$PING = isset($_POST['ping']) ? $_POST['ping'] : "4";
$TRACEROUTE = isset($_POST['traceroute']) ? $_POST['traceroute'] : "10";

// connection methods array
$methods = array (
    "ping"        => "ping -c {$PING} ",
    "traceroute"  => "traceroute -m {$TRACEROUTE} ",
    "nmap"        => "nmap -p ",
    "curl"        => "curl ",
    "nslookup"    => "nslookup ",
);

$auto_IP = false;
$auto_from = false;

$log_file = 'logs/remote_SSH.log'; // log file

// top header
$title = "Доступность объекта с сервера мониторинга";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
require 'functions/header_2.php';

// connection to TBSM database
if (!$connection_TBSM)
    exit("Database TBSM connection failed.");

// target detect
if (isset($_POST['formPing']['sendRequest'])) {
    $target = $_POST['target'];
    $auto_IP = false;
}
else {
    $sel = "SELECT URL FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME = '$NODEID' AND URL <> '' AND URL IS NOT NULL";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    $row = db2_fetch_assoc($stmt);
    $target = empty($row['URL']) ? $NODEID : $row['URL'];
    $auto_IP = (empty($row['URL']) or $row['URL'] == $NODEID) ? false : true;
}

// ping button was pressed
if (isset($_POST['formPing']['sendRequest'])) {
    // command generation
    if ($_POST['method'] == 'nmap')
        $command = strpos($target, ':') === FALSE ? "" : $methods[$_POST['method']] . substr(strrchr($target, ':'), 1) . ' ' . substr($target, 0, strrpos($target, ':'));
    else
        $command = $methods[$_POST['method']] . $target;
}

// monitoring server detect
$sel = "SELECT THRUNODE FROM PFR_TEMS_INODESTS WHERE NODE =
                  (SELECT NODE FROM PFR_LOCATIONS WHERE SERVICE_NAME = '$target' AND NODE = 'N4:$target:nma')";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);
$row = db2_fetch_assoc($stmt);

// is monitoring server among $servers
if (!empty($row['THRUNODE'])) {
    $s = substr($row['THRUNODE'], 0, -3);
    if (array_key_exists($s, $servers)) {
        $FROM = $s;
        $auto_from = true;
    }
}
$FROM = empty($FROM) ? array_keys($servers)[0] : $FROM;

?>
<br \><br \>
<!-- web form -->
<form action="<?php echo $_SERVER['PHP_SELF'];?>?ServiceName=<?php echo $NODEID;?>" method="post" id="formPing">
    <table align="center" cellpadding="10" border="1">
        <tr>
            <th colspan=2 align="center">
                Выбор сервера мониторинга и объекта для проверки
            </th>
        </tr>
        <tr>
            <td>
                <table cellspacing="20">
                    <tr>
                        <td valign = "top">
                            Откуда:
                        </td>
                        <td>
                            <font size="-1" color="red"><?php echo $auto_from ? "объект ".$NODEID." мониторится с ".$FROM."<br>" : ""; ?></font>
                            <?php
                            echo "<table cellspacing='5'>";
                                $i = 0;
                                foreach ($servers as $serv => $param) {
                                    if (substr($serv, 0, 5) != 'RTEMS') {
                                        echo "<tr><td>";
                                        echo "<label><input type='radio' name='server' value='{$serv}' " . ($serv == $FROM ? 'checked' : '') . ">&nbsp;{$serv} ({$param['ip']})</label><br>";
                                        echo "</td></tr>";
                                    }
                                    else {
                                        if ($i++ == 0) {
                                            echo "<tr><td>";
                                            echo "<label><input type='radio' name='server' value='RTEMS' ".(substr($FROM, 0,5) == 'RTEMS' ? 'checked' : '').">&nbsp;RTEMS</label>&nbsp;";
                                            echo "<select size='1' id='rtems_list' name='rtems'>";
                                        }
                                        echo "<option value ='{$serv}' ".($serv == $RTEMS ? 'selected' : '').">".substr($serv, 5)." * {$param['location']}</option>";
                                    }
                                }
                                echo "</select></td></tr>";
                                $i = 0;
                                foreach ($array_HUBs as $reg => $ip) {
                                    if ($reg == '101')
                                        continue;
                                    if ($i++ == 0) {
                                        echo "<tr><td>";
                                        echo "<label><input type='radio' name='server' value='ITM' " . ($FROM == 'ITM' ? 'checked' : '') . ">&nbsp;ITM</label>&nbsp;";
                                        echo "<select size='1' id='reg_list' name='region'>";
                                    }
                                    echo "<option value ='{$reg}' ".($reg == $REGION ? 'selected' : '').">{$array_regions[$reg]}</option>";
                                }
                                echo "</select></td></tr>";
                            echo "</table>";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td valign = "top">
                            Куда:
                        </td>
                        <td>
                            <!-- target choice memorization -->
                            &emsp;<input type="text" id="target" name="target"  size="40" maxlength="256" value="<?php echo $target; ?>" required>
                            &emsp;<font size="-1" color="red"><?php echo $auto_IP ? "<br>для объекта ".$NODEID." подставлен IP-адрес ".$target : ""; ?></font>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">
                            Как:
                        </td>
                        <td>
                            <?php
                            echo "<table cellspacing='5'><tr><td>";
                                echo "<tr><td>";
                                    foreach ($methods as $meth => $c) {
                                        // define active radiobutton
                                        if (isset($_POST['formPing']['sendRequest']))
                                            $active_radio = $_POST['method'];
                                        else if (substr($NODEID, 0, 4) == 'http')
                                            $active_radio = 'curl';
                                        else
                                            $active_radio = 'ping';
                                        echo "<label><input type='radio' name='method' value='{$meth}' ".($meth == $active_radio ? 'checked' : '').">{$meth}</label>&nbsp;";
                                    }
                                echo "</td></tr>";
                                echo "<tr><td id='ping_block'>";
                                    ?>
                                    <div id="slider"></div>
                                    количество пакетов: <span id="contentSlider_show"></span>
                                    <input type="text" id='contentSlider_save' name="ping" value="4" hidden>
                                    <?php
                                echo "</td></tr>";
                                echo "<tr><td id='traceroute_block'>";
                                    ?>
                                    <div id="slider_2"></div>
                                    максимальное количество узлов: <span id="contentSlider_show_2"></span>
                                    <input type="text" id='contentSlider_save_2' name="traceroute" value="10" hidden>
                                    <?php
                                echo "</td></tr>";
                            echo "</table>";
                            ?>
                            <font size="-1" color="red"><?php echo (isset($_POST['formPing']['sendRequest']) and empty($command)) ? "<br>для команды nmap требуется указать номер порта" : ""; ?></font>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2 align="center">
                            <input type="submit" class="btn" name="formPing[sendRequest]" value='Проверить доступность'/>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</form>
<br \><hr><br \>
<?php

// check access button was pressed
if (isset($_POST['formPing']['sendRequest']) and !empty($command)) {
    $from_server = ($_POST['server'] == 'ITM') ? "ITM{$_POST['region']}" : (($_POST['server'] == 'RTEMS') ? $_POST['rtems'] : $_POST['server']);
    $server_pass = ($_POST['server'] == 'ITM') ? (array_key_exists("ITM{$_POST['region']}", $array_exeptions) ? $array_exeptions["ITM{$_POST['region']}"]["root_password"] : '...') : $servers[$from_server]['pas'];

    // ssh connection to server
    $connection = ssh2_connect($_POST['server'] == 'RTEMS' ? 'tems-main' : $from_server, 22);
    if ($connection === false)
        echo "<p align='center' class='red_message'>Сервер <b>{$from_server}</b> недоступен.</p>";
    else {
        echo "<p align='center'>Результат выполнения команды <b>" . $command . "</b> с сервера <b>" . $from_server . "</b></p>";
        ssh2_auth_password($connection, 'root', $_POST['server'] == 'RTEMS' ? $servers['tems-main']['pas'] : $server_pass);

        // output stream and error stream
        $stream = ssh2_exec($connection, "date +\"%A %d.%m.%Y %T\"; ".($_POST['server'] == 'RTEMS' ? "ssh {$from_server} " : "").$command);
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

        // output and error streams blocking
        stream_set_blocking($errorStream, true);
        stream_set_blocking($stream, true);

        echo "<table align=\"center\" cellpadding=\"10\" border=\"1\">";
        echo "<tr>";
        echo "<td>";
        echo "<table align=\"center\" cellspacing=\"5\" border=\"0\"  class=\"black\">";
        echo "<tr>";
        echo "<td>";                    // title
        echo "<span class=\"green_on_black\"><b>root@" . $from_server . ":[/root]#&emsp;" . $command . "</b></span><br>";
        echo "</td>";
        echo "</tr>";
        while ($line = fgets($stream)) {        // output stream
            flush();
            echo "<tr>";
            echo "<td>";
            echo "&nbsp;&nbsp;&nbsp;<span class=\"green_on_black\">" . $line . "</span>&nbsp;&nbsp;&nbsp;";
            echo "</td>";
            echo "</tr>";
        }
        while ($line = fgets($errorStream)) {    // error stream
            flush();
            echo "<tr>";
            echo "<td>";
            echo "<span class=\"green_on_black\">" . $line . "</span>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</td>";
        echo "</tr>";
        echo "</table>";

        // output and error streams closing
        fclose($errorStream);
        fclose($stream);
    }

    // log output
    file_put_contents($log_file, date('d.m.Y H:i:s')."\t".$from_server."\t".$command."\t".$acs_user."\n", FILE_APPEND | LOCK_EX);
}

// database connection close
db2_close($connection_TBSM);

?>
</body>
</html>
