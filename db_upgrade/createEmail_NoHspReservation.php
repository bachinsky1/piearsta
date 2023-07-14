<?php


/*
 * ATTENTION!!! This script should be run only ONCE!
 *
 * This creates new email template and subject in ad_sitedata
 * After creating these settings it is possible to edit their values via Piearsta admin Site setup -> Site data -> Reservation
 *
 * */

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

global $mdb;

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_NoHspReservation'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_NoHspReservation_free', 'Reservation', 'Email body (no hsp FREE SLOT)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_NoHspReservation_paid', 'Reservation', 'Email body (no hsp PAID SLOT)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);
}

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailSubject_NoHspReservation'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create subject');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailSubject_NoHspReservation', 'Reservation', 'Email subject (reservation has no hsp_reservation_id)', '', 'text', 1, 0, 0)";
    doQuery($mdb, $dbQuery);
}


var_dump('Done!');

exit;

