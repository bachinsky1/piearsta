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
 * classificators module
 */

class classificators extends Module {
	
	/**
	 * Class constructor
	 */
	public function __construct()	{		

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);

		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new classificatorsData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
	}
	
	/**
	 * Modules process function
	 * This function runs auto from Module class
	 */
	public function run() {

		$this->module->getData();
			
	}
	
}
?>