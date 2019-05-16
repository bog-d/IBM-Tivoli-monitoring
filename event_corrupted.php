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
        <title>События, отсутствующие в AEL</title>
    </head>
    <body>
<?php
require_once 'connections/WHFED.php';
require_once 'functions/tbsm.php';

$captions = array (
    "WRITETIME" => 'Время записи',
    "FIRST_OCCURRENCE" => 'Первое вхождение',
    "NODE" => 'Узел',
    "PFR_OBJECT" => 'Объект',
    "PFR_KE_TORS" => 'КЭ',
    "PFR_SIT_NAME" => 'Код события',
    "SEVERITY" => 'Критичность',
);

echo "<table border='1' cellpadding='5'>";
echo "<thead>";
echo "<tr>";
foreach ($captions as $title)
    echo "<th>{$title}</th>";
echo "</tr>";
echo "</thead>";

$sql_WHFED = "with latest (ID, PFR_KE_TORS, PFR_SIT_NAME, SEVERITY) as 
                    (
                    select max(ID), PFR_KE_TORS, PFR_SIT_NAME, case when SEVERITY > 0 then 1 else 0 end
                    from DB2INST1.PFR_EVENT_HISTORY
                    where PFR_KE_TORS <> ''
                    group by PFR_KE_TORS, PFR_SIT_NAME, case when SEVERITY > 0 then 1 else 0 end
                    ) 
                select a1.ID, a1.PFR_KE_TORS, a1.PFR_SIT_NAME
                from LATEST a1, LATEST a2
                where 
                    a1.PFR_KE_TORS = a2.PFR_KE_TORS and
                    a1.PFR_SIT_NAME = a2.PFR_SIT_NAME and
                    a1.ID > a2.ID and
                    a1.SEVERITY <> 0
                order by a1.ID asc";
$stmt_WHFED = db2_prepare($connection_WHFED, $sql_WHFED);
$result_WHFED = db2_execute($stmt_WHFED);

$i = 0;
while ($row_WHFED = db2_fetch_assoc($stmt_WHFED)) {
    if (empty(ael_request_2(array('pfr_ke_tors' => $row_WHFED['PFR_KE_TORS'], 'pfr_sit_name' => $row_WHFED['PFR_SIT_NAME'])))) {
        echo "<tr>";
        $sql = "select * from DB2INST1.PFR_EVENT_HISTORY where ID = {$row_WHFED['ID']}";
        $stmt = db2_prepare($connection_WHFED, $sql);
        $result = db2_execute($stmt);
        $row = db2_fetch_assoc($stmt);

        foreach ($captions as $key => $title)
            echo "<td>{$row[$key]}</td>";
        echo "</tr>";
        $i++;
    }
}

echo "<tfoot>";
echo "<tr>";
echo "<th colspan='0'>Всего: {$i}</th>";
echo "</tr>";
echo "</tfoot>";
echo "</table>";

?>
    </body>
</html>
