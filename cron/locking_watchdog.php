#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Watchdog used to clean locks and reservations

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$watchdogLimit = $cfg->get('watchdog_limit');


// 1. Find all lock records expired

$dbQuery =  "SELECT sl.id AS lock_id,
                    sl.status AS lock_status,
                    sl.expire_time AS expire_time,
                    sl.slots AS slots,
                    sl.datetime_from AS start_time,
                    sl.datetime_thru AS end_time,
                    sl.clinic_id AS clinic_id,
                    sl.doctor_id AS doctor_id,
                    r.id AS reservation_id,
                    r.order_id AS order_id,
                    r.status AS reservation_status,
                    o.status AS order_status,
                    o.payment_reference AS payment_reference,
                    t.id AS transaction_id,
                    t.status AS transaction_status
                FROM " . $cfg->getDbTable('shedule', 'lock') . " sl
                LEFT JOIN " . $cfg->getDbTable('reservations', 'self') . " r ON (sl.reservation_id = r.id)
                LEFT JOIN " . $cfg->getDbTable('orders', 'self') . " o ON (r.order_id = o.id)
                LEFT JOIN " . $cfg->getDbTable('transactions', 'self') . " t ON (o.id = t.order_id)
                    WHERE 1
                        AND sl.expire_time <=  '" . date(PIEARSTA_DT_FORMAT) . "'
                    LIMIT " . $watchdogLimit;

$query = new query($mdb, $dbQuery);


// 2. Collect lock_ids, reservation_ids, order_ids, slots_ids for expired records

$dataCollected = array();

$locksExpired = array();
$reservationsToDelete = array();
$ordersToDelete = array();
$slotsToFree = array();


// additionally we collect ids of transactions which status should be set to NON PAID

$transactionsNonPaid = array();

if($query->num_rows()) {

    echo $query->num_rows() . " records found\n";


    while ($row = $query->getrow()) {

        $dataCollected[] = $row;

        // delete expired locks
        $locksExpired[$row['lock_id']] = $row['lock_id'];

        // get slots
        $sotsArr = array();
        $slotsArr = getSlots($row['start_time'], $row['end_time'], $row['doctor_id'], $row['clinic_id'], true);

        foreach ($slotsArr as $slot) {
            // collect only unique slots ids to optimize query
            if(!in_array($slot, $slotsToFree)) {
                $slotsToFree[$row['lock_id']] = $slot;
            }
        }

        // if paid service selected and reservation with order created

        // we touch only waiting for payment reservations and unfinished reservations
        if($row['reservation_status'] == RESERVATION_WAITS_PAYMENT || $row['reservation_status'] == RESERVATION_WAITS_PATIENT_CONFIRMATION) {

            // mark reservation as deleted
            $reservationsToDelete[$row['lock_id']] = $row['reservation_id'];

            // order was not created -- break occurred due to error or unexpected exit
            // we delete lock and reservation
            if($row['order_id']) {

                // mark order as deleted
                $ordersToDelete[$row['lock_id']] = $row['order_id'];

                if(
                    $row['order_status'] == ORDER_STATUS_PENDING ||
                    $row['order_status'] == ORDER_STATUS_NON_PAID
                ) {
                    // order was send to payment but lock expired
                    // we set transaction status to NON PAID (5)
                    if (!empty ($row['transaction_id'])) {
                        $transactionsNonPaid[$row['lock_id']] = $row['transaction_id'];
                    }
                }
            }
        }
    }
} else {

    if($debug) {
        echo PHP_EOL . "No lock records found." . PHP_EOL;
    }
}


// 3. Delete lock records, set canceled status to resrvations and orders, free slots


 // debug output

if($debug) {

    echo PHP_EOL . "Data collected:" . PHP_EOL;
    print_r($dataCollected);
    echo PHP_EOL . "Locks expired:" . PHP_EOL;
    print_r($locksExpired);
    echo PHP_EOL . "Reservations to delete:" . PHP_EOL;
    print_r($reservationsToDelete);
    echo PHP_EOL . "Orders to delete:" . PHP_EOL;
    print_r($ordersToDelete);
    echo PHP_EOL . "Slots to free:" . PHP_EOL;
    print_r($slotsToFree);
    echo PHP_EOL . "Transactions to NON PAID:" . PHP_EOL;
    print_r($transactionsNonPaid);
    echo PHP_EOL . ' ';
}

// delete locks expired

if(count($locksExpired) > 0) {

    $dbQuery = "DELETE FROM " . $cfg->getDbTable('shedule', 'lock') . " WHERE id IN(" . implode(',', $locksExpired) . ")";
    doQuery($mdb, $dbQuery);
}


// free slots

if(count($slotsToFree) > 0) {

    $dbQuery = "UPDATE " . $cfg->getDbTable('shedule', 'self') . " 
            SET locked = 0 
                WHERE 1 
                    AND id IN(" . implode(',', $slotsToFree) . ")";
    doQuery($mdb, $dbQuery);
}


// reservations set canceled with reason '@/toBeDeleted' and sended set to 1

if(count($reservationsToDelete) > 0) {

    // we should now to mark these reservation as 3 status and sended = 0
    // to allow SM to get them and cancel these res internally

    $dbQuery = "UPDATE " . $cfg->getDbTable('reservations', 'self') . " 
            SET status = " . RESERVATION_ABORTED_BY_USER . ",
                status_reason = '@/toBeDeleted',
                status_changed_at = '" . time() . "',
                updated = '" . time() . "',
                cancelled_by = 'cancelled by watchdog as unfinished res',
                cancelled_at = '" . date(PIEARSTA_DT_FORMAT, time()) . "',
                sended = '0' 
                WHERE 1 
                    AND id IN(" . implode(',', $reservationsToDelete) . ")";

    doQuery($mdb, $dbQuery);
}


// delete orders

if(count($ordersToDelete) > 0) {

    // we don't delete it actually, but set canceled status
    $dbQuery = "UPDATE " . $cfg->getDbTable('orders', 'self') . " 
                SET status = " . ORDER_STATUS_NON_PAID . ",
                    status_reason = 'Canceled by watchdog',
                    status_datetime = '" . date(PIEARSTA_DT_FORMAT, time()) . "' 
                WHERE 1 
                    AND id IN(" . implode(',', $ordersToDelete) . ")";
    doQuery($mdb, $dbQuery);
}


// set transactions to NON PAID status

if(count($transactionsNonPaid) > 0) {

    $dbQuery = "UPDATE " . $cfg->getDbTable('transactions', 'self') . " 
                SET status = " . TRANSACTION_STATUS_NON_PAID . " 
                WHERE 1 
                    AND id IN(" . implode(',', $transactionsNonPaid) . ")";
    doQuery($mdb, $dbQuery);
}


// ============================================================================================== //

/*
 * Second part: clean up reservations
 **/


// 1. Find reservations which have waiting payment status (5) or unfinished status (6) and have no locking record

$dbQuery =  "SELECT sl.id AS lock_id,
                    r.id AS reservation_id,
                    r.hsp_reservation_id AS hsp_reservation_id,
                    r.order_id AS order_id,
                    r.status AS reservation_status,
                    r.sended AS sended,
                    o.status AS order_status,
                    o.payment_reference AS payment_reference,
                    t.id AS transaction_id,
                    t.status AS transaction_status
                FROM " . $cfg->getDbTable('reservations', 'self') . " r
                LEFT JOIN " . $cfg->getDbTable('shedule', 'lock') . " sl ON (sl.reservation_id = r.id)
                LEFT JOIN " . $cfg->getDbTable('orders', 'self') . " o ON (r.order_id = o.id)
                LEFT JOIN " . $cfg->getDbTable('transactions', 'self') . " t ON (o.id = t.order_id)
                    WHERE 1
                        AND (r.status = " . RESERVATION_WAITS_PAYMENT . " OR r.status = " . RESERVATION_WAITS_PATIENT_CONFIRMATION . ") 
                        AND (sl.id IS NULL OR sl.id = '' OR sl.id = 0)
                    LIMIT " . $watchdogLimit;

$query = new query($mdb, $dbQuery);


// 2. Collect reservations to be marked as canceled

$dataCollected = array();
$reservationsToMarkCanceled = array();

if($query->num_rows()) {

    if($debug) {
        echo PHP_EOL . $query->num_rows() . " records found" . PHP_EOL;
    }

    while ($row = $query->getrow()) {

        $dataCollected[] = $row;


        if (empty($row['payment_reference'])) {

            $reservationsToMarkCanceled[] = $row['reservation_id'];

        } else {

            if ($row['order_status'] != ORDER_STATUS_PENDING &&
                $row['order_status'] != ORDER_STATUS_PRELIMINARY_PAID) {
                $reservationsToMarkCanceled[] = $row['reservation_id'];
            }

        }
    }

} else {

    if($debug) {
        echo PHP_EOL . "No records found." . PHP_EOL;
    }
}

// 3. Delete lock records, set canceled status to resrvations and orders, free slots

// debug output

if($debug) {

    echo PHP_EOL . "Data collected:" . PHP_EOL;
    print_r($dataCollected);
    echo PHP_EOL . "Reservations to mark canceled:" . PHP_EOL;
    print_r($reservationsToMarkCanceled);
    echo PHP_EOL . ' ';
}


// reservations set to cancel status with reason '@/toBeDeleted' and sended set to 1

if(count($reservationsToMarkCanceled) > 0) {

    $dbQuery = "UPDATE " . $cfg->getDbTable('reservations', 'self') . "
                SET status = " . RESERVATION_ABORTED_BY_USER . ",
                    status_reason = '@/toBeDeleted',
                    status_changed_at = " . time() . ",
                    updated = " . time() . ",
                    cancelled_at = '" . date(PIEARSTA_DT_FORMAT) . "',
                    cancelled_by = 'cancelled by watchdog as unfinished res',
                    sended = '0'
                WHERE 1
                    AND id IN(" . implode(',', $reservationsToMarkCanceled) . ")";

    doQuery($mdb, $dbQuery);
}


// ============================================================================================== //

/*
 * Third part: delete reservations, marked to be deleted
 **/


// 1. Find reservations marked to be deleted and expired a week ago (start < $timeBefore)

// 7 days
$timeBefore = time() - (7 * 24 * 60 * 60);
$timeBeforeDT = date(PIEARSTA_DT_FORMAT, $timeBefore);

if(DEBUG) {
    echo PHP_EOL . PHP_EOL . 'Searching for reservation to be deleted that expired before '.$timeBeforeDT.' where sent to SM (has sended = 1).' . PHP_EOL;
}

$dbQuery = "SELECT id FROM mod_reservations
            WHERE
                status_reason = '@/toBeDeleted' AND
                hsp_reservation_id IS NULL AND
                sended = 1 AND
                start < '" . date(PIEARSTA_DT_FORMAT, $timeBefore) . "'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {

    $resToDelete = array();

    while ($row = $query->getrow()) {
        $resToDelete[] = $row['id'];
    }

    if(count($resToDelete) > 0) {

        $delQuery = "DELETE FROM mod_reservations WHERE id IN(" . implode(',', $resToDelete) . ")";
        doQuery($mdb, $delQuery);

        if(DEBUG) {
            echo PHP_EOL . $query->num_rows() . " reservations deleted." . PHP_EOL;
        }
    }

} else {

    if(DEBUG) {
        echo PHP_EOL . '0 records found.' . PHP_EOL;
    }
}

if($debug) {
    echo PHP_EOL . PHP_EOL . 'Cron finished' . PHP_EOL;
}

exit;

?>