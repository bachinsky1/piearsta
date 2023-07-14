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

class Cl {

    /** @var db  */
    protected $db;

    /** @var config  */
    protected $cfg;

    private $allowed_clinics;
    /**
     * Class constructor
     */
    public function __construct() {

        $this->db = loadLibClass('db');
        $this->cfg = loadLibClass('config');
        if (defined('AD_CMS')) {
            $this->module = &loadLibClass('module.cms');
        } else {
            $this->module = loadLibClass('module');
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
    public function getList($key = false, $onlyExist = false, $doctors = array(), $clinicId = false, $clinicList = false) {
        $list = array();

        if ($clinicId) {
            $dbQuery = "SELECT dtc.*
							FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc 
							LEFT JOIN mod_clinics c ON (c.id = dtc.c_id) 
							WHERE 1
							    AND c.enabled = 1 
							    AND dtc.enabled = 1
								AND `dtc`.`c_id` = '" . mres($clinicId) . "'";
            $query = new query($this->db, $dbQuery);
            while ($query->getrow()) {
                $doctors[] = $query->field('d_id');
            }
        }

        $clinicIdFilter = '';
        $clinicIdFilters = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND clinics.id in (" . $this->allowed_clinics . ")";
            $clinicIdFilters =  "c.id in (" . $this->allowed_clinics . ") AND";
        }
        $dbQuery = "SELECT c.*, cd.title
							FROM `" . $this->cfg->getDbTable('classificators', 'self') . "` c
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cd ON (c.id = cd.c_id)
							WHERE 1
								AND `c`.`enable` = '1'
								AND `cd`.`lang` = '" . mres(getDefaultLang()) . "'
								AND c.type <> 5
								" . ($onlyExist ? "
								AND (
									EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` cdd WHERE c.id = cdd.cl_id " . (!empty($doctors) ? " AND cdd.d_id IN (" . implode(',', $doctors) . ")" : "") . ")
									OR
									EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('clinics', 'classificators') . "` cdd2 WHERE c.id = cdd2.cl_id)
									OR
									" . ($clinicList ? " EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('clinics', 'self') . "` clinics WHERE clinics.enabled = 1 AND (c.id = clinics.city OR c.id = clinics.district) " . $clinicIdFilter . " ) "
                    : "EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc
											LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self') . "` clinics ON dtc.c_id = clinics.id
											WHERE clinics.enabled = 1 AND dtc.enabled = 1 AND (c.id = clinics.city OR c.id = clinics.district) " . $clinicIdFilter . " )") . "
								) " : "") . "
								UNION
                                SELECT cl.*, cli.title FROM mod_classificators cl
                                LEFT JOIN mod_classificators_info cli ON(cli.c_id = cl.id)
                                LEFT JOIN mod_clinics_to_classificators c2cl ON(c2cl.cl_id = cl.id)
                                LEFT JOIN mod_clinics c ON(c.id = c2cl.clinic_id)
                                LEFT JOIN mod_doctors_to_clinics d2c ON(c.id = d2c.c_id)
                                LEFT OUTER JOIN mod_doctors d ON(d.id = d2c.d_id) 
                                WHERE
									" . $clinicIdFilters . "					
                                    c.enabled = 1 AND
                                    d2c.enabled = 1 AND
                                    d.enabled = 1 AND 
                                    d.deleted = 0 AND 
                                    cl.enable = 1 AND 
                                    cl.type = 5
                                GROUP BY cl.id
							ORDER BY type, title ASC";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows() > 0) {

            while ($row = $query->getrow()) {

                $row['title'] = html_entity_decode($row['title']);

                if ($key && isset($row[$key])) {
                    $list[$row['type']][$row[$key]] = $row;
                } else {
                    $list[$row['type']][] = $row;
                }
            }
        }

        return $list;
    }

    public function getListByType($type, $key = '', $query = '', $onlyExist = false, $clinicId = null) {

        $where = "";

        if($query) {
            $where .= " AND cld.title LIKE '%" . mres($query) . "%' ";
        }

        if($onlyExist) {

            $clWhere = "";

            if($clinicId) {
                $clWhere .= " AND d2c.c_id = " . mres($clinicId) . " ";
            }

            $clinicIdFilter = '';
            if ($this->allowed_clinics) {
                $clinicIdFilter = "clin.id in (" . $this->allowed_clinics . ") AND ";
            }
            $where .= " AND EXISTS (
                                    SELECT 1 FROM mod_doctors_to_classificators d2cl
                                    LEFT JOIN mod_classificators c ON (c.id = d2cl.cl_id)  
                                    LEFT JOIN mod_doctors_to_clinics d2c ON (d2c.d_id = d2cl.d_id)
                                    LEFT JOIN mod_clinics clin ON (clin.id = d2c.c_id)  
                                    LEFT JOIN mod_doctors d ON (d.id = d2cl.d_id)  
                                    WHERE 
                                        c.enable = 1 AND
                                        clin.enabled = 1 AND 
										" . $clinicIdFilter . "					   
                                        d2c.vp_id IS NULL AND
                                        d2c.enabled = 1 AND
                                        d.enabled = 1 AND
                                        d.deleted = 0 AND
                                        d2cl.cl_id = cl.id 
                                        " . $clWhere . "
                                    ) ";
        }

        $dbQuery = "SELECT DISTINCT cl.*, cld.title, cld.c_id 
	                FROM mod_classificators cl
	                LEFT JOIN mod_classificators_info cld ON (cld.c_id = cl.id) 
	                WHERE
	                    cl.enable = 1 AND 
	                    cl.type = " . mres($type) . "
	                    " . $where . " 
	                ORDER BY cld.title ASC";

        $query = new query($this->db, $dbQuery);

        return $query->getArray($key);
    }

    public function getSpList($key = false)
    {
        $list = array();

        $dbQuery = "SELECT c.*, cd.title
							FROM `" . $this->cfg->getDbTable('classificators', 'self') . "` c
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cd ON (c.id = cd.c_id)
							WHERE 1
								AND `c`.`enable` = '1'
								AND `cd`.`lang` = '" . mres($this->module->getLang()) . "'
								AND `c`.`type` IN (" . implode(',', $this->cfg->get('classificators_startpage')) . ")
								AND (
									EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('doctors', 'classificators') . "` cdd WHERE c.id = cdd.cl_id)
									OR
									EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('clinics', 'classificators') . "` cdd2 WHERE c.id = cdd2.cl_id)
									OR
									EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc
											LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self') . "` clinics ON dtc.c_id = clinics.id
											WHERE clinics.enabled = 1 AND dtc.enabled = 1 AND (c.id = clinics.city OR c.id = clinics.district) )
								)
							ORDER BY type, sort ASC";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows() > 0) {

            while ($row = $query->getrow()) {
                if ($key && isset($row[$key])) {
                    $list[$row['type']][$row[$key]] = $row;
                } else {
                    $list[$row['type']][] = $row;
                }
            }
        }

        return $list;
    }

    /**
     * @param int|null $limit
     * @return array
     */
    public function getHomepageItems($limit = null)
    {
        $list = array();

        $currLang = getLang();
        $defLang = getDefaultLang();

        $limitString = $limit ? (' LIMIT ' . ($limit * 3)) : '';

        $dbQuery = "SHOW TABLES LIKE 'mod_homepage_items'";
        $tQuery = new query($this->db, $dbQuery);

        if($tQuery->num_rows()) {

            if($limit) {

                $types = array(1,3,5,9);

                foreach ($types as $type) {

                    $dbQuery = "SELECT  hi.`id`, hi.`type`, hi.`title`, hit.`title`, hi.`original_id`, hit.lang FROM mod_homepage_items hi
                                INNER JOIN mod_homepage_items_titles hit ON (hi.original_id = hit.item_id)
                                WHERE 
                                      hi.`type` = " . $type . " AND
                                      (hit.lang = '$currLang' OR hit.lang = '$defLang') 
                                ORDER BY hi.`doctors_with_schedules` DESC, hi.`title_clean` ASC" . $limitString;

                    $query = new query($this->db, $dbQuery);

                    if ($query->num_rows() > 0) {

                        $list[$type] = $query->getArray();
                    }
                }


            } else {

                $dbQuery = "SELECT hi.`id`, hi.`type`, hi.`title`, hit.`title`, hi.`original_id`, hit.lang FROM mod_homepage_items hi
                                INNER JOIN mod_homepage_items_titles hit ON (hi.original_id = hit.item_id)
                                WHERE 
                                      (hit.lang = '$currLang' OR hit.lang = '$defLang') 
                                ORDER BY hi.`doctors_with_schedules` DESC, hi.`title_clean` ASC";

                $query = new query($this->db, $dbQuery);

                if ($query->num_rows() > 0) {

                    while ($row = $query->getrow()) {

                        $list[$row['type']][] = $row;
                    }
                }
            }
        }

        $processedList = array();

        if(!empty($list)) {

            foreach ($list as $typeKey => $type) {

                $processedList[$typeKey] = array();

                foreach ($type as $itemKey => $item) {

                    $filtered_arr = array_filter(
                        $type,
                        function($obj) use ($item) {
                            return $obj['original_id'] === $item['original_id'];
                        });

                    if(count($filtered_arr) > 1) {

                        $found = null;

                        foreach ($filtered_arr as $valuesArr) {

                            if($valuesArr['lang'] == $currLang) {
                                $found = $valuesArr;
                            }
                        }

                        if(empty($found) && is_array($found)) {

                            foreach ($filtered_arr as $valuesArr) {

                                if($valuesArr['lang'] == $defLang) {
                                    $processedList[$typeKey][] = $valuesArr;
                                }
                            }

                        } else {

                            if(is_array($found)) {
                                $processedList[$typeKey][] = $found;
                            }
                        }

                    } else {

                        $processedList[$typeKey][] = $item;
                    }
                }
            }
        }

        // eliminate duplicates (in titles)
        // fit to limit
        // add show more element if needed

        $list = array();

        foreach ($processedList as $typeKey => $type) {

            $showMore = false;

            $tempArr = unique_key($processedList[$typeKey], 'title');

            if(count($tempArr) > $limit) {
                $showMore = true;
            }

            if($limit) {

                $list[$typeKey] = array_slice($tempArr, 0, $limit);

                if($showMore) {
                    $list[$typeKey]['showMore'] = true;
                }

            } else {

                $list[$typeKey] = $tempArr;
            }
        }

        return $list;
    }

    public function getClinics($key = '') {

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }

        $dbQuery = "SELECT c.*
						FROM `mod_clinics` c
	    				WHERE 1
						" . $clinicIdFilter . "		  
	    				    AND c.enabled = 1 
	    					AND EXISTS (SELECT 1 FROM `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc WHERE dtc.c_id = c.id AND dtc.enabled = 1)
						ORDER BY c.`name` ASC";
        $query = new query($this->db, $dbQuery);
        return $query->getArray($key);
    }

    public function getClIdByTitle($title, $type)
    {
        $dbQuery = "SELECT c.id
							FROM `" . $this->cfg->getDbTable('classificators', 'self') . "` c
								LEFT JOIN `" . $this->cfg->getDbTable('classificators', 'details') . "` cd ON (c.id = cd.c_id)
							WHERE 1
								AND `c`.`type` = '" . mres($type) . "'
								AND `cd`.`title` = '" . mres($title) . "'
							LIMIT 1";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            return $query->getOne();
        }

        return null;
    }

    /**
     * @param $clinicId
     * @param $doctorId
     * @return array|bool
     */
    public function getSpecialtiesByDoctor($clinicId, $doctorId)
    {
        $dbQuery = "SELECT ci.c_id, ci.title FROM mod_classificators_info ci
                    LEFT JOIN mod_doctors_to_classificators d2c ON (ci.c_id = d2c.cl_id) 
                    LEFT JOIN mod_doctors_to_clinics d2clinics ON (d2clinics.d_id = d2c.d_id) 
                    LEFT JOIN mod_clinics c ON (c.id = d2clinics.c_id)
                    WHERE
                        c.enabled = 1 AND
                        d2c.cl_type = " . CLASSIF_SPECIALTY . " AND 
                        d2clinics.c_id = " . $clinicId . " AND 
                        d2clinics.enabled = 1 AND
                        d2c.d_id = " . $doctorId;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getArray();
        }

        return false;
    }

    /**
     * @param $clinicId
     * @param $doctorId
     * @return array|bool
     */
    public function getServicesByDoctor($clinicId, $doctorId)
    {
        $dbQuery = "SELECT ci.c_id, ci.title, ci.description, sd.id as service_details_id, sd.price, sd.duration, sd.service_description  
                    FROM mod_classificators_info ci
                    LEFT JOIN mod_doctors_to_classificators d2c ON (ci.c_id = d2c.cl_id) 
                    LEFT JOIN mod_doctors_to_clinics d2clinics ON (d2clinics.d_id = d2c.d_id)
                    LEFT JOIN mod_clinics c ON (c.id = d2clinics.c_id) 
                    LEFT JOIN mod_service_details sd ON (sd.service_id = ci.c_id AND sd.doctor_id = ".mres($doctorId)." AND sd.clinic_id = ".mres($clinicId).") 
                    WHERE
                        c.enabled = 1 AND
                        d2c.cl_type = " . CLASSIF_SERVICE . " AND 
                        d2clinics.c_id = " . mres($clinicId) . " AND 
                        d2clinics.enabled = 1 AND 
                        d2c.d_id = " . mres($doctorId);
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getArray();
        }

        return false;
    }

    /**
     * @param $clinicId
     * @param $doctorId
     * @return array
     */
    public function getRemoteServicesByDoctor($clinicId, $doctorId) {

        $remoteServices = array();

        /** @var serviceDetails $serviceDetailsClass */
        $serviceDetailsClass = loadLibClass('serviceDetails');

        $services = $serviceDetailsClass->getServices($clinicId, $doctorId);

        foreach ($services as $k => $service) {

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

}

?>