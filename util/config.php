<?php
/**
 * Konfiguraatiotiedostojen käsittely
 * @category 	Util
 * @package		mosBase	
 * @author 		Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright 	2018 Mauri Sahlberg, Helsinki
 * @license 	MIT https://opensource.org/licenses/MIT
 */
/**
 * Konfiguraation käsittely
 * */
namespace mosBase;
/**
 * Konfiguraation lukeminen tiedostosta
 * */
class config {
	/**
	 * @var array $data Konfiguraatio tiedostosta
	 * */
	private $data=array();
	
	/** Konstruktori
	 * */
	public function __construct() {
		$this->data=array();
	}
	/**
	 * Lukee konfiguraation tiedostosta
	 * @param string $tiedosto Tiedostonimi, mistä konfiguraatio luetaan
	 * */
	
	protected function lue(string $tiedosto) {
		$this->data = parse_ini_file($tiedosto, true, INI_SCANNER_NORMAL);
		return $this->data;
	}
	
	/**
	 * Hakee yhden osa-alueen konfiguraation
	 * @param string $alue Osa-alue, jonka konfiguraatio halutaan
	 * */
	public function get(string $alue) {
		return $this->data[$alue] ?? False;
	}
	
	/**
	 * Konfiguraation käsittely
	 * 
	 * Kutsuu asetustiedoston parseria. Asettaa luokka-loaderin ja aikavyöhykkeen.
	 * */
	public function init(string $tiedosto) {
		if($this->lue($tiedosto)===False) {
			throw new Exception(sprintf(_("Konfiguraation %s lataaminen epäonnistui."),$tiedosto));
		}
		spl_autoload_register("mosBase\\config::classLoader");
		date_default_timezone_set($this->data["General"]["TZ"]);
	}
	
	public function classLoader($class) {
		$class = str_replace("mosBase\\","",$class);
		$class=str_replace("\\",'/',$class);
		foreach($this->data["ClassDirs"]["dir"] as $classDir) {
			$fn = "$classDir/$class.php";
			if(file_exists($fn)) {
			    require($fn); // if require fails it kills, no need to check return value here!
			    return true;
			}
		}	
		return false;
	}
}
?>