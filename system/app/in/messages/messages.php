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
 * CMS messages params module
 * Admin path. Edit/Add/Delete and other actions with messages params
 * This is module to put some variable to messages
 * 14.12.2008
 */

class messages extends Module_cms {
		
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
		$this->module = new messagesData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				
				
				$this->tpl->assign("mList", $this->module->createModuleList(getP("filterModule")));
				$this->tpl->assign("lList", getSiteLanguageDropDown(getP("notTranslated")));
				$this->tpl->assign("cList", $this->module->getCountryList(getP("filterCountry")));
				$data = $this->createModuleTableNav();		
				$this->includeTemplate($data);
							
		}
		
	}
	
	public function run() {

		$action = $this->uri->segment(3);
		
		$id = getP("mId") ? getP("mId") : $this->uri->segment(4);
		$value = getP("value");
		$langValues = getP("langValues");
		
		switch ($action) {
			case "moduleTable" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
					$this->noLayout(true);
					
					$result["html"] = $this->module->showMessagesTable();
					$result["rCounts"] = $this->getTotalRecordsCount(false);
					jsonSend($result);

				}
				break;
			case "enable" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "enable, id=" . $id . " value=" . $value);
					$this->module->enableMessage($id, $value);
				}
				break;
			case "edit" : 
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
					$this->noLayout(true);
							
					$this->module->editMessage($id);
				}
				break;
			case "save" : 
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
					$this->noLayout(true);
					$this->cmsLog->writeLog($this->getModuleName(), "save, id=" . $id);
					
					$result["id"] = $this->module->saveMessage($id, $value, $langValues);
					jsonSend($result);
				}
				break;	
			case "delete" : 
				if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "delete, id=" . $id);
					$this->module->deleteMessage($id);
				}
				break;
			case "checkname" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					jsonSend($this->module->checkName($id, $value));
				}
				break;
			case "nextedit" :
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$nextId = $this->module->getNextId($id);
					$this->module->editMessage($nextId);
				}
				break;	
		}
	
	}
}

?>
