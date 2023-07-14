<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		Rolands Eņģelis <rolands@efumo.lv>
 * @copyright   Copyright (c) 2012, Efumo.
 * @link		http://adweb.lv
 * @version		1
 */

// ------------------------------------------------------------------------

class banners extends Module_cms {

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
        $this->module = new bannersData();

        $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

        $this->tpl->assign('MODULE_HEAD', $this->tpl->output('head'));
        $this->tpl->assign('MAIN_URL', '/' . $this->uri->segment(0) . '/' . $this->uri->segment(1) . '/');
        $this->tpl->assign('moduleName', $this->getModuleTitle());
        
    	if (getP('clear')) {
			unset($_SESSION[$this->name]['filter']);
		}
        
        // Include head/bottom actions
        switch($this->uri->segment(3)) {
            case '':
            	
                $data = $this->createModuleTableNav(true, false, true);    
                
                $lang = '';
        		if(isset($_SESSION[$this->name]["filter"]["lang"])){
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
        $values = getP('values');
        $enabled = getP('enabled');
        
        switch($action) {
            case 'moduleTable':
                if($this->cmsUser->haveUserRole('VIEW', $this->getModuleId())) {
                    $this->noLayout(true);
					jsonSend($this->module->showTable());
                }
                break;    
            case 'edit':
                if($this->cmsUser->haveUserRole('EDIT', $this->getModuleId())) {
                	$data = $this->module->edit($id);
                	$this->includeTemplate($data, 'edit');
                    
                }
                break;
            case 'save':
                if($this->cmsUser->haveUserRole(($id ? 'EDIT' : 'ADD'), $this->getModuleId())) {
                    $this->noLayout(true);
                    jsonSend(array(
                        'id'    => $this->module->save($id, $values)
                    ));
                }
                break;
            case 'enable':
                if($this->cmsUser->haveUserRole('EDIT', $this->getModuleId())) {
                    $this->noLayout(true);
                    $this->module->enable($id, $enabled);
                }
                break;
            case 'delete':
                if($this->cmsUser->haveUserRole('DELETE', $this->getModuleId())) {
                    $this->noLayout(true);
                    $this->module->delete($id);
                }
                break;
        }
    }
}
?>
