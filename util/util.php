<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright Copyright (c) 2017 Mauri Sahlberg, Helsinki
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
/**
 * Kaikille yhteisiä usein toistuvia "ominaisuuksia"
 * */

namespace mosBase;

trait util {
	/**
     * Käsittelee pdo-virheen
     * @param database $o 
     * @throws Exception 
     * */
    public function pdoError($o, string $s) : void {
        $error = $o->errorInfo();
		$m = _("Tietokantaoperaatio '%s' (%s, %s, %s) epäonnistui!\n");
	    $msg = sprintf($m,$s??"Unknown", $error[0]??"Unknown", $error[1]??"Unknown", $error[2]??"Unkown");
        throw new \Exception($msg);
    }
	/**
     * SQL-lauseen prepare virheenkäsittelyllä
     * @param object $db PDO-database-objekti
     * @param string $s SQL-lause, joka preparoidaan
     * @return object PDO::Statement-objekti
     * @uses mosbase\util\pdoError
     * @throws Exception tai oikeammin pdoError heittää poikkeuksen
     * */
	 public function pdoPrepare(string $s, database $db) {
        if(!isset($db) || gettype($db)!="object") {
			$msg = _("Ei tietokantayhteyttä! ");
			throw new \Exception($msg);
		}
		$st = $db->prepare($s);
        if($st===False) {
            $this->pdoError($db, $s);
        }
        return $st;
    }

	 /**
     * SQL-lauseen suoritus virheenkäsittelyllä
     * @param object $st PDO-statement-objekti
     * @param array $d parametrien data
     * @return true
     * @uses mosbase\util\pdoError
     * @throws Exception tai oikeammin pdoError heittää poikkeuksen
     * */
    
    public function pdoExecute(\PDOStatement $st, $d=False) : bool {
        if($d!==False)
            $res = $st->execute($d);
        else
            $res = $st->execute();
		if($res===False) {
            $this->pdoError($st, $st->queryString);
        }
        return true;
    }

	/**
	 * Selaimen tietojen kalasteleminen
	 * @return array, array (tulos, ip, selain)
	 * */
	public function selainTiedot() : array {
		$res = array("tulos"=>False);
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			$res["selain"]=$_SERVER['HTTP_USER_AGENT'];
			$res["tulos"]=True;
		}
		if(isset($_SERVER["REMOTE_ADDR"])) {
			$res["ip"]=$_SERVER["REMOTE_ADDR"];
			$res["tulos"]=True;
		}
		return $res;
	}
	/**
	 * Siistii numeron
	 *
	 * Muuttaa tavut nätimmäksi yksiköksi
	 * @param int $koko Siivottava luku
	 * @return string Joko siistityn luvun merkkijonona tai tekstin "Ei arvoa" halutulla kielellä.
	 * */
	function isJarjestelma(int $koko) : string {
        if(!isset($koko) || $koko==0)
            return _("Ei arvoa");
        $merkki = $koko < 0 ? -1 : 1;
        $koko = abs($koko);
        $liitteet = array('B','kB', 'MB','GB','TB','PB','YB');
        $indeksi = floor(log($koko,1000));
        $k = $merkki*floor(($koko*100 / pow(1000,$indeksi))) /100;
        return $k.$liitteet[$indeksi];
    }
	
	/**
	 * Onko integeri?
	 * @param string $str Testattava merkkijono
	 * @return bool
	 * */
	function isInt(string $str) : bool {
		if(preg_match("/^(0)|(-?[123456789][0-9]+)$/",$str)) {
			return True;
		}
		return False;
	}
}
?>