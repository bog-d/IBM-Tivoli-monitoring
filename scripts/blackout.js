// свёртывание/развёртывание PTK
$(function() {
    $('.blackout_PTK_toggle').click(function() {
        var id = $(this).attr('id');
        $('table.blackout_PTK_'+id).toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});
