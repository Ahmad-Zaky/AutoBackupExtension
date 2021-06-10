<?php 
    class Database {
        
        private static $instance = NULL;
        private $connection;
         
        // Constructor
        // The db connection function is called within the private constructor.
        private function __construct() {
            $this->open_connection_db();
        }
        
        function __destruct() {
            $this->close();
        }
        
        // Establish a DB Connection using mysqli object
        public function open_connection_db() {
            global $dbconfigs;
                
            $this->connection = new mysqli(
                $dbconfigs['db_server'], 
                $dbconfigs['db_username'],
                $dbconfigs['db_password'],
                $dbconfigs['db_name']
            );

            // Validation
            if($this->connection->connect_errno)
                echo "CONNECTION FAILED! " . $this->connection->connect_error;
        }
        
        // create one object within the class (singleton pattern)
        public static function get_instance() {
            if(!self::$instance)
                self::$instance = new Database();
            
            return self::$instance;
        }
        
        // return the db connection
        public function get_connection() {
            return $this->connection;
        }
        
        // Making a Query
        public function query($sql) {
            $q_result = $this->connection->query($sql);
            
            // Validation
            if(!$q_result)
                die("QUERY FAILED! " . $this->connection->connect_error);
            
            return $q_result;
        }
        
        // get number of rows
        public function num_rows(&$result) {
            return mysqli_num_rows($result);
        }

        // fetch array from query result
        public function fetch_array(&$result) {

            $row = mysqli_fetch_assoc($result);
            
            if(is_array($row))
                $row = array_map('htmlentities', $row);
            
            return $this->change_key_case($row);
        }

        // change keys case to lowercase
        protected function change_key_case($arr) {
            return is_array($arr) ? array_change_key_case($arr) : $arr;
        }

        // escaping sql strings
        public function escape_string($string) {
            
            return $this->connection->real_escape_string($string);
        }
        
        // getting the already inserted id
        public function inserted_id() {
            return $this->connection->insert_id;
        }
        
        // getting affected rows from last operation
        public function affected_query() {
            return $this->connection->affected_rows;
        }

        // close connection
        public function close() {
            mysqli_close($this->connection);
        }
    }
    
    $adb = Database::get_instance(); 
    $connection = $adb->get_connection();
