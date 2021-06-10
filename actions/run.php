<?php	

chdir("../");
require_once('config.php');

// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["autobackup_loggedin"]) || $_SESSION["autobackup_loggedin"] !== true) {
    header("location: $backup_URL/index.php");
    exit;
}

$module = (isset($_REQUEST["module"])) ? $_REQUEST["module"] : "";
$action = (isset($_REQUEST["action"])) ? $_REQUEST["action"] : "";
$user_access_key = (isset($_REQUEST["user_access_key"])) ? $_REQUEST["user_access_key"] : "";


if ($_SESSION["autobackup_user_access_key"] ===  $user_access_key) {

    $result = $backupInstance->validate($module, $action);
    if ($result['success'])
        $result = $backupInstance->execute($module, $action);
    
        echo json_encode($result);
} else
    echo json_encode([
        'success' => false,
        'msg' => 'Access permission denied !!!'
    ]);
