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

class messagesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "messages";
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
		$filterModule = getP("filterModule");
		$notTranslated = getP("notTranslated");
		$filterSearch = getP("filterSearch");
		
		$sqlWhere = $filterModule ? " AND m.module_id = '" . $filterModule . "'" : "";
		$sqlWhere .= $filterSearch ? " AND (m.name LIKE '%" . $filterSearch . "%' OR m.description LIKE '%" . $filterSearch . "%' OR mi.value LIKE '%" . $filterSearch . "%')" : "";
		$sqlWhere .= $notTranslated ? (" AND (mi.value = '' OR m.name = mi.value) AND mi.lang = '" . $notTranslated . "'") : "";
		
		/**
		 * Creating module table, using cmsTable class
		 * This is table head information
		 */
		$tableHeadValues = array(
			'&nbsp;', 
			gLA('m_date','Date'),
			gLA('m_module','Module'),
			gLA('m_name','Name'),
			gLA('m_description','Descriptions'),
			gLA('m_enable','Enabled'),
			gLA('m_actions','Actions'),
		);
		$tableHeadSort = array(false, true, true, true, true, false, false);
		$tableHeadSortFields = Array('', 'm.date', 'm.module_id', 'm.name', 'm.description', '', '');
		
		/**
		 * Start table and creating table head
		 */
		$this->cmsTable->startTable();
		$this->cmsTable->addColgroup(array(1, 120, '', '', '', 1, 105));
		$this->cmsTable->tableType = 'edit';
		$this->cmsTable->drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields);

		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS
									m.id, m.name, m.description, m.date, m.enable,
									module.name AS module_name, module.translations		
							FROM `ad_messages` m
							LEFT JOIN `ad_messages_info` mi ON (mi.id = m.id)
							LEFT JOIN `ad_modules` module ON (module.id = m.module_id)
							WHERE 1 " . $sqlWhere . " 
							GROUP BY m.id
							" . $this->moduleTableSqlParms("m.date", "DESC");
		$query = new query($this->db, $dbQuery);
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
			$this->cmsTable->drawOneCell(isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('module_name'), (getG("sortField") == "m.module_id" ? 'sort' : ''));
			$this->cmsTable->drawOneCell($query->field('name'), (getG("sortField") == "m.name" ? 'sort' : ''));
			$this->cmsTable->drawOneCell($query->field('description'), (getG("sortField") == "m.description" ? 'sort' : ''));
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
		$returnHtml = $this->cmsTable->returnTable;
				
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
		$dbQuery = "SELECT m.id FROM `ad_messages` m, `ad_messages_info` mi, `ad_modules` module WHERE m.module_id = module.id AND m.id = mi.id" . $sqlWhere . " GROUP BY m.id 
					" . $this->moduleTableSqlParms();
		$query = new query($this->db, $dbQuery);
		return $query->getOne();
	}
	
	/**
	 * Creating module list for filter
	 * 
	 * @param int	id of selected module
	 */
	public function createModuleList($mSel = "") {
		$values = array();

		$dbQuery = "SELECT * FROM `ad_modules` m WHERE m.`public` = '1'";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			
			$translations = unserialize($query->field('translations'));
			$values[$query->field('id')] = isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('name');
		}
		
		return dropDownFieldOptions($values, $mSel, true);
		
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
			$dbQuery = "UPDATE `ad_messages` SET `enable` = '" . $value . "' WHERE " . (is_array($mId) ? "`id` IN (" . implode(",", $mId) . ")" : "`id` = '" . $mId . "'");
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
			deleteFromDbById("ad_messages_info", $mId);
			deleteFromDbById("ad_messages", $mId);
		}		
	}
	
	/**
	 * Edit message in DB
	 * 
	 * @param int 	message id, it's need if we are editing tmplparm
	 */
	public function editMessage($mId = "") {
		
		$data = array();
		
		$data["countries"] = getSiteLAndC();
		$data["langauges"] = getSiteLangs();

		if(isset($mId) && $mId != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * " .
						"FROM `ad_messages` " .
						"WHERE `id` = '" . $mId . "'" .
						" LIMIT 0,1";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();
			$data["edit"]["module_id"] = $this->createModuleList($query->field('module_id'));
			$data["edit"]["typeOptions"] = dropDownFieldOptions(
				array(
					"l" => gLA('language_specific','Language specific'), 
					"c" => gLA('country_specific','Country specific'),
				), 
				$query->field('type'), 
				true
			);		
			
			$dbQuery = "SELECT * " .
						"FROM `ad_messages_info` " .
						"WHERE `id` = '" . $mId . "'";
			$query = new query($this->db, $dbQuery);		
			while ($query->getrow()) {
				$data["edit"]["values"][$query->field('country')][$query->field('lang')] = $query->field('value');
			}
			
		}
		else {		
			$data["edit"]["enable"] = 1;
			$data["edit"]["module_id"] = $this->createModuleList();
			$data["edit"]['type'] = 'l';
			$data["edit"]["typeOptions"] = dropDownFieldOptions(
				array(
					"l" => gLA('language_specific','Language specific'), 
					"c" => gLA('country_specific','Country specific'),
				), 
				'', 
				true
			);	
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
		
		$value["date"] = time();
		
		$mId = saveValuesInDb("ad_messages", $value, $mId);		
		
		if (!isset($value["type"])) {
			$value["type"] = 'l';
		}
		
		foreach ($langValues AS $k => $v) {
			$key = substr(str_replace('value_', '', $k), 0, 1);
			if ($value["type"] == 'l') {
				if ($key > 0) {
					unset($langValues[$k]);
				}
			} elseif ($value["type"] == 'c') {
				if ($key == 0) {
					unset($langValues[$k]);
				}
			}
		}

		saveCountryValues("ad_messages_info", $mId, $langValues, "value");
		
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
		
		$dbQuery = "SELECT `id` FROM `ad_messages` WHERE `name` = '" . $value . "'";
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