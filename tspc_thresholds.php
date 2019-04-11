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
</head>
<body>
<?php
require_once 'connections/TBSM.php';
require_once 'connections/TPCDB.php';

$DB = $connection_TSPC_087;
$REGION = '091';

$resource_arr = [];
$table_arr = [];
$severity_array = array (
    'I' => 'Informational',
    'W' => 'Warning',
    'C' => 'Critical',
);

$sel_TSPC = "select
                ra.RESOURCE_ID,
                rc.PROPERTY_NAME,
                rc.OPERATOR,
                rc.THRESHOLD_VALUE,
                ad.SEVERITY_LEVEL
            from tpc.T_RULE_CONSTRAINT rc
                join tpc.T_ALERT_DEFINITION ad 
                on rc.RULE_ID = ad.ALERT_ID
                    join tpc.T_RES_ATTRIBUTE ra 
                    on rc.RULE_ID = ra.ATTRIBUTE_ID
            where ad.ENABLED = 1 and ad.TEC_EVENT = 1
            order by RESOURCE_ID asc";
$stmt_TSPC = db2_prepare($DB, $sel_TSPC);
$result_TSPC = db2_execute($stmt_TSPC);

while ($row = db2_fetch_assoc($stmt_TSPC)) {
    $resource_arr[$row['RESOURCE_ID']][] = array(
        'PROPERTY' => "{$row['PROPERTY_NAME']} {$row['OPERATOR']} {$row['THRESHOLD_VALUE']}",
        'SEVERITY' => $row['SEVERITY_LEVEL'],
    );
}

$sel_TBSM = "select PFR_NAZN, NODE, PFR_KE_TORS from DB2INST1.PFR_LOCATIONS where PFR_ID_TORG = '{$REGION}' and SUBCATEGORY like 'TSPC%'";
$stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
$result = db2_execute($stmt_TBSM);

while ($row_node = db2_fetch_assoc($stmt_TBSM)) {
    resource_type_extract("SWITCH_ID", "T_RES_SWITCH", "LOGICAL_NAME");                         // SAN-коммутаторы
    resource_type_extract("SUBSYSTEM_ID", "T_RES_STORAGE_SUBSYSTEM", "USER_PROVIDED_NAME");     // СХД
    resource_type_extract("COMPUTER_ID", "T_RES_HOST", "HOST_NAME");                            // Сервер TSPC
    //resource_type_extract("OBJECT_ID", "T_RES_RU_COMPUTER", "HOST_NAME");                                   // Сервер TSPC
    resource_type_extract("COMPUTER_ID", "T_RES_HOST", "ORIGINAL_ALIAS");                       // Сервер TSM
    //resource_type_extract("ID", "T_SERVER", "AGENT_ORIGINAL_ALIAS");                                        // Сервер TSM
}

echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr>";
        echo "<th>КЭ</th>";
        echo "<th>Тип</th>";
        echo "<th>Порог</th>";
        echo "<th>Критичность</th>";
    echo "<tr>";
    foreach ($table_arr as $rec) {
        echo "<tr>";
        foreach ($rec as $col) {
            echo "<td>{$col}</td>";
        }
        echo "</tr>";
    }
echo "</table>";

// database connection close
db2_close($DB);
db2_close($connection_TBSM);


function resource_type_extract($id, $table, $name) {
    $sel = "select $id from TPC.{$table} where upper($name) = '{$GLOBALS['row_node']['NODE']}'";
    $stmt = db2_prepare($GLOBALS['DB'], $sel);
    $result = db2_execute($stmt);

    while ($row = db2_fetch_assoc($stmt)) {
        if (array_key_exists($row[$id], $GLOBALS['resource_arr']))
            foreach ($GLOBALS['resource_arr'][$row[$id]] as $val)
                $GLOBALS['table_arr'][] = array(
                    'ke' => $GLOBALS['row_node']['PFR_KE_TORS'],
                    'type' => $GLOBALS['row_node']['PFR_NAZN'],
                    'property' => $val['PROPERTY'],
                    'severity' => $GLOBALS['severity_array'][$val['SEVERITY']],
                );
    }
}

?>
</body>
</html>
