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

class rolesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	private $roles = array('VIEW', 'ADD', 'EDIT', 'DELETE');
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "roles";
		$this->dbTable = 'ad_roles';
		$this->dbTableUserRoles = 'ad_user_roles';
		$this->dbTableUsers = 'ad_users';
	}
	
	/**
	 * Get all roles data from db and create module table
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
				'function' => array(&$this, 'getThisLangTitle'),
				'fields'	=> array('name')
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
	 * Delete role from DB
	 * 
	 * @param int	menu id
	 */
	public function delete($id) {
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);
		}		
	}
	
	/**
	 * Edit Role in DB
	 * 
	 * @param int 	role group id, it's need if we are editing
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
			$data["edit"]["name"] = unserialize($data["edit"]["name"]);
			$data["edit"]["roles"] = unserialize($data["edit"]["roles"]);
			
		}
		
		$data['roles'] = $this->roles;
		$data['modules'] = $this->getPublicModules();
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id ? $id : '';
		
		return jsonSend($r);		
	}
	/**
	 * Get All public modules 
	 * 
	 */
	private function getPublicModules() {
		
		$result = array();
		
		$dbQuery = "SELECT `id`, `translations`, `name`
				FROM `ad_modules` m
				WHERE m.menuname = 'modules' OR m.default = '1'";
		$query = new query($this->db, $dbQuery);
		while ($query->getrow()) {
			$translations = unserialize($query->field('translations'));
			$result[] = array("id" => $query->field('id'), 'name' => isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('name'));
		}
		
		return $result;
	}
	
	/**
	 * Saving menu information in DB
	 * 
	 * @param int	id, it's need if we are editing message
	 * @param array information values
	 */
	public function save($id, $names, $roles) {
		
		$names = addSlashesDeep(jsonDecode($names));
		$roles = addSlashesDeep(jsonDecode($roles));
		$langs = $this->cmsConfig->get('cmsAllLangs');

		$titles = array();
		foreach($langs as $lang => $value){
			$titles[$lang] = $names['name_'.$lang];
		}
		$values = array('name' => serialize($titles));
		
		$rolesArray = array();
		foreach ($roles AS $role => $value) {
			if ($value) {
				$info = explode("_", $role);
				if(!isset($rolesArray[$info[1]])){
					$rolesArray[$info[1]] = array($info[0] => 1);
				} else {
				$rolesArray[$info[1]][$info[0]] = 1;
				}
			}
			
		}
		
		$values['roles'] = serialize($rolesArray);

		$id = saveValuesInDb($this->dbTable, $values, $id);		
		
		$dbQuery = "SELECT `id` FROM `" . $this->dbTableUsers . "` WHERE `group_id` = '" . $id . "'";
 		$query = new query($this->db, $dbQuery);
		$users = $query->getArray();
		foreach($users as $user){
			deleteFromDbById($this->dbTableUserRoles, $user['id'], 'user_id');
			foreach($roles as $key=>$value){
				if($value){
					$info = explode("_", $key);
					$newUserRoles = array(
						'user_id' => $user['id'],
						'module_id' => $info[1],
						'role' => $info[0]
					);
					saveValuesInDb($this->dbTableUserRoles, $newUserRoles);	
				}
			}
		}
		
		return $id;
	}
	
	public function getThisLangTitle($name){
		$name = unserialize($name);
		if (isset($name[$this->cmsConfig->getCmsLang()])) {
			return stripslashes($name[$this->cmsConfig->getCmsLang()]);
		}
		return false;
	}
}
?>