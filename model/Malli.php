<?php
/**
 * Tietokantarajapinta
 *
 * @category    Model
 * @package     mosBase
 * @author      Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright   2018 Mauri Sahlberg, Helsinki
 * @license     MIT https://opensource.org/licenses/MIT
 */
namespace mosBase;

/**
 * "Abstrakti"-luokka tietokantataulujen käsittelemiseen.
 *
 * Käytännössä voidaan potkaista pystyyn itsenäänkin, mikäli taulussa ei ole mitään omaa logiikkaa. Tarkoitettu
 * kuitenkin käytettäväksi abstraktin luokan tapaan.
 * 
 * Oletetaan, että kussakin taulussa voi olla yhdestä tai useammasta sarakkeesta koostuva avain,
 * sekä yhdestä tai useammasta sarakkeesta koostuvia yksikäsitteisyysehtoja.
 * Lisäksi oletetaan, että kustakin taulusta löytyy neljä kenttää: muokkaaja, muokattu, luotu ja luoja.
 * Muokkaaja ja luoja ovat merkkijonoja, jotka tunnistavat riviä muokanneen ja rivin luoneen tahon.
 * Muokattu ja luotu ovat aikaleimoja, jotka kertovat muutoshetken ja luontihetken.
 */

class Malli 
{
    /**
     * @var array $data Rivipuskuri, sisältää viimeiseksi luetun rivin sarakkeet.
     * */
    protected $data;
    /**
     * @var boolean $empty Onko rivipuskurissa rivi vai ei?
     * */
    protected $empty; 
    /**
     * @var string $taulu Tietokantataulun nimi.
     * */
    protected $taulu;
    /**
     * @var array $avaimet Taulukko kenttäjoukkoja, joista kukin joukko kuvaa joko pääavaimen tauluun tai mun uniikin
     * kombinaation sarakkeita.
     * */
    protected $avaimet;
    /**
     * @var string $hakutaulu Hakutaulu tai -näkymä, jota käytetään tietojen esittämiseen alla olevan tietokantataulun sijaan.
     * */
    protected $hakutaulu; 
    /**
     * @var array $hakukentat Hakutaulun sarakkeet, joista etsittävää arvoa haetaan
     * */
    protected $hakukentat; /** @var array $hakukentat Hakukentät **/
    
    /**
     * @var database $db PDO-objekti tietokannasta
     * */
    protected $db;
    
    /**
     * @var log $log mosBase\log objekti tietokantalogaukseen
     * */
    protected $log;
    
    /**
     * Käytetään taulun sarakkeiden ominaisuuksien etsimiseen
     * */
    use Pgsql;
    
    protected const LUOJA='luoja';
    protected const MUOKKAAJA='muokkaaja';
    protected const MUOKATTU='muokattu';
    protected const LUOTU='luotu';
    public const PGSQL='pgsql';
    
    private const LKM="lkm";
    private const FILTERED="filtered";
    private const VALUE="value";
    private const SQLPATTERN='/.*[%_].*/';
    private const REXPATTERN="/.*[.*?+].*/";
    private const NIMI='nimi';
    private const TYYPPI="tyyppi";
    private const RIVIT='rivi';
    private const RIVEJA='riveja';
    private const NULLPATTERN="/\w*NULL\w*/i";
    public const STRINGI="string";
    public const STRINGA="stringA";
    public const INTTI="int";
    public const DATE="date";
    
    /**
     * Konstruktori
     *
     * Jos hakutaulua ei ole määritelty, käytetään itse taulua. Jos hakukenttiä ei ole määritelty, käyttää avaimien
     * kenttiä hakukenttinä, joiden tyypiksi asettaa kaikille "string".
     * 
     * @param database $db Tietokannan pdo-objekti
     * @param log $log Tietokantalogi
     * @param string $taulu Taulun nimi kannassa
     * @param array $avaimet Mitkä ovat taulun uniikkeja sarakkeita, yksin tai yhdessä. $avaimet[nimi]=array(kentta, kentta)
     * @param string $hakutaulu Mistä taulusta haetaan
     * @param string $hakukentat Millä kentillä haetaan
     * @uses Malli::clear()
     * */
    public function __construct(database $db, log $log, string $taulu, array $avaimet, string $hakutaulu="",
                                array $hakukentat=array()) {            
        $this->db = $db;
        $this->log = $log;
        $this->clear();
        $this->taulu=$taulu;
        $this->avaimet=$avaimet;
        if ($hakutaulu!="") {
            $this->hakutaulu=$hakutaulu;
        }
        else {
            $this->hakutaulu=$taulu;
        }
        if (count($hakukentat)) {
            $this->hakukentat=$hakukentat;
        } else {
            $this->hakukentat=array();
            $i=0;
            foreach ($avaimet as $avain) {
                foreach ($avain as $kentta) {
                    if (!array_search($kentta, $this->hakukentat)) {
                        $this->hakukentat[$i++]=array(malli::NIMI=>$kentta, malli::TYYPPI=>"string");
                    }
                }
            }
        }
    }
    
    
    /**
     * Tyhjätään cache
     * */
    protected function clear() : void {
        $this->data = array();
        $this->empty=true;
    }
    
    /**
     * Tutkii löytyvätkö kaikki avaimen kentät hakuehdosto,
     * jos löytyvät rakentaa where-lauseen
     * @param array $avain Yksittäinen avain
     * @param array $data Hakuehto
     * @return array (tulos, where-ehto, array(hakudata))
     * */
    private function checkKey(array $avain, array $data) :array  {
        $d=array();
        $w="";
        $all=true;
        foreach ($avain as $sarake) {
            if (isset($data[$sarake]) && $data[$sarake]!=="" &&
               !preg_match(malli::NULLPATTERN, $data[$sarake])) {
                $d[$sarake]=$data[$sarake];
                if ($w=="") {
                    $w="where {$sarake}=:{$sarake}";
                } else {
                    $w.=" and {$sarake}=:{$sarake}";
                }
                continue;
            }
            $all=false;
            break;
        }
        if ($all) {
            return array(true, $w, $d);            
        }
        return array(false, "", array());
    }
    
    /**
     * Avaimen purkaminen sarake-datasta
     * @param array $data Sarake-data, mistä avainta etsitään,
     * @param int $monesko, monennestako avaimesta alkaen etsitään
     * @return mixed Boolean=false, mikäli ei löytynyt avainta ja array,
     * jossa on where-ehto, positio ja avainsarakkeet arvoineen
     * */
    protected function getKey(array $data, int $monesko=-1) {
        $i=0;
        
        /* Kukin yksittäinen avain on joukko sarakkeita,esim
         * $this->avaimet["primary"]=array(nimi, maa)
        */
        foreach ($this->avaimet as $avain=>$sarakkeet) {
            $i++;
            if ($monesko!=-1 && $i<$monesko) {
                continue;
            }
            list ($all, $w, $d)=$this->checkKey($sarakkeet,$data);
            if ($all) {
                return array("avain"=>$avain, "i"=>$i, "d"=>$d, "w"=>$w);                
            }
        }
        return False;        
    }
    
    /**
     * Etsii taulusta avaimilla
     * @param array $data Hakuehdot. Yksi hakuehto koostuu $ehto[nimi]=array(arvo, arvo)
     * @return boolean true, jos olemassa, false jos ei ole olemassa
     * @uses mosBase\malli::getKey()
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\util::pdoExecute()
     * @uses mosBase\log::log()
     * */
    public function exists(array $data) : bool {        
        $this->clear();
        $j=1;
        $r = $this->getKey($data, $j);
        
        while ($r!==false) {
            $w = $r["w"];
            $j = $r["i"];
            $d = $r["d"];
            $s = "select * from {$this->taulu} $w;";                           
            $st = $this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st, $d);
            $ds = serialize($data);
            $m=sprintf(_("Testaus %s ({%s})"), $s, $ds);
            $this->log->log("system", $m, __FILE__, __METHOD__, __LINE__, log::DEBUGMB);
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows)>1) {
                return false;
            }
            if (count($rows)==0) {
                $r = $this->getKey($data, $j+1);
                continue;
            }
            $re=$this->hasArrayColumns($st);
            if ($re!==false) {
                $s = "select ".$re[0]." from {$this->taulu} $w;";
                $st = $this->pdoPrepare($s, $this->db);
                $this->pdoExecute($st, $d);
                $row = $st->fetch(\PDO::FETCH_ASSOC);
                $this->data = $this->unpack($row, $re[1]);
            } else {
                $this->data = $rows[0];
            }
            $this->empty = false;
            return true;
        }            
        return false;                    
    }
    
    /**
     * Tutkii kuuluuko sarake avaimiin
     * @param string $column Sarakkeen nimi
     * @return boolean
     * */
    protected function isKeyColumn(string $column) : bool {
        foreach ($this->avaimet as $sarakkeet) {
            foreach ($sarakkeet as $sarake) {
                if ($column==$sarake) {
                    return True;
                }
            }
        }
        return False;
    }
    
    /**
     * Rakennetaan päivityslause ja kootaan data
     * @param array $data Data, jolla päivitetään
     * @return array (sql-lause, array(sarake=>arvo)
     * */
    private function update(array $data) : array {        
        $r = $this->getKey($data);
        $s = "update {$this->taulu} set muokattu=now()";
        if (!isset($data[malli::MUOKKAAJA]) && isset($data[malli::LUOJA])) {
            $data[malli::MUOKKAAJA]=$data[malli::LUOJA];
        }
        $d = array();
        foreach ($data as $key=>$value) {
            /* Avainten arvoja ei päitivitetä, eikä luojatietoja. Muokattu menee automaattisesti */
            if ($key==malli::MUOKATTU || $key==malli::LUOJA || $key==malli::LUOTU || $this->isKeyColumn($key)) {
                continue;
            }
            $s.=", $key=:$key";
            $d[$key]=$value;
        }
        $s.=" {$r["w"]}";
        $d = array_merge($d, $r["d"]);
        return array($s, $d);
    }
    
    /**
     * Rakennetaan lisäyslaus ja kootaan data
     * @param array $data Rivi, joka lisätään kantaan
     * @return array (sql-lause, array(sarake=>arvo))
     * */
    private function insert(array $data) : array {
        $s1="insert into {$this->taulu} (luotu ";
        $s2=" values (now()";
        foreach ($data as $key=>$value) {
            if ($key==malli::LUOTU || $key==malli::MUOKKAAJA || $key==malli::MUOKATTU) {
                continue;
            }
            $s1.=", $key";
            $s2.=", :$key";
            $d[$key]=$data[$key];
        }
        if ($this->db->getDatabase()=='pgsql') {
            $s = $s1.")".$s2.") returning *;";
        }
        else {
            $s = $s1.")".$s2.");";
        }
        return array($s, $d);
    }
    /**
     * Lisätään tai päivitetään Sovellus
     * @param array $data allokointi
     * @return mixed false jos epäonnistui true, jos onnistui
     * @uses mosBase\malli::getKey()
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\::pdoExecute()
     * @uses mosBase\log::log()
     * */
    public function upsert(array $data) {
        $insert=false;
        if ($this->exists($data)) {
            list($s, $d)=$this->update($data);
        } else {
            $insert=true;
            list($s, $d)=$this->insert($data);
        }
            
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st,$d);
        $m = sprintf(_("%s (%s)"), $s, serialize($d));
        $this->log->log(log::MOSBASE, $m, __FILE__,__METHOD__,__LINE__, log::DEBUGMB);
        $this->log->log(log::MOSBASE, _("Onnistui"), __FILE__,__METHOD__,__LINE__, log::DEBUGMB);
        
        if ($insert && $this->db->getDatabase()==malli::PGSQL) {
            $r = $st->fetch(\PDO::FETCH_ASSOC);            
            $this->data=$r;
            $this->empty=false;
            return true;
        }
        $tulos = $this->exists($data);
        if ($tulos!==true) {
            $this->log->log(log::MOSBASE, _("WTF? käsiteltyä riviä ei ole!"),__FILE__,__METHOD__,
                    __LINE__, log::ERROR);
        }
        return $tulos;
    }
    
    /**
     * Löytyykö puskurista?
     * @return boolean true jos jotakin löytyy
     * */
    public function has() :bool {
        return !$this->empty;
    }
    
    /**
     * Data puskurista
     * @return array Puskurin sisältö
     * */
    public function give() : array {
        return $this->data;
    }
    
    /** Hakukentän tyyppi string
     * @param $v string Haettava arvo
     * @return array(arvoi oikein "lainattuna", vertailuoperaattori)
     * */
    private function kasitteleStringi(string $v) {
       $dtype = $this->db->getDatabase();
     
        if ($dtype!=malli::PGSQL) {
            $op = "like";
            if (!preg_match(malli::SQLPATTERN, $v)) {
                $v="%".$v."%";
            }
        } else {
            $op = "ilike";
            if (preg_match(malli::REXPATTERN, $v)) {
                $op = "~*";
            } elseif (!preg_match(malli::SQLPATTERN,$v)) {                                
                $v="%".$v."%";
            }    
        }
        return array($v, $op);
    }
    
    /** Hakukentän tyyppi stringgitaulu
     * @param $v string haettava arvo
     * @return array(arvoi oikein "lainattuna", vertailuoperaattori)
     * */
    private function kasitteleStringitaulu($v) {
        $op = "ilike";
        if (preg_match(malli::REXPATTERN, $v)) {
            $op = "~*";
        } elseif (!preg_match(malli::SQLPATTERN,$v)) {                                
            $v="%".$v."%";
        }
        return array($v, $op);            
    }
    /**
     * Datatablen haun käsittely
     * Käydään lävitse kaikki hakutaulun hakukentät ja kokeillaan sopisiko datatablen
     * hakukenttää soviteltava arvo niihin. Rakennetaan sopiva where-ehto selectiä varten.
     * @param string $so where-ehdon "pohjat"
     * @param string $v arvo, jota kentistä etsitään
     * @return string where-ehto
     * */
    
    private function kasitteleHakukentat(string $so, string $v) {        
        $dtype = $this->db->getDatabase();
        $fmt="";
        foreach ($this->hakukentat as $kentta) {
            switch ($kentta[malli::TYYPPI]) {
                case malli::STRINGI:
                    list($v,$op)=$this->kasitteleStringi($v);
                    $so.=sprintf("%s%s %s %s", $fmt, $kentta[malli::NIMI], $op, $this->db->quote($v, \PDO::PARAM_STR));   
                    $fmt=" or ";          
                    break;
                case malli::STRINGA:
                    if ($dtype!=malli::PGSQL) {
                        continue;
                    }
                    list($v,$op) = $this->kasitteleStringitaulu($v);
                    $so.=sprintf("%s%s %s ANY (%s)", $fmt, $this->db->quote($v, \PDO::PARAM_STR), $op, $kentta[malli::NIMI]);
                    $fmt=" or ";
                    break;
                case malli::INTTI:
                    if (is_integer($v)) {
                        $so.=sprintf("%s%s = %s", $fmt, $kentta[malli::NIMI], $this->db->quote($v, \PDO::PARAM_INT));
                        $fmt=" or ";          
                    }
                    break;
                case malli::DATE:
                    $pvm = date_create($v);
                    if ($pvm !== False) {
                        $so.=sprintf("%s%s = %s", $fmt, $kentta[malli::NIMI], $this->db->quote($v, \PDO::PARAM_STR));
                        $fmt=" or ";
                    }
                    break;
                default:
                    throw new Exception(sprintf(_("Ohjelmointivirhe, tuntematon tyyppi:%s"),$kentta[malli::TYYPPI]));
            }                            
            $so.=") ";
        }
        return $so;
    }
    /**
     * Tauluhaku Datatablesia silmällä pitäen
     * @param int $start Mistä rivistä aloitetaan
     * @param int $length Montako riviä haetaan
     * @param string $order Hakukenttä ja suunta
     * @param string $search Haettava merkkijono
     * @param string $where Lisähakuehto
     * @return mixed False jos mitään ei löytynyt tai taulu tuloksia
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\util::pdoExecute()
     * @uses mosBase\log::log()
     * 
     * */
    public function tableFetch(int $start, int $length, string $order, array $search, $where=False) {        
        $d=array();
        $kuka = isset($_SESSION["user"]) ??"anonymous";
        $ds = false;
        $d = array();
       
        $tulos = array(malli::LKM=>0, malli::RIVIT=>array(), malli::RIVEJA=>0, malli::FILTERED=>0);

        if ($where!==False) {           
            $s = sprintf("select count(*) as lkm from %s where %s",$this->hakutaulu, $where);
        }
        else {
            $s = "select count(*) as lkm from ".$this->hakutaulu;
        }
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st, $d);
        if($st->rowCount()==0) {
            return $tulos;
        }
            
        $rivi = $st->fetch(\PDO::FETCH_ASSOC);
        $tulos[malli::LKM]=$rivi[malli::LKM];
        $tulos[malli::FILTERED]=$rivi[malli::LKM];
            
        $o="";
        $v="";
        $so="";
        if(isset($search[malli::VALUE]) && $search[malli::VALUE]!="") {
            $v=$search[malli::VALUE];
            $so=" where (";
            $so=$this->kasitteleHakukentat($so, $v);
        }
        
        if($where !== false) {
            $ds=true;
            if($so!="") {
                $so.=" and $where";
            } else {
                $so.=" where $where";
            }
        }

        $s1= "select * from ".$this->hakutaulu;
        $s2 = " limit $length offset $start;";
        $o="";
        if ($order!==false) {
            $o = " order by $order ";                
        }
        $s = "$s1$so$o$s2";
        $m = "$s";
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st,$d);
        if ($st->rowCount()==0) {
            $this->log->log($kuka, $m, __FILE__,__METHOD__,__LINE__,log::ERROR);
            return $tulos;
        }
        $this->log->log($kuka, $m, __FILE__,__METHOD__,__LINE__,log::DEBUGMB);
        
        $rivit = $st->fetchAll(\PDO::FETCH_ASSOC);
                              
        $tulos[malli::RIVIT]=$rivit;
        $tulos[malli::RIVEJA]=count($rivit);
            
        if($ds) {
            $s1 = "select count(*) as lkm from ".$this->hakutaulu;
            $s = "$s1$so";
            $st=$this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st,$d);
            if($st->rowCount()==0) {
                $m="$s";
                $this->log->log($kuka, $m, __FILE__,__METHOD__,__LINE__,log::ERROR);
            } else {            
                $rivi = $st->fetch(\PDO::FETCH_ASSOC);
                $tulos[malli::FILTERED]=$rivi[malli::LKM];
            }
        }    
        return $tulos;
    }
    /**
     * Kaikki taulun rivit
     * @return Boolean|Array Kaikki taulun rivit tai false
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\util::pdoExecute()
     * */
    public function all() {
        $s = "select * from $this->taulu;";
        $st=$this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st);
        if ($st->rowCount()==0) {
            return false;
        }
        return $st->fetchAll(\PDO::FETCH_ASSOC);        
    }
    
    /**
     * Rivin poistaminen
     * @param array $mika Avaimet arvoineen
     * @return boolean Onnistuiko poistaminen?
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\util::pdoExecute()
     * @uses mosBase\malli::getKey()
     * @uses mosBase\log::log()
     * */
    function delete(array $mika) :bool {                
        $s = "delete from {$this->taulu} ";
        $r = $this->getKey($mika);
        if ($r!==false) {
            $s.=$r["w"];
            $st = $this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st, $r["d"]);
            return true;
        }
        $s.="where ";
        $eka=true;
        $d = array();
        foreach ($mika as $avain=>$arvo) {
            if (!$eka) {
                $s.=" and ";
            } 
            $eka=false;
            $s.="$avain = :$avain";
            $d[$avain]=$arvo;            
        }
        if ($eka===true) {
            $this->log->log(log::MOSBASE, _("En poista kaikkia rivejä!"), __FILE__, __METHOD__, __LINE__, log::FATAL);
            return false;
        }
        $this->log->log((isset($d[malli::MUOKKAAJA])??log::MOSBASE), $s.serialize($d),__FILE__,__METHOD__,__LINE__,log::DEBUGMB);
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st, $d);
        return true;        
    }
    
    /**
     * Viimeksi muokattu
     * Hakee tuoreimman ehdon täyttävän muutoksen sisältävän rivin
     * @param string $where hakuehto
     * @return array muokattu, muokkaaja
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\util::pdoExecute()
     * */
    protected function lastMod(string $where) {
        $s = "select muokattu, muokkaaja from {$this->taulu} ".$where." order by muokattu desc limit 1;";
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st);
        if($st->rowCount()==1) {
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            return array($r[malli::MUOKATTU], $r[malli::MUOKKAAJA]);
        }
        return false;
    }
    
    /**
     * Viimeksi lisätty rivi
     * Tiedot viimeisen hakuehdon täyttävän rivin lisääjästä ja ajankohdasta
     * @param string $where hakuehto
     * @return array luotu, luoja
     * @uses mosBase\util::pdoPrepare
     * @uses mosBase\util::pdoExecute
     * */
    protected function lastInsert(string $where) {
        $s = "select luotu, luoja from {$this->taulu} ".$where." order by luoja desc limit 1;";
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st);
        if($st->rowCount()==1) {
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            return array($r[malli::LUOTU], $r[malli::LUOJA]);
        }
        return false;
    }
}
?>
