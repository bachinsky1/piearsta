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
 * coupons module
 * 30.06.2010
 */

class coupons extends Module {
	
	/**
	 * Class constructor
	 */
	public function __construct()	{		

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);

		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new couponsData();
		
		$this->setTmplDir();
	}
	
	public function setTmplDir() {
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
	}
	
	/**
	 * Modules process function
	 * This function runs auto from Module class
	 */
	public function run() {
		
		switch ($this->getCData("id")) {	
			
			/**
			 * Load startpage coupons
			 */
			case getMirror(getDefaultPageId()):	

				$this->module->loadStarpage(); 
				
				break;
			
			/**
			 * Default coupons module loading
			 */	
			default :			
				
				if (getG('docUrl')) {				
					$this->module->showOne();		
				} else {	
                    $this->module->showList();		
				} 
				
				break;			
		}
			
	}
	
}
?>