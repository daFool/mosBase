<?php

class configTest extends \PHPUnit_Framework_TestCase {
    
    const puuttuvaTiedosto='foobar.ini';
    const huonoTiedosto='/etc/passwd';
    const oikeaTiedosto='mosbase.ini';
    const tuntematonAlue='Tuhannen ja yhden yön satuja';
    const tuntamatonLuokka='PekkaTöpöhäntä';
    const tunnettuLuokka='mosBase\Config';
    const toinenTunnettuLuokka='mosBase\Malli';
    
    private $c;
    
    public function setUp() {
        $this->c = new mosBase\Config();    
    }
    /**
     * @test
     * */
    public function puuttuvaTiedostoFeilaa() {
        $this->expectException(Exception::class);
        $res = $this->c->init(configTest::puuttuvaTiedosto);
    }
    
    /**
     * @test
     * */
    public function viallinenTiedostoFeilaa() {
        $this->expectException(Exception::class);
        $this->c->init(configTest::huonoTiedosto);
    }
    
    /**
     * @test
     * */
    public function toimivaTiedostoLatautuu() {
        $this->assertEquals(true, $this->c->init(configTest::oikeaTiedosto));
    }
    
    /**
     * @test
     * @depends toimivaTiedostoLatautuu
     * */
    public function eiAluettaPalauttaaFalse() {
        $this->c->init(configTest::oikeaTiedosto);
        $this->assertEquals(false, $this->c->get(configTest::tuntematonAlue));
    }
    
    /**
     * @test
     * @depends toimivaTiedostoLatautuu
     * */
    public function GeneralPalauttaaTZn() {
        $want = array("TZ"=>"Europe/Helsinki");
        
        $this->c->init(configTest::oikeaTiedosto);        
        $this->assertEquals($want, $this->c->get("General"));
    }

    /**
     * @test
     * @depends toimivaTiedostoLatautuu
     * */
    public function classLoaderFeilaaTuntemattomallaLuokalla() {
        $this->c->init(configTest::oikeaTiedosto);        
        $this->assertEquals(false, $this->c->classLoader(configTest::tuntamatonLuokka));
    }
    
    /**
     * @test
     * @depends toimivaTiedostoLatautuu
     * */
    public function classLoaderLoytaaLuokat() {
        $this->c->init(configTest::oikeaTiedosto);
        $this->assertEquals(true, $this->c->classLoader(configTest::tunnettuLuokka));
        $this->assertEquals(true, $this->c->classLoader(configTest::toinenTunnettuLuokka));
    }
}
?>