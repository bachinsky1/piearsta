#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

/** @var monitoringFlags $flag */
$flag = loadLibClass('monitoringFlags');
$error = false;
$warning = false;

$debug = DEBUG;

$maxExecTime = $cfg->get('vbSmUploadCronMaxExecutionTime');

$vbSmUploadRetryMaxRunningJobs = $cfg->get('vbSmUploadRetryMaxRunningJobs');
$maxRunningJobs = $cfg->get('maxRunningJobs');

$st = time();
$pid = getmypid();
$method = 'SMRetryBookingRequests';


// Run retry method

$paramsForIns = json_encode(array());
$startTime = date('Y-m-d H:i:s', time());
$expTime = date('Y-m-d H:i:s', (time() + $maxExecTime));

$logInsDbQuery = "INSERT INTO vaccination_cron_log 
                        (sys_process_id, method, params, status, start_time, expiration_time, error_message) 
                        VALUES 
                        (".$pid.", '".$method."', '".$paramsForIns."', 1, '".$startTime."', '".$expTime."', '')";

$logInsQuery = new query($mdb, $logInsDbQuery);

$cronLogId = $mdb->get_insert_id();


/** @var vaccinationRequests $vaccinationRequests */
$vaccinationRequests = loadLibClass('vaccinationRequests');

// check and perform retries attempts

$message = '';
$logData = array();

try {

    $retryRes = $vaccinationRequests->checkAndProcessRetries();

} catch (Exception $e) {

    $retryRes = array(
        'logData' => array(
            'Exception' => true,
            'Code' => $e->getCode(),
            'Message' => $e->getMessage(),
            'File' => $e->getFile(),
            'Line' => $e->getLine(),
            'Trace' => $e->getTrace(),
        ),
    );

    $error = true;
}

$logData = !empty($retryRes['logData']) ? $retryRes['logData'] : 'No log data';

$status = '1';

if(!$retryRes['success']) {
    $status = '2';
    $message = is_array($retryRes['error_message']) ? json_encode($retryRes['error_message']) : $retryRes['error_message'];
    $error = true;
}

// Monitoring flags

if($error) {
    $flag->critical_error($method);
} elseif ($warning) {
    $flag->warning($method);
} else {
    $flag->ok($method);
}

// Update db cron log

$data = array(
    'status' => '2',
    'exec_status' => $status,
    'end_time' => date('Y-m-d H:i:s', time()),
    'exec_time' => (time() - $st),
    'error_message' => $message,
);

saveValuesInDb('vaccination_cron_log', $data, $cronLogId);

// Write log to file

if($cfg->get('vaccinationJobsFileLog') === true) {

    $fileName = $method . '_' . date('Y-m-d_H_i', time()) . '.log';

    $logString = date('Y-m-d H:i:s', time()) . PHP_EOL;
    $logString .= '' . PHP_EOL;
    $logString .= '-------------------------------------------' . PHP_EOL;
    $logString .= '' . PHP_EOL;
    $logString .= json_encode($logData, JSON_PRETTY_PRINT);
    $logString .= '' . PHP_EOL;
    $logString .= '============================================' . PHP_EOL;
    $logString .= '' . PHP_EOL;
    $logString .= '' . PHP_EOL;
    $logString .= '' . PHP_EOL;

    /** @var logFile $log */
    $log = loadLibClass('logFile');

    $log->log($fileName, $logString);
}

if(DEBUG) {
    var_dump('cron finished!');
}

//shell_exec("kill -9 " . intval($pid));
exit;