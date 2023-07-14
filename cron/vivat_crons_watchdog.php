<?php

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

if($debug) {
    var_dump('Running process killer');
}

//$method = 'CleanCronLogData';

// We set killed status (3) to expired cron logs and try to kill processes

$dbQuery = "SELECT * 
            FROM vaccination_cron_log FORCE INDEX (exp_time_status) 
            WHERE 
                  status IN (0,1) AND
                  expiration_time <= '" . date('Y-m-d H:i:s', time()) . "'";

$query = new query($mdb, $dbQuery);

if($query->num_rows()) {

    if($debug) {
        var_dump('Logs to kill processes: ' . $query->num_rows());
    }

    while ($logData = $query->getrow()) {

        // try to kill the process

        shell_exec("kill -9 " . intval($logData['sys_process_id']));

        // update cron log

        $data = array(
            'status' => '3',
            'exec_status' => '1',
        );

        saveValuesInDb('vaccination_cron_log', $data, $logData['id']);
    }
}

if($debug) {
    var_dump('Cron finished!');
}

exit;
