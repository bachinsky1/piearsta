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

$maxExecTime = $cfg->get('vaccinationUploadSlotsCronsMaxExecTime');

$st = time();
$pid = getmypid();
$method = 'MVUploadCache';

$data = array(
    'sys_process_id' => $pid,
    'method' => $method,
    'params' => json_encode(array()),
    'status' => '1',
    'start_time' => date('Y-m-d H:i:s', time()),
    'expiration_time' => date('Y-m-d H:i:s', (time() + $maxExecTime)),
    'error_message' => '',
);

$cronLogId = saveValuesInDb('vaccination_cron_log', $data);

/** @var UploadSlots $libUploadSlots */
$libUploadSlots = loadLibClass('UploadSlots', true, '', 'vivat');

$params = array();

try {

    $result = $libUploadSlots->uploadSlots($params);

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

$status = (isset($result['status']) && $result['status']) ? $result['status'] : '2';

$msg = (isset($result['msg']) && $result['msg']) ? $result['msg'] : '';

if($status == '2') {

    if($msg == '') {
        $msg = 'Unknown error';
    }

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

// File log
if($cfg->get('vaccinationJobsFileLog') === true) {

    $fileName = $method . '_' . date('Y-m-d_H_i', time()) . '.log';
    $logData = $result['logData'] ? $result['logData'] : 'Error. No log data!';

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

if ($debug)
{
    echo 'Cache id: ' . (!empty($result['localData']['cacheId']) ? $result['localData']['cacheId'] : 'NULL') . PHP_EOL;
    echo 'Upload attempts: ' . $result['uploadAttempts'] . PHP_EOL;
    echo 'Upload attempts success: ' . $result['uploadAttemptsSuccess'] . PHP_EOL;
}

$data = array(
    'status' => '2',
    'exec_status' => $status,
    'end_time' => date('Y-m-d H:i:s', time()),
    'exec_time' => (time() - $st),
    'error_message' => is_array($msg) ? json_encode($msg) : $msg,
);

saveValuesInDb('vaccination_cron_log', $data, $cronLogId);

exit;
