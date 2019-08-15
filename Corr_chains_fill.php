<?php
/*
	by GDV
	2019 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Наполнитель корреляционных цепочек мониторинга</title>
</head>
<body>
<?php
require_once 'connections/TBSM.php';
require_once 'functions/corr_chains.php';

const OFFLINE = 'OFFLINE';
$chains_arr = array (
    'add'       => 0,
    'update'    => 0,
    'exist'     => 0,
);

// all service names
$sel_service = "select distinct SERVICE_NAME 
                from PFR_LOCATIONS 
                where SERVICE_NAME is not null and SERVICE_NAME <> '' and SERVICE_NAME <> 'n/a'";
$stmt_service = db2_prepare($connection_TBSM, $sel_service);
$result_service = db2_execute($stmt_service);

// ke and situations for each service name
while ($row_service = db2_fetch_assoc($stmt_service)) {
    $sel_chain = "select distinct PFR_KE_TORS, SIT_CODE 
                    from PFR_TEMS_SIT_AGGR
                    where SIT_CODE = '". OFFLINE ."' and PFR_KE_TORS in (
                        select distinct PFR_KE_TORS
                        from PFR_LOCATIONS
                        where SERVICE_NAME = '{$row_service['SERVICE_NAME']}'
                    )";
    $stmt_chain = db2_prepare($connection_TBSM, $sel_chain);
    $result_chain = db2_execute($stmt_chain);

    // fill array with ke and situations
    $ke_sit_arr = [];
    $simple_ke = '';
    while ($row_chain = db2_fetch_assoc($stmt_chain)) {
        $ke_sit_arr[$row_chain['PFR_KE_TORS']][] = $row_chain['SIT_CODE'];
        if (strpbrk($row_chain['PFR_KE_TORS'], ':.') === false)
            $simple_ke = $row_chain['PFR_KE_TORS'];
    }

    if (!empty($simple_ke) and in_array(OFFLINE, $ke_sit_arr[$simple_ke]) and count($ke_sit_arr) > 1) {
        $state = check_and_save_chain("Все агенты на {$row_service['SERVICE_NAME']}", $ke_sit_arr, $simple_ke, OFFLINE);
        $chains_arr[$state]++;
    }
}

echo "Добавлено цепочек: {$chains_arr['add']}<br>";
echo "Обновлено цепочек: {$chains_arr['update']}<br>";
echo "Существующих цепочек: {$chains_arr['exist']}<br>";

db2_close($connection_TBSM);
?>
</body>
</html>
