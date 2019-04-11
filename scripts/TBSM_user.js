// окно для смены пароля пользователя TBSM
function passReset() {
    do {
        str = prompt("Задайте новый пароль:", "tivoli");
        if (str != null)
            str.replace(/\s+/g,' ');
    } while (str == "" || str == " ")
    if (str == null) {
        setCookie("new_pass", "", -1);
        return false;
    }
    else {
        setCookie("new_pass", encodeURI(str), 30);
        return true;
    }
}

// логика поведения радиокнопок для выбора типа УЗ
$(function() {
    $('input:radio[name=type]').change(function() {
        $val = $("input:radio[name=type]:checked").val();
        switch ($val) {
            case 'ldap':
                $('table.loc_hide').hide(function() {
                    $(this).animate({}, 2000);
                });
                $('table.sit_hide').show(function() {
                    $(this).animate({}, 2000);
                });
                break;
            case 'local':
                $('table.sit_hide').hide(function() {
                    $(this).animate({}, 2000);
                });
                $('table.loc_hide').show(function() {
                    $(this).animate({}, 2000);
                });
                break;
            default:
                break;
        }
    });
});

// проверка наличия отмеченных чекбоксов
function checkChecked() {
    var is_checked = false;
    $('input[type=checkbox]').each(function() {
        if (this.checked)
            is_checked = true;
    });
    if (!is_checked)
        alert("Не отмечен ни один пользователь!");
    return is_checked;
}

// динамическая фильтрация строк в таблице по значению одного столбца
$(function() {
    $('#dynamic_type').change(function() {
        $('table.tbsm_users tr.row_filtered').each(function() {
            var passed = true;
            $(this).children().each(function () {
                var filter_id = $(this).attr('class');
                if (filter_id != '') {
                    var filter_value = ($("#" + filter_id).val() == 'все' ? "" : $("#" + filter_id).val());
                    passed = (passed && (this.innerHTML.indexOf(filter_value) >= 0));
                }
            });
            $(this).toggle(passed);
        });
    });
});

$(function() {
    $('#dynamic_category').change(function() {
        $('table.tbsm_users tr.row_filtered').each(function() {
            var passed = true;
            $(this).children().each(function () {
                var filter_id = $(this).attr('class');
                if (filter_id != '') {
                    var filter_value = ($("#" + filter_id).val() == 'все' ? "" : $("#" + filter_id).val());
                    passed = (passed && (this.innerHTML.indexOf(filter_value) >= 0));
                }
            });
            $(this).toggle(passed);
        });
    });
});

/*
$(function() {
    $('#dynamic_filter').change(function() {
        var filter_value = ($("#dynamic_filter").val() == 'все' ? "" : $("#dynamic_filter").val());
        $('table.tbsm_users tr.row_filtered').each(function() {
            var passed = true;
            $(this).children().each(function () {
                if ($(this).attr('class') == 'col_filtered')
                    passed = (this.innerHTML.indexOf(filter_value) >= 0);
            });
            $(this).toggle(passed);
        });
    });
});

*/
