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
    <link href="css/popup.css" type="text/css" rel="stylesheet">
    <link href="DataTables/datatables.min.css" type="text/css" rel="stylesheet" />
    <link href="DataTables/jquery-ui.css" type="text/css" rel="stylesheet" />
    <link href="DataTables/DataTables-1.10.18/css/dataTables.jqueryui.min.css" type="text/css" rel="stylesheet" />
    <title>Журнал событий мониторинга</title>
    <script type="text/javascript" src="DataTables/datatables.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/event_history_new.js"></script>
    <script src="scripts/cellSelection.min.js"></script>

    <script src="Chart/Chart.bundle.min.js"></script>
    <script src="Chart/chartjs-plugin-annotation.js"></script>
    <script src="scripts/chart_show.js"></script>
    <link rel="stylesheet" type="text/css" href="Chart/Chart.min.css">

    <style>
        td.details-control {
            background: url('images/details_open.png') no-repeat center center;
            cursor: pointer;
        }
        tr.shown td.details-control {
            background: url('images/details_close.png') no-repeat center center;
        }
    </style>
</head>
<body>
<?php
$captions = array (
    "" => 'png',
    "Время записи" => 'input*2',
    "Номер" => 'input',
    "Отделение" => 'input',
    "Узел" => 'input',
    "Объект" => 'input',
    "КЭ" => 'input',
    "Код события в ТОРС / график события" => 'input',
//    "Описание" => '',
    "Критичность" => 'select',
    "Номер инцидента" => 'input',
    "Класс" => 'select',
    "Номер класс." => '',
    "Группа класс." => '',
    "Номер РЗ" => '',
);

echo "<table width='100%' border='0' cellspacing='0' cellpadding='5' class='page_title'>";
    echo "<tr>";
        echo "<td width='20%' align='left' rowspan='0' class='page_title_dark'>";
        echo "</td>";
        echo "<td align='center'>";
            echo "<h3>Журнал событий мониторинга</h3>";
        echo "</td>";
        echo "<td width='25%' align='right' rowspan='0'>";
            $param = (isset($_GET["ServiceName"]) ? "ServiceName={$_GET["ServiceName"]}&" : "") .
                     (isset($_GET["PTK"]) ? "ServiceName={$_GET["PTK"]}&" : "") .
                     (isset($_GET["KE_OBJECT"]) ? "KE_OBJECT={$_GET["KE_OBJECT"]}&" : "") .
                     (isset($_GET["INCIDENT"]) ? "INCIDENT={$_GET["INCIDENT"]}&" : "");
            echo "<a href='http://10.103.0.60/pfr_other/event_history.php?{$param}' target='_blank' title='Перейти к старой версии журнала событий'>Перейти к старой версии</a><br><br><br>";
            echo "<a class='open_window' href='#' title='Справка по функционалу журнала событий'><img src='images/help.png' hspace='5' height='24' width='24' align='middle' title='Справка по функционалу'>Справка</a>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td align='center'>";
            echo "за 2019 год";
            echo "<br><br><div id='wait'></div>";
        echo "</td>";
    echo "</tr>";
    echo "<tr>";
        echo "<td align='center'>";
        echo "</td>";
    echo "</tr>";
echo "</table><br><br>";

echo "<table id='events' class='display compact hover' width='100%'>";
    echo "<thead>";
        echo "<tr>";
            foreach ($captions as $key => $type)
                echo "<th>{$key}</th>";
        echo "</tr>";
    echo "</thead>";
    echo "<tfoot>";
        echo "<tr>";
            foreach ($captions as $key => $type) {
                switch ($key) {
                    case '':
                        $value = isset($_GET["PTK"]) ? $_GET["PTK"] : "";
                        break;
                    case 'Номер':
                        $value = isset($_GET["Serial"]) ? $_GET["Serial"] : "";
                        break;
                    case 'Объект':
                        $value = isset($_GET["ServiceName"]) ? $_GET["ServiceName"] : "";
                        break;
                    case 'КЭ':
                        $value = isset($_GET["KE_OBJECT"]) ? $_GET["KE_OBJECT"] : "";
                        break;
                    case 'Номер инцидента':
                        $value = isset($_GET["INCIDENT"]) ? $_GET["INCIDENT"] : "";
                        break;
                    default:
                        $value = '';
                        break;
                }
                switch ($type) {
                    case 'png':
                        echo "<th><img class='click' src='images/details_open.png' id='details' title='Развернуть все детали' style='margin-left: 10px;'>
                                <input type='text' id='ptk' value='{$value}' hidden /></th>";
                        break;
                    case 'input':
                        echo "<th><input type='text' placeholder='Поиск...' value='{$value}' /></th>";
                        break;
                    case 'input*2':
                        echo "<th><input type='date' id='start' placeholder='ГГГГ-ММ-ДД' value='{$value}' title='Начало диапазона времени'/><br>
                                              <input type='date' id='finish' placeholder='ГГГГ-ММ-ДД' value='{$value}' title='Конец диапазона времени'/></th>";
                        break;
                    case 'select':
                        echo "<th><select><option value=''>Все</option></select></th>";
                        break;
                    default:
                        echo "<th></th>";
                        break;
                }
            }
        echo "</tr>";
    echo "</tfoot>";
echo "</table>";

?>
<br/><br/>
<table align="center" style="width: 100%;">
<!--    <caption>График метрики мониторинга с отметками по сработавшей ситуации</caption>-->
    <tr>
        <td rowspan="2">
            <canvas id="graph-operative"></canvas>
        </td>
        <td>
            <table class="ael_hide" cellpadding="10">
                <tr>
                    <td rowspan="5" style="border: 2px solid; border-right-color: #FF00FF; border-bottom-color: #FFFFFF; border-left-color: #FFFFFF; border-top-color: #FFFFFF;">&nbsp;</td>
                    <td rowspan="4" style="border: 2px solid; border-right-color: #FF0000; border-bottom-color: #FFFFFF; border-left-color: #FFFFFF; border-top-color: #FFFFFF;">&nbsp;</td>
                    <td rowspan="3" style="border: 2px solid; border-right-color: #54C1F0; border-bottom-color: #FFFFFF; border-left-color: #FFFFFF; border-top-color: #FFFFFF;">&nbsp;</td>
                    <td rowspan="2" style="border: 2px solid; border-right-color: #00b050; border-bottom-color: #FFFFFF; border-left-color: #FFFFFF; border-top-color: #FFFFFF;">&nbsp;</td>
                    <td></td>
                    <th colspan="2" id="serial"></th>
                </tr>
                <tr>
                    <td align="right">--></td>
                    <td>Закрытие ситуации:</td>
                    <td id="close_sit"></td>
                </tr>
                <tr>
                    <td colspan="2" align="right">--------></td>
                    <td>Создание инцидента:</td>
                    <td id="inc_create"></td>
                </tr>
                <tr>
                    <td colspan="3" align="right">-----------></td>
                    <td>Срабатывание ситуации:</td>
                    <td id="first_occurrence"></td>
                </tr>
                <tr>
                    <td colspan="4" align="right">-----------------></td>
                    <td>Горизонт события:</td>
                    <td id="start_sit"></td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td>Частота опроса:</td>
                    <td id="frequency"></td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td>Кол-во последовательных проверок:</td>
                    <td id="checks"></td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td align="center">
            <table class="ael_hide gantt" cellpadding="10">
                <tr>
                    <th>Управление графиком</th>
                </tr>
                <tr>
                    <td align="center">
                        <button id="prev" title="Назад, в прошлое..."><b>&nbsp;<font size="+1>"><</font>&nbsp;</b></button>&emsp;
                        <button id="scale_plus" title="Увеличить масштаб..."><b>&nbsp;<font size="+1>">+</font>&nbsp;</b></button>&emsp;
                        <button id="scale_minus" title="Уменьшить масштаб..."><b>&nbsp;<font size="+1>">-</font>&nbsp;</b></button>&emsp;
                        <button id="next" title="Вперёд, в будущее..."><b>&nbsp;<font size="+1>">></font>&nbsp;</b></button>
                    </td>
                </tr>
                <tr>
                    <td align="center"><button id="close" title="Убрать график">Закрыть</button></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- pop-up help window -->
<div class="overlay" title=""></div>
<div class="popup">
    <div class="close_window">x</div>
    В новом табличном виде журнала событий реализован следующий функционал:
    <br><br>
    <table border="1" cellspacing="0" cellpadding="10">
        <tr>
            <th align="center">Функция</th>
            <th align="center">Firefox</th>
            <th align="center">Другие браузеры</th>
        </tr>
        <tr>
            <td>Разбиение на страницы с выбранным количеством строк</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Показ/скрытие выбранных столбцов</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Копирование страницы в буфер обмена</td>
            <td align="center">не поддерживается</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Копирование страницы в Excel</td>
            <td align="center">не поддерживается</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Печать страницы</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Сортировка по столбцу времени</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Изменение порядка столбцов</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Поиск в пределах заданной подсистемы</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Одновременная фильтрация по определённым столбцам</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Фильтрация по диапазону дат</td>
            <td align="center">даты следует вводить в формате ГГГГ-ММ-ДД</td>
            <td align="center">даты выбираются из встроенного календаря</td>
        </tr>
        <tr>
            <td>Дополнительная информация по строкам</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Показ общего и отфильтрованного количества строк</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
        <tr>
            <td>Индикация перехода по страницам</td>
            <td align="center">+</td>
            <td align="center">+</td>
        </tr>
    </table>
</div>

</body>
</html>
