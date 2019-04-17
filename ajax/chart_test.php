<?php
header('Content-Type: application/json');

require_once('../connections/WHFED.php');

$data = array();

$sqlQuery = "select WRITETIME, SEVERITY from DB2INST1.PFR_EVENT_HISTORY where PFR_OBJECT = 'SL10100008126I' order by WRITETIME asc";
$stmt = db2_prepare($connection_WHFED, $sqlQuery);
$result = db2_execute($stmt);

while ($row = db2_fetch_assoc($stmt))
    $data[] = $row;

db2_close($connection_WHFED);
echo json_encode($data);
?>
