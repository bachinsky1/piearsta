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
 * CMS content module general admin class
 * Admin path. Edit/Add/Delete and other actions with content
 * This is general module of cms
 * 03.06.2008
 */

class contentData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $curr_url;
	public $result;
	public $uploadFolder = 'content/';
	public $changefreq = array(
				"always" => "always",
				"hourly" => "hourly",
				"daily" => "daily",
				"weekly" => "weekly",
				"monthly" => "monthly",
				"yearly" => "yearly",
				"never" => "never",
		);
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "content";
		$this->dbTable = 'ad_content';
		$this->dbTable_menus_on_page = 'ad_menus_on_page';
		$this->dbTable_modules_on_page = 'ad_modules_on_page';
		$this->dbTable_modules = 'ad_modules';
		$this->dbTable_menus = 'ad_menus';
		$this->dbTable_templates = 'ad_templates';
		
	}
	
	/**
	 * Get all content data from db and create module list
	 * 
	 * @param int	content parent id
	 */
	public function showList($parentId = 0) {
		header("Content-type:text/html");
		$returnHtml = "";
		
		/**
		 * Getting filter entries
		 * And creating sql where
		 */
		$fCountry = getP("filterCountry") ? getP("filterCountry") : getDefaultCountry();
		$fLang = getP("filterLang") ? getP("filterLang") : getDefaultLanguage($fCountry);
		$fSearch = getP("search");
		
		$sqlWhere = $fLang ? " AND `lang` = '" . $fLang . "'" : "";
		$sqlWhere .= $fCountry ? " AND `country` = '" . $fCountry . "'" : "";
		$sqlWhere .= $fSearch ? " AND (`url` LIKE '%" . $fSearch . "%' OR `description` LIKE '%" . $fSearch . "%' OR `page_title` LIKE '%" . $fSearch . "%' OR `title` LIKE '%" . $fSearch . "%' OR `content` LIKE '%" . $fSearch . "%' OR `keywords` LIKE '%" . $fSearch . "%')" : "";
		
		/**
		 * Getting all information from DB about this module
		 */
		$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE ";
		$dbQuery .= "`parent_id` = '" . $parentId . "'";
		$dbQuery .= $sqlWhere ;
		$dbQuery .= "ORDER BY `sort`, `title` ASC";
		$query = new query($this->db, $dbQuery);

		if ($query->num_rows() > 0) {
			
			$returnHtml .= ($parentId == 0 ? '<ul id="contentTree">' : '<ul>');
			
			while ($query->getrow()) {
				
				if (!$query->field('active')) {
					$class = ' class="red"';
				}
				elseif (!$query->field('enable')) {
					$class = ' class="gray"';
				}
				else {
					$class = '';
				} 
					
				$returnHtml .= '<li class="jstree-drop" id="node' . $query->field('id') . '">';			
				
				$returnHtml .= '<a href="#id:' . $query->field('id') . '/"' . $class . '>' . $query->field('title') . '</a>';
				
				$returnHtml .= $this->showList($query->field('id'));
				$returnHtml .= '</li>';
				
			}
			
			$returnHtml .= '</ul>';
		}
		
		
		return $returnHtml;
	}
	
	/**
	 * Visible or unvisible content
	 * 
	 * @param int	content id
	 * @param bool	visible/unvisible value
	 */
	public function enable($id, $value) {
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `enable` = '" . $value . "' WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}	
	}
	
	/**
	 * Enable or disable content
	 * 
	 * @param int	content id
	 * @param bool	enable/disable value
	 */
	public function active($id, $value) {
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `active` = '" . $value . "' WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}	
	}
	
	/**
	 * Delete content from DB
	 * 
	 * @param int	content id
	 */
	public function delete($id) {
		
		if (!empty($id)) {
			$ids = $this->getChilds($id);
			$ids[] = $id;

			deleteFromDbById($this->dbTable, $ids);
			deleteFromDbById($this->dbTable_menus_on_page, $ids, "page_id");
			deleteFromDbById($this->dbTable_modules_on_page, $ids, "page_id");
		}	
	}
	
	/**
	 * Changing content sort order
	 * 
	 * @param int		content id
	 * @param string	sort changing value
	 */
	public function changeSort($id, $value) {
		
		$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "'";
		$query = new query($this->db, $dbQuery);
		$content = $query->getrow();
		
		if ($value == "down") {
			$sqlParm = ">";
			$sqlParm2 = "ASC";	
		}
		else {
			$sqlParm = "<";
			$sqlParm2 = "DESC";
		}
		
		$dbQuery = "SELECT `id`, `sort` FROM `" . $this->dbTable . "` WHERE `sort` " . $sqlParm . " '" . $content['sort'] . "' AND `lang` = '" . $content['lang'] . "' AND `country` = '" . $content['country'] . "' AND `parent_id` = '" . $content['parent_id'] . "' ORDER BY `sort` " . $sqlParm2 . " LIMIT 0,1";
		$query->query($this->db, $dbQuery);
		
		if ($query->num_rows() > 0) {
			$info = $query->getrow();
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . $content['sort'] . "' WHERE `id` = '" . $info['id'] . "'";
			$query->query($this->db, $dbQuery);
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . $info['sort'] . "' WHERE `id` = '" . $id . "'";
			$query->query($this->db, $dbQuery);
			
		}
	}
	
	/**
	 * Get all childs
	 * 
	 * @param int	content parent id
	 */
	public function getChilds($parentId) {
		$cArray = array();

		$dbQuery = "SELECT `id`, `title`, `parent_id` FROM `" . $this->dbTable . "` WHERE `parent_id` = '" . $parentId . "'";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {			
			while ($query->getrow()) {

				$cArray[] = $query->field('id');

				$cArray = array_merge($cArray, $this->getChilds($query->field('id')));		
			}
		}
		
		return $cArray;
	}
	
	/**
	 * Get all parents names
	 * 
	 * @param int	content id
	 */
	public function getParents($id) {

		$dbQuery = "SELECT `url` FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "' LIMIT 1";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {			
				
			return $query->getOne();							
		}
		
		return '';
	}
	
	/**
	 * Edit or add content in DB
	 * 
	 * @param int	content id
	 */
	public function edit($id = '') {
		if (isset($id) && $id != "") {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "' LIMIT 0,1";
			$query = new query($this->db, $dbQuery);
			$data["edit"] = $query->getrow();
			
			if ($data["edit"]["url"][strlen($data["edit"]["url"]) - 1] == "/") {
				$data["edit"]["url"] = substr($data["edit"]["url"], 0, -1);
			}
			
			if (strpos($data["edit"]["url"], "/")) {
				$data["edit"]["url"] = substr(strrchr($data["edit"]["url"], "/"), 1);
			}
			
			if ($data["edit"]["parent_id"] != '0')  {			
				$data["edit"]["parent_idTitle"] = $this->getContentTitle($data["edit"]["parent_id"]);
			} else {
				$data["edit"]["parent_id"] = '';
			}
			
			$data["edit"]["changefreq"] = dropDownFieldOptions($this->changefreq, $query->field('changefreq'));
			
			if ($data["edit"]["mirror_id"] != '0')  {			
				$data["edit"]["mirror_idTitle"] = $this->getContentTitle($data["edit"]["mirror_id"]);
			} else {
				$data["edit"]["mirror_id"] = '';
			}

			if (is_numeric($data["edit"]["target"])) {
				$data["edit"]["targetTitle"] = $this->getContentTitle($data["edit"]["target"]);
				$data["edit"]["targetReadOnly"] = true;
			} else {
				$data["edit"]["targetTitle"] = $data["edit"]["target"];			
			}
			
			$data["edit"]["createdInfo"] = date("d-m-Y H:i", $data["edit"]["created_date"]) . " " . gLA('by','By') . " " . $data["edit"]["created_user"];
			$data["edit"]["editedInfo"] = date("d-m-Y H:i", $data["edit"]["edit_date"]) . " " . gLA('by','By') . " " . $data["edit"]["edit_user"];
			
			$data["edit"]["countries"]["data"] = getSiteCountries();
			$data["edit"]["countries"]["sel"] = $data["edit"]["country"];
			
			$data["edit"]["languages"]["data"] = getSiteLangsByCountry($data["edit"]["countries"]["sel"]);
			$data["edit"]["languages"]["sel"] = $data["edit"]["lang"];
			
			/**
			 * Creating template list dropdown
			 */
			$list = $this->getTemplateList();
			$data["edit"]["templateList"] = dropDownFieldOptions($list, $data["edit"]["template"], true);
			
			
		}
		else {
			
			if (getP("parentId"))  {			
				$data["edit"]["parent_idTitle"] = $this->getContentTitle(getP("parentId"));
				$data["edit"]["parent_id"] = getP("parentId");
			}
			
			$data["edit"]["changefreq"] = dropDownFieldOptions($this->changefreq);
			
			$data["edit"]["enable"] = 1;
			$data["edit"]["active"] = 1;
		
			$data["edit"]["countries"]["data"] = getSiteCountries();
			$data["edit"]["countries"]["sel"] = getP("country");
			
			$data["edit"]["languages"]["data"] = getSiteLangsByCountry($data["edit"]["countries"]["sel"]);
			$data["edit"]["languages"]["sel"] = getP("language");
			
			/**
			 * Creating template list dropdown
			 */
			$list = $this->getTemplateList();
			$data["edit"]["templateList"] = dropDownFieldOptions($list, '', true);
			
		}
		
		$data["edit"]["modules"] = $this->getAllPublicModules($id);
		$data["edit"]["menuList"] = $this->getMenuList($id);
		
		$data['edit']['uploadFolder'] = $this->uploadFolder;
		
		$r["html"] = $this->tpl->output("edit", $data);
		$r["id"] = $id;

		return jsonSend($r);	
	}
	
	/**
	 * Getting all public modules
	 * 
	 * @param int	content id, it's need if we are editing content
	 */
	private function getAllPublicModules($id = "") {
		
		$mIds = array();
		$mArray = array();
		
		if($id) {
			
			$dbQuery = "SELECT * FROM `" . $this->dbTable_modules_on_page . "` WHERE `page_id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
			while ($query->getrow()) {
				$mIds[$query->field('module_id')] = true;
			}
		}

		$dbQuery = "
			SELECT * 
			FROM `" . $this->dbTable_modules . "` m
			WHERE 1
				AND m.public = '1' 
				AND m.enable = '1'
				AND m.all_pages = '0' 
				AND m.default <> '1'
		";	
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			
			$translations = unserialize($query->field('translations'));
			
			$mArray[] = array(
							"id" => $query->field('id'),
							"name" => $query->field('name'),
							"title" => isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('name'),
							"sel" => (isset($mIds[$query->field('id')]) ? true : false)
							);
		}
		
		return $mArray;
	}
	
	/**
	 * Getting menu list
	 * 
	 */
	private function getMenuList($id = "") {
		
		$mIds = array();
		$result = array();
		
		if($id) {
			
			$dbQuery = "SELECT * FROM `" . $this->dbTable_menus_on_page . "` WHERE `page_id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
			while ($query->getrow()) {
				$mIds[$query->field('menu_id')] = true;
			}
		}
			
		$dbQuery = "SELECT m.id AS id, m.name AS name FROM `" . $this->dbTable_menus  . "` m 
						WHERE 
							m.enable = '1' 
						ORDER BY m.name ASC";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			$result[] = array("id" => $query->field('id'),
								"name" => $query->field('name'),
								"sel" => (isset($mIds[$query->field('id')]) ? true : false));
		}
		
		return $result;	
	}
	
	/**
	 * Getting template list
	 */
	private function getTemplateList() {
		
		$result = Array();

		$dbQuery = "SELECT * FROM `" . $this->dbTable_templates . "` t ORDER BY t.id ASC";
		$query = new query($this->db, $dbQuery);
		while ($query->getrow()) {
			
			$translations = unserialize($query->field('translations'));
			$result[$query->field('id')] = isset($translations[$this->cmsConfig->getCmsLang()]) && $translations[$this->cmsConfig->getCmsLang()] ? $translations[$this->cmsConfig->getCmsLang()] : $query->field('filename');
		}
		
		return $result;
	}
	
	/**
	 * Saving content information in DB
	 * 
	 * @param int		content id, it's need if we are editing content
	 * @param array 	content values
	 * @param array		modules on page
	 */
	public function save($id, $value, $modules) {
                
		$value["url"] = convertUrl($value["url"]) . "/";
		
        if($value['template'] == ''){
            unset($value['template']);
        }
        
		$parent_id = isset($value['parent_id']) ? trim($value['parent_id']) : false;
		
		$valid_parent = $this->valid_parent($id, $parent_id, $value);
		
		if (!$valid_parent) {
			
			return false;
		}
			
		if ($parent_id == '' && $id == '') {
                $dbQuery = "SELECT `id`, `parent_id` FROM " .$this->dbTable. " WHERE lang='".$value['lang']."' AND country='".$value['country']."' AND parent_id = '0' LIMIT 1";
                $query= new query($this->db,$dbQuery);
                if ($query->num_rows()>0) return false;
                        
                }
		$safe = 0;
		foreach ($this->changefreq as $key)
			if ($key == $value["changefreq"])
				$safe = 1;
		
		if (!$safe)
			$value["changefreq"] = "always";

		$value["edit_user"] = $this->cmsUser->getUserName();
		$value["edit_date"] = time();

		if ($value["mirror_id"]) {
			$value["mirror_id"] = $this->getMirrorId($value["mirror_id"]);
		}
		
		if ($value["target"] && is_numeric($value["target"])) {
			unset($value["targetTitle"]);
		} else {
			$value["target"] = $value["targetTitle"];
			unset($value["targetTitle"]);
		}

		if (!$id) {
			$value["created_user"] = $this->cmsUser->getUserName();
			$value["created_date"] = time();
			$value["sort"] = $this->getNextSort($parent_id, $value["country"], $value["lang"]);
		}
		
		if (!isset($value["image"])) {
			$value["image"] = '';
		}
		
		$menus = $value['menu_id'];
		unset($value['menu_id']);
                if (empty($value['parent_id'])) unset($value['parent_id']);
                //die(var_dump($value));
		$save = saveValuesInDb($this->dbTable, $value, $id);

		if (!$id && !$value["mirror_id"]) {
			saveValuesInDb($this->dbTable, array("mirror_id" => $save), $save);
		}
		
		$this->saveModules($save, $modules);
		$this->saveMenus($save, $menus);
		
		$this->set_URL($save);
		$this->sort = 1;
		$this->set_sort($value['lang']);
		
		jsonSend($save);
	}
	
	/**
	 * Get mirror_id by content id
	 * 
	 * @param int		content id
	 */
	private function getMirrorId($id) {
		
		$dbQuery = "SELECT `mirror_id` FROM `" . $this->dbTable . "` 
													WHERE `id` = '" . $id . "' LIMIT 1";
		$query = new query($this->db, $dbQuery);
		if ($mirror = $query->getOne()) {
			return $mirror;
		} else {
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `mirror_id` = '" . $id . "' 
													WHERE `id` = '" . $id . "' LIMIT 1";
			$query = new query($this->db, $dbQuery);
			return $id;
		}
		
	}
	
	/**
	 * Saving all menus on page
	 * 
	 * @param int		content id
	 * @param array		menus on page
	 */
	private function saveMenus($id, $menus) {

		deleteFromDbById($this->dbTable_menus_on_page, $id, "page_id");
		
		if (is_array($menus) && count($menus) > 0) {
			for ($i = 0; $i < count($menus); $i++) {
				$dbQuery = "INSERT INTO `" . $this->dbTable_menus_on_page . "` SET `page_id` = '" . $id . "', `menu_id` = '" . $menus[$i] . "'";
				doQuery($this->db, $dbQuery);
			}
		}
				
	}
	
	/**
	 * Saving all public modules on page
	 * 
	 * @param int		content id
	 * @param array		modules on page
	 */
	private function saveModules($id, $modules) {

	    // this line is above the rest to ensure, that we could save a page without modules
        // to delete existing is they were set previously
        deleteFromDbById($this->dbTable_modules_on_page, $id, "page_id");

	    // if no modules selected, we just do nothing
	    if(empty($modules) || !is_array($modules)) {
	        return false;
        }

		foreach ($modules AS $k => $v) {
			$dbQuery = "INSERT INTO `" . $this->dbTable_modules_on_page . "` SET `page_id` = '" . $id . "', `module_id` = '" . str_replace("module_", "", $k) . "'";
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Getting next sort id for add new content
	 * 
	 * @param int		parent content id, default value = 0
	 * @param int		country id
	 * @param string	language
	 */
	private function getNextSort($parentId = false, $c, $l) {
		
		$dbQuery = "
			SELECT MAX(sort) AS sort FROM `" . $this->dbTable . "` 
				WHERE `country` = '" . $c . "'
						AND `lang` = '" . $l . "'";
		$dbQuery .= " AND `parent_id` = '" . $parentId . "' ";

		$query = new query($this->db, $dbQuery);
		
		return $query->getOne() + 1;
	}
	
	/**
	 * Checking for uniq content name in each language and country
	 * 
	 * @param int		content id, it's need if we are editing content
	 * $value array		content values 
	 */
	public function checkName($id, $value) {

		$result = false;
		$value = addSlashesDeep(jsonDecode($value));
		
		$dbQuery = "SELECT `id` FROM `" . $this->dbTable . "` WHERE `url` = '" . $value["url"] . "' AND `lang` = '" . $value["lang"] . "' AND `country` = '" . $value["country"] . "'";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			if ($id && $id == $query->getOne()) {
				$result = true;
			}
		} else {
			$result = true;
		}
		
		return $result;
	}
	
	/**
	 * Move content or change content order
	 * 
	 * @param int	content id
	 * @param int	destination content id
	 * @param int	previous content id 
	 */
	public function moveOrChangeOrder($id, $dId, $pId) {
		$id = str_replace("node", "", $id);
		$dId = str_replace("node", "", $dId);
		$pId = str_replace("node", "", $pId);
		
		if ($id) {
			$filterLang = $this->functions->getP("filterLang") ? $this->functions->getP("filterLang") : $this->functions->getDefaultLanguage();
			$dbQuery = "SELECT `id`, `sort` FROM `" . $this->dbTable . "` WHERE `parent_id` = '" . $dId . "' AND `lang` = '" . $filterLang . "' ORDER BY `sort` ASC";
			$query = new query($this->db, $dbQuery);
			$sort = "";
			while ($query->getrow()) {
				
				if ($pId == $query->field('id')) {
					$sort = $query->field('sort');
				}
				
				if ($sort > 0 && $sort < $query->field('sort')) {
					$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = sort + 1 WHERE `id` = '" . $query->field('id') . "'";
					$querySort = new query($this->db, $dbQuery);
				}
			}

			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . ($sort > 0 ? $sort + 1 : '0') . "', `parent_id` = '" . $dId . "' WHERE `id` = '" . $id . "'";
			$query = new query($this->db, $dbQuery);
		}	
	}
	
	/**
	 * Copy content page
	 * 
	 * @param int	content id
	 */
	public function copy($id) {
		
		$copyCat = getP('copy');
		
		if ($id && $copyCat) {
			
			$dbQuery = "SELECT `id`, `url`, `lang`, `country` FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "' LIMIT 1";
			$query = new query($this->db, $dbQuery);
			$info = $query->getrow();
				
			// Copy category
			$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = '" . $copyCat . "' LIMIT 1";
			$query = new query($this->db, $dbQuery);
			$infoCopy = $query->getrow();
			
			unset($infoCopy['id']);
			$infoCopy['parent_id'] = $info['id'];
			$infoCopy['lang'] = $info['lang'];
			$infoCopy['country'] = $info['country'];
			$infoCopy['sort'] = $this->getNextSort($infoCopy['parent_id'], $info['country'], $info['lang']);
			$infoCopy['mirror_id'] = $infoCopy['mirror_id'] ? $infoCopy['mirror_id'] : $copyCat;
			
			// Generate new url
			$u = explode("/", $infoCopy['url']);
			$infoCopy['url'] = $info['url'] . $u[count($u) - 2] . "/";
			
			if (is_numeric($infoCopy['target'])) {
				$infoCopy['target'] = getMirror($infoCopy['target'], $info['country'], $info['lang']);
			}
			
			$newId = saveValuesInDb($this->dbTable, $infoCopy, '');
			
			$dbQuery = "SELECT * FROM `" . $this->dbTable_modules_on_page . "` WHERE `page_id` = '" . $copyCat . "'";
			$query = new query($this->db, $dbQuery);
			
			while ($query->getrow()) {
				$dbQuery = "INSERT INTO `" . $this->dbTable_modules_on_page . "` SET `page_id` = '" . $newId . "', `module_id` = '" . $query->field('module_id') . "'";
				$queryM = new query($this->db, $dbQuery);
			}
			
			$this->copyChilds($copyCat, $newId, $infoCopy['url'], $info);
		}
	}
	
	/**
	 * Copy all childs
	 * 
	 * @param int		copy content id
	 * @param int		new content id
	 * @param string	new content url
	 * @param array		info
	 */
	private function copyChilds($copyCat, $newId, $url, $info) {
		
		$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE `parent_id` = '" . $copyCat . "'";
		$query = new query($this->db, $dbQuery);
		$infoCopy = $query->getArray();
		for ($i = 0; $i < count($infoCopy); $i++) {

			$copyChild = $infoCopy[$i]['id'];
			
			unset($infoCopy[$i]['id']);
			$infoCopy[$i]['parent_id'] = $newId;
			$infoCopy[$i]['lang'] = $info['lang'];
			$infoCopy[$i]['country'] = $info['country'];
			$infoCopy[$i]['sort'] = $this->getNextSort($infoCopy[$i]['parent_id'], $info['country'], $info['lang']);
			
			// Generate new url
			$u = explode("/", $infoCopy[$i]['url']);
			$infoCopy[$i]['url'] = $url . $u[count($u) - 2] . "/";
			
			if (is_numeric($infoCopy[$i]['target'])) {
				$infoCopy[$i]['target'] = getMirror($infoCopy[$i]['target'], $info['country'], $info['lang']);
			}
			
			$newIdChild = saveValuesInDb($this->dbTable, $infoCopy[$i], '');
			
			$dbQuery = "SELECT * FROM `" . $this->dbTable_modules_on_page . "` WHERE `page_id` = '" . $copyChild . "'";
			$query = new query($this->db, $dbQuery);
			
			while ($query->getrow()) {
				$dbQuery = "INSERT INTO `" . $this->dbTable_modules_on_page . "` SET `page_id` = '" . $newIdChild . "', `module_id` = '" . $query->field('module_id') . "'";
				$queryM = new query($this->db, $dbQuery);
			}
			
			$this->copyChilds($copyChild, $newIdChild, $infoCopy[$i]['url'], $info);
		}
	}

	/**
	 * 
	 * Save content tree after Drag & Drop
	 * 
	 * @param int	 content parent ID
	 */
	public function saveDND($parentID) {
	
		if (is_numeric($parentID)) {
			$childrens = jsonDecode(getP('childrens'));
			for ($i = 0; $i < count($childrens); $i++) {
				
				$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . ($i + 1) . "', `parent_id` = '" . mres($parentID) . "' WHERE `id` = '" . mres($childrens[$i]) . "'";
				doQuery($this->db, $dbQuery);
				
			}
			$this->set_URL(jsonDecode(getP('main')));
			
			$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE id= '".$parentID."' LIMIT 0,1";
		
			$query = new query($this->db, $dbQuery);
		
			$row = $query->getrow();
		
			$this->set_sort($query->field('lang'));
		}
		
	}
	
	public function set_URL($id) {
		
		$dbQuery = "UPDATE `" . $this->dbTable . "` SET url = '" . $this->get_URL($id) . "' WHERE id = '" . $id . "'";

		doQuery($this->db, $dbQuery);

		$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE parent_id = '" . $id . "'";
		
		$query = new query($this->db, $dbQuery);
		
		$rows = $query->getArray();
		if (!empty($rows)) {
			foreach ($rows as $key => $value){
				if ($value['parent_id'] != "0") {
					$this->set_URL($value['id']);
				} 
					
			}
			
		}
		
	}
	
	public function get_URL($id, $url = '') {

		$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE id = '" . $id . "'";
		
		$query = new query($this->db, $dbQuery);
		$row = $query->getrow();
		$url = basename($row['url']) . '/' . $url;
		if ($row['parent_id'] != '0') {
			$url = $this->get_URL($row['parent_id'], $url);
		} 
			
		return $url;
	}
	
	public function valid_parent($id, $parent_id, $data) {
	
		$dbQuery = "SELECT *  FROM " . $this->dbTable . " WHERE id = '".$id."'";
		
		$query = new query($this->db, $dbQuery);
		
		$page = $query->getrow();

		if ($page['parent_id'] == '0' && $parent_id == false) 
			return true;
		
		if(empty($parent_id)){
			$fCountry = $data['country'] ? $data['country'] : getDefaultCountry();
			$fLang = $data['lang'] ? $data['lang'] : getDefaultLanguage($fCountry);
			$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE 1 AND country = '" . $fCountry  . "' AND lang = '" . $fLang  . "' AND parent_id = '0'"; echo $dbQuery;
			$query = new query($this->db, $dbQuery);
			if ($query->num_rows() > 0) {
				jsonSend('root_already_exists');
				return false;
			} else {
				return true;
			}
				
		} else {
			
			if ($this->is_child($id, $parent_id)) {
				return true;
			} else {
				jsonSend('parent_child_or_self');
			}
			
		}

	}
	
	public function is_child($id, $parent_id) {

		if ($parent_id == $id) {
			$this->is_child = false;	
		} else {
			$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE id = '" . $parent_id . "'";
			$query = new query($this->db, $dbQuery);
			$rows = $query->getrow();
			if ($rows['parent_id'] != '0') {
				$parent_id = $rows['parent_id'];
				$this->is_child($id, $parent_id);
			} else {
				$this->is_child = true;
			}
		}
		return $this->is_child;
		
	}
	
	public function set_sort($lang) {
		$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE lang = '".$lang."' AND parent_id = '0' LIMIT 0,1";
		$query = new query($this->db, $dbQuery);
		$this->set_child_sort($query->getOne('id'));
	}
	
	public function set_child_sort($id) {

		$dbQuery = "SELECT * FROM " . $this->dbTable . " WHERE parent_id = '".$id."' ORDER BY sort ASC";
		
		$query = new query($this->db, $dbQuery);
		
		while ($query->getrow()){
			$this->sort++;
			$dbQuery2 = "UPDATE `" . $this->dbTable . "` SET sort = '" . $this->sort . "' WHERE id = '" . $query->field('id') . "'";
			doQuery($this->db, $dbQuery2);
			$this->set_child_sort($query->field('id'));
		}
	}
	
	

}
?>