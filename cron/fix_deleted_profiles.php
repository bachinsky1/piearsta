#!/usr/bin/php-cgi
<?php

// Script used to fix deleted user profiles enable status

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$dbQuery = "SELECT * FROM mod_profiles
            WHERE
                enable > 0 AND 
                (deleted > 0 OR deleted_at > 0)";

$query = new query($mdb, $dbQuery);

$count = $query->num_rows();
$i = 1;

if($count) {

    while ($row = $query->getrow()) {

        $i++;
        $updDbQuery = "UPDATE mod_profiles 
            SET
                email = '" . '*****' . date('Y-m-d H:i:s', time()) . '_' . $i . "',
                enable = 0
            WHERE
                id = " . $row['id'];

        $updQuery = new query($mdb, $updDbQuery);
    }
}

if($debug) {
    echo $count . ' records updated.' . PHP_EOL;
    echo 'Cron finished' . PHP_EOL;
}

exit;

?>