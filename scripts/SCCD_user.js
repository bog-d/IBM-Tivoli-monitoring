function edit_confirm() {
    formblock= document.getElementById('formId');
    forminputs = formblock.getElementsByTagName('input');
    for (i = 0; i < forminputs.length; i++) {
        var regex = new RegExp(name, "i");
        if (regex.test(forminputs[i].getAttribute('name')))
            if (forminputs[i].checked)
                return true;
    }
    alert("Пользователь не выбран!");
    return false;
}

function delete_confirm() {
    if (edit_confirm())
        return confirm("Удалить выбранного пользователя?");
    else
        return false;
}
