<?php
/*
	by GDV
	2019 - RedSys
*/
if (!isset($_GET['ServiceName']) or !isset($_GET['Time']))
    exit('Отсутствует необходимый параметр.');
$ServiceName = $_GET['ServiceName'];
$Time = $_GET['Time'];

require_once 'connections/TBSM.php';
require_once 'connections/WHFED.php';
require_once 'functions/tbsm.php';

require_once 'Classes/PHPExcel.php';
$pExcel = new PHPExcel();
$pExcel->setActiveSheetIndex(0);
$aSheet = $pExcel->getActiveSheet();
$aSheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$aSheet->getPageSetup()->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
$aSheet->getPageMargins()->setTop(1);
$aSheet->getPageMargins()->setRight(0.75);
$aSheet->getPageMargins()->setLeft(0.75);
$aSheet->getPageMargins()->setBottom(1);
$aSheet->setTitle('Выгрузка из Журнала событий');
$pExcel->getDefaultStyle()->getFont()->setName('Arial');
$pExcel->getDefaultStyle()->getFont()->setSize(8);
include 'css/excel.php';

$table_titles = array (
    "Номер инцидента",
    "Время открытия",
    "Время закрытия",
    "Продолжительность (мин.)",
);

foreach ($table_titles as $key => $col)
    $aSheet->setCellValueByColumnAndRow($key, 1, $col);
$aSheet->getStyle('A1:D1')->applyFromArray($style_header);

$path_history = [ ];		// array for history tree from parents to childs
$results = [ ];				// array for endpoint childs

$sql = "select SERVICEINSTANCEID from TBSMBASE.SERVICEINSTANCE where SERVICEINSTANCENAME = '{$ServiceName}'";
$stmt_TBSM = db2_prepare($connection_TBSM, $sql);
$result_TBSM = db2_execute($stmt_TBSM);
$row = db2_fetch_assoc($stmt_TBSM);
if (empty($row['SERVICEINSTANCEID']))
    exit('Сервис не найден.');
else {
    if (!ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, 0, $path_history))
        $results[0]['service'] = $ServiceName;

    $services_str = implode("', '", array_column($results, 'service'));

    $sel_TBSM = "SELECT PFR_KE_TORS FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME in ('{$services_str}')";
    $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
    $result_TBSM = db2_execute($stmt_TBSM);
    while ($row = db2_fetch_assoc($stmt_TBSM))
        $ke_obj[] = $row['PFR_KE_TORS'];

    $ke_obj = array_unique($ke_obj);
    $search_string = "'".(implode("', '", $ke_obj))."'";
}

$sql = "select TTNUMBER, TIME_OPEN, TIME_CLOSE, 
            TIMESTAMPDIFF(4, CHAR(TIMESTAMP(TIME_CLOSE) - TIMESTAMP(TIME_OPEN))) as DURATION_MIN
        from (
            select TTNUMBER, min(TIME_OPEN) as TIME_OPEN, max(TIME_CLOSE) as TIME_CLOSE
            from (
                    select a1.TTNUMBER as TTNUMBER,
                           a1.SERIAL as SERIAL,
                           a2.WRITETIME as TIME_OPEN,
                           a1.WRITETIME as TIME_CLOSE
                    from DB2INST1.PFR_EVENT_HISTORY a1, DB2INST1.PFR_EVENT_HISTORY a2
                    where a1.ID > a2.ID and a1.SERIAL = a2.SERIAL and a1.TTNUMBER = a2.TTNUMBER and 
                          a1.TTNUMBER <> '' and a1.SEVERITY = 0 and a2.SEVERITY > 0 and
                          a1.PFR_KE_TORS in ({$search_string})
                        union all
                    select a2.TTNUMBER as TTNUMBER,
                           a2.SERIAL as SERIAL,
                           a2.WRITETIME as TIME_OPEN,
                           to_char(current timestamp, 'YYYY-MM-DD HH24:MI:SS') as TIME_CLOSE
                    from DB2INST1.PFR_EVENT_HISTORY a2
                    where a2.SEVERITY > 0 and a2.TTNUMBER <> '' and a2.PFR_KE_TORS in ({$search_string}) and
                        not exists (
                            select *
                            from DB2INST1.PFR_EVENT_HISTORY a1
                            where a1.ID > a2.ID and a1.SERIAL = a2.SERIAL and a1.TTNUMBER = a2.TTNUMBER and a1.SEVERITY = 0
                        )
            )
            group by TTNUMBER
        )
        where TIMESTAMPDIFF(4, CHAR(TIMESTAMP(TIME_CLOSE) - TIMESTAMP(TIME_OPEN))) >= {$Time}";
$stmt_WHFED = db2_prepare($connection_WHFED, $sql);
$result_WHFED = db2_execute($stmt_WHFED);

$i = 0;
while ($row = db2_fetch_assoc($stmt_WHFED)) {
    $i++;
    $col = 0;
    foreach ($row as $key => $cell)
        $aSheet->setCellValueByColumnAndRow($col++, $i + 1, ($key != 'TIME_CLOSE' or strpos($cell, '.000')) ? $cell : '');
}

// columns width autofit
$cellIterator = $aSheet->getRowIterator()->current()->getCellIterator();
$cellIterator->setIterateOnlyExistingCells(true);
foreach ($cellIterator as $cell)
    $aSheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
// insert, format and freeze title and header rows
$aSheet->insertNewRowBefore(1);
$aSheet->insertNewRowBefore(1);
$aSheet->setCellValueByColumnAndRow(0, 1, "Индикатор/подсистема: {$ServiceName}");
$aSheet->setCellValueByColumnAndRow(0, 2, "Время жизни инцидентов более {$Time} мин.");
$aSheet->mergeCells('A1:D1');
$aSheet->mergeCells('A2:D2');
$aSheet->getStyle('A1:A2')->applyFromArray($style_title);
$aSheet->freezePane('A4');
// save Excel book
header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:attachment;filename="event_history_inc.xlsx"');
$objWriter = new PHPExcel_Writer_Excel2007($pExcel);
$objWriter->save('php://output');

// databases connections close
db2_close($connection_TBSM);
db2_close($connection_WHFED);
exit();
