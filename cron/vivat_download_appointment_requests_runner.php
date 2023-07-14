#!/usr/bin/php-cgi
<?php

function logDebugNote($message)
{
    echo $message . "\n";

    // Don't log anything:
    return;

    file_put_contents
    (
        '/projects/projects/piearsta2015/cron/_dummy.log',
        date('Y-m-d H:i:s') . ' - ' . __FILE__ . ' - ' . getmypid() . ' - ' . $message . "\n",
        FILE_APPEND
    );
}

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");
require_once(dirname(__FILE__) . "/../system/func/_pid_exec_funcs.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// for debug!!!! TODO: remove after debugging!!!
$debug = true;
// // //


$interval = $cfg->get('vaccinationCronsRunInterval');
$maxExecTime = $cfg->get('vaccinationDownloadAppointmentsCronsMaxExecTime');
$vaccinationDownloadAppointmentsRunInterval = intval($cfg->get('vaccinationDownloadAppointmentsRunInterval'));


$runnersDbQuery = "SELECT * FROM vivat_runners_log 
                    WHERE 
                          runner = 'vivat_download_appointment_requests_runner.php' 
                    ORDER BY last_run DESC
                    LIMIT 1";

$runnersQuery = new query($mdb, $runnersDbQuery);

if($runnersQuery->num_rows()) {

    $lastRunnerData = $runnersQuery->getrow();

    // check if last running script is still running by its PID
    // if it is -- we just exit

    $pid = $lastRunnerData['pid'];

    exec('ps -p ' . $pid, $output, $result_code);

    if ($result_code === 0)
    {
        logDebugNote('Exiting, because cron runner already is running (with PID ' . $pid . ').');
        exit;

    } else {

        $runnerUpdateData = array(
            'status' => '2'
        );

        saveValuesInDb('vivat_runners_log', $runnerUpdateData, $lastRunnerData['id']);
    }
}

// no active runner there, so we proceed
logDebugNote('Starting download appointments runner');

$runnerPid = getmypid();

$runnerData = array(
    'runner' => 'vivat_download_appointment_requests_runner.php',
    'last_run' => date('Y-m-d H:i:s', time()),
    'pid' => $runnerPid,
);

$runnerLogId = saveValuesInDb('vivat_runners_log', $runnerData);

$iterations = 0;

// init runner control
$runner = new execScript(getmypid(),20,true);
// allow only one instance of this command
$runner->setOnlyOneCmd(true);
$runner->checkStats(false);
// type of stop SIG
$runner->setStopSIG(15);
// max exec time
$runner->setMaxExecTime($maxExecTime);

while(true) {

    $iterations++;

    $dtBefore = date('Y-m-d H:i:s', (time() - $interval) );

    // get crons that wasn't run last 15 min

    $dbQuery = "SELECT * FROM vaccination_vivat_cronjobs
                WHERE 
                    cron_script = 'vivat_download_appointment_requests.php'";

    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {

        // run all found crons in separate processes

        while($row = $query->getrow()) {

//            $duplicateCheckCommand = 'pgrep -f "vivat_download_appointment_requests.php ' . intval($row['id']) . '"';
//
//            logDebugNote('Executing: ' . $duplicateCheckCommand);
//
//            if (empty(exec($duplicateCheckCommand)) === false)
//            {
//                logDebugNote('Command "vivat_download_appointment_requests.php ' . intval($row['id']) . '" already running. Skipping.');
//                sleep(1);
//                continue;
//            }

            // cron accepts cron job id as argument

            $arg = escapeshellarg($row['id']);

            // construct shell execution command

            $currDir = dirname(__FILE__) . '/';

//            $cmd = "php " . $currDir . "vivat_download_appointment_requests.php " . intval($row["id"]) . " > /dev/null & echo $!";
            $cmd = "php " . $currDir . "vivat_download_appointment_requests.php " . intval($row["id"]);

            // run in shell

            $pid = $runner->run($cmd);

            var_dump('Run: ' . $cmd . ', pid: ' . $pid);

//            if($result_code !== 0) {
//
//                if(DEBUG) {
//                    var_dump('Cron id = ' . $row['id'] . ' cannot be started!');
//                }
//
//                continue;
//            }

            $dbUpdQuery = "UPDATE vaccination_vivat_cronjobs
                            SET
                                last_run = '".date('Y-m-d H:i:s', time())."'
                            WHERE
                                id = " . mres($row['id']);
            doQuery($mdb, $dbUpdQuery);
        }

    } else {

        if($debug) {
            var_dump('No cronjobs scheduled to run.');
        }
    }

    // runner finished (cron last run will be updated in appropriate processes we just run)

    $runnerUpdateData = array(
        'last_activity' => date('Y-m-d H:i:s', time()),
        'status' => '1'
    );

    saveValuesInDb('vivat_runners_log', $runnerUpdateData, $runnerLogId);

    if($debug) {
        var_dump('Runner loop finished: ' . $iterations);
    }

    sleep($vaccinationDownloadAppointmentsRunInterval);
}