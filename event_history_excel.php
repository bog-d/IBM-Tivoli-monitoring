<?php
/*
	by GDV
	2019 - RedSys
*/
if (!isset($_GET['data']))
    exit();
if (empty(($data = json_decode($_GET['data'], true))))
    exit();
if (!array_key_exists('options', $data))
    exit();
$data = json_decode($data['options'], true);


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
$aSheet->setTitle('Журнал событий');
$pExcel->getDefaultStyle()->getFont()->setName('Arial');
$pExcel->getDefaultStyle()->getFont()->setSize(8);
include 'css/excel.php';

$table_titles = array (
    "Время записи",
    "Срабатывание ситуации",
    "Номер",
    "Отделение",
    "Узел",
    "Объект",
    "КЭ",
    "Код события в ТОРС",
    "Критичность",
    "Номер инцидента",
    "Класс",
    "Номер классификации",
    "Группа классификации",
    "Номер РЗ",
);
foreach ($table_titles as $key => $col)
    $aSheet->setCellValueByColumnAndRow($key, 1, $col);
$aSheet->getStyle('A1:N1')->applyFromArray($style_header);

$fields = array (
    '' => '',
    'WRITETIME' => '',
    'FIRST_OCCURRENCE' => '',
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

$path_history = [ ];		// array for history tree from parents to childs
$results = [ ];				// array for endpoint childs
$search_arr[] = "1 = 1";

if (!empty($data['search']['value'])) {
    $sql = "select SERVICEINSTANCEID from TBSMBASE.SERVICEINSTANCE where SERVICEINSTANCENAME = '{$data['search']['value']}'";
    $stmt_TBSM = db2_prepare($connection_TBSM, $sql);
    $result_TBSM = db2_execute($stmt_TBSM);
    $row = db2_fetch_assoc($stmt_TBSM);
    if (empty($row['SERVICEINSTANCEID']))
        $search_arr[] = "1 = 0";
    else {
        if (!ext_tree($row['SERVICEINSTANCEID'], $connection_TBSM, 0, $path_history))
            $results[0]['service'] = $data['search']['value'];

        $services_str = implode("', '", array_column($results, 'service'));

        $sel_TBSM = "SELECT PFR_KE_TORS FROM DB2INST1.PFR_LOCATIONS WHERE SERVICE_NAME in ('{$services_str}')";
        $stmt_TBSM = db2_prepare($connection_TBSM, $sel_TBSM);
        $result_TBSM = db2_execute($stmt_TBSM);
        while ($row = db2_fetch_assoc($stmt_TBSM))
            $ke_obj[] = $row['PFR_KE_TORS'];

        $ke_obj = array_unique($ke_obj);
        $search_arr[] = "PFR_KE_TORS in ('".(implode("', '", $ke_obj))."')";
    }
}

foreach($data['columns'] as $i) {
    $field = $i['data'];
    $search = $i['search']['value'];
    if ($search != '')
        if ($field == 'WRITETIME' or $field == 'FIRST_OCCURRENCE') {
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

$sql = "select row_number() over ( order by ".array_keys($fields)[$data['order'][0]['column']]." ".$data['order'][0]['dir'].") AS N, 
                      ID, WRITETIME, FIRST_OCCURRENCE, SERIAL, PFR_TORG, NODE, PFR_OBJECT, PFR_KE_TORS, PFR_SIT_NAME, DESCRIPTION, SEVERITY, TTNUMBER, PFR_TSRM_CLASS, CLASSIFICATIONID, CLASSIFICATIONGROUP, PFR_TSRM_WORDER
                  from DB2INST1.PFR_EVENT_HISTORY
                  where {$search_string}";
$stmt_WHFED = db2_prepare($connection_WHFED, $sql);
$result_WHFED = db2_execute($stmt_WHFED);

$i = 0;
while ($row = db2_fetch_assoc($stmt_WHFED)) {
    $i++;
    $col = 0;
    foreach ($row as $key => $cell) {
        switch ($key) {
            case "N":
            case "ID":
            case "DESCRIPTION":
                break;
            case "WRITETIME":
            case "FIRST_OCCURRENCE":
                $time_output = substr($cell, 8, 2) . '.' . substr($cell, 5, 2) . '.' . substr($cell, 0, 4) . ' ' . substr($cell, 11);
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $time_output);
                break;
            case "SEVERITY":
                switch ($cell) {
                    case "5":
                        $class = "red_status";
                        break;
                    case "4":
                    case "3":
                    case "2":
                        $class = "yellow_status";
                        break;
                    case "1":
                        $class = "blue_status";
                        break;
                    case "0":
                        $class = "green_status";
                        break;
                    default:
                        $class = "";
                        break;
                }
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, array_search($cell, $severity_codes));
                if (!empty($class))
                    $aSheet->getStyle('I' . ($i + 1) . ':I' . ($i + 1))->applyFromArray($$class);
                break;
            case "TTNUMBER":
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                break;
            case "PFR_TSRM_CLASS":
                $class = ($cell == "-30" or $cell == "-10" or $cell == "3" or $cell == "4") ? "blue_status" : "";
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $class_codes[$cell]);
                if (!empty($class))
                    $aSheet->getStyle('K' . ($i + 1) . ':K' . ($i + 1))->applyFromArray($$class);
                break;
            case 'PFR_KE_TORS':
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                break;
            case 'PFR_SIT_NAME':
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                break;
            default:
                $aSheet->setCellValueByColumnAndRow($col++, $i + 1, $cell);
                break;
        }
    }
}

// columns width autofit
$cellIterator = $aSheet->getRowIterator()->current()->getCellIterator();
$cellIterator->setIterateOnlyExistingCells(true);
foreach ($cellIterator as $cell)
    $aSheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
// insert, format and freeze title and header rows
$aSheet->insertNewRowBefore(1);
$aSheet->setCellValueByColumnAndRow(0, 1, 'Журнал событий мониторинга');
$aSheet->mergeCells('A1:N1');
$aSheet->getStyle('A1:A1')->applyFromArray($style_title);
$aSheet->freezePane('A3');
// save Excel book
header('Content-Type:xlsx:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition:attachment;filename="event_history.xlsx"');
$objWriter = new PHPExcel_Writer_Excel2007($pExcel);
$objWriter->save('php://output');

// databases connections close
db2_close($connection_TBSM);
db2_close($connection_WHFED);
exit();
