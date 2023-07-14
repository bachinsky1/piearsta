#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../vendor/autoload.php");
define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// This cron is for creating vrooms for the reservations
//

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = $cfg->get('debug');

error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));

/** @var consultation $consObj */
$consObj = loadLibClass('consultation');

if($debug) {

    echo PHP_EOL . PHP_EOL . '---- Cron "vrooms_create" started ----' . PHP_EOL . PHP_EOL;
}

// 1. Get all reservation with start_time in the future and vroom_create_required flag = 1

$time = date(PIEARSTA_DT_FORMAT, time());
$limit = $cfg->get('vrooms_check_limit');

$resIds = array();

$dbQuery = "SELECT r.id, r.status  
            FROM mod_reservations r  
            WHERE 1  
                AND r.doctor_id IS NOT NULL
                AND r.start >  '$time'
                AND r.status IN (0,2)
                AND r.vroom_create_required = 1 
            ORDER BY r.start
            LIMIT $limit";

$query = new query($mdb, $dbQuery);

$num = 0;
$sucessCount = 0;


// 2. If found -- process reservations (create vrooms)

if($query->num_rows()) {

    $num = $query->num_rows();

    if($debug) {
        echo PHP_EOL . "Reservations found: $num" . PHP_EOL;
        echo "processing..." . PHP_EOL . PHP_EOL;
    }

    $resIds = $query->getArray();

    foreach ($resIds as $res) {

        $resId = $res['id'];

        if($debug) {
            echo "   -- reservation ID = $resId" . PHP_EOL;
        }

        $response = $consObj->createVroom($resId);
        $result = json_decode($response['result'], true);

        if(!empty($response['success'])) {

            $data = array(
                'vroom_create_required' => 0,
            );

            saveValuesInDb('mod_reservations', $data, $resId);

            if($res['status'] == 2) {
                $consObj->confirmVroom($resId);
            }

            $sucessCount++;
        }

        if($debug) {
            echo '      success: ' . (!empty($response['success']) ? 'true' : 'false');
            echo PHP_EOL . PHP_EOL;
        }
    }


} else {

    if($debug) {
        echo PHP_EOL . "No reservation to process." . PHP_EOL;
    }
}

if ($debug) {
    echo PHP_EOL . "Reservations processed successfully $sucessCount from $num." . PHP_EOL;
    echo PHP_EOL . PHP_EOL . '****Cron finished****' . PHP_EOL . PHP_EOL;
}

exit;

?>


