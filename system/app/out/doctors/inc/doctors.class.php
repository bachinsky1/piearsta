<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class doctorsData extends Module {

    private $config = array('uploadFolder' => 'doctors/');
    private $itemsPerPage = 15;
    private $filters = array(
        'main' => array(
            'doctors_filter_search', 'doctors_filter_city', 'doctors_filter_clinic',
            'doctors_filter_specialty', 'doctors_filter_services', 'doctors_filter_ic',
            'doctors_filter_only_with_work'
        ),
        'fast' => array(
            'doctors_filter_country', 'doctors_filter_client', 'doctors_filter_mix', 'doctors_filter_clinic',
        )
    );

    private $doctorFilter = array(
        'search',
        'location',
        'speciality',
        'insurance',
        'clinic',
        'service',
        'availableTimeOnly',
    );

    /** @var array|null  */
    private $subscription = null;

    private $allowed_clinics;
    /**
     * Class constructor
     */
    public function __construct() {

        parent :: __construct();

        $this->name = 'doctors';

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

    private function parseDoctorFilter($filterFromPost,$requestQueryString)
    {
        $filterFromPostValue = function ($name) use ($filterFromPost){
            return (! empty($filterFromPost['main'][$name]) && $filterFromPost['main'][$name] != 'false')
                ? $filterFromPost['main'][$name]
                : null;
        };

        $filter = array_fill_keys($this->doctorFilter,null);

        $filter['search'] = sanitize((string)$filterFromPostValue('doctors_filter_search'));
        $filter['location'] = (int)$filterFromPostValue('doctors_filter_city');
        $filter['clinic'] = (int)$filterFromPostValue('doctors_filter_clinic');
        $filter['speciality'] = sanitize((string)$filterFromPostValue('doctors_filter_specialty'));

        if(is_array($filterFromPostValue('doctors_filter_services'))) {
            $filter['service'] = sanitize($filterFromPostValue('doctors_filter_services'));
        } else {
            $filter['service'] = sanitize((string)$filterFromPostValue('doctors_filter_services'));
        }

        $filter['insurance'] = (int)$filterFromPostValue('doctors_filter_ic');
        $filter['availableTimeOnly'] = $filterFromPostValue('doctors_filter_only_with_work') ? true : null;

        if (! empty($requestQueryString)){
            $queries = array();
            parse_str($requestQueryString, $queries);
            if(! empty($queries['doctors_filter_services']) && $queries['doctors_filter_services'] != 'false') {
                $filter['service'] = sanitize($queries['doctors_filter_services']);
            }

            if(! empty($queries['doctors_filter_specialty']) && $queries['doctors_filter_specialty'] != 'false') {
                $filter['speciality'] = sanitize($queries['doctors_filter_specialty']);
            }
        }

        // add subscription filter

        $filter['subscription'] = false;

        if(getP('subscription')) {
            $filter['subscription'] = getP('subscription') == 'true' || getP('subscription') === true;
        }

        // add dcDoctors filter

        $filter['dcDoctors'] = false;

        if(getP('dcDoctors')) {
            $filter['dcDoctors'] = getP('dcDoctors') == 'true' || getP('dcDoctors') === true;
        }

        $this->doctorFilter = $filter;
    }

    private function appendClinicFilter($clinicId){
        if (empty($clinicId)) {
            return false;
        }

        $this->doctorFilter['clinic'] = (int)$clinicId;
    }

    private function getFilteredDoctorSql()
    {

        $joinDoctorInfo = sprintf(
            "INNER JOIN `%s` as di ON d.id = di.doctor_id ",
            $this->cfg->getDbTable('doctors', 'info')
        );

        $filterDoctorInfo = array();

        if (! empty($this->doctorFilter['search'])){
            $search = mres(trim($this->doctorFilter['search']));
            $filterDoctorInfo[] = sprintf("di.name LIKE '%%%s%%'", $search);
            $filterDoctorInfo[] = sprintf("di.surname LIKE '%%%s%%'", $search);
            $filterDoctorInfo[] = sprintf("CONCAT_WS(' ', di.name, di.surname) LIKE '%%%s%%'", $search);
            $filterDoctorInfo[] = sprintf("CONCAT_WS(' ', di.surname, di.name) LIKE '%%%s%%'", $search);
        }
        if ($filterDoctorInfo){
            $filterDoctorInfo = " AND (".implode(' OR ',$filterDoctorInfo).")";
        }

        $joinDoctorClinics = sprintf("INNER JOIN `%s` AS d2c ON d.id = d2c.d_id"
            , $this->cfg->getDbTable('doctors', 'clinics')
        );

        $filterDoctorClinics = array();

        if (! empty($this->doctorFilter['clinic'])){
            if ($id = (int)(trim($this->doctorFilter['clinic']))){
                $filterDoctorClinics[] = sprintf("(d2c.c_id = %d)"
                    , $id
                );
            }
        }

        if (! empty($this->doctorFilter['location'])){
            if ($id = (int)(trim($this->doctorFilter['location']))){
                $filterDoctorClinics[] = sprintf("(d2c.c_id IN (SELECT id FROM `%s` WHERE city = %d))"
                    , $this->cfg->getDbTable('clinics', 'self')
                    , $id
                );
            }
        }
        if (! empty($this->doctorFilter['insurance'])){
            if ($id = (int)(trim($this->doctorFilter['insurance']))){
                $filterDoctorClinics[] = sprintf("(d2c.c_id IN (SELECT clinic_id FROM `%s` WHERE cl_type = 5 AND cl_id = %d))"
                    , $this->cfg->getDbTable('clinics', 'classificators')
                    , $id
                );
            }
        }

        if ($this->allowed_clinics) {
            $filterDoctorClinics[] = "d2c.c_id IN (" . $this->allowed_clinics . ")";
        }
        if ($filterDoctorClinics){
            $filterDoctorClinics = " AND (".implode(' AND ',$filterDoctorClinics).")";
        }

        $filterSubscription = '';
        $networkWhere = '';

        if(!empty($this->doctorFilter['subscription'])) {

            // check and join clinics only if not already joined

            if(empty($filterDoctorClinics)) {
                //
            }

            if(!empty($this->subscription['clinicId'])) {

                $filterSubscription = " AND d2c.c_id = " . $this->subscription['clinicId'] . " ";

            } elseif (!empty($this->subscription['network'])) {

                $currDate = date('Y-m-d H:i:s', time());
                $clinicNetworkJoin = " INNER JOIN ins_clinic_to_networks c2n ON (d2c.c_id = c2n.clinic_id AND c2n.network_id = ".$this->subscription['network']." AND c2n.start_datetime <= '$currDate' AND c2n.end_datetime > '$currDate') ";
                $networkWhere = " AND c2n.id IS NOT NULL ";
            }
        }

        $filterDoctorSpeciality = '';
        if (! empty($this->doctorFilter['speciality'])){
            if ($speciality = mres(trim($this->doctorFilter['speciality']))){
                $filterDoctorSpeciality = sprintf("INNER JOIN `%s` AS d2cl_1 ON d.id = d2cl_1.d_id AND (d2cl_1.cl_id IN (SELECT c_id FROM `%s` WHERE `title` = '%s'))"
                    , $this->cfg->getDbTable('doctors', 'classificators')
                    , $this->cfg->getDbTable('classificators', 'details')
                    , $speciality
                );
            }
        }
        $filterDoctorService = '';
        if (! empty($this->doctorFilter['service'])){
            if ($service = mres(trim($this->doctorFilter['service']))){
                $filterDoctorService = sprintf("INNER JOIN `%s` AS d2cl_2 ON d.id = d2cl_2.d_id AND (d2cl_2.cl_id IN (SELECT c_id FROM `%s` WHERE `title` = '%s'))"
                    , $this->cfg->getDbTable('doctors', 'classificators')
                    , $this->cfg->getDbTable('classificators', 'details')
                    , $service
                );
            }
        }

        $filterScheduler = '';
        if ($this->doctorFilter['availableTimeOnly']){
            $filterScheduler = sprintf("INNER JOIN `%s` AS sch ON d.id = sch.doctor_id AND sch.clinic_id = d2c.c_id AND sch.date >= CURRENT_DATE()"
                , $this->cfg->getDbTable('shedule', 'self')
            );
        }


        $sql = sprintf("
SELECT DISTINCT d.id FROM `%s` AS d 
%s
%s
%s
%s
%s
%s
%s
WHERE d.deleted = 0 AND d.enabled = 1 AND d2c.enabled = 1 $filterSubscription $networkWhere
"
            , $this->cfg->getDbTable('doctors', 'self')
            , $filterDoctorInfo
                ? $joinDoctorInfo . $filterDoctorInfo
                : ''

            , $joinDoctorClinics . ($filterDoctorClinics ? $filterDoctorClinics : '')

            , " INNER JOIN mod_clinics c ON (c.id = d2c.c_id AND c.enabled = 1) "

            , $clinicNetworkJoin ? $clinicNetworkJoin : ''
            , $filterDoctorSpeciality
            , $filterDoctorService
            , $filterScheduler
        );

//        pre($filterSubscription);
//        pre($clinicNetworkJoin);
//        pre($sql);

        return $sql;

    }

    private function getFilteredDoctorList()
    {

        $query = new query($this->db, $this->getFilteredDoctorSql());
        if ($query->num_rows()) {

            return array_keys($query->getArray('id'));
        }

        return array();
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
                if ($key == 'doctors_filter_only_with_work' || $key == 'payment_type_country' ) {
                    $_SESSION['additional_filters'][$key] = 'true';
                } else {
                    $_SESSION['additional_filters'][$key] = $field;
                }
            } else {
                if ($key == 'payment_type_country' || $key == 'payment_type' ) {
                    $_SESSION['additional_filters'][$key] = 'false';
                }
            }
        }
    }


    /**
     * @param bool $clinic
     * @return $this|array
     * @throws Exception
     */
    public function showList($clinic = false)
    {
        loadLibClass('logger')->file('overview')->addString('function showList')->append();
        //rewrite GET filter params to POST
        $this->_parseGetFilters();

        $docFilters = getP('doctors_filters');

        if (!empty($_SESSION['additional_filters'])){
            $docFilters['main'] = $_SESSION['additional_filters'];
        }

        $this->parseDoctorFilter(
            $docFilters,
            getP('queryString')
        );

        $this->appendClinicFilter(
            $clinic
        );

        loadLibClass('logger')->file('overview')->addArrayFilterEmpty($this->doctorFilter,'doctor filter')->append();

        $this->setPData(true, 'doctors_list');
        $page = getGP('doctors_page') ? getGP('doctors_page') : 0;

        if (getP('ajax_search')) {
            $offset = $page * $this->itemsPerPage;
            $itemCount = $this->itemsPerPage;
            $loadedCount = $offset + $itemCount;
        } else {
            $offset = 0;
            $itemCount = ($page + 1) * $this->itemsPerPage;
            $loadedCount = $itemCount;
        }

        $userData = $this->getPData('userData');



        if($userData['id']) {
            $this->setSessionData();
        }

        $this->setPData($page, 'current_page');

        $filteredDoctorList = $this->getFilteredDoctorList();
        $filters = $this->_getFilteredIds($clinic);
        $filters['ids'] = array_intersect($filteredDoctorList, $filters['ids']);

        $this->setPData((isset($filters['advanced']) && $filters['advanced']) || isset($_SESSION['find']) || getGP('advanced'), 'showAdvanced');

        unset($_SESSION['additional_filters']);

        if(isset($_SESSION['find']) && !empty($_SESSION['find'])) {
            $this->setPData($_SESSION['find'], 'find');
            unset($_SESSION['find']);
        }

        if ($filters['applied'] && !count($filters['ids'])) {

            $total_count = 0;

        } else {

            $where = "";

            $userData = $this->getPData('userData');
            $addFavSelect = "";
            $addFavJoin = "";
            if (isset($userData['id'])) {
                $addFavSelect = ", `pd`.`profile_id`";
                $addFavJoin = "LEFT JOIN `" . $this->cfg->getDbTable('profiles', 'doctors') . "` pd ON (
				    `pd`.`profile_id` = " . mres($userData['id']) . "
				    AND `pd`.`doctor_id` = `d`.`id`
				    AND `pd`.`clinic_id` = `c`.`id`
				)";
            }

            $clinicIdFilter = '';
            if ($this->allowed_clinics) {
                $clinicIdFilter = " AND c.id in (" . $this->allowed_clinics . ")";
            }
            $currDate = date('Y-m-d H:i:s', time());

            $dbQuery = "SELECT DISTINCT
				    			`d`.`id`, `d`.`person_code`, `di`.`name`, `di`.`surname`, `d`.`email`, `d`.`photo`, `d`.`phone`, `d`.`url`, `d`.local, `d`.reviews_count, d.dc_doctor,
				    			`c`.`name` as `clinic_name`, `c`.`id` as `clinic_id`, `ci`.`address` as `clinic_address`, `c`.`url` as `clinic_url`, c2n.network_id as network_id,
		    					clicity.title AS clinic_citytitle, clidistrict.title AS clinic_districttitle, c.zip AS clinic_zip
				    			" . $addFavSelect . "
							FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` AS di ON 
								di.id = (SELECT d1.id FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` AS d1 WHERE d.id = d1.doctor_id LIMIT 1)
				    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (`dtc`.`d_id` = `d`.`id`)
				    			LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self') . "` c ON (`c`.`id` = `dtc`.`c_id`)
				    			LEFT JOIN ins_clinic_to_networks c2n ON (c2n.clinic_id = c.id AND start_datetime <= '$currDate' AND end_datetime > '$currDate') 
				    			LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci ON
				    			ci.id = (SELECT ci1.id FROM `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci1 WHERE c.id = ci1.clinic_id LIMIT 1)
				    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clicity ON (`clicity`.`c_id` = `c`.`city` AND `clicity`.`lang` = '" . getDefaultLang() . "')
		    					LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clidistrict ON (`clidistrict`.`c_id` = `c`.`district` AND `clidistrict`.`lang` = '" . getDefaultLang() . "')
				    			" . $addFavJoin . "
							WHERE 1
							    AND c.enabled = 1 
								" . $clinicIdFilter . "  
								AND `d`.`is_hidden_on_piearsta` = 0
				    			AND `d`.`deleted` = 0
								AND `d`.`enabled` = 1
								AND dtc.enabled = 1
                                AND dtc.vp_id IS NULL
								" . ($filters['applied'] ? " AND `d`.`id` IN (" . implode(',', $filters['ids']) . ")" : "") . "
				    		" . $where . "
							ORDER BY `di`.`surname`, `di`.`name` ASC";
            $query = new query($this->db, $dbQuery);
            $total_count = $query->num_rows();
        }

        // https://andrejs-piearsta.smartmedical.eu/arstu-katalogs/andreyv-test2-62/andrejs-terminal-1/

        $showMore = $total_count > 0 && $loadedCount < $total_count ? true : false;

        $this->setPData($total_count, 'found_doctors');
        $doctors = array();
        $doctorsIds = array();

        $lastDate = getP('lastDate');
        $days = getP('days');

        if($days) {
            $days--;
        } else {
            $days = 6;
        }

        if($lastDate) {

            if(getP('type') == 'next') {

                $lastDate = date('Y-m-d', (strtotime($lastDate)) + (86400 * ($days == 6 || $days == 13 ? 6 : 1) ));

                if( date('N', strtotime($lastDate)) == 1 ) {
                    $lastDate = date('Y-m-d', (strtotime($lastDate)) + 86400);
                }

                $date = date('Y-m-d', strtotime('monday this week', strtotime( $lastDate )));


            } elseif (getP('type') == 'prev') {

                //$lastDate = date('Y-m-d', (strtotime($lastDate)) - 86400 * $days);
                $lastDate = date('Y-m-d', (strtotime($lastDate)) - (86400 * ($days == 6 || $days == 13 ? $days : 6) ));

                if( date('N', strtotime($lastDate)) == 1 ) {
                    $lastDate = date('Y-m-d', (strtotime($lastDate)) + 86400);
                }

                $date = date('Y-m-d', strtotime('monday this week', strtotime( $lastDate )));

            } else {

                if( date('N', strtotime($lastDate)) == 1 ) {
                    $lastDate = date('Y-m-d', (strtotime($lastDate)) + 86400);
                }

                $date = date('Y-m-d', strtotime('monday this week', strtotime( $lastDate )));
            }
        }

        if ($total_count) {
            $dbQuery .= " LIMIT " . $offset . ", " . $itemCount;
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {

                /** @var array $row */
                while ($row = $query->getrow()) {

                    $hasSubscription = false;

                    if(!empty($this->subscription['clinicId'])) {
                        $hasSubscription = $this->subscription['clinicId'] == $row['clinic_id'];
                    } elseif (!empty($this->subscription['network'])) {
                        $hasSubscription = $this->subscription['network'] == $row['network_id'];
                    }

                    $row['hasSubscription'] = $hasSubscription;
                    $row['isDcDoctor'] = $row['dc_doctor'] == 1;

                    $row['shedule'] = $this->getShedule($row['id'], $row['clinic_id'], $date, $days);

                    // DEBUG
                    if(DEBUG) {
                        $row['shedule']['nearestDebug'] = array(
                            'doctorId' => $row['id'],
                            'clinicId' => $row['clinic_id'],
                            'date' => $date,
                            'days' => $days,
                            'lastDate' => $lastDate,
                        );
                    }

                    if ($row['shedule']['prev']) {
                        $this->setPData(true, "sheduleDataPrev");
                    }
                    if ($row['shedule']['next']) {
                        $this->setPData(true, "sheduleDataNext");
                    }

                    $remoteServices = $this->_getRemoteServices($row);

                    if(!empty($remoteServices) && !empty($row['person_code']) && ! empty($row['email'])) {
                        $row['remoteServices'] = $remoteServices;
                        $row['consultations_enabled'] = '1';
                    }

                    $doctors[] = $row;
                    $doctorsIds[] = $row['id'];
                }

                $this->setPData($this->_setDoctorProfession($doctors), "doctors");
                $this->setPData($doctorsIds, 'doctorsIds');
                $this->setPData($this->config, "doctorsConfig");
            }
        }

        $this->setPData($showMore, 'show_more');

        if (getP('ajax_search')) {
            $this->noLayout(true);
            $result = array(
                'debug' => $this->getPData(),
                'content' => $this->tpl->output("_data", $this->getPData()),
                'show_more' => $showMore,
                'total' => $total_count,
            );

            if(DEBUG) {
                $result['nearestDebug'] = array(
                    'date' => $date,
                    'days' => $days,
                    'lastDate' => $lastDate,
                    'type' => getP('type'),
                );
            }

            jsonSend($result);

        } elseif (!$clinic) {

            $filters = $this->getPData('filters');

            foreach ($this->filters['fast'] as $filter_name) {
                if (!isset($filters[$filter_name])) {
                    $filters[$filter_name] = 'true';
                }
            }

            $this->setPData($filters, 'filters');

            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $classificators = $cl->getList('id', true);

            if (!empty($classificators[CLASSIF_CITY])) {
                $this->setPData($classificators[CLASSIF_CITY], 'cityList');
            }

            if (!empty($classificators[CLASSIF_IC])) {
                $this->setPData($classificators[CLASSIF_IC], 'icList');
            }

            $this->setPData($cl->getClinics('id'), 'clinicList');
            $this->tpl->assign("TEMPLATE_DOCTORS_MODULE_DATA", $this->tpl->output("_data", $this->getPData()));
            $this->tpl->assign("TEMPLATE_DOCTORS_MODULE_FILTERS", $this->tpl->output("_filters", $this->getPData()));
            $this->tpl->assign("TEMPLATE_DOCTORS_MODULE", $this->tpl->output("list", $this->getPData()));

            return $this;

        } else {

            return $doctorsIds;
        }
    }

    /**
     * @param array $row
     * @return array
     */
    private function _getRemoteServices(array $row) {

        $row['services'] = $this->_getLinkedClassificators(getDefaultLang(), $row['id'], CLASSIF_SERVICE);

        $remoteServices = array();

        foreach ($row['services'] as $k => $service) {

            if(
                mb_strpos(mb_strtolower($service['title']), 'attalinat') !== false ||
                mb_strpos(mb_strtolower($service['title']), 'attālināt') !== false ||
                mb_strpos(mb_strtolower($service['title']), 'attālinat') !== false ||
                mb_strpos(mb_strtolower($service['title']), 'attalināt') !== false
            ) {
                $remoteServices[] = $service;
            }
        }

        return $remoteServices;
    }


    private function _setDoctorProfession($doctors) {
        if (!empty($doctors)) {
            $ids = array();
            foreach ($doctors as $k => $v) {
                $ids[] = $v['id'];
            }
            $specialties = $this->_getLinkedClassificators($this->getLang(), $ids, CLASSIF_SPECIALTY);
            foreach ($doctors as $k => $v) {
                if (isset($specialties[$v['id']])) {
                    $doctors[$k]['specialty'] = $specialties[$v['id']][0];
                }
            }
        }

        return $doctors;
    }

    private function _parseGetFilters() {
        foreach ($this->filters as $type => $arr) {
            foreach ($arr as $filter_name) {
                $val = getG($filter_name);
                if ($val !== false) {
                    $_POST['doctors_filters'][$type][$filter_name] = $val;
                }
            }
        }
    }

    private function _getDocsByClinicClassifID($type, $id) {
        $return = array();
        if (!$id = (int)$id){
            return $return;
        }

        $field = 'city';
        if ($type == CLASSIF_DISTRICT) {
            $field = 'district';
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }
        $dbQuery = "SELECT `d`.*
			    		FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` c
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (`dtc`.`c_id` = `c`.`id`)
							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dtc`.`d_id`)
			    		WHERE 1
							AND `c`.`" . $field . "` = " . mres($id) . "
							" . $clinicIdFilter . "  
							AND `d`.`is_hidden_on_piearsta` = 0
							AND `d`.`enabled` = 1
							AND dtc.enabled = 1
							AND `d`.`deleted` = 0
			    ORDER BY `d`.`id` DESC";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getDocsByClassifID($type, $id) {
        $return = array();

        if (!$id = (int)$id){
            return $return;
        }

        if (!$id = (int)$id){
            return $return;
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT `d`.*
			    		FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dtcl`.`d_id`)
							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (`dtc`.`d_id` = `d`.`id`)	   
			    		WHERE 1
							AND `dtcl`.`cl_id` = " . mres($id) . "
							AND `dtcl`.`cl_type` = " . mres($type) . "
							AND `d`.`is_hidden_on_piearsta` = 0
							AND `d`.`enabled` = 1
							AND `d`.`deleted` = 0
							" . $clinicIdFilter . "  
			    		ORDER BY `d`.`id` DESC";
        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    /**
     * @return array
     */
    private function _getDocsByRemoteServices() {

        $return = array();

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT `d`.*
			    		FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl
			    			INNER JOIN `mod_remote_services` rs ON (rs.service_id = dtcl.cl_id)
			    			INNER JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dtcl`.`d_id`)
							INNER JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (`dtc`.`d_id` = `d`.`id`)		
			    		WHERE 1
							AND `d`.`is_hidden_on_piearsta` = 0
							AND `d`.`enabled` = 1
							AND `d`.`deleted` = 0
							" . $clinicIdFilter . "
			    		ORDER BY `d`.`id` DESC";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    /**
     * @return array|int[]|string[]
     */
    private function _getDocsBySubscription()
    {
        $return = array();

        if(empty($this->subscription)) {

            return $return;
        }

        $subJoin = " INNER JOIN mod_doctors_to_clinics d2c ON (d2c.d_id = d.id) ";
        $where = "";

        if(!empty($this->subscription['clinicId'])) {

            $subJoin = " INNER JOIN mod_doctors_to_clinics d2c ON (d2c.d_id = d.id AND d2c.c_id = ".$this->subscription['clinicId']." ) ";
            $where = " WHERE d2c.c_id = " . $this->subscription['clinicId'];

        } elseif (!empty($this->subscription['network'])) {

            $currDate = date('Y-m-d H:i:s', time());
            $subJoin .= " INNER JOIN ins_clinic_to_networks c2n ON (c2n.network_id = ".$this->subscription['network']." AND c2n.start_datetime <= '$currDate' AND c2n.end_datetime > '$currDate' ". $clinicIdFilter ." ) ";
        }

        $dbQuery = "SELECT d.* FROM mod_doctors d 
                    $subJoin $where 
                    ORDER BY d.id";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getDocsByDcDoctors()
    {
        $return = array();

        $clinicIdFilter = '';
        $join = '';

        if ($this->allowed_clinics) {
            $join = " LEFT JOIN mod_doctors_to_clinics dtc ON (dtc.d_id = d.id)";
            $clinicIdFilter = " AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }


        $dbQuery = "SELECT d.* FROM mod_doctors d". $join ." WHERE d.dc_doctor = 1". $clinicIdFilter ." ORDER BY d.id";
        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getDocsClinicsByClassifID($type, $id) {
        $return = array();
        if (!$id = (int)$id){
            return $return;
        }
        if (!$type = (int)$type){
            return $return;
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dc.c_id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT `d`.*
			    		FROM `" . $this->cfg->getDbTable('clinics', 'classificators') . "` dtcl
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dc ON (`dc`.`c_id` = `dtcl`.`clinic_id`)
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dc`.`d_id`)
			    		WHERE 1
							AND `dtcl`.`cl_id` = " . mres($id) . "
							AND `dtcl`.`cl_type` = " . mres($type) . "
							AND `d`.`is_hidden_on_piearsta` = 0
							AND `d`.`enabled` = 1
							AND dc.enabled = 1
							AND `d`.`deleted` = 0
							" . $clinicIdFilter . "  
			    		ORDER BY `d`.`id` DESC";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getDocsByClassifValue($type, $value) {
        $return = array();
        if (!$type = (int)$type){
            return $return;
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dc.c_id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT `d`.*
						FROM `" . $this->cfg->getDbTable('classificators', 'details') . "` cli
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl ON (`dtcl`.`cl_id` = `cli`.`c_id`)
							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dtcl`.`d_id`)
							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dc ON (`dc`.`d_id` = `d`.`id`)
			    		WHERE 1
							AND `cli`.`title` LIKE '%" . mres($value) . "%'
							AND `dtcl`.`cl_type` = " . mres($type) . "
							AND `d`.`is_hidden_on_piearsta` = 0
							AND `d`.`enabled` = 1
							AND `d`.`deleted` = 0
							" . $clinicIdFilter . "  
			    		ORDER BY `d`.`id` DESC";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
    }

    private function _getDocsByClinicID($id) {
        $return = array();
        if (!$id = (int)$id){
            return $return;
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }
        $dbQuery = "SELECT `d`.*
			    		FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` as `dtc`
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (`d`.`id` = `dtc`.`d_id`)
			    		WHERE 1
			    			AND `dtc`.`c_id` = " . mres($id) . "
			    			AND `d`.`is_hidden_on_piearsta` = 0
			    			AND `d`.`enabled` = 1
			    			AND dtc.enabled = 1
							AND `d`.`deleted` = 0
							" . $clinicIdFilter . "  
			    		ORDER BY `d`.`id` DESC";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $return = array_keys($query->getArray('id'));
        }

        return $return;
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

    private function _getFilteredIds($clinic = false) {

        if(isset($_SESSION['returning']) && $_SESSION['returning'] && isset($_SESSION['returnInfo']) && isset($_SESSION['returnInfo']['doctor_filters'])) {
            $filters = $_SESSION['returnInfo']['doctor_filters'];
            if(isset($_SESSION['calendarData'])) {
                $this->setPData(json_encode($_SESSION['calendarData']), 'calendarData');
                unset($_SESSION['calendarData']);
            }
            unset($_SESSION['returning']);
        } else {
            $this->_parseGetFilters();
            $filters = $this->_parseFilters(getP('doctors_filters'));
        }

        if(getP('remote_services')) {
            $filters['doctors_filter_remote'] = getP('remote_services');
        }

        if(getP('subscription')) {
            $filters['subscription'] = getP('subscription') == 'true' || getP('subscription') === true;
        }

        if(getP('dcDoctors')) {
            $filters['dcDoctors'] = getP('dcDoctors') == 'true' || getP('dcDoctors') === true;
        }

        if(isset($_SESSION['returnInfo'])) {
            $_SESSION['returnInfo']['doctor_filters'] = $filters;
        } else {
            $_SESSION['returnInfo'] = array(
                'doctor_filters' => $filters,
            );
        }

        if (isset($filters['doctors_filter_search']) && $filters['doctors_filter_search'] == 'false') {
            unset($filters['doctors_filter_search']);
        }


        if (!empty($_SESSION['additional_filters'])) {

            $additionalFilters = $_SESSION['additional_filters'];

            if (!empty($additionalFilters['doctors_filter_search'])) {
                $filters['doctors_filter_search'] = $additionalFilters['doctors_filter_search'];
            }

            if (!empty($additionalFilters['doctors_filter_city'])) {
                $filters['doctors_filter_city'] = $additionalFilters['doctors_filter_city'];
            }

            if (!empty($additionalFilters['doctors_filter_clinic'])) {
                $filters['doctors_filter_clinic'] = $additionalFilters['doctors_filter_clinic'];
            }

            if (!empty($additionalFilters['doctors_filter_specialty'])) {
                $filters['doctors_filter_specialty'] = $additionalFilters['doctors_filter_specialty'];
            }

            if (!empty($additionalFilters['doctors_filter_services'])) {
                $filters['doctors_filter_services'] = $additionalFilters['doctors_filter_services'];
            }

            if (!empty($additionalFilters['doctors_filter_ic'])) {
                $filters['doctors_filter_ic'] = $additionalFilters['doctors_filter_ic'];
            }

            if (!empty($additionalFilters['doctors_filter_only_with_work'])) {
                $filters['doctors_filter_only_with_work'] = true;
            }

            if (!empty($additionalFilters['dcDoctors'])) {
                $filters['dcDoctors'] = true;
            }

            if (!empty($additionalFilters['payment_type_country'])) {
                if ($additionalFilters['payment_type_country'] == 'false'
                ){
                    $filters['payment_type_country'] = true;
                }
            }

            if (!empty($additionalFilters['payment_type'])) {
                if ($additionalFilters['payment_type'] == 'false'
                ){
                    $filters['payment_type'] = true;
                }
            }

            if (!empty($additionalFilters['filter_date'])) {
                $filters['filter_date'] = $additionalFilters['filter_date'];
            }
        }

        $this->setPData($filters, 'filters');

        $return = array(
            'applied' => false,
            'ids' => array()
        );

        if (!$filters && !$clinic) {
            return $return;
        }

        $doctorsClinicsWithSchedule = array();

        $return['applied'] = false;

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dr2c.c_id in (" . $this->allowed_clinics . ")";
        }

        if (
            !empty($filters['doctors_filter_search']) &&
            $filters['doctors_filter_search'] != 'false' &&
            $filters['doctors_filter_search'] != '' &&
            $filters['doctors_filter_search'] != false
        ) {

            $tmpIds = array();

            $joinSchedules = '';
            $where = '';

            if (isset($filters['doctors_filter_only_with_work']) && $filters['doctors_filter_only_with_work'] == 'true') {
//
//                $joinSchedules = " INNER JOIN " . $this->cfg->getDbTable('shedule', 'self') . " AS sch ON ( sch.doctor_id = drs.id AND sch.clinic_id = cl.id ) ";
//                $where = " AND sch.date >= '" . date('Y-m-d', time()) . "' ";

                $where = " AND EXISTS (
                    SELECT sch.id FROM mod_shedules sch 
                    LEFT JOIN mod_clinics clin ON (clin.id = sch.clinic_id) 
                    WHERE
                        clin.enabled = 1 AND 
                        sch.doctor_id = drs.id AND 
                        sch.clinic_id = cl.id AND 
                        sch.date >= '" . date('Y-m-d', time()) . "' 
                ) 
                AND EXISTS (
                    SELECT d2c.primary_id FROM mod_doctors_to_classificators d2c
                    LEFT JOIN mod_classificators c ON (c.id = d2c.cl_id)  
                    WHERE
                        d2c.d_id = drs.id AND 
                        d2c.cl_type = " . CLASSIF_SERVICE . " AND 
                        c.enable = 1  
                ) ";
            }

            $dbQuery = "SELECT drs.id, cl.id AS cl_id
                        FROM " . $this->cfg->getDbTable('clinics', 'self') . " AS cl
                        INNER JOIN " . $this->cfg->getDbTable('doctors', 'clinics') . " AS dr2c ON ( dr2c.c_id = cl.id )
                        INNER JOIN " . $this->cfg->getDbTable('doctors', 'self') . " AS drs ON ( drs.id = dr2c.d_id AND cl.id = dr2c.c_id )
                        LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` AS di ON 
								di.id = (SELECT d1.id FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` AS d1 WHERE drs.id = d1.doctor_id LIMIT 1)								
                        " . $joinSchedules . "
                        WHERE 1
                            AND cl.enabled = 1 
                            AND dr2c.enabled = 1
                            AND dr2c.c_id IS NOT NULL
                            AND drs.is_hidden_on_piearsta = 0
                            AND drs.deleted = 0
                            AND drs.enabled = 1
							" . $clinicIdFilter . "					   
                            AND (
                                    di.name LIKE '%" . mres($filters['doctors_filter_search']) . "%'
                                    OR di.surname LIKE '%" . mres($filters['doctors_filter_search']) . "%'
                                    OR CONCAT_WS(' ', di.name, di.surname) LIKE '%" . mres($filters['doctors_filter_search']) . "%'
                                    OR CONCAT_WS(' ', di.surname, di.name) LIKE '%" . mres($filters['doctors_filter_search']) . "%'
                                )
                            " . $where . "
                        ORDER BY drs.id DESC";

            $query = new query($this->db, $dbQuery);

            if ($query->num_rows()) {

                if(isset($filters['doctors_filter_only_with_work']) && $filters['doctors_filter_only_with_work'] == 'true' && !$clinic) {
                    while ($r = $query->getrow()) {
                        $doctorsClinicsWithSchedule[] = $r;
                        $tmpIds[0][$r['id']] = $r;
                    }
                    $tmpIds[0] = array_keys($tmpIds[0]);
                } else {
                    $tmpIds[] = array_keys($query->getArray('id'));
                }
            } else {
                $tmpIds[] = array();
            }

            $return['ids'][] = call_user_func_array('array_merge', $tmpIds);

            $return['applied'] = true;

        } elseif (isset($filters['doctors_filter_only_with_work']) && $filters['doctors_filter_only_with_work'] == 'true') {

            $joinSchedules = '';
            $where = '';
            //$joinSchedules = " INNER JOIN " . $this->cfg->getDbTable('shedule', 'self') . " AS sch ON ( sch.doctor_id = drs.id ) ";
            //$where = " AND  sch.id IS NOT NULL AND sch.date >= CURRENT_DATE() AND sch.clinic_id = cl.id ";

            $where = " AND EXISTS (
                    SELECT id FROM mod_shedules sch 
                    WHERE
                        sch.doctor_id = drs.id AND 
                        sch.clinic_id = cl.id AND 
                        sch.date >= '" . date('Y-m-d', time()) . "' 
                ) 
                AND EXISTS (
                    SELECT id FROM mod_doctors_to_classificators d2c
                    LEFT JOIN mod_classificators c ON (c.id = d2c.cl_id)  
                    WHERE
                        d2c.d_id = drs.id AND 
                        d2c.cl_type = " . CLASSIF_SERVICE . " AND 
                        c.enable = 1  
                ) ";

            $dbQuery = "SELECT drs.id, cl.id AS cl_id
                        FROM " . $this->cfg->getDbTable('clinics', 'self') . " AS cl
                        INNER JOIN " . $this->cfg->getDbTable('doctors', 'clinics') . " AS dr2c ON ( dr2c.c_id = cl.id )
                        INNER JOIN " . $this->cfg->getDbTable('doctors', 'self') . " AS drs ON ( drs.id = dr2c.d_id AND cl.id = dr2c.c_id )
                        " . $joinSchedules . "
                        WHERE 1
                            AND dr2c.c_id IS NOT NULL
                            AND dr2c.enabled = 1
                            AND drs.is_hidden_on_piearsta = 0
                            AND drs.deleted = 0
                            AND drs.enabled = 1
							" . $clinicIdFilter . "					   
                            " . $where . "
                        ORDER BY drs.id DESC";

            $query = new query($this->db, $dbQuery);

            if ($query->num_rows()) {

                if(!$clinic) {
                    while ($r = $query->getrow()) {
                        $doctorsClinicsWithSchedule[] = $r;
                        $tmpIds[0][$r['id']] = $r;
                    }
                    $tmpIds[0] = array_keys($tmpIds[0]);
                }

            } else {
                $tmpIds[] = array();
            }

            $return['ids'][] = call_user_func_array('array_merge', $tmpIds);
            $return['applied'] = true;
            $return['advanced'] = true;
        }

        if (isset($filters['doctors_filter_city']) && $filters['doctors_filter_city'] != 'false' && $filters['doctors_filter_city'] != '') {
            $return['ids'][] = $this->_getDocsByClinicClassifID(CLASSIF_CITY, (int)$filters['doctors_filter_city']);
            $return['applied'] = true;
            $return['advanced'] = true;
        }

        if (isset($filters['doctors_filter_specialty']) && $filters['doctors_filter_specialty'] != 'false' && $filters['doctors_filter_specialty'] != '') {
            $return['ids'][] = $this->_getDocsByClassifValue(CLASSIF_SPECIALTY, $filters['doctors_filter_specialty']);
            $return['applied'] = true;
            $return['advanced'] = true;
        }

        if (isset($filters['doctors_filter_services']) && $filters['doctors_filter_services'] != 'false' && $filters['doctors_filter_services'] != '') {

            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $serviceId = $cl->getClIdByTitle($filters['doctors_filter_services'], CLASSIF_SERVICE);

            if($serviceId) {
                $return['ids'][] = $this->_getDocsByClassifID(CLASSIF_SERVICE, $serviceId);
            } else {
                $return['ids'] = array();
            }

            $return['applied'] = true;
            $return['advanced'] = true;
        }

        if(isset($filters['doctors_filter_remote']) && $filters['doctors_filter_remote'] == 'true') {
            $return['ids'][] = $this->_getDocsByRemoteServices();
            $return['applied'] = true;
        }

        if (isset($filters['doctors_filter_ic']) && $filters['doctors_filter_ic'] != 'false' && $filters['doctors_filter_ic'] != '') {
            $return['ids'][] = $this->_getDocsClinicsByClassifID(CLASSIF_IC, $filters['doctors_filter_ic']);
            $return['applied'] = true;
            $return['advanced'] = true;
        }

        // add filter by subscription

        if(!empty($filters['subscription'])) {
            $return['ids'][] = $this->_getDocsBySubscription();
            $return['applied'] = true;
        }

        // add filter by dcDoctors

        if(!empty($filters['dcDoctors'])) {
            $return['ids'][] = $this->_getDocsByDcDoctors();
            $return['applied'] = true;
        }

        if ($clinic) {
            $return['ids'][] = $this->_getDocsByClinicID($clinic);
            $return['applied'] = true;
            $return['advanced'] = true;
            $return['clinic'] = $clinic;
        } elseif (isset($filters['doctors_filter_clinic']) && $filters['doctors_filter_clinic'] != 'false' && $filters['doctors_filter_clinic'] != '') {
            $return['ids'][] = $this->_getDocsByClinicID($filters['doctors_filter_clinic']);
            $return['applied'] = true;
            $return['advanced'] = true;
            $return['clinic'] = $filters['doctors_filter_clinic'];
        }

        if (!empty($return['ids']) && count($return['ids']) > 1) {
            $return['ids'] = call_user_func_array('array_intersect', $return['ids']);
        } else {
            if (isset($return['ids'][0])) {
                $return['ids'] = $return['ids'][0];
            }
        }

        if(count($doctorsClinicsWithSchedule) > 0) {
            foreach ($doctorsClinicsWithSchedule as $val) {
                if(in_array($val['id'], $return['ids'])) {
                    if(isset($return['clinic'])) {
                        if($val['cl_id'] == $return['clinic']) {
                            $return['doctorsWithClinics'][] = $val;
                            $return['clinicList'][] = $val['cl_id'];
                        }
                    } else {
                        $return['doctorsWithClinics'][] = $val;
                        $return['clinicList'][] = $val['cl_id'];
                    }
                }
            }
        } else {
            $return['doctorsWithClinics'] = array();
            $return['clinicList'] = array();
        }

        return $return;
    }

    public function showOne() {

        global $config;

        loadLibClass('logger')->file('overview')->addString('function showOne')->append();

        if (!getG('paramID1')) {
            openDefaultPage();
        }

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT `d`.*, di.*, d.id AS id
			    		FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (d.id = dtc.d_id)
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` AS di ON 
								di.id = (SELECT d1.id FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` AS d1 WHERE d.id = d1.doctor_id LIMIT 1)
			    		WHERE 1
							AND `d`.`id` = '" . mres(getG('paramID')) . "'
							AND dtc.vp_id IS NULL
							AND `d`.`is_hidden_on_piearsta` = 0
							AND `d`.`deleted` = 0
							AND dtc.enabled = 1
							AND `d`.`enabled` = 1
							".$clinicIdFilter."
							";

		$query = new query($this->db, $dbQuery);

		if ($query->num_rows()) {

            /** @var array $doctor */
		    $doctor = $query->getrow();

		    $doctor['specialties'] = $this->_getLinkedClassificators($this->getLang(), $doctor['id'], CLASSIF_SPECIALTY);
					    $doctor['services'] = $this->_getLinkedClassificators($this->getLang(), $doctor['id'], CLASSIF_SERVICE);
		    $doctor['companies'] = $this->_getCompanies($doctor['id']);

		    if (!empty($doctor['companies'][getG('paramID1')])) {

				$doctor['active_company'] = $doctor['companies'][getG('paramID1')];

		    } elseif (!getG('paramID1') && !empty($doctor['companies'])) {

				reset($doctor['companies']);
				$doctor['active_company'] = current($doctor['companies']);

		    } else {

		    	openDefaultPage();
		    }

            // get network

            $network = null;
            $clinicId = $doctor['active_company']['id'];
            $currDate = date('Y-m-d H:i:s', time());

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

                    $hasSubscription = $_SESSION['user']['dcSubscription']['product_clinic'] == $doctor['active_company']['id'];

                } elseif (!empty($_SESSION['user']['dcSubscription']['product_network'])) {

                    $hasSubscription = $_SESSION['user']['dcSubscription']['product_network'] == $network;
                }
            }

            $doctor['hasSubscription'] = $hasSubscription;
            $doctor['services'] = $this->_getLinkedClassificators($this->getLang(), $doctor['id'], CLASSIF_SERVICE, 'd_id', $clinicId, $network);

            // here we are checking if given lat and lng are valid google coords and are inside bounds of Latvia
            $bounds = $config['latvia_bounds'];

            if(!$doctor['active_company']['lat'] || !is_numeric($doctor['active_company']['lat']) || $doctor['active_company']['lat'] > $bounds['n'] || $doctor['active_company']['lat'] < $bounds['s']) {
                $doctor['active_company']['lat'] = 'wrong';
            }

            if(!$doctor['active_company']['lng'] || !is_numeric($doctor['active_company']['lng']) || $doctor['active_company']['lng'] < $bounds['w'] || $doctor['active_company']['lng'] > $bounds['e']) {
                $doctor['active_company']['lng'] = 'wrong';
            }

		    if (!empty($doctor['specialties'])) {
				reset($doctor['specialties']);
				$doctor['active_specialty'] = current($doctor['specialties']);
		    }
		    $doctor['companies_count'] = count($doctor['companies']);

		    $doctor['already_faved'] = false;
		    $this->setPData(false, 'showFav');
		    $userData = $this->getPData('userData');
		    if (isset($userData['id'])) {
				$this->setPData(true, 'showFav');
				$dbQuery = "SELECT *
        FROM `" . $this->cfg->getDbTable('profiles', 'doctors') . "`
					    		WHERE 1
        AND `profile_id` = " . $userData['id'] . "
        AND `doctor_id` = " . $doctor['id'] . "
        AND `clinic_id` = " . $doctor['active_company']['id'] . "";
				$query = new query($this->db, $dbQuery);
				if ($query->num_rows()) {
				    $doctor['already_faved'] = true;
				}
		    }

			$sheduleData = $this->getShedule(getG('paramID'), getG('paramID1'));
			$sheduleData['calendar_max_date'] = $doctor['active_company']['max_d'];

            $remoteServices = $this->_getRemoteServices($doctor);

            if(!empty($remoteServices) && !empty($doctor['person_code']) && ! empty($doctor['email'])) {
                $doctor['remoteServices'] = $remoteServices;
                $doctor['consultations_enabled'] = '1';
            } else {
                $doctor['remoteServices'] = null;
                $doctor['consultations_enabled'] = '0';
            }

            if($userData['id']) {
                $this->setSessionData();
            }

            // sanitize description

            $sanitizedDescr = removeTags($doctor['description'], 'script,iframe');
            $doctor['description'] = $sanitizedDescr;

            //


			$this->setPData($sheduleData, 'sheduleData');
		    $this->setPData($doctor, 'doctor');
		    $this->setPData($this->config, "doctorsConfig");

            $this->setPData($config['google']['api_key'], 'API_KEY');

		    $pageTitle = $doctor['name'] . ' ' . $doctor['surname'];
		    if (isset($doctor['active_specialty'])) {
				$pageTitle .= ' - ' . $doctor['active_specialty']['title'];
		    }
		    if (isset($doctor['active_company'])) {
				$pageTitle .= ' - ' . $doctor['active_company']['name'];
		    }
		    $this->setPData(array('pageTitle' => $pageTitle), 'web');
		    $this->setPData(array('pageDescription' => $doctor['description']), 'web');
		    $this->setPData(str_replace(array('{var:phone}', '{var:email}'), array($doctor['phone'], '<a href="mailto:' . $doctor['email'] . '">' . $doctor['email'] . '</a>'), gL('doctors_open_local_schedule_text', 'Some text, Telefons: {var:phone}, Epasts: {var:email}')), 'localDoctorScheduleText');

		    $this->tpl->assign("TEMPLATE_DOCTORS_MODULE_DATA", $this->tpl->output("_data", $this->getPData()));
		    $this->tpl->assign("TEMPLATE_DOCTORS_MODULE", $this->tpl->output("item", $this->getPData()));

		    return $this;
		} else {
		    openDefaultPage();
		}
    }

    /**
     * @param bool $doctorId
     * @param bool $clinicId
     * @param bool $date
     * @param int $days
     * @return array
     * @throws Exception
     */
    public function getShedule($doctorId = false, $clinicId = false, $date = false, $days = 13, $showEmptyDates = true, $action = null)
    {

        $passedDate = $date;
        $passedDays = $days;

        loadLibClass('logger')->file('overview')->addString('function getShedule')->append();
        loadLibClass('logger')->file('overview')->addArrayFilterEmpty(array(
            'doctorId' => $doctorId,
            'clinicId' => $clinicId,
            'date' => $date,
            'days' => $days,
        ),'params')->append();

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
                $firstMonth = date("F", $date);
    		} elseif (getP('type') == 'prev') {
    			$firstMonth = date("F", $date);

    		} else {
    			$firstMonth = date("F", strtotime($date));
    		}
    	}
        /** @var shedule $shedule */
    	$shedule = loadLibClass('shedule');
    	$interval = $shedule->getWeekDays($date, $days);

        $data = $shedule->getDoctorSchedule($date, $days, $doctorId, $clinicId, $this->doctorFilter, $showEmptyDates, $action);

    	$monthData = array();
    	$monthData[14] = $shedule->getMonthDays($date, 13);
    	$monthData[10] = $shedule->getMonthDays($date, 9);
    	$monthData[7] = $shedule->getMonthDays($date, 6);

    	$sheduleData = array(
    		'data' => $data['slots'],
    		'nearest' => $data['nearest'],
    		'can_reserve' => $data['can_reserve'],
    		'prev' => $data['prev'],
    		'next' => $data['next'],
    		'week' => $shedule->getStartAndEndDate($date, $days),
    		'interval' => $interval,
    		'intervalTablet' => array_slice($interval, 0, 10),
    		'intervalMobile' => array_slice($interval, 0, 7),
    		'intervalMobile2' => array_slice($interval, 7),
    		'firstMonth' => gL("month_" . $firstMonth, $firstMonth),
    		'monthData' => $monthData,
    		'debug' => array(
    		    'passedDate' => $passedDate,
    		    'passedDays' => $passedDays,
            ),
    	);

    	return $sheduleData;
    }

    private function _getCompanies($doc_id) {
		$clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }
		$dbQuery = "SELECT `c`.*, ci.*, cc.*, c.id AS id, clicity.title AS citytitle, clidistrict.title AS districttitle
			    		FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc
			    			LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self') . "` `c` ON (`c`.`id` = `dtc`.`c_id`)
			    			LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` ci ON (`c`.`id` = `ci`.clinic_id)
			    			LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts') . "` cc ON (`c`.`id` = `cc`.clinic_id AND cc.default = 1)
			    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clicity ON (`clicity`.`c_id` = `c`.`city` AND `clicity`.`lang` = '" . getDefaultLang() . "')
		    				LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` clidistrict ON (`clidistrict`.`c_id` = `c`.`district` AND `clidistrict`.`lang` = '" . getDefaultLang(). "')
			    		WHERE 1
        AND c.enabled = 1
        AND `dtc`.`d_id` = " . mres($doc_id) . "
							" . $clinicIdFilter . "
							";
		$query = new query($this->db, $dbQuery);
		return $query->getArray('id');
    }

    private function _getLinkedClassificators($lang, $doc_id, $classif_type, $group_by = 'd_id', $clinicId = null, $networkId = null) {

		if (is_array($doc_id)) {
		    $docWhere = "IN (" . implode(",", $doc_id) . ")";
		} else {
		    $docWhere = "= '" . mres($doc_id) . "'";
		}

        $insJoin = "";
        $insSelect = ", 0 as subscription ";
        $currDate = date('Y-m-d H:i:s', time());

        if($clinicId && $networkId) {
            $insJoin = " LEFT JOIN ins_network_clinic_special_prices ins ON (CASE WHEN ins.clinic_id IS NOT NULL THEN ins.clinic_id = $clinicId ELSE ins.network_id = $networkId END AND ins.service_id = cl.id AND ins.start_datetime <= '$currDate' AND ins.end_datetime > '$currDate') ";
            $insSelect = ", ins.id IS NOT NULL as subscription ";
        }

        $sdOn = empty($clinicId) ? '' : ' AND sd.clinic_id = ' . $clinicId;
		$sdFields = $classif_type == CLASSIF_SERVICE ? ', sd.price, sd.duration ' : '';
        $serviceJoin = $classif_type == CLASSIF_SERVICE ? ' LEFT JOIN mod_service_details sd ON (sd.service_id = cl.id AND sd.doctor_id = dtcl.d_id AND sd.is_active = 1'.$sdOn.')' : '';

        $dbQuery = "SELECT DISTINCT `dtcl`.`d_id`, cli.title as localized_title, `cli`.* " . $sdFields . " $insSelect
						FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` dtcl
			    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'self') . "` cl ON (`cl`.`id` = `dtcl`.`cl_id` AND `cl`.`type` = `dtcl`.`cl_type` AND `cl`.`enable` = 1)
			    			LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cli ON (`cli`.`c_id` = `cl`.`id` AND `cli`.`lang` = '" . $lang . "')
			    			$insJoin
			    			" . $serviceJoin . "
			    		WHERE 1
        AND `dtcl`.`d_id` " . $docWhere . "
        AND `dtcl`.`cl_type` = '" . mres($classif_type) . "'
        AND cli.lang = '".mres($lang)."'";

        $query = new query($this->db, $dbQuery);

        $classificators = $query->getArray();

        foreach ($classificators as $k => $classificator) {

            if (empty($classificators[$k]['localized_title'])) {
                $classificators[$k]['localized_title'] = $classificators[$k]['title'];
            }
        }

		if (is_array($doc_id)) {
		    $return = array();
		    foreach ($classificators as $row) {
				if (!isset($return[$row[$group_by]])) {
				    $return[$row[$group_by]] = array();
				}
				$return[$row[$group_by]][] = $row;
		    }
		    return $return;
		} else {
		    return $classificators;
		}
    }

    public function loadClinicsDoctors() {

		$doctorsIds = $this->showList(getG('paramID'));
		$this->setPData($doctorsIds, 'doctorsIds');

        $userData = $this->getPData('userData');

        if($userData['id']) {
            $this->setSessionData();
        }

        $cl = loadLibClass('cl');
		$classificators = $cl->getList('id', true, array(), getG('paramID'));

		if (isset($classificators[CLASSIF_SPECIALTY])) {

			$existsManipulations = array();

			$dbQuery = "
                SELECT DISTINCT(dtcl.cl_id)
                FROM `mod_clinics` c
                LEFT JOIN `mod_doctors_to_clinics` dtc ON (c.id = dtc.c_id AND c.id = ".getG('paramID').")
                LEFT JOIN `mod_doctors_to_classificators` dtcl ON (dtc.d_id = dtcl.d_id)
                LEFT JOIN `mod_doctors` d ON(dtcl.d_id = d.id)
                WHERE
                    c.enabled = 1 AND
                    d.deleted = 0 AND
                    d.is_hidden_on_piearsta = 0 AND
                    d.id IS NOT NULL
        AND `dtcl`.`cl_type` = ".CLASSIF_SPECIALTY."";

			$query = new query($this->db, $dbQuery);

			if ($query->num_rows()) {
			    $existsManipulations = $query->getArray('cl_id');
			    $classificators[CLASSIF_SPECIALTY] = array_intersect_key($classificators[CLASSIF_SPECIALTY], $existsManipulations);
			}
			else{
			    $classificators[CLASSIF_SPECIALTY] = array();
			}

			$this->setPData($classificators[CLASSIF_SPECIALTY], 'specialtyList');
		}
		if (isset($classificators[CLASSIF_SERVICE])) {

			$existsManipulations = array();

			$dbQuery = "
                SELECT DISTINCT(dtcl.cl_id)
                FROM `mod_clinics` c
                LEFT JOIN `mod_doctors_to_clinics` dtc ON (c.id = dtc.c_id AND c.id = ".getG('paramID').")
                LEFT JOIN `mod_doctors_to_classificators` dtcl ON (dtc.d_id = dtcl.d_id)
                LEFT JOIN `mod_doctors` d ON(dtcl.d_id = d.id)
                WHERE
                    c.enabled = 1 AND
                    dtc.enabled = 1 AND
                    d.deleted = 0 AND
                    d.is_hidden_on_piearsta = 0 AND
                    d.id IS NOT NULL AND `dtcl`.`cl_type` = ".CLASSIF_SERVICE."";

			$query = new query($this->db, $dbQuery);

			if ($query->num_rows()) {
			    $existsManipulations = $query->getArray('cl_id');
			    $classificators[CLASSIF_SERVICE] = array_intersect_key($classificators[CLASSIF_SERVICE], $existsManipulations);
			}
			else{
			    $classificators[CLASSIF_SERVICE] = array();
			}

			$this->setPData($classificators[CLASSIF_SERVICE], 'servicesList');
		}
		$this->setPData(getLM($this->cfg->getData('mirrors_doctors_page')), 'doctorPageUrl');
		$this->setPData(getG('paramID'), 'clinic_id');

		$this->tpl->assign("TEMPLATE_DOCTORS_MODULE_DATA", $this->tpl->output("_data", $this->getPData()));
		$this->tpl->assign("TEMPLATE_DOCTORS_MODULE", $this->tpl->output("external_list", $this->getPData()));

		return $this;
    }

    public function addToFavourites($doc_id, $clinic_id, $faved) {

		$this->noLayout(true);

		$userData = !empty($_SESSION['user']) ? $_SESSION['user'] : null;

		if (isset($userData['id'])) {
		    if ($faved !== 'false') {
				$exSqlParams = "AND `doctor_id` = " . mres($doc_id) . " AND `clinic_id` = " . mres($clinic_id);
				deleteFromDbById($this->cfg->getDbTable('profiles', 'doctors'), $userData['id'], 'profile_id', $exSqlParams);
		    } else {
				$dbData		    = array();
				$dbData['profile_id']   = $userData['id'];
				$dbData['doctor_id']    = $doc_id;
				$dbData['clinic_id']    = $clinic_id;

				saveValuesInDb($this->cfg->getDbTable('profiles', 'doctors'), $dbData);
		    }
		    jsonSend(true);
		}
		jsonSend(false);
    }

    public function filterReservations() {

        loadLibClass('logger')->file('overview')->addString('function filterReservations')->append();

        $this->_parseGetFilters();

        $this->parseDoctorFilter(
            getP('doctors_filters'),
            getP('queryString')
        );
        loadLibClass('logger')->file('overview')->addArray($_POST,'filterReservations _POST')->append();
        loadLibClass('logger')->file('overview')->addArray($this->doctorFilter,'filterReservations doctorFilter')->append();

//    	$filters = array();
//
//    	if(getP('remote_services')) {
//    	    $filters['remote_services'] = getP('remote_services');
//        }
//
//    	if (getP('payment_type')) {
//    		$filters['payment_type'] = getP('payment_type');
//    	} else {
//    		$filters['payment_type'] = array(-1);
//    	}

        $qString = getP('queryString');
        $days = intval(getP('days'));

        $days--;

        $queries = array();

        $service = null;
        $specialty = null;

        if($qString && strlen($qString) > 0) {

//            parse_str($qString, $queries);

//            if(isset($queries['doctors_filter_services']) && $queries['doctors_filter_services']) {
//
//                $service = $queries['doctors_filter_services'];
//                if($service && strlen($service) > 0 && $service != 'false') {
//                    $filters['doctors_filters']['main']['doctors_filter_services'] = $service;
//                }
//            }
//
//            if(isset($queries['doctors_filter_specialty']) && $queries['doctors_filter_specialty']) {
//                $specialty = $queries['doctors_filter_specialty'];
//                if($specialty && strlen($specialty) > 0 && $specialty != 'false') {
//                    $filters['doctors_filters']['main']['doctors_filter_specialty'] = $specialty;
//                }
//            }
        }

        $debug = null;

        $lastDate = getP('lastDate');

        if($lastDate) {

            if(getP('type') == 'next') {

                $lastDate = date('Y-m-d', (strtotime($lastDate)) + (86400 * ($days == 6 || $days == 13 ? 6 : 1) ));

                if( date('N', strtotime($lastDate)) == 1 ) {
                    $lastDate = date('Y-m-d', (strtotime($lastDate)) + 86400);
                }

                $date = date('Y-m-d', strtotime('monday this week', strtotime( $lastDate )));


            } elseif (getP('type') == 'prev') {

                //$lastDate = date('Y-m-d', (strtotime($lastDate)) - 86400 * $days);
                $lastDate = date('Y-m-d', (strtotime($lastDate)) - (86400 * ($days == 6 || $days == 13 ? $days : 6) ));

                if( date('N', strtotime($lastDate)) == 1 ) {
                    $lastDate = date('Y-m-d', (strtotime($lastDate)) + 86400);
                }

                $date = date('Y-m-d', strtotime('monday this week', strtotime( $lastDate )));

            } else {

                if( date('N', strtotime($lastDate)) == 1 ) {
                    $lastDate = date('Y-m-d', (strtotime($lastDate)) + 86400);
                }

                $date = date('Y-m-d', strtotime('monday this week', strtotime( $lastDate )));
            }
        }

        $debArray = array();

        $debArray['date'] = $date;

    	if (!is_array(getP('doctorId'))) {

            $sheduleData = $this->getShedule(getP('doctorId'), getP('clinicId'), $date, $days);

            if(DEBUG) {
                $debArray[] = $sheduleData;
            }

//    	    if($filters['remote_services'] == 'true') {
//
//                $docsWithRemoteServices = $this->_getDocsByRemoteServices();
//
//                if(!in_array(getP('doctorId'), $docsWithRemoteServices)) {
//                    $sheduleData['data'] = array();
//                    $sheduleData['nearest'] = null;
//                    $sheduleData['can_reserve'] = false;
//                    $sheduleData['prev'] = null;
//                    $sheduleData['next'] = null;
//                }
//            }

    		$this->setPData($sheduleData, 'sheduleData');

    		$resp = array(
                'html' => $this->tpl->output("calendar", $this->getPData())
            );

            if(DEBUG) {
                $resp['debug'] = $debArray;
            }

            return $resp;
//            if (count($sheduleData['data']) > 0) {
//
//    			return array(
//    				'html' => $this->tpl->output("calendar", $this->getPData())
//    			);
//
//    		} else {
//
//    			return array(
//    				'html' => $this->tpl->output("calendar", $this->getPData())
//    			);
//    		}

    	} else {

    		$body = array();
    		$doctors = array();

    		foreach (getP('doctorId') AS $doctorId => $clinicIds) {

    			foreach ($clinicIds AS $clinicId) {

    				$dbQuery = "SELECT `d`.*, di.*, d.id AS id
			    		FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (d.id = dtc.d_id)
			    			LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` di ON (d.id = di.doctor_id)
			    			LEFT JOIN mod_clinics c ON (c.id = dtc.c_id)
			    		WHERE 1
        AND c.enabled = 1
        AND dtc.vp_id IS NULL
        AND `d`.`id` = '" . mres($doctorId) . "'
        AND `d`.`is_hidden_on_piearsta` = 0
        AND `d`.`deleted` = 0
        AND dtc.enabled = 1
        AND `d`.`enabled` = 1";

    				$query = new query($this->db, $dbQuery);
    				$row = $query->getrow();

    				$row['id'] = $doctorId;
    				$row['clinic_id'] = $clinicId;

    				$row['shedule'] = $this->getShedule($doctorId, $clinicId, $date, $days);
    				$doctors[] = $row;

                    if(DEBUG) {
                        $debArray[] = $row['shedule'];
                    }

    				if ($row['shedule']['prev']) {
    					$this->setPData(true, "sheduleDataPrev");
    				}

    				if ($row['shedule']['next']) {
    					$this->setPData(true, "sheduleDataNext");
    				}

    				$this->setPData($row, 'doctor');
    				$body['calendar_list_body_' . $doctorId . '_' . $clinicId] = $this->tpl->output("calendar_list_body", $this->getPData());
    			}
    		}


    		if (count($doctors) > 0) {
    			$this->setPData($doctors, 'doctors');
                $resp = array(
                    'html_header' => $this->tpl->output("calendar_list_header", $this->getPData()),
                    'html_body' => $body,
                );

                if(DEBUG) {
                    $resp['debug'] = $debArray;
                }

                return $resp;

    		} else {

    		    $resp = array(
                    'empty' => true
                );

                if(DEBUG) {
                    $resp['debug'] = $debArray;
                }

    			return $resp;
    		}
    	}
    }

    public function autocomplete($q)
    {
        $q = trim($q);
    	$result = array();
        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }
    	if ($q) {
    		$dbQuery = "SELECT `di`.`name`, `di`.`surname`
							FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` AS di ON
								di.id = (SELECT d1.id FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` AS d1 WHERE d.id = d1.doctor_id LIMIT 1)
								LEFT JOIN mod_doctors_to_clinics dtc ON (dtc.d_id = d.id)
								LEFT JOIN mod_clinics c ON (c.id = dtc.c_id)
							WHERE 1
        AND c.enabled = 1
        AND `d`.`is_hidden_on_piearsta` = 0
        AND dtc.enabled = 1
        AND `d`.`deleted` = 0
								" . $clinicIdFilter . "
                                AND (
                                    `di`.`name` LIKE '%" . mres($q) . "%'
        OR `di`.`surname` LIKE '%" . mres($q) . "%'
        OR CONCAT_WS(' ', `di`.`name`, `di`.`surname`) LIKE '%" . mres($q) . "%'
				    			)
							ORDER BY `di`.`name`, `di`.`surname` DESC";
    		$query = new query($this->db, $dbQuery);
    		while ($query->getrow()) {
    			$result[] = array(
    			    'value' => $query->field('name') . ' ' . $query->field('surname'),
                    'spec' =>false,
                );
    		}

    		/** @var cl $cl */
    		$cl = loadLibClass('cl');
    		$specialies = $cl->getListByType(CLASSIF_SPECIALTY, '', $q, true);
            if (count($specialies) > 0) {

                $titlesArray = array();

                foreach ($specialies AS $spec) {

                    if(!in_array($spec['title'], $titlesArray)) {
                        $titlesArray[] = $spec['title'];
                        $result[] = array(
                            'value' => $spec['title'],
                            'label' => $spec['title'],
                            'spec' => true,
                        );
                    }
                }

                unset($titlesArray);
            }
    	}

    	return $result;
    }

    public function specialtyAutocomplete($q, $clinicId = null) {
        $q = trim($q);
        $result = array();
        if($q) {
            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $specialies = $cl->getListByType(CLASSIF_SPECIALTY, '', $q, true, $clinicId);

            if (count($specialies) > 0) {

                $titlesArray = array();

                foreach ($specialies AS $spec) {

                    if(!in_array($spec['title'], $titlesArray)) {
                        $titlesArray[] = $spec['title'];
                        $result[] = $spec['title'];
                    }
                }

                unset($titlesArray);
            }
        }

        return $result;
    }

    public function serviceAutocomplete($q, $clinicId = null) {
        $q = trim($q);
        $result = array();
        if($q) {
            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $services = $cl->getListByType(CLASSIF_SERVICE, '', $q, true, $clinicId);
            if (count($services) > 0) {
                foreach ($services AS $service) {
                    $result[] = $service['title'];
                }
            }
        }

        return $result;
    }

    public function doctorAutocomplete($q) {
        $q = trim($q);
        $result = array();
		$clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND dtc.c_id in (" . $this->allowed_clinics . ")";
        }
		
        if ($q) {
            $dbQuery = "SELECT DISTINCT `di`.`name`, `di`.`surname`
							FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (d.id = dtc.d_id)
								LEFT JOIN mod_clinics c ON (c.id = dtc.c_id)
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` AS di ON
								di.id = (SELECT d1.id FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` AS d1 WHERE d.id = d1.doctor_id LIMIT 1)
							WHERE 1
        AND c.enabled = 1
        AND dtc.vp_id IS NULL
        AND `d`.`is_hidden_on_piearsta` = 0
        AND dtc.enabled = 1
        AND `d`.`deleted` = 0
        AND `d`.`enabled` = 1
        AND (
            `di`.`name` LIKE '%" . mres($q) . "%'
        OR `di`.`surname` LIKE '%" . mres($q) . "%'
        OR CONCAT_WS(' ', `di`.`name`, `di`.`surname`) LIKE '%" . mres($q) . "%'
				    			)
							ORDER BY `di`.`name`, `di`.`surname` DESC";
            $query = new query($this->db, $dbQuery);
            while ($query->getrow()) {
                $result[] = array(
                    'value' => $query->field('name') . ' ' . $query->field('surname')
                );
            }
        }
        return $result;
    }

    public function updateReviewsCount($doctorId, $count)
    {
    	$dbData		    = array();
    	$dbData['reviews_count']   = $count;

    	saveValuesInDb($this->cfg->getDbTable('doctors', 'self'), $dbData, $doctorId);
    	return $doctorId;
    }

    private function setSessionData() {

        // if request sheduleId by query string we put it in session

        if(isset($_SESSION['redirectTo'])) {

            parse_str(parse_url($_SESSION['redirectTo'])['query'], $query);

            if(isset($query['scheduleId'])) {
                $_SESSION['schedule_id'] = $query['scheduleId'];
            }

            if(isset($query['cdata'])) {
                $_SESSION['cdata'] = $query['cdata'];
            }
        }

        // if scheduleId in session we pass it to page data (this will call js handler to show addReservation popup)

        if(@$_SESSION['schedule_id']){
            $this->setPData($_SESSION['schedule_id'], 'schedule_id');
            if(!isset($_SESSION['redirectTo'])) {
                unset($_SESSION['schedule_id']);
            }
        }

        if(@$_SESSION['cdata']) {

            $this->setPData(base64_decode($_SESSION['cdata']), 'calendarData');
            if (!isset($_SESSION['redirectTo'])) {
                unset($_SESSION['cdata']);
            }
        }

        if(@$_SESSION['cons_doctor_id'] && @$_SESSION['cons_clinic_id']){
            $this->setPData(array(
                'doctorId' => $_SESSION['cons_doctor_id'],
                'clinicId' => $_SESSION['cons_clinic_id'],
            ), 'consData');

            if(!isset($_SESSION['redirectTo'])) {
                unset($_SESSION['cons_doctor_id']);
                unset($_SESSION['cons_clinic_id']);
            }
        }
    }

    public function getCalendarData() {

        $clinicId = getP('clinicId');
        $doctorId = getP('doctorId');
        $action = getP('action');

        if(!$clinicId || !$doctorId) {
            return array(
                'success' => false,
                'error' => 'no clinicId or doctorId',
            );
        }

        $services = (getP('services') && is_array(getP('services'))) ? getP('services') : array();
        $passedStartDate = $startDate = getP('startDate') ? date(PIEARSTA_DT_FORMAT, strtotime(getP('startDate'))) : date(PIEARSTA_DT_FORMAT, time());
        $days = getP('days') ? getP('days') : null;

        $showEmptyDates = getP('showEmptyDates') ? true : false;

        $this->parseDoctorFilter(
            getP('doctors_filters'),
            null
        );

        // get doctor schedule

        if($showEmptyDates) {


            if($startDate) {

                if($action == 'next') {

                    $startDate = date('Y-m-d', (strtotime($startDate)) + 86400);

                }

                $startDate = date('Y-m-d', strtotime('monday this week', strtotime( $startDate )));
            }

        }

        $result = $this->getShedule($doctorId, $clinicId, $startDate, $days, $showEmptyDates, $action);

        $days = intval($days);

        if(!$showEmptyDates) {

            $result['data'] = array_filter($result['data']);

            if($days && count($result['data']) > $days) {

                if($action == 'prev') {

                    $result['data'] = array_slice($result['data'], -$days, $days);

                } else {

                    $result['data'] = array_slice($result['data'], 0, intval($days));
                }
            }

        } else {

            $newResult = array();

            for($i = 0; $i < $days; $i++) {

                $date = date('Y-m-d', strtotime($startDate . '+ ' . $i . ' day'));

                if(isset($result['data'][$date])) {
                    $newResult[$date] = $result['data'][$date];
                } else {
                    $newResult[$date] = array();
                }
            }

            $result['data'] = $newResult;

            $result['widgetDebug'] = array(
                'passedDate' => $passedStartDate,
                'startDate' => $startDate,
                'days' => $days,
                'data' => $result['data'],
            );
        }

        return $result;
    }
}