<?php
/*var_dump($_POST);*/
// AJAX request and response
if (isset($_POST)) {
    include 'functions/tbsm.php';

    $reason_arr = array (
        '0' => 'Свой текст...',
        '1' => 'В связи с технологическими работами на КЭ',
    );

    // reason parsing
    $reason_str = $_POST['variant'] == '0' ? $_POST['text'] : $reason_arr[$_POST['variant']];

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

    // incidents close
    foreach ($incident_arr_2 as &$value) {
        exec("curl -X POST -d '<UpdateINCIDENT xmlns=\"http://www.ibm.com/maximo\" creationDateTime=\"" . date('c') . "\" transLanguage=\"EN\" messageID=\"123\" maximoVersion=\"7.5\"> <INCIDENTSet><INCIDENT action=\"Change\"><STATUS><![CDATA[CLOSED]]></STATUS><TICKETID>{$value['number']}</TICKETID><WORKLOG action=\"Add\"><CREATEDATE>" . date('c') . "</CREATEDATE><DESCRIPTION_LONGDESCRIPTION><![CDATA[{$reason_str}]]></DESCRIPTION_LONGDESCRIPTION></WORKLOG></INCIDENT></INCIDENTSet></UpdateINCIDENT>' http://tivoli:12345678@{$SCCD_server}/meaweb/es/ITM/INCIDENTUpdate", $arr_AELoff);
/*        if (strpos($arr_AELoff[0], 'record does not exist in the database') !== false)
            $value['result'] = "Инцидент не существует в БД.";
        else if (strpos($arr_AELoff[0], 'is in history and must remain unchanged') !== false)
            $value['result'] = "Инцидент уже заархивирован.";
        else */if (strpos($arr_AELoff[0], $inc_number) !== false)
            $value['result'] = "Инцидент успешно закрыт.";
        else
            $value['result'] = "{$arr_AELoff[0]}.";
    }
    unset($value);

    echo json_encode($incident_arr_2);
    exit();
}
