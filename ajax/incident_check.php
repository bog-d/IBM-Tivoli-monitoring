<?php
/*var_dump($_POST);*/
// AJAX request and response
if (isset($_POST)) {
    require_once '../connections/MAXDB76.php';

    // incidents parsing
    $inc_str = str_replace(array(" ", ",", ";"), "\r\n", $_POST['value']);
    $inc_arr = array_unique(array_filter(explode("\r\n", $inc_str)));
    asort($inc_arr);

    // two-dimensional array create
    $incident_arr_2 = [];
    foreach ($inc_arr as $inc_number)
        $incident_arr_2[] = array(
            'number' => $inc_number,
            'result' => '',
        );
    unset($inc_arr);

    // incidents check
    foreach ($incident_arr_2 as &$value) {
        $sel = "select inc.status, l_synonymdomain.description
                from MAXIMO.ticket inc
                left join MAXIMO.synonymdomain  synonymdomain on inc.status = synonymdomain.value and synonymdomain.domainid = 'INCIDENTSTATUS'
                left join MAXIMO.l_synonymdomain l_synonymdomain on synonymdomain.synonymdomainid = l_synonymdomain.ownerid
                where inc.class = 'INCIDENT' and inc.ticketid = '{$value['number']}'";
        $stmt = db2_prepare($connection_SCCD, $sel);
        $value['result'] = (db2_execute($stmt) and !empty($row = db2_fetch_assoc($stmt))) ? "{$row['STATUS']} ({$row['DESCRIPTION']})" : "Инцидент не найден в БД.";
    }
    unset($value);

    db2_close($connection_SCCD);

    echo json_encode($incident_arr_2);
    exit();
}
