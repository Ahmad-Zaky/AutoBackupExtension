<?php

// Include config file
chdir('../');
require_once "config.php";

// Check if the user is logged in, otherwise redirect to login page
if(!isset($_SESSION["autobackup_loggedin"]) || $_SESSION["autobackup_loggedin"] !== true){
    header("location: $backup_URL/index.php");
    exit;
}

// Validate CSRF Token
if (isset($_POST['autobackup_token']) && $_POST['autobackup_token'] != $_SESSION['autobackup_token']) {
    header("location: $backup_URL/models/logout.php");
    exit;
}

// Define variables and initialize with empty values
$old_password = $new_password = $confirm_password = "";
$old_password_err = $new_password_err = $confirm_password_err = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate old password
    if (empty(trim($_POST["old_password"]))) {
        $old_password_err = "Please enter the old password.";     
    } else {
        $old_password = htmlentities(trim($_POST["old_password"]), ENT_QUOTES, 'UTF-8');
    }

    // Validate new password
    if (empty(trim($_POST["new_password"]))) {
        $new_password_err = "Please enter the new password.";     
    } else {
        $new_password = htmlentities(trim($_POST["new_password"]), ENT_QUOTES, 'UTF-8');
        $reset_err = validatePasswordStrong($new_password);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm the password.";
    } else {
        $confirm_password = htmlentities(trim($_POST["confirm_password"]), ENT_QUOTES, 'UTF-8');
        if (empty($new_password_err) && ($new_password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    

    // Check input errors before updating the database
    if (
        empty($old_password_err) &&
        empty($new_password_err) &&
        empty($confirm_password_err) &&
        empty($reset_err)
    ) {
        
        
        // Check Old Password
        $username = $_SESSION['autobackup_username'];
        $isValid = isOldPasswordValid($old_password, $username);
        if ($isValid>0) {
            
            $username = $adb->escape_string($username);
            $new_password = $adb->escape_string($new_password);
            $new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE automatedbackup_users SET password = '$new_password' WHERE username = '$username'";

            $result = $adb->query($sql);
            if($result) {

                // Unset all of the session variables
                $_SESSION["autobackup_loggedin"] = false;
                $_SESSION["autobackup_id"] = '';
                $_SESSION["autobackup_username"] = '';
                $_SESSION["autobackup_is_admin"] = false;
                
                header("location: $backup_URL/index.php");
                exit();
            }
        }
        $old_password_err = ($isValid === -1) ? 'Wrong Password !' : 'Something Wrong !';   
    }
}

function isOldPasswordValid($old_password, $username)
{
    global $adb;

    if (empty($username) || empty($old_password))
        return false;
    
    $username = $adb->escape_string($username);
    $sql = "SELECT `password` FROM automatedbackup_users WHERE username = '$username'";
    $result = $adb->query($sql);

    // Check if username exists, if yes then verify password
    if (!$result || $adb->num_rows($result) != 1)
        return false;
        
    $user = $adb->fetch_array($result);
    $hashed_password = $user['password'];
    
    return (password_verify($old_password, $hashed_password)) ? true : -1; 
}

function validatePasswordStrong($password) {
    $reset_err = '';
    
    if (strlen($password) < 8) $reset_err = '<li>Password must be at least 8 characters in length.</li>';
    if (!preg_match('@[0-9]@', $password)) $reset_err .= '<li>Password must contain at least one number.</li>';
    if (!preg_match('@[A-Z]@', $password)) $reset_err .= '<li>Password one upper case letter.</li>';
    if (!preg_match('@[a-z]@', $password)) $reset_err .= '<li>Password lower case letter.</li>';
    if (!preg_match('@[^\w]@', $password)) $reset_err .= '<li>Password one special character.</li>';
    
    return $reset_err;
}

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <?php include_once("layout/header.php"); ?>
    <link rel="stylesheet" href="<?php echo $backup_URL; ?>/layout/resources/css/style.css">
    <style>
        body{ font: 14px sans-serif; }

        .contact-form{ width: 360px; padding: 20px; }

        .center-div { margin: auto; width: 50%; }

        .center-h2 { color: white; text-align: center; }
        
        .form-control { width: 150%; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="center-div">
            <h2 class="center-h2">Reset Password</h2>
        </div>
        
        <?php 
        if(!empty($reset_err)) {
            echo '<div class="center-div alert alert-danger" style="width: 360px"><ul>' . $reset_err . '</ul></div>';
        }        
        ?>

        <div class="contact-form">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post"> 
                <input type="hidden" name="autobackup_token" value="<?php echo $_SESSION['autobackup_token']; ?>"/>
                <div class="form-group">
                    <label>Old Password</label>
                    <input type="password" name="old_password" class="form-control <?php echo (!empty($old_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $old_password; ?>">
                    <span class="invalid-feedback"><?php echo $old_password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control <?php echo (!empty($new_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $new_password; ?>">
                    <span class="invalid-feedback"><?php echo $new_password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="form-group">
                    <input id="submit" type="submit" class="btn btn-primary" value="Submit">
                    <a class="btn btn-link ml-2" href="<?php echo $backup_URL; ?>/views/listView.php">Cancel</a>
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