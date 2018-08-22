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

class Log
{

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
    
    use Util;
    
    /**
     * @var object $db PDO-objekti tietokantaan
     * */
    
    private $db;
    
    public const FATAL = 'FATAL';
    public const ERROR = 'ERROR';
    public const INFO = 'INFO';
    public const AUDIT = 'AUDIT';
    public const DEBUG = 'DEBUG';
    public const DEBUGMB = 'DEBUGMB';
    public const MOSBASE = 'mosBase';
    
    private const SELAIN = 'selain';
    /**
     * Konstruktori
     * Asettaa käytössä olevan logaustason ja käytettävän kannan.
     *
     * @param string $level Haluttu logaustaso
     * @param object $db    PDO-kantaonbjekti
     * */
    public function __construct(string $level, database $db)
    {
        $this->levels = array(
                        log::FATAL=>0,
                        log::ERROR=>1,
                        log::INFO=>2,
                        log::AUDIT=>3,
                        log::DEBUG=>4,
                        log::DEBUGMB=>5
        );
        $this->level=$this->levels[$level]??4;
        $this->db=$db;
        $this->sequence=false;
        $this->marker=false;
    }
    
    /* Rakentaa log-insert-lauseen
    * @param array& logattava data
    * @return string insert-lause
    * */
    private function buildInsert(array &$d)
    {
        $s1 = "insert into log (kuka, viesti, tiedosto, tarkenne, rivi, luokka";
        $s2 = " values (:kuka, :viesti, :tiedosto, :tarkenne, :rivi, :luokka";
        if ($this->marker!==false) {
            $s1.=", marker";
            $s2.=", :marker";
            $d["marker"]=$this->marker;
        }
        if ($this->sequence!==false) {
            $s1.=", chain";
            $s2.=", :chain";
            $d["chain"]=$this->sequence;
        }
        $res = $this->selainTiedot();
        if ($res["tulos"]===true) {
            $s1.=", mista, selain";
            $s2.=", :mista, :selain";
            $d["mista"]=isset($res["ip"]) ? $res["ip"] : _("Tuntematon");
            $d[log::SELAIN]=isset($res[log::SELAIN]) ? $res[log::SELAIN] : _("Tuntematon");
        }
        if ($this->db->getDatabase()==malli::PGSQL) {
            $s = "$s1) $s2) returning chain;";
        } else {
            $s = "$s1) $s2);";
        }
        return $s;
    }
      /**
       * Tietokantalogi
       *
       * Kirjoittaa tauluun Logi
       *
       * @param  string $kuka     Kuka teki jotakin
       * @param  string $viesti   Vapaa logiviesti
       * @param  string $tiedosto Tiedosto, joka generoi viestin
       * @param  string $mika     funktio/luokka, joka generoi viestin
       * @param  int    $rivi     rivinumero, joka generoi viestin
       * @param  string $taso     minkä tason viestistä on
       *                          kyse
       * @return void Kuolee mikäli logaus epäonnistuu
       * */
    
    public function l(
        string $kuka,
        string $viesti,
        string $tiedosto,
        string $mika,
        int $rivi,
        string $taso = log::AUDIT
    ) {
        if (isset($this->levels[$taso]) && $this->levels[$taso]<=$this->level) {
            $d=array("kuka"=>$kuka, "viesti"=>$viesti, "tiedosto"=>$tiedosto,
            "tarkenne"=>$mika, "rivi"=>$rivi,
            "luokka"=>$taso);
            $s=$this->buildInsert($d);
            $st = $this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st, $d);
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            $this->sequence=$r["chain"]??false;
        }
    }
    
    /**
     * Asettaa logimarkkerin
     *
     * @param string(255) $m Asetettava markkeri
     **/
    public function setMarker($m = false) : void
    {
        $this->marker=$m;
    }
    
    /**
     * Palauttaa logitason
     *
     * @return int logaustaso
     * */
    public function getLogLevel() : int
    {
        return $this->level;
    }
    
    /**
     * Palauttaa sekvenssin
     *
     * @return int sekvenssi
     * */
    public function getSequence() : int
    {
        return $this->sequence;
    }
    
    /**
     * Palauttaa markkerin
     *
     * @return string markkeri
     * */
    public function getMarker() : string
    {
        return $this->marker;
    }
}
