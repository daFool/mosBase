<?php
/**
 * Tutkiskellaan taulun rakennetta
 *
 * @category  Util
 * @package   mosBase
 * @author    Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright 2018 Mauri Sahlberg, Helsinki
 * @license   MIT https://opensource.org/licenses/MIT
 * @link      www.iki.fi/mos
 */
 /**
  * Testattu ainoastaan postgresql:n uudemmilla versioilla ja
  * yli 7-sarjan php-versioilla. PDO-versio
  * */

namespace mosBase;

trait Pgsql
{
    
    use Util;
    
    /**
     * Palauttaa annetun taulun sarakkeet ja niiden tyypit
     *
     * @param  database $db
     * @param  string   $tablename
     * @return array Palauttaa taulun sarakkeet ja niiden tyypit
     * - name, on sarakkeen nimi
     * - type, on tietokannan ilmoittama tyyppinimi
     * - pdotype, on pdo:n käsitys tyypistä
     * */
    public function tableColumns(database $db, string $tablename) : array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tablename)) {
            return array();
        }
        $s = sprintf("select * from %s limit 1;", $tablename);
        $st = $this->pdoPrepare($s, $db);
        $this->pdoExecute($st);
        $st->fetch(\PDO::FETCH_ASSOC);
        $res=array();
        $clkm = $st->columnCount();
        $mtypes = array("varchar"=>\mosBase\Malli::STRINGI,
                        "int4"=>\mosBase\Malli::INTTI,
                        "timestamptz"=>\mosBase\Malli::DATETIME,
                        "numeric"=>\mosBase\Malli::NUMERIC,
                        "_varchar"=>\mosBase\Malli::STRINGA,
                        "_int4"=>\mosBase\Malli::INTA,
                        "int8"=>\mosBase\Malli::INTTI,
                        "date"=>\mosBase\Malli::DATE,
                        "time"=>\mosBase\Malli::TIME,
                        "timetz"=>\mosBase\Malli::TIME,
                        "text"=>\mosBase\Malli::STRINGI);
        for ($i=0; $i<$clkm; $i++) {
            $c = $st->getColumnMeta($i);
            $mtype = $mtypes[$c["native_type"]]??\mosBase\Malli::STRINGI;
            $res[$c["name"]]=array(
                "type"=>$c["native_type"],
                "pdotype"=>$c["pdo_type"],
                "mytype"=>$mtype
            );              
        }
        return $res;
    }
    
    /**
     * Does the result have arrays in it?
     *
     * @param \PDOStatement $st Statement generating the result
     *
     * @return mixed false if there were no array columns in the result and
     *  an array with select-list and array of fields that are arrays and need to be unpacked
     *  */
    public function hasArrayColumns(\PDOStatement $st)
    {
        $i=0;
        $res=false;
        $slist="";
        $f=array();
        $clkm=$st->columnCount();
        for ($i=0; $i<$clkm; $i++) {
            $c = $st->getColumnMeta($i);
            $slist.=$slist!=""?",":"";
            if (preg_match('/^_.*$/', $c["native_type"])) {
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
     *
     * @param  array $rivi   Assosiatiivinen taulu, jossa avaimet sarakkeita
     * @param  array $column Sarakkeiden nimet indekseinä, arvoina true, jotka ovat jsonia
     * @return array Purettu rivi
     * */
    public function unpack(array $rivi, array $columns) : array
    {
        $result = array();
        foreach ($rivi as $c => $v) {
            if (isset($columns[$c])) {
                $result[$c]=json_decode($v);
            } else {
                $result[$c]=$v;
            }
        }
        return $result;
    }
    
    /**
     * Haku regexillä
     *
     * @param \mosBase\Database $kanta kanta
     * @param string $taulu            taulu, josta haetaan
     * @param array $kentat            sarakkeet, joista haetaan
     * @param string $mita             mitä haetaan
     * @param string $filtteri         ylimääräinen hakuehto
     *
     * @return array - Rivit, joihin tuli osuma
     * */
    public function findWithRegex(
        \mosBase\Database $kanta,
        string $taulu,
        array $kentat,
        string $mita,
        string $filtteri
    ) : array {
        $tyypit = $this->tableColumns($kanta, $taulu);
        $cj=" ";
        if ($filtteri !="") {
            $cj=" and (";
            $w=$filtteri;
        } else {
            $w = " where (";
        }        
        foreach($kentat as $kentta) {
            switch($tyypit[$kentta]["mytype"]) {
                case \mosBase\Malli::STRINGI:
                    $op='~*';
                    $fmt = "%s %s %s :rex";
                    if($this->isInt($mita) || is_numeric($mita)) {
                        $fmt="%s %s %s ':rex'";
                    }
                    $w.=sprintf($fmt, $cj, $kentta, $op);
                    $cj=" or";
                    break;
                case \mosBase\Malli::STRINGA:
                    $fmt="%s exists (select * from unnest(%s) as x where x %s :rex)";
                    if($this->isInt($mita) || is_numeric($mita)) {
                        $fmt="%s exists (select * from unnest(%s) as x where x %s ':rex')";
                    }
                    $op='~*';
                    $w.=sprintf(
                        $fmt,
                        $cj,
                        $kentta,
                        $op);
                    $cj=" or";
                    break;
                case \mosBase\Malli::INTTI:
                    if ($this->isInt($mita)) {
                        $op='=';
                        $w.=sprintf(
                            "%s %s%s:rex",
                            $cj,
                            $kentta,
                            $op
                        );
                        $cj=" or";
                    }
                    break;
                case \mosBase\Malli::DATE:
                case \mosBase\Malli::TIME:
                case \mosBase\Malli::DATETIME:
                    $pvm = $this->resolveTime($tyypit[$kentta]["mytype"], $mita);
                    if ($pvm!==false) {
                        $w.=sprintf("%s %s=:rex::%s", $cj, $kentta, $tyypit[$kentta]["type"]);
                        $cj=" or";
                    }
                    break;
                case \mosBase\Malli::NUMERIC:
                    if(is_numeric($mita)) {
                        $w.=sprintf("%s %s=:rex", $cj, $kentta);
                        $cj=" or";                        
                    }
                    break;
                case \mosBase\Malli::INTA:
                    $w.=sprintf("%s :rex = any(%s)", $cj, $kentta);
                    $cj=" or";
                    break;
            }
        }
        $w.=");";
        $s = sprintf("select * from %s %s", $taulu, $w);
        $d = array("rex"=>$mita);
        $st = $this->pdoPrepare($s, $kanta);
        $this->pdoExecute($st, $d);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}
