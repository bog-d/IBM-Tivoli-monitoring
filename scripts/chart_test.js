
$(document).ready(function () {
    showGraph_operative();
    showGraph_historical();
});

function showGraph_operative()
{
    {
        $.ajax({
            url: "ajax/chart_test.php",
            type: "POST",
            data: {'serial':'65173965', 'graph':'operative'},
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

                var lineGraph = new Chart($("#graph-operative"), {
                    type: 'line',
                    data: chartdata,
                    options: {
                        title: {
                            display: true,
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
                                    unit: 'minute',
                                    stepSize: 10,
                                    displayFormats: {
                                        minute: 'HH:mm'
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
                            events: ['mouseover'],
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
}

function showGraph_historical()
{
    {
        $.ajax({
            url: "ajax/chart_test.php",
            type: "POST",
            data: {'serial':'65173965', 'graph':'historical'},
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

                var bubbleGraph = new Chart($("#graph-historical"), {
                    type: 'bubble',
                    data: {
                        datasets: [{
                            label: 'SL10100008126I',
                            backgroundColor: "#FF0000",
                            data: spot
                        }]
                    },
                    options: {
                        title: {
                            display: true,
                            text: 'Количество срабатывания ситуации по дням за последние 3 месяца'
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
}
