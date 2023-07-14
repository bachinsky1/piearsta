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
 * CMS Roles module
 * Admin path. Edit/Add/Delete
 * 04.12.2010
 */

class roles extends Module_cms {
		
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
		$this->module = new rolesData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				

				$data = $this->createModuleTableNav(true, false, false);		
				$this->includeTemplate($data);
							
		}
		
	}
	
	public function run() {

		$action = $this->uri->segment(3);
		
		$id = getP("id") ? getP("id") : $this->uri->segment(4);
		$value = getP("value");

		switch ($action) {
			case "moduleTable" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
					$this->noLayout(true);
					
					$result["html"] = $this->module->showTable();
					jsonSend($result);

				}
				break;
			case "enable" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "enable, id=" . $id . " value=" . $value);
					$this->module->enable($id, $value);
				}
				break;
			case "edit" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
							
					$this->module->edit($id);
				}
				break;
			case "save" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					$this->cmsLog->writeLog($this->getModuleName(), "save, id=" . $id);
					
					$roles = getP("roles");
					
					$result["id"] = $this->module->save($id, $value, $roles);
					jsonSend($result);
				}
				break;	
			case "delete" : 
				if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "delete, id=" . $id);
					$this->module->delete($id);
				}
				break;
			case "checkname" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					jsonSend($this->module->checkName($id, $value));
				}
				break;
		}
	
	}
}

?>
