// свёртывание/развёртывание элемента div
$(function() {
    $('select#variant').change(function() {
        if ($(this).val() == '0') {
            $('div#textarea').show(function () {
                $('div#textarea').animate({}, 2000);
            });
        }
        else {
            $('div#textarea').hide(function () {
                $('div#textarea').animate({}, 2000);
            });
        }
    });
});

$.ajaxSetup({
    type: "POST",
    cache: false,
    beforeSend: function(){
        $("#output_area").html("<div class='red_message'>Пожалуйста, подождите...</div>");
    },
    error: function (req, text, error) {
        $("#output_area").text('Ошибка! ' + text + ' | ' + error);
    }
});

// нажатие кнопки проверки
$(function() {
    $('input#check').click(function() {
        // проверяем наличие содержимого в полях ввода
        if (!$('textarea#value').val()) {
            alert('Введите номера инцидентов!');
            return false;
        }

        // ajax-запрос к серверу
        var form = $('form#inclose');
        $.ajax({
            url: "ajax/incident_check.php",
            data: form.serialize(),
            dataType: 'json',
            success: function (jsondata) {
                $("#output_area").html('<h4>Результаты проверки инцидентов</h4><ul>');
                for (key in jsondata)
                    $("#output_area").append("<li><b>" + jsondata[key].number + "</b> : " + jsondata[key].result + "</li><br>");
                $("#output_area").append('</ul>');
            }
        });
    });
});

// нажатие кнопки закрытия
$(function() {
    $('input#close').click(function() {
        // проверяем наличие содержимого в полях ввода
        if (!$('textarea#value').val()) {
            alert('Введите номера инцидентов!');
            return false;
        }
        if ($('select#variant').val() == '0' && !$('textarea#text').val()) {
            alert('Введите причину закрытия!');
            return false;
        }

        // последнее предупреждение
        if (!confirm('Вы уверены, что хотите закрыть эти инцидеенты?'))
            return false;

        // ajax-запрос к серверу
        var form = $('form#inclose');
        $.ajax({
            url: "ajax/incident_close.php",
            data: form.serialize(),
            dataType: 'json',
            success: function (jsondata) {
                $("#output_area").html('<h4>Результаты закрытия инцидентов</h4><ul>');
                for (key in jsondata)
                    $("#output_area").append("<li><b>" + jsondata[key].number + "</b> : " + jsondata[key].result + "</li><br>");
                $("#output_area").append('</ul>');
            }
        });
    });
});

