<?php
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
	
	protected function lue($tiedosto) {
		$this->data = parse_ini_file($tiedosto, true, INI_SCANNER_NORMAL);
		return $this->data;
	}
	
	/**
	 * Hakee yhden osa-alueen konfiguraation
	 * @param string $alue Osa-alue, jonka konfiguraatio halutaan
	 * */
	public function get($alue) {
		return $this->data[$alue] ?? False;
	}
	
	public function init($tiedosto) {
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
			    $res = require($fn);
			    return $res==1 ? True : False;
			}
		}	
		return False;
	}
}
?>