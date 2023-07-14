<?php

/*
 * ATTENTION!!! This script should be run only ONCE!
 *
 * This creates new separated by slot types (state or paid) email templates for reservations
 *
 * */

require_once(dirname(__FILE__) . "/../../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

global $mdb;

// // //
// 1. TEMPLATE resMailBody_0_free
// // //

// subject used resMailSubject_0

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_0_free'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body resMailBody_0_free');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_0_free', 'Reservation', 'Email body(status: waiting - FREE slot)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbCheckQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_0_free'";
    $checkQuery = new query($mdb, $dbCheckQuery);

    $id = null;

    if($checkQuery->num_rows()) {
        $row = $checkQuery->getrow();
        $id = $row['id'];
    }

    if($id) {

        $content = file_get_contents(__DIR__ . '/templates/status0_free.html');

        $dbQuery = "INSERT INTO ad_sitedata_values 
                (fid, lang, value, country) 
                VALUES 
                ($id, 'lv', '".mres($content)."', 0)";
        doQuery($mdb, $dbQuery);
    }

} else {

    var_dump('Update body resMailBody_0_free');

    $row = $query->getrow();
    $id = $row['id'];

    $content = file_get_contents(__DIR__ . '/templates/status0_free.html');

    $dbQuery = "UPDATE ad_sitedata_values
                SET
                    lang = 'lv',
                    value = '".mres($content)."',
                    country = 0
                WHERE
                    fid = $id";
    doQuery($mdb, $dbQuery);
}

// // //
// 2. TEMPLATE resMailBody_0_paid
// // //

// subject used resMailSubject_0

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_0_paid'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body resMailBody_0_paid');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_0_paid', 'Reservation', 'Email body(status: waiting - PAID slot)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbCheckQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_0_paid'";
    $checkQuery = new query($mdb, $dbCheckQuery);

    $id = null;

    if($checkQuery->num_rows()) {
        $row = $checkQuery->getrow();
        $id = $row['id'];
    }

    if($id) {

        $content = file_get_contents(__DIR__ . '/templates/status0_paid.html');

        $dbQuery = "INSERT INTO ad_sitedata_values 
                (fid, lang, value, country) 
                VALUES 
                ($id, 'lv', '".mres($content)."', 0)";
        doQuery($mdb, $dbQuery);
    }

} else {

    var_dump('Update body resMailBody_0_paid');

    $row = $query->getrow();
    $id = $row['id'];

    $content = file_get_contents(__DIR__ . '/templates/status0_paid.html');

    $dbQuery = "UPDATE ad_sitedata_values
                SET
                    lang = 'lv',
                    value = '".mres($content)."',
                    country = 0
                WHERE
                    fid = $id";
    doQuery($mdb, $dbQuery);
}

// // //
// 3. TEMPLATE resMailBody_2_free
// // //

// subject used resMailSubject_2

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_2_free'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body resMailBody_2_free');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_2_free', 'Reservation', 'Email body(Status: accepted - FREE slot)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbCheckQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_2_free'";
    $checkQuery = new query($mdb, $dbCheckQuery);

    $id = null;

    if($checkQuery->num_rows()) {
        $row = $checkQuery->getrow();
        $id = $row['id'];
    }

    if($id) {

        $content = file_get_contents(__DIR__ . '/templates/status2_free.html');

        $dbQuery = "INSERT INTO ad_sitedata_values 
                (fid, lang, value, country) 
                VALUES 
                ($id, 'lv', '".mres($content)."', 0)";
        doQuery($mdb, $dbQuery);
    }

} else {

    var_dump('Update body resMailBody_2_free');

    $row = $query->getrow();
    $id = $row['id'];

    $content = file_get_contents(__DIR__ . '/templates/status2_free.html');

    $dbQuery = "UPDATE ad_sitedata_values
                SET
                    lang = 'lv',
                    value = '".mres($content)."',
                    country = 0
                WHERE
                    fid = $id";
    doQuery($mdb, $dbQuery);
}

// // //
// 4. TEMPLATE resMailBody_2_paid
// // //

// subject used resMailSubject_2

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_2_paid'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body resMailBody_2_paid');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_2_paid', 'Reservation', 'Email body(Status: accepted - PAID slot)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbCheckQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_2_paid'";
    $checkQuery = new query($mdb, $dbCheckQuery);

    $id = null;

    if($checkQuery->num_rows()) {
        $row = $checkQuery->getrow();
        $id = $row['id'];
    }

    if($id) {

        $content = file_get_contents(__DIR__ . '/templates/status2_paid.html');

        $dbQuery = "INSERT INTO ad_sitedata_values 
                (fid, lang, value, country) 
                VALUES 
                ($id, 'lv', '".mres($content)."', 0)";
        doQuery($mdb, $dbQuery);
    }

} else {

    var_dump('Update body resMailBody_2_paid');

    $row = $query->getrow();
    $id = $row['id'];

    $content = file_get_contents(__DIR__ . '/templates/status2_paid.html');

    $dbQuery = "UPDATE ad_sitedata_values
                SET
                    lang = 'lv',
                    value = '".mres($content)."',
                    country = 0
                WHERE
                    fid = $id";
    doQuery($mdb, $dbQuery);
}

// // //
// 5. TEMPLATE resMailBody_NoHspReservation_free
// // //

// Create subject (if not exists), that is common for both free and paid noHsp templates

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

// body

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_NoHspReservation_free'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body resMailBody_NoHspReservation_free');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_NoHspReservation_free', 'Reservation', 'Email body (no hsp FREE SLOT)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbCheckQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_NoHspReservation_free'";
    $checkQuery = new query($mdb, $dbCheckQuery);

    $id = null;

    if($checkQuery->num_rows()) {
        $row = $checkQuery->getrow();
        $id = $row['id'];
    }

    if($id) {

        $content = file_get_contents(__DIR__ . '/templates/reservationNoHsp_free.html');

        $dbQuery = "INSERT INTO ad_sitedata_values 
                (fid, lang, value, country) 
                VALUES 
                ($id, 'lv', '".mres($content)."', 0)";
        doQuery($mdb, $dbQuery);
    }

} else {

    var_dump('Update body resMailBody_NoHspReservation_free');

    $row = $query->getrow();
    $id = $row['id'];

    $content = file_get_contents(__DIR__ . '/templates/reservationNoHsp_free.html');

    $dbQuery = "UPDATE ad_sitedata_values
                SET
                    lang = 'lv',
                    value = '".mres($content)."',
                    country = 0
                WHERE
                    fid = $id";
    doQuery($mdb, $dbQuery);
}

// // //
// 6. TEMPLATE resMailBody_NoHspReservation_paid
// // //

// Check if such template already exists
// Create one if not

$dbQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_NoHspReservation_paid'";
$query = new query($mdb, $dbQuery);

if(!$query->num_rows()) {

    var_dump('Create body resMailBody_NoHspReservation_paid');

    $dbQuery = "INSERT INTO ad_sitedata 
                (name, tab, block, title, type, mlang, mcountry, required) 
                VALUES 
                ('resMailBody_NoHspReservation_paid', 'Reservation', 'Email body (no hsp PAID SLOT)', '', 'textarea', 1, 0, 0)";
    doQuery($mdb, $dbQuery);

    $dbCheckQuery = "SELECT id FROM ad_sitedata WHERE tab = 'Reservation' AND name = 'resMailBody_NoHspReservation_paid'";
    $checkQuery = new query($mdb, $dbCheckQuery);

    $id = null;

    if($checkQuery->num_rows()) {
        $row = $checkQuery->getrow();
        $id = $row['id'];
    }

    if($id) {

        $content = file_get_contents(__DIR__ . '/templates/reservationNoHsp_paid.html');

        $dbQuery = "INSERT INTO ad_sitedata_values 
                (fid, lang, value, country) 
                VALUES 
                ($id, 'lv', '".mres($content)."', 0)";
        doQuery($mdb, $dbQuery);
    }

} else {

    var_dump('Update body resMailBody_NoHspReservation_paid');

    $row = $query->getrow();
    $id = $row['id'];

    $content = file_get_contents(__DIR__ . '/templates/reservationNoHsp_paid.html');

    $dbQuery = "UPDATE ad_sitedata_values
                SET
                    lang = 'lv',
                    value = '".mres($content)."',
                    country = 0
                WHERE
                    fid = $id";
    doQuery($mdb, $dbQuery);
}


var_dump('Done!');

exit;

