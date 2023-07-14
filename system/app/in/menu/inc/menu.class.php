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
 * CMS menu module
 * Admin path. Edit/Add/Delete
 * 04.12.2010
 */

class menuData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "menu";
		$this->dbTable = 'ad_menus';
	}
	
	/**
	 * Get all menu data from db and create module table
	 */
	public function showTable() {
		
		/**
		 * Creating module table, using cmsTable class
		 * This is table information
		 */
		$table = array(
			"name" => array(
				'sort' => true,
				'title' => gLA('m_name','Name'),
				'function' => '',
				'fields'	=> array()
			),
			"parent_id" => array(
				'sort' => true,
				'title' => gLA('parent_menu','Parent'),
				'function' => array(&$this, 'getParentName'),
				'fields'	=> array('parent_id'),
				'params' => array()
			),
			"enable" => array(
				'sort' => false,
				'title' => gLA('m_enable','Enable'),
				'function' => array(&$this, 'moduleEnableLink'),
				'fields'	=> array('id', 'enable')
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
	 * Get parent name
	 *
	 * @param int	id
	 */
	public function getParentName($id) {
		if ($id) {
			$dbQuery = "SELECT `name` FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
			
			return $query->getOne();
		}
	}
	
	/**
	 * Enable or disable menu
	 * 
	 * @param int	menu id
	 * @param bool of enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `enable` = '" . $value . "' WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Delete menu from DB
	 * 
	 * @param int	menu id
	 */
	public function delete($id) {
		
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);
		}		
	}
	
	/**
	 * Get all menus
	 *
	 * @param int	id of selected menu
	 */
	public function getAllMenus($sel = "") {
		
		$values = array();
		
		$dbQuery = "SELECT `id`, `name` FROM `" . $this->dbTable . "`";
		$query = new query($this->db, $dbQuery);
		while ($query->getrow()) {
			$values[$query->field('id')] = $query->field('name');
		}
		
		return dropDownFieldOptions($values, $sel, true);
		
	}
	
	/**
	 * Edit menu in DB
	 * 
	 * @param int 	menu id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();

		if(isset($id) && $id != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * " .
						"FROM `" . $this->dbTable . "` " .
						"WHERE `id` = '" . $id . "'" .
						" LIMIT 0,1";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();
			$data["edit"]["parent_id"] = $this->getAllMenus($data["edit"]["parent_id"]);
			
		} else {		
			$data["edit"]["enable"] = 1;
			$data["edit"]["parent_id"] = $this->getAllMenus();
	
		}
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id ? $id : '';
		
		return jsonSend($r);		
	}
	
	/**
	 * Saving menu information in DB
	 * 
	 * @param int	id, it's need if we are editing message
	 * @param array information values
	 */
	public function save($id, $value) {
		
		$value = addSlashesDeep(jsonDecode($value));

		if(empty($value['parent_id'])){
			unset($value['parent_id']);
		}
		
		$id = saveValuesInDb($this->dbTable, $value, $id);		
		
		return $id;
	}
	
	/**
	 * Checking for uniq tmplparm name
	 * 
	 * @param int 		message id, it's need if we are editing menu
	 * @param string	message name 
	 */
	public function checkName($id, $value) {

		$result = false;
		
		$dbQuery = "SELECT `id` FROM `" . $this->dbTable . "` WHERE `name` = '" . $value . "'";
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