<?php

/*
 * ATTENTION!!! This script should be run only ONCE!
 *
 * This creates new page record for Registration Start page, creates the necessary mirror and mapping
 *
 * */

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// get template id for clean-layout template

$templateId = null;

$dbQuery = "SELECT * FROM ad_templates WHERE filename = 'clean-layout'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $row */
    $row = $query->getrow();
    $templateId = $row['id'];
}

// get welcome page id

$welcomePageId = null;

$dbQuery = "SELECT * FROM ad_content WHERE url = 'lv/'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $profileRow */
    $profileRow = $query->getrow();
    $welcomePageId = $profileRow['id'];
}


// insert new page record

$dbQuery = "
    INSERT INTO ad_content 
        (parent_id,lang,url,country,mirror_id,`type`,target,template,template_color,page_title,title,full_title,
         content,image,image_alt,sitemap,changefreq,cache,keywords,description,edit_user,edit_date,show_contact_block,
         show_info_block,show_why_us_block,created_user,created_date,sort,enable,active,blink,show_offers_block,admin_email,`ssl`) 
         VALUES
	    ($welcomePageId,'lv','lv/registration_start/',1,1111,'s','',$templateId,NULL,'Registration start','Registration start','','','','',0,'always',0,'','Registration start','AndreyV',1633013713,0,0,0,'admin',1631715473,30,1,1,0,0,'',1);
";

$query = new query($mdb, $dbQuery);

// get new page id
$newId = null;
$dbQuery = "SELECT * FROM ad_content WHERE url = 'lv/registration_start/'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $row */
    $row = $query->getrow();
    $newId = $row['id'];
}

// set mirror_id to new page id

$dbQuery = "UPDATE ad_content SET mirror_id = $newId WHERE id = $newId";
doQuery($mdb, $dbQuery);

// insert new mirror for subscr page

$dbQuery = "INSERT INTO ad_sitedata 
            (name,tab,block,title,`type`,mlang,mcountry,required,validation,callback,sort) 
            VALUES
           ('mirros_registration_start_page','Mirrors','Registration Start page','','selcat',0,0,0,NULL,NULL,'');";
doQuery($mdb, $dbQuery);

// get new mirror id
$newMirror = null;
$dbQuery = "SELECT * FROM ad_sitedata WHERE name = 'mirros_registration_start_page'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $row */
    $row = $query->getrow();
    $newMirror = $row['id'];
}

// add new mirror mapping to content

$dbQuery = "INSERT INTO ad_sitedata_values 
            (fid, lang, value, country) 
            VALUES 
            ($newMirror, null, $newId, 0)";
doQuery($mdb, $dbQuery);

var_dump('Done!');

exit;

