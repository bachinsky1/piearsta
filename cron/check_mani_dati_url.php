#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

//

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// This cron checks if maniDati url is available -- run it once a minute

// get maniDati url from config
$url = $cfg->get('maniDatiUrl');
$isAvailable = isDomainAvailible($url);
$flagFile = AD_SRV_ROOT . '/md_available';

if($isAvailable) {

    if(!file_exists($flagFile)) {
        file_put_contents($flagFile, base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw=='));
    }

} else {

    if(file_exists($flagFile)) {
        unlink($flagFile);
    }

}


if($debug) {

    echo PHP_EOL . 'Path: ' . AD_SRV_ROOT . PHP_EOL;

    if($isAvailable) {
        echo PHP_EOL . 'maniDatiUrl ' . $url . ' is available.' . PHP_EOL;
    } else {
        echo PHP_EOL . 'maniDatiUrl ' . $url . ' is NOT available.' . PHP_EOL;
    }
    echo PHP_EOL;
    echo PHP_EOL . 'Cron finished.' . PHP_EOL;
    echo PHP_EOL;
}

exit;

?>