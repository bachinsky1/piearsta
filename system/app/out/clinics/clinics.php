<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class clinics extends Module {

    /**
     * Class constructor
     */
    public function __construct() {

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);
	
		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new clinicsData();
	
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
		
		if ($this->getNoLayout()) {
			$this->loadLabels('', true);
		}
    }

    /**
     * Modules process function
     * This function runs auto from Module class
     */
    public function run() {
		$this->addJSFile('ajax-list');
		$this->addJSFile('jquery-ui.min');

    	if (getG('paramID')) {
		    $this->module->showOne();
		} else {
		    $this->addJSFile('clinics');
		    $this->module->showList();
		}
    }
    
    public function action_updateReviewsCount()
    {
    	if (getP('clinica')) {
    		jsonSend($this->module->updateReviewsCount(getP('clinica'), getP('count')));
    	}
    }
    
    public function action_autocomplete()
    {
    	if (getGP('q')) {
    		jsonSend($this->module->autocomplete(getGP('q')));
    	}
    }

    public function action_serviceAutocomplete()
    {
        if (getGP('q')) {
            jsonSend($this->module->serviceAutocomplete(getGP('q')));
        }
    }

    public function action_setFilters()
    {
        if (getGP('fields')) {
            $this->module->setFilters(getGP('fields'));
        }
    }

}
