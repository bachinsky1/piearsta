<?php

/**
 *  == This script requests a doctor the permission to use his google calendar ==
 *
 * Please ensure, that piearsta gmail account is set up correctly and credentials.json file is present
 * in the piearsta web app root, before run this script
 * If there is no credential.json, please follow this link for instructions:
 * https://developers.google.com/calendar/quickstart/php
 * and complete Step 1 from the manual
 * Choose 'Web server' in 'Configure your OAuth client' window
 * and enter the following URL in 'Authorized redirect URIs':
 * https://andrejs-piearsta.smartmedical.eu/google_oauth (dev environment)
 * https://piearsta.lv/google_oauth (prod environment)
 *
 */

// This is command line script!
if (php_sapi_name() != 'cli') {
    header("HTTP/1.0 404 Not Found");
    exit;
}

define('APP_ROOT', dirname(__FILE__) . '/..');

// Bootstrap Piearsta.lv framework
require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

/** @var array $gApiCfg */
$gApiCfg = $cfg->get('g_api');
$gApiSecret = $gApiCfg['secret'];

$debug = DEBUG;

// Init vendor libs (google api client, etc ...)
require __DIR__ . '/../vendor/autoload.php';

// Ask for clinic / doctor
print '' . PHP_EOL;
print 'Enter clinic ID (on Piearsta): ';
$clinicId = trim(fgets(STDIN));
print 'Enter doctor ID (on Piearsta): ';
$doctorId = trim(fgets(STDIN));
print 'Enter doctor calendar title (primary): ';
$calendarTitle = trim(fgets(STDIN));

$calendarTitle = $calendarTitle ? $calendarTitle : 'primary';

// get doctor data

$dbQuery = "SELECT d.id, d.hsp_resource_id, di.name, di.surname, di.notify_email FROM mod_doctors d
            LEFT JOIN mod_doctors_info di ON (d.id = di.doctor_id) 
            LEFT JOIN mod_doctors_to_clinics d2c ON (d.id = d2c.d_id) 
            WHERE 
                d.id = " . mres($doctorId) . " AND
                d.deleted = 0 AND
                d.enabled = 1 AND
                d2c.c_id = " . mres($clinicId);

$query = new query($mdb, $dbQuery);

// set doctorData if exists or exit
if($query->num_rows()) {
    $doctorData = $query->getrow();
} else {
    print PHP_EOL . 'No such doctor in this clinic!' . PHP_EOL . PHP_EOL;
    exit;
}

print '' . PHP_EOL;
print '' . PHP_EOL;
print 'Doctor found in Piearsta.lv database:' . PHP_EOL;
print '' . PHP_EOL;
var_dump($doctorData);
print '' . PHP_EOL;

// construct json for Client state param
// this data will be received in return url
$stateData = array(
    'apiSecret' => $gApiSecret,
    'clinicId' => $clinicId,
    'doctorId' => $doctorId,
    'doctorData' => $doctorData,
    'calendarTitle' => $calendarTitle,
);

/** @var googleApi $gApi */
$gApi = loadLibClass('googleApi');

try {
    $authUrl = $gApi->getNewAuthUrl($stateData);
} catch (Exception $e) {
    print '' . PHP_EOL;
    print 'Error getting auth url occured:' . PHP_EOL;
    print 'Code:' . PHP_EOL;
    print $e->getCode() . PHP_EOL;
    print 'Message:' . PHP_EOL;
    print $e->getMessage() . PHP_EOL;
    print '' . PHP_EOL;
    print 'Script finished.' . PHP_EOL;
}

$shortenUrl = $gApi->getShortenUrl($authUrl, 'google_calendar_sync_' . md5($clinicId . $doctorId . time()));

print '' . PHP_EOL;
print 'Send the following link to doctor/clinic:' . PHP_EOL;
print '' . PHP_EOL;
print 'Full url:' . PHP_EOL;
print '' . PHP_EOL;
print '' . $authUrl . PHP_EOL;
print '' . PHP_EOL;
print 'Shorten url:' . PHP_EOL;
print '' . PHP_EOL;
print '' . $shortenUrl . PHP_EOL;
print '' . PHP_EOL;
print '' . PHP_EOL;
print 'Script finished.' . PHP_EOL;
print '' . PHP_EOL;

exit;
