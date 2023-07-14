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
 * General modules class.
 * Parent class of all modules.
 * All modules extends this class.
 * Check url, run needed modules, load all needed classes, etc...
 * 12.04.2008
 */

class Module {

	public $name;
	public $module;
	public $app = 'out';

    /** @var beforeRouteEnter beforeRouteEnter */
    public $beforeRouteEnter;

    /** @var WorkTime wk */
    public $wk;

	/** @var db */
	public $db;

	/** @var config  */
	public $cfg;

    /** @var tmpl tpl */
	public $tpl;

    /** @var Debugger dbg */
    public $dbg;

    /** @var Uri uri */
    public $uri;

	public static $moduleId;
	public static $noLayout = false;
	public static $cData;
	public static $lang;
	public static $urlDir;
	public static $pageData;
	public static $country;
	public static $linkIds = array();



	protected $layout = 'main';

	/**
	 * Class constructor
	 */
	public function __construct() {

        $this->beforeRouteEnter = &loadLibClass('beforeRouteEnter');
		$this->wk = &loadLibClass('workTime');
		$this->db = &loadLibClass('db');
		$this->cfg = &loadLibClass('config');
		$this->tpl = &loadLibClass('tmpl');
		$this->dbg = &loadLibClass('debugger');

		loadFunc("site");

		$this->uri = &loadLibClass('uri');

		loadFunc("other");

		if (strtolower(server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest') {
			if (getP('webLang') != '') {
				self :: setLang(getP('webLang'));
			}
			$this->noLayout(true);
		}

		$this->cfg->getSiteData();
		$this->setPData($this->cfg->siteData, 'siteData');
	}

	protected function setLayout($layout)
	{
		$this->layout = $layout;
	}

	protected function getLayout()
	{
		return $this->layout;
	}

	public function checkForModule() {

		if ($this->checkForExistModule($this->uri->segment(1))) {

			if ($Module = &loadAppClass($this->uri->segment(1), $this->app, true, '', false)
					&& method_exists($Module, 'action_' . $this->uri->segment(2, getGP('object')))) {

				$this->assignConstants();

				$Module->{'action_' . $this->uri->segment(2, getGP('object'))}();

				return false;
			}

			return true;
		}

		return true;
	}

	/**
	 * Check fot exist module by type and name
	 *
	 * @param string	module type(menu name)
	 * @param string	module name
	 */
	public function checkForExistModule($module) {

		$dbQuery = "SELECT `id` FROM `ad_modules` " .
					"WHERE name = '" . mres($module) . "' LIMIT 1";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Load public uri
	 * Get first segment as site language
	 * And other segments as site url
	 *
	 */
	private function loadPublicUri() {

		// Creating site dir url
		$this->setUrlDir($this->uri->uriString());
	}

	/**
	 * Module loader
	 */
	public function load() {

		if (getG('cache') !== false) {
			emptyCacheDir(AD_CACHE_FOLDER);
		}

        // Before route enter hook
        $this->beforeRouteEnter->beforeRouteEnter();

		$this->loadPublicUri();

		$this->assignConstants();

		$this->loadDefaultModule();

		$this->loadOtherModules();

		/**
		 * Checking no layout property, no layout used when we call ajax request
		 */
		if($this->getNoLayout()) {
			return;

		} else {

            $this->createJSorCSSFile($this->getJSArray(), AD_JS_SRC_FOLDER, AD_JS_FOLDER, 'js');
            $this->createJSorCSSFile($this->getCSSArray(), AD_CSS_SRC_FOLDER, AD_CSS_FOLDER, 'css');

			self :: $moduleId = 1;

			$tpl = $this->getPData('tpl');

			$this->tpl->setTmplDir($tpl["dir"]);
			$this->tpl->setTmpl($this->getLayout());
			$this->tpl->assign("PAGE_HEAD_TEMPLATE", $tpl["tpl"]["head"] ? $this->tpl->output($tpl["tpl"]["head"], $this->getPData()) : '');
			$this->tpl->assign("PAGE_HEADER_TEMPLATE", $tpl["tpl"]["header"] ? $this->tpl->output($tpl["tpl"]["header"], $this->getPData()) : '');
			$this->tpl->assign("PAGE_BODY_TEMPLATE", $tpl["tpl"]["body"] ? $this->tpl->output($tpl["tpl"]["body"], $this->getPData()) : '');
			$this->tpl->assign("PAGE_FOOTER_TEMPLATE", $tpl["tpl"]["footer"] ? $this->tpl->output($tpl["tpl"]["footer"], $this->getPData()) : '');

			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
			$host = $_SERVER['HTTP_HOST'];
			$uri = $_SERVER['REQUEST_URI'];

			$criticalCss = '/css/critical' . $uri . 'style.css';
			
			$this->tpl->assign("CRITICAL_CSS", $criticalCss);
			$this->tpl->assign($this->getPData());
			$output = $this->tpl->fetch();

			if ($result = $this->loadAllLinks()) {
				$output = str_replace($result['ids'], $result['links'], $output);
			}

			if ($this->getCData('cache')) {
				if (isWritable(AD_CACHE_FOLDER)) {
					if ($file = fopen(AD_CACHE_FOLDER . md5($this->getCData('url')) . '.' . $this->getCData('edit_date') .  '.cache', 'w')) {
				         fwrite($file, $output);
				         fclose($file);
				    }
				}
			}

			if ($this->cfg->get('compress')) {
				echo $this->htmlCompress($output);
			} else {
				echo $output;
			}

		}
	}

	/**
	 * Compress html code
	 *
	 */
	private function htmlCompress($html) {

		preg_match_all('!(<(?:code|pre).*>[^<]+</(?:code|pre)>)!', $html, $pre);
		$html = preg_replace('!<(?:code|pre).*>[^<]+</(?:code|pre)>!', '#pre#', $html);
		$html = preg_replace('#<!–[^\[].+–>#', '', $html);
		$html = preg_replace('/[\r\n\t]+/', ' ', $html);
		$html = preg_replace('/>[\s]+</', '><', $html);
		$html = preg_replace('/[\s]+/', ' ', $html);

		if(!empty($pre[0])) {
			foreach($pre[0] as $tag) {
				$html = preg_replace('!#pre#!', $tag, $html, 1);
			}
		}

		return $html;
	}

	/**
	 * Create JS or CSS file
	 * packer version
	 *
	 */
	private function createJSorCSSFile($fArray, $srcFolder, $folder, $type) {
        if (count($fArray) < 1) {
            return;
        }
        if(!file_exists( AD_SRV_ROOT . $srcFolder )) {
            mkdir(AD_SRV_ROOT . $srcFolder, 0777);
        }
        if(!file_exists( AD_SRV_ROOT . $folder )) {
            mkdir(AD_SRV_ROOT . $folder, 0777);
        }
        $mTime = 0;
        foreach ($fArray AS $file){
            if (file_exists(AD_SRV_ROOT . $srcFolder . $file . "." . $type)) {
                $files[] = $file;
                $fTime = filemtime(AD_SRV_ROOT . $srcFolder . $file . "." . $type);
                if ($fTime > $mTime){
                    $mTime = $fTime;
                }
            }
        }
        if(!$files || count($files) < 1) {
            return;
        }
        $mainFile = glob(AD_SRV_ROOT . $folder . md5(implode(".", $fArray)) . "*." . $type);
        $curTime = 0;
        if (count($mainFile) > 0) {
            $parts = explode(".", basename($mainFile[0]));
            $curTime = $parts[1];
        }
        if (count($mainFile) < 1 || $curTime < $mTime) {
            foreach ($mainFile AS $oldOne) {
                @unlink($oldOne);
            }
            $result = '';
            foreach ($fArray AS $file) {
                if (file_exists(AD_SRV_ROOT . $srcFolder . $file . "." . $type)) {
                    $filename[] = $file;
                    if (!$handle = fopen(AD_SRV_ROOT . $srcFolder . $file . "." . $type, 'r')) {
                        showError('Can not open ' . $type . ' file!');
                    }
                    while (!feof($handle)) {
                        $result .= fread($handle, 8192);
                    }
                    fclose($handle);
                }
                $result .= "\n\n";
            }
            if ($type == 'js') {
                if ($this->cfg->get('compress')) {
                    /** @var JavaScriptPacker $packer */
                    $packer = loadLibClass('JavaScriptPacker', false);
                    $packer = new JavaScriptPacker($result, 'Normal', true, false);
                    $packed = $packer->pack();
                } else {
                    $packed = $result;
                }
            } elseif ($type == 'css') {
                if ($this->cfg->get('compress')) {
                    $packer = loadLibClass('CSSPacker');
                    $packed = $packer->process($result);
                } else {
                    $packed = $result;
                }
            } else {
                return;
            }
            try {
                if (!$handle = @fopen(AD_SRV_ROOT . $folder . md5(implode(".", $filename)) . "." . $mTime . "." . $type, 'w')) {
                    showError('Can not open ' . $type . ' file!');
                }
                if (fwrite($handle, $packed) === FALSE) {
                    showError('Can not write in ' . $type . ' file!');
                }
                fclose($handle);
            } catch (Exception $e) {
                showError('Can not create ' . $type . ' file!');
            }
            $this->setPData(array($type . 'File' => md5(implode(".", $filename)) . '.' . $mTime), 'web');
        } else {
            $this->setPData(array($type . 'File' => md5(implode(".", $files)) . '.' . $curTime), 'web');
        }
        return true;
    }

	/**
	 * Add js file to jsArray
	 *
	 * @param string filename
	 */
	public function addJSFile($name) {

		if ($name) {
			$jsArray = $this->getJSArray();
			$jsArray[] = $name;
			$this->setPData(array('jsArray' => $jsArray), 'web');

			return true;
		}

		return false;
	}

	/**
	 * Get jsArray from global web
	 *
	 */
	private function getJSArray() {

		$jsArray = $this->getPData('web');
		$jsArray = $jsArray['jsArray'];

		return $jsArray;

	}

	/**
	 * Add css file to cssArray
	 *
	 * @param string filename
	 */
	public function addCSSFile($name) {

		if ($name) {
			$cssArray = $this->getCSSArray();
			$cssArray[] = $name;
			$this->setPData(array('cssArray' => $cssArray), 'web');

			return true;
		}

		return false;
	}

	/**
	 * Get cssArray from global web
	 *
	 */
	private function getCSSArray() {

		$cssArray = $this->getPData('web');
		$cssArray = $cssArray['cssArray'];

		return $cssArray;

	}

	/**
	 * Load all links/urls
	 * By linksIds array
	 *
	 */
	private function loadAllLinks() {

		if (count(Module :: $linkIds) > 0) {

			$ids = array();
			$links = array();

			for ($i = 0; $i < count(Module :: $linkIds); $i++) {
				$mirrors[] = getMirror(Module :: $linkIds[$i]);
				$id[getMirror(Module :: $linkIds[$i])] = Module :: $linkIds[$i];
			}

			$dbQuery = "SELECT `id`, `url` FROM `ad_content` WHERE `id` IN (" . implode(",", $mirrors) . ")";
			$query = new query($this->db, $dbQuery);

			while ($query->getrow()) {
				$links[] = AD_WEB_FOLDER . makeUrlWithLangInTheEnd($query->field('url'));
				$ids[] = '{{' . $id[$query->field('id')] . '}}';
			}

			return array('links' => $links, 'ids' => $ids);
		}

		return false;
	}

	/**
	 * Load default module
	 */
	private function loadDefaultModule() {
		$dbQuery = "SELECT `name`, `id` FROM `ad_modules` WHERE `default` = '1' LIMIT 1";
		$query = new query($this->db, $dbQuery);
		$query->getrow();
		$wk = &loadLibClass('workTime');
		self :: $moduleId = $query->field("id");
		$Module = &loadAppClass($query->field("name"), $this->app);

		$Module->run();

	}

	/**
	 * Load other modules
	 */
	private function loadOtherModules() {

		$dbQuery = "SELECT * FROM `ad_modules` m
							LEFT JOIN `ad_modules_on_page` mop ON (m.`id` = mop.`module_id`)
						 	WHERE mop.`page_id` = '" . $this->getCData("id") . "' OR m.`all_pages` = 1
						 	GROUP BY m.id
						 	ORDER BY m.`id` ASC";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			self :: $moduleId = $query->field("id");
			$Module = &loadAppClass($query->field("name"), $this->app);
			$Module->run();
		}
	}

	/**
	 * Load all labels for module
	 *
	 * @param int	module id
	 * $param bool	load all messages or not
	 */
	public function loadLabels($id = '', $all = false) {
		$this->labels = &loadLibClass('labels');

		$this->labels->getLabels($id ? $id : $this->getModuleId(), $this->getCountry(), $this->getLang(), $all);
	}

	/**
	 * Set site language
	 */
	public function setLang($l) {
		self :: $lang = $_SESSION['ad_language'] = $l;
	}

	/**
	 * Set site dir url
	 */
	public function setUrlDir($r) {
		self :: $urlDir = $r;
	}

	/**
	 * Get site country
	 */
	public function getCountry() {

		if (self :: $country) {
			return self :: $country;
		} else {

			return self :: $country = getCountry();
		}

	}

	/**
	 * Set site country
	 *
	 * @param int	country id
	 */
	public function setCountry($c) {
		self :: $country = $c;
	}

	/**
	 * Get site language
	 */
	public function getLang() {
        $lang = self :: $lang;
		return getLang($lang);
	}

	/**
	 * Get site dir url
	 */
	public function getUrlDir() {
		return self :: $urlDir;
	}

	/**
	 * Set content data array
	 *
	 * @param mix	array with content data
	 */
	public function setCData($d) {
		self :: $cData = $d;
	}

	/**
	 * Get content data array or element of array
	 *
	 * @param mix	array element
	 */
	public function getCData($e = '') {
		if ($e) {
			return self :: $cData[$e];
		}
		else {
			return self :: $cData;
		}
	}

	/**
	 * Set page data
	 *
	 * @param mix	array with page data
	 * @param mix	value
	 */
	public function setPData($d, $e) {

		if ($e) {
			if (isset(self :: $pageData[$e]) && is_array(self :: $pageData[$e])) {
				if (is_array(self :: $pageData[$e]) && is_array($d)) {
					self :: $pageData[$e] = array_merge(self :: $pageData[$e], $d);
				}

			}
			else {
				self :: $pageData[$e] = $d;
			}

		}
		else {
			self :: $pageData = array_merge(self :: $pageData, $d);
		}
	}

	/**
	 * Set page data
	 *
	 * @param mix	key
	 * @param mix 	new child value
	 * @param mix	page data key
	 */
	public function addPData($k, $v, $e) {

		if (isset(self :: $pageData[$e][$k])) {
			self :: $pageData[$e][$k][] = $v;
		}
	}

	/**
	 * Get page data array or element of array
	 *
	 * @param mix	array element
	 */
	public function getPData($e = '') {
		if ($e) {
			if (isset(self :: $pageData[$e])) {
				return self :: $pageData[$e];
			}

		}
		else {
			return self :: $pageData;
		}
	}

	/**
	 * Change noLayout value
	 */
	public function noLayout($v) {
		Module::$noLayout = $v;
	}

	/**
	 * Get noLayout value
	 *
	 */
	public function getNoLayout() {
		return Module::$noLayout;
	}

	/**
	 * Return used module id
	 */
	public function getModuleId($reset = false) {

		if ($reset) {
			$dbQuery = "SELECT `id` FROM `ad_modules` WHERE `name` = '" . $this->getModuleName() . "' LIMIT 0,1";
			$query = new query($this->db, $dbQuery);

			return self :: $moduleId = $query->getOne();
		}
		else {
			if (self :: $moduleId) {
				return self :: $moduleId;
			}
			else {
				$dbQuery = "SELECT `id` FROM `ad_modules` WHERE `name` = '" . $this->getModuleName() . "' LIMIT 0,1";
				$query = new query($this->db, $dbQuery);

				return self :: $moduleId = $query->getOne();
			}
		}

	}

	/**
	 * Return used module name
	 */
	public function getModuleName() {
		return $this->name ? $this->name : get_class($this);
	}

	/**
	 * Assign global all constants from config file to template
	 */
	public function assignConstants() {

		$this->tpl->assign('AD_IMAGE_FOLDER', AD_IMAGE_FOLDER);
		$this->tpl->assign('AD_CSS_FOLDER', AD_CSS_FOLDER);
		$this->tpl->assign('AD_CSS_VERSION', AD_CSS_VERSION);
		$this->tpl->assign('AD_JS_FOLDER', AD_JS_FOLDER);
		$this->tpl->assign('AD_WEB_FOLDER', AD_WEB_FOLDER);
		$this->tpl->assign('AD_HTTP_HOST', AD_HTTP_HOST);
		$this->tpl->assign('AD_HTTP_ROOT', AD_HTTP_ROOT);
		$this->tpl->assign('AD_UPLOAD_FOLDER', AD_UPLOAD_FOLDER);
		$this->tpl->assign('AD_MODULE_WEB_FOLDER', AD_MODULE_WEB_FOLDER);

	}

	/**
	 * Redirect to 404 page
	 */
	public function show404Page() {

		$lang = '';
		$langs = getSiteLangsByCountry($this->getCountry());
		for ($i = 0; $i < count($langs); $i++) {
			if (preg_match("/" . $langs[$i]['lang'] . "/", $_SERVER['REDIRECT_URL'])) {
				$lang = $langs[$i]['lang'];
			}
		}

		if (!$lang) {
			$lang = getDefaultLanguage($this->getCountry());
		}

		redirect(getLink(getMirror($this->cfg->get('404'), $this->getCountry(), $lang)));
	}

}
?>