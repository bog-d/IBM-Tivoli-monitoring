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
    <title>Проверка целостности таблицы PFR_LOCATIONS</title>
    <script src="scripts/jquery-3.2.1.min.js"></script>
    <script src="scripts/common.js"></script>
</head>
<body>
	<?php
    include 'functions/pfr_checks.php';
    include 'functions/user_roles.php';

    // top header
    $title = "Проверка целостности таблицы PFR_LOCATIONS";
    $links = array ("");
    require 'functions/header_1.php';

    // top header informational message output
    require 'functions/header_2.php';

    ?>

	<form action="/pfr_other/integrity_output.php" method="post" id="formId1"> 
		<br><br><br>
	
		<table width="50%" align="center" border="1" cellpadding="10">			
			<tr>
				<td>
					<b>Тип проверки</b>
				</td>
			</tr>
			<tr>
				<td>			
					<table>	
						<tr>
							<td>
								<table cellspacing="10">	
									<?php $i=0;
									foreach ($check_types as $check) { ?>
										<tr>
											<td>
												<input type="radio" name="check" value="<?php echo $check['name']; ?>" <?php echo $i++ == 0 ? 'checked' : ''; ?>/>&nbsp;<?php echo $check['display']; ?><br>
												&emsp;&emsp;<font size=-2><?php echo $check['comment']; ?></font>
											</td>
										</tr>
										<?php ;
									} ?>
								</table>	
							</td>
							<td valign="top">
								<b>Шаблон поля NODE</b><br><br>
								<input type="text" name="NODE" maxlength="32" size="50" value="%" autofocus="true"><br><br>
								<font size=-2>В шаблоне для фильтрации допустимы символы:
									<table cellspacing="10">	
										<tr>
											<td>%</td>
											<td>Любая строка, содержащая ноль или более символов.</td>
										</tr>
										<tr>
											<td>_</td>
											<td>Любой одиночный символ.</td>
										</tr>
										<tr>
											<td>[ ]</td>
											<td>	Любой одиночный символ, содержащийся в диапазоне ([a-f]) или наборе ([abcdef]).</td>
										</tr>
										<tr>
											<td>[^]</td>
											<td>Любой одиночный символ, не содержащийся в диапазоне ([^a-f]) или наборе ([^abcdef]).</td>
										</tr>
									</table>	
								</font>
                                <br><br><b>SERVICE для сверки с ТОРС</b><br><br>
                                <input type="text" name="SERVICE" maxlength="64" size="50" value="">
							</td>
						</tr>
					</table>	
				</td>
			</tr>
			<tr>
				<td align="center" colspan=0>
					<input type="submit" class="btn" name="sendRequest" value="Выполнить проверку">
				</td>
			</tr>
		</table>
		
	</form>			
</body>
</html>
