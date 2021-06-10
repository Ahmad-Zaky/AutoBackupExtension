<?php

// Include config file
require_once "config.php";

// Validate CSRF Token
if (isset($_POST['autobackup_token']) && $_POST['autobackup_token'] == $_SESSION['autobackup_token']) {
    // Check if the user is already logged in, if yes then redirect him to welcome page
    if(isset($_SESSION["autobackup_loggedin"]) && $_SESSION["autobackup_loggedin"] === true){
        header("location: $backup_URL/views/listView.php");
        exit;
    }
}

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
 
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
 
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = htmlentities(trim($_POST["username"]), ENT_QUOTES, 'UTF-8');
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = htmlentities(trim($_POST["password"]), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        
        // Prepare a select statement
        $username = $adb->escape_string($username);
        $sql = "SELECT `id`, `username`, `password`, `is_admin`, `user_access_key` FROM automatedbackup_users WHERE username = '$username'";
        
        // Attempt to execute the statement
        $result = $adb->query($sql);
        if ($result) {

            // Check if username exists, if yes then verify password
            if ($adb->num_rows($result) == 1) {
                
                $user = $adb->fetch_array($result);

                $id = $user['id'];
                $username = $user['username'];
                $hashed_password = $user['password'];
                $is_admin = $user['is_admin'];
                $user_access_key = $user['user_access_key'];
                
                if (password_verify($password, $hashed_password)) {

                    // Password is correct, so start a new session
                    session_start();
                    
                    // Store data in session variables
                    $_SESSION["autobackup_loggedin"] = true;
                    $_SESSION["autobackup_id"] = $id;
                    $_SESSION["autobackup_username"] = $username;                            
                    $_SESSION["autobackup_is_admin"] = $is_admin;                            
                    $_SESSION["autobackup_user_access_key"] = $user_access_key;                            
                    
                    // Log Sign In History
                    loginHistory($username);

                    // Send Email to notify the admin with the logged in user
                    $email = $backupInstance->settings['loginEmail'];
                    $subject = sprintf($backupInstance->settings['loginSubject'], $backupInstance->date());
                    $body =  sprintf($backupInstance->settings['loginBody'], $username, $backupInstance->date());
                    
                    
                    $status = sendEmail($email, $subject, $body);
                    
                    // Redirect user to listView page
                    header("location: $backup_URL/views/listView.php");
                } else {
                    // Password is not valid, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else {
                // Username doesn't exist, display a generic error message
                $login_err = "Invalid username or password.";
            }

        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <?php include_once("layout/header.php"); ?>
    <link rel="stylesheet" href="<?php echo $backup_URL; ?>/layout/resources/css/style.css">
    
    <style>
        body { font: 14px sans-serif; }

        .contact-form{ width: 360px; padding: 20px; }

        .center-div { margin: auto; width: 50%; }

        .center-h2 { color: white; text-align: center; }
        
        .form-control { width: 150%; }
    </style>
</head>
<body>
    <div class="wrapper">
        
        <div class="center-div">
            <h2 class="center-h2" >Login</h2>
        </div>

        <?php 
        if(!empty($login_err)){
            echo '<div class="center-div alert alert-danger" style="width: 360px">' . $login_err . '</div>';
        }        
        ?>
        
        <div class="contact-form">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <input type="hidden" name="autobackup_token" value="<?php echo $_SESSION['autobackup_token']; ?>" />
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>    
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <input id="submit" type="submit" class="btn btn-primary" value="Login">
                </div>
            </form>
        </div>
    </div>
    <?php include_once("layout/footer.php"); ?>
    <script>
        $('form').on('submit', () => $('#submit').attr('disabled', true)); 
    </script>
</body>
</html>