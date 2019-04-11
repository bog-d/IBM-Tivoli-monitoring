// нажатие кнопки "Создать новую"
$(function() {
    $('button[name=new_btn]').click(function() {
        // подтверждение, если уже редактируется какая-то ситуация
        if (!$('button[name=save_btn]').length || confirm('Прервать редактирование ситуации?')) {
            // деактивируем список ситуаций, чтоб не путать с клонированием
            $('select[name=sits_list]').prop( "disabled", true );
            // прячем список ситуаций и кнопки
            $('tr.first').hide(function () {
                $('tr.first').animate({}, 2000);
            });
            // показываем строку для ввода
            $('tr.next').show(function () {
                $('tr.next').animate({}, 2000);
            });
        }
    });
});

// нажатие кнопки "Редактировать"
$(function() {
    $('button[name=edit_btn]').click(function() {
        // проверяем - выбрана ли ситуация в списке
        if (!$('select[name=sits_list]').val()) {
            alert('Выберите ситуацию из списка!');
            return false;
        }
        // подтверждение, если уже редактируется какая-то ситуация
        if (!$('button[name=save_btn]').length || confirm('Прервать редактирование ситуации?')) {
            $('#formSelect').submit();
        }
    });
});

// нажатие кнопки "Удалить"
$(function() {
    $('button[name=del_btn]').click(function() {
        // проверяем - выбрана ли ситуация в списке
        if (!$('select[name=sits_list]').val()) {
            alert('Выберите ситуацию из списка!');
            return false;
        }
        // подтверждение, если уже редактируется какая-то ситуация
        if (!$('button[name=save_btn]').length || confirm('Прервать редактирование ситуации?')) {
            return confirm('Удалить ситуацию без возможности восстановления?');
        }
    });
});

// нажатие кнопки "Клонировать"
$(function() {
    $('button[name=clone_btn]').click(function() {
        // подтверждение, если уже редактируется какая-то ситуация
        if (!$('button[name=save_btn]').length || confirm('Прервать редактирование ситуации?')) {
            // показываем строку для ввода
            if ($('select[name=sits_list]').val())
                $('tr.next').show(function () {
                    $('tr.next').animate({}, 2000);
                });
            else
                alert('Выберите ситуацию из списка!');
        }
    });
});

// нажатие кнопки "Продолжить..."
$(function() {
    $('button[name=next_btn]').click(function() {
        // проверяем наличие содержимого в строке ввода
        if (!$('input[name=sit_inp]').val()) {
            alert('Введите наименование ситуации!');
            return false;
        }
        else {
            var match = false;
            // проверяем наличие такой же ситуации в списке
            $('select').each(function (i,elem) {
                if ($(elem).text().localeCompare($('input[name=sit_inp]').val()) == 0) {
                    alert('Ситуация с таким наименованием уже имеется!');
                    match = true;
                }
            });
            if (match) {
                return false;
            }
        }
        $('#formSelect').submit();
    });
});

// выбор в списке шаблонов
$(function() {
    $('select[name^=templ]').change(function() {
        // имя ячейки для вывода образца
        $test_name = 'test_' + ($(this).attr("name")).substr(6, ($(this).attr("name")).length - 6);
        // имя поля для ввода текста
        $inp_name = 'str_' + ($(this).attr("name")).substr(6, ($(this).attr("name")).length - 6);

        // в списке шаблонов выбрана "обычная строка"
        if ($(this).val() === 'текстовая строка') {
            $('.' + $test_name).text($('.' + $inp_name).val());
            $('.' + $inp_name).show(function () {
                $('.' + $inp_name).animate({}, 2000);
            });
        }
        // в списке шаблонов выбран шаблон
        else {
            $('.' + $test_name).text($(this).val());
            $('.' + $inp_name).hide(function () {
                $('.' + $inp_name).animate({}, 2000);
            });
        }
    });
});

// нажатие клавиши при вводе текста в строку описания
$(function() {
    $('input[name^=str]').keyup(function() {
        $test_name = 'test_' + ($(this).attr("name")).substr(4, ($(this).attr("name")).length - 4);
        $('.' + $test_name).text($(this).val());
    });
});

// нажатие клавиши при вводе текста в строку поиска ситуации
$(function() {
    $('input[name=sit_search]').keyup(function() {
        var search_str = this.value.toLowerCase();
        var conc = 0;

        if (($('select[name=sits_list]').attr("disabled") == "disabled")) {
            $("select[name=sits_list] option[value='-1']").remove();
            $('select[name=sits_list]').attr("disabled", false);
        }

        $('select[name=sits_list] option').each(function() {
            if (this.text.toLowerCase().indexOf(search_str) >= 0) {
                $(this).toggle(true);
                conc++;
            }
            else {
                $(this).toggle(false);
            }
        });

        if (conc == 0) {
            $('select[name=sits_list]').append($('<option value="-1">Совпадений не найдено</option>'));
            $('select[name=sits_list]').attr("disabled", true);
        }

    });
});
