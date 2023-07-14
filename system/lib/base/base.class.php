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
 * Main base class
 * 16.02.2010
 */
class Base {

	private static $instance;
	
	public function Base() {

		if (!is_php()) {
			die("Error! Need PHP 5.3.x or higher.");
		}
		
		if (!is_php('5.3')) {
			// Kill magic quotes
			@set_magic_quotes_runtime(0); 
		}
	}
 
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		}
		
		return self::$instance;
	}
}

?>