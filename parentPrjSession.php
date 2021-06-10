<?php


/**
 * Parent Pojrect Session Related Content
 * 
 * Subject: Bind autobackup with Parent Project Session user session to allow only specific users who have access to Parent Project Session
 */

// Redirect if User is Not Logged in and the user is not in the whitelist
function vRedirect($baseURL, $delay = '0') {
    header("Location: $baseURL");
    exit;
}

/**
 * Warning: Do not remove the pre _ char 
 * because there is another function with the same name.
 */
function _autobackup_detect_run_in_cli() {
	return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' ||  is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0));
}

// Skip this Validation on Cron Task
if (!_autobackup_detect_run_in_cli()) {
    // Get Current Directory Name
    $current_dir = getcwd();
    $backup_dir = explode('/', $current_dir);
    $backup_dir = $backup_dir[count($backup_dir)-1];
    
    // Go to crm root
    chdir('../');
    
    // include the parent project file which init the session and the logged in user infos
    include_once 'path/to/parent/project/session/or/config/file';
    
    // Get CRM Logged In User    
    $crm_user = '<write your code here>';
    
    /**
     * in case you want to make a white list for your users
     * you may check with the username if its unique or may be any other unique identifier like (access_key)
     */
    $crm_users_whitelist = [
        'user1',
        'user2',
        'user3',
    ];
    
    if (
        !$crm_user || 
        (is_object($crm_user) && !in_array($crm_user->user_name, $crm_users_whitelist))
    ) vRedirect($site_URL);

    chdir($backup_dir);
}
/**
 * END vTiger Content
 */