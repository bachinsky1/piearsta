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
 * CMS Users module
 * Admin path. Edit/Add/Delete
 * 20.11.2008
 */

class shedules extends Module_cms {
		
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
                
		$this->module = new shedulesData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 
				$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->getModuleTitle());				

				$data = $this->createModuleTableNav(false, false);		
                                //print_r($data); die();
				$this->includeTemplate($data);
							
		}
		
	}
	
	public function run() {

		$action = $this->uri->segment(3);
		
		$id = getP("id") ? getP("id") : $this->uri->segment(4);
		$value = getP("value");
                
		$start_time = getP("start_time");
		$end_time = getP("start_time");            
		
		switch ($action) {
			case "moduleTable" : 
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
					
					$result["html"] = $this->module->showTable();
					jsonSend($result);

				}
				break;
			case "book" :                    
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "book, id=" . $id );
					$this->module->book($id);
				}
				break;
			case "edit" : 
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
							
					$this->module->edit($id);
				}
				break;
			case "save" : 
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
					$this->cmsLog->writeLog($this->getModuleName(), "save, id=" . $id);
					
					$roles = getP("roles");
					
					$result["id"] = $this->module->save($id, $value, $roles);
					jsonSend($result);
				}
				break;	
			case "delete" : 
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
					
					$this->cmsLog->writeLog($this->getModuleName(), "delete, id=" . $id);
					$this->module->delete($id);
				}
				break;
			case "checkname" : 
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
					
					jsonSend($this->module->checkName($id, $value));
				}
				break;
				
			case "getGroup" :
				if ($this->cmsUser->isAdmin()) {
					$this->noLayout(true);
					
					jsonSend($this->module->getGroup(getP("group_id")));
				}
				break;
		}
	
	}
}

?>
