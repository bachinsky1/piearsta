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
 * Define all config constants 
 * ALL FOLDER CONSTANTS FOR ADWEB
 * This part does not be changed 
 * 15.02.2010
 */

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

/**
 * Some PHP Server Constants
 * 
 * @AD_SERVER_NAME - $_SERVER["SERVER_NAME"]
 * @AD_QUERY_STRING - $_SERVER["QUERY_STRING"]
 * @AD_SCRIPT_FILENAME - $_SERVER["SCRIPT_FILENAME"]
 * @AD_PHP_SELF - $_SERVER["PHP_SELF"]
 * @AD_REQUEST_URI - $_SERVER["REQUEST_URI"]
 * @AD_HTTP_HOST - http://" . $_SERVER["HTTP_HOST"] . "/
 */
define("AD_SERVER_NAME", $_SERVER["SERVER_NAME"]);
define("AD_QUERY_STRING", $_SERVER["QUERY_STRING"]);
define("AD_SCRIPT_FILENAME", $_SERVER["SCRIPT_FILENAME"]);
define("AD_PHP_SELF", $_SERVER["PHP_SELF"]);
define("AD_REQUEST_URI", $_SERVER["REQUEST_URI"]);
define("AD_HTTP_HOST", "http://" . $_SERVER["HTTP_HOST"] . "/");

/**
 * HTML PATHS CONSTANTS - not contain root path
 * 
 * @AD_IMAGE_FOLDER - Public image folder
 * @AD_CSS_SRC_FOLDER - CSS sources
 * @AD_CSS_FOLDER - Public css folder -- contains generated code only, git-ignored
 * @AD_REVISION - CSS  version
 * @AD_JS_SRC_FOLDER - JS sources
 * @AD_JS_FOLDER - Public js folder -- contains generated code only, git-ignored
 * @AD_CMS_WEB_FOLDER - CMS(ADWEB) folder
 * @AD_CMS_IMAGE_FOLDER - Admin image folder
 * @AD_CMS_CSS_FOLDER - Admin css folder
 * @AD_CMS_JS_FOLDER - Admin js folder
 * @AD_UPLOAD_FOLDER - Public upload folder
 * @AD_SYSTEM_WEB_FOLDER - System folder(modules, libraries, functions, etc...)
 * @AD_MODULE_WEB_FOLDER - Output modules folder
 * @AD_CMS_MODULE_WEB_FOLDER - Input modules folder
 * @AD_HTTP_ROOT - Root folder
 * @AD_TINY_FOLDER - Wysiwig TinyMCE folder
 */
define("AD_IMAGE_FOLDER",  AD_WEB_FOLDER . "img/");
define("AD_CSS_SRC_FOLDER",  AD_WEB_FOLDER . "css_src/");
define("AD_CSS_FOLDER",  AD_WEB_FOLDER . "css/");
define("AD_REVISION",  2);
define("AD_JS_SRC_FOLDER",  AD_WEB_FOLDER . "js_src/");
define("AD_JS_FOLDER",  AD_WEB_FOLDER . "js/");
define("AD_CMS_WEB_FOLDER", AD_WEB_FOLDER . "admin/");
define("AD_CMS_IMAGE_FOLDER",  AD_CMS_WEB_FOLDER . "images/");
define("AD_CMS_CSS_FOLDER",  AD_CMS_WEB_FOLDER . "css/");
define("AD_CMS_JS_FOLDER",  AD_CMS_WEB_FOLDER . "js/"); 
define("AD_UPLOAD_FOLDER", AD_WEB_FOLDER . "files/"); 
define("AD_SYSTEM_WEB_FOLDER", AD_WEB_FOLDER . "system/");
define("AD_MODULE_WEB_FOLDER", AD_SYSTEM_WEB_FOLDER . "app/out/");
define("AD_CMS_MODULE_WEB_FOLDER", AD_SYSTEM_WEB_FOLDER . "app/in/");
define("AD_HTTP_ROOT", AD_HTTP_HOST . AD_WEB_FOLDER);
define("AD_CKEDITOR_FOLDER", AD_SYSTEM_WEB_FOLDER . "lib/CKEditor/");

/**
 * SERVERSIDE CONSTANTS - contain root path
 * 
 * @AD_SRV_ROOT - Root with "/" at the end
 * @AD_SYSTEM_FOLDER - System folder(modules, libraries, functions, etc...)
 * @AD_CFG_FOLDER - Config folder
 * @AD_LIB_FOLDER - Library folder
 * @AD_FUNC_FOLDER - Functions folder
 * @AD_MODULE_FOLDER - Output modules folder
 * @AD_CMS_MODULE_FOLDER - Input modules folder
 * @AD_CMS_FOLDER - CMS(ADWEB) folder
 * @AD_SERVER_IMAGE_FOLDER - Image folder
 */
define("AD_SRV_ROOT", dirname(__FILE__) . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
define("AD_SYSTEM_FOLDER", AD_SRV_ROOT . AD_WEB_FOLDER . "system/");
define("AD_CFG_FOLDER", AD_SYSTEM_FOLDER . "config/");
define("AD_LIB_FOLDER", AD_SYSTEM_FOLDER. "lib/");
define("AD_FUNC_FOLDER", AD_SYSTEM_FOLDER. "func/");
define("AD_APP_FOLDER", AD_SYSTEM_FOLDER. "app/");
define("AD_MODULE_FOLDER", AD_SYSTEM_FOLDER . "app/out/");
define("AD_CMS_MODULE_FOLDER", AD_SYSTEM_FOLDER . "app/in/");    
define("AD_CMS_FOLDER", AD_SRV_ROOT . AD_WEB_FOLDER . "admin/");
define("AD_SERVER_IMAGE_FOLDER", AD_SRV_ROOT . AD_IMAGE_FOLDER);
define("AD_SERVER_UPLOAD_FOLDER", AD_SRV_ROOT . AD_UPLOAD_FOLDER);
define("AD_SMARTY_FOLDER", AD_LIB_FOLDER . "smarty/");
define("AD_SERVER_CKEDITOR_FOLDER", AD_LIB_FOLDER . "CKEditor/");
define("AD_CACHE_FOLDER", AD_SRV_ROOT . AD_WEB_FOLDER . "cache/");
define("AD_LOG_FOLDER", AD_SYSTEM_FOLDER . "log/"); 

?>
