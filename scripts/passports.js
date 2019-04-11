// нажатие кнопки "Перейти к следующей ошибке"
number.count = 0;
function number(){
    return  number.count;
}
$(function() {
    $('input.search').click(function() {
        var c = 0;
        $('td#error').each(function() {
            if (c == 0)
                scrollTo = $(this);
            if (c == number()) {
                number.count = c + 1;
                scrollTo = $(this);
                return false;
            }
            c++;
        });
        if (c == number())
            number.count = 1;
        $('html,body').animate({scrollTop: scrollTo.offset().top});
    });
});

// показ количества ошибок после "Пожалуйста, подождите..."
$(function() {
    var err = $('input[name=err_number]').val();
    $("#to_remove").replaceWith("<b><font color='red'>Найдено ошибок: " + err + "</font></b><br>");
})