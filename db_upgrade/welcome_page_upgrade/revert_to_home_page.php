<?php

/*
 * This script reverts welcome page upgrade changes
 */

require_once(dirname(__FILE__) . "/../../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// get languages
$langDbQuery = "SELECT * FROM ad_languages";
$langQuery = new query($mdb, $langDbQuery);

echo PHP_EOL . 'Langs found: ' . $langQuery->num_rows() . PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;

// make upgrade for each language

while($row = $langQuery->getrow()) {

    $lang = $row['lang'];
    $langId = $row['id'];

    echo PHP_EOL . 'Processing lang: ' . $lang . PHP_EOL;

    $dbQuery = "SELECT * FROM ad_content WHERE title = 'Welcome' AND lang = '" . $lang . "'";
    $query = new query($mdb, $dbQuery);

    // check if upgrade needed

    if(!$query->num_rows()) {
        echo PHP_EOL . 'ATTENTION! No welcome page for lang = '.$lang.'!' . PHP_EOL;
        continue;
    }

    /*
     * START REVERT
     */

    $currWelcomePageRecord = $query->getrow();
    $currWelcomePageId = $currWelcomePageRecord['id'];

    // delete welcome page
    deleteFromDbById('ad_content', $currWelcomePageId);

    // get current home page
    $dbQuery = "SELECT * FROM ad_content WHERE title = 'Home' AND lang = '" . $lang . "'";
    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {

        $currendHomeRecord = $query->getrow();
        $currHomeId = $currendHomeRecord['id'];

        // update home page with mirror_id = id

        $updData = array(
            'parent_id' => 0,
            'url' => $lang . '/',
            'mirror_id' => $currHomeId,
        );

        saveValuesInDb('ad_content', $updData, $currHomeId);

        // set parent_id to home id for those pages that have welcome page as parent currently

        $updDbQuery = "UPDATE ad_content 
                    SET
                        parent_id = " . $currHomeId . "
                    WHERE
                        parent_id = $currWelcomePageId";

        $updQuery = new query($mdb, $updDbQuery);

        // set home page as default page for this language

        $mpDbQuery = "UPDATE ad_languages_to_ct 
                            SET
                                main_id = ".$currHomeId." 
                            WHERE
                                lang_id = " . $langId;

        $mpQuery = new query($mdb, $mpDbQuery);

        echo PHP_EOL . 'Welcome page for lang = '.$lang.' deleted.' . PHP_EOL;
        echo PHP_EOL . 'Parent of home welcome children set to home page id.' . PHP_EOL;
        echo PHP_EOL . '----------------------------------------------------------------' . PHP_EOL;
        echo PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL . 'Revert finished' . PHP_EOL;

exit;

?>