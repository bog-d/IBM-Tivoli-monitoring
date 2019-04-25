var lineGraph;
var $scale = -1;
var $time_shift = 0;

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

function showGraph_operative(serial) {
    if (typeof lineGraph !== 'undefined')
        lineGraph.destroy ();

    $.ajax({
        url: "ajax/chart.php",
        type: "POST",
        data: {'scale':$scale, 'shift':$time_shift, 'serial':serial},
        dataType: 'json',
        success: function (data) {
            if (data.error.length > 0) {
                alert(data.error);
                return;
            }
            // console.log(data);
            $scale = data.scale;
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
                        // text: 'График по выбранной метрике мониторинга'
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
                                enabled: data.inc_create.localeCompare('1970-01-01 03:00:00') > 0,
                                content: data.inc_create.substr(11)
                            },
                            onMouseover: function(e) {
                            }
                        },
                        {
                            id: 'a-line-4', // optional
                            type: 'line',
                            mode: 'vertical',
                            scaleID: 'x-axis-0',
                            value: data.last_occurrence,
                            borderColor: '#0000FF',
                            borderWidth: 1,
                            label: {
                                position: "bottom",
                                yAdjust: 75,
                                enabled: true,
                                content: data.last_occurrence.substr(11)
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
