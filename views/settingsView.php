<?php

chdir("../");
require_once('config.php');

 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["autobackup_loggedin"]) || $_SESSION["autobackup_loggedin"] !== true){
    header("location: $backup_URL/index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Settings</title>
    <?php include_once("layout/header.php"); ?>
    <link rel="stylesheet" href="<?php echo $backup_URL; ?>/layout/resources/css/style.css">
    <style>
        ::placeholder {
            color: #39b7dd;
            opacity: 1; /* Firefox */
        }
    </style>
</head>

<body>
    
    <?php $settings = $backupInstance->settings; ?>

    <div class="wrapper">
        <form onsubmit="onSubmit(event)" action="" method="POST">
            <input type="hidden" name="autobackup_token" value="<?php echo $_SESSION['autobackup_token']; ?>"/>
            <div class="contact-form">
                <div class="btn-back">
                    <a class="btn btn-info" role="button" href="listView.php" >History</a> 
                </div>
                
                <div class="text">
                    <h3>Basic Information</h3>
                </div>
                <div class="input-fields">
                    
                    <select class="input" id="status" title="Status" name="status" required>
                        <option value>Select Status</option>
                        <option value="Active">Active</option>
                        <option value="InActive">InActive</option>
                    </select>
                    <input class="input" title="Buckup Frequency" value="<?php echo $settings['frequency']?>" type="number" placeholder="Buckup Frequency" name="frequency" required>
                    <select class="input" id="unit" title="Frequency Unit" onchange="validateSpecificTime()" name="unit"  required>
                        <option value>Select Frequency Unit</option>
                        <option value="hours">Hours</option>
                        <option value="days">Days</option>
                    </select>

                    <input id="specific_time" class="input" name="specific_time" type="time" title="Specific time" value="<?php echo $settings['specific_time']?>" placeholder="Specific time">

                    <input class="input" name="max_backups" type="number" title = "Max Backups" value="<?php echo $settings['max_backups']?>" placeholder="Max Backups" required>
    
                    <input id="directory-path" class="input" name="path" type="text" title = "Backup Directory"  placeholder="Backup Directory" required>
    
                    <input id="next-trigger-time" class="input" name="next_trigger_time" type="datetime-local" title = "Next Triger Time" placeholder="Next Triger Time" disabled>
                </div>
            
                <div class="text">
                    <h3>Email Information</h3>
                </div>
                <div class="input-fields">

                    <input id="email" class="input" name="email" type="email" title="Email" value="<?php echo $settings['emailReport']?>" placeholder="Email" oninput="validateEmail()">
    
                    <input id='subject' class="input" name="email_sub" type="text" title="Email Subject" value="<?php echo $settings['emailSubject']?>" placeholder="Subject">
    
                    <div class="msg">
                        <textarea id='msg' cols="30" rows="10" name="email_body" title="Email Body" placeholder="Email Body"><?php echo $settings['emailBody']?>
                        </textarea>
                    </div>
                    <input id="submit" type="submit" class="btn" value="Save" />
                </div>
            </div>
        </form>
    </div>
    <?php include_once("layout/footer.php"); ?>
    <script>

        // Validate Form
        document.getElementById('status').value = "<?php echo $settings['status']?>"
        document.getElementById('unit').value = "<?php echo $settings['frequency_unit']?>"
        document.getElementById('next-trigger-time').value = "<?php echo str_replace(' ', 'T', $settings['next_triger_time'])?>"
        
        
        const form = document.querySelector('form');
        form.addEventListener('submit', event => {
            validateDirectoryFormat('js')
        });

        validateDirectoryFormat('php')
        function validateDirectoryFormat(source) {
            var pathElem = document.getElementById('directory-path')
            
            if (source == 'php')
                pathElem.value ="<?php echo $settings['path']?>"

            var path = pathElem.value
            
            const path_reg = '^(/[^/ ]*)+/?$'
            if (path.search(path_reg) == -1) {
                event.preventDefault();
                alert('Please Provide a Valid Directory !!!')
            }            
        }

        validateEmail();
        function validateEmail() {
            
            var email = document.getElementById('email').value;
            
            if (email) {
                document.getElementById('subject').setAttribute("required", true);
                document.getElementById('msg').setAttribute("required", true);
            } 
            
            if (!email) {
                document.getElementById('subject').required = false;
                document.getElementById('msg').required = false;
            }
        }

        validateSpecificTime();
        function validateSpecificTime(){
            
            var unit = document.getElementById('unit').value;
            
            if (unit == 'days') {
                document.getElementById('specific_time').setAttribute("required", true);
            }

            if (unit == 'hours') {
                document.getElementById('specific_time').required = false;
            }
            
        }

        async function onSubmit(e) {
            e.preventDefault();
            
            $('#submit').attr('disabled', true);

            $('body').loading();

            const data = formObject($('form').serializeArray());
            if (!$.isEmptyObject(data)) {
                var result = {};
                const editPage = '<?php echo $backup_URL; ?>/actions/edit.php';
                await $.ajax({
                    url: editPage,
                    type: "POST",
                    data: data,
                    success: function (resposne) {
                        $('#submit').attr('disabled', false);
                        $('body').loading('stop');
                        result = JSON.parse(resposne);
                    },
                    error: function (error) {
                        $('#submit').attr('disabled', false);
                        $('body').loading('stop');
                        console.log(`Error: ${error}`);
                    }
                });

                if (result.success) 
                    $.notify(result.msg, 'success')
                else
                    $.notify(result.msg, 'error')
            }

            function formObject(formData) {
                var jsonData = {};
                formData.forEach( el => jsonData[el['name']] = el['value']);
                return jsonData;
            }
        }
    </script>
</body>
</html>