<?php /* by GDV 2017 - RedSys */
header('Content-Type: text/html;charset=UTF-8'); ?> <!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Управление пользователями веб-форм</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/SCCD_user.js"></script>
</head>
<body>
<?php
include 'functions/user_roles.php';

$usr_file = 'SCCD_trigger.usr';
$access_role = [];
$access_pass = [];
$log_file = 'logs/SCCD_trigger.log';
$user = '';
$new_user = '';
$new_role = '';
$edit_user = '';
$edit_role = '';
$output = '';
$edit_mode = false;

foreach (file($usr_file) as $line) {
    list($key, $role, $pass) = explode(';', $line) + array(NULL, NULL);
    if ($role !== NULL) $access_role[$key] = $role;
    if ($pass !== NULL) $access_pass[$key] = $pass;
}
ksort($access_role);
ksort($access_pass);

// top header
$title = "Управление пользователями формы \"Настройка интеграции с СТП\"";
$links = array ("");
require 'functions/header_1.php';

// web form button was pressed
if (isset($_POST['sendRequest'])) {
    switch ($_POST['sendRequest']) {
        case 'Добавить':
            $new_user = $_POST['txtName'];
            if (array_key_exists($new_user, $access_pass))
                $output = "Ошибка! Такой пользователь уже есть в списке!";
            else {
                $new_role = $_POST['txtRole'];
                $new_pass = password_hash($_POST['txtPass'], PASSWORD_BCRYPT);
                $access_role[$new_user] = $new_role;
                $access_pass[$new_user] = $new_pass;
                ksort($access_role);
                ksort($access_pass);
                $output = "Пользователь " . $new_user . " успешно добавлен с ролью " . $roles_arr[$new_role].".";
            }
            break;
        case 'Сохранить':
            $new_user = $_POST['txtName'];
            if (array_key_exists($new_user, $access_pass)) {
                $new_role = $_POST['txtRole'];
                $access_role[$new_user] = $new_role;
                $output = "Роль пользователя " . $new_user . " изменена на " . $roles_arr[$new_role] . ".";
            }
            else {
                $output = "Ошибка! Такого пользователя нет в списке!";
            }
            break;
        case 'Редактировать':
            $edit_mode = true;
            $edit_user = $_POST['rdbtn'];
            $edit_role = $access_role[$edit_user];
            break;
        case 'Удалить':
            $user = $_POST['rdbtn'];
            if (array_key_exists($user, $access_pass)) {
                unset($access_role[$user]);
                unset($access_pass[$user]);
                $output = "Пользователь " . $user . " успешно удалён.";
            }
            else
                $output = "Ошибка! Такого пользователя нет в списке!";
            break;
        case 'Отменить':
        default:
            break;
    }

    // user access codes file rewrite
    file_put_contents($usr_file, "", LOCK_EX);
    foreach ($access_role as $key => $role)
        file_put_contents($usr_file, $key . ";" . $role . ";" . $access_pass[$key] . ";\n", FILE_APPEND | LOCK_EX);
}

// top header informational message output
require 'functions/header_2.php';

?>
<br \><br \>
<table align="center" border="0" cellspacing="0" cellpadding="20" >
    <tr>
        <td width="30%" valign="top" align="center">
            <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="form1">
            <?php
            if ($edit_mode) {
                ?> <input type="submit" class="btn" name="sendRequest" value="Сохранить" <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?>/> &emsp;&emsp;&emsp;&emsp;&emsp;
                <input type="submit" class="btn" name="sendRequest" value="Отменить" <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?>/>
                <?php ;
            }
            else {
                ?> <input type="submit" class="btn" name="sendRequest" value="Добавить" <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?>/> <?php ;
            }
            ?>
            <br/><br/>
            <table border="1" cellspacing="0" cellpadding="8">
                <tr>
                    <th colspan=2>Добавление/редактирование пользователя</th>
                </tr>
                <tr>
                    <td>
                        <table cellspacing="10">
                            <tr>
                                <td> Фамилия и инициалы:</td>
                                <td><input type="text" name="txtName" size="40" maxlength="32" placeholder="Иванов А.Б." value="<?php echo $edit_user; ?>" required autofocus <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?> ></td>
                            </tr>
                            <tr>
                                <td> Роль:</td>
                                <td><select name="txtRole" size="1"> <?php foreach ($roles_arr as $key => $val) { ?>
                                            <option value="<?php echo $key; ?>" <?php echo $key == $edit_role? 'selected' : ''; ?> <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?> ><?php echo $val; ?></option><?php ;
                                        } ?> </select></td>
                            </tr>
                            <tr>
                                <td> Пароль:</td>
                                <td><input type="password" name="txtPass" size="40" maxlength="32" value = 'tivoli' <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?>
                                           pattern="[A-Za-z0-9]{6,}" title="не менее 6 символов (латинские буквы и/или цифры)" required></td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <p align="left">
            <?php
            if ($edit_mode)
                echo "В настоящее время реализовано изменение только роли пользователя.<br>В дальнейшем планируется добавить функционал изменения имени и пароля.";
            ?>
            </p>
            </form>
        </td>
        <td align="center">
            <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="form2">
            <?php
            if (!$edit_mode) {
                ?> <input type="submit" class="btn" name="sendRequest" value="Редактировать" <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?> /> &emsp;&emsp;&emsp;&emsp;&emsp;
                <input type="submit" class="btn" name="sendRequest" value="Удалить" <?php echo $acs_role == 'admin' ? '' : 'disabled'; ?> onclick="return confirm('Удалить выбранного пользователя?')" />
                <?php ;
            }
            ?>
            <br/><br/>
            <table border="1" cellspacing="0" cellpadding="8">
                <tr>
                    <th></th>
                    <th>Пользователь</th>
                    <th>Роль</th>
                </tr>
                <?php foreach ($access_role as $key => $val) {
                    echo "<tr><td ".(strcmp($key, $new_user) == 0 ? "class = 'gantt_yellow'" : "")."><input type='radio' name='rdbtn' value='".$key."' required ".($acs_role == 'admin' ? '' : 'disabled')."></td>";
                    echo "<td ".(strcmp($key, $new_user) == 0 ? "class = 'gantt_yellow'" : "").">".$key."</td>";
                    echo "<td ".(strcmp($key, $new_user) == 0 ? "class = 'gantt_yellow'" : "").">".$roles_arr[$val]."</td></tr>";
                } ?>
            </table>
            </form>
        </td>
        <td width="30%" valign="top">
            <h4 align="center">ДЛЯ СПРАВКИ:</h4><br/>
            <table class="gantt" cellspacing="0" cellpadding="8">
                <tr>
                    <th colspan="2">Описание ролей</th>
                </tr>
                <tr>
                    <td class="gantt"> Оператор</td>
                    <td class="gantt"> Базовая роль:<ul>
                            <li>Перевод в <font color="blue">режим обслуживания</font> и обратно с автоматическим отключением / включением отправки инцидентов.</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td class="gantt"> Пользователь</td>
                    <td class="gantt"> Продвинутая роль:<ul>
                            <li>Все поля редактирования и кнопки в форме, в т.ч. возможность раздельного перевода в <font color="blue">режим обслуживания</font> и отключения / включения отправки инцидентов.</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td class="gantt"> Администратор</td>
                    <td class="gantt"> Всемогущая роль:<ul>
                            <li>Все поля редактирования и кнопки в форме</li>
                            <li>Управление пользователями с доступом к форме</li>
                            <li>Удаление записей из PFR_LOCATIONS</li>
                            <li>Выгрузка ситуаций с TEMS и обновление данных для отчётов</li>
                            <li>Просмотр алгоритма формирования списка ситуаций</li>
                        </ul>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>