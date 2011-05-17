<?php
class Database{
    var $config = array(
        'host' => 'localhost',
        'port' => 3306
    );
    
    var $mysqli;
    var $table_name;
    var $debug_mode = TRUE;

    //Query settings
    var $limit  = 25;
    var $offset = 0;
    var $order  = "'id' ASC";
    
    var $models = array();
    
    /**
     * Constructor
     */
    public function __construct($config){
        $this->config = $config;
    }
    
    /*
     * Establish and store database connection
     */
    public function connect(){
        //Use default database authentication
        require 'config/database.php';
        
	//Establish connection
        $this->mysqli = new mysqli( 
                           $config['host'],  
                           $config['username'],  
                           $config['password'], 
                           $config['database'], 
                           $config['port'] 
                           ); 

        $this->throwExceptionOnError();
    }
    
    public function get($where, $order = '', $limit = '', $offset = ''){
        if(empty($order)){
            $order = $this->order;
        }

        if(empty($limit)){
            $limit = $this->limit;
        }        
        
        if(empty($offset)){
            $offset = $this->offset;
        }
        
        if(!empty($where)){
            $where = "WHERE $where";
        }
        
        $query = "SELECT * FROM `$this->table_name` $where ORDER BY $order LIMIT $offset, $limit";

        if($this->debug_mode){
            echo $query.'<br/>';
        }
        
        return $this->query_result($query);
    }
    
    public function __call($name, $augments){
        if(!empty($this->table_name)){
            //Make sure the naming convention is correct
            if(preg_match('/by_/', $name)){
                $raw_fields = preg_replace('/by_(.*)/', '$1', $name); 

                if(empty($raw_fields)){
                    //
                    return NULL;
                }else{
                    $fields = explode('_AND_', $raw_fields);

                    $where = '';
                    $count = 0;

                    foreach($fields as $field){
                        if($count > 0){
                            $where .= ' AND ';
                        }

                        $where .= "$field = '$augments[$count]'";

                        $count++;
                    }

                    return $this->get($where);
                }
            }
            
            if(preg_match('/all/', $name)){
                return $this->get('', $augments[0], $augments[1], $augments[2]);
            }
            
            return array();
        }else{
            return NULL;
        }
    }
    
    /*
     * Get table model
     */
    public function __get($name){
        //If this is a generic database class
        if(empty($this->table_name) && preg_match('/get_/', $name)){
            $table_name = preg_replace('/get_(.*)/', '$1', $name);
            
            if( empty( $this->models[$table_name] ) ){
                //Contruct model using a new database connection 
                $model  = new Database($this->config);
                $model->table_name          = $table_name;
                
                $this->models[$table_name]  = $model;
                
                return $model;
            }else{
                //Use existing model
                return $this->models[$table_name];
            }
        }
        
        return NULL;
    }
    
    /*
     * Prepare database query
     */
    protected function query($query){
        //Initiate database connection
        $this->connect();

        $this->mysqli->set_charset("utf8");

        //Setup query
        $stmt = $this->mysqli->prepare($query);     

        $this->throwExceptionOnError();	

        //Execute query
        $stmt->execute();

        $this->throwExceptionOnError();	

        return $stmt;
    }
    
    /*
     * Retrieve results of any query execution
     * 
     * Usage:
     * 
     * $rows = $this->query_result('SELECT * FROM Table');
     * echo $rows[0]->id;
     */
    protected function query_result($query){
        //Process and return statement
        $stmt = $this->query($query);

        $rows = $this->get_result($stmt);

        return $rows;
    }
    
    /*
     * Sort record values into array format
     */
    protected function stmt_bind_assoc (&$stmt, &$out) {
        $data 	= $stmt->result_metadata();
        $fields = array();
        $out 	= array();

        $count = 0;

        //Loop through all fields
        while($field = $data->fetch_field()) {
            $fields[$count] = &$out[$field->name];

            $fields[$count] = $fields[$count];
            $count++;
        }

        call_user_func_array(array($stmt, "bind_result"), $fields);
    }
    
    /*
     * Process and return rows in array format from a statement
     */
    protected function get_result($stmt){
        $this->stmt_bind_assoc($stmt, $row);

        $rows = array();

        // loop through all result rows
        while ($stmt->fetch()) {
            //Store record in object format
            $rows[]	= (object) $row;

            //Setup new row
            $row = array();

            //Pass record info into the row
            $this->stmt_bind_assoc($stmt, $row);
        }

        $this->throwExceptionOnError();

        $stmt->free_result();
        $this->mysqli->close();

        //Return list of rows/records
        return $rows;
    }
        
    /** 
    * Utitity function to throw an exception if an error occurs 
    * while running a mysql command. 
    */
    protected function throwExceptionOnError() { 
        if($this->mysqli->error && debug_mode) { 
            $msg = $this->mysqli->errno . ": " . $this->mysqli->error; 
            throw new Exception('MySQL Error - '. $msg); 
        }
    } 
}
?>
