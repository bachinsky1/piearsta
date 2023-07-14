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
 * Rewrite class
 * Load after all configs and rewrite url if need, redirect etc...
 * 30.06.2010
 */
class Rewrite {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->cfg = &loadLibClass('config');
		$this->uri = &loadLibClass('uri');	
		

	}

}

?>