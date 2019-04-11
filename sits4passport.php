<?php
	header('Content-Type: text/html;charset=UTF-8');

	$database = 'WHMSK';
	$user = 'db2inst1';
	$password = 'passw0rd';
	$hostname = '10.103.0.58';
	$port = 50000;
	$base_page = "http://10.103.0.60/pfr_other/Templates/";
	
	$conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database;" .
	  "HOSTNAME=$hostname;PORT=$port;PROTOCOL=TCPIP;UID=$user;PWD=$password;";
	$conn = db2_connect($conn_string, '', '');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <title>Справка по ситуациям для формирования паспортов</title>
    <style>
        span {
			margin-right: 5px;
		}
		.info {
			border: 1px solid blue;
			padding: 5px;
		}
		.content {
			margin: 5px;
		}
		.warning {
			border: 2px solid orange;
			padding: 5px;
			margin: 5px;
		}
		.error {
			border: 2px solid red;
			padding: 5px;
			margin: 5px;
		}
		.error td {
			border: 2px solid red;
		}
		
    </style>
	<script type="text/javascript">
	</script>
</head>
<body>
	<FORM action="sits4passport.php" method="post">
	   <P>
	   <TEXTAREA name="myListOfSituations" rows="20" cols="80"><?php if (isset($_POST["myListOfSituations"])){echo $_POST["myListOfSituations"];} ?></TEXTAREA>
	   <INPUT type="submit" value="Send">
	   </P>
	</FORM>
	<?php
		$input = '';
		$sits_array='';
		if (isset($_POST["myListOfSituations"]))
		{
			if (strlen($_POST['myListOfSituations'])==0)
			{
				?>
				<div class="error">
					<span>
						Нет данных в поле для ввоода
					</span>
				</div>
				<?php
			}
			else
			{
				if ($conn)
				{
					$input = $_POST["myListOfSituations"];
					$sits_array = explode("\n", str_replace("\r", "", $input));
					echo "<table class=\"content\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
					echo "<tr>";
						echo "<th>"; echo "SIT_DISPLAY_NAME"; echo "</th>";
						echo "<th>"; echo "SIT_CODE"; echo "</th>";
						echo "<th>"; echo "SIT_DESCRIPTION"; echo "</th>";
						echo "<th>"; echo "SIT_FORMULA_DESCRIPTION"; echo "</th>";
						echo "<th>"; echo "SEVERITY_EIF"; echo "</th>";
						echo "<th>"; echo "SAMPLE_RATE"; echo "</th>";
						//echo "<th>"; echo "AGENT_NAME"; echo "</th>";
						//echo "<th>"; echo "SIT_FORMULA"; echo "</th>";
					echo "</tr>";
					foreach($sits_array as $entry)
					{
						$sql = "SELECT * FROM UMSK.PFR_SITS4PASSPORT WHERE SIT_DISPLAY_NAME = '".$entry."'";
						$stmt = db2_prepare($conn, $sql);
						$result = db2_execute($stmt);
						$row_count = 0;
						while ($row = db2_fetch_assoc($stmt))
						{
							$row_count = $row_count + 1;
							echo "<tr>";
								?>
								<td><?php echo $row['SIT_DISPLAY_NAME']; ?></td>
								<td><?php echo $row['SIT_CODE']; ?></td>
								<td><?php echo $row['SIT_DESCRIPTION']; ?></td>
								<td><?php echo $row['SIT_FORMULA_DESCRIPTION']; ?></td>
								<td><?php echo $row['SEVERITY_EIF']; ?></td>
								<td><?php echo $row['SAMPLE_RATE']; ?></td>
								<!--<td><?php echo $row['AGENT_NAME']; ?></td>-->
								<!--<td><?php echo $row['SIT_FORMULA']; ?></td>-->
								<?php
								//SIT_DESCRIPTION VARCHAR(512),
								//SIT_FORMULA VARCHAR(512),
								//SIT_FORMULA_DESCRIPTION VARCHAR(512),
								//SEVERITY_EIF VARCHAR(16),
								//SEVERITY_TEPS VARCHAR(16),
								//SAMPLE_RATE VARCHAR(16),
								//EIF_ENABLED VARCHAR(128),
								//AGENT_NAME VARCHAR(64),
							echo "</tr>";
						}
						if ($row_count > 1)
						{
							echo "<tr>";
								echo "<td class = 'error'>"; echo "ОШИБКА - более одного определения ситуации"; echo "</td>";
								echo "<td class = 'error'>"; echo "ОШИБКА - более одного определения ситуации"; echo "</td>";
								echo "<td class = 'error'>"; echo "ОШИБКА - более одного определения ситуации"; echo "</td>";
								echo "<td class = 'error'>"; echo "ОШИБКА - более одного определения ситуации"; echo "</td>";
								echo "<td class = 'error'>"; echo "ОШИБКА - более одного определения ситуации"; echo "</td>";
								echo "<td class = 'error'>"; echo "ОШИБКА - более одного определения ситуации"; echo "</td>";
							echo "</tr>";
						}
					}
					echo "</table>";
					echo "<br \>";
					db2_close($conn);
				}
				else
				{
					?>
					<div class="error">
						<span>
							Не удается подключиться к таблице БД TBSM.PFR_SITS4PASSPORT
						</span>
					</div>
					<?php
				}	
			}
		}
		
		
		//phpinfo();
	?>
	

</body>
</html>




