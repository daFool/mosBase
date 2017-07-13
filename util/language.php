<?php
/**
 * Kielivalinnat
 * */
namespace mosBase;

/**
 * Kielten käsittely
 * */

class language {
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
     * @var object $log Logi
     * */
    private $log;
    
    /**
     * Konstruktori
     *
     * Ajaa findia löytääkseen kielitiedostot.
     * 
     * @param string $path Kielitiedostojen hakupolku
     * @param string $basepath Sovelluksen asennuspolku
     * @param object $log Logi
     * */
    public function __construct($path, $basepath, $log) {
        $f = `find $path -maxdepth 1 -mindepth 1 -type d -printf "%f\n"`;        
        $this->allLocales = explode("\n", $f);
        $this->languages=array();
        foreach($this->allLocales as $locale) {
            if($locale=="")
                continue;
            array_push($this->languages, array("locale"=>$locale,
                                               "language"=>locale_get_display_language($locale, $locale)));
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
    function onko($locale) {       
        $path = $this->basepath."/locale/$locale/LC_MESSAGES/messages.mo";
         if(file_exists($path)) {
            putenv("LC_ALL=$locale");
            $res=setlocale(LC_ALL, $locale);
            if($res!=$locale) {
                return False;
            }
            bindtextdomain("messages", $this->basepath."/locale");
            bind_textdomain_codeset("messages","UTF-8");    
            textdomain("messages");
            $this->current = $locale;
            $_SESSION["locale"]=$locale;
            return true;
        }
        return false;
    }
    
    /**
     * Käyttäjän lokaali
     *
     * Yrittää selvittää mitä kieltä on tarkoitus käyttää.
     * @uses mosBase\language::onko()
     * @return boolean False jos ei saanut asetettua "oikeaa" kieltä ja True jos sai.
     **/
    function kieli() {
        
        if(isset($_SESSION["locale"])) {
            return $this->onko($_SESSION["locale"]);
        }    
        $kielet = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : false;
        if($kielet===False)
            return "fi_FI";
        $locale = locale_accept_from_http($kielet);
        if($this->onko($locale)) {
            if(isset($_SESSION)) {
                $_SESSION["locale"]=$locale;
            }
            return $locale;
        }
        $rex = "/(.*),/U";
        $res = preg_match_all($rex, $kielet, $matches);
        if($res) {
            $matches=$matches[0];
            $high=0;
            foreach($matches as $match) {
                @list($lang, $pref)=@explode(";",$match);
                if($lang != $match && isset($pref)) {
                    list($q,$val)=explode("=",$pref);
                    $val=substr($val,0,-1);
                } else {
                    $val = 1;
                }
                $lang=str_replace(",","",$lang);
                if($val > $high) {
                    $high=$val;
                    $lng = $this->lokaaliksi($lang);
                    if($lng!==False) {
                        $this->onko($lng);                        
                    }
                }
            }
        }
        return False;    
    }

    /**
     * Onko lokaali?
     * @param $string $locale
     * @return mixed False jos ei ole ja lokaali, joka on olemassa
     * */
    function lokaaliksi($locale) {                        
        foreach($this->allLocales as $mun) {
            if(strlen($locale)==2) {
                $locale=$locale."_".strtoupper($locale);
            }
            if(locale_filter_matches($locale, $mun, false))
                return $mun;
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
        foreach($this->languages as $l) {
            if($l["locale"]==$this->current)
                return $l["language"];
        }
        return "";
    }
}
?>