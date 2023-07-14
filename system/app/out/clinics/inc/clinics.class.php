<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class clinicsData extends Module {

    private $config = array('uploadFolder' => 'clinics/');
    private $itemsPerPage = 15;
    private $filters = array(
        'main' => array(
            'clinics_filter_search', 'clinics_filter_city', 'clinics_filter_district',
            'doctors_filter_services',
        )
    );

    /** @var array|null  */
    private $subscription = null;

    private $allowed_clinics;

    /**
     * Class constructor
     */
    public function __construct() {

        parent :: __construct();
        $this->name = 'clinics';

        if(!empty($_SESSION['user']) && !empty($_SESSION['user']['dcSubscription'])) {

            if(!empty($_SESSION['user']['dcSubscription']['product_clinic'])) {

                $this->subscription = array(
                    'clinicId' => $_SESSION['user']['dcSubscription']['product_clinic'],
                    'network' => null,
                );

            } elseif (!empty($_SESSION['user']['dcSubscription']['product_network'])) {

                $this->subscription = array(
                    'clinicId' => null,
                    'network' => $_SESSION['user']['dcSubscription']['product_network'],
                );
            }
        }

        $this->allowed_clinics = $this->setAllowedClinics();
    }

    private function setAllowedClinics(){
        $result = false;
        if (defined('ALLOWED_CLINICS')){
            if (ALLOWED_CLINICS === '/'){
                $result = true;
            } else {
                $result = ALLOWED_CLINICS;
            }
        }
        return $result;
    }

    public function setFilters($fields)
    {
        if (!empty($fields)) {

            if ($fields['action'] == 'setFilters') {

                unset($_SESSION['additional_filters']);

                $field = $fields['fields'];

                if (!empty($field)){
                    $this->updateSessionData($field);
                }
            }
        }
    }

    /**
     * @param array $fields
     * @return void
     */
    private function updateSessionData($fields)
    {

        foreach ($fields as $key => $field) {
            if (!empty($field)) {
                if ($key == 'dcDoctors') {
                    $_SESSION['additional_filters'][$key] = 'true';
                } else {
                    $_SESSION['additional_filters'][$key] = $field;
                }
            }
        }

    }

    public function showList() {

        $currDate = date('Y-m-d H:i:s', time());
        $this->setPData(true, 'clinics_list');
        $page = getGP('clinics_page') ? getGP('clinics_page') : 0;

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }

        if (getP('ajax_search')) {

            $offset = $page * $this->itemsPerPage;
            $itemCount = $this->itemsPerPage;
            $loadedCount = $offset + $itemCount;

        } else {

            $offset = 0;
            $itemCount = ($page + 1) * $this->itemsPerPage;
            $loadedCount = $itemCount;
        }

        $this->setPData($page, 'current_page');

        $filters = $this->_getFilteredIds();

        $this->setPData((isset($filters['advanced']) && $filters['advanced']) || isset($_SESSION['find']), 'showAdvanced');

        unset($_SESSION['additional_filters']);

        if(isset($_SESSION['find']) && !empty($_SESSION['find'])) {
            $this->setPData($_SESSION['find'], 'find');
            unset($_SESSION['find']);
        }

        if ($filters['applied'] && !count($filters['ids'])) {

            $total_count = 0;

        } else {

            // Check homepage items table -- if there are collected items
            // this table used for correct sort order of clinics.
            // We are ordering clinics by quantity of doctors, which have schedules and than by cleaned clinic's title
            // Home page items collected by cron homepage_items_collect.php

            // If in any reason homepage items table is empty,
            // we use another request (as it was before new sorting logic implementation)

            $hasSortedClinics = false;

            // check if homepage items table exists
            $dbQuery = "SHOW TABLES LIKE 'mod_homepage_items'";
            $query = new query($this->db, $dbQuery);
            $tableExists = $query->num_rows() > 0;

            $nr = 0;

            if($tableExists) {
                $dbQuery = "SELECT id, type FROM mod_homepage_items 
                        WHERE 
                            type = 9 AND 
                            title > ''";
                $query = new query($this->db, $dbQuery);

                $nr = $query->num_rows();
            }

            if($nr > 0) {

                $dbQuery = "SELECT COUNT(id) AS ccount FROM mod_clinics WHERE name > ''";
                $query = new query($this->db, $dbQuery);

                $ccount = intval($query->getrow()['ccount']);

                $hasSortedClinics = $nr == $ccount;
            }


            if($hasSortedClinics) {

                $dbQuery = "SELECT `c`.*, `ci`.*, cc.*, `c`.id AS id, clicity.title AS citytitle, clidistrict.title AS districttitle, hpi.title_clean AS title_clean, hpi.doctors_with_schedules AS doctors_with_schedules, c2n.network_id as network_id
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci ON
				    			ci.id = (SELECT ci1.id FROM `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci1 WHERE c.id = ci1.clinic_id LIMIT 1)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts') . "` cc ON (c.id = cc.clinic_id AND cc.default = 1)
								LEFT JOIN ins_clinic_to_networks c2n ON (c2n.clinic_id = c.id AND start_datetime <= '$currDate' AND end_datetime > '$currDate') 
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clicity ON (`clicity`.`c_id` = `c`.`city` AND `clicity`.`lang` = '" . getDefaultLang() . "')
		    					LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clidistrict ON (`clidistrict`.`c_id` = `c`.`district` AND `clidistrict`.`lang` = '" . getDefaultLang() . "')
		    					LEFT JOIN mod_homepage_items hpi ON (hpi.original_id = c.id)
							WHERE 1 
							" . $clinicIdFilter . "
							AND c.enabled = 1 AND
							    hpi.type = 9 AND
							    ci.lang = '" . getDefaultLang() . "'
								" . ($filters['applied'] ? " AND `c`.`id` IN (" . implode(',', $filters['ids']) . ")" : "") . "
							GROUP BY `c`.`id`
							ORDER BY doctors_with_schedules DESC, title_clean ASC";

            } else {

                $dbQuery = "SELECT `c`.*, `ci`.*, cc.*, `c`.id AS id, clicity.title AS citytitle, clidistrict.title AS districttitle, c2n.network_id as network_id 
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci ON
				    			ci.id = (SELECT ci1.id FROM `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci1 WHERE c.id = ci1.clinic_id LIMIT 1)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts') . "` cc ON (c.id = cc.clinic_id AND cc.default = 1)
								LEFT JOIN ins_clinic_to_networks c2n ON (c2n.clinic_id = c.id AND start_datetime <= '$currDate' AND end_datetime > '$currDate')
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clicity ON (`clicity`.`c_id` = `c`.`city` AND `clicity`.`lang` = '" . getDefaultLang() . "')
		    					LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clidistrict ON (`clidistrict`.`c_id` = `c`.`district` AND `clidistrict`.`lang` = '" . getDefaultLang() . "')
							WHERE 1
							" . $clinicIdFilter . "  
								AND c.enabled = 1 
								AND ci.lang = '" . getDefaultLang() . "'
								" . ($filters['applied'] ? " AND `c`.`id` IN (" . implode(',', $filters['ids']) . ")" : "") . "
							GROUP BY `c`.`id`
							ORDER BY `c`.`name` ASC";
            }

            $query = new query($this->db, $dbQuery);

            $total_count = $query->num_rows();
        }

        $showMore = $total_count > 0 && $loadedCount < $total_count ? true : false;

        $this->setPData($total_count, 'found_clinics');

        if ($total_count) {

            $dbQuery .= " LIMIT " . $offset . ", " . $itemCount;
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows()) {

                $clinics = $query->getArray();

                foreach ($clinics as $k => $clinic) {

                    $hasSubscription = false;

                    if(!empty($_SESSION['user']) && !empty($_SESSION['user']['dcSubscription'])) {

                        if(!empty($_SESSION['user']['dcSubscription']['product_clinic'])) {

                            $hasSubscription = $_SESSION['user']['dcSubscription']['product_clinic'] == $clinic['id'];

                        } elseif (!empty($_SESSION['user']['dcSubscription']['product_network'])) {

                            $hasSubscription = $_SESSION['user']['dcSubscription']['product_network'] == $clinic['network_id'];
                        }
                    }

                    $clinics[$k]['hasSubscription'] = $hasSubscription;
                }

                $this->setPData($clinics, "clinics");
                $this->setPData($this->config, "clinicsConfig");
            }
        }

        if (getP('ajax_search')) {

            $this->noLayout(true);

            $result = array(
                'content' => $this->tpl->output("_data", $this->getPData()),
                'show_more' => $showMore,
                'total' => $total_count
            );

            jsonSend($result);

        } else {

            $cl = loadLibClass('cl');
            $classificators = $cl->getList('id', true, array(), false, true);
            $this->setPData($classificators[CLASSIF_CITY], 'cityList');
            $this->setPData($classificators[CLASSIF_DISTRICT], 'districtList');


            if (!empty($classificators[CLASSIF_SERVICE])) {

                $existsManipulations = array();

                $dbQuery = "SELECT DISTINCT(dtcl.cl_id) 
                        FROM `mod_clinics` c 
                        LEFT JOIN `mod_doctors_to_clinics` dtc ON (c.id = dtc.c_id) 
                        LEFT JOIN `mod_doctors_to_classificators` dtcl ON (dtc.d_id = dtcl.d_id) 
                        WHERE 1 AND 
                            `dtcl`.`cl_type` = " . CLASSIF_SERVICE . " 
                            " . $clinicIdFilter . "
                            AND c.enabled = 1" ;

                $query = new query($this->db, $dbQuery);

                if ($query->num_rows()) {

                    $existsManipulations = $query->getArray('cl_id');
                    $classificators[CLASSIF_SERVICE] = array_intersect_key($classificators[CLASSIF_SERVICE], $existsManipulations);
                }


                $this->setPData($classificators[CLASSIF_SERVICE], 'servicesList');
            }

            $this->setPData($showMore, 'show_more');
            $this->tpl->assign("TEMPLATE_CLINICS_MODULE_DATA", $this->tpl->output("_data", $this->getPData()));
            $this->tpl->assign("TEMPLATE_CLINICS_MODULE_FILTERS", $this->tpl->output("_filters", $this->getPData()));
            $this->tpl->assign("TEMPLATE_CLINICS_MODULE", $this->tpl->output("list", $this->getPData()));

            return $this;
        }
    }

    private function _parseFilters($filters = array()) {

        $return = array();

        if (isset($filters['main'])) {
            $return = array_merge($return, $filters['main']);
        }

        if (isset($filters['fast'])) {
            $return = array_merge($return, $filters['fast']);
        }

        return $return;
    }


    private function _parseGetFilters() {
        foreach ($this->filters as $type => $arr) {
            foreach ($arr as $filter_name) {
                $val = getG($filter_name);
                if ($val) {
                    $_POST['clinics_filters'][$type][$filter_name] = sanitize($val);
                }
            }
        }
    }


    private function _getFilteredIds() {

        $this->_parseGetFilters();
        $filters = $this->_parseFilters(getP('clinics_filters'));

        if (!empty($_SESSION['additional_filters'])) {

            $additionalFilters = $_SESSION['additional_filters'];

            if (!empty($additionalFilters['clinics_filter_search'])) {
                $filters['clinics_filter_search'] = $additionalFilters['clinics_filter_search'];
            }

            if (!empty($additionalFilters['cilnics_filter_city'])) {
                $filters['cilnics_filter_city'] = $additionalFilters['cilnics_filter_city'];
            }

            if (!empty($additionalFilters['doctors_filter_services'])) {
                $filters['doctors_filter_services'] = $additionalFilters['doctors_filter_services'];
            }

            if (!empty($additionalFilters['dcDoctors'])) {
                $filters['dcDoctors'] = true;
            }
        }


        $this->setPData($filters, 'filters');
        $return = array(
            'applied' => false,
            'ids' => array()
        );
        if (!$filters) {
            return $return;
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }

        if ($filters['clinics_filter_search'] != 'false') {

            $searchString = html_entity_decode(mres($filters['clinics_filter_search']));

            $dbQuery = "SELECT `c`.`id`, `c`.`name`, `c`.`reg_nr`
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` `dtc` ON (`dtc`.`c_id` = `c`.`id`)
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl ON (`dtcl`.`d_id` = `dtc`.`d_id` AND `dtcl`.`cl_type` = " . CLASSIF_SPECIALTY . ")
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'self') . "` cl ON (`cl`.`id` = `dtcl`.`cl_id` AND `cl`.`enable` = 1)
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cli ON (`cli`.`c_id` = `cl`.`id` AND `cli`.`lang` = '" . $this->getLang() . "')
							WHERE 1
							" . $clinicIdFilter . "  
							    AND c.enabled = 1 
			    				AND (c.`name` LIKE '%$searchString%'
			    				OR `cli`.`title` LIKE '%$searchString%')
							ORDER BY `c`.`id` DESC";
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {
                $return['ids'][] = array_keys($query->getArray('id'));
            } else {
                $return['ids'][] = array();
            }

            $return['applied'] = true;
        }

        if (isset($filters['cilnics_filter_city']) && $filters['cilnics_filter_city'] != 'false') {

            $dbQuery = "SELECT  `c`.`id`, `c`.`name`, `c`.`reg_nr`
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
							WHERE 1
							" . $clinicIdFilter . "  
							    AND c.enabled = 1 
								AND	`c`.`city` = " . mres($filters['cilnics_filter_city']) . "
							ORDER BY `c`.`id` DESC";
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {
                $return['ids'][] = array_keys($query->getArray('id'));
            } else {
                $return['ids'][] = array();
            }

            $return['applied'] = true;
            $return['advanced'] = true;
        }

        if (isset($filters['clinics_filter_district']) && $filters['clinics_filter_district'] != 'false') {

            $dbQuery = "SELECT `c`.`id`, `c`.`name`, `c`.`reg_nr`
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
							WHERE 1
							" . $clinicIdFilter . "  
							    AND c.enabled = 1 
				    			AND `c`.`district` = " . mres($filters['clinics_filter_district']) . "
							ORDER BY `c`.`id` DESC";
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {
                $return['ids'][] = array_keys($query->getArray('id'));
            } else {
                $return['ids'][] = array();
            }

            $return['applied'] = true;
            $return['advanced'] = true;
        }

        if (isset($filters['doctors_filter_services']) && $filters['doctors_filter_services'] != 'false' && $filters['doctors_filter_services'] != '') {

            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $serviceId = $cl->getClIdByTitle($filters['doctors_filter_services'], CLASSIF_SERVICE);

            if($serviceId) {
                $return['ids'][] = $this->_getClinicsByClassifID(CLASSIF_SERVICE, $serviceId);
            } else {
                $return['ids'] = array();
            }

            $return['applied'] = true;
            $return['advanced'] = true;
        }

        // add filter by subscription

        if(!empty($filters['subscription']) && $filters['subscription'] == 'true') {
            $return['ids'][] = $this->_getClinicsBySubscription();
            $return['applied'] = true;
        }

        // add filter by dcDoctors

        if(!empty($filters['dcDoctors']) && $filters['dcDoctors'] == 'true') {
            $return['ids'][] = $this->_getClinicsByDcDoctors();
            $return['applied'] = true;
        }

        if (!empty($return['ids']) && count($return['ids']) > 1) {
            $return['ids'] = call_user_func_array('array_intersect', $return['ids']);
        } else {
            if (isset($return['ids'][0])) {
                $return['ids'] = $return['ids'][0];
            }
        }

        return $return;
    }

    private function _getClinicsByClassifID($type, $id) {

        $return = array();

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT `c`.*
    					FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
    						LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (c.id = dtc.c_id)
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl ON (dtc.d_id = dtcl.d_id)
			    WHERE 1
				" . $clinicIdFilter . "			  
			        AND c.enabled = 1 
					AND `dtcl`.`cl_id` = " . mres($id) . "
					AND `dtcl`.`cl_type` = " . mres($type) . "
			    ORDER BY `c`.`id` DESC";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getClinicsBySubscription()
    {
        $return = array();

        $subJoin = "";
        $where = "";

        if(!empty($this->subscription['clinicId'])) {

            $where = " WHERE c.id = " . $this->subscription['clinicId'] . " ";

        } elseif (!empty($this->subscription['network'])) {

            $currDate = date('Y-m-d H:i:s', time());
            $subJoin .= " INNER JOIN ins_clinic_to_networks c2n ON (c2n.clinic_id = c.id AND c2n.network_id = ".$this->subscription['network']." AND c2n.start_datetime <= '$currDate' AND c2n.end_datetime > '$currDate') ";
        }

        $dbQuery = "SELECT c.* FROM mod_clinics c $subJoin $where ORDER BY c.id DESC";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getClinicsByDcDoctors()
    {
        $return = array();


        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT c.* FROM mod_clinics c  
                    INNER JOIN mod_doctors_to_clinics d2c ON (d2c.c_id = c.id)
                    INNER JOIN mod_doctors d ON (d.id = d2c.d_id) 
                    WHERE d.dc_doctor = 1
					" . $clinicIdFilter . "					   
                    ORDER BY c.id DESC";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    public function showOne() {

        global $config;

        $userData = $this->getPData('userData');

        if($userData['id']) {
            $this->setSessionData();
        }

        $id = mres(getG('paramID'));
        if ($this->allowed_clinics) {
            $allowedClinics = explode(',',  $this->allowed_clinics );
            if (!in_array(mres(getG('paramID')), $allowedClinics)){
                $id = false;
            }
        }

        $dbQuery = "SELECT c.*, ci.*, c.id AS id, COUNT(`dtc`.`d_id`) as `doctor_count`, clicity.title AS citytitle, clidistrict.title AS districttitle
						FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
							LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci ON
				    			ci.id = (SELECT ci1.id FROM `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci1 WHERE c.id = ci1.clinic_id LIMIT 1)
		    				LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (`dtc`.`c_id` = `c`.`id` AND c.enabled = 1)
		    				LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clicity ON (`clicity`.`c_id` = `c`.`city` AND `clicity`.`lang` = '" . getDefaultLang() . "' AND c.enabled = 1)
		    				LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clidistrict ON (`clidistrict`.`c_id` = `c`.`district` AND `clidistrict`.`lang` = '" . getDefaultLang() . "' AND c.enabled = 1)
		    			WHERE 1
		    			    AND c.enabled = 1
		    			    AND ci.lang = '" . getDefaultLang() . "' 
							AND `c`.`id` = '" . mres(getG('paramID')) . "'";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows() && !empty($id)) {

            /** @var array $clinic */
            $clinic = $query->getrow();

            // get network

            $network = null;
            $clinicId = $clinic['id'];
            $currDate = date('Y-m-d H:i:s', time());

            if(!empty($clinicId)) {

                $netwDbQuery = "
                SELECT * FROM ins_clinic_to_networks 
                WHERE
                    clinic_id = $clinicId AND 
                    start_datetime <= '$currDate' AND 
                    end_datetime > '$currDate'
            ";

                $netwQuery = new query($this->db, $netwDbQuery);

                if($netwQuery->num_rows()) {
                    /** @var array $netwRow */
                    $netwRow = $netwQuery->getrow();
                    $network = $netwRow['network_id'];
                }

                // check if subscription available

                $hasSubscription = false;

                if(!empty($_SESSION['user']) && !empty($_SESSION['user']['dcSubscription'])) {

                    if(!empty($_SESSION['user']['dcSubscription']['product_clinic'])) {

                        $hasSubscription = $_SESSION['user']['dcSubscription']['product_clinic'] == $clinicId;

                    } elseif (!empty($_SESSION['user']['dcSubscription']['product_network'])) {

                        $hasSubscription = $_SESSION['user']['dcSubscription']['product_network'] == $network;
                    }
                }


                $clinic['hasSubscription'] = $hasSubscription;

                // process description / remove unsafe content


                $clinic['description'] = removeTags($clinic['description'], '');

                // here we are checking if given lat and lng are valid google coords and are inside bounds of Latvia
                $bounds = $config['latvia_bounds'];

                if(!empty($clinic['id'])) {

                    // here we are checking if given lat and lng are valid google coords and are inside bounds of Latvia
                    $bounds = $config['latvia_bounds'];

                    if(!$clinic['lat'] || !is_numeric($clinic['lat']) || $clinic['lat'] > $bounds['n'] || $clinic['lat'] < $bounds['s']) {
                        $clinic['lat'] = 'wrong';
                    }

                    if(!$clinic['lng'] || !is_numeric($clinic['lng']) || $clinic['lng'] < $bounds['w'] || $clinic['lng'] > $bounds['e']) {
                        $clinic['lng'] = 'wrong';
                    }

                    $clinic['contacts'] = $this->getClinicsContacts($query->field('id'));

                    $canonicalUrl = $this->cfg->get('piearstaUrl') . 'iestazu-katalogs/' . $clinic['url'] . '/';
                    $this->setPData($canonicalUrl, 'canonicalUrl');

                    $this->setPData($clinic, 'clinic');
                    $this->setPData($this->config, "clinicsConfig");

                    $this->setPData($config['google']['api_key'], 'API_KEY');

                    $this->setPData(array('pageTitle' => $clinic['name']), 'web');
                    $this->setPData(array('pageDescription' => $clinic['description']), 'web');

                    $this->tpl->assign("TEMPLATE_CLINICS_MODULE", $this->tpl->output("item", $this->getPData()));

                    return $this;
                }
            }

        }

        openDefaultPage();
    }

    protected function getClinicsContacts($id)
    {
        $contacts = array();
        $dbQuery = "SELECT c.*, ci.*
    					FROM `" . $this->cfg->getDbTable('clinics', 'contacts') . "` c
    						LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts_info') . "` ci ON (c.id = ci.clinic_contact_id)
    					WHERE 1
    			 			AND `clinic_id` = '" . mres($id) . "'
    			 			AND `lang` = '" . getDefaultLang() . "'";
        $query = new query($this->db, $dbQuery);

        return $query->getArray();
    }

    public function updateReviewsCount($id, $count)
    {
        $dbData		    = array();
        $dbData['reviews_count']   = $count;

        saveValuesInDb($this->cfg->getDbTable('clinics', 'self'), $dbData, $id);
        return $id;
    }

    public function autocomplete($q)
    {
        $result = array();

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }

        if ($q) {
            $dbQuery = "SELECT `c`.`name`
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
							WHERE 1
							" . $clinicIdFilter . "  
							    AND c.enabled = 1 
				    			AND `c`.`name` LIKE '%" . mres($q) . "%'
							ORDER BY `c`.`name` DESC";
            $query = new query($this->db, $dbQuery);
            while ($query->getrow()) {
                $result[] = $query->field('name');
            }
        }

        return $result;
    }

    public function serviceAutocomplete($q) {
        $result = array();
        if($q) {
            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $specialies = $cl->getListByType(CLASSIF_SERVICE, '', $q, true);
            if (count($specialies) > 0) {
                foreach ($specialies AS $spec) {
                    $result[] = $spec['title'];
                }
            }
        }

        return $result;
    }

    private function setSessionData()
    {

        // params in session

        // DC reservation

        if ($_SESSION['dc'] && $_SESSION['dcScheduleId']) {

            $this->setPData($_SESSION['dcScheduleId'], 'schedule_id');

            if($_SESSION['dcServiceId']) {
                $this->setPData($_SESSION['dcServiceId'], 'service_id');
            }

            $this->setPData(true, 'dc');

            unset($_SESSION['dcScheduleId']);
            unset($_SESSION['dcServiceId']);
            unset($_SESSION['dc']);
        }
    }

}