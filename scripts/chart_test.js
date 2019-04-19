var lineGraph;
var bubbleGraph;

$scale = 3;
$time_shift = 0;
$inp_node = '';
$inp_time = '';

$(document).ready(function () {
    if (!$inp_node) {
        $inp_node = $('input#inp_node').val();
        $inp_time = $('input#inp_time').val();
        showGraph_operative();
        showGraph_historical();
        $('table.ael_hide').toggle(function () {
            $(this).animate('slow');
        });
    }
});

$(function() {
    $('button#view').click(function() {
        $time_shift = 0;
        $inp_node = $('input#inp_node').val();
        $inp_time = $('input#inp_time').val();
        showGraph_operative();
        showGraph_historical();
    });
});

$(function() {
    $('button#prev').click(function() {
        $time_shift = $time_shift - 1;
        showGraph_operative();
    });
});

$(function() {
    $('button#next').click(function() {
        $time_shift = $time_shift + 1;
        showGraph_operative();
    });
});

$(function() {
    $('button#scale_plus').click(function() {
        if ($scale < 5) {
            $scale = $scale + 1;
            $('button#scale_minus').prop("disabled", false);
        }
        else
            $(this).prop("disabled", true);
        showGraph_operative();
    });
});

$(function() {
    $('button#scale_minus').click(function() {
        if ($scale > 0) {
            $scale = $scale - 1;
            $('button#scale_plus').prop("disabled", false);
        }
        else
            $(this).prop("disabled", true);
        showGraph_operative();
    });
});

function showGraph_operative() {
    if (typeof lineGraph !== 'undefined')
        lineGraph.destroy ();

        $.ajax({
        url: "ajax/chart_test.php",
        type: "POST",
        data: {'graph':'operative', 'scale':$scale, 'shift':$time_shift, 'inp_node':$inp_node, 'inp_time':$inp_time},
        dataType: 'json',
        success: function (data) {
            // console.log(data);
            var time = [];
            var metrics = [];

            for (var i in data.metrics) {
                time.push(data.metrics[i].time);
                metrics.push(data.metrics[i].value);
            }

            var chartdata = {
                labels: time,
                datasets: [
                    {
                        label: 'Значение метрики',
                        borderColor: '#0000FF',
                        fill: false,
                        hoverBackgroundColor: '#CFCFCF',
                        hoverBorderColor: '#666666',
                        data: metrics
                    },
                ]
            };

            lineGraph = new Chart($("#graph-operative"), {
                type: 'line',
                data: chartdata,
                options: {
                    title: {
                        display: true,
                        fontSize: 16,
                        fontStyle: '',
                        padding: 20,
                        text: 'График по выбранной метрике мониторинга'
                    },
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            bounds: 'data',
                            type: 'time',
                            time: {
                                unit: data.axes,
                                stepSize: data.step,
                                displayFormats: {
                                    second: 'HH:mm:ss',
                                    minute: 'HH:mm',
                                    hour: 'DD.MM. HH:mm',
                                }
                            },
                            scaleLabel: {
                                display: true,
                                labelString: "Время",
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                min: 0,
                                max: 100,
                                stepSize: 10
                            },
                            scaleLabel: {
                                display: true,
                                labelString: "% использования диска",
                            }
                        }]
                    },
                    elements: {
                        line: {
                            tension: 0 // disables bezier curves
                        }
                    },
                    annotation: {
                        events: [],
                        annotations: [{
                            id: 'a-line-1', // optional
                            type: 'line',
                            mode: 'vertical',
                            scaleID: 'x-axis-0',
                            value: data.start_sit,
                            borderColor: '#FF00FF',
                            borderWidth: 1,
                            label: {
                                position: "bottom",
                                yAdjust: 0,
                                enabled: true,
                                content: data.start_sit.substr(11)
                            },
                            onMouseover: function(e) {
                            }
                        },
                        {
                            id: 'a-line-2', // optional
                            type: 'line',
                            mode: 'vertical',
                            scaleID: 'x-axis-0',
                            value: data.first_occurrence,
                            borderColor: '#FF0000',
                            borderWidth: 1,
                            label: {
                                position: "bottom",
                                yAdjust: 25,
                                enabled: true,
                                content: data.first_occurrence.substr(11)
                            },
                            onMouseover: function(e) {
                            }
                        },
                        {
                            id: 'a-line-3', // optional
                            type: 'line',
                            mode: 'vertical',
                            scaleID: 'x-axis-0',
                            value: data.inc_create,
                            borderColor: '#00b050',
                            borderWidth: 1,
                            label: {
                                position: "bottom",
                                yAdjust: 50,
                                enabled: true,
                                content: data.inc_create.substr(11)
                            },
                            onMouseover: function(e) {
                            }
                        }]
                    }
                },
            });
        }
    });
}

function showGraph_historical() {
    if (typeof bubbleGraph !== 'undefined')
        bubbleGraph.destroy ();

    $.ajax({
        url: "ajax/chart_test.php",
        type: "POST",
        data: {'graph':'historical', 'scale':$scale, 'shift':$time_shift, 'inp_node':$inp_node, 'inp_time':$inp_time},
        dataType: 'json',
        success: function (data) {
            var today = new Date;
            var ago = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate());
            var spot = [];

            // console.log(months_ago);

            for (var i in data) {
                spot.push({
                    x: data[i].D,
                    y: data[i].SEVERITY,
                    r: data[i].N
                });
            }

            bubbleGraph = new Chart($("#graph-historical"), {
                type: 'bubble',
                data: {
                    datasets: [{
                        label: 'Кол-во событий',
                        backgroundColor: "#FF0000",
                        data: spot
                    }]
                },
                options: {
                    title: {
                        display: true,
                        fontSize: 16,
                        fontStyle: '',
                        padding: 20,
                        text: 'Количество срабатываний ситуации по дням за последние 3 месяца'
                    },
                    legend: {
                        display: false
                    },
                    scales: {
                        xAxes: [{
                            bounds: 'data',
                            type: 'time',
                            time: {
                                min: ago.toISOString().split('T')[0],
                                max: today.toISOString().split('T')[0],
                                unit: 'day',
                                displayFormats: {
                                    day: 'DD.MM'
                                }
                            },
                            scaleLabel: {
                                display: true,
                                labelString: "Дата",
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                min: 0,
                                max: 5,
                                stepSize: 1
                            },
                            scaleLabel: {
                                display: true,
                                labelString: "Критичность",
                            }
                        }]
                    }
                }
            });
        }
    });
}
