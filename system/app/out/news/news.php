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
 * News module
 * 30.06.2010
 */

class news extends Module {
	
	/**
	 * Class constructor
	 */
	public function __construct()	{		

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);

		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new newsData();
		
		$this->setTmplDir();
	}
	
	public function setTmplDir() {
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
	}
	
	/**
	 * Modules process function
	 * This function runs auto from Module class
	 */
	public function run()
    {


        // fix to show news when site structure include welcome page
        // and start page have an url not / but /home

        $mainpageId = getMirror(getDefaultPageId());

        if (
            $mainpageId == $this->getCData("id") ||
            strpos($_SERVER['REQUEST_URI'], '/home') > -1 ||
			strpos($_SERVER['REQUEST_URI'], '/mans-profils') > -1
        ) {

            $this->module->loadStarpage();

        } else {

            if (getG('docUrl')) {
                $this->module->showOne();
            } else {
                $this->module->showList();
            }
        }
    }
	
}
?>
