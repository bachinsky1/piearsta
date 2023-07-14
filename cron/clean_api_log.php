#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Watchdog used to clean api log table

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$storeLogsFor = $cfg->get('storeLogsFor');
$limit = $cfg->get('logsCleanerLimit');

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeLogsFor * 24 *60 * 60) ));
$limit = $limit ? ' LIMIT ' . $limit : '';

$dbQuery = "DELETE FROM mod_api_log WHERE created < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

if($debug) {
    echo $query->affected_rows() . ' records deleted.' . PHP_EOL;
    echo 'Cron finished' . PHP_EOL;
}

exit;

?>