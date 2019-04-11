<?php
	$check_types = array (  array ( "name"    => "duplicate_PFR_OBJECTSERVER",
								   "display"  => "Дубликаты в PFR_LOCATIONS (NODE + PFR_OBJECTSERVER)",
								   "comment"  => "Выполняется поиск строк в PFR_LOCATIONS, у которых совпадают поля NODE и PFR_OBJECTSERVER",
								   "function" => "duplicate" ),
						    array ( "name"    => "duplicate_SERVICE_NAME",
							        "display" => "Дубликаты в PFR_LOCATIONS (NODE + SERVICE_NAME)",
							        "comment" => "Выполняется поиск строк в PFR_LOCATIONS, у которых совпадают поля NODE и SERVICE_NAME",
								   "function" => "duplicate" ),
						    array ( "name"    => "PFR_LOCATIONS_TBSM_TEMS",
							        "display" => "PFR_LOCATIONS, TBSM и TEMS",
							        "comment" => "Выполняется сопоставление PFR_LOCATIONS и TEMS по полю NODE, а также PFR_LOCATIONS и TBSM по полю SERVICE_NAME",
								   "function" => "three_in_one" ),
                            array ( "name"    => "region_codes_PFR_LOCATIONS_and_TORS",
                                    "display" => "Коды регионов в PFR_LOCATIONS и ТОРС",
                                    "comment" => "Выполняется сопоставление полей PFR_ID_TORG и PFR_TORG таблицы PFR_LOCATIONS с кодами и наименованиями регионов в ТОРС",
                                    "function" => "region_codes" ),
						  );
?>