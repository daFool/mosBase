<?php
require("util/config.php");

$conf = new mosBase\config();
$conf->init(getenv("mosBaseIni"));
$dbconf = $conf->get("Database");

try {
	$pdo = new mosBase\database($dbconf["dsn"], $dbconf["user"], $dbconf["password"]);
	$log = new mosBase\log("AUDIT", $pdo);
	$log->log("system","startup",__FILE__,__FUNCTION__,__LINE__, "AUDIT");
	/* $keys = array("primary"=>array("id"), "foo"=>array("intti", "merkkijono"));
    $taulu = "testi";*/
	$keys = array(array("primary"=>"tunniste"));
	$taulu="peli";
	$malli = new mosBase\malli($pdo, $log, $taulu, $keys);
	$d=array("tunniste"=>6143);
	$r=$malli->exists($d);
	if($r) {
		var_dump($malli->give());
	}
	
}
catch (PDOException $e) {
	printf(_("Tietokantavirhe: %s\n"),$e->getMessage());
	die();
}

?>