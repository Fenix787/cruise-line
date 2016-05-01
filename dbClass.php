<?php
/*
	Database PDO Wrapper Class
	Constructed using PDO tutorial from
	http://culttt.com/2012/10/01/roll-your-own-pdo-php-class/
	
	This class facilitates access to a mysql databse using the
	PHP PDO Library. It supports using bind variables to make
	SQL injection impossible.
	
*/

class Database{
	
	// configuration
	private $host;
    private $user;
    private $pass;
    private $db_name;
	private $dbh;
	private $error;
	
	// default constructor 
    public function __construct($c_host,$c_user,$c_pass,$c_db_name){
        // store mysql connection info
		$this->host = $c_host;
		$this->user = $c_user;
		$this->pass = $c_pass;
		$this->db_name = $c_db_name;
		
		// Create new database object
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name;
        
		// Set options
        $options = array(
            PDO::ATTR_PERSISTENT    => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
        );
		
        // Create a new PDO instanace
        try{
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        }
        // Catch any errors
        catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }
	
	// query method
	public function query($query){
    	$this->stmt = $this->dbh->prepare($query);
	}
	
	// bind method
	public function bind($param, $value, $type = null){
		if (is_null($type)) {
    	    switch (true) {
    	        case is_int($value):
    	            $type = PDO::PARAM_INT;
    	            break;
    	        case is_bool($value):
    	            $type = PDO::PARAM_BOOL;
    	            break;
    	        case is_null($value):
    	            $type = PDO::PARAM_NULL;
    	            break;
    	        default:
    	            $type = PDO::PARAM_STR;
    	    }
    	}
    	$this->stmt->bindValue($param, $value, $type);
	}
	
	// execute method
	public function execute(){
    	return $this->stmt->execute();
	}
	
	// resultset method
	public function resultset(){
    	$this->execute();
    	return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	// single method
	public function single(){
    	$this->execute();
    	return $this->stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	// rowcount method
	public function rowCount(){
    	return $this->stmt->rowCount();
	}
	
	// lastinsertid method
	public function lastInsertId(){
    	return $this->dbh->lastInsertId();
	}
	
	// begin transaction method
	public function beginTransaction(){
    	return $this->dbh->beginTransaction();
	}
	
	// end transaction method
	public function endTransaction(){
    	return $this->dbh->commit();
	}
	
	// cancel transaction method
	public function cancelTransaction(){
    	return $this->dbh->rollBack();
	}
	
	// dump debug method
	public function debugDumpParams(){
    	return $this->stmt->debugDumpParams();
	}
}


?>