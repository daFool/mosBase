<?php
/**
 * Apumetodeita tietokannan käsittelyyn ja vähän muuhunkin
 *
 * @category  Util
 * @package   mosBase
 * @author    Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright 2018 Mauri Sahlberg, Helsinki
 * @license   MIT https://opensource.org/licenses/MIT
 * @link      www.iki.fi/mos
 */
/**
 * Kaikille yhteisiä usein toistuvia "ominaisuuksia"
 * */

namespace mosBase;

define("UNKNOWN", _("Ei tiedossa"));
define("TULOS", "tulos");

trait Util
{
    
    /**
     * Käsittelee pdo-virheen
     *
     * @param  database/PDOStatement $o
     * @throws Exception
     * */
    public function pdoError($o, string $s) : void
    {
        throw new DatabaseException("", $o, $s);
    }
    /**
     * SQL-lauseen prepare virheenkäsittelyllä
     *
     * @param  object $db PDO-database-objekti
     * @param  string $s  SQL-lause, joka preparoidaan
     * @return \PDOStatement PDO::Statement-objekti
     * @uses   mosbase\util\pdoError
     * @throws Exception tai oikeammin pdoError heittää poikkeuksen
     * */
    public function pdoPrepare(string $s, database $db) : \PDOStatement
    {
        if (!isset($db) || gettype($db)!="object") {
            $msg = _("Ei tietokantayhteyttä! ");
            throw new DatabaseException($msg);
        }
        $st = $db->prepare($s);
        if ($st===false) {
            $this->pdoError($db, $s);
        }
        return $st;
    }

    /**
     * SQL-lauseen suoritus virheenkäsittelyllä
     *
     * @param  object $st PDO-statement-objekti
     * @param  array  $d  parametrien data
     * @return true
     * @uses   mosbase\util\pdoError
     * @throws Exception tai oikeammin pdoError heittää poikkeuksen
     * */
    
    public function pdoExecute(\PDOStatement $st, $d = false) : bool
    {
        if ($d!==false) {
            $res = $st->execute($d);
        } else {
            $res = $st->execute();
        }
        if ($res===false) {
            $this->pdoError($st, $st->queryString);
        }
        return true;
    }

    /**
     * Selaimen tietojen kalasteleminen
     *
     * @return array, array (tulos, ip, selain)
     * */
    public function selainTiedot() : array
    {
        $res = array(TULOS=>false);
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $res["selain"]=$_SERVER['HTTP_USER_AGENT'];
            $res[TULOS]=true;
        }
        if (isset($_SERVER["REMOTE_ADDR"])) {
            $res["ip"]=$_SERVER["REMOTE_ADDR"];
            $res[TULOS]=true;
        }
        return $res;
    }
    /**
     * Siistii numeron
     *
     * Muuttaa tavut nätimmäksi yksiköksi
     *
     * @param  int $koko Siivottava luku
     * @return string Joko siistityn luvun merkkijonona tai tekstin "Ei arvoa" halutulla kielellä.
     * */
    public function isJarjestelma(int $koko) : string
    {
        if (!isset($koko) || $koko==0) {
            return _("Ei arvoa");
        }
        $merkki = $koko < 0 ? -1 : 1;
        $koko = abs($koko);
        $liitteet = array('B','kB', 'MB','GB','TB','PB','YB');
        $indeksi = floor(log($koko, 1000));
        $k = $merkki*floor(($koko*100 / pow(1000, $indeksi))) /100;
        return $k.$liitteet[$indeksi];
    }
    
    /**
     * Onko integeri?
     *
     * @param  string $str Testattava merkkijono
     * @return bool
     * */
    public function isInt(string $str) : bool
    {
        if (preg_match("/^(0)$|^(-?[123456789][0-9]+)$/", $str)) {
            return true;
        }
        return false;
    }
    
        /**
     * Onko laillinen mallinmukainen ajanesitys?
     *
     * @param string $malli Mitä haetaan, päivä, aika vai molemmat?
     * @param string $arvo Mitä sovitetaan?
     * @return bool False jos ei sovi, True jos sopii
     * */
    public function resolveTime(string $malli, string $arvo) : bool
    {
        $mallit = array(
            Malli::DATE => array("Y-m-d"),
            Malli::TIME => array("H:i:s", "H:i:sO", "H:i:sP"),
        );
        $mallit[Malli::DATETIME]=array();
        foreach ($mallit[Malli::TIME] as $aika) {
            $mallit[Malli::DATETIME][]=$mallit[Malli::DATE][0]." ".$aika;
        }
        foreach ($mallit[$malli] as $hahmo) {
            if ($koe=\DateTime::createFromFormat($hahmo, $arvo)) {
                $f = substr($koe->format($hahmo),0,strlen($arvo));
                if ($f!=$arvo) {
                    continue;
                }
                return true;
            }
        }
        return false;
    }
}
