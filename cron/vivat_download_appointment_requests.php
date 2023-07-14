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

if(empty($argv[1])) {

    if($debug) {
        var_dump('Argument (cronjob ID) missing. Cron finished.');
    }

    $error = true;

    exit;
}

$maxExecTime = intval($cfg->get('vaccinationDownloadAppointmentsCronsMaxExecTime'));

$st = time();
$cronId = $argv[1];
$pid = getmypid();
$method = 'MVDownloadQueue';

$vaccinationDownloadAppointmentsMaxRunningJobs = $cfg->get('vaccinationDownloadAppointmentsMaxRunningJobs');
$maxRunningJobs = $cfg->get('maxRunningJobs');

$dbQuery = "SELECT * FROM vaccination_vivat_cronjobs
            WHERE id = " . $cronId;

$query = new query($mdb, $dbQuery);

if($query->num_rows()) {

    $flagFileName = $method . '_cronJob_' . $cronId;

    $row = $query->getrow();

    $batchSize = $row['batch_size'];
    $vpList = $row['vp_list'];
    $vpExcluded = $row['vp_excluded'];

    $params = json_encode(array(
        'vp_list' => $row['vp_list'],
        'vp_excluded' => $row['vp_excluded'],
    ));

    // run the job!

    $paramsForIns = json_encode(array(
        'clinic_list' => $vpList,
        'excluded_clinics' => $vpExcluded,
    ));

    $startTime = date('Y-m-d H:i:s', time());
    $expTime = date('Y-m-d H:i:s', (time() + $maxExecTime));

    $logInsDbQuery = "INSERT INTO vaccination_cron_log 
                        (sys_process_id, method, params, cronjob_id, status, start_time, expiration_time, error_message) 
                        VALUES 
                        (".$pid.", '".$method."', '".$paramsForIns."', ".$cronId.", 1, '".$startTime."', '".$expTime."', '')";

    $logInsQuery = new query($mdb, $logInsDbQuery);

    $cronLogId = $mdb->get_insert_id();

    /** @var DownloadAppointmentRequests $libDownloadAppointmentRequests */
    $libDownloadAppointmentRequests = loadLibClass('DownloadAppointmentRequests', true, '', 'vivat');

    $params = array();

    $vpArray = array();
    $excludedArray = array();

    if(!empty($vpList) && $vpList != '*') {
        $vpArray = explode(',', $vpList);
    }

    if(!empty($vpExcluded)) {
        $excludedArray = explode(',', $vpExcluded);
    }

    try {

        $result = $libDownloadAppointmentRequests->downloadAppointmentRequests($vpArray, $excludedArray, $params);

    } catch (Exception $e) {
        $result = array(
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

    if ($debug)
    {
        echo 'Success: ' . (($result['_continue'] === true) ? 'Yes' : 'No') . PHP_EOL;
        if ($result['_continue'] !== true)
        {
            echo 'Error: ' . $result['_error'] . PHP_EOL;
        }
        echo 'Total booking requests rows inserted: ' . $result['totalBookingRequestsRowsInserted'] . PHP_EOL;
    }

    $status = (isset($result['status']) && $result['status']) ? $result['status'] : '2';

    $msg = (isset($result['msg']) && $result['msg']) ? $result['msg'] : '';

    if($status == '2') {

        if($msg == '') {
            $msg = 'Unknown error';
        }

        $error = true;
    }

    $resultMessage = (isset($result['result_msg']) && !empty($result['result_msg'])) ?
        $result['result_msg'] : '';

    $data = array(
        'status' => '2',
        'exec_status' => $status,
        'end_time' => date('Y-m-d H:i:s', time()),
        'exec_time' => (time() - $st),
        'error_message' => is_array($msg) ? json_encode($msg) : $msg,
        'result_message' => $resultMessage,
    );

    saveValuesInDb('vaccination_cron_log', $data, $cronLogId);

    // Monitoring flags

    if($error) {
        $flag->critical_error($flagFileName);
    } elseif ($warning) {
        $flag->warning($flagFileName);
    } else {
        $flag->ok($flagFileName);
    }

    $logData = !empty($result['logData']) ? $result['logData'] : array('message' => 'No log data');

    // File log
    if($cfg->get('vaccinationJobsFileLog') === true) {
        //
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

    if($debug) {
        var_dump('No cronjob to run!');
    }
}

if($debug) {
    var_dump('Cron finished.');
}

//shell_exec("kill -9 " . intval($pid));
exit;

