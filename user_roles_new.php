<?php
// access variables and roles
$acs_user = ''; 		// access user name
$acs_role = ''; 		// access role of the user
$acs_form = false; 		// access to buttons and edit fields based on role of the user

$roles_arr = array (
    'operator' => 'Оператор',
    'user' => 'Пользователь',
    'admin' => 'Администратор',
);  // access roles

$code_lifetime = 60;								// valid access code lifetime in minutes

// AJAX request
$acs = auth(isset($_REQUEST['code']) ? $_REQUEST['code'] : '');

if(!empty($acs))
    list($acs_user, $acs_role) = explode(';', $acs);
$acs_form = ($acs_role == 'admin' or $acs_role == 'user');

// AJAX response
if (isset($_REQUEST['code'])) {
    if (empty($acs_user)) {
        $form_code = "Для активации кнопок введите код доступа:<br><br><form id='authId'>
                    <input id='code' type='password' size='30' maxlength='32' required autofocus='true'>
                    <input type='submit' class='btn' value='OK' title='Перейти к авторизованному доступу'></form>";
        $btn_acs_user = $btn_acs_form = $btn_acs_admin = false;
        $output = 'КОД ДОСТУПА НЕ РАСПОЗНАН';
        $log_flag = false;
    } else {
        $form_code = "{$acs_user} ({$roles_arr[$acs_role]})<br><br><form id='authId'>
                    <input type='submit' class='btn' onclick='clearCookie()' value='Сбросить код доступа' title='Вернуться к анонимному доступу'></form>";
        $btn_acs_user = true;
        $btn_acs_form = $acs_form;
        $btn_acs_admin = ($acs_role == 'admin');
        $output = 'Код доступа подтверждён.';
        $log_flag = false;
    }
    echo json_encode(array('form' => $form_code, 'output' => $output, 'btn_user' => $btn_acs_user, 'btn_form' => $btn_acs_form, 'btn_admin' => $btn_acs_admin));
}

// user authentication and authorisation
// return $acs_user & $acs_role delimited by ';' or empty string in case of wrong code
function auth($code_input) {

    $usr_file = 'SCCD_trigger.usr'; 	// user's access codes file
    $access_pass = [];  				// access codes array

    // user access codes load from file
    foreach (file($usr_file) as $line) {
        list($key, $role, $pass ) = explode(';', $line) + array(NULL, NULL);
        if ($pass !== NULL)
            $access_pass[$key.';'.$role] = $pass;
    }

    // user access code check
    if (!empty($code_input)) 						// user access code was just entered
        $inp_acs_code = $code_input;
    else if (isset($_COOKIE["username"]))			// user access code was entered earlier
        $inp_acs_code = $_COOKIE["username"];
    else											// user access code was not entered yet
        $inp_acs_code = '';

    reset($access_pass);
    while (list($key, $val) = each($access_pass)) {
        if (password_verify($inp_acs_code, $val)) {	// valid user
            if (!empty($code_input)) {
                $date_time_array = getdate(time());
                $h = $date_time_array['hours'];
                $d = (int)$date_time_array['mday'];
                $m = (int)$date_time_array['month'];
                $y = (int)$date_time_array['year'];
                $w = $date_time_array['wday'];
                setcookie('username', $code_input,($w > 0 and $w < 6 and $h >= 9 and $h <= 17) ? mktime(18, 0, 0) : (time() + 3600));
            }
            return $key;
        }
    }

    return '';
}
