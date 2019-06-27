function checkCookie2() {
    if (checkCookie()) {
        var str;
        do {
            str = prompt("Укажите номер заявки (не менее 6 цифр):", "");
            if (str != null)
                str.replace(/\s+/g, ' ');
            else
                return false;
        } while (str === "" || str === " " || str.length < 6);
        setCookie("incident", encodeURI(str), 30);
        return true;
    }
    return false;
}

function checkCookie() {
    var inp=getCookie("comment");
    var title;
    var str;
    if (inp === "")
        title = "Укажите";
    else
        title = "Подтвердите";
    do {
        str = prompt(title + " причину производимых изменений:",decodeURI(inp));
        if (str != null)
            str.replace(/\s+/g,' ');
        else
            return false;
    } while (str === "" || str === " " || str.length > 512);
    setCookie("comment", encodeURI(str), 30);
    return true;
}

function dialog_regions() {
    var exp = $('input#reg_chk').prop("checked") ? "(с выгрузкой" : "(без выгрузки";

    var regs = "";
    $('#reg_list option:selected').each(function(){
        regs = regs + this.value + "\n\r";
    });

    if (regs == "") {
        alert("Выберите регион(ы) для обновления...");
        return false;
    }
    else
        return confirm("Выбранные регионы для обновления:\n\r"+
            exp + " ситуаций из TEMS)\n\r\n\r"+
            regs +
            "\n\r"+
            "Выполнить обновление?");
}

function select_all(name, value) {
    formblock = document.getElementById('formId');
    forminputs = formblock.getElementsByTagName('input');
    for (i = 0; i < forminputs.length; i++) {
        var regex = new RegExp(name, "i");
        if (regex.test(forminputs[i].getAttribute('name'))) {
            if (value == '1') {
                forminputs[i].checked = true;
            } else {
                forminputs[i].checked = false;
            }
        }
    }
}

function snapshot_warning(radio, chkbox) {
    formblock= document.getElementById('formId');
    forminputs = formblock.getElementsByTagName('input');
    var radio_val = '';
    for (var i = 0; i < forminputs.length; i++) {
        var regex = new RegExp(radio, "i");
        if (regex.test(forminputs[i].getAttribute('name'))) {
            if (forminputs[i].checked)
                radio_val = forminputs[i].value;
        }
    }

    formchkbox = formblock.getElementsByTagName('input');
    var chkbox_val = false;
    for (var i = 0; i < forminputs.length; i++) {
        var regex = new RegExp(chkbox, "i");
        if (regex.test(forminputs[i].getAttribute('name'))) {
            if (forminputs[i].checked)
                chkbox_val = true;
        }
    }

    if ((radio_val == 'inc_off' || radio_val == 'inc_on') && (!chkbox_val)) {
        if (confirm('Для настроек отправки инцидентов данной подсистемы отсутствует резервная копия! Настройки могут быть утеряны. Продолжить?..'))
            return checkCookie2();
        else
            return false;
    }
    else
        return checkCookie2();
}

// свёртывание/развёртывание таблицы №1
$(function() {
    $('.table_loc_toggle').click(function() {
        $('table.loc_show').toggle(function() {
            $(this).animate({}, 2000);
        });
        $('table.loc_hide').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

// свёртывание/развёртывание таблицы №1_1
$(function() {
    $('.table_ael_toggle').click(function() {
        $('table.ael_show').toggle(function() {
            $(this).animate({}, 2000);
        });
        $('table.ael_hide').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

// свёртывание/развёртывание таблицы №2
$(function() {
    $('.table_sit_toggle').click(function() {
        $('table.sit_show').toggle(function() {
            $(this).animate({}, 2000);
        });
        $('table.sit_hide').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

// свёртывание/развёртывание таблицы №3
$(function() {
    $('.table_ke_toggle').click(function() {
        $('table.ke_show').toggle(function() {
            $(this).animate({}, 2000);
        });
        $('table.ke_hide').toggle(function() {
            $(this).animate({}, 2000);
        });
    });
});

// логика поведения радиокнопок для управления режимом обслуживания (индикатор)
$(function() {
    $('input:radio[name=maint]').change(function() {
        if ($('input[name=acs_form]').attr("checked") != 'checked') {
            $val = $("input:radio[name=maint]:checked").val();
            switch ($val) {
                case 'ind_idle':
                    $("input:radio[name=incid]").val(['inc_idle']);
                    break;
                case 'to_maint':
                    $("input:radio[name=incid]").val(['inc_off']);
                    break;
                case 'from_maint':
                    $("input:radio[name=incid]").val(['inc_on']);
                    break;
                default:
                    break;
            }
        }
    });
});

// логика поведения радиокнопок для управления режимом обслуживания (отправка инцидентов)
$(function() {
    $('input:radio[name=incid]').change(function() {
        if ($('input[name=acs_form]').attr("checked") != 'checked') {
            $val = $("input:radio[name=incid]:checked").val();
            switch ($val) {
                case 'inc_idle':
                    $("input:radio[name=maint]").val(['ind_idle']);
                    break;
                case 'inc_off':
                    $("input:radio[name=maint]").val(['to_maint']);
                    break;
                case 'inc_on':
                    $("input:radio[name=maint]").val(['from_maint']);
                    break;
                default:
                    break;
            }
        }
    });
});


// мигание поля для динамической фильтрации по имени ситуации
$(function() {
    $('#filter_sit_name').one("mouseover", function() {
        $('#filter_sit_name').hide();
        $('#filter_sit_name').slideToggle("slow");
    });
});

// нажатие клавиши при вводе текста в строку поиска ситуации
$(function() {
    $('input[name=filter_sit_name]').keyup(function() {
        var search_str = this.value.toLowerCase();

        $('#sit_filt td.td_sit_name').each(function() {
            $(this).parent().toggle(this.innerHTML.toLowerCase().indexOf(search_str) >= 0);
        });
    });
});

$.ajaxSetup({
    type: "POST",
    cache: false,
    beforeSend: function(){
        $("#output_area").text('Пожалуйста, подождите...');
    },
    error: function (req, text, error) {
        $("#output_area").text('Ошибка! ' + text + ' | ' + error);
    }
});

// редактирование полей в таблице PFR_LOCATIONS
$(function() {
    $('#pfr_loc_edit').click(function(){
        if (checkCookie()) {
            var form = $('form#formId');
            $.ajax({
                url: "ajax/SCCD_trigger_pfr_loc_edit.php",
                data: form.serialize(),
                dataType: 'json',
                success: function (jsondata) {
                    var i=0;
                    $("#output_area").text('');
                    for (key in jsondata) {
                        var fld_name = "txtfldloc" + jsondata[key].id + jsondata[key].fld;
                        // $('#'+fld_name).val(jsondata[key].val);
                        $('#'+fld_name).slideToggle("fast");
                        $('#'+fld_name).slideToggle("slow");
                        i+=jsondata[key].updated;
                        $("#output_area").append(jsondata[key].err_mess);
                    }
                    $("#output_area").append('Обновлено записей в PFR_LOCATIONS: '+i);
                }
            });
        }
    });
});

// прокручивание тела таблицы
$(function() {
    $("table.sticky").stickyTableHeaders();
});