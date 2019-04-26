var lineGraph;
var $ser_number;
var $scale = -1;
var $time_shift = 0;

$(function() {
    $('button#prev').click(function() {
        $time_shift = $time_shift - 1;
        showGraph_operative($ser_number);
    });
});

$(function() {
    $('button#next').click(function() {
        $time_shift = $time_shift + 1;
        showGraph_operative($ser_number);
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
        showGraph_operative($ser_number);
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
        showGraph_operative($ser_number);
    });
});

$(function() {
    $('button#close').click(function() {
        lineGraph.destroy();
        $('table.ael_hide').toggle();
    });
});


function showGraph_operative(serial) {
    $('div#wait').html("<font color='red'>Пожалуйста, подождите...</font><img src='images/inprogress.gif' hspace='10'>");
    if (typeof lineGraph !== 'undefined') {
        lineGraph.destroy();
    }
    if ($ser_number != serial) {
        $ser_number = serial;
        $scale = -1;
        $time_shift = 0;
    }

    $.ajax({
        url: "ajax/chart.php",
        type: "POST",
        data: {'scale':$scale, 'shift':$time_shift, 'serial':serial},
        dataType: 'json',
        success: function (data) {
            $("div#wait").empty();
            if (data.error.length > 0) {
                alert(data.error);
                return;
            }
            $scale = parseInt(data.scale);
            // console.log(data);

            $('th#serial').text('Серийный номер события: ' + $ser_number);
            $('td#start_sit').text(date_conv(data.start_sit));
            $('td#first_occurrence').text(date_conv(data.first_occurrence));
            data.inc_create.indexOf('1970') < 0 ? $('td#inc_create').text(date_conv(data.inc_create)) : $('td#inc_create').text('не создан');
            data.close_sit.indexOf('1970') < 0 ? $('td#close_sit').text(date_conv(data.close_sit)) : $('td#close_sit').text('не закрыта');
            $('table.ael_hide').show();

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
                                labelString: data.title,
                            }
                        }]
                    },
                    elements: {
                        line: {
                            // tension: 0 // disables bezier curves
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
                            borderColor: '#54C1F0',
                            borderWidth: 1,
                            label: {
                                position: "bottom",
                                yAdjust: 50,
                                enabled: data.inc_create.indexOf('1970') == -1,
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
                            value: data.close_sit,
                            borderColor: '#00b050',
                            borderWidth: 1,
                            label: {
                                position: "bottom",
                                yAdjust: 75,
                                enabled: data.close_sit.indexOf('1970') == -1,
                                content: data.close_sit.substr(11)
                            },
                            onMouseover: function(e) {
                            }
                        }]
                    }
                },
            });
        },
        error: function() {
            $("div#wait").empty();
        }
    });
}

// function to convert date from YYYY-MM-DD HH:MM:SS format to DD.MM.YYYY HH:MM:SS
function date_conv(data) {
    var DateTimeSplit = data.split(' ');
    var DateSplit = DateTimeSplit[0].split('-');
    return DateSplit[2] + '.' + DateSplit[1] + '.' + DateSplit[0] + ' ' + DateTimeSplit[1];
}