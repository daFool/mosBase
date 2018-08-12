<?php
class malliTest extends \PHPUnit\Framework\TestCase {
    use \PHPUnit\DbUnit\TestCaseTrait;
    
    private $pdo=null;
    private $conn=null;
    private static $c=null;
    
    private static $db=null;
    private static $log=null;
    
    /**
    * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
    */
    public function getConnection() {
        if(self::$c ===null)
            self::$c = new mosBase\Config();            
        
        $dbparams = self::$c->get("Database");
        if($this->conn===null) {
            if ($this->pdo== null) {
                $this->pdo = new PDO($dbparams["dsn"], $dbparams["user"], $dbparams["password"]);
            }
            $this->conn = $this->createDefaultDBConnection($this->pdo, $dbparams["name"]);
        }
        return $this->conn;        
    }
    
    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataset
    */
    public function getDataSet() {
        return $this->createXMLDataSet("tests/unit/mallidatabase.xml");
    }
    
     public static function setUpBeforeClass() {
        if (self::$c===null) {
            self::$c = new mosBase\Config();            
        }
        self::$c->init("mosbase.ini");
        $dbparams = self::$c->get("Database");
        self::$db = new mosBase\Database($dbparams["dsn"], $dbparams["user"], $dbparams["password"]);
        self::$log = new mosBase\Log(mosBase\Log::AUDIT, self::$db);
    }
    
    
    /**
     * @test
     * */
    public function fooKonstruktoriIlmanHakutaulua() {
        $foo = new \testStubs\Foo(self::$db, self::$log, false);
        die("Kilroy");
        $this->assertFalse($foo->has());
    }
    
}
?>