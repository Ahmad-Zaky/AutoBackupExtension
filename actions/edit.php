<?php

chdir("../");
require_once('config.php');
 
// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["autobackup_loggedin"]) || $_SESSION["autobackup_loggedin"] !== true) {
    header("location: $backup_URL/index.php");
    exit;
}

// Validate CSRF Token
if (isset($_POST['autobackup_token']) && $_POST['autobackup_token'] != $_SESSION['autobackup_token']) {
    header("location: $backup_URL/models/logout.php");
    exit;
}

// Save Settings
if (!empty($_POST))
    echo json_encode($backupInstance->saveSettings($_POST));
else 
    echo json_encode([
        'success' => flase,
        'msg' => 'No Data has been sent !!!'
    ]);