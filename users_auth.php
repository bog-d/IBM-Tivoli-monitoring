<?php
$acs = $acs_user = $acs_role = '';
$roles_arr = array (    // access roles
    'operator' => 'Оператор',
    'user' => 'Пользователь',
    'admin' => 'Администратор',
);
//var_dump($_POST);

// **************************************************************************************************************************************************
if (isset($_POST['code'])) {
    require_once 'functions/auth.php';

    $to_reset = ($_POST['code'] == 'reset');

    // AJAX request
    if (!$to_reset) {
        $acs = auth($_POST['code']);
        if (!empty($acs))
            list($acs_user, $acs_role) = explode(';', $acs);
        $acs_form = ($acs_role == 'admin' or $acs_role == 'user');
    }

    if (empty($acs_user)) {
        $btn_acs_user = $btn_acs_form = $btn_acs_admin = false;
        $output = $to_reset ? 'КОД ДОСТУПА СБРОШЕН' : 'КОД ДОСТУПА НЕ РАСПОЗНАН';
        $title = "Для активации кнопок введите код доступа:<br><br>";
    } else {
        $btn_acs_user = true;
        $btn_acs_form = $acs_form;
        $btn_acs_admin = ($acs_role == 'admin');
        $output = 'Код доступа подтверждён.';
        $title = "{$acs_user} ({$roles_arr[$acs_role]})<br><br>";
    }

    // AJAX response
    echo json_encode(array(
        'reset' => $to_reset,
        'title' => $title,
        'output' => $output,
        'btn_user' => $btn_acs_user,
        'btn_form' => $btn_acs_form,
        'btn_admin' => $btn_acs_admin,
        ));
    exit();
}
