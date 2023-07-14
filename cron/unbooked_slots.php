#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Watchdog used to clean locks and reservations

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$slots = array();

$nowTime = date('Y-m-d H:i:s', time());

$startTime = microtime(true);

// 1. Set slots booked for existing reservations

// Оптимизация:
// 1. выбираем из резерваций, а слоты джойним
// 2. Выбираем только id слота, остальное не нужно нам
// 3. Делаем DISTINCT -- избегаем долгой выборки дубликатов слотов для перекрывающихся резерваций
// 4. В джойн предикаты слотов добавляем дату резервации! (уменьшает время выборки в 15-20 раз!)
// Результат: на дев-базе время сократилось с 1.610 сек до 0.139 сек. После добавки даты -- до 0.00632 (!)


    // NEW QUERY

$dbQuery =  "SELECT DISTINCT
                    s.id
                FROM mod_reservations r  
                INNER JOIN mod_shedules s ON (
                    r.doctor_id = s.doctor_id AND 
                    r.clinic_id = s.clinic_id AND  
                    DATE(r.start) = s.date
                ) 
                WHERE 
                    r.`start` >= '".$nowTime."' AND
                    r.status NOT IN ( 1,3 ) AND 
                    r.cancelled_at IS NULL  AND
                    r.cancelled_by IS NULL AND 
                    (s.booked = 0 OR s.booked IS NULL) 
                    AND (
                        (s.start_time >= r.`start` AND s.start_time < r.`end`) OR
                        (s.end_time > r.`start` AND s.end_time <= r.`end`) OR
                        (s.start_time <= r.`start` AND s.end_time >= r.`end`)
                    )
                LIMIT 100";

$query = new query($mdb, $dbQuery);

$elapsedTime = microtime(true) - $startTime;

if($query->num_rows()) {

    while($row = $query->getrow()) {

        $slots[] = $row['id'];
    }
}

// debug output
if($debug) {
    echo PHP_EOL . "Slots found:" . PHP_EOL;
    print_r($slots);
}

// book slots
if(count($slots) > 0) {
    $dbQuery = "UPDATE mod_shedules SET booked = 1 WHERE id IN(" . implode(',', $slots) . ")";
    doQuery($mdb, $dbQuery);
}

if($debug) {
    echo PHP_EOL . count($slots) . ' slots processed.' . PHP_EOL;
    echo PHP_EOL . 'DB query time is ' . $elapsedTime . ' s' . PHP_EOL;
    echo PHP_EOL . 'Cron finished' . PHP_EOL;
}

exit;

?>