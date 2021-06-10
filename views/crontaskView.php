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
	echo json_encode(listCronTask());
	exit;
}

// Update Cron Status
if (
    isset($_GET['action']) && $_GET['action'] === 'update' &&
    isset($_GET['task']) && $_GET['task'] === 'update-cron-status'
) {
    echo json_encode(updateCronTaskStatus());
    exit;
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Cron Task</title>
    <?php include_once("layout/header.php"); ?>
    <link rel="stylesheet" href="<?php echo $backup_URL; ?>/layout/resources/css/viewstyle.css">
</head>
<body>
    <div class="container">
        <a class="btn btn-info" role="button" href="<?php echo $backup_URL; ?>/views/listView.php">Back</a>
        <table>
            <thead>
                <th>ID</th>
                <th>Last Start</th>
                <th>Last End</th>
                <th>Status</th>
            </thead>
            <tbody id="list-crontask"></tbody>
        </table>
        <?php include_once("layout/pagination.php"); ?>
    </div>
    <?php include_once("layout/footer.php"); ?>
    <script> 
    loadCron();
    async function loadCron() {
        await load('list-crontask');

        statusElem = $('td[data-name="status"');
        statusElem.css('cursor', 'pointer');
        statusElem.prop('title', 'Double Click to Update Status');
        
        $('td[data-name="status"').on('dblclick', function() {
            if (statusElem.text() !== 'Running')
                updateCronStatus(statusElem);
            else
                $.notify("Please wait untill Cron Task Finish !", 'warning');
        });

    }
    async function updateCronStatus(statusElem) {
        if (!statusElem.length)
            return false;
            
        result = ''
        await $.ajax({
            type: "GET",
            data: {task: 'update-cron-status', action: 'update'},
            success: function (response) {
                result = JSON.parse(response)
                console.log(result);
            }
        });

        if ((!$.isEmptyObject(result) && result.success === true)) {
            statusElem.text(result.status)
            $.notify(result.msg, 'success');
        } else
            $.notify(result.msg, 'error');
        
    }
    </script>
</body>
</html>