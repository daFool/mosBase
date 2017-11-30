<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright Copyright (c) 2017 Mauri Sahlberg, Helsinki
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
/**
 * Tietokantaan logaaminen
 * */
namespace mosBase;

/**
 * Peruslogi
 *
 * Olettaa, että tietokannassa on taulu, joka on luotu sql/taulu_log.sql-skriptalla.
 * Taulussa on "chain", jolla voidaan tunnistaa mihinkä kokonaisuuteen logiviesti kuuluu. Tämän
 * taulun sekvensseistä on kaksi varianttia, normivariantti ja BDR-klusterivariantti.
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
    
	/**
	 * @var int $sequence Mihin logisekvenssiin tämä logirivi kuuluu
	 * */
	private $sequence;
	
	/**
	 * @var string(255) $marker Mihin logikokonaisuuteen tämä logorivi kuuluu
	 * */
	private $marker;
	
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
    public function __construct(string $level, database $db) {
        $this->levels = array("FATAL"=>0, "ERROR"=>1, "INFO"=>2, "AUDIT"=>3, "DEBUG"=>4, "DEBUGMB"=>5);
        $this->level=$this->levels[$level]??4;
		$this->db=$db;
		$this->sequence=False;
		$this->marker=False;
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
    
    public function log(string $kuka, string $viesti, string $tiedosto, string $mika, int $rivi, string $taso="AUDIT") {                        
        if(isset($this->levels[$taso]) && $this->levels[$taso]<=$this->level) {
			$d=array("kuka"=>$kuka, "viesti"=>$viesti, "tiedosto"=>$tiedosto,
					 "tarkenne"=>$mika, "rivi"=>$rivi,
						"luokka"=>$taso);
			$s1 = "insert into log (kuka, viesti, tiedosto, tarkenne, rivi, luokka";
			$s2 = " values (:kuka, :viesti, :tiedosto, :tarkenne, :rivi, :luokka";
			if($this->marker!=False) {
				$s1.=", marker";
				$s2.=", :marker";
				$d["marker"]=$this->marker;
			}
			if($this->sequence!=False) {
				$s1.=", chain";
				$s2.=", :chain";
				$d["chain"]=$this->sequence;
			}
			$res = $this->selainTiedot();
			if($res["tulos"]===True) {
				$s1.=", mista, selain";
				$s2.=", :mista, :selain";
				$d["mista"]=isset($res["ip"]) ? $res["ip"] : _("Tuntematon");
				$d["selain"]=isset($res["selain"]) ? $res["selain"] : _("Tuntematon");									   
			}
			if($this->db->getDatabase()=='pgsql')
				$s = "$s1) $s2) returning chain;";
			else
				$s = "$s1) $s2);";
			$st = $this->pdoPrepare($s, $this->db);
			$this->pdoExecute($st, $d);
			$r = $st->fetch(\PDO::FETCH_ASSOC);
			$this->sequence=$r["chain"]??False;
		}                
    }
	
	/**
	 * Asettaa logimarkkerin
	 * @param string(255) $m Asetettava markkeri
	 **/
	public function setMarker($m=False) : void {
		$this->marker=$m;
	}
}
?>
