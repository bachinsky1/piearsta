<?php

    /*
     * This upgrade script creates new Welcome page in ad_content table
     * and sets it as a parent for all pages, that were children of current homepage
     */

    require_once(dirname(__FILE__) . "/../../system/config/config.cron.php");
    require_once(dirname(__FILE__) . "/../../system/func/other.func.php");

    /** @var config $cfg */
    $cfg = loadLibClass('config');

    $debug = DEBUG;

    // check welcome page template in table
    // and create if not exists

    $dbQuery = "SELECT * FROM ad_templates WHERE filename = 'welcome-page'";
    $query = new query($mdb, $dbQuery);

    if(!$query->num_rows()) {

        $insDbQuery = "INSERT INTO `ad_templates` (`id`, `filename`, `default`, `translations`) VALUES (14, 'welcome-page', 0, 'a:1:{s:2:\"en\";s:12:\"Welcome page\";}')";
        $insQuery = new query($mdb, $insDbQuery);

        $templId = $mdb->get_insert_id();

    } else {

        $tmplRec = $query->getrow();
        $templId = $tmplRec['id'];
    }

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

        if($query->num_rows()) {
            echo PHP_EOL . 'ATTENTION! Welcome page already exists for lang = '.$lang.'!' . PHP_EOL;
            continue;
        }

        /*
         * START UPGRADE
         */

        // get current home page
        $dbQuery = "SELECT * FROM ad_content WHERE title = 'Home' AND lang = '" . $lang . "'";
        $query = new query($mdb, $dbQuery);

        if($query->num_rows()) {

            $currendHomeRecord = $query->getrow();
            $currHomeId = $currendHomeRecord['id'];

            // create welcome page record

            $insDbQuery = "INSERT INTO `ad_content` 
                        (`parent_id`, `lang`, `url`, `country`, `mirror_id`, `type`, `target`, `template`, `template_color`,
                         `page_title`, `title`, `full_title`, `content`, `image`, `image_alt`, `sitemap`, `changefreq`, 
                         `cache`, `keywords`, `description`, `edit_user`, `edit_date`, `show_contact_block`, `show_info_block`,
                         `show_why_us_block`, `created_user`, `created_date`, `sort`, `enable`, `active`, `blink`, 
                         `show_offers_block`, `admin_email`, `ssl`) 
                        VALUES 
                        (0, '".$lang."', '".$lang."/', 1, 0, 's', '', ".$templId.", NULL, 'Piearsta.lv -- Welcome page', 'Welcome', 
                         '', '', '', '', 1, 'always', 0, 'pacients, e-pieraksti, pierakstīties pie ārsta, medicīnas iestāde, slimnīca, poliklīnika, klīnika, doktorāts, ārsts, ģimenes ārsts, speciālists, privātprakse, meklēšana, meklētājs, ārsta konsultācija, diagnostika, manipulācija, medicīna, veselība, skaistums, valsts apmaksāts, apdrošināšana, apdrošināšanas polise, kuponi, izdevīgi piedāvājumi', 'Pieraksties uz konsultācijām pie ārsta vai cita speciālista uz sev piemērotu laiku ātri, ērti un patstāvīgi tiešsaistē!', 
                         'admin', ".time().", 0, 0, 0, 'admin', ".time().", 27, 1, 1, 0, 0, '', 1);
                    ";

            $insQuery = new query($mdb, $insDbQuery);

            $welcomeId = $mdb->get_insert_id();

            // update welcome page with mirror_id = id
            $updData = array(
                'mirror_id' => $welcomeId,
            );

            saveValuesInDb('ad_content', $updData, $welcomeId);

            // update home page with parent_id = welcome page id

            $updData = array(
                'url' => $lang . '/home/',
                'parent_id' => $welcomeId,
                'mirror_id' => $currHomeId,
            );

            saveValuesInDb('ad_content', $updData, $currHomeId);

            // set parent_id to welcome id for those pages that have home page as parent currently

            $updDbQuery = "UPDATE ad_content 
                    SET
                        parent_id = " . $welcomeId . "
                    WHERE
                        parent_id = $currHomeId";

            $updQuery = new query($mdb, $updDbQuery);

            // set new welcome page as default page for this language

            $mpDbQuery = "UPDATE ad_languages_to_ct 
                            SET
                                main_id = ".$welcomeId." 
                            WHERE
                                lang_id = " . $langId;

            $mpQuery = new query($mdb, $mpDbQuery);

            echo PHP_EOL . 'Welcome page for lang = '.$lang.' created. ID = ' . $welcomeId . PHP_EOL;
            echo PHP_EOL . 'Parent of home page children set to new welcome page id.' . PHP_EOL;
            echo PHP_EOL . '----------------------------------------------------------------' . PHP_EOL;
            echo PHP_EOL;
            echo PHP_EOL;
        }
    }

    echo PHP_EOL . 'Upgrade finished' . PHP_EOL;

    exit;

?>