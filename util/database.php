<?php
/**
 * @author Mauri "mos" Sahlberg <mauri.sahlberg@accountor.fi>
 * @copyright Copyright (c) 2017 Accountor Systems Oy
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */
namespace mosBase;
class database extends \PDO {
    private $dsn;
    private $user;
    private $password;
    
    public function __construct($dsn, $user, $password) {
        parent::__construct($dsn, $user, $password);
        $this->dsn=$dsn;
        $this->user=$user;
        $this->password=$password;
    }
    
    public function getDatabase() {
        list($db, $foo)=explode(":", $this->dsn);
        return $db;
    }
}
?>