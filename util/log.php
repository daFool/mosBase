<?php
/**
 * Tietokantaan logaaminen
 * */
namespace mosBase;

/**
 * Peruslogi
 *
 * Olettaa, että tietokannassa on taulu, joka on luotu sql/taulu_log.sql-skriptalla.
 * */

class log {

	/**
	 * @var int $level Nykyinen logaamisen taso
	 * */
	private $level;
    
	/**
	 * @var array $levels Logitasot taulukossa, indeksi on tason nimi ja arvo on logitaso.
	 * */
	private $levels;
    
	use util;
	
	/**
	 * @var object $db PDO-objekti tietokantaan
	 * */
	
	private $db;
	
	/**
	 * Konstruktori
	 * Asettaa käytössä olevan logaustason ja käytettävän kannan.
	 * @param string $level Haluttu logaustaso
	 * @param object $db PDO-kantaonbjekti
	 * */
    public function __construct($level, $db) {
        $this->levels = array("FATAL"=>0, "ERROR"=>1, "INFO"=>2, "AUDIT"=>3, "DEBUG"=>4);
        $this->level=$this->levels[$level]??4;
		$this->db=$db;
    }
    
      /**
     * Tietokantalogi
     *
     * Kirjoittaa tauluun Logi
     * @param string $kuka Kuka teki jotakin
     * @param string $viesti Vapaa logiviesti
     * @param string $tiedosto Tiedosto, joka generoi viestin
     * @param string $mika funktio/luokka, joka generoi viestin
     * @param int $rivi rivinumero, joka generoi viestin
     * @param string $taso minkä tason viestistä on kyse
     * @return void Kuolee mikäli logaus epäonnistuu
     * */
    
    public function log($kuka, $viesti, $tiedosto, $mika, $rivi,  $taso="DEBUG") {                        
        if(isset($this->levels[$taso]) && $this->levels[$taso]<=$this->level) {
			$d=array("kuka"=>$kuka, "viesti"=>$viesti, "tiedosto"=>$tiedosto,
					 "tarkenne"=>$mika, "rivi"=>$rivi,
						"luokka"=>$taso);
			$s1 = "insert into log (kuka, viesti, tiedosto, tarkenne, rivi, luokka";
			$s2 = " values (:kuka, :viesti, :tiedosto, :tarkenne, :rivi, :luokka";
			$res = $this->selainTiedot();
			if($res===True) {
				$s1.=", mista, selain";
				$s2.=", :mista, :selain";
				$d["mista"]=isset($res["ip"]) ? $res["ip"] : _("Tuntematon");
				$d["selain"]=isset($res["selain"]) ? $res["selain"] : _("Tuntematon");									   
			}
			$s = "$s1) $s2);";
			$st = $this->pdoPrepare($s, $this->db);
			$this->pdoExecute($st, $d);
		}
                
    }
}
?>