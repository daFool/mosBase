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
    public function konstruktoriIlmanHakutaulua() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, false);        
        $this->assertFalse($foo->has());
    }
    
    /**
     * @test
     * */
    public function konstruktoriHakutaululla() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $this->assertFalse($foo->has());
    }
    
    /**
     * @test
     * */
    public function pvmTunnistuu() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $this->assertTrue($foo->resolveTime(\mosBase\Malli::DATE, "2018-08-13"));
    }
    /**
     * @test
     * */
    public function pvmFeilaa() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $this->assertFalse($foo->resolveTime(\mosBase\Malli::DATE, "2018-08-32"));
    }
    /**
     * @test
     * */
    public function aikaToimii() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $this->assertTrue($foo->resolveTime(\mosBase\Malli::TIME, "09:30:55"));
        $this->assertTrue($foo->resolveTime(\mosBase\Malli::TIME, "09:30:55+0300"));
        $this->assertTrue($foo->resolveTime(\mosBase\Malli::TIME, "09:30:55+03:00"));                      
    }
    /**
     * @test
     * */
    public function aikaFeilaa() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $this->assertFalse($foo->resolveTime(\mosBase\Malli::TIME, "29:30:55"));
        $this->assertFalse($foo->resolveTime(\mosBase\Malli::TIME, "09:30:55Z0300"));
        $this->assertFalse($foo->resolveTime(\mosBase\Malli::TIME, "09:30:55+3A:00"));                      
    }
    /**
    /**
     * @test
     * @depends konstruktoriIlmanHakutaulua     
     * */
    public function onnistuukoRiviKakkosenHakuIdlla() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $d = array("id"=>2);
        $this->assertTrue($foo->exists($d));
        $row = $foo->give();
        $odotus = array (
            "id"=>2,
            "intti"=>20,
            "merkkijono"=>"Kakskyt",
            "pvm"=>"2017-08-13",
            "aika"=>"17:35:55+03",
            "aikaleima"=>"2017-07-13 09:31:08+03",
            "kommentti"=>"Toinen testirivi",
            "merkkijonot"=>array(0=>"20", 1=>"0xF4", 2=>"010100"),
            "valittu"=>3,
            "muokattu"=>"2018-08-13 09:38:29+03",
            "muokkaaja"=>"Testaaja",
            "luotu"=>"2017-08-13 09:39:08+03",
            "luoja"=>"Luoja"
        );
        $this->assertEquals($odotus, $row);
    }
    
     /**
     * @test
     * @depends konstruktoriIlmanHakutaulua
     * */
    public function feilaakoRiviKolmosenHakuIdlla() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);        
        $d = array("id"=>3);
        $this->assertFalse($foo->exists($d));
    }
    
    /**
     * @test
     * @depends konstruktoriHakutaululla
     * @depends pvmTunnistuu
     * */
    public function loytyykoHeksakymppi() {
         $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
         $search = [];
         $search["value"]="0x0A";
         $res=$foo->tableFetch(0, 10, "id asc", $search);
         $this->assertCount(1, $res["rivi"]);
    }
    
    /**
     *@test
     *@depends konstruktoriHakutaululla
     *@depends pvmTunnistuu
     **/
    public function loytyykoKaksikymmenta() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $search=[];
        $search["value"]=20;
        $res=$foo->tableFetch(0, 10, "id asc", $search);
        $this->assertCount(1, $res["rivi"]);
    }
    
    /**
     *@test
     *@depends konstruktoriHakutaululla
     *@depends pvmTunnistuu
     **/
    public function loytyykoElokuuKolmetoistaSeitsenmantoista() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $search=[];
        $search["value"]="2017-08-13";
        $res=$foo->tableFetch(0, 10, "id asc", $search);
        $this->assertCount(1, $res["rivi"]);
    }
    
    /**
     * @test
     * @depends konstruktoriHakutaululla
     * @depends pvmTunnistuu
     **/
    public function loytyykoKloYhdeksanKolmenkymmentaViisiViisiViisi() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $search=[];
        $search["value"]="09:35:55";
        $res=$foo->tableFetch(0, 10, "id asc", $search);
        $this->assertCount(1, $res["rivi"]);
    }
}
?>