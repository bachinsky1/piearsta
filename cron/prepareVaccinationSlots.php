#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Cron used to collect and prepare free time slots info for vaccination (should be scheduled to run every 15 min)

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

prepareVaccinationSlots($cfg);

exit;

?>