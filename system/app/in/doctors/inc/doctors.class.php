<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class doctorsData extends Module_cms {

    protected $dbTable;
    protected $uploadFolder = 'doctors/';
    public $imagesConfig = array(
		'list' => array('width' => '100', 'height' => '110'),
		'open' => array('width' => '260', 'height' => '286'),
    );

    /**
     * Constructor
     */
    public function __construct() {

		parent :: __construct();
		$this->name = "doctors";
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
		    'surname' => array(
				'sort' => true,
				'title' => gLA('m_surname', 'Surname'),
				'function' => array(&$this, 'clear'),
				'fields' => array('surname')
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
		    "created" => array(
		        'sort' => false,
		        'title' => gLA('m_created','Created'),
		        'function' => 'convertDate',
		        'fields'	=> array('created'),
		        'params' => array('d-m-Y H:i:s')
		    ),
		    'actions' => array(
				'sort' => false,
				'title' => gLA('m_actions', 'Actions'),
				'function' => array(&$this, 'moduleActionsLink'),
				'fields' => array('id', 'local')
		    )
		);
	
		/**
		 * Getting filter entries
		 * And creating sql where
		 */
		$sqlWhere = array();
		$sqlWhereIds = array();
		$filterIds = false;
	
		if (getP("filterName")) {
		    $sqlWhere[] = "di.`name` LIKE '%" . getP("filterName") . "%'";
		}
	
		if (getP("filterSurname")) {
		    $sqlWhere[] = "di.`surname` LIKE '%" . getP("filterSurname") . "%'";
		}
	
		if (getP("filterPhone")) {
		    $sqlWhere[] = "d.`phone` LIKE '%" . getP("filterPhone") . "%'";
		}
	
		if (getP("filterEmail")) {
		    $sqlWhere[] = "d.`email` LIKE '%" . getP("filterEmail") . "%'";
		}
		
		if (getP("filterType") !== false) {
		    $sqlWhere[] = "d.`local` = " . getP("filterType") . "";
		}
		
		if (getP("filterClinics")) {
		    $sqlWhereIds = array_merge($sqlWhereIds, $this->_getDoctorsByClinics(getP("filterClinics")));
		}
	
		if (count($sqlWhereIds) > 0) {
		    $sqlWhere[] = "`id` IN (" . implode(",", $sqlWhereIds) . ")";
		}
	
	
		// SQL request for promo blocks
		$dbQuery = "SELECT d.*, di.*, d.id AS id
						FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` di ON (d.id = di.doctor_id AND di.lang = '" . $this->defLang . "')";
		if (count($sqlWhere) > 0) {
		    $dbQuery .= " WHERE " . implode(" AND ", $sqlWhere);
		}
		$query = new query($this->db, $dbQuery);
	
		// Set total count
		$return['rCounts'] = $query->num_rows();
	
		$dbQuery .= $this->moduleTableSqlParms('d.id', 'DESC');
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

    private function _getDoctorsByClinics($filterClinics) {
		$return = array();
	
		$dbQuery = "SELECT `dtc`.`d_id`
			    		FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (`c`.`id` = `dtc`.`c_id`)
			    		WHERE 1
							AND `c`.`name` LIKE '%" . $filterClinics . "%'
							OR `c`.`reg_nr` LIKE '%" . $filterClinics . "%'
						";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows()) {
		    $return = $query->getArray('d_id', false);
		}
	
		return $return;
    }

    public function moduleActionsLink($id, $local = false) {
		$return = '';
	
		if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
		    $return .= '<a class="edit" href="javascript:;" onclick="moduleView(\'' . $id . '\'); return false;">' . gLA('m_view', 'View') . '</a>';
		}
		
		if ($local && $this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
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
						(SELECT count(id) FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r WHERE d.id = r.doctor_id AND r.profile_id IS NULL) AS terminal_count,
						(SELECT count(id) FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r2 WHERE d.id = r2.doctor_id AND r2.profile_id IS NOT NULL) AS web_count	 
					FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d 
					WHERE d.`id` = " . mres($id);
		    $query = new query($this->db, $dbQuery);
		    if ($query->num_rows()) {
				$data['edit'] = $query->getrow();
				
				if (!$view && $data['edit']['local'] != 1) {
					$this->redirectToModuleRoot();
				}
				
				$dbQuery = "SELECT * FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` WHERE `doctor_id` = '" . $id . "'";
				$query = new query($this->db, $dbQuery);
				
				$dataLang = $query->getArray();
					
				foreach ($dataLang as $key => $value) {
					$data["edit"]["name"][$value["lang"]] = $value["name"];
					$data["edit"]["surname"][$value["lang"]] = $value["surname"];
					$data["edit"]["description"][$value["lang"]] = $value["description"];
				}
		
				$data['edit']['clinics'] = $this->_getLinkedClinics($id);
				$data['edit']['specialities'] = $this->_getLinkedClassificators($id, CLASSIF_SPECIALTY);
				$data['edit']['services'] = $this->_getLinkedClassificators($id, CLASSIF_SERVICE);
		    }
		}
	
		$data['edit']['uploadFolder'] = $this->uploadFolder;

		$data['edit']['external']['clinics'] = $this->_getClinics($id);
		$data['edit']['external']['specialities'] = $this->_getClassificators(CLASSIF_SPECIALTY);
		$data['edit']['external']['services'] = $this->_getClassificators(CLASSIF_SERVICE);
	
		return $data;
    }

    public function save($id, $value) {
		
    	$langValues = getP('langValues');
		$clinics = getP('clinics');
		$specialities = getP('specialities');
		$services = getP('services');
	
		$value["updated"] = time();
		if (!$id) {
		    $value["created"] = time();
		    $value["local"] = 1;
		}
		
		if (isset($value['photo'])) {
		    $in = AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . $value["photo"];
	
		    $this->image = &loadLibClass('image');
	
		    if (file_exists(mkFileName($in, '', 'list/'))) {
				list($w, $h) = getimagesize(mkFileName($in, '', 'list/'));
				if ($w > $this->imagesConfig['list']['width']) {
			   	 $this->image->resizeImg($in, mkFileName($in, '', 'list/'), $this->imagesConfig['list']['width'], $this->imagesConfig['list']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
				}
		    } else {
				$this->image->resizeImg($in, mkFileName($in, '', 'list/'), $this->imagesConfig['list']['width'], $this->imagesConfig['list']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
		    }
	
		    if (file_exists(mkFileName($in, '', 'open/'))) {
				list($w, $h) = getimagesize(mkFileName($in, '', 'open/'));
				if ($w > $this->imagesConfig['open']['width']) {
			  	  $this->image->resizeImg($in, mkFileName($in, '', 'open/'), $this->imagesConfig['open']['width'], $this->imagesConfig['open']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
				}
		    } else {
				$this->image->resizeImg($in, mkFileName($in, '', 'open/'), $this->imagesConfig['open']['width'], $this->imagesConfig['open']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
		    }
		}
	
		$id = saveValuesInDb($this->cfg->getDbTable('doctors', 'self'), $value, $id);
		
		$privateUrl = convertUrl($langValues['name'][$this->defLang] . '-' . $langValues['surname'][$this->defLang] . '-' . $id);
		saveValuesInDb($this->cfg->getDbTable('doctors', 'self'), array('url' => $privateUrl), $id);
		
		$siteLangs = getSiteLangs();
		deleteFromDbById($this->cfg->getDbTable('doctors', 'info'), $id, 'doctor_id');
		foreach ($siteLangs as $key => $values) {
		
			$data = array(
				'doctor_id' => $id,
				'lang' => $values['lang'],
				'name' => $langValues['name'][$values['lang']],
				'surname' => $langValues['surname'][$values['lang']],
				'description' => $langValues['description'][$values['lang']],
			);
			saveValuesInDb($this->cfg->getDbTable('doctors', 'info'), $data);
		}
	
		deleteFromDbById($this->cfg->getDbTable('doctors', 'clinics'), $id, 'd_id');
		if ($clinics && count($clinics)) {
		    foreach ($clinics as $clinic) {
		    	$dbQuery = "INSERT INTO `" . $this->cfg->getDbTable('doctors', 'clinics') . "` VALUES (" . $id . ", " . $clinic . ")";
				doQuery($this->db, $dbQuery);
		    }
		}
	
		// setting relation with specialities
		$this->_updateLinkedClassificators($id, CLASSIF_SPECIALTY, $specialities);
	
		// setting relation with services
		$this->_updateLinkedClassificators($id, CLASSIF_SERVICE, $services);
	
		return $id;
    }

    private function _updateLinkedClassificators($doc_id, $classif_type, $classifiers = array()) {

		// clearing previos link
    	$dbQuery = "DELETE FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` WHERE `d_id` = " . $doc_id . " AND `cl_type` = '" . $classif_type . "'";
		doQuery($this->db, $dbQuery);
		
		// setting up new link if classificators provided
		if (is_array($classifiers) && count($classifiers)) {
		    foreach ($classifiers as $classifier) {
		    	$dbQuery = "INSERT INTO `" . $this->cfg->getDbTable('doctors', 'classificators') . "` VALUES (" . $doc_id . ", " . $classif_type . ", " . $classifier . ", null)";
				doQuery($this->db, $dbQuery);
		    }
		}
    }

    private function _getLinkedClassificators($doc_id, $classif_type) {
		$dbQuery = "SELECT cli.*
			    		FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl
			    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'self') . "` cl ON (cl.`id` = `dtcl`.`cl_id` AND `cl`.`type` = `dtcl`.`cl_type` AND `cl`.`enable` = 1)
			    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cli ON (cli.`c_id` = `cl`.`id` AND `cli`.`lang` = '" . $this->defLang . "')
			    		WHERE 1
							AND `dtcl`.`d_id` = " . mres($doc_id) . "
							AND `dtcl`.`cl_type` = '" . $classif_type . "'
			    		";
		$query = new query($this->db, $dbQuery);
		return $query->getArray();
    }

    private function _getLinkedClinics($doc_id) {
		$dbQuery = "SELECT `c`.*
			    		FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc
			    			LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self') . "` c ON (`c`.`id` = `dtc`.`c_id`)
			    		WHERE 1
							AND `dtc`.`d_id` = " . $doc_id . "";
		$query = new query($this->db, $dbQuery);
		
		$data = array();
		while ($row = $query->getrow()) {
			
			$row['shedule'] = $this->getShedule($doc_id, $row['id'], $date = false, $days = 6);
			$data[] = $row;
		}
		return $data;
    }

    private function _getClassificators($classif_type) {
		$dbQuery = "SELECT `cli`.*
						FROM `" . $this->cfg->getDbTable('classificators', 'self') . "` cl
			    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cli ON (`cli`.`c_id` = `cl`.`id` AND `cli`.`lang` = '" . $this->defLang . "')
			    		WHERE 1
							AND cl.`enable` = 1
							AND cl.`type` = '" . $classif_type . "'
			    ";
		$query = new query($this->db, $dbQuery);
		return $query->getArray();
    }

    private function _getClinics() {
		$dbQuery = "SELECT `c`.*
			    		FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c";
		$query = new query($this->db, $dbQuery);
		return $query->getArray();
    }
    
    public function delete($id) {

		if (!is_numeric($id)) {
		    $id = addSlashesDeep(jsonDecode($id));
		}
	
		if (!empty($id)) {
		    deleteFromDbById($this->cfg->getDbTable('doctors', 'self'), $id);
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
    
    public function getShedule($doctorId = false, $clinicId = false, $date = false, $days = 13, $filter = array())
    {
    	if (!$date) {
    		if (date('N') == 7) {
    			$date = date("Y-m-d", strtotime('last monday'));
    			$firstMonth = date("F", strtotime('last monday'));
    		} else {
    			$date = date("Y-m-d", strtotime('monday this week'));
    			$firstMonth = date("F", strtotime('monday this week'));
    		}
    
    	} else {
    		if (getP('type') == 'next') {
    			$date = date("Y-m-d", strtotime($date) + 86400);
    			$firstMonth = date("F", strtotime($date) + 86400);
    		} elseif (getP('type') == 'prev') {
    			$date = strtotime($date) - (86400 * getP('days'));
    			$firstMonth = date("F", $date);
    			$date = date("Y-m-d", $date);
    			 
    		} else {
    			$firstMonth = date("F", strtotime($date));
    			$date = date("Y-m-d", strtotime($date));
    		}
    	}
    	 
    	$shedule = loadLibClass('shedule');
    	$interval = $shedule->getWeekDays($date, $days);
    	 
    	$data = $shedule->getDoctorShedule($doctorId, $clinicId, $date, $days, $filter);
    	 
    	$sheduleData = array(
    		'data' => $data['data'],
    		'prev' => $data['prev'],
    		'next' => $data['next'],
    		'week' => $shedule->getStartAndEndDate($date, $days),
    		'interval' => $interval,
    		'intervalTablet' => array_slice($interval, 0, 10),
    		'intervalMobile' => array_slice($interval, 0, 7),
    		'firstMonth' => gL("month_" . $firstMonth, $firstMonth),
    		'lastDate' => $shedule->getLastDate($doctorId, $clinicId),
    	);
    	 
    	return $sheduleData;
    }

}

?>