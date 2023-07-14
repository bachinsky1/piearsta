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
 * Admin panel login page.
 * If is logined redirect to main page by cmsurl
 * 18.05.2008
 */

require_once("../system/config/config.php");
require_once(AD_CFG_FOLDER . "config.cms.php");
	
$l = &loadLibClass('loader');

// Free MySQL or other resources on the end of the script
freeResources();


?>
