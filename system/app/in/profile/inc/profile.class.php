<?php

class profileData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	protected $config = array(
		'months' => array(
			'1' => "January",
			'2' => "February",
			'3' => "March",
			'4' => "April",
			'5' => "May",
			'6' => "June",
			'7' => "July",
			'8' => "August",
			'9' => "September",
			'10' => "October",
			'11' => "November",
			'12' => "December",
		)						
	);
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "profile";
		
		$this->defLang = getDefaultLang();
		
		$this->dbTable = $this->cfg->getDbTable('profiles', 'self');
		
		$this->cl = loadLibClass('cl');
		
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
				'params' => array($this->name)
			),
			"email" => array(
				'sort' => false,
				'title' => gLA('email','Email'),
				'function' => '',
			),
			"name" => array(
				'sort' => false,
				'title' => gLA('first_name','First name'),
				'function' => '',
			),
			"surname" => array(
				'sort' => false,
				'title' => gLA('surname','Last Name'),
				'function' => '',
			),
			"phone" => array(
				'sort' => false,
				'title' => gLA('phone','Phone'),
				'function' => '',
			),
			"created" => array(
				'sort' => false,
				'title' => gLA('created','Created'),
				'function' => 'convertDate',
				'fields'	=> array('created'),
				'params' => array('d-m-Y')
			),
			"enable" => array(
				'sort' => false,
				'title' => gLA('m_enable','Enable'),
				'function' => array(&$this, 'moduleEnableLink'),
				'fields'	=> array('id', 'enable')
			),
			"hash_confirm" => array(
				'sort' => false,
				'title' => gLA('m_active','Active'),
				'function' => array(&$this, 'activeData'),
				'fields'	=> array('hash_confirm')
			),
			"actions" => array(
				'sort' => false,
				'title' => gLA('m_actions','Actions'),
				'function' => array(&$this, 'moduleActionsLink'),
				'fields'	=> array('id')
			)
		);

		if (getP("itemsFrom") !== false) {
			$_SESSION['ad_' . $this->getModuleName()]["itemsFrom"] = getP("itemsFrom");
		}	
		elseif (isset($_SESSION['ad_' . $this->getModuleName()]["itemsFrom"])) {
			$_POST["itemsFrom"] = $_SESSION['ad_' . $this->getModuleName()]["itemsFrom"];
		}	

		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * 
							FROM `" . $this->dbTable . "` 
							WHERE 1
							" . $this->getSearchQuery() . $this->moduleTableSqlParms("id", "DESC");
		$query = new query($this->db, $dbQuery);
		
		
		$result["rCounts"] = $this->getTotalRecordsCount(false);

		// Create module table
		$this->cmsTable->createTable($table, $query->getArray());
		$result["html"] = $this->cmsTable->returnTable;
			
		return $result;
		
	}
	
	public function activeData($hash)
	{
		if ($hash) {
			return 'No';
		} else {
			return 'Yes';
		}
	}
	
	public function export() {
		require_once(AD_LIB_FOLDER . 'PHPExcel/PHPExcel.php');
		require_once(AD_LIB_FOLDER . 'PHPExcel/PHPExcel/Writer/Excel5.php');
		
		$dbQuery = "SELECT p.*, cid.title AS insurance, ccd.title AS city, cdd.title AS district
							FROM `" . $this->dbTable . "` p
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'self')	 . "` ci ON (p.insurance_id = ci.id)
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details')	 . "` cid ON (cid.c_id = ci.id AND cid.lang = '" . $this->getLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'self')	 . "` cc ON (p.city_id = cc.id)
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details')	 . "` ccd ON (ccd.c_id = cc.id AND ccd.lang = '" . $this->getLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'self')	 . "` cd ON (p.district_id = cd.id)
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details')	 . "` cdd ON (cdd.c_id = cd.id AND cdd.lang = '" . $this->getLang() . "')
							WHERE 1";
		$query = new query($this->db, $dbQuery);
		
		$excel = new PHPExcel();
		$excel->setActiveSheetIndex(0);
		$excel->getActiveSheet()->setTitle('Export');
		
		
		$headFields = array(
			'id' => 'ID',
			'email' => 'Email',
			'name' => 'Name',
			'surname' => 'Surname',
			'phone' => 'Phone',
			'resident' => 'Resident',
			'person_id' => 'Person ID',
			'person_number' => 'Person Number',
			'gender' => 'Gender',
			'date_of_birth' => 'Date of birth',
			'enable' => 'Enabled',
			'created' => 'Created',
			'insurance' => 'Insurance',
			'insurance_number' => 'Insurance number',
			'city' => 'City',
			'district' => 'District',
			'lang' => 'Lang',
			'email_notifications' => 'Email notifications',
			'sms_notifications' => 'Sms notifications',
			'deleted' => 'Deleted',
			'deleted_at' => 'Deleted At',
		);
		
		
		
		$F = 'A';
		foreach ($headFields AS $field) {
			$excel->getActiveSheet(0)->setCellValue($F . "1", $field);
			$excel->getActiveSheet(0)->getColumnDimension($F)->setAutoSize(true);
			$F++;
		}
		
		$i = 2;
		while ($query->getrow()) {
			$F = 'A';
			foreach ($headFields AS $key => $field) {
				
				if (in_array($key, array('deleted_at', 'created'))) {
					if ($query->field($key) > 0) {
						$excel->getActiveSheet(0)->setCellValue($F . $i, date(PIEARSTA_DT_FORMAT, $query->field($key)));
					} else {
						$excel->getActiveSheet(0)->setCellValue($F . $i, '-');
					}
					
				} elseif (in_array($key, array('deleted', 'sms_notifications', 'email_notifications', 'enable', 'resident'))) {
					$excel->getActiveSheet(0)->setCellValue($F . $i, ($query->field($key) ? 'Yes' : 'No'));
				} else {
					$excel->getActiveSheet(0)->setCellValue($F . $i, $query->field($key));
				}
				
				$excel->getActiveSheet(0)->getColumnDimension($F)->setAutoSize(true);
				$F++;
			}
			$i++;
		}
		
		$eWriter = new PHPExcel_Writer_Excel5($excel);
		
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="profiles_export.xls"');
		header('Cache-Control: max-age=0');
		
		$eWriter->save('php://output');
		
		exit;
		
	}
	
	public function getSearchQuery() {
		$search = '';
	
		if (getP('filterName')) {
			$search .= " AND name LIKE '%" . mres(getP('filterName')) . "%'";
			$_SESSION['filters'][$this->name]["filterName"] = getP('filterName');
		} elseif (isset($_SESSION['filters'][$this->name]["filterName"])){
			$search .= " AND name LIKE '%" . mres($_SESSION['filters'][$this->name]["filterName"]) . "%'";
		}
		
		if (getP('filterLastName')) {
			$search .= " AND surname LIKE '%" . mres(getP('filterLastName')) . "%'";
			$_SESSION['filters'][$this->name]["filterName"] = getP('filterLastName');
		} elseif (isset($_SESSION['filters'][$this->name]["filterLastName"])){
			$search .= " AND surname LIKE '%" . mres($_SESSION['filters'][$this->name]["filterLastName"]) . "%'";
		}
		
		if (getP('filterEmail')) {
			$search .= " AND email LIKE '%" . mres(getP('filterEmail')) . "%'";
			$_SESSION['filters'][$this->name]["filterEmail"] = getP('filterEmail');
		} elseif (isset($_SESSION['filters'][$this->name]["filterEmail"])){
			$search .= " AND email LIKE '%" . mres($_SESSION['filters'][$this->name]["filterEmail"]) . "%'";
		}
		
		if (getP('filterPhone')) {
			$search .= " AND phone LIKE '%" . mres(getP('filterPhone')) . "%'";
			$_SESSION['filters'][$this->name]["filterPhone"] = getP('filterPhone');
		} elseif (isset($_SESSION['filters'][$this->name]["filterPhone"])){
			$search .= " AND phone LIKE '%" . mres($_SESSION['filters'][$this->name]["filterPhone"]) . "%'";
		}
		
	
		return $search;
	}
	
	/**
	 * Enable or disable
	 * 
	 * @param int/array 	news id
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
	 * Delete news from DB
	 * 
	 * @param int/Array 	news id
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
	 * Edit news in DB
	 * 
	 * @param int 	news id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();

		if(isset($id) && intval($id)) {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * FROM `" . $this->dbTable . "` 
								WHERE 1 
									AND `id` = '" . $id . "'
								LIMIT 0,1";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();
			$data["edit"]['messages'] = $this->getMessages($data["edit"]['id']);
			$data["edit"]['persons'] = $this->getPersons($data["edit"]['id']);
			
			if ($data["edit"]['date_of_birth']) {
				$data["edit"]['date_of_birth_splited'] = explode('-', $data["edit"]['date_of_birth']);
			}
			
			$data['langs'] = getSiteLanguageDropDown($data["edit"]['lang']);
			
		} else {
			$data['langs'] = getSiteLanguageDropDown();
		}
		
		$classificators['city'] = $this->cl->getListByType(CLASSIF_CITY);
		$classificators['district'] = $this->cl->getListByType(CLASSIF_DISTRICT);
		$classificators['ic'] = $this->cl->getListByType(CLASSIF_IC);
	
		$data['cl'] = $classificators;
		$data['genders'] = $this->types = $this->cfg->get('genders');
		$data['months'] = $this->config['months'];
		
		return $data;
	}
	
	public function getMessages($id) {
		$dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('profiles', 'messages') . "`
							WHERE 1
								AND `profile_id` = '" . mres($id) . "'";
		$query = new query($this->db, $dbQuery);
		return $query->getArray('id');
	}
	
	public function getPersons($id) {
		$dbQuery = "SELECT p.*
							FROM `" . $this->cfg->getDbTable('profiles', 'persons')	 . "` p
							WHERE 1
								AND p.`profile_id` = '" . mres($id) . "'
							ORDER BY created DESC";
		$query = new query($this->db, $dbQuery);
		return $query->getArray('id');

	}

    /**
     * Saving information in DB
     *
     * @param $id
     * @param $value
     * @return bool|string
     */
	public function save($id, $value) {
		
		$langValues = getP('valueLangs');
		$siteLangs = getSiteLangs();
		$message = getP('message');
		
		if (!$id) {
			$value["created"] = time();
		}
		
		/*if ($value['password'] != '') {
			$value['password'] = md5($value['password']);
		}*/
		
		if ($value["bd_year"] && $value["bd_month"] && $value["bd_date"]) {
			$value['date_of_birth'] = $value["bd_year"] . "-" . str_pad($value["bd_month"], 2, "0", STR_PAD_LEFT) . "-" . $value["bd_date"];
		}
		
		if ($value['resident']) {
			$value['person_number'] = '';
		} else {
			$value['person_id'] = '';
		}
		
		unset($value["bd_year"]);
		unset($value["bd_month"]);
		unset($value["bd_date"]);
			
		$id = saveValuesInDb($this->dbTable, $value, $id);	
		
		if (!empty($message['message'])) {
			$dbData['profile_id'] = $id;
			$dbData['message'] = $message['message'];
			$dbData['subject'] = $message['subject'];
			$dbData['created'] = time();
				
			saveValuesInDb($this->cfg->getDbTable('profiles', 'messages'), $dbData);
		}
		

		return $id;
	}
	
	public function saveMessage($id) {
		$message = getP('message');
	
		if (!empty($message['message'])) {
			$dbData['profile_id'] = $id;
			$dbData['message'] = $message['message'];
			$dbData['subject'] = $message['subject'];
			$dbData['created'] = time();
	
			saveValuesInDb($this->cfg->getDbTable('profiles', 'messages'), $dbData);
		}
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT * FROM `" . $this->dbTable . "`
								WHERE 1
									AND `id` = '" . $id . "'
								LIMIT 0,1";
		$query = new query($this->db, $dbQuery);
			
		$data["edit"] = $query->getrow();
		$data["edit"]['messages'] = $this->getMessages($data["edit"]['id']);

		return array('html' => $this->tpl->output('messages_form', $data));
	}
	
}
?>