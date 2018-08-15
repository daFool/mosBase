<?php
/**
 * Konfiguraatiopoikkeus
 *
 * @category  Util
 * @package   mosBase
 * @author    Mauri "mos" Sahlberg <mauri.sahlberg@gmail.com>
 * @copyright 2018 Mauri Sahlberg, Helsinki
 * @license   MIT https://opensource.org/licenses/MIT
 * @link      www.iki.fi/mos
 */
namespace mosBase;

/**
 * Poikkeuskäsittely
 * */

class DatabaseException extends \Exception
{
    /**
     * @param string $msg   Syy poikkeuksen heittoon
     * @param mixed  $dbobj Mahdollinen tietokantaobjekti: database tai statement
     * @param string $s     Mahdollinen sql-lause
     * */
    public function __construct(string $msg, $dbobj = false, $s = false)
    {
        $this->message=$msg;
        if ($dbobj && method_exists($dbobj, "errorInfo")) {
            $error=$dbobj->errorInfo();
            $m = _("Tietokantaoperaatio '%s' (%s, %s, %s) epäonnistui! %s %s");
            $this->message=sprintf(
                $m,
                $s??UNKNOWN,
                $error[0]??UNKNOWN,
                $error[1]??UNKNOWN,
                $error[2]??UNKNOWN,
                $msg,
                PHP_EOL
            );
        }
    }
}
