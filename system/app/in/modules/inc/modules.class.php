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
 * CMS modules module
 * Admin path. Edit/Add/Delete
 * 04.12.2010
 */

class modulesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "module";
		$this->dbTable = 'ad_modules';
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
						"name" => array(
							'sort' => true,
							'title' => gLA('m_name','Name'),
							'function' => '',
							'fields'	=> array()
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
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . $this->dbTable . "` WHERE `menuname` = 'modules' " . $this->moduleTableSqlParms("id", "DESC");
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
			//remode rules from roles groups for this module
			$dbQuery = "SELECT * FROM `ad_roles`";
			$query = new query($this->db, $dbQuery);
			$roleGroups = $query->getArray();
			foreach($roleGroups as &$rgroup){
				$roles = unserialize($rgroup['roles']);
				unset($roles[$id]);
				$rgroup['roles'] = serialize($roles);
				saveValuesInDb('ad_roles', $rgroup, $rgroup['id']);
			}
			//delete all this module records from ad_user_roles
			$dbQuery = "DELETE FROM `ad_user_roles` WHERE `module_id` = ". $id;
			$query = new query($this->db, $dbQuery);
		
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
		foreach ($data["languages"] as $key => $value) 
			if ($value["enabled"] != 1)
				unset($data["languages"][$key]);

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
			$data["edit"]["translations"] = unserialize($data["edit"]["translations"]);
			
		} else {		
			$data["edit"]["enable"] = 1;

	
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

		if(!empty($value)){	
			$value["menuname"] = "modules";
			$value["translations"] = serialize($langValues);

			$id = saveValuesInDb($this->dbTable, $value, $id);		
		}	
			
		
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