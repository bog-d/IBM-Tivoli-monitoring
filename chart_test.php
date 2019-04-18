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
            width: 1300px;
        }
        #chart-historical {
            width: 900px;
        }
    </style>
</head>
<body>

<table id="chart-operative" cellspacing="20">
    <tr>
        <td>
            <canvas id="graph-operative"></canvas>
        </td>
        <td valign="top">
            <table class='gantt' cellpadding="10">
                <tr>
                    <td class='gantt'>Номер события:</td>
                    <td class='gantt'>65173965</td>
                </tr>
                <tr>
                    <td class='gantt'>Узел:</td>
                    <td class='gantt'>SL10100008126I:LZ</td>
                </tr>
                <tr>
                    <td class='gantt'>Объект:</td>
                    <td class='gantt'>SL10100008126I</td>
                </tr>
                <tr>
                    <td class='gantt'>КЭ:</td>
                    <td class='gantt'>SL10100008126I</td>
                </tr>
                <tr>
                    <td class='gantt'>Код события:</td>
                    <td class='gantt'>LZ_DISK_SPACE_LOW</td>
                </tr>
                <tr>
                    <td class='gantt'>Номера инцидентов:</td>
                    <td class='gantt'>27836293</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table id="chart-historical" cellspacing="20">
    <tr>
        <td>
            <canvas id="graph-historical"></canvas>
        </td>
    </tr>
</table>

</body>
</html>
