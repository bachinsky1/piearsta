<?php

set_time_limit(-1);
ini_set("display_errors", 1);
ini_set("track_errors", 1);
error_reporting(E_ALL);

$_SERVER['SERVER_ADDR'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : gethostbyname(php_uname('n'));
 
/** 
 * Including user configuration file.
 * This file will be created automatic after installing Adweb
 */ 
require_once("config.user.php");
require_once('config.specific.php');

if(file_exists(__DIR__ . '/local.config.php')) {
    require_once('local.config.php');
}

define("AD_LIB_FOLDER", dirname(__FILE__) . "/../lib/");
define("AD_CMS_FOLDER", dirname(__FILE__) . "/../../admin/");
if(!defined("AD_WEB_FOLDER")){
	define("AD_WEB_FOLDER", "/");
}
define("AD_SRV_ROOT", dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
define("AD_SYSTEM_FOLDER", AD_SRV_ROOT . AD_WEB_FOLDER . "system/");
define("AD_CFG_FOLDER", AD_SYSTEM_FOLDER . "config/");
define("AD_FUNC_FOLDER", AD_SYSTEM_FOLDER. "func/");
define("AD_APP_FOLDER", AD_SYSTEM_FOLDER. "app/");
define("AD_MODULE_FOLDER", AD_SYSTEM_FOLDER . "app/out/");  
define("AD_UPLOAD_FOLDER", AD_SRV_ROOT . AD_WEB_FOLDER . "files/");
define("AD_SERVER_UPLOAD_FOLDER", AD_UPLOAD_FOLDER);
define("AD_SMARTY_FOLDER", AD_LIB_FOLDER . "smarty/");
define("AD_CSS_SRC_FOLDER",  AD_WEB_FOLDER . "css_src/");
define("AD_IMAGE_FOLDER",  AD_WEB_FOLDER . "img/");
define('APP_ROOT', AD_SRV_ROOT);

if (defined('PIEARSTA_DT_FORMAT') === false)
{
    define('PIEARSTA_DT_FORMAT', 'Y-m-d H:i:s');
}

require_once(dirname(__FILE__) . "/../func/common.func.php");
require_once(dirname(__FILE__) . "/../func/site.func.php");

/** 
 * loading Adweb base class
 */ 
loadLibClass('base');

/** 
 * loading Adweb main config class
 */
$cfg = &loadLibClass('config', true, $config);

require_once(dirname(__FILE__) . "/../func/functions.func.php");

/** 
 * loading db class
 * and connecting to database
 */
/** @var db $mdb */
$mdb = &loadLibClass('db');
$mdb->open($cfg->get("db_db"), $cfg->get("db_host"), $cfg->get("db_user"), $cfg->get("db_password"));

$cfg->getSiteData();

