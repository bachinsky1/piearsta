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
 * CMS general user class.
 * Give access to cms or not
 * Logging checking, logout and etc.
 * 12.04.2008
 *
 */ 
class User {
	
	public $userTable;
	public $userId;
	public $loginUrl;
	public $userData;
	public $userRolesData;
	
	/**
	 * Constructor
	 */
	public function __construct($userTable) {
		$this->mdb = &loadLibClass('db');
		$this->userTable = $userTable;   
	}


	/**
	 * User login function
	 * Make a login, else return false value and make delay
	 * 
	 * @param string		user name
	 * @param string		user password
	 */
	public function login($userName, $userPassword) {
		
		$result = false;
		$userPassword = md5($userPassword);
		$userId = $this->getUserData($userName, $userPassword);
      
		if ($userId) {    				
			
			$_SESSION['ad_userid'] = $userId;
			$_SESSION['ad_username'] = $userName;
			$_SESSION['ad_userpassword'] = $userPassword;  
			$this->updateLastLogin($userId);  
			$result = true;			      	
		}
		else {
			$this->logout();
			$this->delayLogout(3);
		}	
			
		return $result;
	}
	
	/**
	 * Get user last login time and update it after thar
	 * 
	 * @param int	user id
	 */
	private function updateLastLogin($userId) {
		
		$dbQuery = "UPDATE " . $this->userTable . " SET `lastlogin` = '" . time() . "' WHERE `id` = '" . $userId . "'";
		$query = new query($this->mdb, $dbQuery);
	}


	/**
	 * User logout function
	 */
	public function logout() {
    
		unset($_SESSION['ad_userid']);
		unset($_SESSION['ad_username']);
		unset($_SESSION['ad_userpassword']);
		session_destroy();
		       
	    // Reset all class vars
	    $this->userId = 0;
	    $this->userData = Array();
	    $this->userRolesData = Array();

	}

	/**
	 * User login checking
	 * If user is logged return true, else false
	 */
	public function isLogin() {   

		// Writing page headers for no caching
		$this->writePageHeader();

		$userId = getS('ad_userid');	
		$userName = getS('ad_username');
		$userPassword = getS('ad_userpassword');

		// Checking user name and password again from DB
		$result = ($userName && $userPassword) ? $this->getUserData($userName, $userPassword) : false;  

		if (!$result) {
			logWarn("Username isn't logged in.");
			if (getP("action") == "login") {
				$this->delayLogout(3);
			}
			
       		return false;
		}
		else {
			return true;
		}	

	}

	/**
	 * Getting current logged user name
	 */
	public function getUserName() { 
		if (isset($this->userData["username"])) {
			return $this->userData["username"];
		}	
	}

	/**
	 * Writing header for no caching admin pages
	 */
	public function writePageHeader() {
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-cache");
		header("Cache-Control: post-check=0, pre-check=0");
		header("Pragma: no-cache");
	} 

	/**
	 * Checking have user the requested role for needed module
	 * Return true or false
	 * 
	 * @param string		role name
	 * @param string		module id that must check
	 */
	public function haveUserRole($role, $moduleId) {

		if (!$this->userId) {
			return false;
		} 
		if ($this->isAdmin()) {
			return true;
		}		
 		
		$result = isset($this->userRolesData[$moduleId][$role]) ? true : false;
		return $result;
	}
 
	/**
	 * Admin user checking
	 * If admin return true, else false 
	 * 
	 */
	public function isAdmin() {

		return ($this->userData["admin"] == "1") ? true : false;
	}

	/**
	 * Getting all information from DB users table
	 * And put it in array and return user id
	 * 
	 * @param string 	user name
	 * @param string 	user password
	 */
	public function getUserData($userName, $userPassword) {

		$this->userData = Array();
		$this->userId = 0;

		if ($userName && $userPassword) {
			
			$dbQuery = "SELECT * FROM " . $this->userTable . " WHERE `enable` = '1' AND `username` = '" . mres($userName) . "' AND `password` = '" . mres($userPassword) . "'";
			$query = new query($this->mdb, $dbQuery);
	
			if ($query->result) {
				$query->getrow();
				if ($query->field("username") == $userName) {
					$this->userData = $query->row;
					$this->userId = $this->userData["id"];
			    	
					if (!$query->field("admin")) {
						// Getting all user roles
						$this->getAllUserRoles();	
					}
					    	
		    	}	
	    	}
			$query->free();	
		} 
    	
		return $this->userId;	       
	}	

	/**
	 * Get all user roles from DB
	 */
	public function getAllUserRoles() {

		$this->userRolesData = array();
 	
		if ($this->userId) {

		 	$dbQuery = "SELECT `module_id`, `role` FROM `ad_user_roles` WHERE `user_id` = '" . $this->userId . "'";
	 		$query = new query($this->mdb, $dbQuery);
	 		
			// Filling user roles data array
			while ($query->getrow()) {
				$this->userRolesData[$query->field("module_id")][$query->field("role")] = true;
			}
			
			$query->free();
		} 	
	}	
 
	/**
	 * Delay for hack attacks
	 * $seconds - Int seconds
	 */
	public function delayLogout($seconds) { 
		$endTime = time() + $seconds;
		while (time() < $endTime) {
			$logout = true;
		}	
	}
 
}
?>