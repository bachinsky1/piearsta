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
 * CMS messages params module
 * Admin path. Edit/Add/Delete and other actions with messages params
 * This is module to put some variable to messages
 * 14.12.2008
 */

class messages_adminData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "messages_admin";
		$this->dbTable = "ad_messages_backend";
		$this->dbTableLang = "ad_messages_backend_info";
	}
	
	/**
	 * Get all messages data from db and create module table
	 */
	public function showMessagesTable() {
		header("Content-type:text/html");
		$returnHtml = "";			
		/**
		 * Getting filter entries
		 * And creating sql where
		 */
		$filterModule = trim(getP("filterModule"));
		$notTranslated = trim(getP("notTranslated"));
		$filterSearch = trim(getP("filterSearch"));

		$module_where = '';
		if($filterModule === '0'){
			$module_where = "m.module_id IS NULL ";	
		}
		else if(intval($filterModule)){
			$module_where = "m.module_id = '" . intval($filterModule) . "' ";
		}

		$search_where = $filterSearch ? " (m.name LIKE '%" . $filterSearch . "%' OR m.description LIKE '%" . $filterSearch . "%' OR mi.value LIKE '%" . $filterSearch . "%')" : "";
		$not_translated_join = $notTranslated ? (" AND mi.lang = '" . $notTranslated . "'") : "";
		$not_translated_where = $notTranslated ? ("(mi.value IS NULL OR BINARY m.name = mi.value)") : "";

		$sqlWhere = '';
		$sqlWhere .= $module_where;
		if(!empty($search_where) && !empty($module_where)){
			$sqlWhere .= ' AND ';	
		}
		$sqlWhere  .= $search_where;
		if((!empty($search_where) || !empty($module_where) ) && !empty($not_translated_where)){
			$sqlWhere .= ' AND ';	
		}
		$sqlWhere  .= $not_translated_where;

		/**
		 * Creating module table, using cmsTable class
		 * This is table head information
		 */
		$tableHeadValues = array(
			'&nbsp;', 
			gLA('m_date','Date'),
			gLA('m_module','Module'),
			gLA('m_name','Name'),
			gLA('m_description','Description'),
			gLA('m_js','JS'),
			gLA('m_enable','Enable'),
			gLA('m_actions','Actions')
		);
		$tableHeadSort = array(false, true, true, true, true, false, false, false);
		$tableHeadSortFields = array('', 'm.date', 'm.module_id', 'm.name', 'm.description', '', '', '');
		
		/**
		 * Start table and creating table head
		 */
		$this->cmsTable->startTable();
		$this->cmsTable->addColgroup(array(1, 120, '', '', '', 1, 1, 105));
		$this->cmsTable->tableType = 'edit';
		$this->cmsTable->drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields);

		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "
			SELECT SQL_CALC_FOUND_ROWS
				m.id, m.name, m.description, m.date, m.enable, m.js,
				module.name AS module_name, module.translations		
			FROM `ad_messages_backend` m
			LEFT JOIN `ad_messages_backend_info` mi 
				ON (mi.id = m.id " . (!empty($not_translated_where) ? $not_translated_join :'') . " )
			LEFT JOIN `ad_modules` module 
				ON (module.id = m.module_id)
		";
		if(!empty($sqlWhere)){
			$dbQuery .= " WHERE " . $sqlWhere ;
		}
		$dbQuery .= " GROUP BY m.id " . $this->moduleTableSqlParms("m.date", "DESC");
		
		$query = new query($this->db, $dbQuery);
		$count = new query($this->db, 'SELECT FOUND_ROWS()');
		$returnHtml['rCounts'] = $count->getOne();
		
		while ($query->getrow()) {
			
			$translations = unserialize($query->field('translations'));
			
			/**
			 * Creating one row
			 */
			$this->cmsTable->startOneRow("", "tr_" . $query->field('id'));
			
			/**
			 * Draw all table cells
			 */
			$this->cmsTable->drawOneCell($this->moduleCheckboxLink($query->field('id'), $this->getModuleName()), "check", "firstTd_" . $query->field('id'));
			$this->cmsTable->drawOneCell(date("d-m-Y H:i", $query->field('date')), (getG("sortField") == "m.date" ? 'sort' : ''));
			$module_name = isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('module_name');
			
			if ($module_name == "")
				$module_name = "Base";

			$this->cmsTable->drawOneCell($module_name, (getG("sortField") == "m.module_id" ? 'sort' : ''));
			$this->cmsTable->drawOneCell($query->field('name'), (getG("sortField") == "m.name" ? 'sort' : ''));
			$this->cmsTable->drawOneCell($query->field('description'), (getG("sortField") == "m.description" ? 'sort' : ''));
			$this->cmsTable->drawOneCell($this->moduleJsLink($query->field('id'), $query->field('js')), "");
			$this->cmsTable->drawOneCell($this->moduleEnableLink($query->field('id'), $query->field('enable')), "ac");
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

		$returnHtml['html'] = $this->cmsTable->returnTable;
		
		return $returnHtml;
	}
	
	/**
	 * Get next edit id
	 *
	 * @param int	message id if is
	 */
	public function getNextId($mId) {
		
		/**
		 * Getting filter entries
		 * And creating sql where
		 */
		$filterModule = getP("filterModule");
		$notTranslated = getP("notTranslated");
		$filterSearch = ($notTranslated != "" ? "" : getP("filterSearch"));
		
		$sqlWhere = $filterModule ? " AND m.module_id = '" . $filterModule . "'" : "";
		$sqlWhere .= $filterSearch ? " AND (m.name LIKE '%" . $filterSearch . "%' OR m.description LIKE '%" . $filterSearch . "%' OR mi.value LIKE '%" . $filterSearch . "%')" : "";
		$sqlWhere .= $notTranslated ? ($notTranslated == "all" ? " AND mi.value = ''" : " AND mi.value = '' AND mi.lang = '" . $notTranslated . "'") : "";
		$sqlWhere .= $mId ? " AND m.id > '" . $mId . "'" : "";
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "
			SELECT m.id 
			FROM `".$this->dbTable."` m, `".$this->dbTableLang."` mi, `ad_modules` module 
			WHERE
				m.module_id = module.id 
				AND m.id = mi.id" . $sqlWhere . "
			GROUP BY m.id 
					" . $this->moduleTableSqlParms();

		$query = new query($this->db, $dbQuery);
		return $query->getOne();
	}
	
	/**
	 * Creating module list for filter
	 * 
	 * @param int	id of selected module
	 */
	public function createModuleList($mSel = "", array $predefined_values = array()) {
			
		$dbQuery = "
			SELECT * 
			FROM `ad_modules` m 
			WHERE 
				m.public = '0' OR
				m.menuname = 'modules' OR
				m.name = 'content'
		";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			
			$translations = unserialize($query->field('translations'));
			$values[$query->field('id')] = isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('name');
		}
		if (!empty($predefined_values)) {
			$values = $predefined_values + $values;
			
		}
		if(!empty($mSel))
			$mSel = (int) $mSel;

		return dropDownFieldOptions($values, $mSel, true,'debug');
		
	}
	
	/**
	 * Creating country list for filter
	 * 
	 * @param int	id
	 */
	public function getCountryList($id = "") {
		
		$dbQuery = "SELECT * FROM `ad_countries`";
		$query = new query($this->db, $dbQuery);
		while ($query->getrow()) {
			$values[$query->field('id')] = $query->field('title');
		}
		
		return dropDownFieldOptions($values, $id, true);
		
	}
	
	/**
	 * Enable or disable message
	 * 
	 * @param int/array of message id
	 * @param bool of enable/disable value
	 */
	public function enableMessage($mId, $value) {
		
		if (!is_numeric($mId)) {
			$mId = addSlashesDeep(jsonDecode($mId));
		}
		
		if (!empty($mId)) {
			$dbQuery = "UPDATE `".$this->dbTable."` SET `enable` = '" . $value . "' WHERE " . (is_array($mId) ? "`id` IN (" . implode(",", $mId) . ")" : "`id` = '" . $mId . "'");
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	public function enableJs($mId, $value) {
		
		if (!is_numeric($mId)) {
			$mId = addSlashesDeep(jsonDecode($mId));
		}
		
		if (!empty($mId)) {
			$dbQuery = "UPDATE `".$this->dbTable."` SET `js` = '" . $value . "' WHERE " . (is_array($mId) ? "`id` IN (" . implode(",", $mId) . ")" : "`id` = '" . $mId . "'");
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Delete message from DB
	 * 
	 * @param int/Array of message id
	 */
	public function deleteMessage($mId) {
		
		if (!is_numeric($mId)) {
			$mId = addSlashesDeep(jsonDecode($mId));
		}
		
		if (!empty($mId)) {
			deleteFromDbById($this->dbTableLang, $mId);
			deleteFromDbById($this->dbTable, $mId);
		}		
	}
	
	/**
	 * Edit message in DB
	 * 
	 * @param int 	message id, it's need if we are editing tmplparm
	 */
	public function editMessage($mId = "") {
		$data = array();
		$predefined_filter_values = array(0 => "Base");
		$data["langauges"] = $this->cmsConfig->get('cmsAllLangs');

		foreach ($data["langauges"] as $key => $value) 
			if ($value["enabled"] != 1)
				unset($data["langauges"][$key]);
		
		if(isset($mId) && $mId != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "
				SELECT * 
				FROM `".$this->dbTable."` 
				WHERE `id` = '" . $mId . "'
				LIMIT 0,1
			";
					
			$query = new query($this->db, $dbQuery);
			
			$data["edit"] = $query->getrow();
			
			$data["edit"]["module_list"] = $this->createModuleList($data['edit']['module_id'],$predefined_filter_values);
			$dbQuery2 = "
				SELECT *
				FROM `" . $this->dbTableLang . "`
				WHERE `id` = '" . $mId . "'
				LIMIT 0,
			3";
			$query2 = new query($this->db, $dbQuery2);
			
			while ($query2->getrow()) {
				$data["edit"]["values"][$query2->field('lang')] = $query2->field('value');
			}
			
		}
		else {		
			$data["edit"]["enable"] = 1;
			$data["edit"]["js"] = 0;
			$data["edit"]["module_id"] = $this->createModuleList('',$predefined_filter_values);	
		}
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $mId ? $mId : '';

		return jsonSend($r);		
	}
	
	/**
	 * Saving message information in DB
	 * 
	 * @param int	message id, it's need if we are editing message
	 * @param array message information values
	 * @param array message languages values information
	 */
	public function saveMessage($mId, $value, $langValues) {
		$value = addSlashesDeep(jsonDecode($value));
		$langValues = addSlashesDeep(jsonDecode($langValues));

		$value["date"] = time();

		if(!intval($value['module_id']) || $value['module_id'] == 0){
			unset($value['module_id']);
		} 

		$mId_new = saveValuesInDb($this->dbTable, $value, $mId);		

		foreach ($langValues as $k => $v) {
			$langValues[str_replace('value_', '', $k)] = $v;
			unset($langValues[$k]);

		}

		$siteLangs = $this->cmsConfig->get('cmsAllLangs');
		foreach ($siteLangs as $key => $values){ 
			if ($values["enabled"] == 1){

				$partner_data = array(
					'id' => $mId_new,
					'lang' => $key,
					'value' => $langValues[$key]
				);

				if ($partner_data['value'] != ''){
					if($mId){	
						$dbQuery = "
							INSERT INTO ".$this->dbTableLang." (`id`, `lang`, `value`) 
								VALUES ('" . $mId_new . "', '".$key."', '".$partner_data['value']."')
							ON DUPLICATE KEY UPDATE
								`value` = '".$partner_data['value']."'
							
						";
						$query = new query($this->db, $dbQuery);	
					}
					else {
						saveValuesInDb($this->dbTableLang, $partner_data);
					}
				}
			}
		}
		return $mId;
	}
	
	/**
	 * Checking for uniq tmplparm name
	 * 
	 * @param int 		message id, it's need if we are editing menu
	 * @param string	message name 
	 */
	public function checkName($mId, $value) {

		$result = false;
		$value = jsonDecode($value);
        $value = addSlashesDeep($value);
		$dbQuery = "SELECT `id` FROM `".$this->dbTable."` WHERE `name` = '" . $value['0'] . "' AND `module_id` = '" . $value['1'] . "'";
 		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			if ($mId && $mId == $query->getOne()) {
				$result = true;
			}
		}
		else {
			$result = true;
		}
		return $result;
	}
	
}
?>