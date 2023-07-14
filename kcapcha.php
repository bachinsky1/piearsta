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
 * Captcha generation file
 * 26.05.2009
 */

require_once(dirname(__FILE__) . "/system/config/config.php");
$type = 'captcha';

if ($type == 'captcha') {
	require_once(AD_LIB_FOLDER . "captcha/captcha.class.php");
	
	$captcha = new Captcha();
	$captcha->create();
	$_SESSION['captcha'][$_GET['e']] = $captcha->getKeyString();
}
else {
	require_once(AD_LIB_FOLDER . "captcha/kcaptcha.php");
	
	$captcha = new KCAPTCHA();
	$_SESSION['captcha'][$_GET['e']] = $captcha->getKeyString();
}

?>
