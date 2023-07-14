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
 * Read message strings from some file, administrators or simply GUI.
 * File constants are defined in configuration files.
 * 03.11.2008
 */

class Language {
	
	/**
	 * $langWords - array of strings which holds messages
	 * $lang - current language (en, lv, ru)
	 */
	public $langWords;
	public $cfg;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->cfg = &loadLibClass('config.cms');
		$this->db = &loadLibClass('db');

	}

	/**
	 * Load admin messages from file
	 */
	public function loadCmsMessages() {
		$this->loadTranslations();
	}

	/**
	 * Load all module messages from file
	 * 
	 * @param string	 module name
	 */
	public function loadModuleMessages($module, $js = false) {
		$this->loadTranslations($module, $js);
	}

	/**
	 * Read file and add strings to string array
	 * 
	 * @param string	 file name
	 */
	private function loadTranslations($module = '', $js = false) {
		global $langWords;

		$dbQuery = "SELECT `id` FROM `ad_modules` WHERE `name` = '" . $module . "'";
											
		$query = new query($this->db, $dbQuery);	
		$module_id = $query->getrow();

		$dbQuery = "
			SELECT mb.* , mbi.* 
			FROM ad_messages_backend  AS mb
			LEFT JOIN ad_messages_backend_info AS mbi
				ON mb.`id` = mbi.`id`
			WHERE
				mbi.`lang` = '" . $this->cfg->getCmsLang() . "'
		";
		if(intval($module)){
			$dbQuery .= " AND mb.`module_id` = '".$module_id."' " ;
		}
		else if(!intval($module) && $js){
			$dbQuery .= " AND mb.`module_id` IS NULL" ;
		}
		if($js){
			$dbQuery .= " AND mb.`js` = '1' " ;	
		}
		$query = new query($this->db, $dbQuery);

		$labelArray = array();
		$messages = $query->getArray();

		if ($messages <> ''){
			
			foreach ($messages as $key => $val) {
				$labelArray[$val['name']] = $val['value'];
			}
			
		}
		
		$this->langWords['words'] = $labelArray;

		
		
	}
}
?>
