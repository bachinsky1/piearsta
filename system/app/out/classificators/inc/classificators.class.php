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

class classificatorsData extends Module {

	
	/**
	 * Class constructor
	 */
	public function __construct() {		
		
		parent :: __construct();
		$this->name = 'classificators';
		$this->dbTable = $this->cfg->getDbTable('classificators');
	}
	
	public function getData() {
		$result = array();
		
		$dbQuery = "SELECT *
							FROM `" . $this->dbTable . "`
							WHERE 1 
								AND `enabled` = '1' 
								AND `lang` = '" . $this->getLang() . "'
							ORDER BY `created` DESC";
		$query = new query($this->db, $dbQuery);
		while ($row = $query->getrow()) {
			
			$result[$row['type']][] = $row;
			
		}
		
		
		$this->tpl->assign("classificators", $result);	
	
	}	
}

?>