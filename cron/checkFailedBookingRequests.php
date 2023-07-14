#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

//

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// This cron checks failed booking requests and updates their statuses
//

// first we select records with non-empty queue id -- create requests
// with 3 or 2 status and having duplicates with 7 status
// or with 7 status and not having dups in other statuses

// then collect ids of selected records

$time = date(PIEARSTA_DT_FORMAT, (time() - 30*60));

$bookingRequestsUpdated = 0;
$bookingRequestsDupsDeleted = 0;

 $dbQuery = "SELECT DISTINCT r1.id, r1.vp_id, r1.queue_id, r1.request_datetime
                    FROM vivat_booking_requests r1 
                    WHERE
                          r1.queue_id IS NOT NULL AND
                          r1.request_datetime <= '".$time."' AND 
                          r1.appointment_time_from > '".date(PIEARSTA_DT_FORMAT, time())."' AND
                          ( 
                              (r1.processing_status = 7 AND EXISTS(SELECT r2.id FROM vivat_booking_requests r2 WHERE r2.queue_id = r1.queue_id AND r2.processing_status IN (2,3))) OR 
                              (r1.processing_status = 7 AND NOT EXISTS(SELECT r3.id FROM vivat_booking_requests r3 WHERE r3.queue_id = r1.queue_id AND r3.processing_status IN(0,1,2,3))) OR 
							  (r1.processing_status = 3  AND EXISTS(SELECT r5.id FROM vivat_booking_requests r5 WHERE r5.queue_id = r1.queue_id AND r5.processing_status =2))
                          ) AND 
                          r1.request_datetime = (
                            SELECT MAX(r4.request_datetime) FROM vivat_booking_requests r4
                            WHERE
                                r4.queue_id = r1.queue_id AND 
                                r4.queue_id IS NOT NULL AND
                                r4.request_datetime <= '".$time."' AND 
                                r4.appointment_time_from > '".date(PIEARSTA_DT_FORMAT, time())."' AND
                                ( 
                                    (r4.processing_status = 7 AND EXISTS(SELECT rr2.id FROM vivat_booking_requests rr2 WHERE rr2.queue_id = r4.queue_id AND rr2.processing_status IN (2,3))) OR 
                                    (r4.processing_status = 7 AND NOT EXISTS(SELECT rr3.id FROM vivat_booking_requests rr3 WHERE rr3.queue_id = r4.queue_id AND rr3.processing_status IN(0,1,2,3))) OR
									(r4.processing_status = 3 AND EXISTS(SELECT rr5.id FROM vivat_booking_requests rr5 WHERE rr5.queue_id = r4.queue_id AND rr5.processing_status = 2))
                                )
                          )";

$query = new query($mdb, $dbQuery);

$ids = array();
$fullArr = array();

if($query->num_rows()) {

    while ($row = $query->getrow()) {
        $ids[] = $row['id'];
        $fullArr[] = $row;
    }
}

$vpIds = array();
//echo '<pre>'; print_R($fullArr); die($dbQuery);
foreach ($fullArr as $rec) {

    if(!in_array($rec['vp_id'], $vpIds)) {
        $vpIds[] = $rec['vp_id'];
    }
}

foreach ($vpIds as $vpId) {

    $clDbQuery = "
        SELECT d2c.c_id, d2c.vp_id, c.* FROM mod_doctors_to_clinics d2c
        LEFT JOIN mod_clinics c ON (c.id = d2c.c_id)
        WHERE
            d2c.vp_id = '$vpId'
    ";

    $clQuery = new query($mdb, $clDbQuery);

    if($clQuery->num_rows()) {

        $clinic = $clQuery->getrow();

        // url check
        $isAvailable = isDomainAvailible($clinic['api_url']);

        if($isAvailable) {

            // update statuses to all records with this vpId where id in collected array

            $updDbQuery = "
                UPDATE vivat_booking_requests
                SET
                    processing_status = 0,
                    batch_id = NULL
                WHERE
                    vp_id = '$vpId' AND 
                    id IN (".implode(',', $ids).")
            ";

            $updQuery = new query($mdb, $updDbQuery);

            $bookingRequestsUpdated = $updQuery->affected_rows();

            // delete all other records with these queue id
/*
            $delDbQuery = "
                DELETE FROM vivat_booking_requests
                WHERE
                    queue_id IN ( (
                        SELECT rr.queue_id FROM (
                            SELECT DISTINCT r.queue_id FROM vivat_booking_requests r 
                            WHERE 
                                  r.id IN (" . implode(',', $ids) . ") AND 
                                  r.processing_status = 0
                      ) as rr
                    ) ) AND 
                    id NOT IN (" . implode(',', $ids) . ") AND processing_status !=7
            ";

            $delQuery = new query($mdb, $delDbQuery);

            $bookingRequestsDupsDeleted = $delQuery->affected_rows();*/
        }
    }
}


// Proceed with delete requests processing

$delRequestsUpdated = 0;
$delRequestsDupsDeleted = 0;

 $dbQuery = "SELECT DISTINCT r1.id, r1.vp_id, r1.aiis_record_id, r1.request_datetime
                    FROM vivat_booking_requests r1 
                    WHERE
                          r1.queue_id IS NULL AND
                          r1.aiis_record_id IS NOT NULL AND
                          r1.request_datetime <= '".$time."' AND 
                          r1.appointment_time_from > '".date(PIEARSTA_DT_FORMAT, time())."' AND
                          ( 
                              (r1.processing_status = 7 AND EXISTS(SELECT r2.id FROM vivat_booking_requests r2 WHERE r2.aiis_record_id = r1.aiis_record_id AND r2.processing_status IN (2,3))) OR 
                              (r1.processing_status = 7 AND NOT EXISTS(SELECT r3.id FROM vivat_booking_requests r3 WHERE r3.aiis_record_id = r1.aiis_record_id AND r3.processing_status IN(0,1,2,3))) OR 
							  (r1.processing_status = 3  AND EXISTS(SELECT r5.id FROM vivat_booking_requests r5 WHERE r5.aiis_record_id = r1.aiis_record_id AND r5.processing_status =2))
                          ) AND 
                          r1.request_datetime = (
                            SELECT MAX(r4.request_datetime) FROM vivat_booking_requests r4
                            WHERE
                                r4.aiis_record_id = r1.aiis_record_id AND 
                                r4.aiis_record_id IS NOT NULL AND
                                r4.request_datetime <= '".$time."' AND 
                                r4.appointment_time_from > '".date(PIEARSTA_DT_FORMAT, time())."' AND
                                ( 
                                    (r4.processing_status = 7 AND EXISTS(SELECT rr2.id FROM vivat_booking_requests rr2 WHERE rr2.aiis_record_id = r4.aiis_record_id AND rr2.processing_status IN (2,3))) OR 
                                    (r4.processing_status = 7 AND NOT EXISTS(SELECT rr3.id FROM vivat_booking_requests rr3 WHERE rr3.aiis_record_id = r4.aiis_record_id AND rr3.processing_status IN(0,1,2,3))) OR
									(r4.processing_status = 3 AND EXISTS(SELECT rr5.id FROM vivat_booking_requests rr5 WHERE rr5.aiis_record_id = r4.aiis_record_id AND rr5.processing_status = 2))
                                )
                          )";

$query = new query($mdb, $dbQuery);

$delIds = array();
$fullDelArr = array();

if($query->num_rows()) {

    while ($row = $query->getrow()) {
        $delIds[] = $row['id'];
        $fullDelArr[] = $row;
    }
}

$vpIds = array();

foreach ($fullDelArr as $rec) {

    if(!in_array($rec['vp_id'], $vpIds)) {
        $vpIds[] = $rec['vp_id'];
    }
}

foreach ($vpIds as $vpId) {

    $clDbQuery = "
        SELECT d2c.c_id, d2c.vp_id, c.* FROM mod_doctors_to_clinics d2c
        LEFT JOIN mod_clinics c ON (c.id = d2c.c_id)
        WHERE
            d2c.vp_id = '$vpId'
    ";

    $clQuery = new query($mdb, $clDbQuery);

    if($clQuery->num_rows()) {

        $clinic = $clQuery->getrow();

        // url check
        $isAvailable = isDomainAvailible($clinic['api_url']);

        if($isAvailable) {

            // update statuses to all records with this vpId where id in collected array

            $updDbQuery = "
                UPDATE vivat_booking_requests
                SET
                    processing_status = 0,
                    batch_id = NULL
                WHERE
                    vp_id = '$vpId' AND 
                    id IN (".implode(',', $delIds).")
            ";

            $updQuery = new query($mdb, $updDbQuery);

            $delRequestsUpdated = $updQuery->affected_rows();

            // delete all other records with these queue id
/*
            $delDbQuery = "
                DELETE FROM vivat_booking_requests
                WHERE
                    aiis_record_id IN ( (
                        SELECT rr.aiis_record_id FROM (
                            SELECT DISTINCT r.aiis_record_id FROM vivat_booking_requests r 
                            WHERE 
                                  r.id IN (" . implode(',', $delIds) . ") AND 
                                  r.processing_status = 0
                      ) as rr
                    ) ) AND 
                    id NOT IN (" . implode(',', $delIds) . ") AND processing_status != 7
            ";

            $delQuery = new query($mdb, $delDbQuery);

            $delRequestsDupsDeleted = $delQuery->affected_rows();
*/			
        }
    }
}


if($debug) {

    echo PHP_EOL;
    echo PHP_EOL . 'Processed create requests number: ' . $bookingRequestsUpdated . PHP_EOL;
    echo PHP_EOL . 'Сreate requests duplicates deleted: ' . $bookingRequestsDupsDeleted . PHP_EOL;

    if($bookingRequestsUpdated > 0) {
        echo PHP_EOL . 'Vivat create booking requests statuses changed to be processed' . PHP_EOL;
    }

    echo PHP_EOL . '-----------------------------------------------------------------' . PHP_EOL;
    echo PHP_EOL . 'Processed cancel requests number: ' . $delRequestsUpdated . PHP_EOL;
    echo PHP_EOL . 'Cancel requests duplicates deleted: ' . $delRequestsDupsDeleted . PHP_EOL;

    if($delRequestsUpdated > 0) {
        echo PHP_EOL . 'Vivat cancel booking requests statuses changed to be processed' . PHP_EOL;
    }

    echo PHP_EOL . '-----------------------------------------------------------------' . PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL . 'Cron finished.' . PHP_EOL;
    echo PHP_EOL;
}

exit;

?>
