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
    <title>Пакетная загрузка Паспортов и Протоколов мониторинга</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
</head>
<body>
	<?php
    require_once 'connections/TBSM.php';
    include 'functions/user_roles.php';
    include 'functions/tbsm.php';
    include 'functions/regions.php';

    $pass_temp_dir = '/usr/local/apache2/htdocs/pfr_other/Passports/NEW/';	    // passports temp directory
    $pass_targ_dir = '/usr/local/apache2/htdocs/pfr_other/Passports/';	        // passports target directory
    $arr_file_types = array ('doc', 'docx', 'pdf', 'zip');                      // available file extensions array
    $arr_files = [];                                                            // files parts array
    $output = ""; 						                                        // top header informational message

    $file_names = [ ];			// temp array for file names
    $service_names = [ ];		// temp array for service names

    $except_regions = array (
            'г. Москва',
            'Московская обл.',
            'г. С-Петербург',
            'Ленинградская обл.',
            'Республика Крым',
            'г. Севастополь',
            'региональный контейнер',
    );        // regions with exclusive service names
    $except_codes = array ('101', '102', '000', '060', '087', '201', '057', '088', '202', '091', '092', '000',);

        // ******************************************************************************************************************************

    // user access codes load from file and check
    $acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
    if(!empty($acs))
        list($acs_user, $acs_role) = explode(';', $acs);
    $acs_form = ($acs_role == 'admin' or $acs_role == 'user');

    // top header
    $title = "Пакетная загрузка Паспортов и Протоколов мониторинга";
    $links = array (
            "<a href='passports_load_check.php' target='_blank'>Проверка Паспортов и Протоколов мониторинга</a>",
            "<a href='passports_load_tree.php' target='_blank'>Паспорта и Протоколы мониторинга в дереве TBSM</a>",
        );
    require 'functions/header_1.php';

    // web form button was pressed
    if (isset($_POST['submitBtn'])) {
        $good = $bad = 0;
        switch ($_POST['submitBtn']) {
            // file(s) rename
            case 'Переименовать':
                // directory read
                $files = scandir($pass_temp_dir);
                unset($files[0]);
                unset($files[1]);

                // files rename
                foreach ($files as $file) {
                    $file_ = str_replace(array('.', ' '), '_', $file);
                    if (isset($_POST[$file_]) and strcmp($file, $_POST[$file_]))
                        if (rename($pass_temp_dir . $file, $pass_temp_dir . $_POST[$file_]))
                            $good++;
                        else
                            $bad++;
                }
                $output = "Успешно переименовано файлов: " . $good . " Ошибок при переименовании: " . $bad;
                break;
            case 'Завести':
                $i = 0;
                while (isset($_POST['code' . $i])) {
                    // record insert into PFR_PASSPORT_CODING
                    $sel = "insert into DB2INST1.PFR_PASSPORT_CODING 
                             (  FILE_CODE,
                                PTK,
                                REGION,
                                ENVIRONMENT,
                                PASS_DISPLAY_NAME,
                                PROC_DISPLAY_NAME)
                            values 
                              ( '".$_POST['code' . $i]."',
                                '".$_POST['ptk' . $i]."',
                                '".$_POST['region' . $i]."',
                                '".$_POST['environment' . $i]."',
                                '".$_POST['pass' . $i]."',
                                '".$_POST['proc' . $i]."')";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    if (db2_execute($stmt)) {
                        $good++;
                        // record insert into PFR_PASSPORT_SERVICES
                        $sel = "insert into DB2INST1.PFR_PASSPORT_SERVICES 
                             (  SERVICE_NAME,
                                FILE_CODE)
                            values 
                              ( '*REFINE*".rand()."',
                                '".$_POST['code' . $i]."')";
                        $stmt = db2_prepare($connection_TBSM, $sel);
                        db2_execute($stmt);
                    }
                    else
                        $bad++;
                    $i++;
                }
                $output = "В таблицы PFR_PASSPORT_CODING и PFR_PASSPORT_SERVICES успешно добавлено записей: " . $good . " Ошибок при добавлении: " . $bad;
                break;
            case 'Добавить':
                $i = 0;
                while (isset($_POST['filecode' . $i])) {
                    // record insert into PFR_PASSPORT_VERSIONS
                    if ($_POST['doctype' . $i] == "Паспорт") {
                        // is exists record for passport with suitable extension
                        $insert = true;
                        $proc_file = '';
                        $sel = "SELECT PROC_FILE FROM DB2INST1.PFR_PASSPORT_VERSIONS WHERE FILE_CODE = '" . $_POST['filecode' . $i] . "' AND 
                                                                                            PASS_VERSION = '" . $_POST['version' . $i] . "'";
                        $stmt = db2_prepare($connection_TBSM, $sel);
                        db2_execute($stmt);

                        // pdf passports suit for pdf protocols; zip, doc, docx passports suit for same protocols in any combination
                        while ($row = db2_fetch_assoc($stmt)) {
                            if ($_POST['extension' . $i] == 'pdf' and $row['PROC_FILE'] == 'pdf') {
                                $insert = false;
                                $proc_file = 'pdf';
                                break;
                            }
                            else if ($_POST['extension' . $i] != 'pdf' and $row['PROC_FILE'] != 'pdf') {
                                $insert = false;
                                $proc_file = $row['PROC_FILE'];
                                break;
                            }
                        }

                        if ($insert)
                            $sel = "INSERT INTO DB2INST1.PFR_PASSPORT_VERSIONS 
                                     (  FILE_CODE,
                                        PASS_VERSION,
                                        PASS_DATE,
                                        PASS_FILE,
                                        PASS_SIGNED)
                                    VALUES 
                                      ( '" . $_POST['filecode' . $i] . "',
                                        '" . $_POST['version' . $i] . "',
                                        '" . $_POST['date' . $i] . "',
                                        '" . $_POST['extension' . $i] . "',
                                        '" . (isset($_POST['signed' . $i]) ? 'Y' : 'N') . "')";
                        else
                            $sel = "UPDATE DB2INST1.PFR_PASSPORT_VERSIONS
                                    SET PASS_DATE = '" . $_POST['date' . $i] . "',
                                        PASS_FILE = '" . $_POST['extension' . $i] . "',
                                        PASS_SIGNED = '" . (isset($_POST['signed' . $i]) ? 'Y' : 'N') . "' 
                                    WHERE FILE_CODE = '" . $_POST['filecode' . $i] . "' AND 
                                          PASS_VERSION = '" . $_POST['version' . $i] . "' AND
                                          PROC_FILE = '" . $proc_file . "'";
                    }
                    else {
                        // is exists passport with the same version and suitable extension
                        $insert = true;
                        $pass_file = '';
                        $sel = "SELECT PASS_FILE FROM DB2INST1.PFR_PASSPORT_VERSIONS 
                                WHERE FILE_CODE = '{$_POST['filecode' . $i]}' AND PASS_VERSION = '{$_POST['version' . $i]}'";
                        $stmt = db2_prepare($connection_TBSM, $sel);
                        db2_execute($stmt);

                        // pdf protocols suit for pdf passports; zip, doc, docx protocols suit for same passports in any combination
                        while ($row = db2_fetch_assoc($stmt)) {
                            if ($_POST['extension' . $i] == 'pdf' and $row['PASS_FILE'] == 'pdf') {
                                $insert = false;
                                $pass_file = 'pdf';
                                break;
                            }
                            else if ($_POST['extension' . $i] != 'pdf' and $row['PASS_FILE'] != 'pdf') {
                                $insert = false;
                                $pass_file = $row['PASS_FILE'];
                                break;
                            }
                        }

                        if ($insert)
                            $sel = "insert into DB2INST1.PFR_PASSPORT_VERSIONS 
                                 (  FILE_CODE,
                                    PASS_VERSION,
                                    PASS_DATE,
                                    PROC_DATE,
                                    PROC_FILE,
                                    PROC_SIGNED)
                                values 
                                  ( '".$_POST['filecode' . $i]."',
                                    '".$_POST['version' . $i]."',
                                    '',
                                    '".$_POST['date' . $i]."',
                                    '".$_POST['extension' . $i]."',
                                    '".(isset($_POST['signed' . $i]) ? 'Y' : 'N') . "')";
                        else
                            $sel = "UPDATE DB2INST1.PFR_PASSPORT_VERSIONS
                                    SET PROC_DATE = '" . $_POST['date' . $i] . "',
                                        PROC_FILE = '" . $_POST['extension' . $i] . "',
                                        PROC_SIGNED = '" . (isset($_POST['signed' . $i]) ? 'Y' : 'N') . "' 
                                    WHERE FILE_CODE = '" . $_POST['filecode' . $i] . "' AND 
                                          PASS_VERSION = '" . $_POST['version' . $i] . "' AND
                                          PASS_FILE = '" . $pass_file . "'";
                    }
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    if (db2_execute($stmt)) {
                        $good++;
                        // file move
                        rename($pass_temp_dir . $_POST['filename' . $i], $pass_targ_dir . $_POST['filename' . $i]);
                    }
                    else
                        $bad++;
                    $i++;
                }
                $output = "В таблицу PFR_PASSPORT_VERSIONS успешно добавлено записей: " . $good . " Ошибок при добавлении: " . $bad;
                break;
            case 'Применить':
                $i = 0;
                while (isset($_POST['passcode' . $i])) {
                    $ins_OK = false;
                    if (isset($_POST['service' . $i]) and !empty($_POST['service' . $i])) {
                        // federal service or regional exception
                        $sel = "insert into DB2INST1.PFR_PASSPORT_SERVICES (FILE_CODE, SERVICE_NAME) values ('".$_POST['passcode' . $i]."', '".$_POST['service' . $i]."')";
                        $stmt = db2_prepare($connection_TBSM, $sel);
                        if (db2_execute($stmt)) {
                            $ins_OK = true;
                            $good++;
                        }
                        else
                            $bad++;
                    }
                    else if (isset($_POST['service_templ' . $i]) and !empty($_POST['service_templ' . $i]) and substr($_POST['service_templ' . $i], 0, 1) == "%") {
                        // regional service
                        foreach ($array_regions as $code => $d)
                            if (!in_array($code, $except_codes)) {
                                $sel = "insert into DB2INST1.PFR_PASSPORT_SERVICES (FILE_CODE, SERVICE_NAME) values ('".$_POST['passcode' . $i]."', '".pfr_id_fo($code) . 'FO' . $code . substr($_POST['service_templ' . $i], 1)."')";
                                $stmt = db2_prepare($connection_TBSM, $sel);
                                if (db2_execute($stmt)) {
                                    $ins_OK = true;
                                    $good++;
                                }
                                else
                                    $bad++;
                            }
                    }

                    // REFINE record delete from PFR_PASSPORT_SERVICES
                    if ($ins_OK) {
                        $sel = "DELETE FROM DB2INST1.PFR_PASSPORT_SERVICES WHERE FILE_CODE = '" . $_POST['passcode' . $i] . "' AND SERVICE_NAME LIKE '*REFINE*%'";
                        $stmt = db2_prepare($connection_TBSM, $sel);
                        db2_execute($stmt);
                    }
                    $i++;
                }
                $output = "В таблицу PFR_PASSPORT_SERVICES успешно добавлено записей: " . $good . " Ошибок при добаавлении: " . $bad;
                break;
            default:
                break;
        }
    }

    // top header informational message output
    require 'functions/header_2.php';

    ?> <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="formId" onsubmit="return 1"> <?php

    // ******************************************************************************************************************************

    // directory read
    $files = scandir($pass_temp_dir);
    unset($files[0]);
    unset($files[1]);

    // file name parse and check
    echo "<br><br><h3>1) Проверка корректности имён файлов</h3>";
    echo "В таблице приведены имена файлов, не соответствующие установленному шаблону.<br>";
    echo "Отредактируйте имена файлов в соответствии с образцом и нажмите кнопку \"Переименовать\".<br>";
    echo "Файлы будут переименованы в исходной директории <i>NEW</i>.<br><br>";
    echo "<table border=\"0\">";
        echo "<tr>";
            echo "<td><u>Образец</u>: </td>";
            echo "<td>Паспорт_АСВ ФССП_ФЕД_СПРЭ_версия_001_от_30.09.2016.pdf</td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td></td>";
            echo "<td>Протокол_АСВ ФССП_ФЕД_СПРЭ_версия_001_от_30.09.2016.pdf</td>";
        echo "</tr>";
    echo "</table><br>";
    echo "Для подсказки о типе ошибки в наименовании файла наведите курсор на строку с файлом.<br><br>";

    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Файл</th>";
        echo "</tr>";

        foreach($files as $file) {
            $file_ = str_replace(array ('.', ' '), '_', $file);
            echo "<tr>";
                // file template
                $arr_list = explode('_', $file);
                if (count($arr_list) != 8) {
                    ?> <td class='red_status'> <input name="<?php echo $file_; ?>" type="text" size="100" maxlength="256" value="<?php echo $file; ?>" required title="Количество подчёркиваний не соответствует норме"> </td> <?php
                    continue;
                }

                // file tail - date and extension
                $arr_tail = explode('.', $arr_list[7], 4);
                if (count($arr_tail) != 4) {
                    ?> <td class='red_status'> <input name="<?php echo $file_; ?>" type="text" size="100" maxlength="256" value="<?php echo $file; ?>" required title="Количество точек не соответствует норме"> </td> <?php
                    continue;
                }
                $arr_list[7] = $arr_tail[0].'.'.$arr_tail[1].'.'.$arr_tail[2];
                $arr_list[8] = $arr_tail[3];

                // fyle type - passport or protocol
                if (strcmp($arr_list[0] ,'Паспорт') and strcmp($arr_list[0] ,'Протокол')) {
                    ?> <td class='red_status'> <input name="<?php echo $file_; ?>" type="text" size="100" maxlength="256" value="<?php echo $file; ?>" required title="Имя должно начинаться на 'Паспорт' или 'Протокол'"> </td> <?php
                    continue;
                }

                // date check
                if (!checkdate($arr_tail[1], $arr_tail[0], $arr_tail[2])) {
                    ?> <td class='red_status'> <input name="<?php echo $file_; ?>" type="text" size="100" maxlength="256" value="<?php echo $file; ?>" required title="Недействительная дата"> </td> <?php
                    continue;
                }

                // file extension check
                if (!in_array($arr_list[8], $arr_file_types)) {
                    ?> <td class='red_status'> <input name="<?php echo $file_; ?>" type="text" size="100" maxlength="256" value="<?php echo $file; ?>" required title="Недопустимое расширение файла"> </td> <?php
                    continue;
                }

                // correct file name
                $arr_files[$file] = array (
                    "doc_type" => $arr_list[0],
                    "ptk" => $arr_list[1],
                    "region" => $arr_list[2],
                    "environment" => $arr_list[3],
                    "version" => $arr_list[5],
                    "date" => $arr_list[7],
                    "extension" => $arr_list[8],
                    "file_code" => '',
                );
            echo "</tr>";
        }
        echo "<tr>";
            ?> <td align="center" colspan="0"> <input type="submit" class="btn" name="submitBtn" value="Переименовать"/> </td> <?php
        echo "</tr>";
    echo "</table>";
    echo "<br><br><hr color='lightgray'>";

    // ******************************************************************************************************************************

    // document code
    echo "<br><br><h3>2) Заведение новых кодов документов</h3>";
    echo "В таблице приведены документы, для которых ещё не заведён код документа.<br>";
    echo "Отредактируйте, при необходимости, предлагаемые коды и наименования документов и нажмите кнопку \"Завести\".<br>";
    echo "В таблицу <i>PFR_PASSPORT_CODING</i> будут заведены новые коды документов.<br>";
    echo "В таблицу <i>PFR_PASSPORT_SERVICES</i> будут заведены эти же коды документов с пометкой *REFINE*хххх в поле SERVICE_NAME для дальнейшего сопоставления с ПТК (см. п.4).<br><br>";

    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Тип документа</th>";
            echo "<th>Имя сервиса</th>";
            echo "<th>Региональность</th>";
            echo "<th>Среда</th>";
            echo "<th>Код документа</th>";
            echo "<th>Наименование Паспорта</th>";
            echo "<th>Наименование Протокола</th>";
            echo "<th>Файл</th>";
        echo "</tr>";

        $i = 0;
        foreach($arr_files as $file => &$rec) {
            echo "<tr>";
                // document code search
                $sel = "SELECT * FROM DB2INST1.PFR_PASSPORT_CODING WHERE PTK = '".$rec['ptk']."' and REGION = '".$rec['region']."' and ENVIRONMENT = '".$rec['environment']."'";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                $row = db2_fetch_assoc($stmt);

                // document not found in DB
                if (empty($row)) {
                    echo "<td>".$rec['doc_type']."</td>";
                    echo "<td>".$rec['ptk']."</td>";
                    echo "<td>".$rec['region']."</td>";
                    echo "<td>".$rec['environment']."</td>";

                    $combo = $rec['ptk'].' '.$rec['region'].' '.$rec['environment'];
                    $combo_ = str_replace(' ', '_', $combo);
                    $combo_translit = strtoupper(translit($combo_));

                    // document code uniqueness check
                    $sel = "SELECT FILE_CODE FROM DB2INST1.PFR_PASSPORT_CODING WHERE FILE_CODE = '$combo_translit'";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);
                    $row = db2_fetch_assoc($stmt);

                    ?>
                    <input name="ptk<?php echo $i; ?>" type="text" value="<?php echo $rec['ptk']; ?>" hidden>
                    <input name="region<?php echo $i; ?>" type="text" value="<?php echo $rec['region']; ?>" hidden>
                    <input name="environment<?php echo $i; ?>" type="text" value="<?php echo $rec['environment']; ?>" hidden>

                    <td <?php echo empty($row) ? "" : "class='red_status'"; ?>> <input name="code<?php echo $i; ?>" type="text" size="30" maxlength="256" value="<?php echo $combo_translit; ?>" required title="<?php echo empty($row) ? 'Допустимый код' : 'Такой код уже имеется'; ?>"> </td>
                    <td> <input name="pass<?php echo $i; ?>" type="text" size="50" maxlength="256" value="Паспорт <?php echo $combo; ?>" required> </td>
                    <td> <input name="proc<?php echo $i; ?>" type="text" size="50" maxlength="256" value="Протокол <?php echo $combo; ?>" required> </td>
                    <?php
                    $i++;

                    echo "<td>".$file."</td>";
                }
                // document is in DB
                else
                    $rec['file_code'] = $row['FILE_CODE'];
            echo "</tr>";
        }
        unset($rec);
        echo "<tr>";
            ?> <td align="center" colspan="0"> <input type="submit" class="btn" name="submitBtn" value="Завести"/> </td> <?php
        echo "</tr>";
    echo "</table>";
    echo "<br><br><hr color='lightgray'>";

    // ******************************************************************************************************************************

    // document versions
    echo "<br><br><h3>3) Добавление версий документов</h3>";
    echo "В таблице приведены файлы с новыми версиями документов.<br>";
    echo "Проверьте номера и даты версий и нажмите кнопку \"Добавить\".<br>";
    echo "В таблицу <i>PFR_PASSPORT_VERSIONS</i> будут добавлены версии документов.<br>";
    echo "Файлы будут перемещены в директорию <i>Passports</i>.<br><br>";
    echo "Красным цветом выделены дубликаты документов (совпадают версия и расширение файла).<br><br>";

    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Версия</th>";
            echo "<th>Дата</th>";
            echo "<th>Утверждено</th>";
            echo "<th>Тип файла</th>";
            echo "<th>Файл</th>";
            echo "</tr>";

            $i = 0;
            foreach($arr_files as $file => $rec) {
                if (!empty($rec['file_code'])) {
                    echo "<tr>";
                    // document code search
                    $sel = "SELECT * FROM DB2INST1.PFR_PASSPORT_VERSIONS WHERE FILE_CODE = '" . $rec['file_code'] . "'";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);

                    // duplicates check
                    $warning = false;
                    while ($row = db2_fetch_assoc($stmt)) {
                        if ($rec['doc_type'] == 'Паспорт' and $row['PASS_VERSION'] == $rec['version'] and $row['PASS_FILE'] == $rec['extension'])
                            $warning = true;
                        if ($rec['doc_type'] == 'Протокол' and $row['PASS_VERSION'] == $rec['version'] and $row['PROC_FILE'] == $rec['extension'])
                            $warning = true;
                    }

                    echo "<td " . ($warning ? "class='red_status'" : "") . ">" . $rec['version'] ."</td>";
                    echo "<td>" . $rec['date'] . "</td>";
                    echo "<td align='center'><input name='signed" . $i . "' type='checkbox' title='Утверждён или нет' " . ($rec['extension'] == 'pdf' ? 'checked' : '') . "/></td>";
                    echo "<td " . ($warning ? "class='red_status'" : "") . ">" . $rec['extension'] . "</td>";
                    echo "<td>" . $file . "</td>";

                    // hidden fields for post
                    if (!$warning) {
                        ?> <input name="doctype<?php echo $i; ?>" type="text" value="<?php echo $rec['doc_type']; ?>"
                                  hidden>
                        <input name="filecode<?php echo $i; ?>" type="text" value="<?php echo $rec['file_code']; ?>"
                               hidden>
                        <input name="version<?php echo $i; ?>" type="text" value="<?php echo $rec['version']; ?>"
                               hidden>
                        <input name="date<?php echo $i; ?>" type="text" value="<?php echo $rec['date']; ?>" hidden>
                        <input name="extension<?php echo $i; ?>" type="text" value="<?php echo $rec['extension']; ?>"
                               hidden>
                        <input name="filename<?php echo $i; ?>" type="text" value="<?php echo $file; ?>" hidden>
                        <?php ;
                    }
                    echo "</tr>";
                    $i++;
                }
            }
        echo "<tr>";
        ?> <td align="center" colspan="0"> <input type="submit" class="btn" name="submitBtn" value="Добавить"/> </td> <?php
        echo "</tr>";
    echo "</table>";
    echo "<br><br><hr color='lightgray'>";

    // ******************************************************************************************************************************

    // passport codes for refine
    echo "<br><br><h3>4) Добавление соответствия Паспортов сервисам в TBSM</h3>";
    echo "В таблице приведены Паспорта, которым ещё не сопоставлен сервис в сервисном дереве мониторинга.<br>";
    echo "Добавьте наименования сервисов (не <i>DisplayName</i>!) и нажмите кнопку \"Применить\".<br>";
    echo "В таблице <i>PFR_PASSPORT_SERVICES</i> кодам Паспортов будет сопоставлен заданный сервис.<br><br>";

    echo "Для региональных сервисов в поле \"шаблон для всех регионов\" введите имя по шаблону: <b>%PTK_ZNP</b><br>";
    echo "При этом будут заведены 80 типовых сервисов вида: 03FO001PTK_ZNP, 04FO002PTK_ZNP и т.д.<br>";
    echo "Во все остальные поля вводится полное имя сервиса!<br><br>";

    // DB records select
    $sel = "SELECT PTK, REGION, ENVIRONMENT, PFR_PASSPORT_CODING.FILE_CODE
            FROM PFR_PASSPORT_CODING, PFR_PASSPORT_SERVICES
            where PFR_PASSPORT_CODING.FILE_CODE = PFR_PASSPORT_SERVICES.FILE_CODE and SERVICE_NAME like '*REFINE*%'
            order by PFR_PASSPORT_CODING.FILE_CODE asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    $i=0;
    $bg_color = "LavenderBlush";
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Код Паспорта</th>";
            echo "<th>ПТК</th>";
            echo "<th>Региональность</th>";
            echo "<th>Среда</th>";
            echo "<th>Сервис в TBSM</th>";
        echo "</tr>";
        while ($row = db2_fetch_assoc($stmt)) {
            $is_regional = ($row['REGION'] == 'РЕГ');
            $bg_color = ($bg_color == "Lavender" ? "WhiteSmoke" : "Lavender");
            echo "<tr>";
                echo "<td bgcolor='".$bg_color."' ".($is_regional ? "rowspan='8'" : "").">".$row['FILE_CODE']."</td>";
                echo "<td bgcolor='".$bg_color."' ".($is_regional ? "rowspan='8'" : "").">".$row['PTK']."</td>";
                echo "<td bgcolor='".$bg_color."' ".($is_regional ? "rowspan='8'" : "").">".$row['REGION']."</td>";
                echo "<td bgcolor='".$bg_color."' ".($is_regional ? "rowspan='8'" : "").">".$row['ENVIRONMENT']."</td>";
                echo "<td bgcolor='".$bg_color."'>";
                    ?>
                    <input name="<?php echo ($is_regional ? 'service_templ' : 'service').$i; ?>" type="text" value="" size="60" maxlength="256" >
                    <input name="passcode<?php echo $i; ?>" type="text" value="<?php echo $row['FILE_CODE']; ?>" hidden>
                    <?php
                echo $is_regional ? "&emsp;<font size='-1'>шаблон для всех регионов</font>" : "";
                echo "</td>";
            echo "</tr>";
            $i++;

            // regions exeptions
            if ($is_regional) {
                foreach ($except_regions as $r) {
                    echo "<tr>";
                        echo "<td bgcolor='".$bg_color."'>";
                        ?>
                        <input name="service<?php echo $i; ?>" type="text" value="" size="60" maxlength="256" >
                        <input name="passcode<?php echo $i; ?>" type="text" value="<?php echo $row['FILE_CODE']; ?>" hidden>
                        <?php
                        echo "&emsp;<font size='-1'>".$r."</font></td>";
                    echo "</tr>";
                    $i++;
                }
            }
        }
        echo "<tr>";
            ?> <td align="center" colspan="0"> <input type="submit" class="btn" name="submitBtn" value="Применить"/> </td> <?php
        echo "</tr>";
    echo "</table>";
    echo "<br><br><hr color='lightgray'>";

    // ******************************************************************************************************************************
/*
    // DB records and file directory revision
    echo "<br><br><h3>5) Сверка записей в БД и файлов в директории</h3>";
    echo "В таблицах выводятся расхождения между списком Паспортов из таблиц <i>PFR_PASSPORT_CODING</i> и <i>PFR_PASSPORT_VERSIONS</i> в сравнении с имеющимися файлами в директории Passports.<br><br>";

    // DB records select
    $sel = "SELECT PTK, REGION, ENVIRONMENT, PASS_VERSION, PASS_DATE, PASS_FILE, PROC_DATE, PROC_FILE
        FROM PFR_PASSPORT_CODING, PFR_PASSPORT_VERSIONS
        where PFR_PASSPORT_CODING.FILE_CODE = PFR_PASSPORT_VERSIONS.FILE_CODE
        order by PTK asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    while ($row = db2_fetch_assoc($stmt)) {
        if (!empty($row['PASS_DATE']))
            $db_rev [] = "Паспорт_" . $row['PTK'] . "_" . $row['REGION'] . "_" . $row['ENVIRONMENT'] . "_версия_" . $row['PASS_VERSION'] . "_от_" . $row['PASS_DATE'] . "." . $row['PASS_FILE'];
        if (!empty($row['PROC_DATE']))
            $db_rev [] = "Протокол_" . $row['PTK'] . "_" . $row['REGION'] . "_" . $row['ENVIRONMENT'] . "_версия_" . $row['PASS_VERSION'] . "_от_" . $row['PROC_DATE'] . "." . $row['PROC_FILE'];
    }

    // file directory read
    $files = scandir($pass_targ_dir);
    unset($files[0]);
    unset($files[1]);

    // revision table №1
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Записи в БД, для которых отсутствуют файлы</th>";
        echo "</tr>";
        foreach ($db_rev as $db_rec)
            if (!in_array($db_rec, $files))
                echo "<tr><td>".$db_rec."</td></tr>";
    echo "</table><br><br>";

    // revision table №2
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>Файлы, для которых отсутствуют записи в БД</th>";
        echo "</tr>";
        foreach ($files as $file)
            if (!is_dir($pass_targ_dir.$file) and $file != '.htaccess' and !in_array($file, $db_rev))
                echo "<tr><td>".$file."</td></tr>";
    echo "</table>";
    echo "<br><br><hr color='lightgray'>";

    // ******************************************************************************************************************************

    // PFR_PASSPORT_VERSIONS duplicates
    echo "<br><br><h3>6) Проверка наличия дубликатов в таблице <i>PFR_PASSPORT_VERSIONS</i></h3>";

    // DB records select
    $sel = "SELECT a1.ID as ID1, a2.ID as ID2, a1.FILE_CODE, a1.PASS_VERSION, a1.PASS_DATE, a1.PASS_FILE
        FROM PFR_PASSPORT_VERSIONS a1, PFR_PASSPORT_VERSIONS a2
        where a1.ID < a2.ID and a1.FILE_CODE = a2.FILE_CODE and a1.PASS_VERSION = a2.PASS_VERSION and a1.PASS_DATE = a2.PASS_DATE and a1.PASS_FILE = a2.PASS_FILE";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    // duplicates table
    echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
            echo "<th>ID №1</th>";
            echo "<th>ID №2</th>";
            echo "<th>FILE_CODE</th>";
            echo "<th>PASS_VERSION</th>";
            echo "<th>PASS_DATE</th>";
            echo "<th>PASS_FILE</th>";
        echo "</tr>";
    while ($row = db2_fetch_assoc($stmt)) {
        echo "<tr>";
            echo "<td>" . $row['ID1'] . "</td>";
            echo "<td>" . $row['ID2'] . "</td>";
            echo "<td>" . $row['FILE_CODE'] . "</td>";
            echo "<td>" . $row['PASS_VERSION'] . "</td>";
            echo "<td>" . $row['PASS_DATE'] . "</td>";
            echo "<td>" . $row['PASS_FILE'] . "</td>";
            echo "</tr>";
    }
    echo "</table>";
    echo "<br><br><hr color='lightgray'>";

    // ******************************************************************************************************************************

        // all passport files
        echo "<br><br><h3>7) Файлы Паспортов и сопоставленные им сервисы</h3>";
        echo "В таблице выводится список всех файлов Паспортов из директории <i>Passports</i> с указанием сопоставленного сервиса из сервисного дерева мониторинга.<br><br>";

        // revision table
        echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
        echo "<tr>";
        echo "<th>Файл Паспорта</th>";
        echo "<th>Имя сервиса</th>";
        echo "</tr>";

        // passport groups
        $FIELD1 = '';
        $bg_color = "LavenderBlush";
        $mask = '';

        foreach ($files as $file)
            if (!is_dir($pass_targ_dir.$file) and $file != '.htaccess') {
                // file name parsing
                $arr_list = explode('_', $file);

                if ($arr_list[0] == 'Паспорт') {
                    // new passport group with unique names
                    if ($arr_list[1].$arr_list[2].$arr_list[3] != $FIELD1) {

                        // previous group output
                        if (count($file_names) > 0)
                            for ($i = 0; $i < count($file_names); $i++) {
                                echo "<tr>";
                                echo "<td valign='top' bgcolor='".$bg_color."'>";
                                echo $file_names[$i];
                                echo "</td>";
                                if ($i == 0) {
                                    echo "<td rowspan='" . count($file_names) . "' bgcolor='" . $bg_color . "'>";
                                    if (isset($service_names[0]))
                                        if (strpos($service_names[0], '*REFINE*') === false) {
                                            $count = 0;
                                            foreach ($service_names as $s)
                                                if (preg_match("/^\d{2}FO\d{3}\S+/", $s) == 1) {
                                                    if ($count++ == 1)
                                                        $mask = $s;
                                                }
                                                else
                                                    echo $s . "<br>";
                                            if ($count > 0)
                                                echo $count == 1 ? $mask : $count . " регионов по маске %".substr($mask, 7);
                                        }
                                        else
                                            echo "<font color='red'>НЕ СОПОСТАВЛЕНО (см. п.4)</font>";
                                    else
                                        echo "<font color='red'>!!! ОШИБКА В ОДНОЙ ИЗ ТАБЛИЦ !!!</font>";
                                    echo "</td>";
                                }
                                echo "</tr>";
                            }

                        $file_names = array();
                        $service_names = array();
                        $FIELD1 = $arr_list[1].$arr_list[2].$arr_list[3];
                        $bg_color = ($bg_color == "Lavender" ? "WhiteSmoke" : "Lavender");

                        // DB2 search
                        $sel = "SELECT SERVICE_NAME
                            FROM PFR_PASSPORT_CODING, PFR_PASSPORT_SERVICES
                            where PTK = '".$arr_list[1]."' and REGION = '".$arr_list[2]."' and ENVIRONMENT = '".$arr_list[3]."' and 
                                  PFR_PASSPORT_CODING.FILE_CODE = PFR_PASSPORT_SERVICES.FILE_CODE";
                        $stmt = db2_prepare($connection_TBSM, $sel);
                        $result = db2_execute($stmt);
                        while ($row = db2_fetch_assoc($stmt))
                            $service_names [] = $row['SERVICE_NAME'];

                    }
                    $file_names [] = $file;
                }
            }
        echo "</table>";
        echo "<br><br><hr color='lightgray'>";
*/
    // ******************************************************************************************************************************

    ?> </form> <?php

    // database connections close
    db2_close($connection_TBSM);

    // ******************************************************************************************************************************

    function translit($str) {
        $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я');
        $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Gh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', 'Y', 'Y', 'Y', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'gh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', 'y', 'y', 'y', 'e', 'yu', 'ya');
        return str_replace($rus, $lat, $str);
    }

    ?>
</body>
</html>