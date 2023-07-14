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
 * Admin system logger.class class.
 * Log all critical actions in ADWEB CMS.
 * Example: login, save, delete
 * 03.03.2008
 */

class Syslog {
   
	/**
	 * $logtable - CMS system logger.class table name in DB
	 * $clientIP - Client ip
	 * $username - CMS user name
	 * $mdb - DB class object
	 */
	public $logtable = "ad_adminsyslog";
	public $clientIP;
	public $cmsUser;
	public $mdb;

	/**
	 * AdminSysLog constructor
	 * Constructor use global $cmsUser object
	 */
	public function Syslog() {

		$this->mdb = &loadLibClass('db');
		$this->cmsUser = &loadLibClass('user');
		$this->clientIP = getIp();
	}

	/**
	 * Function: write log in admin system logger.class table in DB
	 * 
	 * @param string	Module name where was some corrections
	 * @param string 	Action logger.class text
	 */
	public function writeLog($moduleName, $action) {

		$dbQuery = "INSERT INTO " . $this->logtable . " " .
						"(`date`, `username`, `module`, `action`, `ip`) " .
						"VALUES (NOW(), '" . $this->cmsUser->getUserName() . "', '" . $moduleName . "', '" . $action . "', '" . $this->clientIP . "')";
		$query = new query($this->mdb, $dbQuery);
		$query->free();

	}

}
?>
