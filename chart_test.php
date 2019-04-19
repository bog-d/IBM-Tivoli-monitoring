<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="Chart/Chart.bundle.min.js"></script>
    <script src="Chart/chartjs-plugin-annotation.js"></script>
    <script src="scripts/chart_test.js"></script>

    <title>Creating Dynamic Data Graph using PHP and Chart.js</title>

    <link rel="stylesheet" type="text/css" href="Chart/Chart.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">

    <style type="text/css">
        #chart-operative {
            width: 1800px;
        }
        #chart-historical {
            width: 1000px;
        }
    </style>
</head>
<body>

<table id="chart-operative" width="100%" cellpadding="30">
    <tr>
        <td>
            <canvas id="graph-operative"></canvas>
        </td>
        <td align="center" width="10%">
            Масштаб:
            <br><br>
            <button id="scale_plus" title="Увеличить масштаб..."><b>&nbsp;<font size="+1>">+</font>&nbsp;</b></button>&emsp;
            <button id="scale_minus" title="Уменьшить масштаб..."><b>&nbsp;<font size="+1>">-</font>&nbsp;</b></button>
            <br><br><br><br>
            История:
            <br><br>
            <button id="prev" title="Назад, в прошлое..."><b>&nbsp;<font size="+1>"><</font>&nbsp;</b></button>&emsp;
            <button id="next" title="Вперёд, в будущее..."><b>&nbsp;<font size="+1>">></font>&nbsp;</b></button>
        </td>
        <td>
            <table class='gantt ael_hide' cellpadding="10">
                <tr>
                    <td class='gantt'>Код события:</td>
                    <td class='gantt'>LZ_DISK_SPACE_LOW</td>
                </tr>
                <tr>
                    <td class='gantt'>Узел:</td>
                    <td class='gantt'><input id="inp_node" value="SL10100011030I:LZ"></td>
                </tr>
                <tr>
                    <td class='gantt'>Время:</td>
                    <td class='gantt'><input type="datetime-local" id="inp_time" value="2019-04-16T07:28"></td>
                </tr>
                <tr>
                    <td class='gantt' colspan="2" align="center"><button id="view">Посмотреть</button></td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table id="chart-historical" cellpadding="20">
    <tr>
        <td>
            <canvas id="graph-historical"></canvas>
        </td>
    </tr>
</table>

</body>
</html>
