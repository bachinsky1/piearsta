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
 * Loader
 * 06.04.2010
 */
class Loader extends Base {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->cfg = &loadLibClass('config');
		$this->wk = &loadLibClass('workTime');

		if (defined('AD_CMS')) {
			loadLibClass('module', false);
			$this->m = &loadLibClass('module.cms');

			$this->m->load();

		} else {

			$this->m = &loadLibClass('module');

			if ($this->m->checkForModule()) {
				$this->m->load();
			}
		}

	}

	/**
	 * Destructor
	 */
	public function __destruct() {


		$this->wk->mark("end");

		if (isset($this->m)) {
			if (!$this->m->getNoLayout() && $this->cfg->get('debug') && in_array(getIp(), $this->cfg->get('debugIp'))) {
				$dbg = loadLibClass('debugger');
				$dbg->load();
				echo $dbg;
			}

		}
	}
}

?>