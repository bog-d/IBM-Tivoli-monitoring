<?php
/*
    All the agents connected from all regions and TEMS-MAIN -> TBSM. DB2INST1. PFR_ALL_NODES table
	by GDV
	2017 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');

// common functions
require_once 'connections/TBSM.php';
include 'functions/remote_exec.php';
include 'functions/regions.php';

// SOAP output file
$res_file = "/usr/local/apache2/htdocs/pfr_other/logs/PFR_LOCATIONS_nodes.log";
// SOAP output file in XML format
$res_xml = "/usr/local/apache2/htdocs/pfr_other/logs/PFR_LOCATIONS_nodes.xml";

/******************************************************************************************************************/

// clear the table
$sel = "delete from DB2INST1.PFR_ALL_NODES";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);

// TEMS list
$sel = "select distinct PFR_OBJECT as PFR_OBJECT FROM DB2INST1.PFR_LOCATIONS where SERVICE_NAME = 'TEMS-MAIN' or SERVICE_NAME like 'ITM%'";
$stmt = db2_prepare($connection_TBSM, $sel);
$result = db2_execute($stmt);
while ($row = db2_fetch_assoc($stmt))
    $tems_list[] = $row['PFR_OBJECT'];

// each TEMS processing
foreach ($tems_list as $tems) {
    // query for nodes on selected TEMS
    $pass = array_key_exists ($tems, $array_exeptions) ? $array_exeptions[$tems]["root_password"] : "...";
    $hub = array_key_exists ($tems, $array_exeptions) ? $array_exeptions[$tems]["HUB"] : 'HUB'.substr($tems, 3,3);
    $region = $tems == 'tems-main' ? '101' : substr($tems, 3);

    remote_exec($tems, 22, 'root', $pass, "echo http://".$tems.":1920///cms/soap > /tmp/URLS.txt", '', false);
    remote_exec($tems, 22, 'root', $pass, "echo \"<CT_Get><userid>sysadmin</userid><password>".(array_key_exists ($tems, $array_exeptions) ? $array_exeptions[$tems]["sysadmin_password"] : "...")."</password><object>ManagedSystem</object><target>ManagedSystemName</target></CT_Get>\" > /tmp/SOAPREQ.txt", '', false);
    $res = remote_exec($tems, 22, 'root', $pass, ". /opt/IBM/ITM/config/".$tems."_ms_".$hub.".config; /opt/IBM/ITM/lx8266/ms/bin/kshsoap /tmp/SOAPREQ.txt /tmp/URLS.txt", $res_file, false);

    if ($res) {
        // XML prepare
        fclose(fopen($res_xml, 'w'));
        $xml_content = false;
        foreach (file($res_file) as $line) {
            if (strpos($line, "<DATA>") !== false)
                $xml_content = true;
            if ($xml_content)
                file_put_contents($res_xml, str_replace ("><", ">`<", $line), FILE_APPEND);
            if (strpos($line, "</DATA>") !== false)
                $xml_content = false;
        }

        // XML parsing
        $xmlStr = file_get_contents($res_xml);
        $xmlObj = simplexml_load_string($xmlStr);
        $arrXml = objectsIntoArray($xmlObj);

        // insert data into the table
        if (!empty($arrXml))
            foreach ($arrXml['ROW'] as $rec) {
                foreach ($rec as &$r)
                    $r = str_replace ("`", "", $r);
                $sel = "insert into DB2INST1.PFR_ALL_NODES (REGION, TIMESTAMP, HOST_ADDRESS, HOST_INFO, MIRROR, NAME, TYPE, STATUS, REASON, PRODUCT, MANAGING_SYSTEM, VERSION) values ('" . $region . "', '" . $rec['Timestamp'] . "', '" . $rec['Host_Address'] . "', '" . $rec['Host_Info'] . "', '" . $rec['Mirror'] . "', '" . $rec['Name'] . "', '" . $rec['Type'] . "', '" . $rec['Status'] . "', '" . $rec['Reason'] . "', '" . $rec['Product'] . "', '" . $rec['Managing_System'] . "', '" . $rec['Version'] . "')";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
            }
    }
    else {
        $sel = "insert into DB2INST1.PFR_ALL_NODES (REGION, TIMESTAMP, HOST_ADDRESS, HOST_INFO, MIRROR, NAME, TYPE, STATUS, REASON, PRODUCT, MANAGING_SYSTEM, VERSION) values ('" . $region . "', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR', 'ERROR')";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
    }
}

// database connections close
db2_close($connection_TBSM);

/******************************************************************************************************************/

function objectsIntoArray($arrObjData, $arrSkipIndices = array())
{
    $arrData = array();

    // if input is object, convert into array
    if (is_object($arrObjData)) {
        $arrObjData = get_object_vars($arrObjData);
    }

    if (is_array($arrObjData)) {
        foreach ($arrObjData as $index => $value) {
            if (is_object($value) || is_array($value)) {
                $value = objectsIntoArray($value, $arrSkipIndices); // recursive call
            }
            if (in_array($index, $arrSkipIndices)) {
                continue;
            }
            $arrData[$index] = $value;
        }
    }
    return $arrData;
}

?>
