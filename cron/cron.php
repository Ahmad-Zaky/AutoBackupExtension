<?php

chdir('../');
require_once('config.php');

$autobackup_unique_key = $backupInstance->settings['autobackup_unique_key'];

if(
    autobackup_detect_run_in_cli() || 
    (
        isset($_REQUEST['autobackup_unique_key']) && 
        $_REQUEST['autobackup_unique_key'] == $autobackup_unique_key
    )
) {
    try {
        $cronTask = listCronTask();
        if ($cronTask['success'] && $cronTask['pageList'][0]['status'] !== 'Inactive') {
            
            if ($cronTask['success'] && $cronTask['pageList'][0]['status'] === 'Active') {
                backup_start();
                echo "Auto Backup CRON Task Started at " . $backupInstance->date() . "\n";

                backup_mark_running();
                require_once 'cron/backup.service';
                backup_mark_finished();
                    
                backup_end();
                echo "Auto Backup CRON Task Finished at " . $backupInstance->date() . "\n";
            } else
                echo "Auto Backup CRON Task had Timedout as it is not completed last time it run !!!\n";

        } else
            echo "Auto Backup CRON Task Status in Inactive !!!\n";

    } catch (Exception $e) {
        echo "[ERROR]: AutoBackup - cron task execution throwed exception.\n";
        echo $e->getMessage();
        echo "\n";
    }
}