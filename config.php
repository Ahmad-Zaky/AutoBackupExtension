<?php


/**
 * Bind Auto Backup with vTiger
 */

require_once('vTigerSession.php');

/**
 * Bind Auto Backup with vTiger
 */

// Get Current Directory Name
$current_dir = getcwd();
$backup_dir = explode('/', $current_dir);
$backup_dir = $backup_dir[count($backup_dir)-1];

// Backup URL
$siteURL = "https://ahly.cityclubeg.net/";
$backup_URL = $siteURL . $backup_dir;


// Global Includes
require_once('models/helper.php');
require_once('models/AutoBackup.php');


// Database Configs
$dbconfigs['db_server'] = "localhost";              // MYSQL server address
$dbconfigs['db_username'] = "root";                 // your username MYSQL
$dbconfigs['db_password'] = "<password>";           // Password
$dbconfigs['db_name'] = "<autobackup_database>";    // database (autobackup) name
$dbconfigs['db_backup_name'] = "<backup database>"; // database name which will this script make backup from it

require_once('models/database.php');


// Mail Libs and Configs
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'models/lib/PHPMailer/src/PHPMailer.php';
require 'models/lib/PHPMailer/src/SMTP.php';
require 'models/lib/PHPMailer/src/Exception.php';

$emailConfigs = [
    'SMTPDebug' => SMTP::DEBUG_OFF,
    'Host' => 'smtp.gmail.com',
    'SMTPAuth' => true,  
    'Username' => '<outgoing_server_email>', 
    'Password' => '<outgoing_server_password>',  
    'SMTPSecure' => PHPMailer::ENCRYPTION_STARTTLS,
    'Port' => 587,
    'FromEmail' => '<from_email>',
    'FromName' => '<from_name>'
];


// Global Variables & Constants
$_timezone = "<set here the number of hours represented in seconds positive or negative depends>";
$backupInstance = new AutoBackup();


// Generate Token
session_start();
if(empty($_POST['autobackup_token'])) $_SESSION['autobackup_token'] = generateToken(32);
