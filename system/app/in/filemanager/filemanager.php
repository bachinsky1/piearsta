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
 * CMS file manager module
 * Admin path. Add/Delete/Edit actions with files in upload folder.
 * This is general file manager module for cms.
 * 08.02.2009
 */

class filemanager extends Module_cms {
	
	/**
	 * $module - Object of module admin class
	 */
	public $module;
	
	function __construct() {
		
		parent :: __construct();
		$this->name = get_class($this);
		
		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new filemanagerData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
		
		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 
				
				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				$this->tpl->assign("moduleName", $this->name);	
				
			

				break;		
		}
		
	}
	
	function run() {
		
		$action = $this->uri->segment(3);
			
		switch ($action) {
			// Default value: show all files and folders
			case "" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {					
					$this->tmpl->newBlock("FILEMANAGER_DATA");
					$this->tmpl->assign("defaultFolder", AD_UPLOAD_FOLDER);
				}
				break;
			case "moduleTable" : 
				if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
					$result = $this->module->showFolderWithFiles($folder, true);
					$this->functions->ajaxSend($result);
				}
				break;
			// delete value: delete file or folder	
			case "delete" : 
				if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
					$this->cmsLog->writeLog($this->getModuleName(), "delete, file or folder: " . $item);
					$this->module->deleteFileOrFolder($item);
				}
				break;
			// edit value: edit file or folder
			case "edit" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->tmpl = new TemplatePower(AD_CMS_MODULE_FOLDER . $this->getModuleName() . "/tmpl/edit.html");
					$this->tmpl->prepare();
					$this->tmpl->newBlock("EDIT_FOLDER_OR_FILE");
					$this->module->editFileOrFolder($item);
				}
				break;
			// checkname value: check for correct entered file or folder name
			case "checkname" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$result = $this->module->checkFileOrFolderName($item, $value);
					$this->functions->jsonSend($result);
				}
				break;
			// save value: save file or folder values	
			case "save" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->cmsLog->writeLog($this->getModuleName(), "save, file or folder: " . $item);
					$this->module->saveFileOrFolderInfo($item, $value);
				}
				break;
			// move value: move file to folder	
			case "move" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->cmsLog->writeLog($this->getModuleName(), "move file: " . $item . " to folder:" . $mFolder);
					$result = $this->module->moveFileToFolder($item, $mFolder);
					$this->functions->jsonSend($result);
				}
				break;
			// addFolder value: add new folder
			case "addFolder" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->tmpl = new TemplatePower(AD_CMS_MODULE_FOLDER . $this->getModuleName() . "/tmpl/edit.html");
					$this->tmpl->prepare();
					$this->tmpl->newBlock("ADD_FOLDER");
					$this->tmpl->assign("fItem", $item);
				}
				break;
			// saveFolder value: save new folder value	
			case "saveFolder" : 
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->cmsLog->writeLog($this->getModuleName(), "create folder: " . $value . " in: " . $item);
					$this->module->saveNewFolder($item, $value);
				}
				break;
			// uploadForm value: show file upload form		
			case "uploadForm":
				if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
					$this->tmpl = new TemplatePower(AD_CMS_MODULE_FOLDER . $this->getModuleName() . "/tmpl/uploadform.html");
					$this->tmpl->prepare();
					$this->tmpl->assign("uploadFolder", $this->functions->getG("folder", AD_UPLOAD_FOLDER));
				}						
				break;
			case "upload":
				if ($this->cmsUser->userId) {
					$this->noLayout(true);
					$data = $this->module->uploadFiles();
					jsonSend($data);
				} 	
				break;							
		}
		
	}
		
}

?>