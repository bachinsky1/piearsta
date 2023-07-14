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
 * CMS sitemap module
 * Admin path. show sitemap
 * This module is used in popup window to select page
 * 22.10.2008
 */

class sitemap extends Module_cms {
		
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
		$this->module = new sitemapData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
		
		// Including and preparing needs templates
		switch ($this->uri->segment(3)) {								
			case "" : 

				$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
				
				// Assign fields names
				$this->tpl->assign("idField", getP('idField'));	
				$this->tpl->assign("titleField", getP('titleField'));
				$this->tpl->assign("func", getP('func') ? getP('func') : 'defaultReturn');	
				
				$data["countries"]["data"] = getSiteCountries();
				$data["countries"]["sel"] = (getP("filterCountry") ? getP("filterCountry") : getDefaultCountry());
				
				$data["languages"]["data"] = getSiteLangsByCountry($data["countries"]["sel"]);
				$data["languages"]["sel"] = (getP("filterLang") ? getP("filterLang") : getDefaultLanguage($data["countries"]["sel"]));
				
				$this->noLayout(true);
				jsonSend($this->tpl->output($this->name, $data));
							
		}
	}
	
	function run() {

		switch ($this->uri->segment(3)) {								
			case "moduleTable" : 

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
		
	}
}
?>
