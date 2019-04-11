<?php
/*var_dump($_POST);*/
// AJAX request and response
if (isset($_POST)) {
    require_once '../connections/TBSM.php';
    require_once '../connections/MAXDB76.php';
    require_once '../functions/logs.php';

    $return_arr = [];
    $fields_name = array(
        'node'  => 'NODE',
        'obj'   => 'PFR_OBJECT',
        'ke'    => 'PFR_KE_TORS',
        'filt'  => 'SITFILTER',
        'nazn'  => 'PFR_NAZN',
        'url'   => 'URL',
    );
    $log_error = false;

    foreach ($_POST["txtfld_loc"] as $key => $rec) {
        $sel = "select * from DB2INST1.PFR_LOCATIONS where ID = $key";
        $stmt = db2_prepare($connection_TBSM, $sel);
        if (db2_execute($stmt)) {
            $row = db2_fetch_assoc($stmt);
            foreach ($rec as $field => $value)
                if (strcmp($row[$fields_name[$field]], $value) != 0) {
                    $sel_upd = "update DB2INST1.PFR_LOCATIONS set {$fields_name[$field]} = '{$value}' where ID = {$key}";
                    $stmt_upd = db2_prepare($connection_TBSM, $sel_upd);
                    if (db2_execute($stmt_upd)) {
                        $message = log_write($row['SERVICE_NAME'], "Запись в таблице PFR_LOCATIONS обновлена (id={$key}, field={$fields_name[$field]}) с \"{$row[$fields_name[$field]]}\" на \"{$value}\".");
                        $return_arr[] = array('id' => $key, 'fld' => $field, 'val' => $value, 'updated' => 1, 'err_mess' => $log_error ? '' : $message);
                        $log_error = empty($message) ? $log_error : true;
                    }
                    else
                        $return_arr[] = array('id' => $key, 'fld' => $field, 'val' => $value, 'updated' => 0, 'err_mess' => 'Ошибка обновления записи в таблице PFR_LOCATIONS (id={$key}, field={$fields_name[$field]}). ');
                }
        }
        else
            $return_arr[] = array('id' => $key, 'fld' => $field, 'val' => $value, 'updated' => 0, 'err_mess' => 'Ошибка поиска записи в таблице PFR_LOCATIONS (id={$key}). Возможно, запись уже удалена. ');
    }
    db2_close($connection_TBSM);

    echo json_encode($return_arr);
    exit();
}

