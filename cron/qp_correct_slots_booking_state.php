<?php

// PIEARSTA-320 - Improve query performance

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

$libCleanOldData = loadLibClass('CleanOldData', true, '', 'cleanOldData');

$params = array(
    'debugUseTableCopy' => true,
    'debugUseAllRowsNotJustUpcoming' => false,
    'clinicId' => 1124,
    'doctorId' => 1767,
);
$result = $libCleanOldData->qpCorrectSlotsBookingState($params);

if (DEBUG)
{
    echo 'Old query affected rows: ' . $result['data']['oldQueryAffectedRows'] . PHP_EOL;
    echo 'New query affected rows: ' . $result['data']['newQueryAffectedRows'] . PHP_EOL;
    echo 'Old query execution time: ' . $result['data']['oldQueryExecutionTime'] . PHP_EOL;
    echo 'New query execution time: ' . $result['data']['newQueryExecutionTime'] . PHP_EOL;
    echo 'New Query is faster by: ' . $result['data']['newQueryIsFasterBy'] . PHP_EOL;
}
