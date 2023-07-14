#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$st = time();
$pid = getmypid();
$method = 'SMFailedBatchesProcessor';
$maxExecTime = $cfg->get('vbSmUploadCronMaxExecutionTime');

$data = array(
    'sys_process_id' => $pid,
    'method' => $method,
    'params' => '',
    'status' => '1',
    'start_time' => date('Y-m-d H:i:s', time()),
    'expiration_time' => date('Y-m-d H:i:s', (time() + $maxExecTime)),
    'error_message' => '',
);

$cronLogId = saveValuesInDb('vaccination_cron_log', $data);

/** @var vaccinationRequests $vaccinationRequests */
$vaccinationRequests = loadLibClass('vaccinationRequests');

$message = '';
$logData = array();

// collect new batches and send to SM

try {

    $processFailedRes = $vaccinationRequests->processFailedBatches();

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
}

$logData = !empty($processFailedRes['logData']) ? $processFailedRes['logData'] : 'No log data';

$status = '1';

if(!$processFailedRes['success']) {
    $status = '2';
    $message = $processFailedRes['error_message'];
}

$data = array(
    'status' => '2',
    'exec_status' => $status,
    'end_time' => date('Y-m-d H:i:s', time()),
    'exec_time' => (time() - $st),
    'error_message' => is_array($message) ? json_encode($message) : $message,
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

if(DEBUG) {
    var_dump('cron finished!');
}

exit;
