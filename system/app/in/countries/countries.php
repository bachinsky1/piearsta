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
 * CMS countries module
 * Admin path. Edit/Sort/Enable/Disable and other actions with site countries
 * This is general countries module of cms
 * 10.05.2010
 */

class countries extends Module_cms {
		
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
		$this->module = new countriesData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				
		
				$this->includeTemplate();
							
		}
		
	}
	
	public function run() {

		$action = $this->uri->segment(3);
		
		$id = getP("id") ? getP("id") : $this->uri->segment(4);
		$value = getP("value");
		$domains = getP("domains");
		$languages = getP("languages");
		
		switch ($action) {
			case "moduleTable" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
					$this->noLayout(true);
					
					$result["html"] = $this->module->showTable();
					jsonSend($result);

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
					
					$result["id"] = $this->module->save($id, $value, $domains, $languages);
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
			case "nextedit" :
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$nextId = $this->module->getNextId($id);
					$this->module->edit($nextId);
				}
				break;
		}
	
	}
}

?>
