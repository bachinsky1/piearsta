#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug= DEBUG;

// set status 7 for duplicates having proc status 2

$dbQuery = "SELECT queue_id, COUNT(id) as count FROM vivat_booking_requests
            WHERE processing_status = 2  
            GROUP BY queue_id";

$query = new query($mdb, $dbQuery);

$queueIds = array();

if($query->num_rows()) {

    $queueIds = $query->getArray();
}

$status2dups = 0;

if(!empty($queueIds)) {

    foreach ($queueIds as $qid) {

        if ($qid['count'] < 2) {
            continue;
        }

        $status2dups += intval($qid['count']);

        var_dump('Found proc status 2 duplicates: ' . $qid['count'] . ' for queue id: ' . $qid['queue_id']);

        // get the first record with this queue id

        $firstDbQuery = "SELECT * FROM vivat_booking_requests 
                        WHERE 
                              queue_id = " . $qid['queue_id'] . " AND 
                              processing_status = 2
                          ORDER BY request_datetime ASC
                          LIMIT 1";

        $firstQuery = new query($mdb, $firstDbQuery);

        $firstRecord = $firstQuery->getrow();
        $firstId = $firstRecord['id'];

        var_dump('first id: ' . $firstId);

        // get the latest duplicate

        $reqDbQuery = "SELECT * FROM vivat_booking_requests 
                        WHERE 
                              queue_id = " . $qid['queue_id'] . " AND 
                              processing_status = 2 AND 
                              id <> " . $firstId . "
                          ORDER BY request_datetime DESC
                          LIMIT 1";

        $reqQuery = new query($mdb, $reqDbQuery);

        $reqData = array();

        if($reqQuery->num_rows()) {
            $reqData = $reqQuery->getrow();
        }

        if(!empty($reqData)) {

            // delete all this queue id dups from db

            $delDbQuery = "DELETE FROM vivat_booking_requests 
                            WHERE 
                                  queue_id = " . $qid['queue_id'] . " AND 
                                  processing_status = 2 AND 
                                  id <> " . $firstId;

            doQuery($mdb, $delDbQuery);

            $reqData['count'] = (intval($qid['count']) - 1);
            $reqData['processing_status'] = '7';
            unset($reqData['id']);
            unset($reqData['pk']);

            foreach ($reqData as $k => $v) {

                if ($v == null) {
                    unset($reqData[$k]);
                }
            }

            // save one record instead of all duplicates with updated count

            saveValuesInDb('vivat_booking_requests', $reqData);
        }
    }

    var_dump('Processing status 2 dups finished. ' . $status2dups . ' duplicated records processed.');
}

// get duplicated record's queue_ids

$dbQuery = "SELECT queue_id, COUNT(id) as count FROM vivat_booking_requests
            WHERE processing_status = 7  
            GROUP BY queue_id";

$query = new query($mdb, $dbQuery);

$queueIds = array();

if($query->num_rows()) {

    $queueIds = $query->getArray();
}

$status7dups = 0;

if(!empty($queueIds)) {

    foreach ($queueIds as $qid) {

        if($qid['count'] < 2) {
            continue;
        }

        $status7dups += intval($qid['count']);

        var_dump('Found proc status 7 duplicates: ' . $qid['count'] . ' for queue id: ' . $qid['queue_id']);

        // get the latest of duplicates for given queue id

        $reqDbQuery = "SELECT * FROM vivat_booking_requests 
                        WHERE 
                              queue_id = " . $qid['queue_id'] . " AND 
                              processing_status = 7
                          ORDER BY request_datetime DESC
                          LIMIT 1";

        $reqQuery = new query($mdb, $reqDbQuery);

        $reqData = array();

        if($reqQuery->num_rows()) {
            $reqData = $reqQuery->getrow();
        }

        if(!empty($reqData)) {

            // delete all this queue id dups from db

            $delDbQuery = "DELETE FROM vivat_booking_requests 
                            WHERE 
                                  queue_id = " . $qid['queue_id'] . " AND 
                                  processing_status = 7";

            doQuery($mdb, $delDbQuery);

            $reqData['count'] = $qid['count'];
            unset($reqData['id']);
            unset($reqData['pk']);

            foreach ($reqData as $k => $v) {

                if($v == null) {
                    unset($reqData[$k]);
                }
            }

            // save one record instead of all duplicates with updated count

            saveValuesInDb('vivat_booking_requests', $reqData);
        }
    }

    var_dump('Processing status 7 dups finished. ' . $status7dups . ' duplicated records processed.');
}

var_dump('Script finished');

exit;