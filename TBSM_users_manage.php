<?php
/*
	by GDV
	2018 - RedSys
*/ 
    if (isset($_POST['addUsers']))
        header('Location: TBSM_users_add.php');
    else
        header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <link href="css/popup.css" type="text/css" rel="stylesheet">
	<title>Управление учётными записями (УЗ) TBSM</title>

    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/TBSM_user.js"></script>
</head>
<body>
<?php
    require 'functions/user_roles.php';
    require 'functions/tbsm.php';

    $User = '';
    $PassReset = '';

	$repeat = false;
    $output = '';

// **************************************************************************************************************************************************

    // top header
    $title = "Управление учётными записями (УЗ) TBSM";
    $links = array ("<a class='open_window' href='#'>Справка по функционалу УЗ</a>");
    require 'functions/header_1.php';

    // manage form button was pressed
    if (isset($_POST['sendRequestManage'])) {
        list($command, $login) = explode(';', $_POST['sendRequestManage']);
        switch ($command) {
            case 'Обновить метку времени':
                $output = "Данные об области видимости пользователя {$login} ".(rtrim(shell_exec("{$path_LDAP_sync}{$script_user_role_get_from_nco} update {$login}")) == '(1 row affected)' ? '' : 'не ')."обновлены.";
                break;
            case 'Изменить активность':
                $output = rtrim(shell_exec("{$path_LDAP_sync}{$script_user_status_change} {$login}"));
                break;
            case 'Изменить пароль':
                if (isset($_COOKIE["new_pass"])) {
                    $pas = $_COOKIE["new_pass"];
                    $output = rtrim(shell_exec($path_user_manage . "pass_change.sh $login $pas"));
                }
                else
                    $output = 'При изменении пароля произошла ошибка!';
                break;
            case 'Удалить пользователя':
                $output = "Данные об области видимости ".(rtrim(shell_exec("{$path_LDAP_sync}{$script_user_role_get_from_nco} delete {$login}")) == '(1 row affected)' ? '' : 'не ')."удалены. ";
                $output = $output . rtrim(shell_exec($path_user_manage."delete_user.sh $login"));
                break;
        }
    }

    // top header informational message output
    require 'functions/header_2.php';

    // form access restriction
    $acs_form = ($acs_role == 'admin' or $acs_role == 'user');
    if (!$acs_form) {
        echo "<h2 align='center'>У вас нет полномочий для просмотра этой формы!</h2>";
        exit;
    }

    // get information about nco users
    shell_exec($path_LDAP_sync.$script_get_nco_users);
    $nco_users = file($path_LDAP_sync.$file_TBSM_users);

    // users info to array
    foreach ($nco_users as $str ) {
        list($group, $login, $name, $access) = explode('|', $str);
        $users_array[$login]['name'] = $name;
        $users_array[$login]['access'] = $access;
        $users_array[$login]['group'][] = $group;
        $users_array[$login]['hidden'] = (isset($users_array[$login]['hidden']) and $users_array[$login]['hidden']) ? true : false;

        if (in_array($login, $service_accounts) or in_array($group, $service_groups))
            $users_array[$login]['hidden'] = true;
    }
    ksort($users_array);

    ?>
    <br \>

    <!-- exist user actions form -->
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="formManage">
        <p align="center">
            <button type="submit" name="addUsers" value="Добавить УЗ" title="Добавить учётные записи TBSM" <?php echo $acs_form ? '' : 'disabled'; ?> /><img src="images/new.png">&emsp;Добавить УЗ</button>
            &emsp;&emsp;
            <button type="button" id="service_view" title="Показать служебные учётные записи TBSM" <?php echo $acs_role == 'admin' ? '' : 'hidden'; ?> /><img src="images/eye.png">&emsp;Служебные УЗ</button>
        </p>
        <br>
        <table class='tbsm_users' align="center" cellspacing="0" cellpadding="5" border="1">
            <tr>
                <th>Логин</th>
                <th>Полное имя</th>
                <th>Тип УЗ</th>
                <th>Категория УЗ<br>(роль)</th>
                <th>Область<br>видимости</th>
                <th>Активность УЗ</th>
                <th>Членство в группах</th>
                <th>Действия</th>
            </tr>
<!--            <tr>
                <td colspan="2" class='col_filter'></td>
                <td class='col_filter' align='center'>
                    <select size="1" id="dynamic_type" title="Динамический фильтр по типу УЗ">
                        <option value = 'все' selected>все</option>
                        <option value = 'Локальный'>Локальный</option>
                        <option value = 'Доменный'>Доменный</option>
                    </select>
                </td>
                <td class='col_filter' align='center'>
                    <select size="1" id="dynamic_category" title="Динамический фильтр по категории УЗ">
                        <option value = 'все' selected>все</option>
                        <?php
/*                        foreach ($ldap_group_name as $cat => $vis)
                            echo "<option value = '{$vis}'>{$vis}</option>";
                        */?>
                    </select>
                </td>
                <td colspan="0" class='col_filter'></td>
            </tr>
-->
            <?php
            $i = 0;
            foreach ($users_array as $user => $param ) {
                echo "<tr class='row_filtered ".($param['hidden'] ? "new_records rec_hide" : "")."'>";
                    ?>
                    <!-- Логин -->
                    <td class=''>
                        <?php echo $user; ?>
                    </td>
                    <!-- Полное имя -->
                    <td class=''>
                        <?php echo $param['name']; ?>
                    </td>
                    <!-- Тип УЗ -->
                    <td class='dynamic_type'>
                        <?php
                        $ldap_group = "";
                        foreach ($param['group'] as $group)
                            if (strpos($group, 'LDAP') !== false)
                                $ldap_group = $group;
                        echo empty($ldap_group) ? 'Локальный' : 'Доменный';
                        ?>
                    </td>
                    <!-- Категория УЗ<br>(роль) -->
                    <td class='dynamic_category'>
                        <?php
                        echo $ldap_group_name[$ldap_group];
                        ?>
                    </td>
                    <!-- Область<br>видимости -->
                    <td class=''>
                        <?php
                        if (!empty($ldap_group)) {
                            list($role, $time) = explode(' ', rtrim(shell_exec("{$path_LDAP_sync}{$script_user_role_get_from_nco} select {$user}")));
                            ?>
                            <table width="100%">
                                <tr>
                                    <td><?php echo $visibility_group[$role]; ?></td>
                                    <td align="right"><button type='submit' name='sendRequestManage' value='Обновить метку времени;<?php echo $user; ?>' title='Обновить данные об области видимости пользователя <?php echo "{$user}' ".($acs_form ? '' : 'disabled'); ?> /><img src="images/refresh.png"></button></td>
                                </tr>
                                <tr>
                                    <td colspan='0'><font size='-1'><?php echo date("d.m.Y H:i", $time); ?></font></td>
                                </tr>
                            </table>
                            <?php
                        }
                        else
                            echo $visibility_group[''];
                        ?>
                    </td>
                    <!-- Активность -->
                    <td class='' align="center">
                        <?php echo $param['access'] == 1 ? "<img src='images/sit_started.png' title='УЗ активна'>" : "<img src='images/sit_stopped.png' title='УЗ неактивна'>"; ?>
                    </td>
                    <!-- Членство в группах -->
                    <td class=''>
                        <table>
                            <?php
                            foreach ($param['group'] as $group) {
                                echo "<tr>";
                                    echo "<td>";
                                        echo $group;
                                    echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </table>
                    </td>
                    <!-- Действия -->
                    <td class=''>
                        <?php
                        if ($param['access'] == 1)
                            echo "<button type='submit' name='sendRequestManage' value='Изменить активность;{$user} false' title='Сделать УЗ пользователя {$user} неактивной' onclick=\"return confirm('УЗ пользователя будет переведена в статус НЕАКТИВНА. Вы уверены?');\" ".($acs_form ? '' : 'disabled')."/><img src='images/sit_stopped.png' height='16' width='16'></button>";
                         else
                             echo "<button type='submit' name='sendRequestManage' value='Изменить активность;{$user} true' title='Сделать УЗ пользователя {$user} активной' onclick=\"return confirm('УЗ пользователя будет переведена в статус АКТИВНА. Вы уверены?');\" ".($acs_form ? '' : 'disabled')."/><img src='images/sit_started.png' height='16' width='16'></button>";

                        if (empty($ldap_group))
                            echo "<button type='submit' name='sendRequestManage' value='Изменить пароль;{$user}' title='Изменить пароль пользователя {$user}' onclick='return passReset()' ".($acs_form ? '' : 'disabled')." /><img src='images/key.png'></button>";
                        else
                            echo "<button type='submit' name='sendRequestManage' value='Изменить пароль;{$user}' title='Изменить пароль доменного пользователя здесь нельзя!' onclick='return passReset()' disabled /><img src='images/key.png'></button>";
                        ?>
                        <button type="submit" name="sendRequestManage" value="Удалить пользователя;<?php echo $user; ?>" title="Удалить пользователя <?php echo $user; ?>" onclick="return confirm('Пользователь будет удалён! Вы уверены?');" <?php echo $acs_form ? '' : 'disabled'; ?> /><img src="images/delete.png"></button>
                    </td>
                </tr> <?php
                $i++;
            }
            ?>
            <tr>
                <th colspan="0">Всего записей: <?php echo $i; ?></th>
            </tr>
        </table>
    </form>

    <br \>

    <!-- pop-up help window -->
    <div class="overlay" title=""></div>
    <div class="popup">
        <div class="close_window">x</div>
        <table border="1" cellspacing="0" cellpadding="10">
            <tr>
                <th align="center">Категория учётной записи (роль)</th>
                <th align="center">Функция</th>
                <th align="center">Ответственность</th>
            </tr>
            <tr>
                <td>Пользователь</td>
                <td>Проведение работ по устранению инцидентов</td>
                <td>Своевременное выполнение работ по обнаружению и устранению инцидентов</td>
            </tr>
            <tr>
                <td>Администратор системы</td>
                <td>Настройка Системы;<br>
                    Назначение полномочий пользователям Системы в соответствии с функциональными ролями;<br>
                    Выполнение регламентных операций с Системой.
                </td>
                <td>Своевременное выполнение работ по настройке Системы и регламентных работ</td>
            </tr>
            <tr>
                <td>Руководитель</td>
                <td>Контроль работ по устранению инцидентов и внесению изменений.</td>
                <td>Контроль корректности и сроков работ по устранению инцидентов и внесению изменений в Систему</td>
            </tr>
        </table>
    </div>
</body>
</html>
