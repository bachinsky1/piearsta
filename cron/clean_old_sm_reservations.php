#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Clean old SM reservations ( older than NOW() - $config['store_SM_reservations_days'] (days) )

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// 1. Set archive status to reservations

$now = new DateTime();
$dt =  $now->sub(DateInterval::createFromDateString(2 . ' days'));
$time = strtotime($dt->format(PIEARSTA_DT_FORMAT));

if(DEBUG) {
    echo 'Clean old reservations cron started.' . PHP_EOL;
}

$dbQuery = "UPDATE mod_reservations r
            SET
                r.`status_before_archive` = r.status,
                r.`status` = 4,
                r.`status_changed_at` = '" . time() . "'
            WHERE
                r.`status` <> 4 AND
                r.`start` IS NOT NULL AND
                r.`start` <= '" . date(PIEARSTA_DT_FORMAT, time()) . "'";

if(DEBUG) {
    echo 'First step -- move to archive.' . PHP_EOL;
}

doQuery($mdb, $dbQuery);

if(DEBUG) {
    echo 'First step complete.' . PHP_EOL;
}

// 2. Get old SM reservations

if(DEBUG) {
    echo 'Second step -- clean old SM reservations started.' . PHP_EOL;
}

$reservationsToDelete = array();

// calc time
$daysToStoreReservations = $cfg->get('store_SM_reservations_days');
$now = new DateTime();
$dt =  $now->sub(DateInterval::createFromDateString($daysToStoreReservations . ' days'));
$time = $dt->format(PIEARSTA_DT_FORMAT);

$dbQuery =  "SELECT *
                FROM mod_reservations  
                WHERE 
                    `start` <= '" . $time . "' AND
                    `profile_id` IS NULL
                LIMIT 1000";

$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    while($row = $query->getrow()) {
        $reservationsToDelete[] = $row['id'];
    }
}

// debug output
if($debug) {
    echo "Reservations found:\n";
    print_r($reservationsToDelete);
}


// delete old reservations
if(count($reservationsToDelete) > 0) {
    $dbQuery = "DELETE FROM mod_reservations WHERE id IN(" . implode(',', $reservationsToDelete) . ")";
    doQuery($mdb, $dbQuery);
}

if(DEBUG) {
    echo 'Second step complete.' . PHP_EOL;
}

if(DEBUG) {

    echo '\n' . count($reservationsToDelete) . ' reservations deleted.';
    echo '\n' . 'Cron finished';
}

exit;

?>