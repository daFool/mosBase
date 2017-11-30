<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright Copyright (c) 2017 Mauri Sahlberg, Helsinki
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
/**
 * Istuntojen käsittely - kun Apacheen on asennettu mod_mellon ja istunnot rakennellaan
 * AD-federoinnilla.
 * */
namespace mosBase;

/**
 * Istunto
 * */
class session {
    /**
     * @var string $session_name Istunnon nimi
     * */
    protected $session_name;
    
    /**
     * @var string $session_timeout Istunnon kesto sekunneissa
     * */
    protected $session_timeout;
    
    /**
     *@var string $session_cookiepath Istuntopiparin polku
     **/
    protected $session_cookiepath;
    
    /**
     * @var string $hostname Hostin fqdn
     * */
    
    protected $hostname;
    
    /**
     * @var string $baseurl Sivuston perusosoite
     * */
    protected $baseurl;
    
    /**
     * @var string $mellonendpoint Mahdollisen mellon installaattion osoite
     * */
    protected $mellonendpoint;
    
    /**
     * @var string $mellonrex Regexp, jolla etsitään ryhmiä Mellonin muuttujista
     * */
    
    protected $mellonrex;
    /**
     * @var array $ldapGroup, ryhmät joilla on käyttöoikeus
     * */
    protected $ldapGroup;
    
    /**
     * Konstruktori
     * @param array $sessionparams Istunnon parametrit
     * @param array $generalparams Yhteiset parametrit
     * */
    public function __construct($sessionparams, $generalparams) {
        $this->session_name = $sessionparams["SESSION_NAME"];
        $this->session_timeout = $sessionparams["SESSION_TIMEOUT"];
        $this->session_cookiepath = $sessionparams["SESSION_COOKIEPATH"];
        $this->hostname = $generalparams["hostname"];
        $this->baseurl = $generalparams["baseurl"];
        $this->mellonendpoint = $sessionparams["MellonEndpoint"]??"";
        $this->mellonrex = $sessionparams["MellonRex"]??"";
        $this->ldapGroup = $sessionparams["ldapGroup"]??array();
    }
    
    /**
     * Sivun aloitus
     *
     * Kutsutaan jokaisen sivun alussa ensimmäisenä asiana. Varmistaa piparin ajantasaisuuden ja istunnon
     * olemassa olon. Jos istuntoa ei ole, siirtää kirjautumissivulle.
     * */
    public function pageStart() {
        session_name($this->session_name);
        session_set_cookie_params($this->session_timeout, $this->session_cookiepath);
        session_cache_expire($this->session_timeout);
        session_cache_limiter("nocache");
        session_start();
        setcookie(session_name(), session_id(), time()+$this->session_timeout, $this->session_cookiepath);

        if(!isset($_SESSION['activity'])) {
            $_SESSION['activity']=time();
            $logout=false;
        }
        else {
            if (time() - $_SESSION['activity'] > $this->session_timeout) {
                $logout=true;
            } else {
                $_SESSION['activity']=time();
                $logout=false;
            }
        }

        if (isset($_REQUEST['logout']) || $logout===true) {  
            // Unset all of the session variables.
            $_SESSION = array();
    
            // If it is desired to kill the session, also delete the session cookie.
            // Note: This will destroy the session, and not just the session data!
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]);
            }

            // Finally, destroy the session.
            session_destroy();
            if(isset($_SERVER["MELLON_NAME_ID"])) {
                $logouturl=$this->mellonendpoint."/logout?ReturnTo=";
                $logouturl.=urlencode("https://".$this->hostname.$this->baseurl);
            }
            else
                $logouturl="$baseurl";
            header("Location: $logouturl");
        }   
        
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"]===false) {
            if(isset($_SERVER["MELLON_NAME_ID"])) {
                $groups=$_SERVER["MELLON_http://schemas_xmlsoap_org/claims/Group"];
                if(preg_match_all($this->mellonrex, $groups, $m, PREG_PATTERN_ORDER)) {
                    foreach($this->ldapGroup as $group) {
                        if(array_search($group, $m[1])) {
                            $_SESSION["loggedin"]=true;
                            $_SESSION["user"]=explode('\\',$_SERVER["MELLON_NAME_ID"])[1];
                            break;
                        }
                    }
                }            
            }
        }     
    }
    
    /**
     * Onko istuntoa?
     * */
    public function loggedIn($loginUrl=False) {    
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"]===false) {
            if($loginUrl===False)
                header("Location: ".$this->baseurl."/controller/cLogin.php");
            else
                header("Location: $loginUrl");
        die;
    }
}
}
?>
