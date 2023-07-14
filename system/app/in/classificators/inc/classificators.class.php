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
 * CMS classificators/textlist module
 * Admin path. Edit/Add/Delete and other actions.
 * 27.07.2010
 */

class classificatorsData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 * $uploadFolder - string	upload folder for frontend
	 * $uploadFolderSmall - string	upload folder for admin view
	 */
	public $result;
	public $types;
	
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "classificators";
		
		$this->dbTable 		= 'mod_classificators';
		$this->dbTableLang	= 'mod_classificators_info';
		$this->types = $this->cfg->get('classificators_types');
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
						"chekcbox" => array(
							'sort' => false,
							'title' => '',
							'function' => array(&$this, 'moduleCheckboxLink'),
							'fields' => array('id'),
							'params' => array('classificators')
						),
						"piearstaId" => array(
							'sort' => true,
							'title' => gLA('m_id','ID'),
							'function' => '',
						),
						"title" => array(
							'sort' => true,
							'title' => gLA('m_title','Title'),
							'function' => '',
							'fields'	=> array('title')
						),
						"type" => array(
							'sort' => true,
							'title' => gLA('type','Type'),
							'function' => array(&$this, 'getTypeTitle'),
							'fields'	=> array('type')
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
						),
					);
		
		if(getP('filterType') && getP('filterType') != '0'){
			$table["sort"] = array(
				'sort' => false,
				'title' => '',
				'function' => array(&$this, 'moduleSortLinks'),
				'fields' => array('id')
			);
			
			foreach ($table AS &$row) {
				$row['sort'] = false;
			}
		}
		
		
		/**
		 * Getting all information from DB about this module
		 */
		
		$sqlWhere = getP('filterType') ? " AND mp.type = '" . getP('filterType') . "'" : "";
		
		$dbQuery = "SELECT mp.*, mpl.title, mpl.lang 
							FROM `" . $this->dbTable . "` AS mp 
								LEFT JOIN `" . $this->dbTableLang . "` AS mpl ON (mp.`id` = mpl.`c_id` AND mpl.`lang` = '".getDefaultLang()."')
							WHERE 1			
		" . $sqlWhere;
		$query = new query($this->db, $dbQuery);
		$rCounts = $query->num_rows();
		
		// Add ORDER BY clouse
		if(getP('filterType') != '0'){
			$dbQuery .= $this->moduleTableSqlParms('sort', "ASC");
		} else {
			$dbQuery .= $this->moduleTableSqlParms('id', "DESC");
		}
		
		$query = new query($this->db, $dbQuery);
		

		// Create module table
		$this->cmsTable->createTable($table, $query->getArray());

		return array('html' => $this->cmsTable->returnTable, 'rCounts' => $rCounts);
		
	}
	
	public function getTypeTitle($type)
	{
		if (!empty($type) && isset($this->types[$type])) {
			return $this->types[$type];
		}
	}
	
	
	/**
	 * Enable or disable
	 * 
	 * @param int/array 	classificators id
	 * @param bool 			enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `enable` = '" . $value . "' WHERE " . (is_array($id) ? "`id` IN (" . implode(",", $id) . ")" : "`id` = '" . $id . "'");
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Delete classificators from DB
	 * 
	 * @param int/Array 	classificators id
	 */
	public function delete($id) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);			
		}		
	}
	
	/**
	 * Edit classificators in DB
	 * 
	 * @param int 	classificators id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();
		$data["langauges"] = getSiteLangs();

		if ($id) {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * 
							FROM `" . $this->dbTable . "` 
							WHERE `id` = '" . $id . "'
							LIMIT 0,1";
							
			$dbQuery2 = "SELECT *
							FROM `" . $this->dbTableLang . "`
							WHERE `c_id` = '" . $id . "'";
							
			$query = new query($this->db, $dbQuery);		
			$query2 = new query($this->db, $dbQuery2);
			
			$data["edit"] = $query->getrow();
			
			$dataLang = $query2->getArray();
			
			foreach ($dataLang as $key => $value) {
				$data["edit"]["title"][$value["lang"]] = $value["title"];			
			}

		}
		
		$data['types'] = $this->types;
		
		return $data;
	}
	
	/**
	 * Saving information in DB
	 * 
	 * @param int	 id, it's need if we are editing language
	 * @param array  information values
	 */
	public function save($id, $value) {

		$value = addSlashesDeep(jsonDecode($value));
		$langValues = jsonDecode(getP('langValues'));
	
		if(!$id){
			$value["created"] = time();
		}
		
		$reSort = 0;
		if (!$id) {
			$values['sort'] = 1;
			$reSort = 1;
		}
		
		$cId = saveValuesInDb($this->dbTable, $value, $id);
		if (!$id) {
			saveValuesInDb($this->dbTable, array('piearstaId' => $cId), $cId);
		}
		
		if ($reSort) {
			reSort($this->dbTable, $cId, array('type' => $value['type']));
		}
		
		$siteLangs = getSiteLangs();			
		foreach ($siteLangs as $key => $values) {
			$data = array(
				'c_id' => $cId,
				'lang' => $values['lang'],
				'title' => $langValues['title'][$values['lang']],
			);
			if ($id) {

                /*$dbQuery = "
					UPDATE " . $this->dbTableLang . " SET
						`title` = '" . $data['title']."'
					WHERE `c_id` = '" . $id . "' AND `lang` = '".$values['lang']."'
				";*/

                $dbQuery = " SELECT * FROM " . $this->dbTableLang . " WHERE `c_id` = '" . $id . "'  
                AND `lang` = '".$values['lang']."'";
                $query = new query($this->db, $dbQuery);

                if ($query->num_rows() > 0){
                    $dbQuery = "UPDATE `" .  $this->dbTableLang . "` SET `title` = '" . $data['title']."' 
                    WHERE `c_id` = " . $id ." AND `lang` = '".$values['lang']."'";
                } else {
                    $dbQuery = "INSERT INTO  " . $this->dbTableLang . " SET 
                    `title` = '" . $data['title']."',
                    `c_id` = '" . $id . "',
                    `lang` = '".$values['lang']."'";
                }
                $query = new query($this->db, $dbQuery);

			} else {
				saveValuesInDb($this->dbTableLang, $data);
			}
				
			
		}
		return $cId;
	}
	
	public function sort($id, $sort) {
	
		$query = new query($this->db, "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = " . intval($id));
		if ($query->num_rows()) {
			$content = $query->getrow();;
	
			// Set sorting parameters
			if ($sort == 'down') {
				$sql_parm = '>';
				$sql_parm2 = 'ASC';
			} else {
				$sql_parm = '<';
				$sql_parm2 = 'DESC';
			}
	
			// Select next or previous row
			$sql = "SELECT `id`, `sort` FROM `" . $this->dbTable . "` WHERE `sort` " . $sql_parm . " '" . $content['sort'] . "' AND `type` = '" . mres($content['type']) . "' ORDER BY `sort` " . $sql_parm2 . " LIMIT 0, 1";
			$query->query($this->db, $sql);
	
			if ($query->num_rows()) {
				$info = $query->getrow();
	
				doQuery($this->db, "UPDATE `" . $this->dbTable . "` SET `sort` = " . intval($content['sort']) . " WHERE `id` = " . intval($info['id']));
				doQuery($this->db, "UPDATE `" . $this->dbTable . "` SET `sort` = " . intval($info['sort']) . " WHERE 1  AND `type` = '" . mres($content['type']) . "' AND `id` = " . intval($id));
	
				return true;
			}
		}
	
		return false;
	}
 
}
?>