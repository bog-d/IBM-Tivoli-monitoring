$(function(){
    var csda = $('.calendar').cellSelection({
        ignoreCell: '.ignore'
    });

    $('.get_date').on('click', function() {
        var arr = csda.cellSelection('getArray', 'data');
        var d = new Date();
        d.setTime(d.getTime() + (5*1000));
        var expires = "expires=" + d.toGMTString();
        document.cookie = "calendar_date=" + arr[0]['date'] + "; " + expires;
    });
});

// свёртывание/развёртывание элементов div
$(function() {
    $('.table_loc_toggle').click(function() {
        $('table.loc_show').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

$(function() {
    $('.toggle').click(function() {
        $('div.hide').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});
