<?php
	header('Content-Type: text/html;charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <META content="text/html; charset=UTF-8" http-equiv="Content-Type">
    <title>Шаблон для заведения заявок в ручном режиме</title>
    <style>
        .Table
		{
			display: table;
		}
		.Title
		{
			display: table-caption;
			text-align: center;
			font-weight: bold;
			font-size: larger;
		}
		.Heading
		{
			display: table-row;
			font-weight: bold;
			text-align: center;
		}
		.Row
		{
			display: table-row;
		}
		.Cell
		{
			display: table-cell;
			border: solid;
			border-width: thin;
			padding-left: 2px;
			padding-right: 2px;
		}
    </style>
	<script>
	</script>
</head>
<body>
	<?php
	//phpinfo();
	?>
	<div>
    <div>
        <!--<p>Данные для заведения заявки в ТП в ручном режиме</p>-->
    </div>
    <!--<div class="Heading">
        <div class="Cell">
            <p>Наименование параметра</p>
        </div>
        <div class="Cell">
            <p>Значение</p>
        </div>
    </div>-->
    <div >
        <div>
            <p>Последнее вхождение: 
 				<?php
				if (isset($_GET["\$selected_rows_LastOccurrence"]))
				{
					echo date("Y/m/d H:i:s",$_GET["\$selected_rows_LastOccurrence"]);
				}
				?>
			</p>
        </div>
    </div>
    <div>
        <div>
            <p>Первое вхождение: 
				<?php
				if (isset($_GET["\$selected_rows_FirstOccurrence"]))
				{
					echo date("Y/m/d H:i:s",$_GET["\$selected_rows_FirstOccurrence"]);
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Счетчик событий:  
				<?php
				if (isset($_GET["\$selected_rows_Tally"]))
				{
					echo $_GET["\$selected_rows_Tally"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Федеральный округ:
				<?php
				if (isset($_GET["\$selected_rows_pfr_fo"]))
				{
					echo $_GET["\$selected_rows_pfr_fo"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>ОПФР:
				<?php
				if (isset($_GET["\$selected_rows_pfr_torg"]))
				{
					echo $_GET["\$selected_rows_pfr_torg"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Подкатегория:

				<?php
				if (isset($_GET["\$selected_rows_pfr_nazn"]))
				{
					echo $_GET["\$selected_rows_pfr_nazn"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>ПТК:
        
				<?php
				if (isset($_GET["\$selected_rows_pfr_ptk"]))
				{
					echo $_GET["\$selected_rows_pfr_ptk"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Классификатор:

				<?php
				if (isset($_GET["\$selected_rows_pfr_tsrm_code"]))
				{
					echo $_GET["\$selected_rows_pfr_tsrm_code"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Объект мониторинга:

				<?php
				if (isset($_GET["\$selected_rows_pfr_object"]))
				{
					echo $_GET["\$selected_rows_pfr_object"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Описание:

				<?php
				if (isset($_GET["\$selected_rows_pfr_description"]))
				{
					echo $_GET["\$selected_rows_pfr_description"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Инцидент:

				<?php
				if (isset($_GET["\$selected_rows_TTNumber"]))
				{
					echo $_GET["\$selected_rows_TTNumber"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Статус инцидента:

				<?php
				if (isset($_GET["\$selected_rows_TicketStatus"]))
				{
					echo $_GET["\$selected_rows_TicketStatus"];
				}
				?>
			</p>
        </div>
    </div>
	<div>
        <div>
            <p>Заявка (ручн.):
				<?php
				if (isset($_GET["\$selected_rows_pfr_ttnumber_manual"]))
				{
					echo $_GET["\$selected_rows_pfr_ttnumber_manual"];
				}
				?>
			</p>
        </div>
    </div>
</div>
</body>
</html>
