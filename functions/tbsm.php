<?php

// ******************************************************************************************************************************

// all child service search recursive function
// fills informational array about each service
// info array structure:
//                      'level' => nesting level of the service
//                      'endpoint' => boolean: is it endpoint service (haven't any childs) or no
//                      'id' => SERVICEINSTANCEID field of SERVICEINSTANCE table
//                      'service' => SERVICEINSTANCENAME field of SERVICEINSTANCE table
//                      'display' => DISPLAYNAME field of SERVICEINSTANCE table
//                      'template' => SERVICESLANAME field of SERVICEINSTANCE table
//                      'maintenance' => TIMEWINDOWNAME field of SERVICEINSTANCE table
// returns TRUE - if there are child services, FALSE - if there are no child services (i.e. this is endpoint service)
//
function ext_tree($parent_id, $con, $lev, $info) {

    // rows selection from TBSM tables
    $sel = "SELECT SERVICEINSTANCEID, SERVICEINSTANCENAME, DISPLAYNAME, SERVICESLANAME, TIMEWINDOWNAME
				FROM TBSMBASE.SERVICEINSTANCE, TBSMBASE.SERVICEINSTANCERELATIONSHIP
				WHERE PARENTINSTANCEKEY = '$parent_id' AND SERVICEINSTANCEID = CHILDINSTANCEKEY";
    $stmt = db2_prepare($con, $sel);
    $result = db2_execute($stmt);

    $values = 0;
    while ($row = db2_fetch_assoc($stmt)) {
        $values++;
        $info[$lev] = array (
                            'level' => $lev,
                            'endpoint' => false,
                            'id' => $row['SERVICEINSTANCEID'],
                            'service' => $row['SERVICEINSTANCENAME'],
                            'display' => $row['DISPLAYNAME'],
                            'template' => $row['SERVICESLANAME'],
                            'maintenance' => $row['TIMEWINDOWNAME'],
                            );
        // unique value
        if (!in_array($row['SERVICEINSTANCEID'], array_column($GLOBALS['results'], 'id')))
            $GLOBALS['results'][] = $info[count($info)-1];

        // recursive function call
        ext_tree($row['SERVICEINSTANCEID'], $con, $lev+1, $info);
    }

    return ($values > 0);
}

// ******************************************************************************************************************************

$arr_event_lifetime = array (
    "5 мин" => 300,		"5 мин + 1 мин" => 360,
    "10 мин" => 600,	"10 мин + 1 мин" => 660,
    "30 мин" => 1800,	"30 мин + 1 мин" => 1860,
    "1 ч" => 3600,		"1 ч + 1 мин" => 3660,
    "2 ч" => 7200,		"2 ч + 1 мин" => 7260,
    "4 ч" => 14400,		"4 ч + 1 мин" => 14460,
    "6 ч" => 21600,		"6 ч + 1 мин" => 21660,
    "12 ч" => 43200,	"12 ч + 1 мин" => 43260,
    "24 ч" => 86400,	"24 ч + 1 мин" => 86460,
    "36 ч" => 129600,	"36 ч + 1 мин" => 129660,
);

// ******************************************************************************************************************************

// situation severity codes for event
$severity_codes = array (
    "Critical" => "5",
    "Marginal" => "4",
    "Minor" => "3",
    "Warning" => "2",
    "Informational" => "1",
    "Harmless" => "0",
    "Fatal" => "5",
    "Определено в Systems Director" => "5",
    "Определено в TSPC" => "5",
    "" => "-",
);

// class of service SCCD
$class_codes = array (
   "-30" => "Выкл. (Тест.)",
   "-10" => "Выкл. (Прод.)",
    "-1" => "Не задано",
     "0" => "Не задано",
     "1" => "Продуктивный",
     "2" => "Продуктивный",
     "3" => "Тест",
     "4" => "Тест",
);

// ******************************************************************************************************************************

$log_file = 'logs/TBSM_user_manage.log';

$path_user_manage = "/StorageDisk/RAD/TBSM_user_manage/";
$script_create_user = "create_user.sh";
$script_user_role_add = "user_role_add.sh";
$file_roles = "roles.txt";
$script_get_users_info = 'get_users_info.sh';
//$file_users = 'users.txt';
//$file_groups = 'groups.txt';
//$file_group_members = 'group_members.txt';
$file_user_groups_roles = 'user_groups_roles.txt';
$file_roles = 'roles.txt';

$path_LDAP_sync = "/StorageDisk/RAD/TBSM_LDAP_sync/";
$script_get_ad_users = "get_ad_users.sh";
$file_users_LDAP_ADMINS = "users_LDAP_ADMINS.txt";
$file_users_LDAP_MGRS = "users_LDAP_MGRS.txt";
$file_users_LDAP_TIVOLIUSERS = "users_LDAP_TIVOLIUSERS.txt";
$script_get_nco_users = "get_nco_users.sh";
$file_TBSM_users = "TBSM_users.txt1";
$script_add_user_to_tbsm = "add_user_to_tbsm.sh";
$script_user_roles_get = "user_roles_get.sh";
$script_user_status_change = "user_status_change.sh";
$script_user_role_get_from_nco = "custom_pfr_user_scope.sh";

$ldap_group_name = array (
    ''                  => "&emsp;&emsp;&emsp;---",
    'LDAP_TIVOLIADMINS' => 'Администратор',
    'LDAP_TIVOLIMGRS'   => 'Руководитель',
    'LDAP_TIVOLIUSERS'  => 'Пользователь',
);

$visibility_group = array (
    "PFR_R_VIEWER_009" => "ОПФР 009",
    "PFR_R_VIEWER_013" => "ОПФР 013",
    "PFR_R_VIEWER_017" => "ОПФР 017",
    "PFR_R_VIEWER_071" => "ОПФР 071",
    "PFR_R_VIEWER_081" => "ОПФР 081",
    "PFR_R_VIEWER_ALL" => "ВСЕ",
    ''                 => "&emsp;&emsp;---",
);

$duty_accounts = array ( 'tbsmadmin', 'tbsmuser', 'root', 'ncoadmin', 'ncouser', 'nobody', );

// ******************************************************************************************************************************

function get_from_ad($group, $file) {
    $users_arr = [];
    $login = '';
    $displayname = '';
    $email = '';
    $region = '';

    $ad_users = file($file);
    foreach ($ad_users as $rec) {
        list($key, $value) = explode(' ', $rec);
        switch ($key) {
            case 'dn::':
                if ($login != '') {
                    $users_arr[$login] = array(
                        'group' => $group,
                        'displayname' => $displayname,
                        'email' => $email,
                        'region' => $region,
                        'nco' => '',
                        );
                    $login = '';
                    $displayname = '';
                    $email = '';
                    $region = '';
                }
                break;
            case 'displayName::':
                $displayname = base64_decode($value);
                break;
            case 'userPrincipalName:':
                list($login, $region) = explode('@', $value);
                $region = substr($region, 1, 3);
                break;
            case 'mail:':
                $email = $value;
                break;
            default:
                break;
        }
    }
    if ($login != '') {
        $users_arr[$login] = array(
            'group' => $group,
            'displayname' => $displayname,
            'email' => $email,
            'region' => $region,
            'nco' => '',
        );
    }

    return $users_arr;
}

// ******************************************************************************************************************************

$AEL_col_list_arr = array(
    '' => 'Serial',
    'Критичность' => 'Severity',
    'Последнее вхождение' => 'LastOccurrence',
    'Первое вхождение' => 'FirstOccurrence',
    'Счётчик событий' => 'Tally',
    'Назначение' => 'pfr_nazn',
    'КЭ' => 'pfr_ke_tors',
    'Подкатегория' => 'pfr_subcategory',
    'Код проблемы' => 'pfr_sit_name',
    'Описание' => 'pfr_description',
    'Инцидент' => 'TTNumber',
    'Рабочее задание' => 'pfr_tsrm_worder',
    'Интервал РЗ' => 'pfr_tsrm_worder_delay',
    'Классификатор' => 'pfr_tsrm_class',
);

// function to get active events from AEL
function ael_request($ke) {
    $event_arr = [];

    exec("curl -X GET --insecure --connect-timeout 10 --user root:passw0rd \"http://10.103.0.60:9595/objectserver/restapi/alerts/status?filter=pfr_ke_tors='{$ke}'&collist=".implode(',', $GLOBALS['AEL_col_list_arr'])."\"", $arr_data);
    $json_arr = json_decode(implode(' ', $arr_data), true);

    foreach ($json_arr['rowset']['rows'] as $event)
        $event_arr[$event['Serial']] = $event;

    return $event_arr;
}
