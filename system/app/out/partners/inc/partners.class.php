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
 * partners module
 */

class partnersData extends Module {
	
	/**
	 * Class constructor
	 */
	public function __construct() {		
		
		parent :: __construct();
		$this->name = 'partners';
		$this->dbTable = 'mod_partners';
		$this->dbTableLang = 'mod_partners_data';
		
	}
	
	/**
	 * Get all partners
	 * 
	 */
	public function showList() {
		$result = array();
		
		$dbQuery = "SELECT mp.*, mpl.title, mpl.lang, mpl.page_url
			FROM `".$this->dbTable."` AS mp 
			LEFT JOIN `".$this->dbTableLang."` AS mpl
				ON mp.`id` = mpl.`partner_id` AND mpl.`lang` = '".getDefaultLang()."'
			WHERE mp.`enable` = 1";
			
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows()) {
			while ($row = $query->getrow()) {
				$result[] = $row;
			} 		
		}
		
		$this->setPData($result, "partners");
		
		$this->tpl->assign("PARTNERS_CONTENT", $this->tpl->output("list", $this->getPData()));	
	
	}	
}

?>