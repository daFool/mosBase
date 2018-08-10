<?php

class databaseTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * @test
     **/
    
    public function DriveriOnPgsql() {
        $c = new mosBase\Config();
        $this->assertTrue($c->init("mosbase.ini"));
        $d=$c->get("Database");
        $db = new mosBase\Database($d["dsn"], $d["user"], $d["password"]);        
        $this->assertEquals("pgsql", $db->getDatabase());        
    }
}
?>