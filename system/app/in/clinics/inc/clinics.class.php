<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class clinicsData extends Module_cms {

    protected $dbTable;
    protected $uploadFolder = 'clinics/';
    public $imagesConfig = array(
		'list' => array('width' => '100', 'height' => '110'),
		'open' => array('width' => '260', 'height' => '286'),
    );

    /**
     * Constructor
     */
    public function __construct() {

		parent :: __construct();
		$this->name = "clinics";
		$this->defLang = getDefaultLang();
		
		if (!is_dir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder)) {
		    @mkdir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder);
		    @chmod(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder, 0777);
		}
		
		if (!is_dir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'list/')) {
		    @mkdir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'list/');
		    @chmod(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'list/', 0777);
		}
		
		if (!is_dir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'open/')) {
		    @mkdir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'open/');
		    @chmod(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'open/', 0777);
		}
    }

    /**
     * Returns HTML as promo blocks table content
     */
    public function showTable() {

		$table = array(
		    'id' => array(
				'sort' => true,
				'title' => gLA('m_id', 'Id'),
		    ),
		    'name' => array(
				'sort' => true,
				'title' => gLA('m_name', 'Name'),
				'function' => array(&$this, 'clear'),
				'fields' => array('name')
		    ),
		    'reg_nr' => array(
				'sort' => true,
				'title' => gLA('m_registration_nr', 'Registration Nr.'),
				'function' => array(&$this, 'clear'),
				'fields' => array('reg_nr')
		    ),
		    'phone' => array(
				'sort' => true,
				'title' => gLA('m_phone', 'Phone'),
				'function' => array(&$this, 'clear'),
				'fields' => array('phone')
		    ),
		    'email' => array(
				'sort' => true,
				'title' => gLA('m_email', 'E-mail'),
				'function' => array(&$this, 'clear'),
				'fields' => array('email')
		    ),
		    'local' => array(
				'sort' => true,
				'title' => gLA('m_type', 'Type'),
				'function' => array(&$this, 'moduleShowType'),
				'fields' => array('local')
		    ),
		    'actions' => array(
				'sort' => false,
				'title' => gLA('m_actions', 'Actions'),
				'function' => array(&$this, 'moduleActionsLink'),
				'fields' => array('id', 'local')
		    ),
		);
	
		/**
		 * Getting filter entries
		 * And creating sql where
		 */
		$sqlWhere = array();
	
		if (getP("filterName")) {
		    $sqlWhere[] = "cd.`name` LIKE '%" . getP("filterName") . "%'";
		}
	
		if (getP("filterRegNr")) {
		    $sqlWhere[] = "c.`reg_nr` LIKE '%" . getP("filterRegNr") . "%'";
		}
	
		if (getP("filterPhone")) {
		    $sqlWhere[] = "c.`phone` LIKE '%" . getP("filterPhone") . "%'";
		}
	
		if (getP("filterEmail")) {
		    $sqlWhere[] = "c.`email` LIKE '%" . getP("filterEmail") . "%'";
		}
		
		if (getP("filterType") !== false) {
		    $sqlWhere[] = "c.`local` = " . getP("filterType") . "";
		}
	
		$dbQuery = "SELECT c.*, cd.*, cc.*, c.id as id
					FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
						LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` cd ON (c.id = cd.clinic_id AND cd.lang = '" . $this->defLang . "')
						LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts') . "` cc ON (c.id = cc.clinic_id AND cc.default = 1)		
					WHERE 1";
		if (count($sqlWhere) > 0) {
		    $dbQuery .= " AND " . implode(" AND ", $sqlWhere);
		}
		$query = new query($this->db, $dbQuery);
	
		// Set total count
		$return['rCounts'] = $query->num_rows();
	
		$dbQuery .= $this->moduleTableSqlParms('c.id', 'DESC');
		$query = new query($this->db, $dbQuery);
	
		// Create table
		$this->cmsTable->createTable($table, $query->getArray());
		$return['html'] = $this->cmsTable->returnTable;
	
		return $return;
	}
	    
	public function moduleShowType($local) {
		$return = '';
		
		if ($local) {
		    $return = gLA('m_type_local', 'Manual');
		} else {
		    $return = gLA('m_type_imported', 'Auto');
		}
		
		return $return;
	}
	
	public function moduleActionsLink($id, $local = false) {
		$return = '';
	
		/*if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
		    $return .= '<a class="edit" href="javascript:;" onclick="moduleView(\'' . $id . '\'); return false;">' . gLA('m_view', 'View') . '</a>';
		}*/
		
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
		    $return .= '<a class="edit" href="javascript:;" onclick="moduleEdit(\'' . $id . '\'); return false;">' . gLA('m_edit', 'Edit') . '</a>';
		}
		
		if ($local && $this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
		    $return .= '<a href="javascript:;" onclick="moduleDelete(\'' . $id . '\'); return false;">' . gLA('m_delete','Delete') . '</a>';
		}
		
		return $return;
    }
    
	public function edit($id = 0, $view = false) {
		$data = array();
		$data['edit']['id'] = 0;
		$data["langauges"] = getSiteLangs();
		$data['view'] = $view;

		if ($id) {
	    	$dbQuery = "SELECT *,
	    						(SELECT count(id) FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r WHERE c.id = r.clinic_id AND (r.profile_id IS NULL OR r.profile_id = 1)) AS terminal_count,
								(SELECT count(id) FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r2 WHERE c.id = r2.clinic_id AND r2.profile_id IS NOT NULL AND r2.profile_id <> 1) AS web_count 
	    					FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
							WHERE `id` = " . mres($id) . "";
	    	$query = new query($this->db, $dbQuery);
	    	if ($query->num_rows()) {
				$data['edit'] = $query->getrow();
				
				if (!$view && $data['edit']['local'] != 1) {
					//$this->redirectToModuleRoot();
				}
				
				$dbQuery = "SELECT * FROM `" . $this->cfg->getDbTable('clinics', 'info') . "` WHERE `clinic_id` = '" . $id . "'";
				$query = new query($this->db, $dbQuery);
				
				$dataLang = $query->getArray();
					
				foreach ($dataLang as $key => $value) {
					//$data["edit"]["about"][$value["lang"]] = $value["about"];
					$data["edit"]["address"][$value["lang"]] = $value["address"];
					$data["edit"]["keywords"][$value["lang"]] = $value["keywords"];
					$data["edit"]["description"][$value["lang"]] = $value["description"];
				}
				
				$data['edit']['contacts'] = $this->getClinicsContacts($id);
				
				$data['edit']['doctors'] = $this->_getLinkedDoctors($id);
			}
		}

		$data['edit']['uploadFolder'] = $this->uploadFolder;
		$data['edit']['external']['doctors'] = $this->_getDoctors();
		$data['edit']['external']['cities'] = $this->_getClassificators(CLASSIF_CITY);
		$data['edit']['external']['districts'] = $this->_getClassificators(CLASSIF_DISTRICT);
		//pR($data['edit']['external']);
		
		return $data;
	}
	
	protected function getClinicsContacts($id)
	{
		$contacts = array();
		$dbQuery = "SELECT * FROM `" . $this->cfg->getDbTable('clinics', 'contacts') . "` WHERE `clinic_id` = '" . $id . "'";
		$query = new query($this->db, $dbQuery);
		while ($row = $query->getrow()) {
	
			$dbQuery = "SELECT * FROM `" . $this->cfg->getDbTable('clinics', 'contacts_info') . "` WHERE `clinic_contact_id` = '" . $row['id'] . "'";
			$queryData = new query($this->db, $dbQuery);
			$row['lang_data'] = $queryData->getArray('lang');
	
	
			$contacts[] = $row;
		}
		 
		return $contacts;
	}

	public function save($id, $value) {

		$doctors = getP('doctors');
		$langValues = getP('langValues');
		$contacts = getP('contacts');

		$value['updated'] = time();
		if (!$id) {
	    	$value["created"] = time();
	    	$value["local"] = 1;
		}

		if (empty($value['district'])) {
			$value['district'] = 'null';
		}
	
		if (isset($value['logo'])) {
	    	$in = AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . $value['logo'];

	    	$this->image = &loadLibClass('image');

	    	if (file_exists(mkFileName($in, '', 'list/'))) {
				list($w, $h) = getimagesize(mkFileName($in, '', 'list/'));
				if ($w != $this->imagesConfig['list']['width'] || $h != $this->imagesConfig['list']['height']) {
		    		$this->image->resizeImg($in, mkFileName($in, '', 'list/'), $this->imagesConfig['list']['width'], $this->imagesConfig['list']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
				}
	    	} else {
				$this->image->resizeImg($in, mkFileName($in, '', 'list/'), $this->imagesConfig['list']['width'], $this->imagesConfig['list']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
	    	}

	    	if (file_exists(mkFileName($in, '', 'open/'))) {
				list($w, $h) = getimagesize(mkFileName($in, '', 'open/'));
				if ($w != $this->imagesConfig['open']['width'] || $h != $this->imagesConfig['open']['height']) {
					$this->image->resizeImg($in, mkFileName($in, '', 'open/'), $this->imagesConfig['open']['width'], $this->imagesConfig['open']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
				}
	    	} else {
				$this->image->resizeImg($in, mkFileName($in, '', 'open/'), $this->imagesConfig['open']['width'], $this->imagesConfig['open']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
	    	}
		}

		$id = saveValuesInDb($this->cfg->getDbTable('clinics', 'self'), $value, $id);
	
		$privateUrl = convertUrl($value['name'] . '-' . $id);
		saveValuesInDb($this->cfg->getDbTable('clinics', 'self'), array('url' => $privateUrl), $id);
		
		$siteLangs = getSiteLangs();
		deleteFromDbById($this->cfg->getDbTable('clinics', 'info'), $id, 'clinic_id');
		foreach ($siteLangs as $key => $values) {
				
			$data = array(
				'clinic_id' => $id,
				'lang' => $values['lang'],
				'address' => $langValues['address'][$values['lang']],
				'description' => $langValues['description'][$values['lang']],
				'keywords' => $langValues['keywords'][$values['lang']],
			);
			saveValuesInDb($this->cfg->getDbTable('clinics', 'info'), $data);
		}

		deleteFromDbById($this->cfg->getDbTable('doctors', 'clinics'), $id, 'c_id');
		if ($doctors && count($doctors)) {
	    	foreach ($doctors as $doctor) {
	    		$dbQuery = "INSERT INTO `" . $this->cfg->getDbTable('doctors', 'clinics') . "` (d_id, c_id) VALUES (" . $doctor . ", " . $id . ")";
				doQuery($this->db, $dbQuery);
	    	}
		}
		
		deleteFromDbById($this->cfg->getDbTable('clinics', 'contacts'), $id, 'clinic_id');
		$default = false;
		foreach ($contacts as $contact) {
			
			if (!empty($contact['name'][$this->defLang]) && (!empty($contact['email']) || !empty($contact['phone']))) {
				
				$data = array(
					'clinic_id' => $id,
					'email' => $contact['email'],
					'phone' => $contact['phone'],
				);
				
				if (!$default) {
					$data['default'] = 1;
					$default = true;
				}
				
				$contactId = saveValuesInDb($this->cfg->getDbTable('clinics', 'contacts'), $data);
					
				foreach ($siteLangs as $key => $values) {
						
					$data = array(
						'clinic_contact_id' => $contactId,
						'lang' => $values['lang'],
						'name' => $contact['name'][$values['lang']],
					);
					saveValuesInDb($this->cfg->getDbTable('clinics', 'contacts_info'), $data);
				}
			}
			
			
		}

		return $id;
	}

	private function _getDoctors() {
		$dbQuery = "SELECT d.*, di.*, d.id AS id
		    			FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
		    				LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` di ON (d.`id` = `di`.doctor_id AND di.lang = '" .$this->defLang . "')";
		$query = new query($this->db, $dbQuery);
		return $query->getArray();
	}

	private function _getLinkedDoctors($c_id) {
		$dbQuery = "SELECT `d`.*, di.*, d.id AS id
		    			FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc
		    				LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dtc`.`d_id`)
		    				LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` di ON (`d`.`id` = `di`.doctor_id AND di.lang = '" .$this->defLang . "')		
						WHERE 1
							AND `dtc`.`c_id` = " . mres($c_id) . "";
		$query = new query($this->db, $dbQuery);
		return $query->getArray();
	}
    
	private function _getLinkedClassificator($id, $classif_type) {
		if (!$id) {
			return "";
		}
		
		$dbQuery = "SELECT `ClInfo`.`title`
						FROM `" . $this->cfg->getDbTable('classificators', 'self') . "` as `Cl`
		    				LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` as `ClInfo` ON (`ClInfo`.`c_id` = `Cl`.`id` AND `ClInfo`.`lang` = '" . $this->defLang . "')
						WHERE 1
							AND `Cl`.`id` = " . $id . "
							AND `Cl`.`enable` = 1
							AND `Cl`.`type` = '" . $classif_type . "'";
		$query = new query($this->db, $dbQuery);
		return $query->getOne();
	}

	private function _getClassificators($classif_type) {
		$dbQuery = "SELECT `ClInfo`.*
						FROM `" . $this->cfg->getDbTable('classificators', 'self') . "` as `Cl`
							LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` as `ClInfo` ON (`ClInfo`.`c_id` = `Cl`.`id` AND `ClInfo`.`lang` = '" . $this->defLang . "')
						WHERE 1
							AND `Cl`.`enable` = 1
							AND `Cl`.`type` = '" . $classif_type . "'";
		$query = new query($this->db, $dbQuery);
		return $query->getArray();
	}
    
	public function delete($id) {

		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}

		if (!empty($id)) {
			deleteFromDbById($this->cfg->getDbTable('clinics', 'self'), $id);
		}
	}

    /**
     * Redirects to list
     */
	public function redirectToModuleRoot() {
		$segments = $this->uri->segmentArray();
		while (count($segments) > 3) {
			array_pop($segments);
		}
		redirect("/" . implode("/", $segments));
    }

	public function clear($text) {
		return stripslashes($text);
	}
}