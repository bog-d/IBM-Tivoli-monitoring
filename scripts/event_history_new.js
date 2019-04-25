$(document).ready(function() {
    var table = $('#events').DataTable( {
        ajax: {
            url: 'ajax/event_history_table.php',
            type: 'POST'
        },
        processing: true,
        serverSide: true,
        order: [[ 1, 'desc' ]],
        colReorder: true,
        pageLength: 10,
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
            { data: "SERIAL",
                className: 'dt-body-center',
                render: function ( data, type, row ) {
                    return type === "display" ?
                        '<a href=\'#\' title=\'Показать график...\' onclick="showGraph_operative(' + data + '); return false;">' + data + '</a>' :
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
                orderable: false
            },
            { data: "PFR_KE_TORS",
                render: function ( data, type, row ) {
                    return type === "display" ?
                        '<a href=\'http://10.103.0.106/maximo/ui/login?event=loadapp&value=CI&additionalevent=useqbe&additionaleventvalue=CINAME=' + data + '\' ' +
                        'target=\'blank\' title=\'Перейти в СТП к КЭ...\'>' + data + '</a>' :
                        data;
                },
                orderable: false
            },
            { data: "PFR_SIT_NAME",
                orderable: false
            },
/*
            { data: "DESCRIPTION",
                width: "20%",
                orderable: false
            },
*/
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
                orderable: false
            }
        ],
        fnRowCallback: function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
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
        initComplete: function () {
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
            'colvis', 'copy', 'excel', 'print'
        ],
        language: {
            "processing": "Пожалуйста, подождите...",
            "search": "Поиск в пределах подсистемы:",
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
                this.search('^' + $('input', this.footer()).val());
                this.draw();
            }
        }

        $( 'input', this.footer() ).on( 'keyup change', function () {
            if ( that.search() !== this.value ) {
                var attr = $(this).attr('id');
                if (typeof attr !== typeof undefined && attr !== false && (attr == 'start' || attr == 'finish'))
                    that.search($('input#start').val() + '*' + $('input#finish').val());
                else
                    that.search(this.value);
                that.draw();
            }
        } );
    } );

/*
    $('#events tbody')
        .on( 'mouseenter', 'td', function () {
            var colIdx = table.cell(this).index().column;

            $( table.cells().nodes() ).removeClass( 'highlight' );
            $( table.column( colIdx ).nodes() ).addClass( 'highlight' );
        } );
*/

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

    function format ( d ) {
        return '<table cellpadding="0" cellspacing="10" border="0" style="padding-left:50px;">'+
                '<tr>'+
                    '<td>Описание ситуации:</td>'+
                    '<td>'+
                        d.DESCRIPTION +
                        '&emsp;&emsp;<input type="button" onclick="showGraph_operative(' + d.SERIAL + ')" title="Показать график..." value="Хронология">' +
                    '</td>'+
            '</tr>'+
                '<tr>'+
                    '<td>Настройка интеграции с СТП:</td>'+
                    '<td>' +
                        '<a href="http://10.103.0.60/pfr_other/SCCD_trigger.php?ServiceName=' + d.PFR_OBJECT + '" target="_blank"' +
                        'title="Перейти в форму по имени объекта мониторинга..."><img src="images/link.png" align="top" hspace="5">перейти по объекту</a>&emsp;&emsp;'+
                        '<a href="http://10.103.0.60/pfr_other/SCCD_trigger.php?KE=' + d.PFR_KE_TORS + '" target="_blank"' +
                        'title="Перейти в форму по имени КЭ..."><img src="images/link.png" align="top" hspace="5">перейти по КЭ</a>' +
                    '</td>' +
                '</tr>'+
            '</table>';
    }
} );



