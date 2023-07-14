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
 * Check for maintenance mode
 */
include('mode.php');

// Init vendor libs (google api client, etc ...)
require __DIR__ . '/vendor/autoload.php';

/**
 * ADWEB
 * General main file.
 * Includs all needs file and run all needs modules.
 * 09.05.2008
 */

define('APP_ROOT', dirname(__FILE__));

require_once(dirname(__FILE__) . "/system/config/config.php");

// ***  Content Security Policy  *** //
// we implement CSP for public part only, not an admin panel

if (!defined('AD_CMS')) {

    // here nonce generate and in system/app/out/content/content.php we include it to $web system array as cspNonce property,
// so it is available in every page template on Piearsta
    define('CSP_NONCE', base64_encode(random_bytes(20)));

    // deny all srcs
    $cspStr = "default-src 'none';";

    // allow form actions on self
    $cspStr .= "form-action 'self';";

    // deny frame ancestors
    $cspStr .= "frame-ancestors *.cookiebot.com https://consentcdn.cookiebot.com;";

    // allow frame src from self and youtube
    $cspStr .= "frame-src 'self' *.youtube.com *.cookiebot.com https://consentcdn.cookiebot.com;";

    // allow connect src from self
    $cspStr .= "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com *.cookiebot.com https://consentcdn.cookiebot.com;";

    // allow css stylesheets from self and allow all inline styles
    $cspStr .= "style-src 'self' 'unsafe-inline';";

    // script allowed from self, inline with nonce attribute, from google sites and allow unsafe eval due to jquery requires
    $cspStr .= "script-src 'self' 'nonce-".CSP_NONCE."' https://*.googletagmanager.com *.google-analytics.com *.googletagmanager.com *.facebook.com *.cookiebot.com 'unsafe-eval' 'unsafe-inline' 'strict-dynamic';";

    // allow images from self
    $cspStr .= "img-src 'self' data: *.ytimg.com https://*.google-analytics.com https://*.googletagmanager.com *.googleapis.com *.piearsta.lv;";

    // allow fonts from self
    $cspStr .= "font-src 'self';";

    // set report only header (doesn't block content actually, but only reports in console - for debug)
    //header("Content-Security-Policy-Report-Only: " . $cspStr);

    // set actual csp header (actually blocks content)
    header("Content-Security-Policy: " . $cspStr);


//    header("Content-Security-Policy-Report-Only: default-src 'none'; form-action 'self'; frame-ancestors 'none'; connect-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'nonce-".CSP_NONCE."' *.google-analytics.com *.googletagmanager.com 'unsafe-eval'; img-src 'self'; font-src 'self';");

}

// continue request processing

$l = &loadLibClass('loader');

// Free MySQL or other resources on the end of the script
freeResources();

?>
