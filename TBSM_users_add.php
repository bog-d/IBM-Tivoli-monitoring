<?php
/*
	by GDV
	2018 - RedSys
*/ 
	header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
	<title>Добавление учётных записей (УЗ) TBSM</title>

    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/TBSM_user.js"></script>
</head>
<body>
<?php
    require 'functions/user_roles.php';
    require 'functions/tbsm.php';

	$Login = '';
	$Pass = '';
    $Role = '';
    $FirstName = '';
	$LastName = '';
    $FullName = '';

	$repeat = false;
    $output = '';

// **************************************************************************************************************************************************

    // top header
    $title = "Добавление учётных записей (УЗ) TBSM";
    $links = array ("<a href='TBSM_users_manage.php'>Вернуться к управлению УЗ TBSM</a>");
    require 'functions/header_1.php';

    // add local user form button was pressed
    if (isset($_POST['localUserAdd'])) {
        $Login = $_POST['txtLogin'];
        $Pass = $_POST['txtPass'];
        $Role = $_POST['lstRole'];
        $FirstName = $_POST['txtFirstName'];
        $LastName = $_POST['txtLastName'];
        $FullName = $FirstName.' '.$LastName;

        $output = rtrim(shell_exec("{$path_user_manage}{$script_create_user} {$Login} {$Pass} \"{$FullName}\""));
        $repeat = (strcmp($output, 'Пользователь с таким логином уже имеется!') == 0);
        if (!$repeat)
            shell_exec("{$path_user_manage}{$script_user_role_add} {$Login} {$Role}");
    }

    // export LDAP users form button was pressed
    if (isset($_POST['LDAPUserExport'])) {
        shell_exec($path_LDAP_sync.$script_get_ad_users);
        $output = "Выгрузка данных из LDAP обновлена.";
    }

    // add LDAP user form button was pressed
    if (isset($_POST['LDAPUserAdd'])) {
        foreach ($_POST['chkbx_add'] as $key => $value) {
            list($login, $displayname, $category) = explode('|', $key);
            $visibility = $_POST['sel'][$login];
            $buffer = shell_exec("{$path_LDAP_sync}{$script_add_user_to_tbsm} \"{$category}|{$login}|{$displayname}|{$visibility}\"");
            $output = "{$output}Пользователь {$login} ({$displayname}) " . (strpos($buffer, "успешно добавлен") === false ? 'не ' : '') . "добавлен. ";
            $output = $output . "Данные об области видимости ".(rtrim(shell_exec("{$path_LDAP_sync}{$script_user_role_get_from_nco} insert {$login} {$visibility}")) == '(1 row affected)' ? '' : 'не ')."добавлены.<br>";

        }
        shell_exec($path_LDAP_sync.$script_get_nco_users);
    }

    // get nco users
    $nco_users = file($path_LDAP_sync.$file_TBSM_users);
    foreach ($nco_users as $value ) {
        list($group, $login, $name, $access) = explode('|', $value);
        $users_array[] = $login;
    }
    $nco_users_array = array_unique($users_array);

    // get LDAP users
    $ad_users_array = array_merge(get_from_ad('LDAP_TIVOLIADMINS', $path_LDAP_sync.$file_users_LDAP_ADMINS),
        get_from_ad('LDAP_TIVOLIMGRS', $path_LDAP_sync.$file_users_LDAP_MGRS),
        get_from_ad('LDAP_TIVOLIUSERS', $path_LDAP_sync.$file_users_LDAP_TIVOLIUSERS));
    ksort($ad_users_array);

    // remove LDAP users which are nco users as well
    foreach ($ad_users_array as $login => $user)
         if (in_array($login, $nco_users_array))
            unset($ad_users_array[$login]);

    // top header informational message output
    require 'functions/header_2.php';

    // form access restriction
    $acs_form = ($acs_role == 'admin' or $acs_role == 'user');
    if (!$acs_form) {
        echo "<h2 align='center'>У вас нет полномочий для просмотра этой формы!</h2>";
        exit;
    }

    ?>
    <br \>
    <table align="center" cellpadding="10" border="1">
        <tr>
            <th>Тип УЗ</th>
        </tr>
        <tr>
            <td>
                <label><input type='radio' name='type' value='ldap'>&nbsp;Доменный</label>&emsp;&emsp;
                <label><input type='radio' name='type' value='local'>&nbsp;Локальный</label>
            </td>
        </tr>
    </table>
    <br \>

    <!-- new local user adding form -->
    <table class="loc_hide" align="center" cellpadding="10" border="1">
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="formLocalUserAdd"
              onsubmit="return confirm('Добавить в TBSM пользователя со следующими реквизитами? \n\r'+
                                            '\n\r'+
                                            '   Логин: \t\t'+txtLogin.value+'\n\r'+
                                            '   Пароль: \t'+txtPass.value+'\n\r'+
                                            '   Роль: \t\t'+lstRole.value+'\n\r'+
                                            '\n\r'+
                                            '   Фамилия: \t\t'+txtLastName.value+'\n\r'+
                                            '   Имя, отчество: \t'+txtFirstName.value+'\n\r');">
        <tr>
                <th colspan=2>
                    Реквизиты пользователя
                </th>
            </tr>
        <tr>
            <td>
                <table cellspacing="10">
                    <tr>
                        <td>Логин:</td>
                        <td><input type="text" name="txtLogin" size="40" maxlength="32" value="<?php echo $repeat ? $Login : ''; ?>" placeholder="<?php echo $repeat ? '' : 'username'; ?>" title="не менее 6 символов (латинские буквы и/или цифры)" required autofocus></td>
                    </tr>
                    <tr>
                        <td>Пароль:</td>
                        <td><input type="password"  name="txtPass" size="40" maxlength="32" pattern="[A-Za-z0-9]{6,}" title="не менее 6 символов (латинские буквы и/или цифры)" required value="tivoli"></td>
                    </tr>
                    <tr>
                        <td>Роль:</td>
                        <td>
                            <select size="1" name="lstRole">
                                <?php
                                $handle = @fopen("{$path_user_manage}{$file_roles}", "r");
                                while ($value = fgets($handle, 4096)) {
                                    ?><option value="<?php echo $value; ?>"><?php echo $value; ?></option><?php ;
                                }
                                fclose($handle);
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan=2 align="center">&nbsp;</td>
                    </tr>
                    <tr>
                        <td>Фамилия:<br><font size="-2">(опционально)</font></td>
                        <td><input type="text" name="txtLastName" size="40" maxlength="32" value="<?php echo $repeat ? $LastName : ''; ?>" placeholder="<?php echo $repeat ? '' : 'Иванов'; ?>" ></td>
                    </tr>
                    <tr>
                        <td>Имя, отчество:<br><font size="-2">(опционально)</font></td>
                        <td><input type="text" name="txtFirstName" size="40" maxlength="32" value="<?php echo $repeat ? $FirstName : ''; ?>" placeholder="<?php echo $repeat ? '' : 'Иван Иванович'; ?>" ></td>
                    </tr>
                    <tr>
                        <td colspan=2 align="center">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan=2 align="center">
                            <input type="submit" name="localUserAdd" value='Добавить пользователя' <?php echo $acs_form ? '' : 'disabled'; ?> />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        </form>
    </table>

    <!-- update LDAP users list -->
    <table class="sit_hide" align="center" cellpadding="10" border="0">
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="formLDAPUserExport">
        <tr><td>
            <input type="submit" name="LDAPUserExport" value='Обновить выгрузку данных из LDAP' <?php echo $acs_form ? '' : 'disabled'; ?> title="Эта процедура занимает несколько минут..."/>
        </td></tr>
    </form>
    </table>
    <br \>

    <!-- new LDAP user adding form -->
    <table class="sit_hide" align="center" cellpadding="10" border="1">
        <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="formLDAPUserAdd" onsubmit="return checkChecked() && confirm('Добавить в TBSM выбранных пользователей?');">
        <tr>
            <th>Логин</th>
            <th>Категория УЗ (роль)</th>
            <th>Полное имя</th>
            <th>Почта</th>
            <th>Область видимости</th>
            <th><img src="images/new.png"></th>
        </tr>
        <?php
        foreach ($ad_users_array as $login => $rec) {
            echo "<tr>";
                echo "<td>{$login}</td>";
                echo "<td>{$ldap_group_name[$rec['group']]}</td>";
                echo "<td>{$rec['displayname']}</td>";
                echo "<td>{$rec['email']}</td>";
                echo "<td align='center'>";
                    $vis = ($rec['group'] != 'LDAP_TIVOLIADMINS' and array_key_exists("PFR_R_VIEWER_{$rec['region']}", $visibility_group)) ? "PFR_R_VIEWER_{$rec['region']}" : "PFR_R_VIEWER_ALL";
                    echo "<select size='1' name='sel[$login]'>";
                        foreach ($visibility_group as $key => $val)
                            if (!empty($key))
                                echo "<option value='$key' ".($key == $vis ? 'selected ' : '').($rec['group'] == 'LDAP_TIVOLIADMINS' ? ($acs_role == 'admin' ? '' : ' disabled ') : ($acs_form ? '' : ' disabled ')).">{$val}</option>";
                    echo "</select>";
                echo "</td>";
                echo "<td align='center'>";
                    echo"<input type='checkbox' name='chkbx_add[{$login}|{$rec['displayname']}|{$rec['group']}]' ".($rec['group'] == 'LDAP_TIVOLIADMINS' ? ($acs_role == 'admin' ? '' : ' disabled ') : ($acs_form ? '' : ' disabled '))." title='Отметить для добавления в TBSM.'>";
                echo "</td>";
            echo "</tr>";
        }
        ?>
        <tr>
            <td colspan="0" align="center">
                <input type="submit" name="LDAPUserAdd" value='Добавить отмеченных пользователей' <?php echo $acs_form ? '' : 'disabled'; ?> title="Добавить отмеченные учётные записи в TBSM..."/>
            </td>
        </tr>
        </form>
    </table>

</body>
</html>
