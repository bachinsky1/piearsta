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
 * CMS users module
 * Admin path. Edit/Add/Delete
 * 20.11.2008
 */

class shedulesData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	private $roles = array('VIEW', 'ADD', 'EDIT', 'DELETE');
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "shedules";
		$this->dbTable = 'mod_shedules';
                $this->reservationTable = 'mod_reservations';

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
			"doctor_id" => array(
				'sort' => true,
				'title' => gLA('m_doctor','Doctor'),
				'function' => array(&$this, 'toDoctor'),
				'fields'	=> array('doctor_id')
			),
			"clinic_id" => array(
				'sort' => true,
				'title' => gLA('m_clinic','Clinic'),
				'function' => array(&$this, 'toClinic'),
				'fields'	=> array('clinic_id'),
			),
			"created" => array(
                                'sort' => true,
                                'title' => gLA('m_created','Created'),
                                'function' => 'convertDate',
                                'fields'	=> array('created'),
                                'params' => array('d-m-Y H:i:s')
				),                    
			"start_time" => array(
                                'sort' => true,
                                'title' => gLA('m_start','Start'),
                                'function' => array(&$this, 'toDate'),
                                'fields'	=> array('start_time'),
				),
			"end_time" => array(
				'sort' => true,
				'title' => gLA('m_end','End'),
                                'function' => array(&$this, 'toDate'),
				'fields'	=> array('end_time')
			),	
                        "actions" => array(
                                'sort' => false,
                                'title' => gLA('m_actions','Actions'),
                                'function' => array(&$this, 'sheduleActionsLink'),
                                'fields'	=> array('id')
                        )                    

		);

		/**
		 * Getting all information from DB about this module
		 */

                $dbQuery =  "SELECT SQL_CALC_FOUND_ROWS 
                                s.id, 
                                s.doctor_id, 
                                s.clinic_id, 
                                s.start_time,
                                s.end_time,
                                r.created
                            FROM " . 
                                $this->dbTable . " s 
                            INNER JOIN ".$this->reservationTable." r ON(
                                r.doctor_id = s.doctor_id AND 
                                r.clinic_id = s.clinic_id
                            ) 
                            WHERE 
                            
                            r.`start` >= NOW() AND
                            s.start_time >= now() AND 
                            r.status NOT IN ( 1,3 ) AND 
                            r.cancelled_at IS NULL  AND
                            r.cancelled_by IS NULL  
                            AND (
                                (s.start_time >= r.`start` AND
                                s.start_time < r.`end` )
                                OR (s.end_time > r.`start` AND s.end_time <= r.`end` )
                            )
                            AND 
                            s.booked = 0 " .
                            $this->moduleTableSqlParms("start_time", "DESC");


		$query = new query($this->db, $dbQuery);
		
		// Create module table
		$this->cmsTable->createTable($table, $query->getArray(), true, 'start_time');

		return $this->cmsTable->returnTable;
	}
	

        
        public function toDate($value) {
            if($value) return date('d-m-Y H:i',strtotime($value));
        }
        
        public function toDoctor($value) {
            
            if($value){
                $sql = "SELECT name, surname FROM mod_doctors_info WHERE doctor_id = ".$value;
                $res_query = new query($this->db, $sql);
                $res_row = $res_query->getrow(); 
                
                return $res_row['name'].' '.$res_row['surname'].' ['.$value.']';
            }
            
            return $value;
        }  
        
        public function toClinic($value) {
            if($value){
                $sql = "SELECT name FROM mod_clinics WHERE id = ".$value;
                $res_query = new query($this->db, $sql);
                $res_row = $res_query->getrow(); 
                
                return $res_row['name'].' ['.$value.']';
            }
            
            return $value;
        }         
	
	/**
	 * Enable or disable
	 * 
	 * @param int	id
	 * @param bool	enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `enable` = '" . $value . "' WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Delete from DB
	 * 
	 * @param int	id
	 */
	public function delete($id) {
		
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);

		}		
	}
	
	/**
	 * Edit 
	 * 
	 * @param int 	id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();

		if(isset($id) && $id != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * " .
						"FROM `" . $this->dbTable . "` " .
						"WHERE `id` = '" . $id . "'" .
						" LIMIT 0,1";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();

			
		} else {		
			$data["edit"]["enable"] = 1;
	
		}

		$data['modules'] = $this->getPublicModules();

		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id ? $id : '';

		return jsonSend($r);		
	}
	

	/**
	 * Get All public modules 
	 * 
	 */
	private function getPublicModules() {
		
		$result = array();
		
		$dbQuery = "SELECT `id`, `translations`, `name`
				FROM `ad_modules` m
				WHERE m.menuname = 'modules' OR m.default = '1'";
		$query = new query($this->db, $dbQuery);
		while ($query->getrow()) {
			$translations = unserialize($query->field('translations'));
			$result[] = array("id" => $query->field('id'), 'name' => isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('name'));
		}
		
		return $result;
	}
	
	/**
	 * Saving information in DB
	 * 
	 * @param int	id, it's need if we are editing
	 * @param array information values
	 * @param array roles 
	 */
	public function save($id, $value, $roles) {
		
		$value = addSlashesDeep(jsonDecode($value));
		
		$value['date'] = time();
		
		$id = saveValuesInDb($this->dbTable, $value, $id);
		
		
		
		return $id;
	}
	
        
	public function book($id = "") {    
		
		if(isset($id) && $id != "") {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `booked` = 1 WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}			
	}        

}
?>