#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Cron used to collect and prepare free time slots info for vaccination (should be scheduled to run every 15 min)

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

$debug = DEBUG;

// 1 Check number of vaccination points with free slots in mod_shedules
// compare with vp number in vivat_cache_data and vivat_cache_vplist

$dbQuery = "SELECT dtc.vp_id, COUNT(s.id) as slotNumber FROM mod_doctors_to_clinics dtc 
            LEFT JOIN mod_shedules s ON (s.clinic_id = dtc.c_id AND s.doctor_id = dtc.d_id)
            WHERE
                dtc.vp_id IS NOT NULL AND
                s.start_time > '" . date('Y-m-d H:i:s', time()) . "' AND 
                s.booked = 0 AND 
                s.locked = 0
            GROUP BY dtc.vp_id";
$query = new query($mdb, $dbQuery);

$vpCount = 0;

if($query->num_rows()) {
    $resArr = $query->getArray();
    $vpCount = count($resArr);
}

$vpCountInData = 0;

$dbQuery = "SELECT vp_id FROM vivat_cache_data
            GROUP BY vp_id";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    $resArr = $query->getArray();
    $vpCountInData = count($resArr);
}

$vpCountInVpList = 0;

$dbQuery = "SELECT COUNT(vp_id) as vpCount FROM vivat_cache_vplist";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    $resArr = $query->getrow();
    $vpCountInVpList = $resArr['vpCount'];
}
echo PHP_EOL;
echo PHP_EOL . '++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
echo PHP_EOL . 'Checking vaccination points number' . PHP_EOL;
echo PHP_EOL . 'VPs in mod_shedules: '. $vpCount . PHP_EOL;
echo PHP_EOL . 'VPs in vivat_cache_data: '. $vpCountInData . PHP_EOL;
echo PHP_EOL . 'VPs in vivat_cache_vplist: '. $vpCountInVpList . PHP_EOL;

if($vpCount == $vpCountInData && $vpCount == $vpCountInVpList) {
    echo PHP_EOL . 'Result is OK' .  PHP_EOL;
} else {
    echo PHP_EOL . 'Result is FAILED' .  PHP_EOL;
}

// 2 Check number of free slots for specific vaccination point in mod_schedules
// and compare it with data in vivat_cache_data.

echo PHP_EOL;
echo PHP_EOL . '++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
echo PHP_EOL . 'Checking slot count for each vaccination point' . PHP_EOL;

$vpArr = array();
$dbQuery = "SELECT DISTINCT vp_id FROM vivat_cache_vplist";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    while($row = $query->getrow()) {
        $vpArr[] = $row['vp_id'];
    }
}

foreach ($vpArr as $vpId) {

    echo PHP_EOL;
    echo PHP_EOL . '   VP: ' . $vpId . PHP_EOL;
    echo PHP_EOL;

    // from shedules
    $dbQuery = "SELECT COUNT(s.id) as slotsCount FROM mod_shedules s 
                LEFT JOIN mod_doctors_to_clinics dtc ON(dtc.c_id = s.clinic_id AND dtc.d_id = s.doctor_id)
                WHERE 
                      dtc.vp_id = '" . $vpId . "' AND
                      s.start_time > '" . date('Y-m-d H:i:s', time()) . "' AND
                      s.booked = 0 AND
                      s.locked = 0";
    $query = new query($mdb, $dbQuery);

    $slotsFromShedules = 0;

    if($query->num_rows()) {
        $slotsFromShedules = $query->getrow()['slotsCount'];
    }

    $dbQuery = "SELECT SUM(free_slots) as slotsFromData FROM vivat_cache_data
                WHERE vp_id = '" . $vpId . "'";
    $query = new query($mdb, $dbQuery);

    $slotsFromData = 0;

    if($query->num_rows()) {
        $slotsFromData = $query->getrow()['slotsFromData'];
    }

    echo PHP_EOL . '   Slots from mod_shedules: ' . $slotsFromShedules . PHP_EOL;
    echo PHP_EOL . '   Slots from vivat_cache_data: ' . $slotsFromData . PHP_EOL;

    echo PHP_EOL;

    if($slotsFromShedules == $slotsFromData) {
        echo PHP_EOL . '   Result is OK' .  PHP_EOL;
    } else {
        echo PHP_EOL . '   Result is FAILED' .  PHP_EOL;
    }
    echo PHP_EOL . '   -------------------------------' . PHP_EOL;
    echo PHP_EOL;
}


// Check number of free slots for all vaccination points in mod_shedules and compare it
// with total number of free slots in vivat_cache_data and in vivat_cache_log table ( for specific cache_id ).

echo PHP_EOL;
echo PHP_EOL . '++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
echo PHP_EOL . 'Checking slot count for ALL vaccination points' . PHP_EOL;
echo PHP_EOL;

$vpStringArr = array();

foreach ($vpArr as $vpId) {
    $vpStringArr[] = '"' . $vpId . '"';
}

// from shedules
$dbQuery = "SELECT COUNT(s.id) as slotsCount FROM mod_shedules s 
                LEFT JOIN mod_doctors_to_clinics dtc ON(dtc.c_id = s.clinic_id AND dtc.d_id = s.doctor_id)
                WHERE 
                    s.start_time > '" . date('Y-m-d H:i:s', time()) . "' AND 
                    s.booked = 0 AND 
                    s.locked = 0 AND
                    dtc.vp_id IN(" . implode(',', $vpStringArr) . ")";
$query = new query($mdb, $dbQuery);

$slotsAllFromShedules = 0;

if($query->num_rows()) {
    $slotsAllFromShedules = $query->getrow()['slotsCount'];
}

$dbQuery = "SELECT SUM(free_slots) as slotsFromData FROM vivat_cache_data
                WHERE vp_id IN(" . implode(',', $vpStringArr) . ")";
$query = new query($mdb, $dbQuery);

$slotsAllFromData = 0;

if($query->num_rows()) {
    $slotsAllFromData = $query->getrow()['slotsFromData'];
}

$dbQuery = "SELECT total_free_slots FROM vivat_cache_log 
            ORDER BY id DESC 
            LIMIT 1";
$query = new query($mdb, $dbQuery);

$slotsAllFromLog = 0;

if($query->num_rows()) {
    $slotsAllFromLog = $query->getrow()['total_free_slots'];
}

echo PHP_EOL . 'Total free slots from mod_shedules: ' . $slotsAllFromShedules . PHP_EOL;
echo PHP_EOL . 'Total free slots from vivat_cache_data: ' . $slotsAllFromData . PHP_EOL;
echo PHP_EOL . 'Total free slots from vivat_cache_log: ' . $slotsAllFromLog . PHP_EOL;
echo PHP_EOL;

if($slotsAllFromShedules == $slotsAllFromData && $slotsAllFromData == $slotsAllFromLog) {
    echo PHP_EOL . 'Result is OK' .  PHP_EOL;
} else {
    echo PHP_EOL . 'Result is FAILED' .  PHP_EOL;
}

echo PHP_EOL;
echo PHP_EOL;

// finishing test

echo PHP_EOL . '++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
echo PHP_EOL . 'Test finished' . PHP_EOL;

exit;

?>