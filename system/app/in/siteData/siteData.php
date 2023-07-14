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

/**
 * @subpackage	siteData
 * @author		Maris Melnikovs <maris.melnikovs@efumo.lv>
 * @copyright	Copyright (c) 2012, Efumo.
 * @link		http://www.efumo.lv
 * @version		2
 * 17.01.2013
 */

	class siteData extends Module_cms {
		
		public $module;
		
		function __construct() {
			
			parent :: __construct();
			$this->name = get_class($this);
			require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
			$this->module = new siteDataData();
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->tpl->assign("MODULE_HEAD", $this->tpl->output("head"));
			$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
			$this->tpl->assign("moduleName", $this->getModuleTitle());
			$data = $this->module->getSiteData();
			$this->includeTemplate($data);
		}
		
		function run() {
			
			$action = $this->uri->segment(3);
			
			switch ($action) {
				case "save" :
					if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
						$this->noLayout(true);
							
						$values = getP("values");
							
						$this->module->save( $values );
					}
					break;
				case "clearTwitter" :
					if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
						$this->noLayout(true);
					}
					break;
			}
		}
	}

?>