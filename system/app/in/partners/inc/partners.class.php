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
 * CMS partners/textlist module
 * Admin path. Edit/Add/Delete and other actions.
 * 27.07.2010
 */

class partnersData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 * $uploadFolder - string	upload folder for frontend
	 * $uploadFolderSmall - string	upload folder for admin view
	 */
	public $result;
	public $uploadFolder = 'partners/';
	
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "partners";
		
		$this->dbTable = 'mod_partners';
		$this->dbTableLang = 'mod_partners_data';
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
							'params' => array('partners')
						),
						"title" => array(
							'sort' => true,
							'title' => gLA('m_title','Title'),
							'function' => '',
							'fields'	=> array('title')
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
                        "sort" => array(
                            'sort' => false,
                            'title' => '',
                            'function' => array(&$this, 'moduleSortLinks'),
                            'fields' => array('id')
                        )
					);
		
		/**
		 * Getting all information from DB about this module
		 */
		
		$dbQuery = "
			SELECT mp.*, mpl.title, mpl.lang 
			FROM `".$this->dbTable."` AS mp 
			LEFT JOIN `".$this->dbTableLang."` AS mpl
				ON mp.`id` = mpl.`partner_id` AND mpl.`lang` = '".getDefaultLang()."'
		" . $this->moduleTableSqlParms('sort', "DESC");
		$query = new query($this->db, $dbQuery);
		
		$rCounts = $this->getTotalRecordsCount(false);

		// Create module table
		$this->cmsTable->createTable($table, $query->getArray());

		return array('html' => $this->cmsTable->returnTable, 'rCounts' => $rCounts);
		
	}
	
	
	/**
	 * Enable or disable
	 * 
	 * @param int/array 	partners id
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
	 * Delete partners from DB
	 * 
	 * @param int/Array 	partners id
	 */
	public function delete($id) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);
			$dbQuery = "DELETE  
							FROM `" . $this->dbTableLang . "` 
							WHERE `partner_id` = '" . $id . "'";
							
							
			$query = new query($this->db, $dbQuery);
			
		}		
	}
	
	/**
	 * Edit partners in DB
	 * 
	 * @param int 	partners id, it's need if we are editing
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
							WHERE `partner_id` = '" . $id . "'
							LIMIT 0,3";
							
			$query = new query($this->db, $dbQuery);		
			$query2 = new query($this->db, $dbQuery2);
			
			$data["edit"] = $query->getrow();
			$data["edit"]["alt"] = @unserialize($data["edit"]["alt"]);
			
			$dataLang = $query2->getArray();
			
			foreach ($dataLang as $key => $value) {
				$data["edit"]["title"][$value["lang"]] = $value["title"];
				$data["edit"]["page_url"][$value["lang"]] = $value["page_url"];
			
			}

		}
		
		$data['edit']['uploadFolder'] = $this->uploadFolder;
		
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
		
		if (empty($value["logo"])) {
			
			$value["logo"] = '';
			
		} 
		
		if(!$id){
			$value["sort"] = getNextSortValue($this->dbTable);
		}
		
		$partner_id = saveValuesInDb($this->dbTable, $value, $id);
		$siteLangs = getSiteLangs();	
			
			foreach ($siteLangs as $key => $values) {
				$partner_data = array(
					'partner_id' => $partner_id,
					'lang' => $values['lang'],
					'title' => $langValues['title'][$values['lang']],
					'page_url' => $langValues["page_url"][$values['lang']]
				);
				if($id){
					$dbQuery = "
						UPDATE " . $this->dbTableLang . " SET 
							`title` = '".$partner_data['title']."', `page_url` = '".$partner_data['page_url']."'
						WHERE `partner_id` = '" . $id . "' AND `lang` = '".$values['lang']."'
					";
					$query = new query($this->db, $dbQuery);
				}
				else
					saveValuesInDb($this->dbTableLang, $partner_data);
				
			}
		return $partner_id;
	}
    /**
     * Generates next sort value based on parent_id
     * @param   string $table For table name.
     * @author  Dzintars Rerihs <dzintars@efumo.lv>
     */
    private function getNextSortValue($table='') {
	
        $query = new query($this->db, "SELECT MAX(`sort`) AS sort FROM `" . $table . "`");
        if ($query->num_rows()) {
            return $query->getOne() + 1;
        }

        return 1;
    }
    
    /**
	 * Changing promoblock sort order
	 * 
	 * @param int		promoblock id
	 * @param string	sort changing value
	 */
	public function changeSort($id, $value) {
		$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "'";
		$query = new query($this->db, $dbQuery);
		$content = $query->getrow();
		
		if ($value == "down") {
			$sqlParm = "<";
			$sqlParm2 = "DESC";	
		}
		else {
			$sqlParm = ">";
			$sqlParm2 = "ASC";
		}
		
		$dbQuery = "SELECT `id`, `sort` FROM `" . $this->dbTable . "` WHERE `sort` " . $sqlParm . " '" . $content['sort'] . "' ORDER BY `sort` " . $sqlParm2 . " LIMIT 0,1";
		$query->query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			$info = $query->getrow();
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . $content['sort'] . "' WHERE `id` = '" . $info['id'] . "'";
			$query->query($this->db, $dbQuery);
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . $info['sort'] . "' WHERE `id` = '" . $id . "'";
			$query->query($this->db, $dbQuery);
			
		}
	}
    
}
?>