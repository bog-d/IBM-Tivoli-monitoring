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

const OFFLINE = 'OFFLINE';

$sel_service = "select distinct SERVICE_NAME 
                from PFR_LOCATIONS 
                where SERVICE_NAME is not null and SERVICE_NAME <> '' and SERVICE_NAME <> 'n/a'";
$stmt_service = db2_prepare($connection_TBSM, $sel_service);
$result_service = db2_execute($stmt_service);

$i = 0;
$chains_arr = [];
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

    $ke_sit_arr = [];
    $simple_ke = '';
    while ($row_chain = db2_fetch_assoc($stmt_chain)) {
        $ke_sit_arr[$row_chain['PFR_KE_TORS']][] = $row_chain['SIT_CODE'];
        if (strpbrk($row_chain['PFR_KE_TORS'], ':.') === false)
            $simple_ke = $row_chain['PFR_KE_TORS'];
    }

    if (!empty($simple_ke) and in_array(OFFLINE, $ke_sit_arr[$simple_ke]) and count($ke_sit_arr) > 1) {
        $sel = "insert into PFR_CORRELATION_CHAIN (PFR_CORRELATION_CHAIN_DESCRIPTION)  
                values ('Все агенты на {$row_service['SERVICE_NAME']}')";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
        $chain_id = db2_last_insert_id($connection_TBSM);

        $j = 0;
        foreach ($ke_sit_arr as $ke => $sit_arr)
            foreach ($sit_arr as $sit) {
                $event_type = (($ke == $simple_ke and $sit == OFFLINE) ? 'm' : 's');
                $sel = "insert into PFR_CORRELATIONS (PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE, PFR_CORRELATION_CHAIN_ID)  
                                            values ('{$ke}', '{$sit}', '{$event_type}', {$chain_id})";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                $j++;
            }
        $i++;
        $chains_arr[$j] = array_key_exists($j, $chains_arr) ? ($chains_arr[$j] + 1) : 1;
    }
}

ksort($chains_arr);
echo "Добавлено цепочек: {$i}<br><br>";
foreach ($chains_arr as $j => $count)
    echo "с кол-вом звеньев {$j}: {$count} шт.<br>";

db2_close($connection_TBSM);
?>
</body>
</html>
