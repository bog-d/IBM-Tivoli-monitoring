<?php
$fields = array (
    '' => '',
    'WRITETIME' => '',
    'FIRST_OCCURRENCE' => '',
    'SERIAL' => '',
    'PFR_TORG' => '',
    'NODE' => '',
    'PFR_OBJECT' => '',
    'PFR_KE_TORS' => '',
    'PFR_SIT_NAME' => '',
    'DESCRIPTION' => '',
    'SEVERITY' => '',
    'TTNUMBER' => '',
    'PFR_TSRM_CLASS' => '',
    'CLASSIFICATIONID' => '',
    'CLASSIFICATIONGROUP' => '',
    'PFR_TSRM_WORDER' => '',
);

if (!empty($_POST)) {
    require_once '../connections/TBSM.php';
    require_once '../connections/WHFED.php';
    require_once '../functions/tbsm.php';
    require_once 'chart_sit_collections.php';

    $recordsTotal = 0;
    $recordsFiltered = 0;
    $order_arr = [];
    $error = '';
    $data_arr = [];
    $path_history = [ ];		// array for history tree from parents to childs
    $results = [ ];				// array for endpoint childs

    $draw = $_POST["draw"];
    $error .= settype($draw, "int") ? '' : 'Ошибка приведения к целому параметра draw! ';

    $sql = "select count(*) as TOTAL from DB2INST1.PFR_EVENT_HISTORY";
    $stmt_WHFED = db2_prepare($connection_WHFED, $sql);
    $result_WHFED = db2_execute($stmt_WHFED);
    $row = db2_fetch_assoc($stmt_WHFED);
    $recordsTotal = $row['TOTAL'];

    $search_arr[] = "1 = 1";

    if (!empty($_POST['search']['value'])) {
        $sql = "select SERVICEINSTANCEID from TBSMBASE.SERVICEINSTANCE where SERVICEINSTANCENAME = '{$_POST['search']['value']}'";
        $stmt_TBSM = db2_prepare($connection_TBSM, $sql);
        $result_TBSM = db2_execute($stmt_TBSM);
        $row = db2_fetch_assoc($stmt_TBSM);
        if (empty($row['SERVICEINSTANCEID']))
            $search_arr[] = "1 = 0";
        else {
            if (!ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, 0, $path_history))
                $results[0]['service'] = $_POST['search']['value'];

            $services_str = implode("', '", array_column($results, 'service'));

            $sel_TBSM = "SELECT PFR_KE_TORS FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME in ('{$services_str}')";
            $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
            $result_TBSM = db2_execute($stmt_TBSM);
            while ($row = db2_fetch_assoc($stmt_TBSM))
                $ke_obj[] = $row['PFR_KE_TORS'];

            $ke_obj = array_unique($ke_obj);
            $search_arr[] = "PFR_KE_TORS in ('".(implode("', '", $ke_obj))."')";
        }
    }

    foreach($_POST['columns'] as $i) {
        $field = $i['data'];
        $search = $i['search']['value'];
        if ($search != '')
            if ($field == 'WRITETIME' or $field == 'FIRST_OCCURRENCE') {
                list($start, $finish) = explode('*', $search);
                if (empty($start))
                    $search_arr[] = "substr({$field}, 1, 10) <= '{$finish}'";
                else if (empty($finish))
                    $search_arr[] = "substr({$field}, 1, 10) >= '{$start}'";
                else
                    $search_arr[] = "substr({$field}, 1, 10) >= '{$start}' and substr({$field}, 1, 10) <= '{$finish}'";
            }
            else if ($field == 'SEVERITY')
                $search_arr[] = "{$field} = {$search}";
            else if ($field == 'PFR_TSRM_CLASS') {
                if ($search == '0')
                    $search_arr[] = "({$field} = -1 or {$field} = 0)";
                else if ($search == '2')
                    $search_arr[] = "({$field} = 1 or {$field} = 2)";
                if ($search == '3')
                    $search_arr[] = "({$field} = 3 or {$field} = 4)";
                else
                    $search_arr[] = "{$field} = {$search}";
            }
            else {
                if (strpos($search, '^') === 0)
                    $search_arr[] = "{$field} = '".substr($search, 1)."'";
                else
                    $search_arr[] = "{$field} like '%{$search}%'";
            }
    }
    $search_string = implode(' and ', $search_arr);

    $sql = "select count(*) as TOTAL from DB2INST1.PFR_EVENT_HISTORY where {$search_string}";
    $stmt_WHFED = db2_prepare($connection_WHFED, $sql);
    $result_WHFED = db2_execute($stmt_WHFED);
    $row = db2_fetch_assoc($stmt_WHFED);
    $recordsFiltered = $row['TOTAL'];

/*    $sql = "select distinct SEVERITY from DB2INST1.PFR_EVENT_HISTORY where {$search_string}";
    $stmt_WHFED = db2_prepare($connection_WHFED, $sql);
    $result_WHFED = db2_execute($stmt_WHFED);
    while ($row = db2_fetch_assoc($stmt_WHFED))
        $sev_arr[] = $row['SEVERITY'];*/

    $start_pos = $_POST['start'] + 1;
    $end_pos = $_POST['length'] == -1 ? 0 : ($_POST['start'] + $_POST['length']);
    $sql = "select * from (select row_number() over ( order by ".array_keys($fields)[$_POST['order'][0]['column']]." ".$_POST['order'][0]['dir'].") AS N, 
                      ID, WRITETIME, FIRST_OCCURRENCE, SERIAL, PFR_TORG, NODE, PFR_OBJECT, PFR_KE_TORS, PFR_SIT_NAME, DESCRIPTION, SEVERITY, TTNUMBER, PFR_TSRM_CLASS, CLASSIFICATIONID, CLASSIFICATIONGROUP, PFR_TSRM_WORDER
                  from DB2INST1.PFR_EVENT_HISTORY
                  where {$search_string}) as t where ".(empty($end_pos) ? "t.N >= {$start_pos}" : "t.N between {$start_pos} and {$end_pos}");
    $stmt_WHFED = db2_prepare($connection_WHFED, $sql);
    $result_WHFED = db2_execute($stmt_WHFED);

    while ($row = db2_fetch_assoc($stmt_WHFED)) {
        // sampled situation detect
        $sql_sit = "select * from DB2INST1.PFR_TEMS_SIT_AGGR where SIT_CODE = '{$row['PFR_SIT_NAME']}' and INTERVAL = '000000'";
        $stmt_TBSM = db2_prepare($connection_TBSM, $sql_sit);
        $result_TBSM = db2_execute($stmt_TBSM);

        // traceroute from ISM_SERVER_ICMP_STATUS situation
        $trace_title = $trace_data = '';
        if ($row['PFR_SIT_NAME'] == 'ISM_SERVER_ICMP_STATUS' and $row['SEVERITY'] == 5 and !empty($row['TTNUMBER'])) {
            // IP detect
            $sel_TBSM = "SELECT URL FROM DB2INST1.PFR_LOCATIONS WHERE PFR_OBJECT = '{$row['PFR_OBJECT']}' and URL is not null";
            $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
            $result_TBSM = db2_execute($stmt_TBSM);
            $ip_arr = [];
            while ($row_TBSM = db2_fetch_assoc($stmt_TBSM))
                $ip_arr[] = $row_TBSM['URL'];
            $ip_arr = array_unique(array_filter($ip_arr));

            // time range detect
            $inc_time = mktime(substr($row['FIRST_OCCURRENCE'], 11, 2), substr($row['FIRST_OCCURRENCE'], 14, 2), substr($row['FIRST_OCCURRENCE'], 17, 2),
                substr($row['FIRST_OCCURRENCE'], 5, 2), substr($row['FIRST_OCCURRENCE'], 8, 2), substr($row['FIRST_OCCURRENCE'], 0, 4));
            $sit_time_min = '1' . date("ymdHis", $inc_time - 301);
            $sit_time_max = '1' . date("ymdHis", $inc_time + 301);

            // traceroute detect
            $sel_WHFED = "SELECT * FROM DB2INST1.PFR_TRACEROUTE WHERE (HOST = '{$row['PFR_OBJECT']}' or HOST in ('" . implode("', '", $ip_arr) .
                "')) and TIMESTAMP > '{$sit_time_min}' and TIMESTAMP < '{$sit_time_max}'";
            $stmt_WHFED_2 = db2_prepare($connection_WHFED, $sel_WHFED);
            $result_WHFED_2 = db2_execute($stmt_WHFED_2);
            $row_WHFED = db2_fetch_assoc($stmt_WHFED_2);
            $trace_title = empty($row_WHFED) ? "" : "<br>" .
                        substr($row_WHFED['TIMESTAMP'], 5, 2) . "." . substr($row_WHFED['TIMESTAMP'], 3, 2) . ".20" .
                        substr($row_WHFED['TIMESTAMP'], 1, 2) . " " . substr($row_WHFED['TIMESTAMP'], 7, 2) . ":" .
                        substr($row_WHFED['TIMESTAMP'], 9, 2) . ":" . substr($row_WHFED['TIMESTAMP'], 11, 2) .
                        "<br>с хоста " . $row_WHFED['NODE'];
            $trace_data = str_replace("\n", "<br>", $row_WHFED['TRACE']);
        }

        $data_arr[] = array(
            "DT_RowId" => "row_{$row['ID']}",
            "WRITETIME" => substr($row['WRITETIME'], 0, 19),
            "FIRST_OCCURRENCE" => substr($row['FIRST_OCCURRENCE'], 0, 19),
            "SERIAL" => $row['SERIAL'],
            "PFR_TORG" => $row['PFR_TORG'],
            "NODE" => $row['NODE'],
            "PFR_OBJECT" => $row['PFR_OBJECT'],
            "PFR_KE_TORS" => $row['PFR_KE_TORS'],
            "PFR_SIT_NAME" => $row['PFR_SIT_NAME'],
            "DESCRIPTION" => $row['DESCRIPTION'],
            "SEVERITY" => array_search($row['SEVERITY'], $severity_codes),
            "TTNUMBER" => $row['TTNUMBER'],
            "PFR_TSRM_CLASS" => $class_codes[$row['PFR_TSRM_CLASS']],
            "CLASSIFICATIONID" => $row['CLASSIFICATIONID'],
            "CLASSIFICATIONGROUP" => $row['CLASSIFICATIONGROUP'],
            "PFR_TSRM_WORDER" => $row['PFR_TSRM_WORDER'],
            "SAMPLED_SIT" => $row['PFR_SIT_NAME'] == 'OFFLINE' ? false : empty(db2_fetch_assoc($stmt_TBSM)),
            "SIT_IN_COLLECTION" => array_key_exists($row['PFR_SIT_NAME'], $sits_arr),
            "TRACEROUTE_TITLE" => $trace_title,
            "TRACEROUTE_DATA" => $trace_data,
        );
    }
    db2_close($connection_WHFED);
    db2_close($connection_TBSM);

    $json_arr = array(
        "draw" => $draw,
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        "data" => $data_arr,
        "error" => $error,
        "options" => json_encode($_POST),
        "files" => array(),
    );
    echo json_encode($json_arr, JSON_PRETTY_PRINT);
    exit();
}
