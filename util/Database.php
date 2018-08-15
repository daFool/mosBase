<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright Copyright (c) 2017 Mauri Sahlberg, Helsinki
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
namespace mosBase;

/**
 * Eroaa isännästä tallettamalla tiedon tietokannan tyypistä
 * */
class Database extends \PDO
{
    /**
     * @var string $dsn Data source name - connection string
     * */
    private $dsn;
    /**
     * @var string $user Database connection user
     * */
    private $user;
    
    /**
     * @var string $password Database user password
     * */
    private $password;
    
    /**
     * Konstruktori
     * Käytännössä koppaa talteen yhteysparametrit ja kutsuu PDO:ta.
     * */
    public function __construct(string $dsn, string $user, string $password) 
    {
        parent::__construct($dsn, $user, $password);
        $this->dsn=$dsn;
        $this->user=$user;
        $this->password=$password;
    }
    
    /**
     * Purkaa DSN:stä tietokantayhteystyypin
     *
     * @return string Tietokanta-ajurin tyyppi/nimi
     * */
    public function getDatabase() : string 
    {
        return explode(":", $this->dsn)[0];        
    }
}
?>