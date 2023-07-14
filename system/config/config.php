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
 * Main config file 
 * Require all need config files
 * Run and load all needed classes and functions 
 * 15.02.2010
 */

require_once("config.user.php");

/**
 * Set Default time zone.
 */
date_default_timezone_set("Europe/Riga");
setlocale(LC_TIME, 'lv_LV.utf8');

/** 
 * Including constants configuration file.
 */ 
require_once("config.constants.php");

/**
 * Include specific configuration file.
 */
require_once('config.specific.php');

/**
 * Include config.white_label.php if exists
 * This file will define this project instance as "White Label" Piearsta version
 * making it specific only for one clinic or clinic range
 * The White Label version has some templates different from conventional Piearsta
 * All the Piearsta functionalty is limited to work only with predifined clinics
 *
 */
if(file_exists(__DIR__ . '/config.white_label.php')) {

    require_once('config.white_label.php');

    if (is_array($config['allowed_clinics'])){
        if(empty($config['allowed_clinics'])){
            $ids = '/';
        } elseif (in_array('*', $config['allowed_clinics'])){
            $ids = [];
        } else {
            $ids = implode(',',$config['allowed_clinics']);
        }
    }

    if (!empty($ids)){
        define('ALLOWED_CLINICS', $ids);
    }
}

/**
 * Include local.config.php if exists
 * This allows developers to hold own configuration for project
 * local.config.php added to gitignore, so it will not be commited
 */


if(file_exists(__DIR__ . '/local.config.php')) {
    require_once('local.config.php');
}


/** 
 * Including functions, used across project and globally available.
 */ 
require_once(AD_FUNC_FOLDER . "common.func.php");

/** 
 * loading Adweb base class
 */ 
loadLibClass('base');

/** 
 * loading Adweb working time class
 */
$wk = &loadLibClass('workTime');
$wk->mark("start");

/** 
 * loading Adweb main config class
 */
$cfg = &loadLibClass('config', true, $config);

/** 
 * loading main functions
 */
loadFunc("functions");

/** 
 * loading db class
 * and connecting to database
 */
/** @var db $mdb */
$mdb = &loadLibClass('db');
if ($cfg->get("db_db_local") && $cfg->get("db_host_local")  &&  $cfg->get("db_user_local")  &&  $cfg->get("db_password_local")){
    $mdb->open($cfg->get("db_db_local"), $cfg->get("db_host_local"), $cfg->get("db_user_local"), $cfg->get("db_password_local"));
}
$mdb->open($cfg->get("db_db"), $cfg->get("db_host"), $cfg->get("db_user"), $cfg->get("db_password"));

/**
 * loading smarty class but not init
 */
loadLibClass('smarty', false);

//if (in_array(getIp(), $cfg->get('debugIp'))) { 
//	ini_set("display_errors", 1);
//	ini_set("track_errors", 1);
//	error_reporting(E_ALL);
//}

?>