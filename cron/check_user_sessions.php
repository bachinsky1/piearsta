#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

//

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// This cron performs check sessions for activity and sets canceled to inactive sessions

// limit of sessions can be active simultaneously for one user
$limit = $cfg->get('cron_sessions_limit');

// session lifetime (stored in milliseconds, so we divide it by 1000 to get seconds)
$sessLifetime = ($cfg->get('sessionTimeout') / 1000) + 300;

// consider session expired if last_used time < than time() - sessLifetime
$expireTime = date(PIEARSTA_DT_FORMAT, time() - $sessLifetime);

// session record to be deleted if last_used time is more than twice older than sessLifetime
$deleteTime = date(PIEARSTA_DT_FORMAT, time() - ($sessLifetime * 2));

$sessionsToDelete = array();
$idsToDelete = array();
$sessionsToCancel = array();
$idsToCancel = array();

// collect session records to be deleted
$dbQuery = "SELECT * FROM mod_users_sessions 
            WHERE 
                last_used < '" . $deleteTime . "' LIMIT " . $limit;
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    $sessionsToDelete = $query->getArray();
    if(count($sessionsToDelete) > 0) {
        $idsToDelete = array_column($sessionsToDelete, 'id');
    }
}

// collect session records to be marked as expired
$dbQuery = "SELECT * FROM mod_users_sessions 
            WHERE
                is_canceled = 0 AND 
                last_used < '" . $expireTime . "' LIMIT " . $limit;
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    $sessionsToCancel = $query->getArray();
    if(count($sessionsToCancel) > 0) {
        $idsToCancel = array_column($sessionsToCancel, 'id');
    }
}

// now perform delete and update

if(count($idsToDelete) > 0) {

    $dbQuery = "DELETE FROM mod_users_sessions WHERE id IN (" . implode(',', $idsToDelete) . ")";
    doQuery($mdb, $dbQuery);
}

if(count($idsToCancel) > 0) {

    $dbQuery = "UPDATE mod_users_sessions 
            SET
                is_canceled = 1,
                cancelation_reason = " . SESSION_CANCEL_EXPIRED . " 
            WHERE id IN (" . implode(',', $idsToCancel) . ")";
    doQuery($mdb, $dbQuery);
}


// if debug allowed we print the report of cron job

if($debug) {

    echo PHP_EOL . 'Sessions to delete:' . PHP_EOL;
    print_r($sessionsToDelete);
    echo PHP_EOL . 'Sessions to cancel:' . PHP_EOL;
    print_r($sessionsToCancel);
    echo PHP_EOL . '+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
    echo PHP_EOL . 'Sessions to delete ids:' . PHP_EOL;
    print_r($idsToDelete);
    echo PHP_EOL . 'Sessions to cancel ids:' . PHP_EOL;
    print_r($idsToCancel);
    echo PHP_EOL;
    echo PHP_EOL . 'Records deleted: ' . count($idsToDelete) . PHP_EOL;
    echo PHP_EOL . 'Records updated: ' . count($idsToCancel) . PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL . 'Cron finished.' . PHP_EOL;
    echo PHP_EOL;
}

exit;

?>