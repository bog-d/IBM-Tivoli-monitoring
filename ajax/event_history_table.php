<?php
$fields = array (
    '' => '',
    'WRITETIME' => '',
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
    include '../functions/tbsm.php';

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
            ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, 0, $path_history);
            $search_arr[] = "PFR_OBJECT in ('".(implode("', '", array_column($results, 'service')))."')";
        }
    }

    foreach($_POST['columns'] as $i) {
        $field = $i['data'];
        $search = $i['search']['value'];
        if ($search != '')
            if ($field == 'WRITETIME') {
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
    $end_pos = $_POST['start'] + $_POST['length'];
    $sql = "select * from
              (select row_number() over ( order by ".array_keys($fields)[$_POST['order'][0]['column']]." ".$_POST['order'][0]['dir'].") AS N, 
                      ID, WRITETIME, SERIAL, PFR_TORG, NODE, PFR_OBJECT, PFR_KE_TORS, PFR_SIT_NAME, DESCRIPTION, SEVERITY, TTNUMBER, PFR_TSRM_CLASS, CLASSIFICATIONID, CLASSIFICATIONGROUP, PFR_TSRM_WORDER
              from DB2INST1.PFR_EVENT_HISTORY
              where {$search_string}) as t
            where t.N between {$start_pos} and {$end_pos}";
    $stmt_WHFED = db2_prepare($connection_WHFED, $sql);
    $result_WHFED = db2_execute($stmt_WHFED);

    while ($row = db2_fetch_assoc($stmt_WHFED)) {
        $data_arr[] = array(
            "DT_RowId" => "row_{$row['ID']}",
            "WRITETIME" => substr($row['WRITETIME'], 0, 19),
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
        );
    }
    db2_close($connection_WHFED);

    $json_arr = array(
        "draw" => $draw,
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        "data" => $data_arr,
        "error" => $error,
        "options" => array(),
        "files" => array(),
    );
    echo json_encode($json_arr, JSON_PRETTY_PRINT);
    exit();
}
