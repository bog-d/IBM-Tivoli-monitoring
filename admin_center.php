<?php
/*
	by GDV
	2017 - RedSys
*/ 
	header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
	<link href="css/style.css" type="text/css" rel="stylesheet">
    <link href="css/popup.css" type="text/css" rel="stylesheet">

    <title>Админская панель управления</title>
	<style>
		table { border-spacing: 0; background: #0C0B11 }
	</style>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
    <script src="scripts/TBSM_user.js"></script>
</head>
<body bgcolor="#000000">
	<?php	
		include 'functions/user_roles.php';

        $acs_user = $acs_role = '';
		$output = '(авторизуйтесь для активации ссылок)';

        // user access codes load from file and check
        $acs = auth(isset($_POST['txtpass']) ? $_POST['txtpass'] : '');
        if(!empty($acs))
            list($acs_user, $acs_role) = explode(';', $acs);
    ?>
	
	<table align="center">
        <tr>
			<td valign="top"><font color="white" size="+1">
				<?php				
					if (empty($acs_user)) {
						if (isset($_POST['txtpass']))
							$output = 'КОД ДОСТУПА НЕ РАСПОЗНАН';
						?> <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="cookieId">
							<br>&nbsp;&nbsp;&nbsp;Код доступа:&nbsp;&nbsp;<input type="password" name="txtpass" size="20" maxlength="20" required autofocus="true">
							<input type="submit" class="btn_blue" name="cookie_clear" value="OK"/> 
						</form> <?php
                        echo "&emsp;<font size='-1'>$output</font>";
					}
					else {
                        ?><form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" id="cookieId"><br>
                            <?php echo $acs_user; ?>&emsp;
                            <input type="submit" class="btn_blue" name="cookie_clear" onclick="clearCookie()" value="Выйти"/>
                        </form><?php
                    }
				?>
			</font></td>
            <td align="right" valign="bottom">
                <a class='open_window' href='#' title="Справочная информация"><img src="images/help.png" width="25" height="25"></a>
            </td>
		</tr>
		<tr>
			<td colspan="0"><img src="images/admin_center.jpg" width="1000" height="512" alt="Навигация"
                    <?php echo $acs_role == 'admin' ? "usemap='#Admin_Navigation'" : ''; ?>
                    <?php echo $acs_role == 'user' ? "usemap='#User_Navigation'" : ''; ?>
            ></td>
        </tr>
	</table>
  
	<p><map name="Admin_Navigation">
		<area coords=" 15,420,350,500" href="integrity_check.php" title="Проверка целостности PFR_LOCATIONS по различным критериям" target="_blank">
		<area coords="720,390,930,460" href="users_manage.php" title="Управление пользователями" target="_blank">
		<area coords="790,250,990,320" href="Stopped_sutuations.php" title="Список остановленных ситуаций" target="_blank">
        <area coords=" 10,290,300,360" href="Sits_constructor.php" title="Конструктор ситуаций мониторинга" target="_blank">
        <area coords="400,440,690,480" href="incident_close.php" title="Закрытие инцидентов в СТП" target="_blank">
	</map></p>

    <p><map name="User_Navigation">
            <area coords="720,390,930,460" href="TBSM_users_manage.php" title="Управление пользователями" target="_blank">
    </map></p>

    <!-- pop-up help window -->
    <div class="overlay" title=""></div>
    <div class="popup">
        <div class="close_window">x</div>
        <b>Портал администратора мониторинга</b> - это панель со ссылками для быстрого перехода к инструментам, используемым при администрировании системы мониторинга:
        <ul>
            <li><b>Конструктор ситуаций мониторинга</b></li>
                &emsp; предназначен для создания, клонирования, редактирования и удаления ситуаций по разработанным шаблонам.<br><br>
            <li><b>Проверка целостности таблицы PFR_LOCATIONS</b></li>
                &emsp; по заданным комбинациям полей и шаблонам ищет дубликаты записей в PFR_LOCATIONS, а также выполняет для этой таблицы сопоставление сервисов с TBSM и кодов регионов с ТОРС.<br><br>
            <li><b>Закрытие инцидентов</b></li>
                &emsp; позволяет закрывать списком заданные инциденты в СТП.<br><br>
            <li><b>Остановленные ситуации</b></li>
                &emsp; список содержит ситуации мониторинга с установленным флагом автозапуска в статусе "Остановлена" или "Не назначена".<br><br>
            <li><b>Управление пользователями</b></li>
                &emsp; формы для просмотра и добавления пользователей в TBSM и для управления пользователями, имеющими доступ к административным веб-формам.
        </ul>
        Для перехода по ссылкам необходимо авторизоваться, введя код доступа. Этот же код доступа используется и для всех остальных веб-форм.
    </div>

</body>
</html>