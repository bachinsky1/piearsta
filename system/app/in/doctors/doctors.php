<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class doctors extends Module_cms {

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
		$this->module = new doctorsData();
	
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
	
		$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
		$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
		$this->tpl->assign("moduleName", $this->getModuleTitle());
	
		if (getP('clear')) {
		    unset($_SESSION[$this->name]['filter']);
		}
	
		// Include head/bottom actions
		switch ($this->uri->segment(3)) {
		    case 'edit':
				$this->tpl->assign("aboutTextLength", 500);
				break;
		    case '':
	
				$data = $this->createModuleTableNav(true, false, true);
				$lang = '';
				if (isset($_SESSION[$this->name]["filter"]["lang"])) {
				    $lang = $_SESSION[$this->name]["filter"]["lang"];
				}
				$data['langs'] = getAllSiteLangs($lang);
		
				$this->includeTemplate($data);
			break;
		}
    }

    /**
     * Index function
     */
    public function run() {

		// Variables used in requests
		$action = $this->uri->segment(3);
		$id = getP('id') ? getP('id') : ($this->uri->segment(4) ? $this->uri->segment(4) : 0);
		$value = getP("value");
	
		switch ($action) {
		    case 'moduleTable':
				if ($this->cmsUser->haveUserRole('VIEW', $this->getModuleId())) {
				    $this->noLayout(true);
				    jsonSend($this->module->showTable());
				}
				break;
		    case 'view':
				if ($this->cmsUser->haveUserRole('VIEW', $this->getModuleId())) {
				    $data = $this->module->edit($id, true);
				    $this->includeTemplate($data, 'edit');
				}
				break;
		    case "edit" :
				if ($this->cmsUser->haveUserRole($id ? "EDIT" : "ADD", $this->getModuleId())) {
				    $data = $this->module->edit($id);
				    $this->includeTemplate($data, 'edit');
				}
				break;
		    case "delete" :
				if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
				    $this->noLayout(true);
				    $this->cmsLog->writeLog($this->getModuleName(), "delete, id=" . $id);
				    $this->module->delete($id);
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
		}
    }

}

?>
