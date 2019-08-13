<?php
/*var_dump($_POST);*/
// AJAX request and response
if (isset($_POST)) {
    require_once '../connections/TBSM.php';

    $check = true;
    $message = '';
    $error = false;
    $return_arr = [];

    $new_arr = [];
    $id_arr = [];
    $val_arr = [];

    $chain_id = $_POST['edit_chain_id'];
    $chain_name = $_POST['edit_chain_name'];

    // consistency check
    if (isset($_POST['list_ke']))
        foreach ($_POST['list_ke'] as $key => $value)
            $new_arr[] = $value . '`' . $_POST['list_si'][$key];
    if (isset($_POST['list_ke_new']))
        foreach ($_POST['list_ke_new'] as $key => $value)
            if (!isset($_POST['chk_del_new']) or !array_key_exists($key, $_POST['chk_del_new']))
                $new_arr[] = $value . '`' . $_POST['list_si_new'][$key];
    sort($new_arr);

    $sel = "select ID, PFR_CORRELATION_CHAIN_DESCRIPTION from PFR_CORRELATION_CHAIN";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    while($row = db2_fetch_assoc($stmt))
        if ($row['ID'] != $chain_id) {
            // existing name
            if (strcmp($chain_name, $row['PFR_CORRELATION_CHAIN_DESCRIPTION']) == 0) {
                $message = 'Цепочка с таким именем уже имеется!';
                $check = false;
                break;
            }
            $id_arr[] = $row['ID'];
        }

    if ($check) {
        foreach ($id_arr as $id) {
            $val_arr = [];
            $sel = "select PFR_KE_TORS, PFR_SIT_NAME from PFR_CORRELATIONS where PFR_CORRELATION_CHAIN_ID = {$id}";
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
            while ($row = db2_fetch_assoc($stmt))
                $val_arr[] = $row['PFR_KE_TORS'] . '`' . $row['PFR_SIT_NAME'];
            sort($val_arr);

            if (empty(array_diff($new_arr, $val_arr))) {
                $check = false;
                $sel = "select PFR_CORRELATION_CHAIN_DESCRIPTION from PFR_CORRELATION_CHAIN where ID = {$id}";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                $row = db2_fetch_assoc($stmt);
                $chain_name = $row['PFR_CORRELATION_CHAIN_DESCRIPTION'];
                $message = "Все данные этой цепочки содержатся в цепочке \"{$chain_name}\"";
                break;
            }
            if (empty(array_diff($val_arr, $new_arr))) {
                $check = false;
                $sel = "select PFR_CORRELATION_CHAIN_DESCRIPTION from PFR_CORRELATION_CHAIN where ID = {$id}";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                $row = db2_fetch_assoc($stmt);
                $chain_name = $row['PFR_CORRELATION_CHAIN_DESCRIPTION'];
                $message = "Цепочка \"{$chain_name}\" является подмножеством данной цепочки.";
                break;
            }
        }
    }

    // save changes  to DB
    if ($check) {
        // chain name
        if ($chain_id == 0)
            $chain_name = $_POST['new_chain_name'];
//        else {
//            $sel = "select PFR_CORRELATION_CHAIN_DESCRIPTION from PFR_CORRELATION_CHAIN where ID = {$chain_id}";
//            $stmt = db2_prepare($connection_TBSM, $sel);
//            $result = db2_execute($stmt);
//            if (!$error and !$result)
//                $error = true;
//            $row = db2_fetch_assoc($stmt);
//            $chain_name = $row['PFR_CORRELATION_CHAIN_DESCRIPTION'];
//        }

        // save existing records
        if (isset($_POST['list_ke'])) {
            foreach ($_POST['list_ke'] as $key => $value) {
                $event_type = $_POST['type_list'] == $key ? 'm' : 's';
                $sel = "update PFR_CORRELATIONS 
                set PFR_KE_TORS = '{$value}', PFR_SIT_NAME = '{$_POST['list_si'][$key]}', PFR_CORRELATION_EVENT_TYPE = '$event_type' 
                where ID = {$key}";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                if (!$error and !$result)
                    $error = true;
            }
        }

        // delete existing records
        if (isset($_POST['chk_del'])) {
            foreach ($_POST['chk_del'] as $key => $value) {
                $sel = "delete from PFR_CORRELATIONS where ID = {$key}";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                if (!$error and !$result)
                    $error = true;
            }
        }

        // new chain
        if ($chain_id == 0) {
            $sel = "insert into PFR_CORRELATION_CHAIN (PFR_CORRELATION_CHAIN_DESCRIPTION)  
                    values ('{$chain_name}')";
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
            if (!$error and !$result)
                $error = true;
            $chain_id = db2_last_insert_id($connection_TBSM);
        }
        // existing chain
        else {
            $sel = "update PFR_CORRELATION_CHAIN set PFR_CORRELATION_CHAIN_DESCRIPTION = '{$chain_name}' where ID = {$chain_id}";
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
            if (!$error and !$result)
                $error = true;
        }

        // new records
        if (isset($_POST['list_ke_new'])) {
            foreach ($_POST['list_ke_new'] as $key => $value) {
                // save changes if not to delete
                if (!isset($_POST['chk_del_new']) or !array_key_exists($key, $_POST['chk_del_new'])) {
                    $event_type = $_POST['type_list'] == "new_{$key}" ? 'm' : 's';
                    $sel = "insert into PFR_CORRELATIONS (PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE, PFR_CORRELATION_CHAIN_ID)  
                    values ('{$value}', '{$_POST['list_si_new'][$key]}', '$event_type', {$chain_id})";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);
                    if (!$error and !$result)
                        $error = true;
                }
            }
        }

        // delete chains without records
        $sel = "delete from PFR_CORRELATION_CHAIN d
                where not exists (
                    select * from PFR_CORRELATIONS c
                    where d.ID = c.PFR_CORRELATION_CHAIN_ID
                )";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
        if (!$error and !$result)
            $error = true;
    }

    db2_close($connection_TBSM);

    $return_arr['check_status'] = $check;
    $return_arr['message'] = $message;
    $return_arr['save_status'] = !$error;
    echo json_encode($return_arr);
    exit();
}
