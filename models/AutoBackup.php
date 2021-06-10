<?php

class AutoBackup {
    
    protected $hostname = '';
    protected $username = '';
    protected $password = '';
    protected $dbname   = '';
    
    protected $settings = [];
    
    protected $now = 0;
    protected $date = "0000-00-00 00:00:00";

    protected $dumpfname = '';
    protected $mysqldump = 'mysqldump'; // You can add the bin directory for this command if needed

    // Custom Email Info Could be replced in the future
    protected $replyTo = '<reply_email>';
    protected $replyToName = '<reply_name>';
    protected $fromEmail = '<reply_email>';

    protected $settingMap = [
        "localbackup_status"        => "status",  
        "localbackup_frequency"     => "frequency",
        "localbackup_number"        => "max_backups",
        "localbackup_directory"     => "path",
        "frequency_unit"            => "frequency_unit",
        "specific_time"             => "specific_time",
        "next_triger_time"          => "next_triger_time",
        "emailreport_email"         => "emailReport",
        "emailreport_subject"       => "emailSubject",
        "emailreport_body"          => "emailBody",
        "emailreport_login_email"   => "loginEmail",
        "emailreport_login_subject" => "loginSubject",
        "emailreport_login_body"    => "loginBody",
        "autobackup_unique_key"    => "autobackup_unique_key",
    ];

    protected $settingsListEnums = [
        'localbackup_status' => ['Active', 'Inactive'],
        'frequency_unit' => ['days', 'hours']
    ];

    protected $settingsValidationRules = [];

    protected $cron_status = [
        'INACTIVE' => 0,
        'ACTIVE' => 1,
        'RUNNING' => 2
    ];

    const TMP_PATH = 'tmp/';
    const OPERATOR = "+";
    const PATH_REG = '~^(/[^/ ]*)+/?$~m';
    const FILE_EXTENSION = 'gz';
    const FILE_TYPE = 'Database';
    const BACKUP_TYPE = 'Localbackup';
    
    /**
     * __construct
     *
     * @return void
     */
    function __construct() {
        
        // Database configuration
        $this->setDBConfiguration();
        
        // Backup Setting
        $this->setSettings();
        
        // SQL filename Generation
        $this->dumpfname = $this->dbname . "_" . date("Y-m-d_H-i-s", $this->now()).".sql";

        // Set Settings Validation Rules
        global $backup_dir;
        include_once "models/rules.php";
        $this->settingsValidationRules = $settings_validation_rules;
    }
       
    /**
     * __get
     *
     * @param  mixed $key
     * @return void
     */
    function __get($key) {
        return $this->$key;
    }
    
    /**
     * __set
     *
     * @param  mixed $key
     * @param  mixed $value
     * @return void
     */
    function __set($key, $value) {
        $this->$key  = $value;
    }
    
    /**
     * setDBConfiguration
     *
     * @return void
     */
    protected function setDBConfiguration() {

        global $dbconfigs;

        $this->hostname  = $dbconfigs['db_server'];         // MYSQL server address
        $this->username  = $dbconfigs['db_username'];       // your username MYSQL
        $this->password  = $dbconfigs['db_password'];       // Password
        $this->dbname    = $dbconfigs['db_backup_name'];    // database name
    }

    /**
     * setSettings
     *
     * @return void
     */
    protected function setSettings() {
        global $adb;
 
        $query = "SELECT * FROM automatedbackup_settings";
        $result = $adb->query($query);

        if ($adb->num_rows($result) > 0) {
            while($row = $adb->fetch_array($result)) {
                if(isset($this->settingMap[$row['key']])){
                    $keyMap = $this->settingMap[$row['key']];
                    $this->settings[$keyMap] = $row['value'];
                }
            }
        }
    }

    /**
     * validate
     *
     * @param  string $module
     * @param  string $action
     * @return array[success => boolean, msg => string]
     */
    public function validate($module="", $action="") {
        if (empty($this->settings['status']) || empty($this->now()) || empty($this->settings['next_triger_time']) || empty($this->settings['path']) )
            return false;
        
        if ($this->settings['status'] !== "Active")
            return [
                'success' => false,
                'msg' => "The Status is InActive !!!"
            ]; 
        
        if ($this->now() < strtotime($this->settings['next_triger_time']) && ($module !== "AutoBackup" && $action !== "run"))
            return [
                'success' => false,
                'msg' => "There is a Problem in Time OR in query parameter !!!"
            ]; 

        if (!preg_match(self::PATH_REG, $this->settings['path']) && !file_exists($this->settings['path']))
            return [
                'success' => false,
                'msg' => "The path is wrong !!!"
            ];   


        return [
            'success' => true,
            'msg' => "Validated Successfully"
        ];    
    }
      
    /**
     * execute
     *
     * @param  string $module
     * @param  string $action
     * @return boolean $status
     */
    public function execute($module="", $action="") {

        if(empty($this->dbname) || empty($this->settings['max_backups'])) 
            return [ 
                'success' => false, 
                'msg' => 'Database name does not exist or There is no Max Backups !!!'
            ];

        $filesNames = $this->getFilesName();
		$files = $this->filter($filesNames, $this->dbname);

        if ($this->countfiles($files) < $this->settings['max_backups']) {
            return $this->run($module, $action);
        } else {

            $deleted = $this->getDeleted($files);
            if($this->delete($deleted['deletedFileWithPath'])) {
                if($this->backupDeleteLog($deleted['deletedFile'])) {
                    return $this->run($module, $action);
                }
            }
        }
        
        return [
            'success' => false, 
            'msg' => 'Delete Bakup or Log Deleted Backup has Failed !!!'
        ];
    }
    
    /**
     * run
     *
     * @param  mixed $module
     * @param  mixed $action
     * @return array $status
     */
    protected function run($module="", $action="") {
        /**
         * Status values Mapping
         * 
         * 0 -> Failed to run Backup
         * 1 -> Backup created
         * 2 -> Create Log Inserted
         * 3 -> Next Trigger Time Updated
         * 4 -> Email Sent Successfully
         */
        $status_msg = [
            'Failed to run Backup',
            'Backup created',
            'Create Log Inserted',
            'Next Trigger Time Updated',
            'Email Sent Successfully'
        ];

        
        $status = 0;
        $zipfname = '';
        if (!($zipfname = $this->create()))
            return [
                'status' => $status,
                'status_msg' => $status_msg[$status],
                'success' => false,
                'msg' => "Backup Creation Failed !!!"
            ];
        
        $status = 1;
        if(!$this->backupCreateLog($zipfname))
            return [
                'status' => $status,
                'status_msg' => $status_msg[$status],
                'success' => false,
                'msg' => "Backup Creation Log Failed !!!"
            ];
        
            
        $status = 2;
        if (!($module == "AutoBackup" && $action == "run") && !empty($zipfname)) {
            $new_next_triger_time = $this->updateTime();
            
            if (!$this->updateNextTrigerTime($new_next_triger_time))
                return [
                    'status' => $status,
                    'status_msg' => $status_msg[$status],
                    'success' => false,
                    'msg' => "Next Trigger Time Update Failed !!!"
                ];
            
            $status = 3;
        }
        
        
        if(!function_exists('sendEmail')) 
            return [
                'status' => $status,
                'status_msg' => $status_msg[$status],
                'success' => false,
                'msg' => "Sending Email Failed because function send email does not exist !!!"
            ];
        
            
        $username = $_SESSION["autobackup_username"];
        $toEmail = $this->settings['emailReport'];
        $subject = html_entity_decode($this->settings['emailSubject']);
        $body = !empty($username) 
                    ? sprintf('User <strong>' . $username . '</strong> Did an Action: ' . $this->settings['emailBody'], $zipfname, $this->date())
                    : sprintf($this->settings['emailBody'], $zipfname, $this->date());
        
        return (sendEmail($toEmail, $subject, $body) === false) ? [
            'status' => $status,
            'status_msg' => $status_msg[$status],
            'success' => false,
            'msg' => "Sending Email Failed because of an Internal Issue !!!"
        ] : [
            'status' => $status++,
            'status_msg' => $status_msg[$status],
            'success' => true,
            'msg' => "Backup Process finished Successfully."
        ];
    }
    
    /**
     * create
     *
     * @return string $zipfname
     */
    protected function create() {

        if (
                empty(self::TMP_PATH) || 
                empty($this->dumpfname) || 
                empty($this->hostname)|| 
                empty($this->username) || 
                empty($this->dbname) || 
                empty($this->settings['path'])
            ) 
            return false;

        global $current_dir;

        $mysqlPath = $this->settings['path'] . self::TMP_PATH;

        // Check tmp Path Existing
        if (!is_dir($mysqlPath))
            mkdir($mysqlPath);

        // Get MySQL File Name with its path
        $pathWithDumpFName =  $mysqlPath . $this->dumpfname;

        //command
        $command  = "$this->mysqldump --host=$this->hostname --user=$this->username ";
        $command .= ($this->password) ? "--password='$this->password' " : "";
        $command .= $this->dbname . " > " . $pathWithDumpFName;
        
        //run command
        exec($command, $output, $retval);

        if($retval !== 0)
            return false;

        // compressed into a GZIP file
        $zipfname = $this->dbname . "_" . date("Y-m-d_H-i-s", $this->now()) ."." . self::FILE_EXTENSION;
        $pathWithFName = $this->settings['path'] . $zipfname;
        
        if ($this->compress($pathWithDumpFName, $pathWithFName)) {
            $this->delete($pathWithDumpFName);  
            return $zipfname;
        }
        
        return false;
    }

    /**
     * compress
     *
     * @param String $source 
     * @param String $dest 
     * @param Integer $level 
     * @return Boolean 
     */
    protected function compress($source, $dest, $level = 9){ 
        $mode = 'wb' . $level; 
        if ($fp_out = gzopen($dest, $mode)) { 
            if ($fp_in = fopen($source,'rb')) { 
                while (!feof($fp_in)) 
                    gzwrite($fp_out, fread($fp_in, 1024 * 512)); 
                fclose($fp_in); 
            } else
                return false; 

            gzclose($fp_out); 
            return true; 
        }
        return false; 
    } 

    /**
     * delete
     *
     * @param  string $filename
     * @return boolean
     */
    protected function delete($filename) {
        
        if (empty($filename)) 
            return false;
        
        if(unlink($filename)){
            return true ;
        }
        return false;	
    }
    
    /**
     * updateNextTrigerTime
     *
     * @param  string $new_next_triger_time
     * @return boolean
     */
    protected function updateNextTrigerTime($new_next_triger_time) {

        if (empty ($new_next_triger_time)) 
            return false;

        global $adb;
        $query = "UPDATE automatedbackup_settings SET `value` = '$new_next_triger_time'
                  WHERE `key` LIKE 'next_triger_time'";
        
        return ($adb->query($query)) ? true : false;         
    }
        
    /**
     * backupCreateLog
     *
     * @param  string $fileName
     * @return boolean
     */
    protected function backupCreateLog($fileName) {
        if (empty($fileName) || empty($this->settings['path']))
            return false;
        
        global $adb;
        
        $filePath    = $this->settings['path'];
        $createdTime = $this->date();
        $fileType  	 = self::FILE_TYPE;
        $fileSize    = filesize($filePath.$fileName); // bytes
        $is_Delete   = 0;
        $backupType    = self::BACKUP_TYPE;
        
        $filePath = $adb->escape_string($filePath);
        $fileSize = $adb->escape_string($fileSize);

        $query = "INSERT INTO automatedbackup_logs 
                 (`createdtime`, `filename`, `filetype`, `filesize`, `path`, `deleted`, `type`) VALUES 
                 ('$createdTime', '$fileName', '$fileType', '$fileSize', '$filePath', '$is_Delete', '$backupType')";
    
        return (!empty($adb->query($query))) ? true : false;	
    }
        
    /**
     * backupDeleteLog
     *
     * @param  string $fileName
     * @return void
     */
    protected function backupDeleteLog($fileName) {

        if (empty($fileName)) 
            return false;

        $is_Delete = 1;
        
        global $adb;
        $fileName = $adb->escape_string($fileName);
        $query = "UPDATE automatedbackup_logs SET `deleted` = $is_Delete WHERE `filename` LIKE '%$fileName%'";
        if($adb->query($query))
            return true;  
    }
    
    /**
     * saveSettings
     *
     * @param  array $settings
     * @return boolean
     */
    public function saveSettings($post) {
        
        $settings = $this->parseSettings($post);
        $isValid = $this->validateSettings($settings);
        
        if (!$isValid['success'])  
            return $isValid;

        foreach ($settings as $key => $setting) {
            global $adb;
            
            $setting = $adb->escape_string($setting);
            $key = $adb->escape_string($key);
            $query = "UPDATE automatedbackup_settings SET `value` = '" .$setting. "' WHERE `key` = '" .$key. "'";
            if (!$adb->query($query))
                return [
                    'success' => false,
                    'msg' => 'Save Settings Query Failed !!!'
                ];
        }
    
        return [
            'success' => true,
            'msg' => 'Settings Saved Successfully'
        ];
    }

    /**
     * validateSettings 
     *
     * @param Array $settings
     * @return Array
     */
    protected function validateSettings($settings) {
        if (empty($settings))
            return [
                'success' => false,
                'msg' => 'Settings are Empty !!!'
            ];

        foreach($settings as $setting => $value) {
            
            $rules = $this->settingsValidationRules[$setting];

            // Validate empty rule
            if (empty($value)) {
                if (array_key_exists('required', $rules)) {

                    // Validate Required Fields
                    if ($rules['required']['is_required'] && !$rules['required']['has_dependent']) 
                        return [
                            'success' => false,
                            'msg' => $rules['required']['msg']
                        ];
                        
                    // Validate Dependent Required Fields Rules
                    if (!$rules['required']['is_required'] && $rules['required']['has_dependent']) {
                        
                        // Validate Dependent Required Fields whether empty or not
                        if (!empty($settings[$rules['required']['dependent_field']])) {
                            
                            // Validate Dependent Required Fields whether equals a specific value or not
                            if ($settings[$rules['required']['dependent_field']] !== $rules['required']['dependent_value']) 
                                continue;

                            return [
                                'success' => false,
                                'msg' => $rules['required']['msg']
                            ];
                            
                        }

                        // Validate Dependent Required Fields whether empty or not
                        if (empty($settings[$rule['required']['dependent_field']]))
                            continue;
                    }

                } else
                    continue;
            }
            
            // Validate remain rules
            foreach ($rules as $ruleName => $rule) {
                switch ($ruleName) {
                    case 'search':
                        if (!in_array($value, $this->{$rule['args'][0]}[$setting]))
                            return [
                                'success' => false,
                                'msg' => sprintf($rule['msg'], implode(', ', $this->settingsListEnums[$setting]))
                            ];
                        break;
                    case 'isnumeric':
                        if (!is_numeric($value))
                            return [
                                'success' => false,
                                'msg' => $rule['msg']
                            ];
                        break;
                    case 'positive':
                        if (is_numeric($value) && $value < 1)
                            return [
                                'success' => false,
                                'msg' => $rule['msg']
                            ];
                        break;
                    case 'regex':
                        if (!preg_match($rule['regex'], $value))
                            return [
                                'success' => false,
                                'msg' => $rule['msg']
                            ];
                        break;
                    case 'email':
                        if (!filter_var($value, $rule['const']))
                            return [
                                'success' => false,
                                'msg' => $rule['msg']
                            ];
                        break;
                    case 'fileexists':
                        if (!is_dir($value))
                            return [
                                'success' => false,
                                'msg' => $rule['msg']
                            ];
                        break;
                }
            }
        }
        
        return [
            'success' => true,
            'msg' => 'Settings Validated Successfully'
        ];
    }

    /**
     * parseSettings
     * 
     * @param Array $post
     * @return Array
     */
    protected function parseSettings($post) {
        return (empty($post)) ? [] : [
            'localbackup_status'    => htmlentities($post["status"], ENT_QUOTES, 'UTF-8'),
            'localbackup_frequency' => htmlentities($post["frequency"], ENT_QUOTES, 'UTF-8'),
            'frequency_unit'        => htmlentities($post["unit"], ENT_QUOTES, 'UTF-8'),
            'specific_time'         => htmlentities($post["specific_time"], ENT_QUOTES, 'UTF-8'),
            'localbackup_number'    => htmlentities($post["max_backups"], ENT_QUOTES, 'UTF-8'),
            'localbackup_directory' => htmlentities($post["path"], ENT_QUOTES, 'UTF-8'),
            'emailreport_email'     => htmlentities($post["email"], ENT_QUOTES, 'UTF-8'),
            'emailreport_subject'   => htmlentities($post["email_sub"], ENT_QUOTES, 'UTF-8'),
            'emailreport_body'      => htmlentities($post["email_body"], ENT_QUOTES, 'UTF-8')
        ];
    }

    /**
     * listBackupLogs
     *
     * @param Integer $page
     * @param Integer $limit
     * @param Integer $count
     * @return Array
     */
    public function listBackupLogs($page, $limit, $count) {
        
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
            'createdtime',
            'filename',
            'filesize',
            'deleted'
        ];
        
        $offset = (($page - 1) * $limit);

        global $adb;
        
        if (!$count) {
            $query = "SELECT COUNT(id) as logs_count FROM automatedbackup_logs";
            $result = $adb->query($query);
            $count = $adb->fetch_array($result)['logs_count'];
        }

        $query = "SELECT * FROM automatedbackup_logs order by id desc LIMIT $offset, $limit";
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

            $filtered['filesize'] = formatSizeUnits($filtered['filesize']);
            $filtered['deleted'] = ($filtered['deleted'] == 0) ? "No" : "yes";

            $pageList[] = $filtered;
        }
        
        return [
            'success' => true,
            'pageList' => $pageList,
            'count' => $count,
            'msg' => '',
        ];
    }

    /**
     * updateTime
     *
     * @return string $next_triger_time
     */
    protected function updateTime() {
        $next_triger_time = false;
        
        if (
            empty($this->settings['frequency_unit']) ||
            empty($this->settings['specific_time']) ||
            empty($this->settings['frequency']) ||
            !is_numeric($this->settings['frequency'])
        ) 
            return false;
        
        switch ($this->settings['frequency_unit']) {
            case "hours":
                $this->settings['frequency'] = $this->now() + ($this->settings['frequency'] * 60 * 60);
                $next_triger_time = date('Y-m-d H:i:s', $this->settings['frequency']);
                break;
            case "days":
                $this->settings['frequency'] = self::OPERATOR . $this->settings['frequency'] . " " . $this->settings['frequency_unit']; 
                $next_triger_time = date('Y-m-d '.$this->settings['specific_time'], strtotime($this->settings['frequency']));
                break;
        }
        
        return $next_triger_time;
    }

    /**
     * getDeleted
     *
     * @param  array $files
     * @return array
     */
    protected function getDeleted($files) {

        if (empty($files) || empty($this->settings['path'])) 
            return false;

        $dates = $this->normalize($files);
        
        $this->sortDates($dates);
        
        $deletedFile = $this->denormalize($dates[0]);
        
        $delete_Log = $deletedFile.".". self::FILE_EXTENSION;
        
        return [
            'deletedFile' => $deletedFile,
            'deletedFileWithPath' => $this->settings['path'].$delete_Log
        ];
    }
    
    /**
     * getFilesName
     *
     * @return string $filesName
     */
    protected function getFilesName() {
        
        if (empty($this->settings['path']) || empty(self::FILE_EXTENSION)) 
            return false;
        
        $ext = self::FILE_EXTENSION;
        $filesName = [];
        $files = glob($this->settings['path'] . "*.$ext");
        
        foreach($files as $file) {
            $fileName =	basename($file, ".$ext");
            array_push($filesName, $fileName);
        }
        return $filesName;
    }
    
    /**
     * filter
     *
     * @param  string $filesNames
     * @param  string $key
     * @return array $filterdFiles
     */
    protected function filter($filesNames, $key) {
        if(empty($filesNames) || empty($key)) 
            return false;
        
        $filterdFiles = [];
    
        foreach($filesNames as $file) {
            if (strpos($file, $key) !== false && strpos($file, " ") === false) {
                array_push($filterdFiles,$file);
            }
        }	
        return $filterdFiles;
    }
        
    /**
     * countfiles
     *
     * @param  array $files
     * @return integer|boolean
     */
    protected function countfiles($files) {
        
        if (empty($files)) 
            return false;
        
        if (is_array($files))
            return count($files);
    
        return false;
    }
    
    /**
     * normalize
     *
     * @param  array $filteredFiles
     * @return array $normalized_date
     */
    protected function normalize($filteredFiles) {

        if (empty($filteredFiles)) 
            return false;

        $normalized_date = [];
        foreach($filteredFiles as $file){
                $date = explode('_', $file);
                $date[sizeof($date) - 1] = str_replace("-",":",$date[sizeof($date) - 1]);
                $normalized_date[] =  $date[sizeof($date) - 2] . ' ' . $date[sizeof($date) - 1];
        }
        return $normalized_date;
    }
        
    /**
     * denormalize
     *
     * @param  string $date
     * @return string $fileName
     */
    protected function denormalize($date) {

        if (empty($date) || empty($this->dbname)) 
            return false;

        $array = explode (" ", $date);
        $array[1] = str_replace(":","-",$array[1]);
        $fileName = $this->dbname."_".$array[0]."_".$array[1];

        return $fileName;
    }
        
    /**
     * sortDates
     *
     * @param  array $array
     * @return array $array
     */
    protected function sortDates($array) {
        
        if(empty($array)) 
            return false;
      
        $compare = function($a,$b) {
            $a_timestamp = strtotime($a); // convert a (string) date/time to a (int) timestamp
            $b_timestamp = strtotime($b);

            // new feature in php 7
            // return $a_timestamp <=> $b_timestamp;
            
            // php 5.6 version
            $return = ($a_timestamp < $b_timestamp) ? -1 : '';
            $return = ($a_timestamp == $b_timestamp) ? 0 : '';
            $return = ($a_timestamp > $b_timestamp) ? 1 : '';

            return $return;
        };
        
        usort($array, $compare);
    
        return $array;
    }

    public function now() {
        global $_timezone;
        return time() + $_timezone;
    }

    public function date($hasTimeZone=true) {
        $now = $hasTimeZone ? $this->now() : time();
        return date("Y-m-d H:i:s", $now);
    }
}