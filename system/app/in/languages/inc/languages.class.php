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
 * ADWEB
 * CMS languages module general admin class
 * Admin path. Edit/Delete and other actions with languages
 * 20.10.2008
 */

class languagesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "languages";
	}
	
	/**
	 * Get all data from db and create module table
	 */
	public function showTable() {
		header("Content-type:text/html");
		$returnHtml = "";
				
		/**
		 * Creating module table, using cmsTable class
		 * This is table head information
		 */
		$tableHeadValues = array(
			gLA('m_title','Title'),
			gLA('m_enable','Enable'),
			gLA('m_actions','Actions'),
			'&nbsp;'
		);
		$tableHeadSort = array(false, false, false, false);
		$tableHeadSortFields = array('', '', '', '');
		
		/**
		 * Start table and creating table head
		 */
		$this->cmsTable->startTable();
		$this->cmsTable->tableType = 'edit';
		$this->cmsTable->drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields);
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "
			SELECT l.*, lc.`default` 
			FROM `ad_languages` l 
			LEFT JOIN `ad_languages_to_ct` lc 
				ON lc.`lang_id` = l.`id` AND lc.`default` = '1'
			ORDER BY l.`sort` ASC
		";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {

			/**
			 * Creating one row
			 */
			$this->cmsTable->startOneRow("", "tr_" . $query->field('id'));
		
			/**
			 * Draw all table cells
			 */			
			$this->cmsTable->drawOneCell($query->field('title'));
			$this->cmsTable->drawOneCell($this->moduleEnableLink($query->field('id'), $query->field('enable'), $query->field('default')), "ac");
            if(!$query->field('default')){
                $this->cmsTable->drawOneCell($this->moduleActionsLink($query->field('id')), "", "lastTd_" . $query->field('id'));
            } else {
                $this->cmsTable->drawOneCell(
                	'<a class="edit" href="javascript:;" onclick="moduleEdit(\'' . $query->field('id') . '\'); return false;">' . gLA("m_edit",'Edit') . '</a>');
            }
			$this->cmsTable->drawOneCell($this->moduleSortLinks($query->field('id')), "switch");
			
			/**
			 * Closing this row
			 */
			$this->cmsTable->endOneRow($query->field('id'));
		}
		
		/**
		 * Closing this table
		 */
		$this->cmsTable->endTable();
		$returnHtml = $this->cmsTable->returnTable;
				
		return $returnHtml;
	}
	
	/**
	 * Get next edit id
	 *
	 * @param int	language id
	 */
	public function getNextId($id) {
		
		$sqlWhere = $id ? "WHERE id > '" . $id . "'" : "";
		
		$dbQuery = "SELECT id FROM `ad_languages` " . $sqlWhere . " GROUP BY id " . $this->moduleTableSqlParms();
		$query = new query($this->db, $dbQuery);
		return $query->getOne();
	}
	
	/**
	 * Enable or disable language
	 * 
	 * @param int/array 	language id
	 * @param bool 			enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			$countrySqlWhere =  (is_array($id) ? "`lang_id` IN (" . implode(",", $id) . ")" : "`lang_id` = '" . $id . "'");

			//Check if language is set as default 
			$countryQuery = "
				SELECT ltc.`default` 
				FROM `ad_languages_to_ct`  ltc
				WHERE 
					ltc.`default` = '1' 
			";
			$countryQuery .= ' AND '.$countrySqlWhere;
			$country_results = new query($this->db, $countryQuery);

			if(!$country_results->getrow()){
				$sqlWhere =  (is_array($id) ? "`id` IN (" . implode(",", $id) . ")" : "`id` = '" . $id . "'");

				$dbQuery = "
					UPDATE `ad_languages` 
					SET `enable` = '" . $value . "'
					WHERE ". $sqlWhere;
				$query = new query($this->db, $dbQuery);
	            
	            if($value == 0){
	                deleteFromDbById("ad_languages_to_ct", $id, "lang_id");
	            }
        	}
		}			
	}
	
	/**
	 * Delete language from DB
	 * 
	 * @param int/Array 	language id
	 */
	public function delete($id) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			deleteFromDbById("ad_languages_to_ct", $id, "lang_id");
			deleteFromDbById("ad_languages", $id);
		}		
	}
	
	/**
	 * Edit language in DB
	 * 
	 * @param int 	language id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();

		if(isset($id) && $id != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
            $dbQuery = "
            	SELECT l.*, lc.`default`
            	FROM `ad_languages` AS l 
            	LEFT JOIN `ad_languages_to_ct` AS lc 
            		ON lc.lang_id = l.id AND lc.`default` = '1'
            	WHERE 
            		l.`id` = '" . $id . "'
            ";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();
			
		}
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id ? $id : '';
		
		return jsonSend($r);		
	}
	
	/**
	 * Saving language information in DB
	 * 
	 * @param int	language id, it's need if we are editing language
	 * @param array language information values
	 */
	public function save($id, $value) {

		if(intval($id)){
			$id = intval($id);
		}
		else{
			$id = false;
		}
			
		$value = addSlashesDeep(jsonDecode($value));

		$countryQuery = "
			SELECT ltc.`default` 
			FROM `ad_languages_to_ct`  ltc
			WHERE 
				ltc.`default` = '1' 
				AND ltc.`lang_id` = '".$id."'		
		";

		$country_results = new query($this->db, $countryQuery);

		if($country_results->getrow()){
			unset($value['enable']);
		}
			
		$id = saveValuesInDb("ad_languages", $value, $id);		
		
		return $id;
	}
	
	/**
	 * Checking for uniq language name
	 * 
	 * @param int 		language id
	 * @param string	language name 
	 */
	public function checkName($id, $value) {

		$result = false;
		
		$dbQuery = "SELECT `id` FROM `ad_languages` WHERE `lang` = '" . $value . "'";
 		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			if ($id && $id == $query->getOne()) {
				$result = true;
			}
		}
		else {
			$result = true;
		}
		
		return $result;
	}
	
	/**
	 * Changing languages sort order
	 * 
	 * @param int		language id
	 * @param string	sort changing value
	 */
	public function changeSort($id, $value) {
		
		$dbQuery = "SELECT * FROM `ad_languages` WHERE `id` = '" . $id . "'";
		$query = new query($this->db, $dbQuery);
		$content = $query->getrow();
		
		if ($value == "down") {
			$sqlParm = ">";
			$sqlParm2 = "ASC";	
		}
		else {
			$sqlParm = "<";
			$sqlParm2 = "DESC";
		}
		
		$dbQuery = "SELECT `id`, `sort` FROM `ad_languages` WHERE `sort` " . $sqlParm . " '" . $content['sort'] . "' ORDER BY `sort` " . $sqlParm2 . " LIMIT 0,1";
		$query->query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			$info = $query->getrow();
			
			$dbQuery = "UPDATE `ad_languages` SET `sort` = '" . $content['sort'] . "' WHERE `id` = '" . $info['id'] . "'";
			$query->query($this->db, $dbQuery);
			
			$dbQuery = "UPDATE `ad_languages` SET `sort` = '" . $info['sort'] . "' WHERE `id` = '" . $id . "'";
			$query->query($this->db, $dbQuery);
			
		}
	}
	
}
?>