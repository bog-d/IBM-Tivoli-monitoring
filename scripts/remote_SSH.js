// логика поведения строки ввода
$(function() {
    $('#target').keyup(function() {
        if ($(this).val().indexOf('http') >= 0) {
            $("input:radio[name=method]").val(['curl']);
            $('#ping_block').hide();
        }
    });
});

// выбор в списке регионов
$(function() {
    $('select[name^=region]').change(function() {
        $("input[name^=server]").filter('[value=ITM]').prop('checked', true);
    });
});

// ползунок для выбора количества пакетов в пинге
$(document).ready(function(){
    if ($("input:radio[name=method]:checked").val() != 'ping')
        $('#ping_block').hide();
    $( "#slider" ).slider({
        value : 4,
        min : 1,
        max : 100,
        step : 1,
        create: function( event, ui ) {
            val = $( "#slider" ).slider("value");
            $( "#contentSlider_show" ).html( val );
            $( "#contentSlider_save" ).val( val );
        },
        slide: function( event, ui ) {
            $( "#contentSlider_show" ).html( ui.value );
            $( "#contentSlider_save" ).val( ui.value );
        }
    });
});

// ползунок для выбора количества пакетов в traceroute
$(document).ready(function(){
    if ($("input:radio[name=method]:checked").val() != 'traceroute')
        $('#traceroute_block').hide();
    $( "#slider_2" ).slider({
        value : 10,
        min : 10,
        max : 30,
        step : 1,
        create: function( event, ui ) {
            val = $( "#slider_2" ).slider("value");
            $( "#contentSlider_show_2" ).html( val );
            $( "#contentSlider_save_2" ).val( val );
        },
        slide: function( event, ui ) {
            $( "#contentSlider_show_2" ).html( ui.value );
            $( "#contentSlider_save_2" ).val( ui.value );
        }
    });
});

// логика поведения радиокнопок для выбора метода проверки доступа
$(function() {
    $('input:radio[name=method]').change(function() {
        $val = $("input:radio[name=method]:checked").val();
        switch ($val) {
            case 'ping':
                $('#traceroute_block').hide();
                $('#ping_block').show();
                break;
            case 'traceroute':
                $('#ping_block').hide();
                $('#traceroute_block').show();
                break;
            default:
                $('#ping_block').hide();
                $('#traceroute_block').hide();
                break;
        }
    });
});

