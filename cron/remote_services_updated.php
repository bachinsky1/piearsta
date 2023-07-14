#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// script to fill mod_remote_services from mod_classificator_info:

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$dbQuery =  "TRUNCATE TABLE mod_remote_services;";
$query = new query($mdb, $dbQuery);

$dbQuery = "INSERT INTO mod_remote_services (service_id) 
	SELECT c_id FROM mod_classificators_info
		WHERE 
			LOWER(title) LIKE '%attalinat%' OR
			LOWER(title) LIKE '%attālināt%' OR
			LOWER(title) LIKE '%attālinat%' OR
			LOWER(title) LIKE '%attalināt%'";
$count = doQuery($mdb, $dbQuery);

// debug output
if($debug) {
    echo PHP_EOL . "Remote services Updated: " . $count . PHP_EOL;
    echo 'Cron finished' . PHP_EOL;
}

exit;

?>