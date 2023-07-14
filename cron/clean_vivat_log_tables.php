#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Watchdog used to clean vaccination tables

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;


// get config params for all vaccination-related tables to be cleaned

// vivat_cache_log
$storeVivatCacheLogFor = $cfg->get('storeVivatCacheLogFor');
$vivatCacheLogCleanerLimit = $cfg->get('vivatCacheLogCleanerLimit');

// vivat_cache_upload_log
$storeVivatCacheUploadLogFor = $cfg->get('storeVivatCacheUploadLogFor');
$vivatCacheLogUploadCleanerLimit = $cfg->get('vivatCacheLogUploadCleanerLimit');

// vivat_booking_requests
$storeVivatBookingRequestsFor = $cfg->get('storeVivatBookingRequestsFor');
$vivatBookingRequestsCleanerLimit = $cfg->get('vivatBookingRequestsCleanerLimit');


// vivat_auth_tokens
$storeVivatAuthTokensFor = $cfg->get('storeVivatAuthTokensFor');
$vivatAuthTokensCleanerLimit = $cfg->get('vivatAuthTokensCleanerLimit');

// vaccination_cron_log
$storeVaccinationCronLogFor = $cfg->get('storeVaccinationCronLogFor');
$vaccinationCronLogCleanerLimit = $cfg->get('vaccinationCronLogCleanerLimit');

// sm_booking_batches
$storeSmBookingBatchesFor = $cfg->get('storeSmBookingBatchesFor');
$smBookingBatchesCleanerLimit = $cfg->get('smBookingBatchesCleanerLimit');



// Perform cleaning

$stat = array();

// 1. vivat_cache_log

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeVivatCacheLogFor * 24 *60 * 60) ));
$limit = $vivatCacheLogCleanerLimit ? ' LIMIT ' . $vivatCacheLogCleanerLimit : '';

$dbQuery = "DELETE FROM vivat_cache_log WHERE generation_end < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

$stat['vivat_cache_log'] = $query->affected_rows() . ' records deleted.';

// 2. vivat_cache_upload_log

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeVivatCacheUploadLogFor * 24 *60 * 60) ));
$limit = $vivatCacheLogUploadCleanerLimit ? ' LIMIT ' . $vivatCacheLogUploadCleanerLimit : '';

$dbQuery = "DELETE FROM vivat_cache_upload_log WHERE end_time < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

$stat['vivat_cache_upload_log'] = $query->affected_rows() . ' records deleted.';

// 3. vivat_booking_requests

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeVivatBookingRequestsFor * 24 *60 * 60) ));
$limit = $vivatBookingRequestsCleanerLimit ? ' LIMIT ' . $vivatBookingRequestsCleanerLimit : '';

$dbQuery = "DELETE FROM vivat_booking_requests WHERE request_datetime < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

$stat['vivat_booking_requests'] = $query->affected_rows() . ' records deleted.';

// 4. vivat_auth_tokens

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeVivatAuthTokensFor * 24 *60 * 60) ));
$limit = $vivatAuthTokensCleanerLimit ? ' LIMIT ' . $vivatAuthTokensCleanerLimit : '';

$dbQuery = "DELETE FROM vivat_auth_tokens WHERE expired_at < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

$stat['vivat_auth_tokens'] = $query->affected_rows() . ' records deleted.';

// 5. vaccination_cron_log

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeVaccinationCronLogFor * 24 *60 * 60) ));
$limit = $vaccinationCronLogCleanerLimit ? ' LIMIT ' . $vaccinationCronLogCleanerLimit : '';

$dbQuery = "DELETE FROM vaccination_cron_log WHERE expiration_time < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

$stat['vaccination_cron_log'] = $query->affected_rows() . ' records deleted.';

// 6. sm_booking_batches

$dateToCleanLogsBefore = date(PIEARSTA_DT_FORMAT, (time() - ($storeSmBookingBatchesFor * 24 *60 * 60) ));
$limit = $smBookingBatchesCleanerLimit ? ' LIMIT ' . $smBookingBatchesCleanerLimit : '';

$dbQuery = "DELETE FROM sm_booking_batches WHERE end_time < '" . $dateToCleanLogsBefore . "'" . $limit;
$query = new query($mdb, $dbQuery);

$stat['sm_booking_batches'] = $query->affected_rows() . ' records deleted.';


// Finishing

if($debug) {
    echo ' ' . PHP_EOL;
    var_dump($stat);
    echo ' ' . PHP_EOL;
    echo 'Cron finished' . PHP_EOL;
}

exit;

?>