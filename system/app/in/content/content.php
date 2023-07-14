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
 * CMS content module
 * Admin path. Edit/Add/Delete and other actions with content
 * This is general module of cms
 * 03.06.2008
 */

class content extends Module_cms {
		
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
		$this->module = new contentData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
		
		// Including and preparing needs templates
		switch ($this->uri->segment(2)) {								
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				
				
				$data["countries"]["data"] = getSiteCountries();
				$data["countries"]["sel"] = (getP("filterCountry") ? getP("filterCountry") : getDefaultCountry());
				
				$data["languages"]["data"] = getSiteLangsByCountry($data["countries"]["sel"]);
				$data["languages"]["sel"] = (getP("filterLang") ? getP("filterLang") : getDefaultLanguage($data["countries"]["sel"]));
				
				$this->includeTemplate($data);					
		}
	}
	
	function run() {

		$action = $this->uri->segment(2);
		
		$id = getP("id") ? getP("id") : $this->uri->segment(3);
		$value = getP("value");
		
		switch ($action) {
			case "moduleTable" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
					$this->noLayout(true);
					
					$result["html"] = $this->module->showList();
					if (getP("filterCountry")) {
						if (getP("filterLang")) {
							$result["filterLang"] = '';
						} else {
							$result["filterLang"] = getSiteLangsByCountry(getP("filterCountry"));
							foreach ($result["filterLang"] AS $k => $v) {
								$values[$v['lang']] = $v['title'];
							}
							
							$result["filterLang"] = dropDownFieldOptions($values, '', true);
						}
					}
							
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
			case "active" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "active, id=" . $id . " value=" . $value);
					$this->module->active($id, $value);
				}
				break;	
			case "delete" : 
				if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "delete, id=" . $id);
					$this->module->delete($id);
				}
				break;
			case "sort" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "sort, id=" . $id . " value=" . $value);
					$this->module->changeSort($id, $value);
				}
				break;	
			case "edit" : 
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->module->edit($id);
				}
				break;
			case "save" : 
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "save, id=" . $id);
					$this->module->save($id, $value, getP('modules'));
				}
				break;
			case "checkname" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);

					jsonSend($this->module->checkName($id, $value));
				}
				break;
			case "saveDND" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);

					jsonSend($this->module->saveDND($value));
				}
				break;
			case "move" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					$result = $this->module->moveOrChangeOrder($id, $dId, $pId);
				}
				break;	
			case "copy" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					$result = $this->module->copy($id);
				}
				break;					
			case "createTitleUrl" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					jsonSend(convertUrl($value));
				}
				break;
		}
		
	}
}
?>
