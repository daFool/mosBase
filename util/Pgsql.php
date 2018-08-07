<?php
/**
 * Tutkiskellaan taulun rakennetta
 *
 * @category    Util
 * @package     mosBase
 * @author      Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright   2018 Mauri Sahlberg, Helsinki
 * @license     MIT https://opensource.org/licenses/MIT
 * @link        www.iki.fi/mos
 *
 */
 /**
  * Testattu ainoastaan postgresql:n uudemmilla versioilla ja
  * yli 7-sarjan php-versioilla. PDO-versio
  * */

namespace mosBase;

trait Pgsql {
    
    use Util;
    
    /**
     * Palauttaa annetun taulun sarakkeet ja niiden tyypit
     * @param database $db
     * @param string $tablename
     * @return array Palauttaa taulun sarakkeet ja niiden tyypit
     * - name, on sarakkeen nimi
     * - type, on tietokannan ilmoittama tyyppinimi
     * - pdotype, on pdo:n käsitys tyypistä
     * */
    public function tableColumns(database $db, string $tablename) : array {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tablename)) {
            return array();
        }
        $s = sprintf("select * from %s limit 1;", $tablename);
        $st = $this->pdoPrepare($s, $db);
        $this->pdoExecute($st);
        $st->fetch(\PDO::FETCH_ASSOC);
        $res=array();
        $clkm = $st->columnCount();
        for($i=0;$i<$clkm;$i++) {
            $c = $st->getColumnMeta($i);
            array_push($res, array("name"=>$c["name"], "type"=>$c["native_type"], "pdotype"=>$c["pdo_type"]));
        }
        return $res;
    }
    
    /**
     * Does the result have arrays in it?
     * @param \PDOStatement $st  Statement generating the result
     *
     * @return mixed false if there were no array columns in the result and
     *  an array with select-list and array of fields that are arrays and need to be unpacked
     *  */
    public function hasArrayColumns(\PDOStatement $st) {
        $i=0;
        $res=false;
        $slist="";
        $f=array();
        $clkm=$st->columnCount();
        for($i=0;$i<$clkm;$i++) {
            $c = $st->getColumnMeta($i);
            $slist.=$slist!=""?",":"";
            if (preg_match('/^_.*$/',$c["native_type"])) {
                $slist.="to_json(".$c["name"].") as ".$c["name"];
                $res=true;
                $f[$c["name"]]=true;                
            } else {
                $slist.=$c["name"];
            }
        }
        if ($res) {
           return array($slist, $f); 
        }
        return false;
    }
    
    /**
     * Unpacks json from the result
     * @param array $rivi Assosiatiivinen taulu, jossa avaimet sarakkeita
     * @param array $column Sarakkeiden nimet indekseinä, arvoina true, jotka ovat jsonia
     * @return array Purettu rivi
     * */
    public function unpack(array $rivi, array $columns) : array {
        $result = array();
        foreach ($rivi as $c=>$v) {
            if (isset($columns[$c])) {
                $result[$c]=json_decode($v);
            } else {
                $result[$c]=$v;
            }
        }
        return $result;
    }
}
?>