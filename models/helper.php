<?php

function redirect($baseURL, $delay = '0') {
    echo "<script>setTimeout(\"location.href = '$baseURL';\",$delay);</script>";
}

function loginHistory($username) {
    if (empty($username)) return false;

    global $adb;
    global $egypt_timezone;

    $ip = $_SERVER['REMOTE_ADDR'];
    $signed_in = date("Y-m-d H:i:s", time() + $egypt_timezone);
    $query = "INSERT INTO automatedbackup_users_logs (`username`, `ip`, `signed_in`, `signed_out`, `status`) VALUES ('$username', '$ip', '$signed_in', '--', 'Signed In')";
    $adb->query($query);
}

/**
 * Does not work properly as IP is not static
 */
function logoutHistory($username) {
    if (empty($username)) return false;
    
    global $adb;
    global $egypt_timezone;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $signed_out = date("Y-m-d H:i:s", time() + $egypt_timezone);
    
    $username = $adb->escape_string($username);
    $ip = $adb->escape_string($ip);
    $loginIdQuery = "SELECT MAX(id) AS id FROM automatedbackup_users_logs WHERE `username`='$username' AND ip='$ip' AND `status` LIKE 'Signed In' LIMIT 1";
    $result = $adb->query($loginIdQuery);
    
    if ($result) {
        $loginId = $adb->fetch_array($result)["id"];
    }

    if (!empty($loginId)){
        $query = "UPDATE automatedbackup_users_logs SET `signed_out` = '$signed_out', `status`='Signed Out' WHERE id = $loginId";
        $result = $adb->query($query);
    }
}

function backup_start() {
    global $adb, $backupInstance;

    // Update last start
    $now = $backupInstance->date();
    $query = "UPDATE automatedbackup_cron 
                SET `laststart` = '$now', `lastend` = ''
                WHERE `id` = 1";

    $adb->query($query);
}

function backup_mark_running() {
    global $adb, $backupInstance;

    // Update Status to Running
    $RUNNING = $backupInstance->cron_status['RUNNING'];
    $query = "UPDATE automatedbackup_cron 
                SET `status` = $RUNNING
                WHERE `id` = 1";

    $adb->query($query);
}

function backup_mark_finished() {
    global $adb, $backupInstance;

    // Update Status to Finished (Active)
    $ACTIVE = $backupInstance->cron_status['ACTIVE'];
    $query = "UPDATE automatedbackup_cron 
                SET `status` = $ACTIVE
                WHERE `id` LIKE 1";

    $adb->query($query);
}

function backup_end() {
    global $adb, $backupInstance;

    // Update last end
    $now = $backupInstance->date();
    $query = "UPDATE automatedbackup_cron 
                SET `lastend` = '$now'
                WHERE `id` LIKE 1";

    $adb->query($query);
}


function sendEmail($toEmail, $subject, $body) {
    /**
     * SMTP Server Should be enabled 
     */
    return [
        'success' => true,
        'msg' => ''
    ];;

    if(!filter_var($toEmail, FILTER_VALIDATE_EMAIL) || !class_exists('PHPMailer'))
        return !class_exists('PHPMailer');
        
    $mail = new PHPMailer(true);
    try {
            
        //Server settings
        global $emailConfigs;

        $mail->SMTPDebug  = $emailConfigs['SMTPDebug'];        // Enable verbose debug output
        $mail->isSMTP();                                       // Send using SMTP
        $mail->Host       = $emailConfigs['Host'];             // Set the SMTP server to send through
        $mail->SMTPAuth   = $emailConfigs['SMTPAuth'];         // Enable SMTP authentication
        $mail->Username   = $emailConfigs['Username'];         // SMTP username
        $mail->Password   = $emailConfigs['Password'];         // SMTP password
        $mail->SMTPSecure = $emailConfigs['SMTPSecure'];       // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
        $mail->Port       = $emailConfigs['Port'];;            // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

        //Recipients
        $mail->setFrom($emailConfigs['FromEmail'], $emailConfigs['FromName']);
        $mail->addAddress($toEmail);
        $mail->addReplyTo('info@example.com', 'Information');
        

        //Content
        $mail->isHTML(true);
        $mail->Subject = html_entity_decode($subject);
        $mail->Body    = html_entity_decode($body);

        $mail->send();
        return [
            'success' => true,
            'msg' => ''
        ];
    } catch (Exception $e) {
        return [
            "success" => fasle,
            "msg" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"
        ];
    }
}

function listHistory($page, $limit, $count) {

    if(
        (!is_numeric($page) || $page < 1) ||
        (!is_numeric($limit) || $limit < 1) ||
        (!is_numeric($count) || $count < 0)
    )
        return [
            'success' => false,
            'msg' => 'Provided Empty or invalid properties !!!'
        ];

    
    $allowed = [
        'username',
        'ip',
        'signed_in',
        'signed_out',
        'status'
    ];
    
        
    $offset = (($page - 1) * $limit);

    global $adb;
    
    if (!$count) {
        $query = "SELECT COUNT(id) as logs_count FROM automatedbackup_users_logs";
        $result = $adb->query($query);
        $count = $adb->fetch_array($result)['logs_count'];
    }

    $query = "SELECT * FROM automatedbackup_users_logs order by id desc LIMIT $offset, $limit";
    $result = $adb->query($query);
    
    if (!$result)
        return [
            'success' => false,
            'msg' => 'Query Failed !!!'
        ];

    $pageList = [];
    while($row = $adb->fetch_array($result)) {
        $filtered = [];
        foreach ($row as $key => $value) {
            if (in_array($key, $allowed))
                $filtered[$key] = $value;
        }
        $pageList[] = $filtered;
    }
    
    return [
        'success' => true,
        'pageList' => $pageList,
        'count' => $count,
        'msg' => '',
    ];
}

function listCronTask($page=0, $limit=0, $count=0) {
    global $adb, $backupInstance;

    $allowed = [
        'laststart',
        'lastend',
        'status'
    ];

    $statusMap = [
        'Inactive',
        'Active',
        'Running'
    ];

    $query = "SELECT * FROM `automatedbackup_cron` LIMIT 1";
    $result = $adb->query($query);

    if (!$result)
        return [
            'success' => false,
            'msg' => 'Query Failed !!!'
        ];

    $cron = $adb->fetch_array($result);
    
    $laststart = (!empty($cron['laststart']) && $cron['laststart'] !== '0000-00-00 00:00:00') ? strtotime($cron['laststart']) : '';
    $lastend = (!empty($cron['lastend']) && $cron['lastend'] !== '0000-00-00 00:00:00') ? strtotime($cron['lastend']) : '';

    return [
        'success' => true,
        'pageList' => [
            [
                'laststart' => getReadableTimeDiff($backupInstance->now(), $laststart),
                'lastend' => getReadableTimeDiff($backupInstance->now(), $lastend),
                'status' => $statusMap[$cron['status']]
            ]
        ],
        'count' => 1,
        'msg' => ''
    ];
}

function updateCronTaskStatus() {
    global $adb;
    
    $statusMap = [
        'Inactive',
        'Active'
    ];

    // Get Current Status
    $query = "SELECT `status` FROM `automatedbackup_cron` LIMIT 1";
    $result = $adb->query($query);
    
    if (!$result)
        return [
            'success' => false,
            'msg' => 'Select Query Failed !!!'
        ];

    // Update Status Value (Toggle between Active and Inactive)
    $status = !(int)$adb->fetch_array($result)['status'];
    $status = $status ? '1' : '0';
    $query = "UPDATE automatedbackup_cron 
            SET `status` = $status
            WHERE `id` = 1";

    $result = $adb->query($query);
    if (!$result)
        return [
            'success' => false,
            'msg' => 'Update Query Failed !!!'
        ];

    return [
        'success' => true,
        'status' => $statusMap[$status],
        'msg' => 'Autobackup Cron Turned to '.$statusMap[$status]
    ];
}

function getReadableTimeDiff($dateTime1, $dateTime2) {

    if(empty($dateTime1) || empty($dateTime2)) return '';

    if (is_numeric($dateTime1) &&  is_numeric($dateTime2))
        $timestampDiff = $dateTime1 - $dateTime2;
    else
        $timestampDiff = strtotime($dateTime1) - strtotime($dateTime2);
                
    $absTimestampDiff = abs($timestampDiff);
    
    // days
    $days = (int)($absTimestampDiff / (24*3600));
    if ($days)
        return (int)$days > 1 ? "$days days" : "$days day";
    
    // Hours
    $hours = (int)($absTimestampDiff / 3600);
    if ($hours)
        return (int)$hours > 1 ? "$hours hours" : "$hours hour";
    
    // Minutes
    $minutes = (int)($absTimestampDiff / 60);
    if ($minutes)
        return (int)$minutes > 1 ? "$minutes minutes" : "$minutes minute";

    // Seconds
    return ($absTimestampDiff) > 1 ? "$absTimestampDiff seconds" : "$absTimestampDiff second";
}

function formatSizeUnits($bytes) {

    if ($bytes >= 1073741824) 
        return number_format($bytes / 1073741824, 2) . ' GB';
    
    if ($bytes >= 1048576) 
        return number_format($bytes / 1048576, 2) . ' MB';
    
    if ($bytes >= 1024)
        return number_format($bytes / 1024, 2) . ' KB';
    
    if ($bytes > 1)
        return $bytes . ' bytes';
    
    if ($bytes == 1)
        return $bytes . ' byte';

    return '0 bytes';
}

function generateToken($length = 32) {
    return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $length);
}

function autobackup_detect_run_in_cli() {
	return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' ||  is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}