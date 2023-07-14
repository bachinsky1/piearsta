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
 * CMS reviews/textlist module
 * Admin path. Edit/Add/Delete and other actions.
 * 27.07.2010
 */

class reviewsData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 * $uploadFolder - string	upload folder for frontend
	 * $uploadFolderSmall - string	upload folder for admin view
	 */
	public $result;
	
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "reviews";
		
		$this->dbTable = 'mod_reviews';
		$this->dbTableLang = 'mod_reviews_data';
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
						/*"chekcbox" => array(
							'sort' => false,
							'title' => '',
							'function' => array(&$this, 'moduleCheckboxLink'),
							'fields' => array('id'),
							'params' => array('reviews')
						),*/
						"author" => array(
							'sort' => true,
							'title' => gLA('author','Author'),
							'function' => '',
							'fields'	=> array('author')
						),
						"created" => array(
							'sort' => false,
							'title' => gLA('created','Created'),
							'function' => 'convertDate',
							'fields'	=> array('created'),
							'params' => array('d-m-Y H:i:s')
						),
						"actions" => array(
							'sort' => false,
							'title' => gLA('m_actions','Actions'),
							'function' => array(&$this, 'moduleActionsLink'),
							'fields'	=> array('id')
						),
					);
		
		/**
		 * Getting all information from DB about this module
		 */
		
		$dbQuery = "
			SELECT mp.*, mpl.author, mpl.lang 
			FROM `".$this->dbTable."` AS mp 
			LEFT JOIN `".$this->dbTableLang."` AS mpl
				ON mp.`id` = mpl.`review_id` AND mpl.`lang` = '".getDefaultLang()."'
		" . $this->moduleTableSqlParms('id', "DESC");
		$query = new query($this->db, $dbQuery);
		
		$rCounts = $this->getTotalRecordsCount(false);

		// Create module table
		$this->cmsTable->createTable($table, $query->getArray());

		return array('html' => $this->cmsTable->returnTable, 'rCounts' => $rCounts);
		
	}
	
	/**
	 * Delete reviews from DB
	 * 
	 * @param int/Array 	reviews id
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
	 * Edit reviews in DB
	 * 
	 * @param int 	reviews id, it's need if we are editing
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
							WHERE `review_id` = '" . $id . "'";
							
			$query = new query($this->db, $dbQuery);		
			$query2 = new query($this->db, $dbQuery2);
			
			$data["edit"] = $query->getrow();
			
			$dataLang = $query2->getArray();
			
			foreach ($dataLang as $key => $value) {
				$data["edit"]["author"][$value["lang"]] = $value["author"];
				$data["edit"]["lead"][$value["lang"]] = $value["lead"];
				$data["edit"]["text"][$value["lang"]] = $value["text"];
			
			}

		}
		
		return $data;
	}
	
	/**
	 * Saving information in DB
	 * 
	 * @param int	 id, it's need if we are editing language
	 * @param array  information values
	 */
	public function save($id, $value) {
		
		$langValues = getP('langValues');
		
		if(!$id){
			$value["created"] = time();
			$id = saveValuesInDb($this->dbTable, $value, $id);
		}
		
		$siteLangs = getSiteLangs();	

		deleteFromDbById($this->dbTableLang, $id, 'review_id');
		foreach ($siteLangs as $key => $values) {
			$data = array(
				'review_id' => $id,
				'lang' => $values['lang'],
				'author' => $langValues['author'][$values['lang']],
				'lead' => $langValues["lead"][$values['lang']],
				'text' => $langValues["text"][$values['lang']],
			);
			
			saveValuesInDb($this->dbTableLang, $data);	
		}
		
		return $id;
	}

}
?>