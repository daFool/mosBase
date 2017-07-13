<?php
/**
 * Kaikille yhteisiä usein toistuvia "ominaisuuksia"
 * */

namespace mosBase;

trait util {
	/**
     * Käsittelee pdo-virheen
     * @param object $o Joko db-objekti tai itse kantaobjekti
     * @throws Exception 
     * */
    public function pdoError($o, $s) {
        $error = $o->errorInfo();
        $msg = sprintf(_("Tietokantaoperaatio '$s' (%s, %s, %s) epäonnistui!\n"),
                        $error[0], $error[1], $error[2]);
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
	 public function pdoPrepare($s, $db) {
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
    
    public function pdoExecute($st, $d) {
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
	public function selainTiedot() {
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
	function isJarjestelma($koko) {
        if(!isset($koko) || $koko==0)
            return _("Ei arvoa");
        $merkki = $koko < 0 ? -1 : 1;
        $koko = abs($koko);
        $liitteet = array('B','kB', 'MB','GB','TB','PB','YB');
        $indeksi = floor(log($koko,1000));
        $k = $merkki*floor(($koko*100 / pow(1000,$indeksi))) /100;
        return $k.$liitteet[$indeksi];
    }
}
?>