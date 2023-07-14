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
 * CMS users module
 * Admin path. Edit/Add/Delete
 * 20.11.2008
 */

class usersData extends Module_cms {
	
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
		$this->name = "users";
		$this->dbTable = 'ad_users';
		$this->dbTableRoles = 'ad_user_roles';
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
			"username" => array(
				'sort' => true,
				'title' => gLA('m_username','Username'),
				'function' => '',
				'fields'	=> array()
			),
			"name" => array(
				'sort' => true,
				'title' => gLA('m_name','name'),
				'function' => '',
				'fields'	=> array()
			),
			"lastlogin" => array(
					'sort' => true,
					'title' => gLA('m_lastlogin','Last Login'),
					'function' => 'convertDate',
					'fields'	=> array('lastlogin'),
					'params' => array('d-m-Y H:i:s')
				),
			"admin" => array(
				'sort' => true,
				'title' => gLA('m_admin','Admin'),
				'function' => array(&$this, 'isAdmin'),
				'fields'	=> array('admin')
			),	
			"enable" => array(
				'sort' => true,
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
		$developer = ($this->cmsUser->userData["group_id"] !== NULL) ? "WHERE `group_id` != 'NULL'" : ""; 
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . $this->dbTable . "` " . $developer .  "" . $this->moduleTableSqlParms("id", "DESC");

		$query = new query($this->db, $dbQuery);
		
		
		// Create module table
		$this->cmsTable->createTable($table, $query->getArray(), true, 'id');

		return $this->cmsTable->returnTable;
	}
	
	/**
	 * Is admin or not
	 *
	 * @param enum	true/false
	 */
	public function isAdmin($value) {
		
		return $value ? gLA('m_yes','Yes') : gLA('m_no','No') ;
	}
	
	/**
	 * Enable or disable
	 * 
	 * @param int	id
	 * @param bool	enable/disable value
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
			deleteFromDbById($this->dbTableRoles, $id, 'user_id');
		}		
	}
	
	/**
	 * Edit 
	 * 
	 * @param int 	id, it's need if we are editing
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
			$data["edit"]["roles"] = $this->getUserRoles($id);
			
		} else {		
			$data["edit"]["enable"] = 1;
	
		}
		$data['groups'] = $this->getUserGroups();
		$data['modules'] = $this->getPublicModules();
		$data['roles'] = $this->roles;
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id ? $id : '';

		return jsonSend($r);		
	}
	
	/**
	 * Get All public modules 
	 * 
	 */
	private function getUserRoles($user) {
		
		$result = array();
		
		$dbQuery = "
			SELECT *
			FROM `ad_user_roles`
			WHERE `user_id` = '" . $user . "'";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			$result[$query->field('module_id')][$query->field('role')] = true;
		}
		
		return $result;
	}
	
	/**
	 * Get All user groups 
	 * 
	 */
	private function getUserGroups() {
		$dbQuery = "SELECT `id`, `name`
				FROM `ad_roles`";
		$query = new query($this->db, $dbQuery);
		$result = $query->getArray();
		foreach($result as &$item){
			$name = unserialize($item['name']);
			if (isset($name[$this->cmsConfig->getCmsLang()])) {
				$item['name'] =  stripslashes($name[$this->cmsConfig->getCmsLang()]);
			}
		}
		return $result;
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
	 * Saving information in DB
	 * 
	 * @param int	id, it's need if we are editing
	 * @param array information values
	 * @param array roles 
	 */
	public function save($id, $value, $roles) {
		
		$value = addSlashesDeep(jsonDecode($value));
		$roles = addSlashesDeep(jsonDecode($roles));
		
		$value['date'] = time();
		unset($value['password2']);
		if ($value['password']) {
			$value['password'] = md5($value['password']);
		} else {
			unset($value['password']);
		}
		
		if(isset($value['group_id'] ) && $value['group_id'] && !$value['admin']){
			if(!$this->checkIfIsGroup($value['group_id'], $roles)){
				unset($value['group_id']);
			}
		}
		
		$id = saveValuesInDb($this->dbTable, $value, $id);
		
		if (!$value['admin']) {
			// Save user roles
			$this->saveRoles($id, $roles);
		}
		
		
		return $id;
	}
	
	/**
	 * Saving roles information in DB
	 * 
	 * @param int	id
	 * @param array information values
	 */
	private function saveRoles($id, $roles) {
		
		deleteFromDbById($this->dbTableRoles, $id, 'user_id');
		$sql = array();
		
		$dbQuery = "INSERT INTO `ad_user_roles` (`user_id`, `module_id`, `role`) VALUES ";
		foreach ($roles AS $role => $value) {
			if ($value) {
				$info = explode("_", $role);
			
				$sql[] = "('" . $id . "', '" . $info[1] . "', '" . $info[0] . "')";
			}
			
		}
		
		$dbQuery .= implode(",", $sql);
		$query = new query($this->db, $dbQuery);
	}
	
	/**
	 * Checking for uniq username
	 * 
	 * @param int 		id, it's need if we are editing menu
	 * @param string	username 
	 */
	public function checkName($id, $value) {

		$result = false;
		
		$dbQuery = "SELECT `id` FROM `" . $this->dbTable . "` WHERE `username` = '" . $value . "'";
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
	
	/**
	 * Checking if is group
	 * 
	 * @param int 		group id
	 * @param string	checked roles 
	 */
	public function checkIfIsGroup($group_id, $roles){
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
		$roles = serialize($rolesArray);
		
		$dbQuery = "
			SELECT `roles` 
			FROM `ad_roles` 
			WHERE `id` = '" . $group_id . "'
			LIMIT 1
		";
		$query = new query($this->db, $dbQuery);		
		$rol = $query->getone();

		if($roles == $rol){
			return true;
		} 
		else {
			return false;
		}
	}
	
	/**
	 * Get group roles by group id
	 * 
	 * @param int 		group id
	 */
	public function getGroup($group_id){
		
		$dbQuery = "SELECT `roles` " .
					"FROM `ad_roles` " .
					"WHERE `id` = '" . $group_id . "'" .
					" LIMIT 1";
		$query = new query($this->db, $dbQuery);		

		$allroles = unserialize($query->getone());
		
		$result = array();
		foreach($allroles as $key=>$roles){
			foreach($allroles[$key] as $role=>$k){
				
				$result[] = $role.'_'.$key;
			}
		}
		return jsonSend($result);
	}
}
?>