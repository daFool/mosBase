<?php
namespace testStubs;

class TestiTaulu extends \mosBase\Malli {
    
    public function __construct(\mosBase\Database $db, \mosBase\Log $log, bool $hakutaulut=false) {
        $taulu = "testi";
        $avaimet =  array("primary"=>array("id"));
        if (!$hakutaulut) {
            parent::__construct($db, $log, $taulu, $avaimet);
        } else {
            $hakutaulut = [];
            $hakutaulut[] = array("nimi"=>"intti", "tyyppi"=>\mosBase\Malli::INTTI);
            $hakutaulut[] = array("nimi"=>"merkkijono", "tyyppi"=>\mosBase\Malli::STRINGI);
            $hakutaulut[] = array("nimi"=>"aika", "tyyppi"=>\mosBase\Malli::DATE);
            $hakutaulut[] = array("nimi"=>"aikaleima", "tyyppi"=>\mosBase\Malli::DATE);
            $hakutaulut[] = array("nimi"=>"kommentti", "tyyppi"=>\mosBase\Malli::DATE);
            $hakutaulut[] = array("nimi"=>"merkkijonot", "tyyppi"=>\mosBase\Malli::STRINGA);
            $hakutaulu="testi";
            parent::__construct($db, $log, $taulu, $avaimet, $hakutaulu, $hakutaulut);
        }
    }
}
?>