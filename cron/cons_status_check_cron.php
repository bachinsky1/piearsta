#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../vendor/autoload.php");
define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// This cron is for check whether the user has attended the consultation or not

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = $cfg->get('debug');

error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));

$time = date(PIEARSTA_DT_FORMAT);

// We get all the consultations from yesterday
global $mdb;

$dbQuery = "SELECT r.id AS reservation_id,
       r.created AS reservation_created,
       r.hsp_reservation_id AS hsp_id, 
       r.start AS start,
       r.show_up AS show_up,
       r.service_type AS type
                FROM " . $cfg->getDbTable('reservations', 'self') . " r 
                    WHERE 1 
                    AND r.service_type = 1
                    AND r.show_up IS NULL
                    AND r.start BETWEEN CURDATE() - INTERVAL 1 DAY AND CURDATE()
                    ORDER BY r.start";

$query = new query($mdb, $dbQuery);

$dataCollected = array();

if ($query->num_rows()) {

    if ($debug) {
        echo PHP_EOL . PHP_EOL . '****Cron started****' . PHP_EOL;
        echo $query->num_rows() . ' : reservations found' . PHP_EOL;
    }

    while ($row = $query->getrow()) {

        $dataCollected[] = $row;

    }
} else {

    if ($debug) {
        echo PHP_EOL . PHP_EOL . '****Cron started****' . PHP_EOL;
        echo PHP_EOL . PHP_EOL . 'No reservations found' . PHP_EOL;
    }
}


if ($debug) {
    echo PHP_EOL . PHP_EOL . '=====================================' . PHP_EOL;
    echo PHP_EOL . 'Consultations collected to check status:  ' . PHP_EOL;
    print_r($dataCollected);
}


$consultationsToUpdate = array();

if (!empty($dataCollected)) {

    /** @var db $db */
    $db = &loadLibClass('db');
    $vroomDbConnection = $cfg->get("vroom")['db'];

    if ($vroomDbConnection) {

        $db->open(
            $vroomDbConnection['db_database'],
            $vroomDbConnection['db_host'],
            $vroomDbConnection['db_username'],
            $vroomDbConnection['db_password']
        );

        if ($db->connect_id) {
            foreach ($dataCollected as $consultation) {

                $dbQuery = "SELECT vr.pareservationId AS pa_reservation_id,
       vc.status AS videochat_status,
       vc.dcDurationDocPat AS duration
                FROM vrooms vr
                LEFT JOIN videochats vc ON (vr.id = vc.vroomId) 
                    WHERE 1
                    AND vr.pareservationId = " . $consultation['reservation_id'];

                $query = new query($db, $dbQuery);

                if ($query->num_rows()) {

                    while ($row = $query->getrow()) {


                        $minDuration = !empty($cfg->get('minConsultationDuration')) ?
                            $cfg->get('minConsultationDuration') : 120;


                        if ($row['duration'] >= $minDuration) {

                            $consultationsToUpdate[] = $row;
                        }
                    }
                }
            }
        }
        $db->close();
    }
}

/** @var db $mdb */
$mdb = &loadLibClass('db');
$mdb->open(
        $cfg->get("db_db"),
        $cfg->get("db_host"),
        $cfg->get("db_user"),
        $cfg->get("db_password")
);

$updated = [];

if (!empty($consultationsToUpdate)) {

    foreach ($consultationsToUpdate as $consultation) {

        /** @var reservation $res */
        $res = loadLibClass('reservation');
        $res->setReservation($consultation['pa_reservation_id']);
        $reservationToUpdate = $res->getReservation();

        if ($reservationToUpdate){
            $data = [
                'show_up' => true
            ];

            $res->updateReservation($consultation['pa_reservation_id'], $data);
            $updated[] = $consultation['pa_reservation_id'];
        }
    }
}


if ($debug) {
    echo PHP_EOL . PHP_EOL . '=====================================' . PHP_EOL;
    echo PHP_EOL . 'Updated reservations id`s:  ' . PHP_EOL;
    print_r($updated);
    echo PHP_EOL . '****Cron finished****' . PHP_EOL;
}