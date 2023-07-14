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
 * CMS countries module
 * Admin path. Edit/Sort/Enable/Disable and other actions with site countries
 * This is general countries module of cms
 * 10.05.2010
 */

class countriesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	public $uploadFolder = 'countries/';
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "countries";
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
			gLA('m_actions','Actions')
		);
		$tableHeadSort = array(false, false);
		$tableHeadSortFields = array('', '');
		
		/**
		 * Start table and creating table head
		 */
		$this->cmsTable->startTable();
		$this->cmsTable->tableType = 'edit';
		$this->cmsTable->drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields);
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT * FROM `ad_countries` ORDER BY `id` ASC";
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
			$this->cmsTable->drawOneCell($this->moduleActionsLink($query->field('id')), "", "lastTd_" . $query->field('id'));
			
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
	 * @param int	country id
	 */
	public function getNextId($id) {
		
		if(intval($id)){
			$sqlWhere = $id ? "WHERE id > '" . $id . "'" : "";
		
			$dbQuery = "SELECT id FROM `ad_countries` " . $sqlWhere . " GROUP BY id " . $this->moduleTableSqlParms();
			$query = new query($this->db, $dbQuery);
			return $query->getOne();	
		}
		return false;
	}
	
	/**
	 * Delete country from DB
	 * 
	 * @param int/Array 	country id
	 */
	public function delete($id) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			deleteFromDbById("ad_languages_to_ct", $id, "country_id");
			deleteFromDbById("ad_countries_domains", $id, "country_id");
			deleteFromDbById("ad_countries", $id);
		}		
	}
	
	/**
	 * Edit country in DB
	 * 
	 * @param int 	country id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();
		$r = array();

		if(isset($id) && intval($id)) {
			
			/**
			 * Getting all information from DB about this country
			 */
			$dbQuery = "
				SELECT * 
				FROM `ad_countries` 
				WHERE `id` = '" . $id . "'
			";
			$query = new query($this->db, $dbQuery);		
			$country_data = $query->getrow();

			if($country_data){

				$data["edit"] = $country_data;
				
				$dbQuery = "
					SELECT * 
					FROM `ad_countries_domains` 
					WHERE `country_id` = '" . $id . "'
				";
				$query = new query($this->db, $dbQuery);

				while ($query->getrow()) {
					$data["edit"]["domains"][] = array(
						'id' => $query->field('id'), 
						'url' => $query->field('domain'), 
						'default' => $query->field('default')
					);
				}
				
				$dbQuery = "
					SELECT * 
					FROM `ad_languages` WHERE `enable` = '1'
				";					
				$query = new query($this->db, $dbQuery);

				while ($query->getrow()) {
					
					$data["edit"]["langs"][$query->field('lang')]["title"] = $query->field('title');
					$data["edit"]["langs"][$query->field('lang')]["id"] = $query->field('id');
					
					$dbQuery = "
						SELECT * 
						FROM `ad_languages_to_ct` ct 
						WHERE 
							ct.lang_id = '" . $query->field('id') . "' 
							AND ct.`country_id` = '" . $id . "'
					";
					$queryCT = new query($this->db, $dbQuery);	

					while ($queryCT->getrow()) {
						
						$data["edit"]["langs"][$query->field('lang')]["info"]["default"] = $queryCT->field('default');
						$data["edit"]["langs"][$query->field('lang')]["info"]["main_id"] = $queryCT->field('main_id');
						
						if ($data["edit"]["langs"][$query->field('lang')]["info"]["main_id"] != 0)  {			
							$data["edit"]["langs"][$query->field('lang')]["info"]["main_title"] = $this->getContentTitle($data["edit"]["langs"][$query->field('lang')]["info"]["main_id"]);
						} else {
							$data["edit"]["langs"][$query->field('lang')]["info"]["main_id"] = '';
						}
						
						

					}
				}
				
				$data['edit']['uploadFolder'] = $this->uploadFolder;

				$r["html"] = $this->tpl->output("edit", $data);
				$r["id"] = $id ? $id : '';	
			}		
		}

		return jsonSend($r);		
	}
	
	/**
	 * Saving country information in DB
	 * 
	 * @param int		country id, it's need if we are editing country
	 * @param array 	country information values
	 * @param array		domains
	 * @param array		languages info
	 */
	public function save($id, $value, $domains, $languages) {
		
		$value = addSlashesDeep(jsonDecode($value));
		$domains = addSlashesDeep(jsonDecode($domains));
		$languages = addSlashesDeep(jsonDecode($languages));

		$dbQuery = "SELECT `id` FROM `ad_countries` WHERE `id` = '".intval($id)."'";
		$country = new query($this->db, $dbQuery);

		if($country->getrow()){

			$id = saveValuesInDb("ad_countries", $value, $id);

			if (!empty($id)) {
				deleteFromDbById("ad_languages_to_ct", $id, "country_id");
				deleteFromDbById("ad_countries_domains", $id, "country_id");
			}

			$default_domain = false;
			if(isset($domains['default_domain'])){
				$default_domain = $domains['default_domain'];
				unset($domains['default_domain']);
			}

			foreach($domains as $d_id => $d_value) {
				$d_options = array();
				if(intval($d_id) && !empty($d_value)){
					$d_options['country_id'] = $id;
					$d_options['domain'] = $d_value;

					if($default_domain == $d_id){ 
						$d_options['default'] = 1;
					}

					saveValuesInDb('ad_countries_domains', $d_options);
				}
			}

			foreach ($languages AS $l => $info) {
				$a = array();
				
				$a["country_id"] = $id;
				$a["lang_id"] = $l;
				if($info["main_id"] && intval($info["main_id"])){
					$a["main_id"] = $info["main_id"];	
				}
				
				$a["default"] = $info["default"];
				
				saveValuesInDb("ad_languages_to_ct", $a);
			}

			return $id;
		}
		else{
			return false;
		}
	}
	
}
?>