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
 * CMS templates module
 * Admin path. Edit/Add/Delete
 * 04.12.2010
 */

class templatesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "templates";
		$this->dbTable = 'ad_templates';
	}
	
	/**
	 * Get all data from db and create module table
	 */
	public function showTable() {
		
		/**
		 * Creating module table, using cmsTable class
		 * This is table information
		 */
		$table = array(
						"filename" => array(
							'sort' => true,
							'title' => gLA('m_name','Name'),
							'function' => '',
							'fields'	=> array()
						),
						"actions" => array(
							'sort' => false,
							'title' => gLA('m_actions','Actions'),
							'function' => array(&$this, 'moduleActionsLink'),
							'fields'	=> array('id')
						)
					);

		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . $this->dbTable . "` " . $this->moduleTableSqlParms("id", "DESC");
		$query = new query($this->db, $dbQuery);
		
		
		// Create module table
		$this->cmsTable->createTable($table, $query->getArray(), true, 'id');

		return $this->cmsTable->returnTable;
	}
	
	/**
	 * Enable or disable
	 * 
	 * @param int	id
	 * @param bool of enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `enable` = '" . $value . "' WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Delete from DB
	 * 
	 * @param int	id
	 */
	public function delete($id) {
		
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);
		}		
	}
	
	/**
	 * Edit menu in DB
	 * 
	 * @param int 	menu id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();
		$data['languages'] = $this->cmsConfig->get('cmsAllLangs');

		if(isset($id) && $id != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "
				SELECT * 
				FROM `" . $this->dbTable . "` 
				WHERE `id` = '" . $id . "'
				LIMIT 0,1
			";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();
			$data["edit"]["translations"] = unserialize($data["edit"]["translations"]);
			
		} 
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id ? $id : '';
		
		return jsonSend($r);		
	}
	
	/**
	 * Saving menu information in DB
	 * 
	 * @param int	id, it's need if we are editing
	 * @param array information values
	 */
	public function save($id, $value, $langValues) {
		
		$value = addSlashesDeep(jsonDecode($value));
		$langValues = addSlashesDeep(jsonDecode($langValues));

		$value["translations"] = serialize($langValues);
		
		$id = saveValuesInDb($this->dbTable, $value, $id);		
		
		return $id;
	}
	
	/**
	 * Checking for uniq name
	 * 
	 * @param int 		id, it's need if we are editing
	 * @param string	name 
	 */
	public function checkName($id, $value) {

		$result = false;
		
		$dbQuery = "SELECT `id` FROM `" . $this->dbTable . "` WHERE `filename` = '" . $value . "'";
 		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			if ($id && $id == $query->getOne()) {
				$result = true;
			}
		} else {
			$result = true;
		}
		
		return $result;
	}
	
}
?>