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
 * Main cms config class
 * With this class can get, set config values(Extends from parent)
 * 18.02.2010
 */
class Config_cms extends Config {
	
	/**
	* CMS user table name in DB
	*/
	//public $userTable;
 
	/**
	* String url of cms system
	*/
	//public $adminPage;  
 
	/**
	* CMS current language 
	*/
	//public $cmsLang;
 
	/**
	* Array with all cms languages 
	*/
	//public $cmsAllLangs = Array();
	
	/** 
	 * Constructor
	 */
	public function __construct($config) {
		parent::__construct($config);
	}
	
	/**
	 * Get cms selected language
	 */
	public function getCmsLang() {
		if (isset($this->config['cmsLang'])){ 
			return $this->config['cmsLang'];
		}
		else{ 
			if (getC("cmsLang")){
				$this->config['cmsLang'] = getC("cmsLang");
			}
			else{
				foreach ($this->config['cmsAllLangs'] as $key => $value){
					if ($value["default"] == 1){
						$this->config['cmsLang'] = $key;
					}
				}
			}
		}
		return $this->config['cmsLang'];
	}
	
}

?>