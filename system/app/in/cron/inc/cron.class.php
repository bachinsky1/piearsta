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
 * Cron module
 * Show all active crons and can run manualy each one
 * 10.05.2010
 */

class cronData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "cron";

		$this->dbTable = 'ad_cron';
		$this->dbTableLog = 'ad_cron_log';
	}
	
	/**
	 * Get all info data from db and create module table
	 */
	public function showTable() {
		header("Content-type:text/html");
		$returnHtml = "";
		
		/**
		 * Creating module table, using cmsTable class
		 * This is table head information
		 */
		$tableHeadValues = array(
			gLA('m_date','Date'),
			gLA('m_name','Name'),
			gLA('status','Status'),
			gLA('run','Run')
		);
		$tableHeadSort = array(false, true, false, false);
		$tableHeadSortFields = array('', 'cron', '', '');
		
		/**
		 * Start table and creating table head
		 */
		$this->cmsTable->startTable();
		$this->cmsTable->drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields);
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `".$this->dbTable."`" . $this->moduleTableSqlParms();
		$query = new query($this->db, $dbQuery);
		
		while ($query->getrow()) {

			/**
			 * Creating one row
			 */
			$this->cmsTable->startOneRow();
			
			$info = $this->getLastInfo($query->field('cron'));
			
			/**
			 * Draw all table cells
			 */
			$this->cmsTable->drawOneCell(date("d-m-Y H:i:s", $info["datetime"] ? $info["datetime"] : time()));
			$this->cmsTable->drawOneCell($query->field('cron'), (getG("sortField") == "cron" ? 'sort' : ''));
			$this->cmsTable->drawOneCell($info["status"] ? gLA('ok','OK') : gLA('none','None'));
			$this->cmsTable->drawOneCell('<a target="_blank" href="/loader.php?f=' . $query->field('cron') . '" title="' . $query->field('cron') . '">' . $query->field('cron') . '</a>');
			
			/**
			 * Closing this row
			 */
			$this->cmsTable->endOneRow();
		}
		
		/**
		 * Closing this table
		 */
		$this->cmsTable->endTable();
		$returnHtml = $this->cmsTable->returnTable;
				
		return $returnHtml;
	}
	
	/**
	 * Getting last update time and status
	 * 
	 * @param string	cron name
	 */
	private function getLastInfo($cron) {
		
		$dbQuery = "SELECT `status`, `datetime` FROM `".$this->dbTableLog."` WHERE `cron` = '" . $cron . "' ORDER BY `datetime` DESC LIMIT 1";
		$query = new query($this->db, $dbQuery);
		$query->getrow();
		
		$r["status"] = $query->field('status');
		$r["datetime"] = $query->field('datetime');
		
		return $r;
	}
	
}
?>