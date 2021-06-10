<?php

chdir('../');
require_once('config.php');

$username = $_SESSION["autobackup_username"];

// Log Sign out History
logoutHistory($username);

// Unset all of the session variables
$_SESSION["autobackup_loggedin"] = false;
$_SESSION["autobackup_id"] = '';
$_SESSION["autobackup_username"] = '';
$_SESSION["autobackup_is_admin"] = false;


// Redirect to login page
header("location: $backup_URL/index.php");
exit;

?>