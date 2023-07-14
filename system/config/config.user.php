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
 * ADWEB
 * User configuration file.
 * 15.02.2010
 */

define("AD_WEB_FOLDER", "/");
define("AD_MAINPAGE_MIRROR_ID", 4);


/**
 * Database configuration
 */



if(isDEMO()) {

    $config["db_host"] = "127.0.0.1";
    $config["db_db"] = "piearsta_2015";
    $config["db_user"] = "root";
    $config["db_password"] = "a";

    $config["wkhtmltopdf"] = '/usr/local/bin/wkhtmltopdf';

    $config['sync_remote_host'] = '192.168.1.20';
    $config['sync_remote_user'] = 'user';
    $config['sync_remote_pass'] = '9c13f34fdf';
    $config['sync_remote_path'] = '/tmp';
    $config['sync_local_path']  = '/tmp';
    $config['sync_session_prefix']  = 'sess_';

    $config['sync_remote_host'] = '192.168.1.20';
    $config['sync_remote_user'] = 'user';
    $config['sync_remote_pass'] = '9c13f34fdf';
    $config['sync_remote_path'] = '/tmp';
    $config['sync_local_path']  = '/tmp';
    $config['sync_session_prefix']  = 'sess_';

} elseif (isLOCAL()) {


    $config["db_host"] = "127.0.0.1";
    $config["db_db"] = "piearsta_2015";
    $config["db_user"] = "root";
    $config["db_password"] = "a";

    $config["wkhtmltopdf"] = '/usr/local/bin/wkhtmltopdf';

    $config['sync_remote_host'] = '192.168.1.20';
    $config['sync_remote_user'] = 'user';
    $config['sync_remote_pass'] = '9c13f34fdf';
    $config['sync_remote_path'] = '/tmp';
    $config['sync_local_path']  = '/tmp';
    $config['sync_session_prefix']  = 'sess_';

} else {

	// PROD configurations
    $config["db_host"] = "127.0.0.1";
    $config["db_db"] = "piearsta_2015";
    $config["db_user"] = "root";
    $config["db_password"] = "a";

    $config["wkhtmltopdf"] = '/usr/local/bin/wkhtmltopdf';

    $config['sync_remote_host'] = '192.168.1.20';
    $config['sync_remote_user'] = 'user';
    $config['sync_remote_pass'] = '9c13f34fdf';
    $config['sync_remote_path'] = '/tmp';
    $config['sync_local_path']  = '/tmp';
    $config['sync_session_prefix']  = 'sess_';
}


/**
 *
 * Check if demo server
 */
function isDEMO() {

    if(isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], array('95.85.54.160', 'Akopyan'))) {
        return true;
    }

    return false;
}

/**
 *
 * Check if local server
 */
function isLOCAL() {

    if(isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], array('127.0.0.1', '192.168.1.20', '192.168.1.125'))) {
        return true;
    }

    return false;
}


/**
 * open sessions
 */

// session timeouts in msec
$config['sessionTimeout'] = 30 * 60 * 1000;
$config['sessionTimeoutWarnBefore'] = 5 * 60 * 1000;

if (isset($_SERVER['SERVER_NAME'])) {
    $host = explode(".", $_SERVER['SERVER_NAME']);
    unset($host[0]);
    ini_set('session.cookie_domain', '.' . implode('.', $host));
}

// Extend session cookie lifetime up to 24 hours
// since our application itself will manage sessions expiration
$config['apacheSessionLifetime'] = 24 * 60 * 60;


ini_set('session.cookie_lifetime', $config['apacheSessionLifetime']);
ini_set('session.gc_maxlifetime', $config['apacheSessionLifetime']);

// set SameSite attribute in session cookie

$cookieParams = session_get_cookie_params();

$maxlifetime = $cookieParams['lifetime'];
$path = $cookieParams['path'];
$domain = $cookieParams['domain'];
$secure = $cookieParams['secure'];
$httponly = $cookieParams['httponly'];
$samesite = 'lax'; // here is what we need

session_set_cookie_params($maxlifetime, $path.'; samesite='.$samesite, $domain, $secure, $httponly);
session_start();

/**
 * some servers not support $_SERVER['REDIRECT_URL']
 */
if (isset($_SERVER['QUERY_STRING']) && isset($_SERVER['REQUEST_URI'])) {
	$_SERVER['REDIRECT_URL'] = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
}

?>
