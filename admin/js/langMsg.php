<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		David Akopyan <davids@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @since		Version 2
 */

// ------------------------------------------------------------------------

/**
 * Php file that create JS language array from language file
 * This is php file, that must insert in each module. 
 * With GET var module.
 * 07.08.2008
 */

require_once("../../system/config/config.php");
require_once(AD_CFG_FOLDER . "config.cms.php");
require_once(AD_CFG_FOLDER . "/../func/site.func.php");

$cmsConfig = &loadLibClass('config.cms');
$cmsUser = &loadLibClass('user', true, $cmsConfig->get('userTable'));
loadFunc("site.func");

if ($cmsUser->isLogin()) {
	$cmsLang = &loadLibClass('language');
	$cmsLang->loadCmsMessages();

	$module = trim(getGP("module"));
	$add_new = trim(getGP('addNew'));

	if (!empty($module) && !$add_new) {
		$cmsLang->loadModuleMessages($module, true);

		echo "langStrings.addString(" . json_encode($cmsLang->langWords["words"]) . ");";
	}
	
	if(empty($module) && !empty($add_new))  {
		$key = trim(addSlashesDeep(jsonDecode(getGP('key'))));
		$value = trim(addSlashesDeep(jsonDecode(getGP('defValue'))));

		if(!empty($key)){
			$value = !empty($value) ? $value : $key ;
			$message = gLA($key, $value, '', true, true) ;	

			echo json_encode(array($key => $message));
		}
		
	}

	
}

?>
