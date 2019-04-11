<?php
// user authentication and authorisation
// return $acs_user & $acs_role delimited by ';' or empty string in case of wrong code
function auth($code_input) {

    $usr_file = 'SCCD_trigger.usr'; 	// user's access codes file
    $access_pass = [];  				// access codes array

    // user access code check
    if (!empty($code_input)) 						// user access code was just entered
        $inp_acs_code = $code_input;
    else if (isset($_COOKIE["username"]))			// user access code was entered earlier
        $inp_acs_code = $_COOKIE["username"];
    else											// user access code was not entered yet
        return '';

    // user access codes load from file
    foreach (file($usr_file) as $line) {
        list($key, $role, $pass ) = explode(';', $line) + array(NULL, NULL);
        if ($pass !== NULL)
            $access_pass[$key.';'.$role] = $pass;
    }

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
                setcookie('acsuser', explode(';', $key)[0],($w > 0 and $w < 6 and $h >= 9 and $h <= 17) ? mktime(18, 0, 0) : (time() + 3600));
            }
            return $key;
        }
    }

    return '';
}
