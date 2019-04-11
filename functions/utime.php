<?php

// date and time conversion from '2016-11-03 01:00:00.000000' to UNIX format
function utime($date_time_string) {
    $dt_elements = explode(' ', $date_time_string);
    $date_elements = explode('-', $dt_elements[0]);
    $time_elements = explode(':', $dt_elements[1]);
    return mktime($time_elements[0], $time_elements[1], 0, $date_elements[1], $date_elements[2], $date_elements[0]);
}

// --------------------------------------------------------------------------------------------------------------------------------------------------

// time conversion from value in seconds to "ХХ ч ХХ мин ХХ с"
function readable_time($total_time_sec, $show_seconds) {
    $total_time_hour = floor($total_time_sec / 3600);
    $total_time_min = floor($total_time_sec / 60) - $total_time_hour * 60;
    $total_time_sec = $total_time_sec - $total_time_hour * 3600 - $total_time_min * 60;

    // return $total_time_hour . " ч " . $total_time_min . " мин " . $total_time_sec . " c";
    return ($total_time_hour < 10 ? '0' : '').$total_time_hour.
            ':'.($total_time_min < 10 ? '0' : '').$total_time_min.
            ($show_seconds ? (':'.($total_time_sec < 10 ? '0' : '').$total_time_sec) : '');
}

// --------------------------------------------------------------------------------------------------------------------------------------------------

/* recursive function for processing overlapping ranges of work
Parameters:
    $operation
        'intersection' - returns intersection of overlapping work intervals
        'combination'  - returns merging of overlapping work intervals
    $inp_array
        work time-interval array ('start' => unix timestamp of the interval begin,
                                  'end'   => unix timestamp of the interval finish,
                                  'inter' => boolean value to indicate intersection/merge operation was applied)
Return:
    $out_array - the structure is identical for $inp_array
*/
function search_concurrences($operation, $inp_array) {
    $out_array = [];
    $glue = false;
    $goto_recursive = false;

    if ($operation != 'combination' and $operation != 'intersection')
        return $inp_array;

    foreach ($inp_array as $i) {
        if (empty($out_array))
            $out_array[] = array('start' => $i['start'], 'end' => $i['end'], 'inter' => $i['inter']);
        else {
            $glue = false;
            foreach ($out_array as $key => $j)
                if ($i['start'] <= $j['end'] and $i['end'] >= $j['start']) {
                    $out_array[$key]['start'] = $operation == 'combination' ? min($i['start'], $j['start']) : max($i['start'], $j['start']);
                    $out_array[$key]['end'] = $operation == 'combination' ? max($i['end'], $j['end']) : min($i['end'], $j['end']);
                    $out_array[$key]['inter'] = true;
                    $glue = true;
                    break;
                }
            if ($glue)
                $goto_recursive = true;
            else
                $out_array[] = array('start' => $i['start'], 'end' => $i['end'], 'inter' => false);
        }
    }

    if ($goto_recursive)
        return search_concurrences($operation, $out_array);
    else
        return $out_array;
}

?>

