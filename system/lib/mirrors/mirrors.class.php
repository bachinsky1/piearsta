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
 * Mirrors class
 * 18.05.2010
 */
class Mirrors {

	public $mirrors;
	public $alias;
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->db = &loadLibClass('db');
	}
	
	/**
	 * Get site mirrors
	 * 
	 */
	public function getMirrors() {
		
		$dbQuery = "SELECT `id`, `url`, `lang`, `country`, `mirror_id` 
						FROM `ad_content` 
						WHERE 
							`mirror_id` <> ''";
		$query = new query($this->db, $dbQuery);
		while ($query->getrow()) {
			
			$this->alias[$query->field("id")] = $query->field("mirror_id");
			
			$this->mirrors[$query->field("mirror_id")][$query->field("country")][$query->field("lang")]["id"] = $query->field("id");
			$this->mirrors[$query->field("mirror_id")][$query->field("country")][$query->field("lang")]["url"] = makeUrlWithLangInTheEnd($query->field("url"));
			
		}

	}
	
}

?>