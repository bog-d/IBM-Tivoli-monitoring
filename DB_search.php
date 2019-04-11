<?php
/*
	by GDV
	2018 - RedSys

    Run: http://10.103.0.60/pfr_other/DB_search.php?needle=VL09100008004PC&type=VARCHAR&scheme=TPC
    Parameters: needle
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
require_once 'connections/TPCDB.php';

// connection to database
$DB = $connection_TSPC_087;

// get script parameters
if (!isset($_GET['needle']))
    exit("'needle' parameter missed!");
if (!isset($_GET['type']))
    exit("'type' parameter missed!");

$needle = strtoupper($_GET['needle']);
$type = strtoupper($_GET['type']);
$scheme = isset($_GET['scheme']) ? strtoupper($_GET['scheme']) : "";
echo "<h3>Поиск значения {$needle} (тип {$type}) в таблицах БД (".(empty($scheme) ? "все схемы" : "схема {$type}").") без учёта регистра:</h3>";

// tables
$sel = "select distinct TBNAME from SYSIBM.SYSCOLUMNS".(empty($scheme) ? "" : " where TBCREATOR = '{$scheme}'");
$stmt_table = db2_prepare($DB, $sel);
$result = db2_execute($stmt_table);

while($row_table = db2_fetch_assoc($stmt_table)) {
    $table = trim($row_table['TBNAME']);
    $new_table = true;

    // columns
    $sel = "select NAME from SYSIBM.SYSCOLUMNS where TBNAME = '{$row_table['TBNAME']}' and COLTYPE = '{$type}'";
    $stmt_column = db2_prepare($DB, $sel);
    $result = db2_execute($stmt_column);

    while($row_column = db2_fetch_assoc($stmt_column)) {
        $column = trim($row_column['NAME']);

        // values
        $sel = "select \"{$column}\", count(*) as COUNT from {$scheme}.{$table} where upper(\"{$column}\") = '{$needle}' group by \"{$column}\"";
        $stmt = db2_prepare($DB, $sel);
        if ($stmt and @db2_execute($stmt)) {
            while ($row = @db2_fetch_assoc($stmt)) {
                if ($new_table) {
                    echo "{$scheme}.{$table}<br><br>";
                    $new_table = false;
                }
                echo "&emsp;{$column} - {$row['COUNT']} совпадений<br><br>";
            }
        }
    }
}

// database connection close
db2_close($DB);

?>
</body>
</html>
