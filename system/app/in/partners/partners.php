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
 * CMS partners/textlist module
 * Admin path. Edit/Add/Delete and other actions.
 * 27.07.2010
 */

class partners extends Module_cms {
		
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
		$this->module = new partnersData();
		
        $this->clearOtherFiltersData($this->name); //Unsets other modules filter values from SESSION
        
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "edit" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				

				break;
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());					
				
				$data = $this->createModuleTableNav();		
				$this->includeTemplate($data);
				break;			
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
					
					jsonSend($this->module->showTable());

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
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
							
					$data = $this->module->edit($id);
					$this->includeTemplate($data, 'edit');
				}
				break;
			case "save" : 
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
					$this->noLayout(true);
					$this->cmsLog->writeLog($this->getModuleName(), "save, id=" . $id);
					
					$result["id"] = $this->module->save($id, $value);
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
            case "sort" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "sort, id=" . $id . " value=" . $value);
					$this->module->changeSort($id, $value);
				}
				break;
		}
	
	}
}

?>
