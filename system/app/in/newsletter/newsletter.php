<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		Jānis Šakars <janis.sakars@efumo.lv>
 * @copyright   Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		1
 */
// ------------------------------------------------------------------------

class newsletter extends Module_cms {

    /**
     * $module - Object of module admin class
     * @var newslettersData
     */
    public $module;

    /**
     * Constructor
     */
    public function __construct() {

        parent :: __construct();
        $this->name = get_class($this);

        require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
        $this->module = new newsletterData();

        $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

        $this->tpl->assign('MODULE_HEAD', $this->tpl->output('head'));
        $this->tpl->assign('MAIN_URL', '/' . $this->uri->segment(0) . '/' . $this->uri->segment(1) . '/');
        $this->tpl->assign('moduleName', $this->getModuleTitle());

        /**
         * Loads data and templates for ajax request from inside
         */
        switch ($this->uri->segment(3)) {
            case "" : // main screen, table with existing data
                $this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
                   
                $this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
                $this->tpl->assign("moduleName", $this->getModuleTitle());

				$data = $this->createModuleTableNav(false, false, true);
				$data['languages'] = array();
				$languages = getSiteLangsByCountry(getCountry());
				foreach ($languages as $key => $value) {
					$data['languages'][$value['lang']] = $value['title'];
				}
				$data['languageOptions'] = dropDownFieldOptions($data['languages'], '', true);
				$this->includeTemplate($data);
                
				break;
        }
    }

    /**
     * Index function
     */
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
            case "delete" :
            	if ($this->cmsUser->haveUserRole("VIEW", $this->getModuleId())) {
                    $this->noLayout(true);

                    $this->cmsLog->writeLog($this->getModuleName(), "delete, id=" . $id);
                    $this->module->delete($id);
                } 
                break;
            case "enable" : 
                if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
                    $this->noLayout(true);
                    
                    $this->cmsLog->writeLog($this->getModuleName(), "blocked, id=" . $id . " value=" . $value);
                    $this->module->enable($id, $value);
                }
                break;
        }
    }

}