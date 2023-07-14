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
$maxRows = 10000;

if(empty($argv[1])) {

    if(DEBUG) {
        var_dump('Argument (cronjob ID) missing. Cron finished.');
    }

    exit;
}

/* 
$maxRows = $defaultMaxRows;
if($argv[2]) $maxRows = (int)$argv[2];
if(empty($maxRows)) $maxRows = $defaultMaxRows;
*/

$maxExecTime = $cfg->get('vbSmUploadCronMaxExecutionTime');

$st = time();
$cronId = $argv[1];
$pid = getmypid();
$method = 'SMProcessBookingRequests';

$vbSmUploadMaxRunningJobs = $cfg->get('vbSmUploadMaxRunningJobs');
$maxRunningJobs = $cfg->get('maxRunningJobs');

// check for maximum in-progress/prepared rows to process (non final statuses)
//if(!empty($maxRows)) {
//    $isMaxRowsLimit = maxProcessingExceeded($cronId,$maxRows);
//    if($isMaxRowsLimit) exit;
//}

$dbQuery = "SELECT * FROM sm_vaccination_booking_cronjobs
            WHERE id = " . $cronId;

$query = new query($mdb, $dbQuery);

if($query->num_rows()) {

    $flagFileName = $method . '_cronJob_' . $cronId;

    // get cron params from db by cron id from argument

    $row = $query->getrow();

    $batchSize = $row['batch_size'];
    $clinicList = $row['clinic_list'];
    $excludedList = $row['excluded_clinics'];
    $cronUniqueId = time() . '_' . $cronId;

    $params = json_encode(array(
        'clinic_list' => $row['clinic_list'],
        'excluded_clinics' => $row['excluded_clinics'],
    ));

    // run the job!

    $paramsForIns = json_encode(array(
        'clinic_list' => $clinicList,
        'excluded_clinics' => $excludedList,
    ));

    $startTime = date('Y-m-d H:i:s', time());
    $expTime = date('Y-m-d H:i:s', (time() + $maxExecTime));

    $logInsDbQuery = "INSERT INTO vaccination_cron_log 
                        (sys_process_id, method, params, cronjob_id, status, start_time, expiration_time, error_message) 
                        VALUES 
                        (".$pid.", '".$method."', '".$paramsForIns."', ".$cronId.", 1, '".$startTime."', '".$expTime."', '')";

    $logInsQuery = new query($mdb, $logInsDbQuery);

    $cronLogId = $mdb->get_insert_id();

    $clinicArray = null;

    if($clinicList != '*') {
        $clinicArray = explode(',', $clinicList);
    }

    $excludedArray = null;

    if(!empty($excludedList)) {
        $excludedArray = explode(',', $excludedList);
    }

    /** @var vaccinationRequests $vaccinationRequests */
    $vaccinationRequests = loadLibClass('vaccinationRequests');

    $message = '';
    $logData = array();

    // collect new batches and send to SM

    try {

        $processRes = $vaccinationRequests->processRequests($clinicArray, $excludedArray, $batchSize, $cronId);

    } catch (Exception $e) {

        $processRes = array(
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

    $logData = !empty($processRes['logData']) ? $processRes['logData'] : 'No log data';

    $status = '1';
    $resultMessage = !empty($processRes['result_message']) ? $processRes['result_message'] : '';

    if(!$processRes['success']) {
        $status = '2';
        $message = $processRes['error_message'];
        $error = true;
    }

    // Monitoring flags

    if($error) {
        $flag->critical_error($flagFileName);
    } elseif ($warning) {
        $flag->warning($flagFileName);
    } else {
        $flag->ok($flagFileName);
    }

    $data = array(
        'status' => '2',
        'exec_status' => $status,
        'end_time' => date('Y-m-d H:i:s', time()),
        'exec_time' => (time() - $st),
        'error_message' => is_array($message) ? json_encode($message) : $message,
        'result_message' => $resultMessage,
    );

    saveValuesInDb('vaccination_cron_log', $data, $cronLogId);

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

} else {

    if(DEBUG) {
        var_dump('No cronjob to run!');
    }
}

if(DEBUG) {
    var_dump('cron finished!');
}

//shell_exec("kill -9 " . intval($pid));
exit;