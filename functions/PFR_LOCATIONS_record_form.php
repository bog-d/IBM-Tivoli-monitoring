<?php
// PFR_LOCATIONS table fields fill
$PFR_LOCATIONS_fields = [ ];
$sel = "SELECT NAME, COLTYPE, LENGTH, DEFAULT FROM SYSIBM.SYSCOLUMNS where TBNAME = 'PFR_LOCATIONS' order by COLNO asc";
$stmt = db2_prepare($connection_TBSM, $sel);
db2_execute($stmt);
while ($row = db2_fetch_assoc($stmt))
    $PFR_LOCATIONS_fields[trim($row['NAME'])] = array(
        'COLTYPE' => trim($row['COLTYPE']),
        'LENGTH' => trim($row['LENGTH']),
        'DEFAULT' => trim(str_replace("'", "", $row['DEFAULT'])),
        'VALUE' => '',
    );

// **************************************************************************************************************************************************

/*
    Function for actions with single PFR_LOCATIONS record
    Parameters:
        $params     - script parameters for return action
        $action     - action (see $actions_arr array above)
    Return:
        false       - action failed
        true        - action successful
*/
function record_form($params, $action)
{
    $data = $GLOBALS['PFR_LOCATIONS_fields'];
    $sub_arr = $GLOBALS['SUBCATEGORY_array'];
    $conn = $GLOBALS['connection_TBSM'];
	
	$actions_arr = array (
		"cancel"    => array (
			"text"  	=>  "Вернуться",
			"image" 	=>  "images/arrowdown.png",
			"title" 	=>  "Вернуться назад",
            "onclick"   =>  "return true;",     // return confirm('Сделанные изменения не будут сохранены! Вернуться?');",
            "access"    =>  "",
		),
		"save"      => array (
			"text"  	=>  "Сохранить",
			"image" 	=>  "images/ok.png",
			"title" 	=>  "Сохранить текущую запись",
            "onclick"   =>  "return confirm('Сохранить текущую запись?');",
            "access"    =>  "",
		),
		"clone"     => array (
			"text"  	=>  "Клонировать",
			"image" 	=>  "images/copy.png",
			"title" 	=>  "Клонировать текущую запись",
            "onclick"   =>  "return confirm('Если были сделаны изменения - они не будут сохранены! Клонировать текущую запись?');",
            "access"    =>  "",
		),
		"delete"    => array (
			"text"  	=>  "Удалить",
			"image" 	=>  "images/delete.png",
			"title" 	=>  "Удалить текущую запись",
            "onclick"   =>  "return confirm('Удалить текущую запись?');",
            "access"    =>  "",
        ),
	);

    // insert or update record to PFR_LOCATIONS
    if ($action == 'save') {
        // insert
        if (empty($data['ID']['VALUE'])) {
            $fields = "";
            $values = "";
            foreach ($data as $field => $prop) {
                if ($field != 'ID') {
                    $fields .= "{$field}, ";
                    switch ($prop['COLTYPE']) {
                        case 'INTEGER':
                            $values .= (!empty($_POST[$field]) ? intval($_POST[$field]) : intval($prop['DEFAULT'])) . ", ";
                            break;
                        case 'TIMESTMP':
                            $values .= "'" . date("Y-m-d H:i:s") . "', ";
                            break;
                        default:
                            $values .= "'" . (empty($_POST[$field]) ? $prop['DEFAULT'] : $_POST[$field]) . "', ";
                            break;
                    }
                }
            }
            $fields = rtrim($fields, ", ");
            $values = rtrim($values, ", ");

            $ins = "insert into DB2INST1.PFR_LOCATIONS ({$fields}) values ({$values})";
            $stmt = db2_prepare($conn, $ins);
            return db2_execute($stmt);
        }
        // update
        else {
            $fields_values = "";
            foreach ($data as $field => $prop) {
                if ($field != 'ID')
                    switch ($prop['COLTYPE']) {
                        case 'INTEGER':
                            $fields_values .= $field . " = " . intval($_POST[$field]) . ", "; break;
                        case 'TIMESTMP':
                            $fields_values .= $field . " = '" . date("Y-m-d H:i:s") . "', "; break;
                        default:
                            $fields_values .= $field . " = '" . $_POST[$field] . "', "; break;
                    }
            }
            $fields_values = rtrim($fields_values, ", ");

            $upd =  "update DB2INST1.PFR_LOCATIONS set ".$fields_values." where ID = {$data['ID']['VALUE']}";
            $stmt = db2_prepare($conn, $upd);
            return db2_execute($stmt);
        }
    }

    // delete record from PFR_LOCATIONS
    if ($action == 'delete') {
        $del = "delete from DB2INST1.PFR_LOCATIONS where ID = '".$data['ID']['VALUE']."'";
        $stmt = db2_prepare($conn, $del);
        return db2_execute($stmt);
    }

    // select existing record from PFR_LOCATIONS
    if ($action == 'edit') {
        $sel = "select * from DB2INST1.PFR_LOCATIONS where ID = {$data['ID']['VALUE']}";
        $stmt = db2_prepare($conn, $sel);
        if (!db2_execute($stmt))
            return false;
        if (empty($row = db2_fetch_assoc($stmt)))
            return false;
        foreach ($data as $field => &$prop)
            $prop['VALUE'] = $row[$field];
        unset($prop);
    }

    // web-form output
    echo "<h3>Запись в таблице PFR_LOCATIONS</h3>";
	echo "<form action='{$_SERVER["PHP_SELF"]}?{$params}' method='post'>";
	echo "<table border='0' cellspacing='0' cellpadding='5'>";
		foreach ($data as $field => $prop) {
			echo "<tr>";
				echo "<td>{$field}</td>";
				echo "<td>";
				    if ($field == 'SUBCATEGORY') {
                        echo "<select class = 'select_in_form' size = '1' id = 'subcategory' name = '{$field}'>";
                        foreach ($sub_arr as $key => $val)
                            echo "<option value = '{$key}' ".($key == $prop['VALUE'] ? 'selected' : '').">$key</option>";
                        echo "</select>";
                    }
                    else
                        echo "<input type='text' 
                                    name='{$field}'
                                    value='".($action == 'edit' ? $prop['VALUE'] : (empty($prop['VALUE']) ? $prop['DEFAULT'] : $prop['VALUE']))."'
                                    size='64'
                                    maxlength='{$prop['LENGTH']}'".
                                    ($prop['COLTYPE'] == 'INTEGER' ? "pattern=\"[-]?[0-9]+\"" : '').
                                    (($action == 'view' or $prop['COLTYPE'] == 'TIMESTMP' or $field == 'ID') ? 'disabled' : '').">";
				echo "</td>";
				echo "<td><font size='-2'>{$prop['COLTYPE']} ({$prop['LENGTH']})</font></td>";
			echo "</tr>";
		}
		echo "<tr><td align='center' colspan='0'><br>";
            $actions_arr['delete']['access'] = empty($data['ID']['VALUE']) ? 'disabled' : '';
			foreach ($actions_arr as $key => $value)
				echo "<button type='submit' name='sendRequest' value='{$key}' title='{$value['title']}' onclick=\"{$value['onclick']}\" {$value['access']}><img src='{$value['image']}' width='16' height='16'>&nbsp;{$value['text']}</button>&emsp;";
		echo "</td></tr>";
	echo "</table>";
	// hidden field for ID
    echo "<input hidden type='text' name='ID_hidden' value='{$data['ID']['VALUE']}'>";
	echo "</form>";
	return true;
}

// **************************************************************************************************************************************************

/*
    Function for actions with several PFR_LOCATIONS records
    Parameters:
        $params     - script parameters for return action
        $action     - action (see $actions_arr array above)
        $data       - array of strings
    Return:
        false       - action failed
        true        - action successful
*/
function several_records_form($params, $action, $data)
{
    $conn = $GLOBALS['connection_TBSM'];

    $show_fields_arr = array(
        'PFR_FO',
        'PFR_ID_FO',
        'PFR_TORG',
        'PFR_ID_TORG',
        'PFR_NAZN',
        'NODE',
        'PFR_OBJECT',
        'PFR_OBJECTSERVER',
        'SUBCATEGORY',
        'AGENT_NODE',
        'SITFILTER',
        'PFR_KE_TORS',
        'SERVICE_NAME',
        'URL',
        'TEMS',
    );
    $actions_arr = array (
        "cancel"    => array (
            "text"  	=>  "Вернуться",
            "image" 	=>  "images/arrowdown.png",
            "title" 	=>  "Вернуться назад",
            "onclick"   =>  "return true;",     // return confirm('Сделанные изменения не будут сохранены! Вернуться?');",
        ),
        "save_several"  => array (
            "text"  	=>  "Сохранить",
            "image" 	=>  "images/ok.png",
            "title" 	=>  "Добавить записи в PFR_LOCATIONS",
            "onclick"   =>  "return confirm('Добавить все записи в PFR_LOCATIONS?');",
        ),
    );
    $example_subcategory = '';
    $return_status = true;

    // insert records into PFR_LOCATIONS
    if ($action == 'save') {
        foreach ($_POST['rec'] as $rec) {
            $fields = "";
            $values = "";
            foreach ($rec as $field => $value) {
                $fields .= "{$field}, ";
                switch ($data[0][$field]['COLTYPE']) {
                    case 'INTEGER':
                        $values .= intval($value) . ", ";
                        break;
                    case 'TIMESTMP':
                        $values .= "'" . date("Y-m-d H:i:s") . "', ";
                        break;
                    default:
                        $values .= "'{$value}', ";
                        break;
                }
            }
            $fields = rtrim($fields, ", ");
            $values = rtrim($values, ", ");

            $ins = "insert into DB2INST1.PFR_LOCATIONS ({$fields}) values ({$values})";
            $stmt = db2_prepare($conn, $ins);
            $return_status = $return_status ? db2_execute($stmt) : $return_status;
        }
        return $return_status;
    }

    // web-form output
    echo "<h3>Добавление записей в таблицу PFR_LOCATIONS</h3>";
    echo "<h4 class='tr_rec_toggle'>Показать/скрыть примеры записей</h4>";
    echo "<form action='{$_SERVER["PHP_SELF"]}?{$params}' method='post'>";
    echo "<table border='1' cellspacing='0' cellpadding='5'>";
        echo "<tr>";
        foreach ($data[0] as $field => $prop)
            if (in_array($field, $show_fields_arr))
                echo "<th>{$field}</th>";
        echo "</tr>";
        $i = 0;
        foreach ($data as $rec) {
            if ($i++ == 0)
                continue;
            echo "<tr class='new_records'>";
            foreach ($data[0] as $field => $prop) {
                if (in_array($field, $show_fields_arr)) {
                    echo "<td>";
                        if ($field == 'SUBCATEGORY') {
                            echo "<select size = '1' id = 'subcategory' name = 'rec[$i][$field]'>";
                            foreach (subcategory_expected($rec['NODE']['VALUE']) as $val) {
                                echo "<option value = '{$val}'>$val</option>";
                                if ($i == 2 and empty($example_subcategory))
                                    $example_subcategory = $val;
                            }
                            echo "</select>";
                        }
                        else
                            echo "<input type='text' name='rec[$i][$field]' value='".(array_key_exists($field, $rec) ? $rec[$field]['VALUE'] : '').
                                    "' maxlength='{$prop['LENGTH']}' ".($prop['COLTYPE'] == 'INTEGER' ? "pattern=\"[-]?[0-9]+\"" : '').
                                    " onkeydown=\"this.style.width = ((this.value.length + 1) * 7) + 'px';\">";
//                          size='".(($field == 'PFR_ID_FO' or $field == 'PFR_ID_TORG' or $field == 'TEMS') ? '10' : '20')."'
                    echo "</td>";
                }
            }
            echo "</tr>";
            // example records from PFR_LOCATIONS
            if ($i == 2) {
                $sel = "select * from DB2INST1.PFR_LOCATIONS where SUBCATEGORY = '{$example_subcategory}' and PFR_NAZN <> 'AUTO_DEFAULT'";
                $stmt = db2_prepare($conn, $sel);
                if (!db2_execute($stmt))
                    return false;
                $k = 0;
                while ($row = db2_fetch_assoc($stmt)) {
                    echo "<tr class='rec_hide example_records'>";
                    foreach ($data[0] as $field => $prop)
                        if (in_array($field, $show_fields_arr))
                            echo "<td>{$row[$field]}</td>";
                    echo "</tr>";
                    if (++$k == 10)
                        break;
                }
            }
        }
    echo "</table><br>";
    foreach ($actions_arr as $key => $value)
        echo "<button type='submit' name='sendRequest' value='{$key}' title='{$value['title']}' onclick=\"{$value['onclick']}\"><img src='{$value['image']}' width='16' height='16'>&nbsp;{$value['text']}</button>&emsp;";
    echo "</form>";
    return true;
}

// **************************************************************************************************************************************************

// checks
define("CHECK_ABSENT",                                  -1);
define("CHECK_DUPLICATE",                               -2);
define("CHECK_WARNING_SERVICE_NAME_IS_EMPTY",            2);
define("CHECK_WARNING_SERVICE_NAME_IS_NOT_EQUAL_TBSM",   4);

/*
    PFR_LOCATIONS records consistency check
    Parameters:
        $conn       - connection to TBSM database
        $node       - node from TEMS
        $reg        - region from TEMS
        $ip         - IP-address from BigFix
        $service    - service instance name from TBSM
    Return: 'record ID' * 'check status'
    Check status:
        CHECK_ABSENT           - record isn't found
        CHECK_DUPLICATE        - more than one record(s) is found
        CHECK_WARNING_...      - record isn't correct
 */
function record_check($conn, $node, $reg, $ip, $service)
{
    // PFR_LOCATIONS records select
    $sel = "select * from DB2INST1.PFR_LOCATIONS 
            where (upper(AGENT_NODE) = '" . strtoupper($node) . "' or (upper(NODE) = '" . strtoupper($node) . "' and AGENT_NODE = '')) and
                    substr(PFR_OBJECTSERVER, 4, 3) = '" . $reg . "'";
    $stmt = db2_prepare($conn, $sel);
    $result = db2_execute($stmt);

    $consistency = 0;
    $id = '';
    $rec_found = 0;
    while ($row = db2_fetch_assoc($stmt)) {
        // check list
        if (empty($row['SERVICE_NAME']))
            $consistency += CHECK_WARNING_SERVICE_NAME_IS_EMPTY;
        if (strcmp($row['SERVICE_NAME'], $service))
            $consistency += CHECK_WARNING_SERVICE_NAME_IS_NOT_EQUAL_TBSM;


        $id = $row['ID'];
        $rec_found++;
    }

    if ($rec_found == 0)
        return ' *'.CHECK_ABSENT;
    else if ($rec_found > 1)
        return ' *'.CHECK_DUPLICATE;
    else
        return $id.'*'.$consistency;
}

// **************************************************************************************************************************************************
/*  Function for expecting SERVICE NAME based on node name
    Parameters:
        $node       - node name
    Return: expected SERVICE NAME (string) */

function service_expected($node) {
    $sub_node_arr = explode(':', $node);
    switch (count($sub_node_arr)) {
        case 2:     return $sub_node_arr[0];
        case 3:
        case 4:     return $sub_node_arr[1];
        default:    return $node;
    }
}

// **************************************************************************************************************************************************
/*  Function for expecting SUBCATEGORY based on node name
    Parameters:
        $node       - node name
    Return: expected SUBCATEGORYs array of strings ($SUBCATEGORY_array key) */

function subcategory_expected($node) {
    $sub_arr = $GLOBALS['SUBCATEGORY_array'];
    $agents_arr = [];

    foreach ($sub_arr as $key => $values) {
        foreach (explode('|', $values) as $val) {
            if (preg_match("/{$val}/", $node, $matches) > 0) {
                $agents_arr[] = $key;
            }
        }
    }
    return $agents_arr;
}

// **************************************************************************************************************************************************
/*
 * Examples of patterns ( [^:]* ->  0 or more symbols except )
 *      *:E9        ->      ^[^:]*:E9$
 *      *:*:34      ->      ^[^:]*:[^:]*:34$
 */
$SUBCATEGORY_array = array(
    "AEM_AGENT"				=>	"^[^:]*:E9$",
    "CACHE_DB"				=>	"^[^:]*:[^:]*:34$",
    "DATAPOWER_AGENT"		=>	"^[^:]*:[^:]*:BN$",
    "DATAPOWER_SERVER"		=>	"^BN:[^:]*:DPC$|^BN:[^:]*:DPS$",
    "DB2"					=>	"^[^:]*:[^:]*:UD$",
    "DB2_AS_SERVICE"		=>	"^DB2:[^:]*:UD:[^:]*$",
    "ELARDO_AGENT"			=>	"^[^:]*:01$",
    "ELARDO_SERVICE"		=>	"^01:[^:]*:SRV$",
    "ESXI"					=>	"^VM:[^:]*:ESX$",
    "FIREBIRD"				=>	"^[^:]*:60$",
    "HTTP_AGENT"			=>	"^[^:]*:KHTA$|^[^:]*:[^:]*:KHTA$",
    "HTTP_SERVER"			=>	"^[^:]*:[^:]*:KHTP$",
    "I5"					=>	"^[^:]*:KA4$",
    "IIS"					=>	"^[^:]*:Q7$",
    "IP_TELEPHONE"			=>	"^03:[^:]*:K03$",
    "ISM_AGENT"				=>	"^[^:]*:IS$",
    "ISM_CLUSTER"			=>	"^[^:]*:SERVICE_POLIMATIKA_ACCESS$",
    "LINUX"					=>	"^[^:]*:LZ$",
    "LINUX_AGENTLESS"		=>	"^R4:[^:]*:LNX$",
    "LO"					=>	"^LO:[^:]*$",
    "LOTUS_DOMINO"			=>	"^[^:]*:[^:]*:GB$",
    "LZ_CLUSTER"			=>	"^[^:]*:CLUSTER_ELASTICSEARCH$",
    "MAGELAN_NODE"			=>	"^[^.]*\.[^.]*\.[^.]*\.[^.]*$",
    "MESSAGE_BROKER"		=>	"^[^:]*:[^:]*:KQIB$",
    "MESSAGE_BROKER_AGENT"	=>	"^[^:]*:KQIA$|^[^:]*:[^:]*:KQIA$",
    "MQ"					=>	"^[^:]*:[^:]*:MQ$",
    "MQ_CHANNEL_GROUP"		=>	"^[^:]*:[^:]*:MQ:[^:]*$",
    "MQ_CLUSTER"			=>	"^[^:]*:[^:]*:VM-MQ$",
    "MQ_RABBIT"				=>	"^RABBIT:[^:]*:10$",
    "MSSQL"					=>	"^[^:]*:[^:]*:MS$|^[^:]*:[^:]*:MSS$",
    "MSSQL_CLUSTER"			=>	"^TEMS:SV10100008024I$",
    "MYSQL"					=>	"[^:]*:MYSQL00$",
    "NETEZZA"				=>	"^NZ[^:]*$",
    "NETW_AGENT"			=>	"^[^:]*:N4$",
    "NETW_DEVICE"			=>	"^N4:[^:]*:NMA$",
    "NTP_SERVICE"			=>	"^SERVICE_NTP_.*$",
    "PERFORMANCE_ANALYZER"	=>	"^[^:]*:PA$",
//    "PFR_ISM_SERVICE"		=>	"*:*",
//    "PFR_REQUEST"			=>	"*",
//    "PFR_RRT_SERVICE"		=>	"*",
//    "PFR_SERVICE"			=>	"*",
    "POSTGRESQL"			=>	"^[^:]*:[^:]*:PN$",
    "PTKKS_AGENT"			=>	"^[^:]*:[^:]*:43$",
//    "QRADAR_SOURCE"			=>	"*",
    "RRT_AGENT"				=>	"^[^:]*:T6$",
    "SERVICE"				=>	"^PED:SERVICE_[^:]*$",
    "SP_AGENT"				=>	"^[^:]*:SY$",
    "SSH_OS_AGENT"			=>	"^[^:]*:[^:]*:K49$",
//    "STORWIZE_DEVICE"		=>	"*",
    "SYSTEMS_DIRECTOR_NODE"	=>	"^.*.ADM.PFR.RU$",
    "TEMS"					=>	"^ITM[^:]*$",
    "TEPS"					=>	"^ITM[^:]*:TEPS$",
    "TOMCAT"				=>	"^[^:]*:[^:]*:KYJT$",
    "TSMSERVER"				=>	"^[^:]*:[^:]*:SK$",
    "TSPC_AGENT"			=>	"^[^:]*:P1$",
//    "TSPC_NODE"				=>	"*",
    "UNIVERSAL"				=>	"^[^:]*:UA$|^[^:]*:V03_UPSMONITORING00$|^[^:]*:V04_TSMSCH00$|^[^:]*:PTK_MONITORING00$|^[^:]*:STORWIZE00$",
    "UNIX_LOG"				=>	"^[^:]*:KUL$",
    "UPS_DEVICE"			=>	"^[^.]*\.[^.]*\.[^.]*\.[^.]*$",
    "VMWARE_AGENT"			=>	"^[^:]*:[^:]*:VM$",
    "VMWARE_CLUSTER"		=>	"^[^:]*-CLU-[^:]*$",
    "VMWARE_DATACENTER"		=>	"^[^:]*-VDC-[^:]*$",
    "WAS_AGENT"				=>	"^[^:]*:[^:]*:KYNA$",
    "WAS_AS_SERVICE"		=>	"^[^:]*:[^:]*:KYNS:[^:]*$",
    "WAS_CLUSTER"			=>	"^TEMS:SPUSERVER[^:]*$",
    "WAS_SERVER"			=>	"^[^:]*:[^:]*:KYNS$",
    "WAS_SUBNODE"			=>	"^[^:]*:[^:]*:KYNA:[^:]*$",
    "WH_PROXY"				=>	"^[^:]*:WAREHOUSE$",
    "WINDOWS"				=>	"^[^:]*:[^:]*:NT$",
    "WINDOWS_AGENTLESS"		=>	"^R2:[^:]*:WIN$",
    "ZOS_CLUSTER"			=>	"^TEMS:[^:]*PLEX:DB[^:]*$",
    "ZOS_DB2"				=>	"^DB[^:]*:[^:]*:DB2$",
    "ZOS_DP"				=>	"^[^:]*DB2:OSP[^:]*$",
    "ZOS_EM"				=>	"^[^:]*:CMS$",
    "ZOS_MQ"				=>	"^[^:]*:[^:]*:MQESA$",
    "ZOS_MV"				=>	"^[^:]*:[^:]*:KSDSDE$",
    "ZOS_MVSSYS"			=>	"^[^:]*:[^:]*:MVSSYS$",
    "ZOS_N3"				=>	"^[^:]*:OSP.*$",
    "ZOS_NETW"				=>	"^[^:]*:[^:]*:KN3AGENT$",
    "ZOS_OB"				=>	"^[^:]*:[^:]*:[^:]*:KOBDRA$",
    "ZOS_SG"				=>	"^[^:]*:DBP.*$|^[^:]*:MIGCAT$",
    "ZOS_STORAGE"			=>	"^[^:]*:[^:]*:STORAGE$",
    "ZOS_SYSPLEX"			=>	"^[^:]*:[^:]*:SYSPLEX$",
    "ZOS_TAPE"				=>	"^[^:]*:LIB.*$|^[^:]*:ATL.*$",
    "ZOS_WO"				=>	"^[^:]*:[^:]*:KWOSDI$",
);
