﻿
<?php
	header('Content-Type: text/html;charset=UTF-8');

	$database = '';
	$user = '';
	$password = '';
	$hostname = '';
	$port = 50000;
	$base_page = "";
	
	$conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$database;" .
	  "HOSTNAME=$hostname;PORT=$port;PROTOCOL=TCPIP;UID=$user;PWD=$password;";
	$conn = db2_connect($conn_string, '', '');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <title>Инструкции для настройки мониторинга</title>
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
		
    </style>
	<script type="text/javascript">
	</script>
</head>
<body>
	<?php
		if ($conn)
		{
			
			$sql = "SELECT DISTINCT LINK4INSTRUCTION, INSTRUCTION_DISPLAY_NAME FROM DB2INST1.PFR_LINK4INSTRUCTION";
			
			$stmt = db2_prepare($conn, $sql);
			$result = db2_execute($stmt);
			$row_count = 0;
			echo "<table class=\"content\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
				echo "<tr>";
					echo "<th>";
						echo "Инструкция";
					echo "</th>";
				echo "</tr>";
			while ($row = db2_fetch_assoc($stmt))
			{
				$row_count = $row_count + 1;
				echo "<tr>";
					echo "<td>";
					$LINK4INSTRUCTION=$row['LINK4INSTRUCTION'];
					$URL=$base_page.$LINK4INSTRUCTION;	
					echo "<a href=\"".$URL."\">".$row['INSTRUCTION_DISPLAY_NAME']."</a>";
					echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<br \>";
			db2_close($conn);
			?>
				<div class="info">
					<span>
						Количество строк в выборке
					</span>
					<span>
						 <?php
							echo $row_count;
						 ?>
					</span>
				</div>
			<?php			
		}
		else
		{
			?>
			<div class="error">
				<span>
					Не удается подключиться к таблице БД TBSM.PFR_LINK4INSTRUCTION
				</span>
			</div>
			<?php
		}
	?>

</body>
</html>




