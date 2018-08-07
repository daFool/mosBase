<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright Copyright (c) 2017 Mauri Sahlberg, Helsinki
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
/**
 * Kielivalinnat
 * */
namespace mosBase;

/**
 * Kielten käsittely
 * */

class Language {
    /**
     * @var array $allLocales Kaikki levyltä löytyneet lokalisointitiedostot
     * */
    private $allLocales;
    
    /**
     * @var array $languages Kaikki tuetut kielet
     * */
    private $languages;
    
    /**
     * @var string $current Käytössäoleva lokaali
     * */
    private $current;
    
    /**
     * @var string $basepath Peruspolku
     * */
    private $basepath;
    
    /**
     * @var log $log Logi
     * */
    private $log;
    
    private const LOCALE="locale";
    private const LANGUAGE="language";
    private const MESSAGES='messages';
    private const KIELIREX="/(.*),/U";
    
    /**
     * Konstruktori
     *
     * Ajaa findia löytääkseen kielitiedostot.
     * 
     * @param string $path Kielitiedostojen hakupolku
     * @param string $basepath Sovelluksen asennuspolku
     * @param log $log Logi
     * */
    public function __construct(string $path, string $basepath, log $log) {
        $cmd = sprintf('find %s -maxdepth 1 -mindepth 1 -type d -printf "%f\n"', $path);
        $f = shell_exec($cmd);        
        $this->allLocales = explode("\n", $f);
        $this->languages=array();
        foreach ($this->allLocales as $locale) {
            if ($locale=="") {
                continue;
            }
            array_push($this->languages, array(language::LOCALE=>$locale,
                                               language::LANGUAGE=>locale_get_display_language($locale, $locale)));
        }
        $this->log = $log;
        $this->basepath = $basepath;
    }
    
    /**
     * Löytyykö lokaalin kieli?
     * Etsii ja asettaa, jos on
     * @param string $locale Etsittävä lokaali
     * @return boolean False jos ei, True jos on
     * */
    public function onko(string $locale) : boolean {       
        $path = $this->basepath."/locale/$locale/LC_MESSAGES/messages.mo";
         if (file_exists($path)) {
            putenv("LC_ALL=$locale");
            $res=setlocale(LC_ALL, $locale);
            if($res!=$locale) {
                return false;
            }
            bindtextdomain(language::MESSAGES, $this->basepath."/locale");
            bind_textdomain_codeset(language::MESSAGES,"UTF-8");    
            textdomain(language::MESSAGES);
            $this->current = $locale;
            $_SESSION[language::LOCALE]=$locale;
            return true;
        }
        return false;
    }
    
    /**
     * ACCEPT-LANGUAGES purku
     * @param string $kielet Toivotut kielet
     * @return boolean true jos tuetaan ja false jos ei
     * */
    private function searchLocale(string $kielet) : boolean {
        $res = preg_match_all(language::KIELIREX, $kielet, $matches);
        if ($res) {
            $matches=$matches[0];
            $high=0;
            foreach ($matches as $match) {
                @list($lang, $pref)=@explode(";",$match);
                if ($lang != $match && isset($pref)) {
                    $val=explode("=",$pref)[1];
                    $val=substr($val,0,-1);
                } else {
                    $val = 1;
                }
                $lang=str_replace(",","",$lang);
                if ($val > $high) {
                    $high=$val;
                    $lng = $this->lokaaliksi($lang);
                    if ($lng!==false) {
                        return $this->onko($lng);                        
                    }
                }
            }
        }
        return false;    
    }
   
    /**
     * Käyttäjän lokaali
     *
     * Yrittää selvittää mitä kieltä on tarkoitus käyttää.
     * @uses mosBase\language::onko()
     * @return string palauttaa lokaalin
     **/
    public function kieli() : boolean {
        
        $res = false;
        if(isset($_SESSION[language::LOCALE])) {
            $res=$this->onko($_SESSION[language::LOCALE]);
        }
        if($res===false && !isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $res="fi_FI";
        }
        else {
            $kielet = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            $locale = locale_accept_from_http($kielet);
            if($this->onko($locale)) {
                if(isset($_SESSION)) {
                    $_SESSION[language::LOCALE]=$locale;
                }
                $res=$locale;
            }
        }
        return $res || $this->searchLocale($kielet);        
    }

    /**
     * Onko lokaali?
     * @param $string $locale
     * @return mixed False jos ei ole ja lokaali, joka on olemassa
     * */
    public function lokaaliksi(string $locale) {                        
        foreach ($this->allLocales as $mun) {
            if (strlen($locale)==2) {
                $locale=$locale."_".strtoupper($locale);
            }
            if (locale_filter_matches($locale, $mun, false)) {
                return $mun;
            }
        }
        return False;
    }

    /**
     * Tuetut kielet
     * @return array kielet
     * */
    public function kielet() {
        return $this->languages;
    }
    
    /**
     * Mikä kieli on valittuna
     * @return string Valittu lokaali
     * */
    public function nyt() {
        foreach ($this->languages as $l) {
            if ($l[language::LOCALE]==$this->current) {
                return $l[language::LANGUAGE];
            }
        }
        return "";
    }
}
?>