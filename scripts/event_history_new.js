date_start = '';
date_finish = '';

$(document).ready(function() {
    var table = $('#events').DataTable( {
        ajax: {
            url: 'ajax/event_history_table.php',
            type: 'POST'
        },
        processing: true,
        serverSide: true,
        deferRender: true,
        order: [[ 1, 'desc' ]],
        colReorder: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Все"]],
        // scrollY: '60vh',
        // scrollCollapse: true,
        // scrollX: true,
        columns: [
            { data:           null,
                className:      'details-control',
                orderable:      false,
                defaultContent: ''
            },
            { data: "WRITETIME",
                render: function ( data, type, row ) {
                    var DateTimeSplit = data.split(' ');
                    var DateSplit = DateTimeSplit[0].split('-');
                    return type === "display" ?
                        DateSplit[2] + '.' + DateSplit[1] + '.' + DateSplit[0] + ' ' + DateTimeSplit[1]:
                        data;
                },
                orderable: true
            },
            { data: "FIRST_OCCURRENCE",
                render: function ( data, type, row ) {
                    var DateTimeSplit = data.split(' ');
                    var DateSplit = DateTimeSplit[0].split('-');
                    return type === "display" ?
                        DateSplit[2] + '.' + DateSplit[1] + '.' + DateSplit[0] + ' ' + DateTimeSplit[1]:
                        data;
                },
                orderable: true
            },
            { data: "SERIAL",
                className: 'dt-body-center',
                render: function ( data, type, row ) {
                    return type === "display" ?
                        data + '<img class="click" id="serial" src="images/filter.png" title="Установить/снять фильтр по номеру события" hspace="10" align="top">' :
                        data;
                },
                orderable: false
            },
            { data: "PFR_TORG",
                orderable: false
            },
            { data: "NODE",
                orderable: false
            },
            { data: "PFR_OBJECT",
                render: function ( data, type, row ) {
                    return (type === "display" && data.length > 0) ?
                        data + '<a href="http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName=' + data + '" target="_blank"' +
                        'title="Перейти в форму \'Настройка интеграции с СТП\' к сервису ' + data + '"><img src="images/link.png" align="top" hspace="5"></a>' :
                        data;
                },
                orderable: false
            },
            { data: "PFR_KE_TORS",
                render: function ( data, type, row ) {
                    return (type === "display" && data.length > 0) ?
                        '<a href=\'http://10.103.0.106/maximo/ui/login?event=loadapp&value=CI&additionalevent=useqbe&additionaleventvalue=CINAME=' + data + '\' ' +
                        'target=\'blank\' title=\'Перейти в СТП к КЭ...\'>' + data + '</a>' +
                        '<a href="http://10.103.0.60/pfr_other/SCCD_trigger.php?KE=' + data + '" target="_blank"' +
                        'title="Перейти в форму \'Настройка интеграции с СТП\' к КЭ ' + data + '"><img src="images/link.png" align="top" hspace="5"></a>' :
                        data;
                },
                orderable: false
            },
            { data: "PFR_SIT_NAME",
                render: function ( data, type, row ) {
                    return type === "display" ?
                        '<table width="100%" style="background:transparent"><tr><td>' + data + '</td>' + '<td id="chart" align="right"></td></tr></table>' :
                        data;
                },
                orderable: false
            },
            { data: "SEVERITY",
                className: 'cell_severity',
                orderable: false
            },
            { data: "TTNUMBER",
                render: function ( data, type, row ) {
                    return type === "display" ?
                        '<a href=\'http://10.103.0.106/maximo/ui/maximo.jsp?event=loadapp&value=incident&additionalevent=useqbe&additionaleventvalue=ticketid=' + data +
                            '&datasource=NCOMS\' target=\'blank\' title=\'Перейти в СТП к инциденту...\'>' + data + '</a>' :
                        data;
                },
                className: 'dt-body-center',
                orderable: false
            },
            { data: "PFR_TSRM_CLASS",
                className: 'cell_tsrm_class',
                orderable: false
            },
            { data: "CLASSIFICATIONID",
                render: function ( data, type, row ) {
                    return (type === "display" && data !== null) ?
                        '<a href=\'http://10.103.0.106/maximo/ui/login?event=loadapp&value=ASSETCAT&additionalevent=useqbe&additionaleventvalue=CLASSIFICATIONID=' + data + '\' ' +
                        'target=\'blank\' title=\'Перейти в СТП к классификации...\'>' + data + '</a>' :
                        data;
                },
                className: 'dt-body-center',
                orderable: false
            },
            { data: "CLASSIFICATIONGROUP",
                orderable: false
            },
            { data: "PFR_TSRM_WORDER",
                render: function ( data, type, row ) {
                    return type === "display" && data != null ?
                        '<a href=\'http://10.103.0.106/maximo/ui/?event=loadapp&amp;value=wotrack&amp;additionalevent=useqbe&amp;additionaleventvalue=wonum=' + data +
                            '&amp;forcereload=true\' target=\'blank\' title=\'Перейти в СТП к РЗ...\'>' + data + '</a>' :
                        data;
                },
                className: 'dt-body-center',
                orderable: false
            }
        ],
        fnRowCallback: function(nRow, aData) {
            if (aData['SAMPLED_SIT']) {
                if (aData['SIT_IN_COLLECTION'])
                    $('td#chart', nRow).html('<a id="cell_pfr_sit_name" href=\'#\'><img src="images/chart.png" align="top" hspace="10" width="24" height="24" title="Показать график..."></a>');
                else
                    $('td#chart', nRow).html('<img src="images/chart_inactive.png" align="top" hspace="10" width="24" height="24" title="График будет в перспективе...">');
            }

            $('a#cell_pfr_sit_name', nRow).attr('onclick', 'showGraph_operative(' + aData['SERIAL'] + '); return false;');

            switch (aData["SEVERITY"]) {
                case "Critical":
                    $('td.cell_severity', nRow).attr('class', 'red_status dt-body-center'); break;
                case "Marginal":
                case "Minor":
                case "Warning":
                    $('td.cell_severity', nRow).attr('class', 'yellow_status dt-body-center'); break;
                case "Informational":
                    $('td.cell_severity', nRow).attr('class', 'blue_status dt-body-center'); break;
                case "Harmless":
                    $('td.cell_severity', nRow).attr('class', 'green_status dt-body-center'); break;
                default:
                    break;
            }

            if (aData["PFR_TSRM_CLASS"].indexOf('Выкл.') == 0 || aData["PFR_TSRM_CLASS"].indexOf('Тест') == 0)
                    $('td.cell_tsrm_class', nRow).attr('class', 'blue_status');
        },
        drawCallback: function(settings) {
            // filters and data for export to excel
            var api = this.api();
            $('a#excel').attr('href', 'http://10.103.0.60/pfr_other/event_history_excel.php?data=' + JSON.stringify(api.ajax.json(), ['options']));
        },
        initComplete: function (settings, json) {
            // lists filters
            this.api().columns().every( function () {
                var column = this;
                var select = $('select', this.footer());
                if (column.dataSrc() == 'SEVERITY') {
                    select.append('<option value="5">Critical</option>');
                    select.append('<option value="4">Marginal</option>');
                    select.append('<option value="3">Minor</option>');
                    select.append('<option value="2">Warning</option>');
                    select.append('<option value="1">Informational</option>');
                    select.append('<option value="0">Harmless</option>');
                }

                if (column.dataSrc() == 'PFR_TSRM_CLASS') {
                    select.append('<option value="-30">Выкл. (Тест.)</option>');
                    select.append('<option value="-10">Выкл. (Прод.)</option>');
                    select.append('<option value="0">Не задано</option>');
                    select.append('<option value="2">Продуктивный</option>');
                    select.append('<option value="3">Тест</option>');
                }

                select.on('change', function () {
                    var val = $(this).val();
                    column
                        .search(val)
                        .draw();
                });
            } );
        },
        dom: 'lr<"rightimg"B><"rightimg"f>rtip',
        buttons: [
            'colvis', 'copy', 'print'
        ],
        language: {
            "processing": "<font color='red'>Пожалуйста, подождите...</font><img src='images/inprogress.gif' hspace='10'>",
            "search": "Поиск в составе индикатора/подсистемы:",
            "lengthMenu": "Показать _MENU_ записей",
            "info": "Записи с _START_ до _END_ из _TOTAL_ записей",
            "infoEmpty": "Записи с 0 до 0 из 0 записей",
            "infoFiltered": "(отфильтровано из _MAX_ записей)",
            "infoPostFix": "",
            "loadingRecords": "Загрузка записей...",
            "zeroRecords": "Записи отсутствуют.",
            "emptyTable": "В таблице отсутствуют данные",
            "paginate": {
                "first": "Первая",
                "previous": "Предыдущая",
                "next": "Следующая",
                "last": "Последняя"
            },
            "aria": {
                "sortAscending": ": активировать для сортировки столбца по возрастанию",
                "sortDescending": ": активировать для сортировки столбца по убыванию"
            }
        }
    } );

    $('#events tfoot tr').appendTo('#events thead');

    table.columns().every( function () {
        var that = this;

        if ($('input', this.footer()).val()) {
            if ($('input', this.footer()).val().length > 0) {
                var att = $('input', this.footer()).attr('id');
                if (typeof att !== typeof undefined && att !== false && att == 'ptk')
                    table.search($('input', this.footer()).val());
                else
                    this.search('^' + $('input', this.footer()).val());
                this.draw();
            }
        }

        $( 'input', this.footer() ).on( 'keyup change', function () {
            if ( that.search() !== this.value ) {
                var attr = $(this).attr('id');
                if (attr == 'start') {
                    date_start = this.value;
                    that.search(date_start + '*' + date_finish);
                }
                else if (attr == 'finish') {
                    date_finish = this.value;
                    that.search(date_start + '*' + date_finish);
                }
                else
                    that.search(this.value);
                that.draw();
            }
        } );
    } );

    $('#events tbody').on('click', '#serial', function () {
        var td = $(this).closest('td');
        var cell = table.cell( td );
        var serial = cell.data();

        var column = table.column(2);
        var input = $('input', column.footer());
        var search = input.val() == serial ? '' : serial;

        input.val(search);
        column
            .search(search)
            .draw();
    } );

    // Add event listener for opening and closing details
    $('#events tbody').on('click', 'td.details-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row( tr );

        if ( row.child.isShown() ) {
            row.child.hide();
            tr.removeClass('shown');
        }
        else {
            row.child( format(row.data()) ).show();
            tr.addClass('shown');
        }
    } );

    // Add event listener for toggle all details
    $('img#details').on( 'click', function () {
        table.rows().each(function () {
            $('.details-control').trigger( 'click' );

            if ($('img#details').attr('src') == 'images/details_close.png') {
                $('img#details').attr('src', 'images/details_open.png');
                $('img#details').attr('title', 'Развернуть все детали');
            }
            else {
                $('img#details').attr('src', 'images/details_close.png');
                $('img#details').attr('title', 'Свернуть все детали');
            }
        } );
    } );

    function format ( d ) {
        return '<table cellpadding="0" cellspacing="10" border="0" style="padding-left:50px;">'+
                '<tr>'+
                    '<td>Описание ситуации:</td>'+
                    '<td>'+
                        d.DESCRIPTION +
                        // '&emsp;&emsp;<input type="button" onclick="showGraph_operative(' + d.SERIAL + ')" title="Показать график..." value="Хронология">' +
                    '</td>'+
                '</tr>'+
                '<tr>'+
                    '<td valign="top">' + (d.TRACEROUTE_TITLE.trim() == '' ? '' : 'Результаты трассировки:<br>' + d.TRACEROUTE_TITLE) + '</td>'+
                    '<td>'+
                        d.TRACEROUTE_DATA +
                    '</td>'+
                '</tr>'+
                // '<tr>'+
                //     '<td>Настройка интеграции с СТП:</td>'+
                //     '<td>' +
                //         '<a href="http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName=' + d.PFR_OBJECT + '" target="_blank"' +
                //         'title="Перейти в форму по имени объекта мониторинга..."><img src="images/link.png" align="top" hspace="5">перейти по объекту</a>&emsp;&emsp;'+
                //         '<a href="http://10.103.0.60/pfr_other/SCCD_trigger.php?KE=' + d.PFR_KE_TORS + '" target="_blank"' +
                //         'title="Перейти в форму по имени КЭ..."><img src="images/link.png" align="top" hspace="5">перейти по КЭ</a>' +
                //     '</td>' +
                // '</tr>'+
            '</table>';
    }
} );
