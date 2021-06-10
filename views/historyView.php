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
    echo json_encode(listHistory(
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
    <title>History</title>
    <?php include_once("layout/header.php"); ?>
    <link rel="stylesheet" href="<?php echo $backup_URL; ?>/layout/resources/css/viewstyle.css">
</head>
<body>
    <div class="container">
        <a class="btn btn-info" role="button" href="<?php echo $backup_URL; ?>/views/listView.php">Back</a>
        <table>
            <thead>
                <th>ID</th>
                <th>Username</th>
                <th>IP</th>
                <th>Signed In</th>
                <th>Signed Out</th>
                <th>Status</th>
            </thead>
            <tbody id="list-history"></tbody>
        </table>
        <?php include_once("layout/pagination.php"); ?>
    </div>
    <?php include_once("layout/footer.php"); ?>
    <script> window.addEventListener('load', load('list-history')); </script>
</body>
</html>