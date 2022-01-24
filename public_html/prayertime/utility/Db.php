<?php

//define('DB_MAIN', 'driver|localhost|user1|pa55word|db1');
//define('DB_MAIN', 'pgsql|ec2-54-235-247-209.compute-1.amazonaws.com|dbxxwagcpdfpvw|ce8808765b7c11d0026c1a0aca7cdc83dc0eb5f4971bb72bbd0895c3f9f499b0|dddavvon9btvbs');

// Connect to database db1
//$db = new Db();

// Request "SELECT * FROM table1 WHERE a=16 AND b=22"
// Get an array of stdClass's
//$rows = $db->fetchAll('SELECT * FROM table1 WHERE a=? AND b=?', 16, 22);

class Db
{

    private static $databases;
    private $connection;

    public function __construct($connDetails = 'DB_MAIN') {
        if(!isset(self::$databases) ||
            !isset(self::$databases[$connDetails]) ||
            !is_object(self::$databases[$connDetails])) {

            $configFile = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
            $config = include($configFile);

            $driver = $config['DB'][$connDetails]['driver'];
            $host   = $config['DB'][$connDetails]['host'];
            $user   = $config['DB'][$connDetails]['user'];
            $pass   = $config['DB'][$connDetails]['password'];
            $dbname = $config['DB'][$connDetails]['dbname'];
            $port   = $config['DB'][$connDetails]['port'];

            //list($driver, $host, $user, $pass, $dbname) = explode('|', $connDetails);
            $dsn = "$driver:host=$host;dbname=$dbname;port=$port";
            self::$databases[$connDetails] = new PDO($dsn, $user, $pass);
        }
        
        $this->connection = self::$databases[$connDetails];
    }
    
    public function fetchAll($sql){
        $args = func_get_args();
        array_shift($args);
        $statement = $this->connection->prepare($sql);        
        $statement->execute($args);
         return $statement->fetchAll(PDO::FETCH_OBJ);
    }
}