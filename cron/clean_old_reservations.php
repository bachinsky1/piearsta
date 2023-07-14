#!/usr/bin/php-cgi
<?php

// ---
// Note: clean_old_sm_reservations.php step-2 also delete old reservations
//      It has simpler WRERE profile_id, uses field start not end, uses 1000 limit,
//      selects all data up to limit of 1000 rows not just id, has no tableCopy functionality
// ---

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

$libCleanOldData = loadLibClass('CleanOldData', true, '', 'cleanOldData');

$result = $libCleanOldData->deleteReservationsOlderThenWithoutProfileId();

if (DEBUG)
{
    echo 'Deleted reservations count: ' . $result['data']['affectedRowsDeleteReservations'] . PHP_EOL;
}
