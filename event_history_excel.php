<?php
if (!isset($_GET['sql']))
    exit();
if (empty(($sql_arr = json_decode($_GET['sql'], true))))
    exit();
if (!array_key_exists('options', $sql_arr))
    exit();
$sql = trim(str_replace("\\r\\n", " ", $sql_arr['options']), "\"");

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

$stmt_WHFED = db2_prepare($connection_WHFED, $sql);
$result_WHFED = db2_execute($stmt_WHFED);

foreach ($table_titles as $key => $col)
    $aSheet->setCellValueByColumnAndRow($key, 1, $col);
$aSheet->getStyle('A1:N1')->applyFromArray($style_header);

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
db2_close($connection_WHFED);
exit();
