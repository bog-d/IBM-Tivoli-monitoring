<?php
// function to check, update or save the chain
// $chain_name  - chain name
// $ke_sit_arr  - array of ke and situations ($ke_sit_arr[$ke][$sit])
// $m_ke        - ke of type 'm'
// $m_sit       - situation of type 'm'
// returns the state 'add' or 'update' or 'exist'
function check_and_save_chain($chain_name, $ke_sit_arr, $m_ke, $m_sit)
{
    $connection_TBSM = $GLOBALS['connection_TBSM'];
    $state = '';

    // check chain name
    $sel = "select ID 
            from PFR_CORRELATION_CHAIN  
            where PFR_CORRELATION_CHAIN_DESCRIPTION = '{$chain_name}'";
    $stmt = db2_prepare($connection_TBSM, $sel);
    $result = db2_execute($stmt);
    $row = db2_fetch_assoc($stmt);

    // new chain
    if (empty($row)) {
        $sel = "insert into PFR_CORRELATION_CHAIN (PFR_CORRELATION_CHAIN_DESCRIPTION)  
                values ('{$chain_name}')";
        $stmt = db2_prepare($connection_TBSM, $sel);
        $result = db2_execute($stmt);
        $chain_id = db2_last_insert_id($connection_TBSM);

        foreach ($ke_sit_arr as $ke => $sit_arr)
            foreach ($sit_arr as $sit) {
                $event_type = (($ke == $m_ke and $sit == $m_sit) ? 'm' : 's');
                $sel = "insert into PFR_CORRELATIONS (PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE, PFR_CORRELATION_CHAIN_ID)  
                        values ('{$ke}', '{$sit}', '{$event_type}', {$chain_id})";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
            }
        $state = 'add';
    }
    // existing chain
    else {
        $chain_id = $row['ID'];
        $valid = true;
        $checked_elem_arr = [];

        foreach ($ke_sit_arr as $ke => $sit_arr)
            foreach ($sit_arr as $sit) {
                $event_type = (($ke == $m_ke and $sit == $m_sit) ? 'm' : 's');

                // check chain element
                $sel = "select ID
                        from PFR_CORRELATIONS  
                        where PFR_CORRELATION_CHAIN_ID = {$chain_id} and PFR_KE_TORS = '{$ke}' and PFR_SIT_NAME = '{$sit}' and PFR_CORRELATION_EVENT_TYPE = '{$event_type}'";
                $stmt = db2_prepare($connection_TBSM, $sel);
                $result = db2_execute($stmt);
                $row = db2_fetch_assoc($stmt);

                // element to update
                if (empty($row)) {
                    $valid = false;

                    $sel = "insert into PFR_CORRELATIONS (PFR_KE_TORS, PFR_SIT_NAME, PFR_CORRELATION_EVENT_TYPE, PFR_CORRELATION_CHAIN_ID)  
                            values ('{$ke}', '{$sit}', '{$event_type}', {$chain_id})";
                    $stmt = db2_prepare($connection_TBSM, $sel);
                    $result = db2_execute($stmt);

                    $checked_elem_arr[] = db2_last_insert_id($connection_TBSM);
                }
                // element exists
                else
                    $checked_elem_arr[] = $row['ID'];
            }
        if ($valid)
            $state = 'exist';
        else {
            $state = 'update';

            // delete obsolete elements of chain
            $sel = "delete
                    from PFR_CORRELATIONS  
                    where PFR_CORRELATION_CHAIN_ID = {$chain_id} and ID not in (".implode(', ', $checked_elem_arr).")";
            $stmt = db2_prepare($connection_TBSM, $sel);
            $result = db2_execute($stmt);
        }
    }

    return $state;
}

