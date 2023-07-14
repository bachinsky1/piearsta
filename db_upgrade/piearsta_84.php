<?php

/*
 *
 * !!!!!!!!!!!!!!!   ATTENTION: this script should be run only once!!!   !!!!!!!!!!!!!!!
 *
 */


// Adds 20 slots for cancelation reasons (Admin->SiteSetup->SiteData->Cancellation reasons tab) to ad_sitedata table
// And add some predefined values (reasons) to ad_sitedata_values


require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

$idsArray = array();

echo PHP_EOL . 'Upgrading db' . PHP_EOL;

$valuesArr = array(
    'Ne saņēmu pieraksta apstiprinājumu',
    'Netieku uz šo laiku',
    'Pierakstījos uz citu datumu vai laiku',
    'Pierakstījos pie cita specialista',
    'Esmu saslimusi',
    'Ārkārtās situācijas dēļ',
    'Kļūdains pieraksts',
    'Karantīns',
);

for($i = 1; $i < 22; $i++) {

    if($i == 21) {
        $name = 'other';
        $block = 'other';
    } else {
        $name = 'cancReason_' . $i;
        $block = 'Reason_' . $i;
    }

    $sql = "INSERT INTO `ad_sitedata`
                    (`name`, `tab`, `block`, `title`, `type`, `mlang`, `mcountry`, `required`, `validation`, `callback`, `sort`) 
                VALUES
                    ('" . $name . "', 'Cancellation reasons', '" . $block . "', '', 'text', 0, 0, 0, NULL, NULL, '')";
    $query = new query($mdb, $sql);

    $idsArray[$i] = $fid = $mdb->get_insert_id();

    $value = '';
    if($i == 21) {
        $value = 'Cits';
    } else {

        if(array_key_exists(($i-1), $valuesArr)) {
            $value = $valuesArr[$i - 1];
        }
    }

    $valSql = "INSERT INTO `ad_sitedata_values`
                    (fid, lang, `value`, country) 
                VALUES 
                    (".$fid.", 'lv', '".$value."', 0)";
    $query = new query($mdb, $valSql);
}

echo PHP_EOL . 'Records inserted: ' . count($idsArray) . PHP_EOL;
echo PHP_EOL . 'Script finished.' . PHP_EOL;

exit;

?>