<?php

class malliTest extends \PHPUnit_Framework_TestCase {
    
    public function testCreateInstance() {
        $conf = new mosBase\config();
        $conf->init(getenv("mosBaseIni"));
        $dbconf = $conf->get("Database");
    	$pdo = new mosBase\database($dbconf["dsn"], $dbconf["user"], $dbconf["password"]);
    	$log = new mosBase\log("AUDIT", $pdo);
		$log->setMarker("testCreateInstance");
    	$log->log("system","startup",__FILE__,__FUNCTION__,__LINE__, "AUDIT");
        $keys = array("primary"=>array("id"), "foo"=>array("intti", "merkkijono"));
        $taulu = "testi";
        $malli = new mosBase\malli($pdo, $log, $taulu, $keys);
        $this->assertInstanceOf(mosBase\malli::class, $malli);
		$log->log("system", "end",__FILE__, __FUNCTION__,__LINE__, "AUDIT");
        $log->setMarker();		
		return $malli;
    }
    
    /**
	 * @depends testCreateInstance
	 * */
	public function testUpsert($malli) {		
		$data = array("luoja"=>"mauri.sahlberg@accountor.fi",
					  "intti"=>5,
					  "merkkijono"=>"viisi",
					  "pvm"=>"1969-07-10",
					  "aika"=>"12:12:12+03",
					  "aikaleima"=>"2017-05-04 12:12:12+03",
					  "kommentti"=>"testi");
		$tulos = $malli->upsert($data);
		$this->assertEquals(true, $tulos);
		$data["muokkaaja"]="matti.muokkaaja@pingpong.com";
		$tulos = $malli->upsert($data);
		$this->assertEquals(true, $tulos);
		$tulos = $malli->has();
		$this->assertEquals(true, $tulos);
		$d = $malli->give();
		$this->assertArrayHasKey("id", $d);
		$this->assertArrayHasKey("luotu",$d);
		$this->assertArrayHasKey("muokattu",$d);
		unset($d["id"]);
		unset($d["muokattu"]);
		unset($d["luotu"]);
		$this->assertEquals($data, $d);
		return $malli;
	}
	
    /**
	 * @depends testUpsert
	 * */
	public function testDelete($malli) {
		$data = array();
		$res = $malli->delete($data);
		$this->assertEquals(false, $res);
		$data = array("id"=>666);
		$res = $malli->delete($data);
		$this->assertEquals(true, $res);
		$data = array("intti"=>5, "merkkijono"=>"viisi");
		$res = $malli->delete($data);
		$this->assertEquals(true, $res);
		$data = array("intti"=>5, "stringi"=>"viisi");		
		$this->expectException(Exception::class);
		$res = $malli->delete($data);
		$this->assertEquals(false, $res);
		return $malli;
	}
    
	public function testFetchTable() {
		$conf = new mosBase\config();
        $conf->init(getenv("mosBaseIni"));
        $dbconf = $conf->get("Database");
    	$pdo = new mosBase\database($dbconf["dsn"], $dbconf["user"], $dbconf["password"]);
    	$log = new mosBase\log("AUDIT", $pdo);
    	$log->log("system","startup",__FILE__,__FUNCTION__,__LINE__, "AUDIT");
        $keys = array("primary"=>array("id"), "foo"=>array("intti", "merkkijono"));
        $taulu = "testi";
        $hakutaulu = "testi";
		$hakukentat = array(array("nimi"=>"intti", "tyyppi"=>"int"), array("nimi"=>"merkkijono", "tyyppi"=>"string"), array("nimi"=>"aikaleima", "tyyppi"=>"date"));
		$malli = new mosBase\malli($pdo, $log, $taulu, $keys, $hakutaulu, $hakukentat);
        $this->assertInstanceOf(mosBase\malli::class, $malli);
		$vertailu=array();
		for($i=0;$i<50;$i++) {
			$pvm = new DateTime();
			$pvm = $pvm->add(new DateInterval("P${i}D"));
			$d = array("merkkijono"=>sprintf("Merkkijono %d", $i), "intti"=>$i, "aikaleima"=>$pvm->format("Y-m-d H:i:s"));
			$malli->upsert($d);
			$vertailu[$i]=$d;
		}
		$tulos=$malli->tableFetch(0, 50, "merkkijono asc", false);
		$this->assertEquals(50, $tulos["riveja"]);
		$tulos=$malli->tableFetch(0,20, "merkkijono asc", array("value"=>5));
		$this->assertEquals(5, $tulos["riveja"]);
		$tulos=$malli->tableFetch(22,50, false, false);
		$this->assertEquals(28, $tulos["riveja"]);
    	$tulos=$malli->tableFetch(0,20, "merkkijono asc", array("value"=>"Merkkijono 3%"));
		$this->assertEquals(11, $tulos["riveja"]);
	    return $malli;
	}
}

?>
