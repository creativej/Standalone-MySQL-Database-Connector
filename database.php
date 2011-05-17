<?php
/**
 * Standalone database connector
 *
 * @author		Jamison Lu
 * 
 */
class Database{
    //Default config setting
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
    var $order  = "`id` ASC";
    
    var $models = array();
    
    //************************ Public functions ****************************//
    
    /**
     * Constructor
     */
    public function __construct($config){
        $this->config = $config;
    }
    
    /*
     * Dynamic method to handle all calls
     */
    public function __call($name, $augments){
        if(!empty($this->table_name)){
            //Handles $this->by_{fields}          
            if($result = $this->find('by_', $name, $augments)){
                return $result;
            }            
            
            //Handles $this->first_by_{fields}
            if($result = $this->find('first_by_', $name, $augments, true)){
                return $result;
            }
            
            //Get all record
            if(preg_match('/all/', $name)){
                return $this->get('', $augments[0], $augments[1], $augments[2]);
            }
            
            return NULL;
        }else{
            return NULL;
        }
    }
    
    /*
     * Get table model
     */
    public function __get($name){
        //Make sure this is a generic database class
        //and the naming convention is used
        if(empty($this->table_name) && preg_match('/get_/', $name)){
            $table_name = preg_replace('/get_(.*)/', '$1', $name);
            
            //Check to see if a model exist already
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
     * Generic method for retrieving data
     */
    public function get($where = '', $order = '', $limit = '', $offset = ''){
        //Format values
        $this->get_value(&$order, $this->order);
        $this->get_value(&$limit, $this->limit);
        $this->get_value(&$offset, $this->offset);
        
        $query = "SELECT * FROM `$this->table_name` $where ORDER BY $order LIMIT $offset, $limit";

        //Outputs query for debug purpose
        if($this->debug_mode){
            echo $query.'<br/>';
        }
        
        return $this->query_result($query);
    }   
    
    //************************ Protected functions ****************************//

    /*
     * Establish and store database connection
     */
    protected function connect(){       
	//Establish connection
        $this->mysqli = new mysqli( 
                           $this->config['host'],  
                           $this->config['username'],  
                           $this->config['password'], 
                           $this->config['database'], 
                           $this->config['port']
                           ); 

        $this->throwExceptionOnError();
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
    
    /*
     * Dynamically contruct MySQL where statement
     */
    protected function contruct_where($raw_fields, $augments, $max_augments){
        $fields = explode('_AND_', $raw_fields);

        $where = 'WHERE ';
        $count = 0;
        
        foreach($fields as $field){
            if($count > 0){
                $where .= ' AND ';
            }
            
            $where .= "$field = '$augments[$count]'";

            $count++;
        }
        
        //Store the maximum augments for getting order, offest, limit
        $max_augments = $count + 3;
        
        return $where;
    }
    
    /*
     * Query and return result
     */
    protected function find($filter, $name, $augments, $single_record = false){       
        //Make sure the naming convention is correct
        if(preg_match('/^'.$filter.'/', $name)){
            $raw_fields = preg_replace('/^'.$filter.'(.*)/', '$1', $name); 

            $result = array();

            //Make sure there are fields specified
            if(!empty($raw_fields)){
                $max_augments = 0;
                
                $where = $this->contruct_where($raw_fields, $augments, &$max_augments);
                
                $order      = $augments[$max_augments - 3];
                $limit      = $augments[$max_augments - 2];
                $offset     = $augments[$max_augments - 1];                

                //Query one record only
                if($single_record){
                    $limit = 1;
                }                
                
                $result = $this->get($where, $order, $limit, $offset);
            }
            
            if($single_record){
                //Make sure there are results
                if(sizeof($result) > 0){
                    //return single record
                    return $result[0];
                }else{
                    //returns empty class;
                    return new stdClass;
                }
            }else{
                return $result;
            }
        }else{
            return FALSE;
        }
    }
    
    /*
     * Make sure the value is not empty
     */
    protected function get_value($value, $default){
        if(empty($value)){
            $value = $default;
        }
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
