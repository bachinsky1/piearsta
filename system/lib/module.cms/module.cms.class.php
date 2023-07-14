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
 * General cms modules class.
 * Parent class of all admin path modules. 
 * All admin path modules extends this class.
 * 14.04.2008
 */

class Module_cms extends Module {

	public $app = 'in';
	public static $lang;
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		
		$this->cmsConfig = &loadLibClass('config.cms',false,array('first_second'));
		$this->cmsUser = &loadLibClass('user', true, $this->cmsConfig->get('userTable'));
		$this->cmsLog = &loadLibClass('syslog');
		$this->cmsLang = &loadLibClass('language');
		$this->cmsTable = &loadLibClass('cms.table');
		
		
		
		parent :: __construct();
		
		if (isset($_SESSION['ad_' . $this->getModuleName()]["itemsFrom"]) && !getP("itemsRewind")) {
			
			$this->tpl->assign('MODULE_FROM', $_SESSION['ad_' . $this->getModuleName()]["itemsFrom"]);
			
		} 
		
	}
	
	/**
	 * Load module messages and assign all cms message plus constants
	 */
	public function messageLoader($module = '') {

		if ($module) {
			$this->cmsLang->loadModuleMessages($module);
		}
		
		$this->assignLanguageTexts();
	}
	
	/**
	 * Module loader
	 */
	public function load() {
		
		if (getG("logout") !== false) {
			$this->cmsUser->logout();
		}
		
		if ($this->cmsUser->isLogin()) {
			
			$this->assignConstants();
			/**
			 * If isset $cLang - change cms interface language
			 */
			$cLang = getP("cmsLang");

			if (isset($cLang) && !empty($cLang)) {
				setcookie("cmsLang", $cLang, time() + 60 * 60 * 24 * 30, AD_CMS_WEB_FOLDER);
				$this->cmsConfig->set('cmsLang',$cLang);
			}

			// Loading cms language messages
			$this->cmsLang->loadCmsMessages();

			/**
			 * generate main menu
			 */
			$this->getMainMenu();
			
			switch ($this->uri->segment(1)) {
				case "content":
					$this->messageLoader($this->uri->segment(1));
					$this->tpl->assign('MODULE_NAME', $this->uri->segment(1));
					$Module = &loadAppClass("content", $this->app);	
					break;
				case "modules":
					
					if ($this->uri->segment(2) == 'ckeditor') {
						$this->messageLoader();
						
						$this->tpl->setTmplDir(AD_SERVER_CKEDITOR_FOLDER . 'tmpl/');
						$this->tpl->assign("MAIN_URL", "/" . $this->uri->segment(0) . "/" . $this->uri->segment(1) . "/");
						$this->tpl->assign('MODULE_NAME', $this->uri->segment(2));	
						
						// Assign fields names
						$this->tpl->assign("idField", getP('idField'));	
						$this->tpl->assign("mode", getP('mode', false, 'advanced'));
						
						$this->noLayout(true);
						jsonSend($this->tpl->output($this->uri->segment(2)));
						
					} else {
						$this->messageLoader($this->uri->segment(2));
						if ($this->uri->segment(2)) {			
							$this->tpl->assign('MODULE_NAME', $this->uri->segment(2));
							if ($this->checkForExistModule("modules", $this->uri->segment(2))) {
								$Module = &loadAppClass($this->uri->segment(2), $this->app);
							}
							else {
								showError("Have not installed this module: " . $this->uri->segment(2), 500);
							}
						} else {
							if ($this->cmsUser->isAdmin()) {
								$this->redirectToFirstChild("modules");
							}
						}
					}
					
					break;
				case "config":
					$this->messageLoader($this->uri->segment(2));
					if ($this->uri->segment(2)) {		
						$this->tpl->assign('MODULE_NAME', $this->uri->segment(2));
						
						if ($this->checkForExistModule("config", $this->uri->segment(2))) {
							$Module = &loadAppClass($this->uri->segment(2), $this->app);
						}
						else {
							showError("Have not installed this module: " . $this->uri->segment(2), 500);
						}
					} 
					else {
						if ($this->cmsUser->isAdmin()) {
							$this->redirectToFirstChild("config");
						}
					}
					
					break;
				case "tools":
					if (!$this->cmsUser->isAdmin()) {
						redirect(AD_CMS_WEB_FOLDER);
					}
					else {
						$this->messageLoader($this->uri->segment(2));
						if ($this->uri->segment(2)) {		
							$this->tpl->assign('MODULE_NAME', $this->uri->segment(2));
							
							if ($this->checkForExistModule("tools", $this->uri->segment(2))) {
								$Module = &loadAppClass($this->uri->segment(2), $this->app);
							}
							else {
								showError("Have not installed this module: " . $this->uri->segment(2), 500);
							}
						} else {
							if ($this->cmsUser->isAdmin()) {
								$this->redirectToFirstChild("tools");
							}
						}
						
					}
					break;
				case "":
					//$this->messageLoader();	
				break;
				default:
					showError("CMS Url is incorrect!", 500);		

			}
			
			if (isset($Module)) {
				$Module->run();
			}

			/**
			 * Checking no layout property, no layout used when we call ajax request
			 */
			if($this->getNoLayout()) {
				return;
				
			}
			else {

				$this->tpl->setTmplDir(AD_CMS_FOLDER . "tmpl/");
				$this->tpl->setTmpl("main.html");
				$this->tpl->assign("language_menu", dropDownFieldOptionsLang($this->cmsConfig->get('cmsAllLangs'), $this->cmsConfig->getCmsLang()));
				
				$output = $this->tpl->fetch();
				switch ($this->uri->segment(1)) {
					case "modules":			
						$this->tpl->assign("modules_Menu", $this->tpl->output("modulesMenu", $this->getModulesMenu("modules")));											
						break;
					case "config":
						$this->tpl->assign("modules_Menu", $this->tpl->output("modulesMenu", $this->getModulesMenu("config")));		
						break;
					case "tools":
						if (!$this->cmsUser->isAdmin()) {
							redirect(AD_CMS_WEB_FOLDER);
						}
						$this->tpl->assign("modules_Menu", $this->tpl->output("modulesMenu", $this->getModulesMenu("tools")));
						break;
					case "":		
						break;
	
				}



				$output = $this->tpl->fetch();
				
				echo $output;
			}
				
		}
		else {
			
			// Getting page action from post data
			$action = getP("action");

			switch ($action) {
				case "login" :
					
					$userName = getP("username");
					$userPassword = getP("password");
					
					$access = $this->cmsUser->login($userName, $userPassword);
					$this->cmsLog->username = $userName;
					if (!$access) {
						$this->cmsLog->writelog("users", "Access Denied");
					}
					else {
						$this->cmsLog->writelog("users", "Successful Access");
						redirect(server("REQUEST_URI"));				
					}
					
					break;
				case "language" :
					$newCmsLang = getP("language");
					setcookie("cmsLang", $newCmsLang);
					$this->cmsConfig->set('cmsLang',$newCmsLang);	
					break;
			}	
			
			// Loading cms language messages
			$this->cmsLang->loadCmsMessages();
			
			$this->tpl->setTmplDir(AD_CMS_FOLDER . "tmpl/");
			$this->tpl->setTmpl("login.html");
			$this->tpl->assign("language_menu", dropDownFieldOptionsLang($this->cmsConfig->get('cmsAllLangs'), $this->cmsConfig->getCmsLang()));
			$this->tpl->assign("url", server("REDIRECT_URL"));

			if (isset($access) && $access === false) {
				$this->tpl->assign("denied", true);
			}
				
			$this->assignLanguageTexts();
			$this->assignConstants();
			
			$output = $this->tpl->fetch();
			
			echo $output;
		}
	}
	
	/**
	 * Check fot exist module by type and name
	 * 
	 * @param string	module type(menu name)
	 */
	private function redirectToFirstChild($type) {
		
		$dbQuery = "SELECT `name` FROM `ad_modules` WHERE `menuname` = '" . $type . "' ORDER BY id LIMIT 1";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			redirect($query->getOne() . "/");
		}
	}
	
	/**
	 * Create modules navigations
	 * 
	 * @param bool	add button
	 * @param bool	bulks selection
	 * @param bool	paginator
	 */
	public function createModuleTableNav($add = true, $bulk = true, $pager = true, $copy = false) {
		
		$data = array("add" => $add, "bulk" => $bulk, "pager" => $pager, "copy" => $copy);

		$tmplFile = AD_CMS_FOLDER . 'tmpl/helpers/moduleTable.html';
		if (!file_exists($tmplFile)) {
			showError('File ' . $tmplFile . ' does not exist!', 500);
		}
		$data["type"] = "Top";
		$r["tTable"] = $this->tpl->output($tmplFile, $data, false, false);
		$data["type"] = "Bottom";
		$r["bTable"] = $this->tpl->output($tmplFile, $data, false, false);
		
		return $r;
	}
	
	/**
	 * Check fot exist module by type and name
	 * 
	 * @param string	module type(menu name)
	 * @param string	module name
	 */
	public function checkForExistModule($type, $module) {
		
		$dbQuery = "
			SELECT `id` FROM `ad_modules` " .
			"WHERE 
				(`menuname` = '" . $type . "' OR `menuname` = '') 
				AND name = '" . $module . "' LIMIT 1
		";
		$query = new query($this->db, $dbQuery);

		if ($query->num_rows() > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Draw modules menu in cms
	 */
	private function getModulesMenu($type) {
		
		$dbQuery = "
			SELECT * FROM `ad_modules` m
			WHERE m.menuname = '" . $type . "' 
			ORDER BY m.id
		";

		$query = new query($this->db, $dbQuery);
		$i = 0;
		$mArray = array();
		$cnt = $query->num_rows();

		while ($query->getrow()) {
			
			if ($this->cmsUser->haveUserRole("VIEW", $query->field('id'))) {
				$translations = unserialize($query->field('translations'));
				$translation_text = isset($translations[$this->cmsConfig->getCmsLang()]) ? $translations[$this->cmsConfig->getCmsLang()] : '';

				$mArray["data"][$i]["name"] = $translation_text ? $translation_text : $query->field('name');
				$mArray["data"][$i]["link"] = AD_CMS_WEB_FOLDER . $this->uri->segment(1) . "/" . $query->field("name");
				$mArray["data"][$i]["class"] = $this->uri->segment(2) == $query->field("name") ? "active" : false;		
				
				if ($cnt == ($i + 1)) {
					if ($mArray["data"][$i]["class"]) {
						$mArray["data"][$i]["class"] .= " last";
					}
					else {
						$mArray["data"][$i]["class"] = "last";
					}
				}
				
				$i++;
			}
				
		}
		
		$query->free();
		return $mArray;
	}
	
	/**
	 * Assign all modules messages in template
	 */
	public function assignLanguageTexts() {
		$langWords = $this->cmsLang->langWords["words"];
	}
	
	/**
	 * Assign global all constants from config file to template
	 */
	public function assignConstants() {
		
		$this->tpl->assign('AD_IMAGE_FOLDER', AD_IMAGE_FOLDER);
		$this->tpl->assign('AD_CSS_FOLDER', AD_CSS_SRC_FOLDER);
		$this->tpl->assign('AD_CSS_VERSION', AD_CSS_VERSION);
		$this->tpl->assign('AD_JS_FOLDER', AD_JS_SRC_FOLDER);
		$this->tpl->assign('AD_MODULE_WEB_FOLDER', AD_MODULE_WEB_FOLDER);
		$this->tpl->assign('AD_UPLOAD_FOLDER', AD_UPLOAD_FOLDER);
		
		$this->tpl->assign('AD_CKEDITOR_FOLDER', AD_CKEDITOR_FOLDER);
		$this->tpl->assign('AD_CMS_IMAGE_FOLDER', AD_CMS_IMAGE_FOLDER);
		$this->tpl->assign('AD_CMS_CSS_FOLDER', AD_CMS_CSS_FOLDER);
		$this->tpl->assign('AD_CMS_JS_FOLDER', AD_CMS_JS_FOLDER);
		$this->tpl->assign('AD_CMS_WEB_FOLDER', AD_CMS_WEB_FOLDER);
		$this->tpl->assign('AD_CMS_MODULE_WEB_FOLDER', AD_CMS_MODULE_WEB_FOLDER);
		$this->tpl->assign('AD_CMS_LANGUAGE', $this->cmsConfig->getCmsLang());
	
		$this->tpl->assign('AD_WEB_FOLDER', AD_WEB_FOLDER);
		$this->tpl->assign('AD_HTTP_HOST', AD_HTTP_HOST);
		$this->tpl->assign('AD_HTTP_ROOT', AD_HTTP_ROOT);

	}
	
	/**
	 * creat main menu array and assign to template
	 */
	public function getMainMenu() {
		
		$content = false;
		$i = 0;
		$roles = array('VIEW', 'ADD', 'EDIT', 'DELETE');
		foreach ($roles as $role) {
			if ($this->cmsUser->haveUserRole($role, 1)) {
				$content = true;
				break;
			}
		}

		if ($content === true) {
			$mMenu[$i]["name"] = gLA("menu_content",'Content','',true);
			$mMenu[$i]["link"] = AD_CMS_WEB_FOLDER . "content/";
			$mMenu[$i]["active"] = $this->uri->segment(1) == "content" ? true : false;

			$i++;
		}

		$mMenu[$i]["name"] = gLA("menu_modules",'Modules','',true);
		$mMenu[$i]["link"] = AD_CMS_WEB_FOLDER . "modules/";
		$mMenu[$i]["active"] = $this->uri->segment(1) == "modules" ? true : false;
		$i++;

		if ($this->cmsUser->isAdmin()) {
			$mMenu[$i]["name"] = gLA("menu_config",'Site setup','',true);
			$mMenu[$i]["link"] = AD_CMS_WEB_FOLDER . "config/";
			$mMenu[$i]["active"] = $this->uri->segment(1) == "config" ? true : false;
			$i++;
			$mMenu[$i]["name"] = gLA("menu_admin",'Admin tools','',true);
			$mMenu[$i]["link"] = AD_CMS_WEB_FOLDER . "tools/";
			$mMenu[$i]["active"] = $this->uri->segment(1) == "tools" ? true : false;
		}

		$this->tpl->assign("mainMenu", $mMenu);
	}

	
	/**
	 * Including template for used module
	 * 
	 * $param mix 		 data	
	 * @param string	 template name, if empty use module name as template name
	 */
	public function includeTemplate($data = NULL, $tmplFile = "") {
		
		if (!is_object($this->tpl)) {
			return false;
		}
		
		clearstatcache();
		$tmplFile = $tmplFile != "" ? $tmplFile : $this->getModuleName();		
		if (!file_exists(AD_APP_FOLDER . $this->app . '/' . $this->getModuleName() . '/tmpl/'. $tmplFile . '.html')) {
			showError('File ' . AD_APP_FOLDER . $this->app . '/' . $this->getModuleName() . '/tmpl/'. $tmplFile . '.html does not exist!', 500);
		}
		
		if ($this->cmsUser->haveUserRole('VIEW', $this->getModuleId())) {
			$this->tpl->assign('ADMIN_MODULE_BLOCK', $this->tpl->output($tmplFile, $data));
		} else {
			$this->tpl->setTmplDir(AD_CMS_FOLDER . 'tmpl/');
			$this->tpl->assign('ADMIN_MODULE_BLOCK', $this->tpl->output('denied'));
		}
		
	}
	
	/**
	 * Return used module title
	 */
	public function getModuleTitle() {
		
		$dbQuery = "SELECT `translations`, `name` FROM `ad_modules` m WHERE m.name = '" . $this->getModuleName() . "' LIMIT 0,1";
		$query = new query($this->db, $dbQuery);
		$query->getrow();
		
		$translations = unserialize($query->field('translations'));
		
		return isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('name');
	}
	
	/**
	 * Getting total cms table records count from db
	 * 
	 * @param string 	table name in db
	 * @param string 	row name in db, default value = id
	 * @param string 	sql string with exceptions
	 */
	public function getTotalRecordsCount($table, $field = "id", $exSqlParams = "") {
		
		if ($table === false) {
			$query = new query($this->db, "SELECT FOUND_ROWS()");
			return $query->getOne();
		}
		
		$dbQuery = "SELECT COUNT(" . $field . ") AS rcount FROM " . $table . $exSqlParams;
		$query = new query($this->db, $dbQuery);
		return $query->getOne();				
	}
	
	/**
	 * Return html tag with edit element link
	 * 
	 * @param int	 element id
	 */
	public function moduleEditLink($id) {
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
			return '<a href="javascript:;" onclick="moduleEdit(\'' . $id . '\'); return false;">' . gLA("m_edit",'Edit') . '</a>';
		}
		else {
			return '';
		}
	}
	
	/**
	 * Return html tag with delete element link
	 * 
	 * @param int	 element id
	 */
	public function moduleDeleteLink($id) {
		if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
			return '<a href="javascript:;" onclick="moduleDelete(\'' . $id . '\'); return false;">' . gLA("m_delete",'Delete') . '</a>';
		} else {
			return '';
		}
	}
	
	/**
	 * Return html tag with sort elements links
	 * 
	 * @param int	 element id
	 */
	public function moduleSortLinks($id){
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
			return '<a href="javascript:;" onclick="moduleSort(\'' . $id . '\', \'up\'); return false;">' .
						'<img src="' . AD_CMS_IMAGE_FOLDER . 'design/pointer_4t.png" alt="' . gLA("sort_up",'Move Up') . '" /></a>' .
					'<a href="javascript:;" onclick="moduleSort(\'' . $id . '\', \'down\'); return false;">' .
						'<img src="' . AD_CMS_IMAGE_FOLDER . 'design/pointer_4b.png" alt="' . gLA("sort_down",'Move down') . '" /></a>';
		}
		else {
			return '';
		}
	}
	
	/**
	 * Return html tag with default raidobox element
	 * 
	 * @param int	 	element id
	 * @param int	 	default value
	 * @param string	module name	
	 */
	public function moduleDefaultBox($id, $default, $module) {
		
		if ($default == "1") {
			$checked = " checked";
		}
		else {
			$checked = "";
		}
		
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
			return '<input onclick="javascript:moduleDefault(\'' . $id . '\')" type="radio" name="' . $module . '" value="' . $id . '"' . $checked . ' />';
		} 
		else {
			return '<input readonly="readonly" type="radio" name="' . $module . '" value="' . $id . '"' . $checked . ' />';
		}
	}
	
	/**
	 * Return html tag with checkbox element
	 * 
	 * @param int	 	element id
	 * @param string	module name
	 */
	public function moduleCheckboxLink($id, $module) {
			return '<input class="radio" type="checkbox" id="' . $module . '_' . $id . '" name="' . $module . 'Box" value="' . $id . '" />';
	}
	
	/**
	 * Create sql exception params for cms table.
	 * Example: limit or order.
	 * 
	 * @param string
	 * @param string
	 */
	public function moduleTableSqlParms($sortField = '', $sortOrder = 'ASC') {
	
		if (isset($_SESSION['ad_' . $this->getModuleName()]["itemsFrom"]) && !getP("itemsRewind")) {
			$_POST["itemsFrom"] = $_SESSION['ad_' . $this->getModuleName()]["itemsFrom"];
			
			$this->tpl->assign('MODULE_FROM', $_POST["itemsFrom"]);
			
		} elseif (getP("itemsFrom") !== false && getP("itemsRewind") !== false) {
			$_SESSION['ad_' . $this->getModuleName()]["itemsFrom"] = getP("itemsFrom");
		}		
		
		$msgFrom = getP("itemsFrom") ? getP("itemsFrom") : "0";
		$msgShow = getP("itemsShow", false, "25");
		$sortField = getP("sortField") == "" ? $sortField : getP("sortField");
		$sortOrder = getP("sortOrder") == "" ? $sortOrder : getP("sortOrder");
	
		$sqlLimit = " LIMIT " . $msgFrom . "," . $msgShow;
		$sqlOrder = $sortField ? " ORDER BY " . $sortField . " " . $sortOrder : "";
		$returnSql = $sqlOrder . $sqlLimit;

		return $returnSql;
	}
	
	/**
	 * Create sql exception params for cms table.
	 * Example: limit or order.
	 * 
	 * @param array
	 */
	public function moduleTableSqlParmsArray($sort = array()) {
        
		$msgFrom = getP('itemsFrom') ? getP('itemsFrom') : 0;
		$msgShow = getP('itemsShow', false, 25);
        
        if(!getP('sortField') || !getP('sortOrder')) {
            $sort_sql = '';
            
            if(is_array($sort) && sizeof($sort)) {
                foreach ($sort as $field=>$order) {
                    $sort_sql .= $field . ' ' . $order . ', ';
                }
                
                $sort_sql = substr($sort_sql, 0, -2);
            }
        } else {
            $sort_sql = getP('sortField') . ' ' . getP('sortOrder');
        }
	
		$sqlLimit = ' LIMIT ' . $msgFrom . ',' . $msgShow;
		$sqlOrder = $sort_sql ? ' ORDER BY ' . $sort_sql : '';
		$returnSql = $sqlOrder . $sqlLimit;

		return $returnSql;
	}
	
	/**
	 * Return html tag with actions buttons element link
	 * 
	 * @param int	 element id
	 */
	public function moduleActionsLink($id, $delete = true) {
		$r = '';
		
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
			$r .= '<a class="edit" href="javascript:;" onclick="moduleEdit(\'' . $id . '\'); return false;">' . gLA('m_edit','Edit') . '</a>';
		}
		else {
			$r .= '';
		}
		
		if ($delete) {
			if ($this->cmsUser->haveUserRole("DELETE", $this->getModuleId())) {
				$r .= '<a href="javascript:;" onclick="moduleDelete(\'' . $id . '\'); return false;">' . gLA('m_delete','Delete') . '</a>';
			} else {
				$r .= '';
			}
		}
		
		
		
		return $r;
	}
	
	/**
	 * Return html stag with enable or disable element link
	 * 
	 * @param int	 element id
	 * @param bool 	 enable element value
	 */
	public function moduleEnableLink($id, $enabled, $default = false) {
		
		if ($enabled) {
			$checked = ' checked="checked"';
			$value = "0";
		} else {
			$checked = '';
			$value = "1";
		}
		
		if ($default) 
			$disabled = ' disabled = "disabled"';
		else
			$disabled = '';
		
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
			return '<input onclick="moduleEnable(\'' . $id . '\', \'' . $value . '\'); return false;" type="checkbox" name="e_' . $id . '" value="1" class="radio"' . $checked . $disabled . ' />';
		} else {
			return '<input onclick="return false;" type="checkbox" name="e_' . $id . '" value="1" class="radio"' . $checked . ' />';
		}
		
		
	}
	
	public function moduleJsLink($id, $enabled, $default = false) {
		
		if ($enabled) {
			$checked = ' checked="checked"';
			$value = "0";
		} else {
			$checked = '';
			$value = "1";
		}
		
		if ($default) 
			$disabled = ' disabled = "disabled"';
		else
			$disabled = '';
		
		if ($this->cmsUser->haveUserRole("EDIT", $this->getModuleId())) {
			return '<input onclick="moduleJs(\'' . $id . '\', \'' . $value . '\'); return false;" type="checkbox" name="e_' . $id . '" value="1" class="radio"' . $checked . $disabled . ' />';
		} else {
			return '<input onclick="return false;" type="checkbox" name="e_' . $id . '" value="1" class="radio"' . $checked . ' />';
		}
		
		
	}
	
	/**
	 * Getting content title by id
	 * 
	 * @param int	content id
	 */
	public function getContentTitle($id) {
		
		$dbQuery = "SELECT `title` FROM `ad_content` WHERE `id` = '" . $id . "' LIMIT 0,1";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			return $query->getOne();
		}
	}
	
	public function clearOtherFiltersData($thisFilter = ''){
		if(isset($_SESSION['filters']) && is_array($_SESSION['filters'])){
			foreach($_SESSION['filters'] as $key=>$values){
				if($key != $thisFilter){
					unset($_SESSION['filters'][$key]);
				}
			}
		}
	}
        
	/**
	 * Return html tag with actions buttons element link
	 * 
	 * @param int	 element id
	 */
	public function sheduleActionsLink($id) {
		$r = '';

		$r .= '<a class="edit" href="javascript:;" onclick="sheduleBook(\'' . $id . '\'); return false;">' . gLA('m_book','Book this time') . '</a>';
		
		return $r;
	}        
	
}

?>