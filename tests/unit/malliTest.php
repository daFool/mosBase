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
    
    /**
     *@test
     *@depends konstruktoriHakutaululla
     **/
    public function loytyykoKakkosrivinAikaleimalla() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $search=[];
        $search["value"]="2017-07-13 09:31:08";
        $res=$foo->tableFetch(0, 10, "id asc", $search);
        $this->assertCount(1, $res["rivi"]); 
    }
    
    /**
     *@test
     *@depends konstruktoriHakutaululla
     **/
    public function loytyykoTestirivit() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $search=[];
        $search["value"]="testirivi";
        $res=$foo->tableFetch(0, 10, "id asc", $search);
        $this->assertCount(2, $res["rivi"]); 
    }
    
    /**
     *@test
     *@depends konstruktoriHakutaululla
     **/
    public function osumatonHaku() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $search=[];
        $search["value"]="gargamel";
        $res=$foo->tableFetch(0, 10, "id asc", $search);
        $this->assertCount(0, $res["rivi"]);  
    }
    
    /**
     *@test
     *@depends konstruktoriHakutaululla
     **/
    public function uusiRivi() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $rivi=array("id"=>3,
                    "intti"=>30,
                    "merkkijono"=>"Kolkyt",
                    "pvm"=>"2019-03-03",
                    "aika"=>"23:59:59+03",
                    "aikaleima"=>"2020-01-02 12:12:12+03:00",
                    "kommentti"=>"Kolmas testirivi",
                    "merkkijonot"=>"{'30','0x1D','011110'}",
                    "valittu"=>1,
                    "luoja"=>"Ruoja");
        $this->assertTrue($foo->upsert($rivi));
        $this->assertTrue($foo->has());
        $res = $foo->give();
        foreach($rivi as $i=>$v) {
            switch($i) {
                case "aikaleima":
                    break;
                default:
                    $this->assertEquals($v, $res[$i]);
                    break;
            }
        }
        $this->assertArrayHasKey('id', $res);
    }

    /**
     *@test
     *@depends konstruktoriHakutaululla
     **/   
    public function paivitaRivi() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $rivi=array("id"=>2,
                    "intti"=>30,
                    "merkkijono"=>"Kolkyt",
                    "pvm"=>"2019-03-03",
                    "aika"=>"23:59:59+03",
                    "aikaleima"=>"2020-01-02 12:12:12+03:00",
                    "kommentti"=>"Kolmas testirivi",
                    "merkkijonot"=>"{'30','0x1D','011110'}",
                    "valittu"=>1,
                    "muokkaaja"=>"Ruoja");
        $this->assertTrue($foo->upsert($rivi));
        $this->assertTrue($foo->has());
        $this->assertTrue($foo->exists(array("id"=>2)));
        $res = $foo->give();
        foreach($rivi as $i=>$v) {
            switch($i) {
                case "aikaleima":
                    break;
                case "merkkijonot":
                    break;                
                default:
                    $this->assertEquals($v, $res[$i]);
                    break;
            }
        }        
    }
    
    /**
     *@test
     *@depends uusiRivi
     **/
    public function poistaRivi()
    {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $d = array("id"=>3);
        $this->assertTrue($foo->delete($d));
        $this->assertFalse($foo->exists($d));
    }
    
     /**
     *@test
     *@depends konstruktoriHakutaululla
     *@depends poistaRivi
     **/
    public function regexpHaut() {
        $foo = new \testStubs\TestiTaulu(self::$db, self::$log, true);
        $rivi=array("id"=>3,
                    "intti"=>30,
                    "merkkijono"=>"Kolkyt",
                    "pvm"=>"2019-03-03",
                    "aika"=>"23:59:59+03",
                    "aikaleima"=>"2020-01-02 12:12:12+03:00",
                    "kommentti"=>"Kolmas testirivi",
                    "merkkijonot"=>"{'30','0x1D','011110','0xF4'}",
                    "valittu"=>1,
                    "luoja"=>"Ruoja");
        $this->assertTrue($foo->upsert($rivi));
        $this->assertTrue($foo->has());
        $res = $foo->give();
        foreach($rivi as $i=>$v) {
            switch($i) {
                case "aikaleima":
                    break;
                default:
                    $this->assertEquals($v, $res[$i]);
                    break;
            }
        }
        $this->assertArrayHasKey('id', $res);
        $kentat = [ "id", "intti", "merkkijono", "pvm", "aika", "aikaleima", "kommentti", "merkkijonot", "valittu", "muokattu",
                   "muokkaaja", "luotu", "luoja" ];
         $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, "17:35:55+03", "");
        $this->assertCount(1, $rivit);
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, "0xF4", "");
        $this->assertCount(2, $rivit);
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, ".*ky.*","where id=2");
        $this->assertCount(1, $rivit);
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, ".*ky.*","");
        $this->assertCount(3, $rivit);
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, "20", "");
        $this->assertCount(1, $rivit);       
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, "2020-01-02 11:12:12+02", "");
        $this->assertCount(1, $rivit);
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, "2018-08-13", "");
        $this->assertCount(1, $rivit);
        $rivit = $foo->findWithRegex(self::$db, "testi", $kentat, "Gargamel", "");
        $this->assertCount(0, $rivit);        
    }

}
?>