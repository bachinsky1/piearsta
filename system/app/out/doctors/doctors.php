<?php

/**
 * ADWeb - Content managment system
 *
 */
// ------------------------------------------------------------------------

class doctors extends Module {

    /**
     * Class constructor
     */
    public function __construct() {

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);
	
		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new doctorsData();
	
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
		// $this->addCSSFile('jquery-ui.min');
		// $this->addCSSFile('jquery-ui.structure.min');

		switch ($this->getCData("id")) {
		    
		    case getMirror($this->cfg->getData('mirrors_clinics_page')):
				if (getG('paramID')) {
				    $this->addJSFile('doctors_external');
				    $this->module->loadClinicsDoctors();
				}
				break;
		    default :
				if (getG('paramID')) {
				    $this->addJSFile('doctors');
				    $this->module->showOne();
				} else {
				    $this->addJSFile('doctors');
				    $this->module->showList();
				}
			break;
		}
    }

    public function action_getCalendarData()
    {
        jsonSend($this->module->getCalendarData());
    }
    
    public function action_filterReservations() 
    {
    	
    	$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
    	jsonSend($this->module->filterReservations());
    	
    }
    
    public function action_updateReviewsCount()
    {
    	if (getP('doctor')) {
    		jsonSend($this->module->updateReviewsCount(getP('doctor'), getP('count')));
    	}
    }
    
    public function action_autocomplete()
    {
    	if (getGP('q')) {
    		jsonSend($this->module->autocomplete(getGP('q')));
    	}
    }

    public function action_specialtyAutocomplete()
    {
        if (getGP('q')) {

            $clinicId = getGP('clinicId') ? getGP('clinicId') : null;

            jsonSend($this->module->specialtyAutocomplete(getGP('q'), $clinicId));
        }
    }

    public function action_serviceAutocomplete()
    {
        if (getGP('q')) {

            $clinicId = getGP('clinicId') ? getGP('clinicId') : null;

            jsonSend($this->module->serviceAutocomplete(getGP('q'), $clinicId));
        }
    }

    public function action_doctorAutocomplete()
    {
        if (getGP('q')) {
            jsonSend($this->module->doctorAutocomplete(getGP('q')));
        }
    }

    public function action_setFilters()
    {
        if (getGP('fields')) {
            $this->module->setFilters(getGP('fields'));
        }
    }

    public function action_AddFav()
    {
        $this->module->addToFavourites(getP('doc_id'), getP('clinic_id'), getP('faved'));
    }

}
