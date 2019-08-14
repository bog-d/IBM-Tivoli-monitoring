// окончание загрузки страницы
$(function() {
    var total_count = 0;

    $('tr.rec_ch').each(function() {
        total_count++;
    });

    $('#chain_count').text(' (всего ' + total_count + ')');
});

// показ выбранной цепочки в нижней таблице
$(function() {
    $('tr.rec_ch').click(function() {
        $('tr.rec_ch').each(function() {
            $(this).removeClass("new_records");
        });
        $(this).toggleClass("new_records");

        $('tr#title').show();
        var chain_selected = $(this).attr("chain_id_sel");
        $('tr.chain').each(function() {
            if ($(this).attr("chain_id_show") == chain_selected)
                $(this).show(function () {
                    $(this).animate({}, 2000);
                });
            else
                $(this).hide();
        });

        $('#chain_id').val(chain_selected);
    });
});

// нажатие клавиши при вводе текста в строку поиска цепочки
$(function() {
    $('input[name=chain_search]').keyup(function() {
        var search_str = this.value.toUpperCase();
        var comp = false;
        var show_count = 0;
        var total_count = 0;

        $('tr.rec_ch').each(function() {
            comp = false;
            $(this).find('td').each(function(){
                if ($(this).text().toUpperCase().indexOf(search_str) >= 0)
                    comp = true;
            });

            if (comp) {
                $(this).show();
                show_count++;
            }
            else {
                $(this).hide();

                if ($(this).hasClass("new_records")) {
                    $(this).removeClass("new_records");
                    $('tr#title').hide();
                    $('tr.chain').each(function() {
                        $(this).hide();
                    });
                    $('#chain_id').val(null);
                }
            }
            total_count++;
        });

        $('#chain_count').text(show_count == total_count ? ' (всего ' + total_count + ')' : ' (отфильтровано ' + show_count + ' из ' + total_count + ')');
    });
});

// подтверждение при удалении цепочки
function del_confirm() {
    var chain_selected = $('#chain_id').val();
    if (chain_selected) {
        var i = 0;
        $('tr.chain').each(function() {
            if ($(this).attr("chain_id_show") == chain_selected)
                i++;
        });
        return confirm('Количество звеньев в выбранной цепочке: ' + i + '\n\r' + 'Все они будут удалены. Вы уверены?');
    }
    else {
        alert('Выберите цепочку для удаления!');
        return false;
    }
}

// подтверждение при редактировании цепочки
function edit_confirm() {
    var chain_selected = $('#chain_id').val();
    if (!chain_selected) {
        alert('Выберите цепочку для редактирования!');
        return false;
    }
}

// подтверждение при сохранении цепочки
function save_confirm() {
    // var m_number = 0;
    //
    // $('select[id^=sel_type_]').each(function() {
    //     if ($(this).val().localeCompare('m') == 0 && $(this).prop('disabled') == false)
    //         m_number++;
    // });
    //
    // if (m_number == 0) {
    //     alert('Добавьте звено с типом события "m"!');
    //     return false;
    // }
    // if (m_number > 1) {
    //     alert('Допустимо только одно звено с типом события "m"!');
    //     return false;
    // }

    if (confirm('Сохранить цепочку в БД?..')) {
        $.ajax({
            type: "POST",
            url: "ajax/corr_constructor_check_and_save.php",
            data: $('form#formEdit').serialize(),
            dataType: 'json',
            beforeSend: function(){
                $("#to_remove").text('Пожалуйста, подождите...');
            },
            success: function (data) {
                $("#to_remove").text('');
                if (data.check_status) {
                    alert('Проверка на консистентность прошла успешно.\n\r' +
                        (data.save_status ? 'Данные сохранены.' : 'При сохранении данных произошла ошибка!'));
                    window.location.href = "http://10.103.0.60/pfr_other/Corr_constructor.php";
                }
                else
                    alert('Проверка данных на консистентность не прошла!\n\r' + data.message);
            },
            error: function (req, text, error) {
                $("#to_remove").text('Ошибка при проверке данных на консистентность! ' + text + ' | ' + error);
            }
        });
    }
    return false;
}

// нажатие кнопки "Создать новую"
$(function() {
    $('button[name=new_btn]').click(function() {
        // подтверждение, если уже редактируется какая-то цепочка
        if (!$('button[name=save_btn]').length || confirm('Прервать редактирование цепочки?')) {
            // прячем список цепочек и кнопки
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

// нажатие кнопки "Продолжить..."
function next_confirm() {
    var text = $('input[name=chain_inp]').val();
    if (!text) {
        alert('Введите описание цепочки!');
        return false;
    }
    else {
        var match = false;
        // проверяем наличие цепочки с таким же именем в списке
        $('td.chain_descr').each(function() {
            if ($(this).text().localeCompare(text) == 0) {
                alert('Цепочка с таким именем уже имеется!');
                match = true;
            }
        });
        return !match;
    }
}

// нажатие клавиши при вводе текста в строку поиска КЭ
$(function() {
    $('input[name=input]').keyup(function() {
        var search_str = this.value.toUpperCase();
        var id = $(this).attr("id").substr(4);
        var conc = 0;

        if (($('select[id=sel_' + id + ']').attr("disabled") == "disabled")) {
            $('select[id=sel_' + id + '] option[value="-1"]').remove();
            $('select[id=sel_' + id + ']').attr("disabled", false);
        }

        var i = true;
        $('select[id=sel_' + id + '] option').each(function() {
            this.selected = false;
            if (this.text.indexOf(search_str) >= 0) {
                $(this).toggle(true);
                conc++;
                if (i) {
                    this.selected = true;
                    i = false;
                }
            }
            else {
                $(this).toggle(false);
            }
        });

        if (conc == 0) {
            $('select[id=sel_' + id + ']').append($('<option value="-1" selected>Совпадений не найдено</option>'));
            $('select[id=sel_' + id + ']').attr("disabled", true);
        }
    });
});

// нажатие кнопки "Добавить звено"
$(function() {
    $('button[name=add_btn]').click(function() {
        var added = false;
        var rest = false;

        $('tr.new_row').each(function() {
            if ($(this).hasClass("rec_hide") && !added) {
                var id_new = $(this).attr("id_new");
                $('#sel_type_new_' + id_new).prop('disabled', false);
                $('#chk_new_' + id_new).prop('checked', false);

                $(this).show(function () {
                    $(this).animate({}, 2000);
                });
                $(this).removeClass("rec_hide");

                added = true;
            }
            else if ($(this).hasClass("rec_hide") && added)
                rest = true;
        });

        if (!rest)
            $('button[name=add_btn]').prop('disabled', true);
    });
});

// отметка чек-бокса для удаления записи
$(function() {
    $('body input:checkbox').change(function() {
        var id = $(this).attr("id").substr(4);

        $('input[id=inp_ke_' + id + ']').prop('hidden', $(this).prop('checked'));
        $('input[id=inp_si_' + id + ']').prop('hidden', $(this).prop('checked'));

        $('select[id=sel_ke_' + id + ']').prop('disabled', $(this).prop('checked'));
        $('select[id=sel_si_' + id + ']').prop('disabled', $(this).prop('checked'));
        $('input[id=sel_type_' + id + ']').prop('disabled', $(this).prop('checked'));
    });
});

// при изменении радиокнопки "m" изменяются соответствующие переключатели для удаления
$(function() {
    $('input[name=type_list]').on('focus', function() {
        var id = $('[name=type_list]:checked').attr("id").substr(9);
        $('input[id=chk_' + id + ']').prop('disabled', false);
    }).change(function() {
        var id = $(this).attr("id").substr(9);
        $('input[id=chk_' + id + ']').prop('disabled', true);
    });
});
