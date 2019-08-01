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
                alert('Цепочка с таким описанием уже имеется!');
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

