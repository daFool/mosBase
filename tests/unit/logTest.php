<?php

class logTest extends \PHPUnit\Framework\TestCase {
    use \PHPUnit\DbUnit\TestCaseTrait;
    
    private $pdo=null;
    private $conn=null;
    private static $c=null;
        
    private static $db=null;
    
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
        return $this->createFlatXMLDataSet("tests/unit/testdatabase.xml");
    }
    
    public static function setUpBeforeClass() {
        if (self::$c===null) {
            self::$c = new mosBase\Config();            
        }
        self::$c->init("mosbase.ini");
        $dbparams = self::$c->get("Database");
        self::$db = new mosBase\Database($dbparams["dsn"], $dbparams["user"], $dbparams["password"]);
    }
    
    /**
    * @test
    */
    public function logStartupCorrectDefaults() {
        $log = new mosBase\Log(mosBase\Log::INFO, self::$db);
        $this->assertEquals(2, $log->getLogLevel());
        $this->assertEquals(0, $log->getSequence());
        $this->assertEquals("", $log->getMarker());
    }
    
    /**
     * @test
     * */
    public function setMarkerSucceeds() {
        $log = new mosBase\Log(mosBase\Log::INFO, self::$db);
        $log->setMarker("Mun");
        $this->assertEquals("Mun", $log->getMarker());
    }
    
    /**
     * @test
     * */
    public function logForCorrectLevel() {
        $log = new mosBase\Log(mosBase\Log::DEBUG, self::$db);
        $log->l("Mää", "Testaan",__FILE__,__METHOD__,__LINE__);
        $this->assertTableRowCount("log",1);
    }
    
    /**
     * @test
     * */
    public function noLogForIncorrectLevel() {
        $log = new mosBase\Log(mosBase\Log::ERROR, self::$db);
        $log->l("Mää", "Testaan",__FILE__,__METHOD__,__LINE__);
        $this->assertTableRowCount("log",0);
    }
    
    /**
     * @test
     * */
    public function correctMarkerAndLogRow() {
        global $_SERVER;
        $log = new mosBase\Log(mosBase\Log::DEBUG, self::$db);
        $log->setMarker("Mun");
        $log->l("Mää", "Testaan", __FILE__, __METHOD__,__LINE__, mosBase\Log::AUDIT);
        $this->assertTableRowCount("log",1);
        $_SERVER["HTTP_USER_AGENT"]="Mozilla/5.0 (X11; Fedora; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.79 Safari/537.36";
        $_SERVER["REMOTE_ADDR"]="127.0.0.1";
        $log->l("Mää", "Testaan lisää", __FILE__, __METHOD__, __LINE__, mosBase\Log::INFO);        
        $this->assertTableRowCount("log",2);
        $qt = $this->getConnection()->createQueryTable('log','select kuka, viesti, tiedosto, tarkenne, luokka, selain, marker from log');
        $et = $this->createXMLDataSet("tests/unit/logirivit.xml");
        $this->assertTablesEqual($et->getTable('log'), $qt);
    }
    
    /**
     * @test
     * */
    public function kolmeOikeaaKokonaislukua() {
        $log = new mosBase\Log(mosBase\Log::DEBUG, self::$db);
        
        $this->assertTrue($log->isInt("123"));
        $this->assertTrue($log->isInt("-123"));
        $this->assertTrue($log->isInt("0"));
    }
    
    /**
     *@test
     **/
    public function kolmeVaaraaKokonaislukua() {
        $log = new mosBase\Log(mosBase\Log::DEBUG, self::$db);
        
        $this->assertFalse($log->isInt("0123"));
        $this->assertFalse($log->isInt("-12 a3"));
        $this->assertFalse($log->isInt("0 Kissa"));
    }
    /**
     *@test
     **/
    public function etuliitteetTavustaYotaan() {
        $log = new mosBase\Log(mosBase\Log::DEBUG, self::$db);
        $i=42;
        // 42 4242 424242 42424242
        $o = [ "42B", "4.24kB", "424.24kB", "42.42MB", "4.24GB", "424.24GB", "42.42TB", "4.24PB", "424.24PB", "42.24YB" ];
        for($j=0;$j<9;$j++) {
            var_dump($i);
            $this->assertEquals($o[$j], $log->isJarjestelma($i));
            $i.="42";
        }
    }
}
?>
