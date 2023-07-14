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
 * CMS System logger module
 * This module show changes in admin each module. This module needed to control users actions.
 * Only cms admin user can empty system log table.
 * 27.10.2008
 */

class systemLog extends Module_cms {
		
	/**
	 * $module - Object of module admin class
	 */
	public $module;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		
   		parent :: __construct();
		$this->name = get_class($this);
		
		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new systemLogData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				
					
				$data = $this->createModuleTableNav(false, false);		
				$this->includeTemplate($data);						
		}
		
	}
	
	public function run() {

		$action = $this->uri->segment(3);
		
		switch ($action) {
			case "moduleTable" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
					$this->noLayout(true);
					
					$result["html"] = $this->module->showSyslogTable();
					$result["rCounts"] = $this->getTotalRecordsCount(false);
					jsonSend($result);

				}
				break;
			case "empty" : 
				if ($this->cmsUser->isAdmin()) {
					$this->module->emptySyslogTable();
				}
				break;	
		}
	
	}
}

?>
