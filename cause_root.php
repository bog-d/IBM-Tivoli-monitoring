<?php
/*
	by GDV
	2016 - RedSys
*/ 
	header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
	<link href="css/style.css" type="text/css" rel="stylesheet">
    <title>Корневая причина события мониторинга</title>
</head>
<body>
	<?php
	$sql_in = 'cause_root_in.sql'; 			// sql command input file
	$serial = '';
	$pfr_cause_serial = '';
	
	$metrics = array ("Titles" => array ("LastOccurrence" => "Последнее вхождение",
							 "FirstOccurrence" => "Первое вхождение",
					   	 	 "Tally" => "Счётчик событий", 
					   	 	 "pfr_fo" => "Федеральный округ", 
					   	 	 "pfr_torg" => "ОПФР", 
					   	 	 "pfr_subcategory" => "Подкатегория", 
					   	 	 "pfr_ptk" => "Наименование", 
					   	 	 "pfr_ke_tors" => "КЭ", 
					   	 	 "pfr_description" => "Описание", 
					   	 	 "TTNumber" => "Инцидент", 
					   	 	 "TicketStatus" => "Рабочее задание", 
					   	 	 "pfr_tsrm_class" => "АС ТП",
					   	 	 "pfr_object" => "Объект мониторинга",
					   	 	 "pfr_nazn" => "Назначение",
					   	 	 "pfr_tsrm_code" => "Классификатор",
					   	 	 "pfr_sit_name" => "Код проблемы"
							)); 			// parameters from alerts.status table
	
	$metrics["Selected"] = $metrics["Titles"];
	$metrics["Root"] = $metrics["Titles"];
	
	// get script parameters
	$serial = $_GET["SERIAL"];
	
	// top header informational message output	
	echo "<table width=\"100%\" border=\"0\" cellspacing=\"0\" cellpadding=\"10\" class=\"page_title\">";				
		echo "<tr>";
			echo "<td align=\"center\">";
				echo "<h3>Корневая причина события мониторинга</h3>";
			echo "</td>";
		echo "</tr>";
	echo "</table>";
	echo "<br>";
	
	foreach (array_keys($metrics["Titles"]) as $param) {
		$command = "select ".$param." from alerts.status where Serial = ".$serial;
		file_put_contents($sql_in, $command.";\ngo\n", LOCK_EX);
		
		unset($out);
		$metrics["Selected"][$param] = "";
		exec("/opt/IBM/tivoli/netcool/omnibus/bin/nco_sql -user root -password passw0rdt1vol1 -server NCOMS < $sql_in", $out);
		for ($i = 2; $i < count($out)-1; $i++ ) 
			$metrics["Selected"][$param] = $metrics["Selected"][$param].$out[$i];
	}

	$command = "select pfr_cause_serial from alerts.status where Serial = ".$serial;
	file_put_contents($sql_in, $command.";\ngo\n", LOCK_EX);

	unset($out);	
	exec("/opt/IBM/tivoli/netcool/omnibus/bin/nco_sql -user root -password passw0rdt1vol1 -server NCOMS < $sql_in", $out);
	for ($i = 2; $i < count($out)-1; $i++ ) 
		$pfr_cause_serial = $pfr_cause_serial.$out[$i];
	
	if ($pfr_cause_serial != "0") {
		foreach (array_keys($metrics["Titles"]) as $param) {
			$command = "select ".$param." from alerts.status where Serial = ".$pfr_cause_serial;
			file_put_contents($sql_in, $command.";\ngo\n", LOCK_EX);
			
			unset($out);
			$metrics["Root"][$param] = "";
			exec("/opt/IBM/tivoli/netcool/omnibus/bin/nco_sql -user root -password passw0rdt1vol1 -server NCOMS < $sql_in", $out);
			for ($i = 2; $i < count($out)-1; $i++ ) 
				$metrics["Root"][$param] = $metrics["Root"][$param].$out[$i];
		}
	}
	
	// form output																		
	echo "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\">";
		echo "<tr>";				
			foreach ($metrics["Titles"] as $key => $value) {
				echo "<th>";
					echo $value;
				echo "</th>";	
			}			
		echo "</tr>";
		echo "<tr>";				
			foreach ($metrics["Selected"] as $key => $value) {
				echo "<td>";
					echo $value;
				echo "</td>";
			}			
		echo "</tr>";				
		echo "<tr>";	
			echo "<td colspan=".count($metrics["Titles"])." align=\"center\">";
				echo "<br><img src=\"images/arrowdown.png\" title=\"Корневая причина\" align=\"middle\" hspace=20>КОРНЕВАЯ ПРИЧИНА<img src=\"images/arrowdown.png\" title=\"Корневая причина\" align=\"middle\" hspace=20><br>";
			echo "</td>";
		echo "</tr>";				
		echo "<tr>";	
			if ($pfr_cause_serial != "0") 
				foreach ($metrics["Root"] as $key => $value) {
					echo "<td>";
						echo $value;
					echo "</td>";
				}	
			else {
				echo "<td colspan=".count($metrics["Titles"])." align=\"center\">";
					echo "<br>У данного события нет корневой причины.<br>";
				echo "</td>";
			}
		echo "</tr>";				
	echo "</table><br \>";
	
	?>
</body>
</html>