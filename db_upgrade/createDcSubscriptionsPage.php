<?php

/*
 * ATTENTION!!! This script should be run only ONCE!
 *
 * This creates new page record for My Subscription page, creates the necessary mirror and mapping
 * and adds new page to Profile menu
 *
 * */

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// get template id for profile template

$templateId = null;

$dbQuery = "SELECT * FROM ad_templates WHERE filename = 'profile'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $row */
    $row = $query->getrow();
    $templateId = $row['id'];
}

// get profile page id

$profilePageId = null;

$dbQuery = "SELECT * FROM ad_content WHERE url = 'lv/profils/'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $profileRow */
    $profileRow = $query->getrow();
    $profilePageId = $profileRow['id'];
}


// insert new page record

$dbQuery = "
    INSERT INTO ad_content 
        (parent_id,lang,url,country,mirror_id,`type`,target,template,template_color,page_title,title,full_title,
         content,image,image_alt,sitemap,changefreq,cache,keywords,description,edit_user,edit_date,show_contact_block,
         show_info_block,show_why_us_block,created_user,created_date,sort,enable,active,blink,show_offers_block,admin_email,`ssl`) 
         VALUES
	    ($profilePageId,'lv','lv/profils/my-subscriptions/',1,1111,'s','',$templateId,NULL,'Mans aboniments','Mans aboniments','','','','',1,'always',0,'subscriptions','Mans aboniments','AndreyV',1633013713,0,0,0,'admin',1631715473,27,1,1,0,0,'',1);
";

$query = new query($mdb, $dbQuery);

// get new page id
$newId = null;
$dbQuery = "SELECT * FROM ad_content WHERE url = 'lv/profils/my-subscriptions/'";
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
           ('mirros_profile_subscription_page','Mirrors','Profile subscription page','','selcat',0,0,0,NULL,NULL,'');";
doQuery($mdb, $dbQuery);

// get new mirror id
$newMirror = null;
$dbQuery = "SELECT * FROM ad_sitedata WHERE name = 'mirros_profile_subscription_page'";
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


// add new page to menu

// get profile menu id
$menuId = null;
$dbQuery = "SELECT * FROM ad_menus WHERE name = 'PROFILE'";
$query = new query($mdb, $dbQuery);

if($query->num_rows()) {
    /** @var array $row */
    $row = $query->getrow();
    $menuId = $row['id'];
}

$dbQuery = "INSERT INTO ad_menus_on_page
            (page_id, menu_id) 
            VALUES 
            ($newId, $menuId)";
doQuery($mdb, $dbQuery);

var_dump('Done!');

exit;

