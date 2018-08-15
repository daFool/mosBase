<?php
/**
 * Konfiguraatiotiedostojen käsittely
 *
 * @category  Util
 * @package   mosBase
 * @author    Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright 2018 Mauri Sahlberg, Helsinki
 * @license   MIT https://opensource.org/licenses/MIT
 */
/**
 * Konfiguraation käsittely
 * */
namespace mosBase;

/**
 * Konfiguraation lukeminen tiedostosta
 * */
class Config
{
    /**
     * @var array $data Konfiguraatio tiedostosta
     * */
    private $data=[];
    
    /**
     * Konstruktori
     * */
    public function __construct()
    {
    }
    /**
     * Lukee konfiguraation tiedostosta
     *
     * @param  string $tiedosto Tiedostonimi, mistä konfiguraatio luetaan
     * @return mixed Moniulotteinen taulukko konfiguraatiota tai false, mikäli konfiguraatiotiedosto ei auennut
     * */
    
    protected function lue(string $tiedosto)
    {
        try {
            $this->data = parse_ini_file($tiedosto, true, INI_SCANNER_NORMAL);
            return $this->data;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Hakee yhden osa-alueen konfiguraation
     *
     * @param  string $alue Osa-alue, jonka konfiguraatio halutaan
     * @return mixed Taulukko osa-alueen parametreja tai false, jos aluetta ei ole
     * */
    public function get(string $alue)
    {
        return $this->data[$alue] ?? false;
    }
    
    /**
     * Konfiguraation käsittely
     *
     * Kutsuu asetustiedoston parseria. Asettaa luokka-loaderin ja aikavyöhykkeen.
     * */
    public function init(string $tiedosto)
    {
        if ($this->lue($tiedosto)===false) {
            throw new ConfigException(sprintf(_("Konfiguraation %s lataaminen epäonnistui."), $tiedosto));
        }
        if (isset($this->data["ClassDirs"])) {
            spl_autoload_register("mosBase\Config::classLoader");
        }
        if (!isset($this->data["General"]["TZ"])) {
            throw new ConfigException(sprintf(_("Konfiguraatiosta %s uupuu aikavyöhyke, liian suspektia!"), $tiedosto));
        }
        date_default_timezone_set($this->data["General"]["TZ"]);
        return true;
    }
    
    /**
     * Luokkien autolataaja
     *
     * Ei ole yhteensopiva
     *
     * @param string                                                                      $class Ladattavan luokan nimi
     * @param boolean Palauttaa true, jos sai ladattua luokan tai false jos ei löytänyt
     * */
    public function classLoader($class)
    {
        $class = str_replace("mosBase\\", "", $class);
        $class=str_replace("\\", '/', $class);
        foreach ($this->data["ClassDirs"]["dir"] as $classDir) {
            $fn = "$classDir/$class.php";
            if (file_exists($fn)) {
                include_once $fn; // if require fails it kills, no need to check return value here!
                return true;
            }
        }
        return false;
    }
}
