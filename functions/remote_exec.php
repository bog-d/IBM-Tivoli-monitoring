<?php

//  script execution on remote server
// return true if successful, false in error case
function remote_exec($host, $port, $user, $pass, $command, $log, $append) {

    $connection = ssh2_connect($host, $port);
    if ($connection == false)
        return false;
    if (!ssh2_auth_password($connection, $user, $pass))
        return false;
    $stream = ssh2_exec($connection, $command);
    if ($stream == false)
        return false;
    if (!stream_set_blocking($stream, true))
        return false;
    $stream_out = stream_get_contents(ssh2_fetch_stream($stream, SSH2_STREAM_STDIO));
    if ($stream_out === false)
        return false;
    ssh2_exec($connection, 'exit');
    unset($connection);

    if (!empty($log))
        if ($append)
            file_put_contents($log, $stream_out, FILE_APPEND | LOCK_EX);
        else
            file_put_contents($log, $stream_out, LOCK_EX);
    return true;
}

// **************************************************************************************************************************************************

// TEMS data get and database tables fill
// no return
function TEMS_data_reload($reg_array, $PFR_TEMS_SIT_AGGR_fill_update) {

    $log_file_scr = 'logs/SCCD_scripts.log';    // external scripts run log

    // multiple choice available
    foreach ($reg_array as $reg) {
        // without situations export
        if ($reg == 'no')
            continue;
        // situations export on teps-main
        if ($reg == '101')
            remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_export_for_reg.sh", $log_file_scr, true);
        // situations export on RPM
        if ($reg != '101')
            remote_exec('itm' . $reg, 22, 'root', ($reg == '102' or $reg == '013' or $reg == '032' or $reg == '205') ? 'passw0rd' : 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_export_for_reg.sh", $log_file_scr, true);
        // situations move to teps-main
        if ($reg != '101')
            remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_regions_harvest.sh " . $reg, $log_file_scr, true);
        // situations import to DB
        remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_db2_import.sh " . $reg, $log_file_scr, true);
    }

    // summary table fill
    if ($PFR_TEMS_SIT_AGGR_fill_update)
        remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/PFR_TEMS_SIT_AGGR_fill.sh", $log_file_scr, true);
}

// **************************************************************************************************************************************************

// TEMS data get and database tables fill
// no return

// новая функция передалана под надобности настроек интеграции, старая используется BigFix (надо тоже подогнать под новую) !!!!!!!!!!!!!!!!!!!!!!!!!!!

function TEMS_data_reload_new($reg_array, $PFR_TEMS_SIT_AGGR_fill_update, $export_situations_from_TEMS) {

    $log_file_scr = 'logs/SCCD_scripts.log';    // external scripts run log

    // multiple choice available
    foreach ($reg_array as $reg) {
        if ($export_situations_from_TEMS) {
            // situations export on teps-main
            if ($reg == '101')
                remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_export_for_reg.sh", $log_file_scr, true);
            // situations export on RPM
            if ($reg != '101')
                remote_exec('itm' . $reg, 22, 'root', ($reg == '102' or $reg == '013' or $reg == '032' or $reg == '205') ? 'passw0rd' : 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_export_for_reg.sh", $log_file_scr, true);
            // situations move to teps-main
            if ($reg != '101')
                remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_regions_harvest.sh " . $reg, $log_file_scr, true);
            // situations import to DB
            remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/axibase_db2_import.sh " . $reg, $log_file_scr, true);
        }
        // summary table fill
        if ($PFR_TEMS_SIT_AGGR_fill_update)
            remote_exec('teps-main', 22, 'root', 'passw0rdt1vol1', "/StorageDisk/SITUATIONS/PFR_TEMS_SIT_AGGR_fill.sh " . $reg, $log_file_scr, true);
    }
}

// **************************************************************************************************************************************************

?>
