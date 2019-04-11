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
    <title>Проверка Паспортов и Протоколов мониторинга</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/passports.js"></script>
    <style type="text/css">
        a:link, a:visited {
            text-decoration: none;
            color: black;
        }
        #header {
            top: 10px;
            right: 20px;
            position: fixed;
            z-index: 9999;
        }
    </style>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    include 'functions/user_roles.php';
    include 'functions/tbsm.php';
    include 'functions/regions.php';

    $pass_targ_dir = '/usr/local/apache2/htdocs/pfr_other/Passports/';	        // passports target directory
    $output = ""; 						                                        // top header informational message
    $error = 0;                                                                 // number of errors found
    $files = [];                                                                // array of files in PASSPORT directory
    $marks = [];                                                                 // array of file's marks

    // ******************************************************************************************************************************

    // user access codes load from file and check
    $acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
    if(!empty($acs))
        list($acs_user, $acs_role) = explode(';', $acs);
    $acs_form = ($acs_role == 'admin' or $acs_role == 'user');

    // top header
    $title = "Проверка Паспортов и Протоколов мониторинга";
    $links = array ("");
    require 'functions/header_1.php';

    // top header informational message output
    require 'functions/header_2.php';

    // directory read
    $files = scandir($pass_targ_dir);
    foreach($files as $key => $file)
        if (is_dir($pass_targ_dir.$file) or strcmp($file, ".htaccess") === 0)
            unset($files[$key]);
    $marks = $files;

    // passport tables read from DB
    $sel = "select s.FILE_CODE as sFILE_CODE, v.FILE_CODE as vFILE_CODE, c.FILE_CODE as cFILE_CODE, 
                   s.SERVICE_NAME, 
                   v.PASS_VERSION, v.PASS_DATE, v.PASS_FILE, v.PROC_DATE, v.PROC_FILE, 
                   c.PTK, c.REGION, c.ENVIRONMENT
            from DB2INST1.PFR_PASSPORT_VERSIONS v
            full outer join DB2INST1.PFR_PASSPORT_SERVICES s
            on v.FILE_CODE = s.FILE_CODE
            full outer join DB2INST1.PFR_PASSPORT_CODING c
            on v.FILE_CODE = c.FILE_CODE
            order by s.FILE_CODE, SERVICE_NAME, PASS_VERSION asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    $CODE = '';
    $bg_color = "LavenderBlush";
    echo "<br><br><table width='100%' border='1' cellspacing='0' cellpadding='10'>";
        echo "<tr>";
            echo "<th rowspan='2'>Код</th>";
            echo "<th colspan='1'>PFR_PASSPORT_SERVICES</th>";
            echo "<th colspan='5'>PFR_PASSPORT_VERSIONS</th>";
            echo "<th colspan='3'>PFR_PASSPORT_CODING</th>";
            echo "<th rowspan='2'>Файловая система</th>";
        echo "</tr>";
        echo "<tr>";
            echo "<th>Сервис в TBSM</th>";
            echo "<th colspan='3'>Паспорт</th>";
            echo "<th colspan='2'>Протокол</th>";
            echo "<th colspan='3'>ПТК (для имени файла)</th>";
        echo "</tr>";
        while ($row = db2_fetch_assoc($stmt)) {
            if ($row['SFILE_CODE'] != $CODE) {
                $CODE = $row['SFILE_CODE'];
                $bg_color = ($bg_color == "Lavender" ? "WhiteSmoke" : "Lavender");
            }

            echo "<tr bgcolor='{$bg_color}'>";
                foreach ($row as $key => $value) {
                    switch ($key) {
                        case 'SFILE_CODE':
                            $sfile_code = $value;
                            break;
                        case 'VFILE_CODE':
                            $vfile_code = $value;
                            break;
                        case 'CFILE_CODE':
                            $cfile_code = $value;

                            if (empty($sfile_code) or empty($vfile_code) or empty($cfile_code)) {
                                echo "<td id='error' class='red_message'>";
                                $error++;
                            }
                            else
                                echo "<td>";
                            echo max($sfile_code, $vfile_code, $cfile_code);
                            if (empty($sfile_code))
                                echo "<br>ПУСТОЕ ПОЛЕ в PFR_PASSPORT_SERVICES";
                            if (empty($vfile_code))
                                echo "<br>ПУСТОЕ ПОЛЕ в PFR_PASSPORT_VERSIONS";
                            if (empty($cfile_code))
                                echo "<br>ПУСТОЕ ПОЛЕ в PFR_PASSPORT_CODING";

                            echo "</td>";
                            break;
                        case 'SERVICE_NAME':
                            $sel_tbsm = "select DISPLAYNAME from TBSMBASE.SERVICEINSTANCE where SERVICEINSTANCENAME = '$value'";
                            $stmt_tbsm = db2_prepare($connection_TBSM, $sel_tbsm);
                            $result_tbsm = db2_execute($stmt_tbsm);
                            $row_tbsm = db2_fetch_assoc($stmt_tbsm);

                            if (empty($value)) {
                                echo "<td id='error' class='red_message'>ПУСТОЕ ПОЛЕ</td>";
                                $error++;
                            }
                            else if (empty($row_tbsm['DISPLAYNAME']) and substr($value, 7) != 'PTK_PED') {
                                echo "<td id='error' class='red_message'>$value</td>";
                                $error++;
                            }
                            else
                                echo "<td><a href='#' title='{$row_tbsm['DISPLAYNAME']}'>$value</td></td>";
                            break;
                        case 'PASS_VERSION':
                            if (strlen($value) != 3) {
                                echo "<td id='error' class='red_message'>$value</td>";
                                $error++;
                            }
                            else
                                echo "<td>$value</td>";
                            break;
                        case 'PROC_DATE':
                            $pd = $value;
                            break;
                        case 'PROC_FILE':
                            if (!empty($pd) and !empty($value))
                                echo "<td>$pd</td><td>$value</td>";
                            else if (empty($pd) and empty($value))
                                echo "<td></td><td></td>";
                            else if (!empty($pd) and empty($value)) {
                                echo "<td>$pd</td><td id='error' class='red_message'>ПУСТОЕ ПОЛЕ</td>";
                                $error++;
                            }
                            else {
                                echo "<td id='error' class='red_message'>ПУСТОЕ ПОЛЕ</td><td>$value</td>";
                                $error++;
                            }
                                break;
                        default:
                            echo "<td>$value</td>";
                            break;
                    }
                }

                // files existance check
                $pass_name = "Паспорт_{$row['PTK']}_{$row['REGION']}_{$row['ENVIRONMENT']}_версия_{$row['PASS_VERSION']}_от_{$row['PASS_DATE']}.{$row['PASS_FILE']}";
                $proс_name = "Протокол_{$row['PTK']}_{$row['REGION']}_{$row['ENVIRONMENT']}_версия_{$row['PASS_VERSION']}_от_{$row['PROC_DATE']}.{$row['PROC_FILE']}";

                $err_pass = $err_proc = false;
                if (($key_pass = array_search($pass_name, $files)) === false)
                    $err_pass = true;
                if (!empty($row['PROC_DATE']))
                    if (($key_proc = array_search($proс_name, $files)) === false)
                        $err_proc = true;

                if ($err_pass or $err_proc)
                    echo "<td id='error' class='red_message'>";
                else
                    echo "<td>";

                if (!$err_pass) {
                    echo $pass_name;
                    $marks[$key_pass] = null;
                }
                else {
                    echo "ОТСУТСТВИЕ ФАЙЛА";
                    $error++;
                }

                if (!$err_proc) {
                    if (!empty($row['PROC_DATE'])) {
                        echo "<br>$proс_name";
                        $marks[$key_proc] = null;
                    }
                }
                else {
                    echo "<br>ОТСУТСТВИЕ ФАЙЛА";
                    $error++;
                }
                echo "</td>";
            echo "</tr>";
        }

        // outsider files in PASSPORT directory
        if (count(array_filter($marks)) > 0) {
            $error++;
            echo "<tr>";
                echo "<td colspan='10' valign='top' align='center'><b>Файлы, отсутствующие в таблицах:</b></td>";
                echo "<td id='error' class='red_message'>";
                    foreach ($marks as $key => $mark)
                        if (!empty($mark))
                            echo "<span id='error' class='red_message'>{$files[$key]}</span><br>";
                echo "</td>";
            echo "</tr>";
        }
    echo "</table>";

    // non-scrolling button
    ?>
        <div id="header">
            <form>
                <p align="center">
                    <input type="button" class="search" value="Перейти к следующей ошибке">
                    <input type="text" name="err_number" value="<?php echo $error; ?>" hidden>
                </p>
            </form>
        </div>
    <?php

    // database connections close
    db2_close($connection_TBSM);

    ?>
</body>
</html>