// свёртывание/развёртывание PTK
$(function() {
    $('.blackout_PTK_toggle').click(function() {
        var id = $(this).attr('id');
        $('table.blackout_PTK_'+id).toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

// прокручивание тела таблицы
$(function() {
    $("table.sticky").stickyTableHeaders();
});

// свёртывание/развёртывание списка неосновных КЭ
$(function() {
    $('td.expand').click(function() {
        var id = $(this).attr('id');
        var atrib = $('img.i' + id).attr('src');

        if (atrib.indexOf('details_open') > 0) {
            $('img.i' + id).attr('src', 'images/details_close.png');
            $('img.i' + id).attr('title', 'Свернуть');
        }
        else {
            $('img.i' + id).attr('src', 'images/details_open.png');
            $('img.i' + id).attr('title', 'Развернуть');
        }

        $('tr.nonbase_' + id).toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});
