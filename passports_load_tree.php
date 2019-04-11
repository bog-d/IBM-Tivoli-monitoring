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
        <title>Паспорта и Протоколы мониторинга в дереве TBSM</title>
        <script src="scripts/jquery-3.2.1.min.js"></script>
        <script src="scripts/common.js"></script>
        <script src="scripts/passports.js"></script>
    </head>
<body>
<?php
require_once 'connections/TBSM.php';
include 'functions/user_roles.php';
include 'functions/tbsm.php';
include 'functions/regions.php';

$level = 0;					// iteration level
$path_info = [ ];		    // array for history tree from parents to childs
$results = [ ];				// array for endpoint childs

// connection to TBSM database
if (!$connection_TBSM)
    exit("Database TBSM connection failed.");

// user access codes load from file and check
$acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
if(!empty($acs))
    list($acs_user, $acs_role) = explode(';', $acs);
$acs_form = ($acs_role == 'admin' or $acs_role == 'user');

// top header
$title = "Паспорта и Протоколы мониторинга в дереве TBSM";
$links = array ("");
require 'functions/header_1.php';

// top header informational message output
$service = isset($_POST['service_name']) ? $_POST['service_name'] : 'MIC-PUIT';
$sel = "SELECT SERVICEINSTANCEID, DISPLAYNAME FROM TBSMBASE.SERVICEINSTANCE where SERVICEINSTANCENAME = '$service'";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);
$row = db2_fetch_assoc($stmt);
if (empty($row))
    $output = "Заданный контейнер не найден в сервисном дереве мониторинга!";
else {
    $service_id = $row['SERVICEINSTANCEID'];
    $service_displayname = $row['DISPLAYNAME'];
}
require 'functions/header_2.php';

?>
    <br>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <label>В списке приведены все дочерние сервисы с шаблоном <i>PFR_SERVICE</i> для контейнера  <b><?php echo empty($service_displayname) ? '' : $service_displayname; ?></b> : </label>
        <input type="text" name="service_name" size="40" value="<?php echo $service; ?>">
        <input type="submit" name="service_change" value="Изменить">
    </form>
    <br>
    Узлы дерева с имеющимися Паспортами представлены в виде гиперссылок, по которым можно перейти в форму "Настройка интеграции с СТП" для просмотра всех версий документов.<br>
    Здесь приведены последние имеющиеся версии Паспорта и Протокола.<br><br>
<?php

if (empty($row))
    exit();

// service tree and passports
echo "<table>";
    echo "<tr>";
        echo "<th>Сервисное дерево мониторинга</th>";
        echo "<th>&emsp;&emsp;&emsp;Паспорт</th>";
        echo "<th>&emsp;&emsp;&emsp;Протокол</th>";
        echo "</tr>";
    echo "<tr><td rowspan='0' valign='top'>";
            // root element passport presence
            $sel = "SELECT * FROM PFR_PASSPORT_SERVICES where SERVICE_NAME = '$service'";
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
            $row = db2_fetch_assoc($stmt);
            echo "<ul class='ul-treefree'><li>".(!empty($row) ? "<a href='http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName={$service}' target='_blank'>" : "")."<img src='images/service.png'>&nbsp;{$service_displayname} ({$service})".(!empty($row) ? "</a>" : "");

                    // recursive function call to find all child services of Green Zone
                    if (!ext_tree($service_id, $connection_TBSM, $level, $path_info))
                    exit('Отсутствуют дочерние элементы!');
                    $l = -1;

                    foreach ($results as $serv)
                    if ($serv['template'] == 'PFR_SERVICE:Standard') {
                    // passport presence
                    $sel = "select * from PFR_PASSPORT_SERVICES where SERVICE_NAME = '".$serv['service']."'";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);
                    $row = db2_fetch_assoc($stmt);

                    if ($serv['level'] > $l) {
                    $l++;
                    echo "<ul>";
                        }
                        else {
                        echo "</li>";
                        if ($serv['level'] < $l) {
                        echo "</li>";
                        for ($i = 0; $i < $l - $serv['level']; $i++)
                        echo "</ul></li>";
                $l = $serv['level'];
                }
                }
                echo "<li>".(!empty($row) ? "<a href='http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName=".$serv['service']."' target='_blank'>" : "")."<img src='images/service.png'>&nbsp;".$serv['display']." (".$serv['service'].")".(!empty($row) ? "</a>" : "");
                    }
                    echo "</li>";
                for ($i = 0; $i < $l; $i++)
                echo "</ul></li>";
            echo "</ul></li></ul>";
            echo "</td></tr>";
    echo "<tr><td height='30px'>&nbsp;</td></tr>";

    foreach ($results as $serv)
    if ($serv['template'] == 'PFR_SERVICE:Standard') {
    // passport presence
    $sel = "select PASS_VERSION, PASS_DATE, PASS_FILE, PASS_SIGNED, PROC_DATE, PROC_FILE, PROC_SIGNED
    from PFR_PASSPORT_VERSIONS
    where FILE_CODE = (select FILE_CODE FROM PFR_PASSPORT_SERVICES where SERVICE_NAME = '".$serv['service']."')";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    // latest versions detect
    $max_pass = '';
    $max_proc = '';
    $str_pass = '&emsp;&emsp;&emsp;';
    $str_proc = '';
    while ($row = db2_fetch_assoc($stmt)) {
    if (strcmp($row['PASS_VERSION'], $max_pass) > 0) {
    $max_pass = $row['PASS_VERSION'];
    $str_pass = "&emsp;&emsp;&emsp;".$row['PASS_VERSION']." от ".$row['PASS_DATE'];
    }
    if (strcmp($row['PROC_DATE'], $max_proc) > 0) {
    $max_proc = $row['PROC_DATE'];
    $str_proc = "&emsp;&emsp;&emsp;".$row['PROC_DATE']." версии ".$row['PASS_VERSION']." от ".$row['PASS_DATE'];
    }
    }
    echo "<tr><td valign='top'>".$str_pass."</td><td valign='top'>".$str_proc."</td></tr>";
    }
    echo "</table>";
echo "<br><br><hr color='lightgray'>";

?>
</body>
</html>
