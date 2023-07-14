#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Clean old SM reservations ( older than NOW() - $config['store_SM_reservations_days'] (days) )

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$slotsToUnbook = array();

// 1. Get some slots to unbook

$dbQuery =  "SELECT  
                    s.id, 
                    s.doctor_id, 
                    s.clinic_id, 
                    s.start_time,
                    s.end_time,
                    r.created,
                    r.`start`,
                    r.end
                FROM mod_shedules s  
                INNER JOIN mod_reservations r ON (
                    r.doctor_id = s.doctor_id AND 
                    r.clinic_id = s.clinic_id
                ) 
                WHERE 
                    r.`start` >= NOW() AND
                    s.start_time >= now() AND 
                    r.status NOT IN ( 1,3 ) AND 
                    r.cancelled_at IS NULL AND
                    r.cancelled_by IS NULL  
                    AND (
                        (s.start_time >= r.`start` AND s.start_time < r.`end`) OR
                        (s.end_time > r.`start` AND s.end_time <= r.`end`) OR
                        (s.start_time <= r.`start` AND s.end_time >= r.`end`)
                    )
                    AND s.booked = 1
                LIMIT 100";

$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    while($row = $query->getrow()) {
        $slotsToUnbook[] = $row['id'];
    }
}

// debug output
if($debug) {
    echo "Slots to unbook:\n";
    print_r($slotsToUnbook);
}

// unbook slots
if(count($slotsToUnbook) > 0) {
    $dbQuery = "UPDATE mod_shedules SET booked = 0 WHERE id IN(" . implode(',', $slotsToUnbook) . ")";
    doQuery($mdb, $dbQuery);
}

if($debug) {

    echo '\n' . count($slotsToUnbook) . ' slots unbooked.';
    echo '\n' . 'Cron finished';
}

exit;

?>