#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

//

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// This cron collects homepage items from classifier and clinics and counts doctors with schedules for each item
// It cleans up item titles to remove punctuation and so on from title start, to ensure correct alphabet ordering

fillHomepageItemsTable();

exit;

?>