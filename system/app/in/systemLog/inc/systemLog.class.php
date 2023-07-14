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
 * ADWEB
 * CMS system logger module admin class
 * Admin path. Show system log table and admin can empty this table
 * 27.10.2008
 */

class systemLogData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	function __construct() {
		parent :: __construct();
		$this->name = "syslog";
	}
	
	/**
	 * Get all info data from db and create module table
	 */
	public function showSyslogTable() {
		header("Content-type:text/html");
		$returnHtml = "";
		
		/**
		 * Creating module table, using cmsTable class
		 * This is table head information
		 */
		$tableHeadValues = Array(
			gLA('m_date','Date'), 
			gLA('m_module','Module'), 
			gLA('m_action','Action'), 
			gLA('m_username','Username'), 
			gLA('m_ip','IP'), 
		);
		$tableHeadSort = Array(true, true, false, true, true);
		$tableHeadSortFields = Array('`date`', '`module`', '', '`username`', '`ip`');
		
		/**
		 * Start table and creating table head
		 */
		$this->cmsTable->startTable();
		$this->cmsTable->drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields);
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `ad_adminsyslog`" . $this->moduleTableSqlParms("date", "DESC");
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			
			/**
			 * Creating one row
			 */
			$this->cmsTable->startOneRow();
			
			/**
			 * Draw all table cells
			 */
			$this->cmsTable->drawOneCell($query->field('date'));
			$this->cmsTable->drawOneCell($query->field('module'));
			$this->cmsTable->drawOneCell($query->field('action'));
			$this->cmsTable->drawOneCell($query->field('username'));
			$this->cmsTable->drawOneCell($query->field('ip'));
			
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
	 * Empty system log table
	 */
	public function emptySyslogTable() {
		
		$dbQuery = "TRUNCATE TABLE `ad_adminsyslog`";
		$query = new query($this->db, $dbQuery);
	
	}
	
}
?>