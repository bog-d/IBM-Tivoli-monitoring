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
	<div class="Table">
    <div class="Title">
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
    <div class="Row">
        <div class="Cell">
            <p>Последнее вхождение</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_LastOccurrence"]))
				{
					echo date("Y/m/d H:i:s",$_GET["\$selected_rows_LastOccurrence"]);
				}
				?>
			</p>
        </div>
    </div>
    <div class="Row">
        <div class="Cell">
            <p>Первое вхождение</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_FirstOccurrence"]))
				{
					echo date("Y/m/d H:i:s",$_GET["\$selected_rows_FirstOccurrence"]);
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Счетчик событий</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_Tally"]))
				{
					echo $_GET["\$selected_rows_Tally"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Федеральный округ</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_fo"]))
				{
					echo $_GET["\$selected_rows_pfr_fo"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>ОПФР</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_torg"]))
				{
					echo $_GET["\$selected_rows_pfr_torg"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Подкатегория</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_nazn"]))
				{
					echo $_GET["\$selected_rows_pfr_nazn"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>ПТК</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_ptk"]))
				{
					echo $_GET["\$selected_rows_pfr_ptk"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Классификатор</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_tsrm_code"]))
				{
					echo $_GET["\$selected_rows_pfr_tsrm_code"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Объект мониторинга</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_object"]))
				{
					echo $_GET["\$selected_rows_pfr_object"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Описание</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_description"]))
				{
					echo $_GET["\$selected_rows_pfr_description"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Инцидент</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_pfr_ttnumber_manual"]))
				{
					echo $_GET["\$selected_rows_pfr_ttnumber_manual"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Статус инцидента</p>
        </div>
        <div class="Cell">
            <p>
				<?php
				if (isset($_GET["\$selected_rows_TicketStatus"]))
				{
					echo $_GET["\$selected_rows_TicketStatus"];
				}
				?>
			</p>
        </div>
    </div>
	<div class="Row">
        <div class="Cell">
            <p>Заявка (ручн.)</p>
        </div>
        <div class="Cell">
            <p>
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
