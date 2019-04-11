<?php
/*
	by GDV
	2018 - RedSys
*/
header('Content-Type: text/html;charset=UTF-8');

require_once 'connections/MAXDB76.php';
require_once 'connections/WHFED.php';

// log file
$log_file = '/usr/local/apache2/htdocs/pfr_other/logs/events_enrich.log';

// arrays for CLASSIFICATIONID, CLASSIFICATIONGROUP, WONUM
$classificationids = array();
$classificationgroups = array();
$wonums = array();
$changedates = array();

// arrays for unique SERIAL
$serial_arr = array ( 'SERIAL' => array (
    'CLASSIFICATIONID' => '',
    'CLASSIFICATIONGROUP' => '',
    'WONUM' => '',
    'CHANGEDATE' => '',
));
$ser_wonum_arr = array ( 'SERIAL' => array (
    'WONUM' => '',
    'CHANGEDATE' => '',
));

// **************************************************************************************************************************************************

file_put_contents($log_file, "----------------------------------------------------\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, date('d.m.Y H:i:s')."\n\n", FILE_APPEND | LOCK_EX);
$begin_time = time();

// select NEW records with existing incident number
file_put_contents($log_file, "1) Запрос классификаций для новых инцидентов:\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "ID \t\t\t SERIAL \t TTNUMBER \t CLASSIFICATIONID \t CLASSIFICATIONGROUP \t WONUM \n", FILE_APPEND | LOCK_EX);

$sel = "select ID, SERIAL, TTNUMBER
        from DB2INST1.PFR_EVENT_HISTORY
        where TTNUMBER <> '' and PFR_TSRM_WORDER is null
        and WRITETIME > '".date('Y-m-d', time()-86400)."'";
$stmt = db2_prepare($connection_WHFED, $sel);
$result = db2_execute($stmt);
$i = 0;
while ($row = db2_fetch_assoc($stmt)) {
    // new SERIAL put on array
    if (!array_key_exists($row['SERIAL'], $serial_arr)) {
        $classificationids = [];
        $classificationgroups = [];
        $wonums = [];
        $changedates = [];

        // get CLASSIFICATIONID(s)
        $sel_1 = "select distinct inc.CLASSIFICATIONID
            from MAXIMO.INCIDENT inc 
            left join MAXIMO.ciclass cc 
            on inc.CINUM = cc.CINUM and inc.FAILURECODE = cc.FAILURECODE 
            where ticketid = '" . $row['TTNUMBER'] . "'";
        $stmt_1 = db2_prepare($connection_SCCD, $sel_1);
        $result_1 = db2_execute($stmt_1);
        while ($row_1 = db2_fetch_assoc($stmt_1))
            $classificationids [] = $row_1['CLASSIFICATIONID'];

        // get CLASSIFICATIONGROUP(s)
        $sel_2 = "SELECT CASE WHEN DISPLAYNAME = 'Дежурная смена УТЭ' THEN 'УТЭ' 
                          WHEN DISPLAYNAME = 'Дежурная смена УПЭ' THEN 'УПЭ' 
                     END AS CLASSIFICATIONGROUP 
              FROM MAXIMO.PERSON 
              WHERE DISPLAYNAME IN ('Дежурная смена УТЭ', 'Дежурная смена УПЭ') AND
                    PERSONID IN (SELECT RESPPARTYGROUP 
                                 FROM MAXIMO.PERSONGROUPTEAM 
                                 WHERE PERSONGROUP IN (SELECT PERSONGROUP 
                                                       FROM MAXIMO.CLASSSTRUCTURE 
                                                       WHERE CLASSSTRUCTUREID IN (SELECT CLASSSTRUCTUREID 
                                                                                  FROM MAXIMO.INCIDENT 
                                                                                  WHERE HISTORYFLAG = 0 AND TICKETID = '" . $row['TTNUMBER'] . "')))";
        $stmt_2 = db2_prepare($connection_SCCD, $sel_2);
        $result_2 = db2_execute($stmt_2);
        while ($row_2 = db2_fetch_assoc($stmt_2))
            $classificationgroups [] = $row_2['CLASSIFICATIONGROUP'];

        // get WONUM(s)
        $sel_3 = "select WONUM, CHANGEDATE
                  from MAXIMO.WORKORDER 
                  where ORIGRECORDCLASS = 'INCIDENT' and ORIGRECORDID = '".$row['TTNUMBER']."'";
        $stmt_3 = db2_prepare($connection_SCCD, $sel_3);
        $result_3 = db2_execute($stmt_3);
        while ($row_3 = db2_fetch_assoc($stmt_3)) {
            $wonums [] = $row_3['WONUM'];
            $changedates [] = $row_3['CHANGEDATE'];
        }

        // array record add
        $serial_arr[$row['SERIAL']] = array (
            'CLASSIFICATIONID' => implode(', ', $classificationids),
            'CLASSIFICATIONGROUP' => implode(', ', $classificationgroups),
            'WONUM' => implode(', ', $wonums),
            'CHANGEDATE' => implode(', ', $changedates),
        );
    }

    if (!empty($serial_arr[$row['SERIAL']]['CLASSIFICATIONID'])) {
        // record update
        $upd = "update DB2INST1.PFR_EVENT_HISTORY
            set CLASSIFICATIONID = '" . $serial_arr[$row['SERIAL']]['CLASSIFICATIONID'] . "',
                CLASSIFICATIONGROUP = '" . $serial_arr[$row['SERIAL']]['CLASSIFICATIONGROUP'] . "',
                PFR_TSRM_WORDER= '" . $serial_arr[$row['SERIAL']]['WONUM'] . "'
            where ID = " . $row['ID'];
        $stmt_upd = db2_prepare($connection_WHFED, $upd);
        $result_upd = db2_execute($stmt_upd);

        file_put_contents($log_file, $row['ID'] . "\t" . $row['SERIAL'] . "\t" . $row['TTNUMBER'] . "\t" . $serial_arr[$row['SERIAL']]['CLASSIFICATIONID'] . "\t" . (empty($serial_arr[$row['SERIAL']]['CLASSIFICATIONGROUP']) ? '------' : $serial_arr[$row['SERIAL']]['CLASSIFICATIONGROUP']) . "\t" . $serial_arr[$row['SERIAL']]['WONUM'] . "\t(" . $serial_arr[$row['SERIAL']]['CHANGEDATE']. ")\n", FILE_APPEND | LOCK_EX);
        $i++;
    }
}

file_put_contents($log_file, "\nОбновлено записей: ".$i."\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "Время выполнения: ".(time() - $begin_time)." c \n\n", FILE_APPEND | LOCK_EX);
$begin_time = time();

// **************************************************************************************************************************************************

// select OLD records with incident number without WONUM
file_put_contents($log_file, "2) Запрос номеров РЗ для инцидентов с классификациями:\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "ID \t\t\t SERIAL \t TTNUMBER \t WONUM \n", FILE_APPEND | LOCK_EX);

$sel = "select ID, SERIAL, TTNUMBER
        from DB2INST1.PFR_EVENT_HISTORY
        where TTNUMBER <> '' and PFR_TSRM_WORDER = ''
        and WRITETIME > '".date('Y-m-d', time()-86400)."'";
$stmt = db2_prepare($connection_WHFED, $sel);
$result = db2_execute($stmt);
$i = 0;
while ($row = db2_fetch_assoc($stmt)) {
    // not just filled records
    if (!array_key_exists($row['SERIAL'], $serial_arr)) {
        // new SERIAL put on array
        if (!array_key_exists($row['SERIAL'], $ser_wonum_arr)) {
            $wonums = [];
            $changedates = [];

            // get WONUM(s)
            $sel_3 = "select WONUM, CHANGEDATE
                      from MAXIMO.WORKORDER 
                      where ORIGRECORDCLASS = 'INCIDENT' and ORIGRECORDID = '" . $row['TTNUMBER'] . "'";
            $stmt_3 = db2_prepare($connection_SCCD, $sel_3);
            $result_3 = db2_execute($stmt_3);
            while ($row_3 = db2_fetch_assoc($stmt_3)) {
                $wonums [] = $row_3['WONUM'];
                $changedates [] = $row_3['CHANGEDATE'];
            }

            // array record add
            $ser_wonum_arr[$row['SERIAL']] = array (
                'WONUM' => implode(', ', $wonums),
                'CHANGEDATE' => implode(', ', $changedates),
            );
        }

        if (!empty($ser_wonum_arr[$row['SERIAL']]['WONUM'])) {
            $upd = "update DB2INST1.PFR_EVENT_HISTORY
            set PFR_TSRM_WORDER= '" . $ser_wonum_arr[$row['SERIAL']]['WONUM'] . "'
            where ID = " . $row['ID'];
            $stmt_upd = db2_prepare($connection_WHFED, $upd);
            $result_upd = db2_execute($stmt_upd);

            file_put_contents($log_file, $row['ID'] . "\t" . $row['SERIAL'] . "\t" . $row['TTNUMBER'] . "\t" . $ser_wonum_arr[$row['SERIAL']]['WONUM'] . "\t(" . $ser_wonum_arr[$row['SERIAL']]['CHANGEDATE']. ")\n", FILE_APPEND | LOCK_EX);
            $i++;
        }
    }
}

file_put_contents($log_file, "\nОбновлено записей: ".$i."\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "Время выполнения: ".(time() - $begin_time)." c \n\n", FILE_APPEND | LOCK_EX);

// databases connections close
db2_close($connection_WHFED);
db2_close($connection_SCCD);
