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
 * CMS content module
 * Default module in cms. Generate content, load templates and show it
 * Be careful, if wanna edit it.
 * 21.04.2008
 */

class contentData extends Module {

	private $dbTable = "ad_content";
	private $pageParentIds = array();
	private $contentData = array();
	private $siteMenus = array();

	/**
	 * Class constructor
	 */
	public function __construct() {

		parent :: __construct();

		// !!!
		// DEBUG
		// TODO: following code (setcookie) block should be commented out on prod!!!

//        setcookie('_gaDDETQ6300GZ341_6811_2', 'test', time()+(60*60*24*7), '/', '.'.$_SERVER['HTTP_HOST']);
//        setcookie('_ga_#_TQ6300GZ341_6811_2', 'test', time()+(60*60*24*7), '/', '.'.$_SERVER['HTTP_HOST']);
//        setcookie('_gid_FFETQ6300GZ341_6811_2', 'test', time()+(60*60*24*7), '/', '.'.$_SERVER['HTTP_HOST']);
//        setcookie('_gid_444FCQ_TQ6300GZ341_6811_2', 'test', time()+(60*60*24*7), '/','.'.$_SERVER['HTTP_HOST']);
//        setcookie('_gat_SCPF_TdQ6300GZ341_6811_2', 'test', time()+(60*60*24*7), '/','.'.$_SERVER['HTTP_HOST']);
//        setcookie('_gat__SCPF_TdQ6300GZ341_6811_2', 'test', time()+(60*60*24*7), '/','.'.$_SERVER['HTTP_HOST']);
//        sleep(1);
//
//        if(!$_COOKIE['_gat__SCPF_TdQ6300GZ341_6811_2']) {
//            header("Refresh:0");
//            exit;
//        }

		// !!!


		$cookieConsent = $this->getCookieConsent();
		$this->setPData($cookieConsent, 'cookieConsent');
	}

	/**
	 * Get inherit page content
	 *
	 * @param int	content id
	 */
	private function getInheritContent($id) {

		$dbQuery = "SELECT `page_title`, `title`, `full_title`, `content`, `image`, `keywords`, `description`
						FROM " . $this->dbTable . " WHERE `id` = '" . $id . "' LIMIT 0,1";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			return $query->getrow();
		} else {
			return false;
		}
	}

	/**
	 * Get template name by id
	 *
	 * @param int	template id
	 */
	public function getTemplateById($id) {
		$dbQuery = "SELECT `filename` FROM `ad_templates` WHERE `id` = '" . $id . "' LIMIT 0,1";
		$query = new query($this->db, $dbQuery);

		return $query->getOne();
	}

	/**
	 * Checking page url.
	 * If is not correct opening default site page
	 *
	 * @param string		page url
	 */
	public function checkPageUrl($pageUrl) {

		$lang = $this->getLang();

		if($this->cfg->get('whiteLabel') && $pageUrl == $lang . '/') {
			$pageUrl = $lang . '/home/';
		}

		if (!$pageUrl) {
			openDefaultPage();
		}

		$dbQuery = "SELECT content.*
									FROM
										`ad_countries_domains` domains,
										`ad_content` content,
										`ad_languages` lang,
										`ad_languages_to_ct` lc,
										`ad_templates` tpl
									WHERE 1
										AND domains.`domain` = '" . $_SERVER["HTTP_HOST"] . "'
										AND content.`active` = '1'
										AND content.`country` = domains.`country_id`
										AND content.`url` = '" . mres($pageUrl) . "'
										AND lang.`enable` = '1'
										AND lang.`lang` = content.`lang`
										AND lc.`lang_id` = lang.`id`
										AND lc.`country_id` = domains.`country_id`
								  LIMIT 1";

		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {

			$this->contentData = $query->getrow();

			if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
				$this->noLayout(true);
			} else {
				if ($this->contentData['cache'] && empty($_POST)) {
					if (file_exists(AD_CACHE_FOLDER . md5($this->contentData['url']) . '.' . $this->contentData['edit_date'] . '.cache')) {
						if ($cache = fopen(AD_CACHE_FOLDER . md5($this->contentData['url']) . '.' . $this->contentData['edit_date'] . '.cache', 'r')) {
							$output = '';
							while (!feof($cache)) {
								$output .= fread($cache, 8192);
							}

							fclose($cache);
							die($output);
						}
					}
				}
			}

			if ($this->contentData['ssl'] && !isset($_SERVER['HTTPS'])) {
				redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			} elseif (!$this->contentData['ssl'] && isset($_SERVER['HTTPS'])) {
				redirect('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
			}

			if (isset($_SERVER["HTTP_HOST"])) {
				$langToDomain = $this->cfg->get('langDomains');
				if (isset($langToDomain[$this->contentData['lang']]) && $langToDomain[$this->contentData['lang']] != $_SERVER["HTTP_HOST"]) {
					redirect('http://' . $langToDomain[$this->contentData['lang']] . '/' . $this->contentData['url']);
				}
			}

			// Setting site lang
			$this->setLang($this->contentData['lang']);

			// Setting country
			$this->setCountry($this->contentData['country']);

			if ($this->contentData["type"] == "r") {
				if ($this->contentData["target"]) {
					if (is_numeric($this->contentData["target"])) {
						redirect(getLink($this->contentData["target"]));
					}
					else {
						redirect($this->contentData["target"]);
					}
				}
			} elseif ($this->contentData["type"] == "i") {
				if ($this->contentData["target"]) {
					if (is_numeric($this->contentData["target"])) {
						if ($inherit = $this->getInheritContent($this->contentData["target"])) {
							$this->contentData = array_merge($this->contentData, $inherit);
						}
					}
				}
			}

			/**
			 * Getting template filename
			 */
			$this->contentData["template"] = $this->contentData["template"] ? $this->getTemplateById($this->contentData["template"]) : '';

			/**
			 * Getting all parent id's
			 */
			$this->contentData["parentIds"] = $this->getAllParentIds($this->contentData["id"]);

			return $this->contentData;

		} else {

			if ($this->cfg->get('404')) {

				$this->show404Page();

			} else {

				if (isset($_SESSION['ad_language']) && $id = getLangMainPage($_SESSION['ad_language'])) {

					$url = getLink($id);

					// if there is no page on selected language we set lang to default and get url again

					if(!$url) {

						$this->setLang(getDefaultLang());
						$id = getLangMainPage($_SESSION['ad_language']);
						$url = getLink($id);
					}

					redirect($url);

				} else {

					if ($id = getLangMainPage($this->cfg->get("langInTheEnd") ? $this->uri->segment($this->uri->totalSegments() - 1) : $this->uri->segment(0))) {

						$url = getLink($id);

						if(!$url) {

							$this->setLang(getDefaultLang());
							$id = getLangMainPage($_SESSION['ad_language']);
							$url = getLink($id);
						}

						redirect($url);

					} else {

						openDefaultPage();
					}
				}
			}
		}
	}

	/**
	 * Get all page parent ids from db
	 *
	 * @param int	content id
	 */
	function getAllParentIds($id) {

		$dbQuery = "SELECT `id`, `parent_id` FROM " . $this->dbTable . " WHERE `id` = '" . $id . "' LIMIT 0,1";
		$query = new query($this->db, $dbQuery);
		if ($query->getrow()) {
			$this->pageParentIds[] = $query->field("id");
			$parentId = $query->field('parent_id');
			if ($parentId) {
				$this->getAllParentIds($parentId);
			}
		}
		$query->free();

		return $this->pageParentIds;
	}

	/**
	 * Display all content menus on the page
	 */
	public function displayPageMenus() {

		$this->getAllMenus();

		// Special handling for add doctor menu items
		//
		if($this->siteMenus['TOP']) {

			$topMenu = $this->siteMenus['TOP'];

			$pieasakiArstuKey = array_search('/profils/piesaki-arstu/', array_column($topMenu, 'url'));

			// we have pieasaki arstu menu item
			if($pieasakiArstuKey) {
				$pieasakiArstuCfg = $this->cfg->get('pieasakiArstu');

				if(!$pieasakiArstuCfg['active']) {
					unset($this->siteMenus['TOP'][$pieasakiArstuKey]);
				} else {
					$this->siteMenus['TOP'][$pieasakiArstuKey]['class'] = $pieasakiArstuCfg['class'];
					$this->siteMenus['TOP'][$pieasakiArstuKey]['right'] = $pieasakiArstuCfg['right'];
				}
			}

			$arstiemKey = array_search('/arstiem/', array_column($topMenu, 'url'));

			// we have arstiem menu item
			if($arstiemKey) {
				$arstiemCfg = $this->cfg->get('arstiem');

				if(!$arstiemCfg['active']) {
					unset($this->siteMenus['TOP'][$arstiemKey]);
				} else {
					$this->siteMenus['TOP'][$arstiemKey]['class'] = $arstiemCfg['class'];
					$this->siteMenus['TOP'][$arstiemKey]['right'] = $arstiemCfg['right'];
				}
			}
		}

		// // //


		if(isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {

			// Set some additional info to PROFILE menu
			// like item numbers or menu icons

			// Yes, it's hardcoded here...

			if($this->siteMenus['PROFILE']) {

				$profileMenu = $this->siteMenus['PROFILE'];

				foreach ($profileMenu as $k => $item) {

					// set icons for profile menu

					$lPref = '/'. $this->getLang();

					switch ($item['url']) {

						case $lPref . '/profils/mans-profils/' :
							$profileMenu[$k]['menuIcon'] = 'home-menu.png';
							break;
						case $lPref . '/profils/zinojumi/' :
							$profileMenu[$k]['menuIcon'] = 'zinojumi-menu.png';
							break;
						case $lPref . '/profils/mani-dati/' :
							$profileMenu[$k]['menuIcon'] = 'mans-profils-menu.png';
							break;
						case $lPref . '/profils/mani-dati/citas-personas/' :
							$profileMenu[$k]['menuIcon'] = 'mani-dati-menu.png';
							break;
						case $lPref . '/profils/mani-pieraksti/' :
							$profileMenu[$k]['menuIcon'] = 'pieraksti-menu.png';
							break;
						case $lPref . '/profils/manas-konsultacijas/' :
							$profileMenu[$k]['menuIcon'] = 'konsultacijas-menu.png';
							break;
						case $lPref . '/profils/mani-arsti/' :
							$profileMenu[$k]['menuIcon'] = 'mani-arsti-menu.png';
							break;
						case $lPref . '/profils/mainit-paroli/' :
							$profileMenu[$k]['menuIcon'] = 'change-pass-menu.png';
							break;
						case $lPref . '/profils/my-orders/' :
							$profileMenu[$k]['menuIcon'] = 'pirkumi-menu.png';
							break;
						case $lPref . '/profils/my-subscriptions/' :
							$profileMenu[$k]['menuIcon'] = 'abonement-menu.png';
							break;
						default :
							$profileMenu[$k]['menuIcon'] = 'mans-profils-menu.png';
					}

					// set number badge on messages menu item
					if(
						$item['id'] == getMirror($this->cfg->getData('mirros_profile_messages_page')) &&
						isset($_SESSION['user']['unreadMessages'])
					) {
						if($_SESSION['user']['unreadMessages'] > 0) {
							$profileMenu[$k]['number'] = $_SESSION['user']['unreadMessages'];
						} else {
							unset($item['number']);
						}
					}

					// set number of reservations (mirros_profile_reservations_page)
					if(
						$item['id'] == getMirror($this->cfg->getData('mirros_profile_reservations_page')) &&
						isset($_SESSION['user']['reservationsCount'])
					) {
						if($_SESSION['user']['reservationsCount'] > 0) {
							$profileMenu[$k]['number'] = $_SESSION['user']['reservationsCount'];
						} else {
							unset($item['number']);
						}
					}

					// set number of consultations (mirros_profile_consultations_page)
					if(
						$item['id'] == getMirror($this->cfg->getData('mirros_profile_consultations_page')) &&
						isset($_SESSION['user']['consultationsCount'])
					) {
						if($_SESSION['user']['consultationsCount'] > 0) {
							$profileMenu[$k]['number'] = $_SESSION['user']['consultationsCount'];
						} else {
							unset($item['number']);
						}
					}

					// ... other items count in menu if needed can be added the same way
					//


					// unset mySubscription from menu array if current user has no subscription

					if(
						$item['id'] == getMirror($this->cfg->getData('mirros_profile_subscription_page')) &&
						(empty($_SESSION['user']) || empty($_SESSION['user']['dcSubscription']))
					)
					{
						unset($profileMenu[$k]);
					}

				}

				$this->siteMenus['PROFILE'] = $profileMenu;

//                pre($this->siteMenus['PROFILE']);
//                pre($_SESSION['user']);

			}

//            pre($_SESSION['user']);

		}

//        echo '<pre>';
//        var_dump($this->siteMenus);
//        exit;

		$this->setPData($this->siteMenus, "menu");
		$this->setPData($this->getCountryMenu(), "ctrAr");
		$this->setPData($this->getLangMenu(), "langMenu");
	}

	/**
	 * Get all menu nodes
	 *
	 * @param int	menu id
	 * @param int	parent page id
	 */
	private function getAllMenus($parentId = 0, $pParentId = '') {

		$r = array();
		$dbQuery = "SELECT c.`id`, c.`parent_id`, c.`title`, c.`url`, m.`id` AS menu_id, m.`name`, m.`expanded`
						FROM `ad_content` c, `ad_menus` m, `ad_menus_on_page` mp
						WHERE mp.`menu_id` = m.id
								AND mp.page_id = c.id
								AND c.`lang` = '" . $this->getLang() . "'
								AND c.`enable` = '1'
								AND c.`active` = '1'
								AND c.`country` = '" . $this->getCountry() . "'
								" . ($pParentId ? " AND c.`parent_id` = '" . $pParentId . "'" : "") . "
								AND m.enable = '1'
								AND m.parent_id = '" . $parentId . "'
						ORDER BY m.name, c.`sort` ASC";
		$query = new query($this->db, $dbQuery);
		$cnt = $query->num_rows();

		$first = true;
		$menuName = '';
		$i = 1;

		$mirrorId = getMirror(getDefaultPageId());

		while($row = $query->getrow()) {
			$dont_include = false;
//             if($this->isNewsContent($query->field('id')) && !$this->containsDocuments($query->field('id'), 'mod_news', " AND n.enable = '1'")){
//                 $dont_include = true;
//             }
// 			if($this->isNewsContent($query->field('id'), 'faq') && !$this->containsDocuments($query->field('id'), 'mod_faq', " AND n.enable = '1' ")){
//                 $dont_include = true;
//             }
			if(!$dont_include){

				$menuName = $query->field('name');

				$isAct = (in_array($query->field('id'), $this->pageParentIds) ? true : false);

				if ($query->field('id') == $mirrorId && $this->contentData["parent_id"] != 0) {
					$isAct = false;
				}

				if($menuName == 'PROFILE') {

					// for PROFILE menu we make active only exact that page not the parents!

					$isAct = $row['id'] == $this->contentData['id'];
				}

				if ($menuName && $menuName != $query->field('name')) {
					$first = true;

					// Change last item of previous menu to last
					if (isset($this->siteMenus[$menuName][count($this->siteMenus[$menuName]) - 1])) {
						$this->siteMenus[$menuName][count($this->siteMenus[$menuName]) - 1]['last'] = true;
					}


				} else {
					$first = false;
				}

				if ($pParentId) {
					$this->siteMenus[$query->field('name')][$pParentId][] = array(
						"id" 	 => $query->field('id'),
						"pid"	 =>	$query->field('parent_id'),
						"first"	 => ($i == 1 ? true : $first),
						"last"	 => ($i == $cnt ? true : false),
						"title"  => $query->field('title'),
						"url"	 => "/" . makeUrlWithLangInTheEnd($query->field('url')),
						"active" => $isAct
					);
				} else {
					$this->siteMenus[$query->field('name')][] = array(
						"id" 	 => $query->field('id'),
						"pid"	 =>	$query->field('parent_id'),
						"first"	 => ($i == 1 ? true : $first),
						"last"	 => ($i == $cnt ? true : false),
						"title"  => $query->field('title'),
						"url"	 => "/" . makeUrlWithLangInTheEnd($query->field('url')),
						"active" => $isAct
					);
				}


				if ($isAct || $query->field('expanded')) {
					$this->getAllMenus($query->field('menu_id'), $query->field('id'));
				}


				$i++;
			}
		}

		return $r;
	}

	public function containsDocuments($id, $table = 'mod_news', $extra = '') {
		$result = 0;

		$dbQuery = "SELECT COUNT(`id`) FROM `" . $table . "` n WHERE n.`content_id` = '" . $id . "' " . $extra . " ";
		$query = new query($this->db, $dbQuery);
		$documents_cnt = $query->getOne();
		if ($documents_cnt) {
			$result = 1;
		}

		return $result;

	}

	public function isNewsContent($id, $module_name = 'news') {
		$result = false;

		$dbQuery = "
        	SELECT mop.`page_id`
        	FROM `ad_modules` AS m
        	INNER JOIN `ad_modules_on_page` AS mop
        		ON m.`id` = mop.`module_id`
        	WHERE
        		mop.`page_id` = '" . $id . "'
        		AND m.`name` = '".$module_name."'
        ";
		$query = new query($this->db, $dbQuery);

		$isNews = $query->getOne();
		if ($isNews) {
			$result = true;
		}

		return $result;

	}

	/**
	 * Display page language menu
	 */
	public function getLangMenu() {

		$langArray = array();

		$dbQuery = "SELECT * FROM `ad_languages` l, `ad_languages_to_ct` lc WHERE l.id = lc.lang_id AND lc.country_id = '" . $this->getCountry() . "' AND l.enable = '1' ORDER BY l.sort ASC";
		$query = new query($this->db, $dbQuery);
		$cnt = $query->num_rows();

		if ($cnt > 0) {
			$langDomains = $this->cfg->get('langDomains');
			$first = true;
			$i = 1;
			while ($query->getrow()) {

				$domain = isset($langDomains[$query->field('lang')]) ? 'http://' . $langDomains[$query->field('lang')] : '';
				$thismirror = getMirror($this->contentData["id"], $this->getCountry(), $query->field('lang'));
				$checkid = $this->contentData["id"];
				if($thismirror == $this->contentData["id"]){
					$checkid =  getDefaultPageId();
				}

				$langArray["langs"][] = array(
					"first"	 => $first,
					"last"	 => ($i == $cnt ? true : false),
					"title"	 => $query->field('title'),
					"lang"	 => $query->field('lang'),
					"active" =>	($this->getLang() == $query->field('lang') ? true : false),
					"link"   => $domain.getLink(getMirror($checkid, $this->getCountry(), $query->field('lang')))
				);

				if ($first) {
					$first = false;
				}

				$i++;

			}

		}

		return $langArray;
	}

	/**
	 * Display country menu
	 */
	public function getCountryMenu() {

		$ctrArr = array();

		$dbQuery = "SELECT c.*, cd.domain
							FROM
								`ad_countries` c, `ad_countries_domains` cd
							WHERE
								cd.`country_id` = c.id
								AND cd.`default` = '1'
							ORDER BY c.`id` ASC";
		$query = new query($this->db, $dbQuery);
		$cnt = $query->num_rows();

		if ($cnt > 0) {

			$first = true;
			$i = 1;
			while ($query->getrow()) {

				$link = 'http://' . $query->field('domain');

				if ($this->getCountry() == $query->field('id')) {
					$ctrArr["active"]["first"] = $first;
					$ctrArr["active"]["last"] = ($i == $cnt ? true : false);
					$ctrArr["active"]["title"] = $query->field('title');
					$ctrArr["active"]["ctr"] = $query->field('id');
					$ctrArr["active"]["link"] = $link;
					$ctrArr["active"]["id"] = $query->field('id');
					$ctrArr["active"]["google_analytics"] = $query->field('google_analytics');
					$ctrArr["active"]["webmasters"] = $query->field('webmasters');

				}

				$ctrArr["ctr"][] = array(
					"first"	 => $first,
					"last"	 => ($i == $cnt ? true : false),
					"title"	 => $query->field('title'),
					"ctr"	 => $query->field('id'),
					"active" =>	($this->getCountry() == $query->field('id') ? true : false),
					"link"   => $link,
					"id"   => $query->field('id'),
					"google_analytics"   => $query->field('google_analytics'),
					"webmasters"   => $query->field('webmasters')
				);

				if ($first) {
					$first = false;
				}

				$i++;
			}

		}

		return $ctrArr;
	}

	/**
	 * Load all site mirrors
	 */
	public function loadMirrors() {
		$this->mirrors = &loadLibClass('mirrors');

		$this->mirrors->getMirrors();
	}

	/**
	 * Get full page path, tree
	 *
	 * @param bool	create links or not, default false
	 * @param int	content id
	 */
	public function getPagePath($noLinks = false, $id = "") {

		$outHtml = "";
		$parentIds = $id ? $this->getAllParentIds($id) : $this->pageParentIds;

		$pCount = count($parentIds);

		$outHtml .= gL('home_title', 'Sakums');
		for ($i = $pCount; $i > 1; $i--) {

			$outHtml .= '&nbsp;&nbsp;/&nbsp;&nbsp;' . $this->contentData["title"];
		}

		return $outHtml;
	}

	public function getDoctorsCount()
	{
		$dbQuery = "SELECT count(id)
							FROM
								`mod_doctors`";
		$query = new query($this->db, $dbQuery);
		return $query->getOne();
	}

	public function getProfilesCount()
	{
		$dbQuery = "SELECT count(id)
							FROM
								`mod_profiles`";
		$query = new query($this->db, $dbQuery);
		return $query->getOne();
	}

	public function getRandomReview()
	{
		$dbQuery = "SELECT *
							FROM `mod_reviews` r
								LEFT JOIN `mod_reviews_data` rd ON (r.id = rd.review_id AND rd.lang = '" . $this->getLang() . "')
							ORDER BY RAND()
							LIMIT 1";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows()) {
			return $query->getrow();
		} else {
			return false;
		}
	}

	public function getAnnouncement()
	{
		/** @var array $annCfg */
		$annCfg = $this->cfg->get('announcement');

		if(!$annCfg || !$annCfg['showAnnouncements'] || !$annCfg['items'] || !count($annCfg['items'])) {
			return null;
		}

		$annTplDir = (isset($annCfg['tmplPath']) && $annCfg['tmplPath']) ? $annCfg['tmplPath'] : 'announcement/';
		$oldTmplDir = $this->tpl->getTmplDir();
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/' . $annTplDir);
		$tplPath = AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/' . $annTplDir;

		$currDateTime = date(PIEARSTA_DT_FORMAT, time());
		$annToShow = array();

		foreach ($annCfg['items'] as $key => $item) {

			$item['from'] = $item['from'] ? $item['from'] : '2000-01-01 00:00:00';
			$item['to'] = $item['to'] ? $item['to'] : '9999-12-31 23:59:59';

			if(
				$item['from'] <= $currDateTime &&
				$item['to'] >= $currDateTime &&
				$item['active']
			) {

				if(file_exists($tplPath . $item['template'] . '.html')) {
					$annToShow[$key] = $item;
					$annToShow[$key]['template'] = $this->tpl->output($item['template'], $this->getPData());

					if(file_exists($tplPath . $item['popup'] . '.html')) {
						$annToShow[$key]['popup'] = $this->tpl->output($item['popup'], $this->getPData());
					}
				}
			}
		}

		$this->tpl->setTmplDir($oldTmplDir);

		if(count($annToShow)) {
			$annToShow = array_values($annToShow);
			return $annToShow[mt_rand(0, count($annToShow) - 1)];
		} else {
			return null;
		}
	}

	public function getReturn()
	{
		return $this->return;
	}

	public function getPageContent()
	{
		$retArray = array(
			'success' => false,
			'content' => '',
		);

		$lang = mres($_POST['pageLang']);
		$pageUrl = mres($_POST['pageUrl']);

		if(empty($pageUrl)) {
			$this->return = $retArray;
			return false;
		}

		if(empty($lang)) {
			$lang = 'lv';
		}

		$pageUrl = $lang . $pageUrl;

		//
		$dbQuery = "SELECT content FROM ad_content WHERE url = '" . $pageUrl . "'";
		$query = new query($this->db, $dbQuery);

		if($query->num_rows()) {
			$row = $query->getRow();

			$retArray['success'] = true;
			$retArray['content'] = $row['content'];
		}

		$this->return = $retArray;
	}

	public function dmssLink()
	{
		$_SESSION['allowDmssAuthRoute'] = true;

		$location = getP('url');

		$retArray = array(
			'success' => !empty($location),
			'location' => $location,
		);

		$this->return = $retArray;
	}

	public function getCookieConsent()
	{

		$cookieConsent = array(
			'necessary' => true,
			'preferences' => false,
			'statistics' => false,
			'marketing' => false,
		);

		if (isset($_COOKIE["CookieConsent"]))
		{
			switch ($_COOKIE["CookieConsent"])
			{
				case "-1":

					//The user is not within a region that requires consent - all cookies are accepted

					$cookieConsent = array(
						'necessary' => true,
						'preferences' => true,
						'statistics' => true,
						'marketing' => true,
					);

					break;

				default: //The user has given their consent

					//Read current user consent in encoded JavaScript format
					$valid_php_json = preg_replace('/\s*:\s*([a-zA-Z0-9_]+?)([}\[,])/', ':"$1"$2', preg_replace('/([{\[,])\s*([a-zA-Z0-9_]+?):/', '$1"$2":', str_replace("'", '"',stripslashes($_COOKIE["CookieConsent"]))));
					$CookieConsent = json_decode($valid_php_json);

					if (!filter_var($CookieConsent->preferences, FILTER_VALIDATE_BOOLEAN)
						&& !filter_var($CookieConsent->statistics, FILTER_VALIDATE_BOOLEAN) && !
						filter_var($CookieConsent->marketing, FILTER_VALIDATE_BOOLEAN))
					{
						//The user has opted out of cookies, set strictly necessary cookies only

						$this->removeStatisticsCookies();

						$cookieConsent = array(
							'necessary' => true,
							'preferences' => false,
							'statistics' => false,
							'marketing' => false,
						);
					}
					else
					{

						if (filter_var($CookieConsent->preferences, FILTER_VALIDATE_BOOLEAN))
						{
							//Current user accepts preference cookies
							$cookieConsent['preferences'] = true;
						}
						else
						{
							//Current user does NOT accept preference cookies
							$cookieConsent['preferences'] = false;
						}

						if (filter_var($CookieConsent->statistics, FILTER_VALIDATE_BOOLEAN))
						{
							//Current user accepts statistics cookies
							$cookieConsent['statistics'] = true;
						}
						else
						{
							//Current user does NOT accept statistics cookies
							$cookieConsent['statistics'] = false;

							// and here we remove all statistics related cookies

							$this->removeStatisticsCookies();
						}

						if (filter_var($CookieConsent->marketing, FILTER_VALIDATE_BOOLEAN))
						{
							//Current user accepts marketing cookies
							$cookieConsent['marketing'] = true;
						}
						else
						{
							//Current user does NOT accept marketing cookies
							$cookieConsent['marketing'] = false;
						}
					}
			}
		}
		else
		{
			//The user has not accepted cookies - set strictly necessary cookies only

			$this->removeStatisticsCookies();

			$cookieConsent = array(
				'necessary' => true,
				'preferences' => false,
				'statistics' => false,
				'marketing' => false,
			);
		}
		return $cookieConsent;
	}

	private function removeStatisticsCookies()
	{

		foreach ($_COOKIE as $key => $item) {

			if(
				strpos($key, '_ga') !== false ||
				strpos($key, '_gid') !== false ||
				strpos($key, '_gat') !== false
			) {

				setcookie($key, FALSE, -1, '/', '.'.$_SERVER['HTTP_HOST']);
				setcookie($key, FALSE, -1, '/', '.piearsta.lv');
				unset($_COOKIE[$key]);
			}
		}
	}
}
?>
