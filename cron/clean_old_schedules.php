#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

$libCleanOldData = loadLibClass('CleanOldData', true, '', 'cleanOldData');

$result = $libCleanOldData->deleteSchedulesOlderThen();

if (DEBUG)
{
    echo 'Deleted schedules count: ' . $result['data']['affectedRowsDeleteSchedules'] . PHP_EOL;
    echo 'Deleted schedules-temp count: ' . $result['data']['affectedRowsDeleteSchedulesTemp'] . PHP_EOL;
}