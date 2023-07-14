#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Watchdog used to clean locks and reservations

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');


$dbQuery =   "
                SELECT 
                    processing_status,
                    COUNT(id)  as cnt
                FROM 
                    vivat_booking_requests 
                WHERE 
                    processing_status IN(0,3,7)
                GROUP BY 
                    processing_status
                
                UNION 

                SELECT
                    '2' as processing_status,
                    COUNT(id)  as cnt
                FROM 
                    vivat_booking_requests 
                WHERE                    
                    processing_start_datetime >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND
                    processing_status = 2
                
                UNION
                
                SELECT 
                    '1error' AS processing_status,
                    COUNT(id)   as cnt
                FROM 
                    vivat_booking_requests 
                WHERE 
                    processing_end_datetime < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND
                    processing_status = 1
                
                UNION
                
                SELECT 
                    '1' AS processing_status,
                    COUNT(id)   as cnt
                FROM 
                    vivat_booking_requests 
                WHERE 
                    processing_end_datetime >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND
                    processing_status = 1
";
$query = new query($mdb, $dbQuery);
$statusArr = $query->getArray('processing_status');

if(!empty($statusArr)){
    foreach($statusArr as $status => $row){
        file_put_contents('../admin/log/vivat/status'.$status.'.txt',$row['cnt']);
    }
}