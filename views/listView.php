<?php

// Include config file
chdir('../');
require_once "config.php";

// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["autobackup_loggedin"]) || $_SESSION["autobackup_loggedin"] !== true){
    header("location: $backup_URL/index.php");
    exit;
}

// Pagination
if (isset($_GET['page'])) {
    echo json_encode($backupInstance->listBackupLogs(
        $_GET['page'],
        $_GET['limit'],
        $_GET['count']
    ));
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>AutoBackup</title>    
    <?php include_once("layout/header.php"); ?>
    <link rel="stylesheet" href="<?php echo $backup_URL; ?>/layout/resources/css/viewstyle.css">
</head>
<body>
    <div class="container">
        <a name="settings" class="btn btn-info" role="button" href="<?php echo $backup_URL; ?>/views/settingsView.php">Setting</a> 
        <button name="backup" class="btn btn-primary" onclick="runBackup()" >Run Backup</button>
        <a name="reset" class="btn btn-secondary" role="button" href="<?php echo $backup_URL; ?>/views/resetView.php">Reset Password</a>
        
        <?php if($_SESSION['autobackup_is_admin']): ?>
            <a class="btn btn-secondary" role="button" href="<?php echo $backup_URL; ?>/views/historyView.php">Login History</a>
            <a class="btn btn-secondary" role="button" href="<?php echo $backup_URL; ?>/views/crontaskView.php">Scheduler</a>
        <?php endif; ?>
        
        <a class="btn btn-danger" role="button" href="<?php echo $backup_URL; ?>/actions/logout.php" >Log Out</a>
        <table>
            <thead>
                <th>ID</th>
                <th>Date</th>
                <th>File Name</th>
                <th>File Size</th>
                <th>Deleted</th>
            </thead>
            <tbody id="list-logs"></tbody>
        </table>
        <?php include_once("layout/pagination.php"); ?>
    </div>
    <?php include_once("layout/footer.php"); ?>
    <script>
        
        window.addEventListener('load', load('list-logs'));

        async function runBackup() {
            
            $('button[name="backup"]').attr('disabled', true)
            $('body').loading();
            
            var result = {};
            
            const data = {
                'module':'AutoBackup',
                'action':'run',
                'user_access_key': '<?php echo $_SESSION['autobackup_user_access_key'];?>'
            };

            const runPage = '<?php echo $backup_URL; ?>/actions/run.php';
            await $.ajax({
                url: runPage,
                type: "GET",
                data: data,
                success: function (resposne) {
                    load('list-logs');
                    $('body').loading('stop');
                    $('button[name="backup"]').attr('disabled', false);
                    result = JSON.parse(resposne);
                    console.log(result)
                },
                error: function (error) {
                    $('body').loading('stop');
                    $('button[name="backup"]').attr('disabled', false);
                    console.log(`Error: ${error}`);
                }
            });
            
            if (result.success !== undefined) {
                if (result.success) 
                    $.notify(result.msg, 'success')
                else
                    $.notify(result.msg, 'error')
            } else
                $.notify("Something Wrong !!!", 'error')

        }
    </script>
</body>
</html>