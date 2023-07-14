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
 * Contacts module
 * 30.06.2010
 */

class contacts extends Module {
	/**
	 * Class constructor
	 */
	public function __construct()	{		

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);

		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
		$this->module = new contactsData();
		
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
	}
	
	/**
	 * Modules process function
	 * This function runs auto from Module class
	 */
	public function run() {

	    // It is decided to open mail client for registered users as well

        // Comment next lines to return to old functionality
        ///
        $subject = $this->cfg->getData('mailSubj');
        $body = 'Jautājums:';
        $params = '?subject=' . $subject . '&body=' . $body;

        header('location: mailto:' . $this->cfg->getData('mailTo') . $params);
        exit;
        ///

	    if(!$this->getPData()['isLoggedUser']) {

            $subject = $this->cfg->getData('mailSubj');
            $body = 'Jautājums:';
            $params = '?subject=' . urlencode($subject) . '&body=' . $body;

	        header('location: mailto:' . $this->cfg->getData('mailTo') . $params);
	        exit;
        }

        $this->addJSFile('contacts');

	    $themes = getContactThemes();

        $this->setPData($themes, 'themes');
        $this->setPData(json_encode($themes), 'themesJson');

		if(getP('action') == 'sendForm') {
			$this->module->SaveForm();
		} else {
			if (isset($_SESSION['contacts']['ok'])) {
				unset($_SESSION['contacts']['ok']);
				$this->setPData(array('ok' => true), 'contacts');
			}
		}
		
		$this->setPData(false, "showHelLine");
		$this->tpl->assign("CONTACTS_CONTENT", $this->tpl->output("template", $this->getPData()));
		
	}
	
}
?>