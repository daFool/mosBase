<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@accountor.fi>
 * @copyright Copyright (c) 2017 Accountor Systems Oy
 * @license MIT
 * @license https://opensource.org/licenses/MIT
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

class malli 
{
    /**
     * @var array Rivipuskuri, sisältää viimeiseksi luetun rivin sarakkeet.
     * */
    protected $data;
    /**
     * @var boolean Onko rivipuskurissa rivi vai ei?
     * */
    protected $empty; 
    /**
     * @var string Tietokantataulun nimi.
     * */
    protected $taulu;
    /**
     * @var array Taulukko kenttäjoukkoja, joista kukin joukko kuvaa joko pääavaimen tauluun tai mun uniikin
     * kombinaation sarakkeita.
     * */
    protected $avaimet;
    /**
     * @var string Hakutaulu tai -näkymä, jota käytetään tietojen esittämiseen alla olevan tietokantataulun sijaan.
     * */
    protected $hakutaulu; 
    /**
     * @var array Hakutaulun sarakkeet, joista etsittävää arvoa haetaan
     * */
    protected $hakukentat; /** @var array $hakukentat Hakukentät **/
    
    /**
     * @var object $db PDO-objekti tietokannasta
     * */
    protected $db;
    
    /**
     * @var object $log mosBase\log objekti tietokantalogaukseen
     * */
    protected $log;
    
    use util;
    
    /**
     * @param object $db Tietokannan pdo-objekti
     * @param object $log Tietokantalogi
     * @param string $taulu Taulun nimi kannassa
     * @param array $avaimet Mitkä ovat taulun uniikkeja sarakkeita, yksin tai yhdessä. $avaimet[nimi]=array(kentta, kentta)
     * @param string $hakutaulu Mistä taulusta haetaan
     * @param string $hakukentat Millä kentillä haetaan
     * @uses Malli2::clear()
     * */
    public function __construct($db, $log, $taulu, $avaimet, $hakutaulu="", $hakukentat=array()) {            
        $this->db = $db;
        $this->log = $log;
        $this->clear();
        $this->taulu=$taulu;
        $this->avaimet=$avaimet;
        if($hakutaulu!="") {
            $this->hakutaulu=$hakutaulu;
        }
        else {
            $this->hakutaulu=$taulu;
        }
        if(count($hakukentat)) {
            $this->hakukentat=$hakukentat;
        } else {
            $this->hakukentat=array();
            $i=0;
            foreach($avaimet as $avain) {
                foreach($avain as $kentta) {
                    if(!array_search($kentta, $this->hakukentat)) {
                        $this->hakukentat[$i++]=array("nimi"=>$kentta, "tyyppi"=>"string");
                    }
                }
            }
        }
    }
    
    
    /**
     * Tyhjätään cache
     * */
    protected function clear() {
        $this->data = array();
        $this->empty=true;
    }
    
    /**
     * Avaimen purkaminen sarake-datasta
     * @param array $data Sarake-data, mistä avainta etsitään,
     * @param int $monesko, monnestako avaimesta alkaen etsitään
     * @return mixed Boolean=false, mikäli ei löytynyt avainta ja array,
     * jossa on where-ehto, positio ja avainsarakkeet arvoineen
     * */
    protected function getKey($data, $monesko=-1) {
        $i=0;
        $nullrex="/\w*NULL\w*/i";
        
        foreach($this->avaimet as $avain=>$sarakkeet) {
            $w = "";
            $d = array();
            $i++;
            if($monesko!=-1 && $i<$monesko)
                continue;
            // Onko hakuehdossa tämä avain mukana, vai ei?
            $found=False;
            $all=True;
            foreach($sarakkeet as $sarake) {
                if(isset($data[$sarake]) && $data[$sarake]!=="" &&
                       !preg_match($nullrex, $data[$sarake])) {
                    $d[$sarake]=$data[$sarake];
                    if($w=="") {
                        $w="where {$sarake}=:{$sarake}";
                    } else {
                        $w.=" and {$sarake}=:{$sarake}";
                    }
                    $found=True;
                }
                else {
                    $all=False;
                    break;
                }
            }
            if($all && $found) {
                $r = array("avain"=>$avain, "i"=>$i, "d"=>$d, "w"=>$w);
                return $r;
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
    public function exists($data) {        
        $found=false;
        $this->clear();
        $j=1;
        $r = $this->getKey($data, $j);
        
        while($r!=False) {
            $w = $r["w"];
            $j = $r["i"];
            $d = $r["d"];
            $s = "select * from {$this->taulu} $w;";                           
            $st = $this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st, $d);
            $ds = serialize($data);
            $m=sprintf(_("Testaus %s ({%s})"), $s, $ds);
            $this->log->log("system", $m, __FILE__, __METHOD__, __LINE__, "DEBUG");
            $rows = $st->fetchAll(\PDO::FETCH_ASSOC);
            if(count($rows)>1) {
                return false;
            }
            if(count($rows)==0) {
                $r = $this->getKey($data, $j+1);
                continue;
            }
            $this->data = $rows[0];
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
    protected function isKeyColumn($column) {
        foreach($this->avaimet as $avain=>$sarakkeet) {
            foreach($sarakkeet as $sarake) {
                if($column==$sarake)
                    return True;
            }
        }
        return False;
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
    public function upsert($data) {
        $insert=false;
        if($this->exists($data)) {
            $r = $this->getKey($data);
            $s = "update {$this->taulu} set muokattu=now()";
            if(!isset($data["muokkaaja"]) && isset($data["luoja"])) {
                $data["muokkaaja"]=$data["luoja"];
            }
            foreach($data as $key=>$value) {
                if($key=="muokattu" || $key=="luoja" || $key=="luotu" || $this->isKeyColumn($key))
                    continue;
                $s.=", $key=:$key";
                $d[$key]=$value;
            }
            $s.=" {$r["w"]}";
            $d = array_merge($d, $r["d"]);
        } else {
            $s1="insert into {$this->taulu} (luotu ";
            $s2=" values (now()";
            foreach($data as $key=>$value) {
                if($key=="luotu" || $key=="muokkaaja" || $key=="muokattu")
                    continue;
                $s1.=", $key";
                $s2.=", :$key";
                $d[$key]=$data[$key];
            }
            $insert=true;
            if($this->db->getDatabase()=='pgsql')
                $s = $s1.")".$s2.") returning *;";
            else
                $s = $s1.")".$s2.");";
        }
            
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st,$d);
        $m = sprintf(_("%s (%s)"), $s, serialize($d));
        $this->log->log("SYSTEM", $m, __FILE__,__METHOD__,__LINE__, "DEBUG");
        $this->log->log("SYSTEM", _("Onnistui"), __FILE__,__METHOD__,__LINE__, "DEBUG");
         if($insert && $this->db->getDatabase()=='pgsql') {
            $r = $st->fetch(\PDO::FETCH_ASSOC);            
            $this->data=$r;
            $this->empty=false;
            return true;
        }
        $tulos = $this->exists($data);
        if($tulos!==true) {
            $this->log->log("SYSTEM", _("WTF? käsiteltyä riviä ei ole!"),__FILE__,__METHOD__,
                    __LINE__, "ERROR");
        }
        return $tulos;
    }
    
    /**
     * Löytyykö puskurista?
     * @return boolean true jos jotakin löytyy
     * */
    public function has() {
        return !$this->empty;
    }
    
    /**
     * Data puskurista
     * @return array Puskurin sisältö
     * */
    public function give() {
        return $this->data;
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
    public function tableFetch($start, $length, $order, $search, $where=False) {        
        $d=array();
        $kuka = isset($_SESSION["user"]) ??"system";
        $ds = false;
        $d = array();     
        $tulos = array("lkm"=>0, "rivit"=>array(), "riveja"=>0, "filtered"=>0);

        if($where!==False) {           
            $s = sprintf("select count(*) as lkm from %s where %s",$this->hakutaulu, $where);
        }
        else
            $s = "select count(*) as lkm from ".$this->hakutaulu;
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st, $d);
        if($st->rowCount()==0) {
            return $tulos;
        }
            
        $rivi = $st->fetch(\PDO::FETCH_ASSOC);
        $tulos["lkm"]=$rivi["lkm"];
        $tulos["filtered"]=$rivi["lkm"];
            
        $o="";
        $v="";
        $so="";
        if(isset($search["value"]) && $search["value"]!="") {
            $v=$search["value"];
            $so=" where (";
            $fmt="";
            $dtype = $this->db->getDatabase();
            foreach($this->hakukentat as $kentta) {
                switch($kentta["tyyppi"]) {
                    case "string":
                        if($dtype!="pgsql") {
                            $op = "like";
                            if(!preg_match("/.*[%_].*/", $v)) {
                                $v="%".$v."%";
                            }
                        } else {
                            $op = "ilike";
                            if(preg_match("/.*[.*?+].*/", $v)) {
                                $op = "~*";
                            } elseif(!preg_match("/.*[%_].*/",$v)) {                                
                                $v="%".$v."%";
                            }    
                        }
                        $so.=sprintf("%s%s %s %s", $fmt, $kentta["nimi"], $op, $this->db->quote($v, \PDO::PARAM_STR));
                        $fmt=" or ";          
                        break;
                    case "int":
                        if(is_integer($v)) {
                            $so.=sprintf("%s%s = %s", $fmt, $kentta["nimi"], $this->db->quote($v, \PDO::PARAM_INT));
                            $fmt=" or ";          
                        }
                        break;
                    case "date":
                        $pvm = date_create($v);
                        if($pvm !== False) {
                            $so.=sprintf("%s%s = %s", $fmt, $kentta["nimi"], $this->db->quote($v, \PDO::PARAM_STR));
                            $fmt=" or ";
                        }
                        break;                        
                }                
            }
            $so.=") ";
            $ds = true;
        }
        if($where !== false) {
            $ds=true;
            if($so!="") {
                $so.=" and $where";
            } else
                $so.=" where $where";
                
        }

        $s1= "select * from ".$this->hakutaulu;
        $s2 = " limit $length offset $start;";
        $o="";
        if($order!==false) {
            $o = " order by $order ";                
        }
        $s = "$s1$so$o$s2";
        $m = "$s";
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st,$d);
        if($st->rowCount()==0) {
            $this->log->log($kuka, $m, __FILE__,__METHOD__,__LINE__,"ERROR");
            return $tulos;
        }
        $this->log->log($kuka, $m, __FILE__,__METHOD__,__LINE__,"DEBUG");
        
        $rivit = $st->fetchAll(\PDO::FETCH_ASSOC);
                              
        $tulos["rivit"]=$rivit;
        $tulos["riveja"]=count($rivit);
            
        if($ds) {
            $s1 = "select count(*) as lkm from ".$this->hakutaulu;
            $s = "$s1$so";
            $st=$this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st,$d);
            if($st->rowCount()==0) {
                $m="$s";
                $this->log->log($kuka, $m, __FILE__,__METHOD__,__LINE__,"ERROR");
                return $tulos;
            }
            
            $rivi = $st->fetch(\PDO::FETCH_ASSOC);
            $tulos["filtered"]=$rivi["lkm"];        
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
        if($st->rowCount()==0)
            return false;
        $rivit = $st->fetchAll(\PDO::FETCH_ASSOC);
        return $rivit;
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
    function delete($mika) {                
        $s = "delete from {$this->taulu} ";
        $r = $this->getKey($mika);
        if($r!==False) {
            $s.=$r["w"];
            $st = $this->pdoPrepare($s, $this->db);
            $this->pdoExecute($st, $r["d"]);
            return True;
        }
        $s.="where ";
        $eka=true;
        $d = array();
        foreach($mika as $avain=>$arvo) {
            if(!$eka) {
                $s.=" and ";
            } 
            $eka=false;
            $s.="$avain = :$avain";
            $d[$avain]=$arvo;            
        }
        if($eka===true) {
            $this->log->log("SYSTEM", _("En poista kaikkia rivejä!"), __FILE__, __METHOD__, __LINE__, "FATAL");
            return false;
        }
        $this->log->log((isset($d["muokkaaja"])??"SYSTEM"), $s.serialize($d),__FILE__,__METHOD__,__LINE__,"INFO");
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st, $d);
        return True;        
    }
    
    /**
     * Viimeksi muokattu
     * Hakee tuoreimman ehdon täyttävän muutoksen sisältävän rivin
     * @param string $where hakuehto
     * @return array muokattu, muokkaaja
     * @uses mosBase\util::pdoPrepare()
     * @uses mosBase\util::pdoExecute()
     * */
    private function lastMod($where) {
        $s = "select muokattu, muokkaaja from {$this->taulu} ".$where." order by muokattu desc limit 1;";
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st);
        if($st->rowCount()==1) {
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            return array($r["muokattu"], $r["muokkaaja"]);
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
    private function lastInsert($where) {
        $s = "select luotu, luoja from {$this->taulu} ".$where." order by luoja desc limit 1;";
        $st = $this->pdoPrepare($s, $this->db);
        $this->pdoExecute($st);
        if($st->rowCount()==1) {
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            return array($r["luotu"], $r["luoja"]);
        }
        return false;
    }
}
?>