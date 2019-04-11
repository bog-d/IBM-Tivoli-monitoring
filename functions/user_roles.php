<?php

$acs_user = ''; 		// access user name
$acs_role = ''; 		// access role of the user
$acs_form = false; 		// access to buttons and edit fields based on role of the user

$roles_arr = array (    // access roles
    'operator' => 'Оператор',
	'user' => 'Пользователь',
	'admin' => 'Администратор',
    );

// **************************************************************************************************************************************************

require_once 'functions/auth.php';

// user access codes load from file and check
$acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
if(!empty($acs))
    list($acs_user, $acs_role) = explode(';', $acs);
$acs_form = ($acs_role == 'admin' or $acs_role == 'user');

