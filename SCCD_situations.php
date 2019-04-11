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
        <title>Полный перечень ситуаций на TEMS</title>
        <script src="scripts/jquery-3.2.1.min.js"></script>
        <script src="scripts/common.js"></script>
        <script src="scripts/SCCD_situations.js"></script>
    </head>
    <body>
    <?php
    require_once 'connections/TBSM.php';
    require 'functions/user_roles.php';
    require 'functions/regions.php';
    include 'functions/PFR_LOCATIONS_record_form.php';

    $output = '';
    $fields_arr = array(
        'region' => array(
            'title' => 'Регион',
            'filter_name' => 'filter_region',
            'filter_title' => 'Фильтр по региону',
            'filter_value' => '',
            'table_field' => 'TOBJACCL.REGION',
            'dynamic_filter' => 'dynamic_region',
            'dynamic_class' => 'class_region',
        ),
        'node' => array(
            'title' => 'Узел',
            'filter_name' => 'filter_node',
            'filter_title' => 'Фильтр по узлу',
            'filter_value' => '',
            'table_field' => 'TOBJACCL.NODEL',
            'dynamic_filter' => 'dynamic_node',
            'dynamic_class' => 'class_node',
        ),
        'sit_name' => array(
            'title' => 'Имя ситуации',
            'filter_name' => 'filter_situation',
            'filter_title' => 'Фильтр по имени ситуациии',
            'filter_value' => '',
            'table_field' => 'TOBJACCL.OBJNAME',
            'dynamic_filter' => 'dynamic_situation',
            'dynamic_class' => 'class_situation',
        ),
/*        'sit_state' => array(
            'title' => 'Статус ситуации',
            'filter_name' => 'filter_status',
            'filter_title' => 'Фильтр по статусу ситуациии',
            'filter_value' => '',
            'table_field' => 'ISITSTSH.DELTASTAT',
            'dynamic_filter' => 'dynamic_status',
            'dynamic_class' => 'class_status',
        ),*/
    );

    // get filters
    foreach($fields_arr as &$val)
        if (isset($_POST[$val['filter_name']]))
            $val['filter_value'] = strtoupper($_POST[$val['filter_name']]);
        else if (isset($_GET[$val['filter_name']]))
            $val['filter_value'] = strtoupper($_GET[$val['filter_name']]);
    unset($val);

    // GET parameters string form
    $param_str = '';
    foreach ($fields_arr as $val)
        $param_str .= "{$val['filter_name']}={$val['filter_value']}&";
    $param_str = substr($param_str, 0, -1);

    // view locations button was pressed
    if (isset($_POST['editRecord'])) {
        $PFR_LOCATIONS_fields['ID']['VALUE'] = $_POST['editRecord'];
        if (record_form($param_str, 'edit'))
            exit();
        else
            $output = "Ошибка отображения записи из PFR_LOCATIONS";
    }

    // add locations button was pressed
    if (isset($_POST['addRecord'])) {
        $add_records_arr = [];
        $add_records_arr = array($PFR_LOCATIONS_fields);
        $i = 1;
        foreach ($_POST['addRecord'] as $key => $val) {
            list($pfr_id_torg, $node) = explode('*', $key);
            $add_records_arr[$i]['PFR_ID_TORG']['VALUE'] = $pfr_id_torg;
            $add_records_arr[$i]['NODE']['VALUE'] = $node;
            $add_records_arr[$i]['PFR_TORG']['VALUE'] = $pfr_id_torg == '101' ? 'ИЦПУ' :
                                                        (array_key_exists($pfr_id_torg, $array_regions) ? substr($array_regions[$pfr_id_torg], 6) : '');
            $add_records_arr[$i]['PFR_ID_FO']['VALUE'] = pfr_id_fo($pfr_id_torg);
            $add_records_arr[$i]['PFR_FO']['VALUE'] = pfr_fo($add_records_arr[$i]['PFR_ID_FO']['VALUE']);
            $add_records_arr[$i]['PFR_OBJECTSERVER']['VALUE'] = $pfr_id_torg == '101' ? 'NCOMS' : "NCO{$pfr_id_torg}";
            $add_records_arr[$i]['TEMS']['VALUE'] = $pfr_id_torg == '101' ? 'TEMS' : "HUB{$pfr_id_torg}";
            $add_records_arr[$i]['PFR_OBJECT']['VALUE'] = $add_records_arr[$i]['PFR_KE_TORS']['VALUE'] = $add_records_arr[$i]['SERVICE_NAME']['VALUE'] = service_expected($node);
            $i++;
        }

        if (several_records_form($param_str, '', $add_records_arr))
            exit();
        else
            $output = "Ошибка добавления записей в PFR_LOCATIONS";
    }

    // buttons from edit/add/clone/delete form
    if (isset($_POST['sendRequest'])) {
        if ($_POST['sendRequest'] == 'save') {
            $PFR_LOCATIONS_fields['ID']['VALUE'] = $_POST['ID_hidden'];
            if (record_form($param_str, 'save'))
                $output = "Запись сохранена в PFR_LOCATIONS";
            else
                $output = "Ошибка сохранения записи в PFR_LOCATIONS";
        }
        if ($_POST['sendRequest'] == 'save_several') {
            if (several_records_form($param_str, 'save', array($PFR_LOCATIONS_fields)))
                $output = "Записи добавлены в PFR_LOCATIONS";
            else
                $output = "Ошибка добавления записей в PFR_LOCATIONS";
        }
        if ($_POST['sendRequest'] == 'clone') {
            foreach ($PFR_LOCATIONS_fields as $field => &$prop)
                if ($field != 'ID')
                    $prop['VALUE'] = $_POST[$field];
            unset($prop);
            if (record_form($param_str, 'add'))
                exit();
            else
                $output = "Ошибка отображения записи из PFR_LOCATIONS";
        }
        if ($_POST['sendRequest'] == 'delete') {
            $PFR_LOCATIONS_fields['ID']['VALUE'] = $_POST['ID_hidden'];
            if (record_form($param_str, 'delete'))
                $output = "Запись удалена из PFR_LOCATIONS";
            else
                $output = "Ошибка удаления записи из PFR_LOCATIONS";
        }
    }

    // form SELECT for query
    $str_select = implode(', ', array_column($fields_arr, 'table_field'));

    // form WHERE for query
    $str_conditions = '';
    foreach($fields_arr as $val)
        if (!empty($val['filter_value']))
            $str_conditions = $str_conditions."{$val['table_field']} like '%{$val['filter_value']}%' and ";
    $str_conditions = substr($str_conditions, 0, -5);

    // is there any filter?
    $str_filters = '';
    foreach($fields_arr as $val)
        $str_filters = $str_filters.$val['filter_value'];

    // selection from situations tables
    if (empty($str_filters))
        $sel = "select {$str_select} 
                from DB2INST1.PFR_TEMS_TOBJACCL TOBJACCL
                  left join DB2INST1.PFR_TEMS_ISITSTSH ISITSTSH
                  on (TOBJACCL.REGION = ISITSTSH.REGION and TOBJACCL.OBJNAME = ISITSTSH.SITNAME)
                where TOBJACCL.REGION = '-1'";
    else
        $sel = "select distinct {$str_select} 
                from DB2INST1.PFR_TEMS_TOBJACCL TOBJACCL
                  left join DB2INST1.PFR_TEMS_ISITSTSH ISITSTSH
                  on (TOBJACCL.REGION = ISITSTSH.REGION and TOBJACCL.OBJNAME = ISITSTSH.SITNAME)
                where {$str_conditions} 
                order by TOBJACCL.REGION, TOBJACCL.NODEL, TOBJACCL.OBJNAME asc";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);

    // top header
    $title = "Полный перечень ситуаций на TEMS";
    $links = array ("");
    require 'functions/header_1.php';

    $output = (empty($str_filters) and empty($output)) ? "<div class='red_message'>Добавьте параметры поиска в поля фильтров...</div>" : $output;

    // top header informational message output
    require 'functions/header_2.php';

    // help text
    ?>
    <p>При отсутствии ситуации в "Настройках интеграции с СТП", проверьте её наличие на этой странице...</p>
    <table border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td><u>Если ситуация здесь отсутствует, проверьте:</u>&emsp;&emsp;&emsp;&emsp;&emsp;</td>
            <td><u>Если ситуация здесь присутствует, проверьте:</u></td>
        </tr>
        <tr>
            <td valign="top"><ol>
                <li>доступность регионального TEMS</li>
                <li>наличие и дистрибуцию ситуации в TEMS</li>
            </ol></td>
            <td valign="top"><ol>
                <li>наличие корректной записи в PFR_LOCATIONS</li>
                    <li><a href="Documents/Справочная информация по наполнению веб-формы ситуациями.docx">список правил</a>, по которым обрабатываются ситуации</li>
            </ol></td>
        </tr>
    </table>
    <?php

    // table output
    echo "<form action='{$_SERVER['PHP_SELF']}?{$param_str}' method='post'>";
    echo "<table class='all_sits' border='1' cellspacing='0' cellpadding='5'>";
        // titles
        echo "<tr>";
            foreach($fields_arr as $val)
                echo "<th>{$val['title']}</th>";
            echo "<th rowspan='4'>Запись в<br>PFR_LOCATIONS</th>";
        echo "</tr>";

        // filters
        echo "<tr>";
            foreach($fields_arr as $val)
                echo "<td class='col_filter' align='center'>Статический фильтр: <input type='text' id=\"{$val['filter_name']}\" name=\"{$val['filter_name']}\" value=\"{$val['filter_value']}\" title=\"{$val['filter_title']}\"></td>";
        echo "</tr>";
        echo "<tr>";
            echo "<td class='col_filter' align='center' colspan='0'><input type='submit' id='filter_apply' name='filter_apply' value='Применить статические фильтры' title='Применить статические фильтры'></td>";
        echo "</tr>";

        // dynamic filters
        echo "<tr>";
            foreach($fields_arr as $val)
                echo "<td class='col_filter' align='center' colspan='0'>Динамический фильтр: <input type='text' id=\"{$val['dynamic_filter']}\" name=\"{$val['dynamic_filter']}\" value='' title='Динамический фильтр по строкам'></td>";
        echo "</tr>";

        // data rows
        $i = 0;
        $absent_records = 0;
        $bg_color = "Lavender";
        $node_value = '';
        while ($row = db2_fetch_assoc($stmt)) {
            $current_node = $row['NODEL'];
            $current_region = $row['REGION'];
            // new NODE - change color and selection from PFR_LOCATIONS table
            if ($current_node != $node_value) {
                $bg_color = ($bg_color == "Lavender" ? "WhiteSmoke" : "Lavender");

                $sel_loc = "select ID from DB2INST1.PFR_LOCATIONS where NODE = '{$current_node}'";
                $stmt_loc = db2_prepare($connection_TBSM, $sel_loc);
                $result_loc = db2_execute($stmt_loc);
            }
            echo "<tr bgcolor='{$bg_color}' class='row_filtered'>";
                foreach($fields_arr as $val) {
                    echo "<td class=\"{$val['dynamic_filter']}\">{$row[substr($val['table_field'], strpos($val['table_field'], '.') + 1)]}</td>";
                }
                echo "<td align='center' class='pfr_locations'>";
                    // new NODE - view or add record to PFR_LOCATIONS table
                    if ($current_node != $node_value) {
                        $node_value = $current_node;

                        $nodes_count = 0;
                        while ($row_loc = db2_fetch_assoc($stmt_loc)) {
                            echo "<button type='submit' name='editRecord' value='{$row_loc['ID']}' title='Просмотреть/редактировать запись с ID={$row_loc['ID']}' ".($acs_role == 'admin' ? '' : 'disabled')."><img src='images/find.png' width='16' height='16' ></button>";
                            $nodes_count++;
                        }
                        if ($nodes_count == 0) {
                            echo "<input name='addRecord[$current_region*$current_node]' type='checkbox' title='Добавить запись для узла {$current_node}' " . ($acs_role == 'admin' ? '' : 'disabled') . ">";
                            $absent_records++;                        }
                    }
                echo "</td>";
            echo "</tr>";
            $i++;
        }

        // total rows number
        echo "<tr>";
            echo "<td colspan='3'>Всего строк: {$i}</td>";
            echo "<td align='center'>";
                if ($absent_records > 0)
                    echo "<button type='submit' name='BtnAdd' title='Добавить записи для выбранных узлов' ".($acs_role == 'admin' ? '' : 'disabled').">Добавить</button>";
            echo "</td>";
        echo "</tr>";
    echo "</table>";
    echo "</form>";

    // database connections close
    db2_close($connection_TBSM);

    ?>
    </body>
</html>
