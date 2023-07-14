<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		David Akopyan <davids@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		2
 */

// ------------------------------------------------------------------------

/** 
 * Adweb configuration file
 * CMS admin panel configuration file 
 * 18.02.2010
 */

// Define AD_CMS constant to check admin path
define("AD_CMS", true);

// Default CMS language
define("CMS_DEFAULT_LANGUAGE", "en");

$config["userTable"] = "ad_users";
$config["adminPage"] = "admin/";
$config["cmsAllLangs"] = array(
	"en" => array(
		"label" => "in english",
		"enabled" => 1,
		"default" => 1
	), 
	/*"ru" => array(
		"label" => "по-русски",
		"enabled" => 1,
		"default" => 0
	), 
	"lv" => array(
		"label" => "latviski",
		"enabled" => 1,
		"default" => 0
	) */
);

/** 
 * loading Adweb cms config class
 */
$cfg = &loadLibClass('config.cms', true, $config);
/** 
 * loading main cms functions
 */
loadFunc("functions.cms");

?>