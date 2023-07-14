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
 * CMS coupons/textlist module
 * Admin path. Edit/Add/Delete and other actions.
 * 27.07.2010
 */

class couponsData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 * $uploadFolder - string	upload folder for frontend
	 * $uploadFolderSmall - string	upload folder for admin view
	 */
	protected $result;
	protected $uploadFolder = 'coupons/';
	protected $imagesConfig = array(
		'small' => array('width' => '220', 'height' => '140'),
		'big' => array('width' => '420', 'height' => '280'),
	);
	
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "coupons";
		
		$this->dbTable = 'mod_coupons';
		$this->dbTableLang = 'mod_coupons_data';
		$this->dbTableProfile = 'mod_profiles_coupons';
		
		$this->cfg->getSiteData();
		
	}
	
	/**
	 * Get all data from db and create module table
	 */
	public function showTable() {
		
				
		/**
		 * Creating module table, using cmsTable class
		 * This is table information
		 */
		$table = array(
			"chekcbox" => array(
				'sort' => false,
				'title' => '',
				'function' => array(&$this, 'moduleCheckboxLink'),
				'fields' => array('id'),
				'params' => array('coupons')
			),
			"title" => array(
				'sort' => true,
				'title' => gLA('m_title','Title'),
				'function' => '',
				'fields'	=> array('title')
			),
			"date_to" => array(
				'sort' => false,
				'title' => gLA('m_date','Date To'),
				'function' => 'convertDate',
				'fields'	=> array('date_to'),
				'params' => array('d-m-Y')
			),
			"cnt" => array(
				'sort' => false,
				'title' => gLA('coupons_cnt','Printed coupons'),
			),
			"updated" => array(
				'sort' => false,
				'title' => gLA('m_updated','Updated'),
				'function' => 'convertDate',
				'fields'	=> array('updated'),
				'params' => array('d-m-Y H:i:s')
			),
			"enable" => array(
				'sort' => false,
				'title' => gLA('m_enable','Enable'),
				'function' => array(&$this, 'moduleEnableLink'),
				'fields'	=> array('id', 'enable')
			),
			"actions" => array(
				'sort' => false,
				'title' => gLA('m_actions','Actions'),
				'function' => array(&$this, 'moduleActionsLink'),
				'fields'	=> array('id')
			),
		);
		
		/**
		 * Getting all information from DB about this module
		 */
		
		$dbQuery = "
			SELECT mp.*, mpl.title, mpl.lang, (SELECT count(profile_id) FROM `" . $this->dbTableProfile . "` cp WHERE cp.coupon_id = mp.id) AS cnt 
			FROM `".$this->dbTable."` AS mp 
			LEFT JOIN `".$this->dbTableLang."` AS mpl
				ON mp.`id` = mpl.`coupon_id` AND mpl.`lang` = '".getDefaultLang()."'
		" . $this->moduleTableSqlParms('id', "DESC");
		$query = new query($this->db, $dbQuery);
		
		$rCounts = $this->getTotalRecordsCount(false);

		// Create module table
		$this->cmsTable->createTable($table, $query->getArray());

		return array('html' => $this->cmsTable->returnTable, 'rCounts' => $rCounts);
		
	}
	
	
	/**
	 * Enable or disable
	 * 
	 * @param int/array 	coupons id
	 * @param bool 			enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `enable` = '" . $value . "' WHERE " . (is_array($id) ? "`id` IN (" . implode(",", $id) . ")" : "`id` = '" . $id . "'");
			$query = new query($this->db, $dbQuery);
		}			
	}
	
	/**
	 * Delete coupons from DB
	 * 
	 * @param int/Array 	coupons id
	 */
	public function delete($id) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			deleteFromDbById($this->dbTable, $id);
		}		
	}
	
	/**
	 * Edit coupons in DB
	 * 
	 * @param int 	coupons id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();
		$data["langauges"] = getSiteLangs();

		if ($id) {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT * 
							FROM `" . $this->dbTable . "` 
							WHERE `id` = '" . $id . "'
							LIMIT 0,1";
							
			$dbQuery2 = "SELECT *
							FROM `" . $this->dbTableLang . "`
							WHERE `coupon_id` = '" . $id . "'";
							
			$query = new query($this->db, $dbQuery);		
			$query2 = new query($this->db, $dbQuery2);
			
			$data["edit"] = $query->getrow();
			$data["edit"]["files"] = unserialize($data["edit"]["files"]);
			$data["edit"]["links"] = unserialize($data["edit"]["links"]);
			
			$dataLang = $query2->getArray();
			
			foreach ($dataLang as $key => $value) {
				$data["edit"]["title"][$value["lang"]] = $value["title"];
				$data["edit"]["lead"][$value["lang"]] = $value["lead"];
				$data["edit"]["text"][$value["lang"]] = $value["text"];
				$data["edit"]["page_url"][$value["lang"]] = $value["page_url"];
				$data["edit"]["page_title"][$value["lang"]] = $value["page_title"];
				$data["edit"]["page_keywords"][$value["lang"]] = $value["page_keywords"];
				$data["edit"]["page_description"][$value["lang"]] = $value["page_description"];
				$data["edit"]["company"][$value["lang"]] = $value["company"];
			}
			
			$dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('profiles', 'coupons')	 . "` p
							WHERE 1
								AND p.`coupon_id` = '" . mres($id) . "'";
			$query = new query($this->db, $dbQuery);
			$data["edit"]["pcoupons"] = $query->getArray();

		}
		
		$data['edit']['uploadFolder'] = $this->uploadFolder;
		$data['siteData'] = $this->cfg->siteData;
		
		
		return $data;
	}
	
	/**
	 * Saving information in DB
	 * 
	 * @param int	 id, it's need if we are editing language
	 * @param array  information values
	 */
	public function save($id, $value) {
		
		$langValues = getP('langValues');
		$files = getP('files');
		$links = getP('links');
		
		$value["updated"] = time();
		$value["files"] = serialize($files);
		$value['links'] = serialize($links);
		
		if (!$id) {
			$value["created"] = time();
			$value["owner"] = $this->cmsUser->userData['id'];
		}
		
		if ($value["date_to"]) {
			list($dd, $mm, $yy) = explode("-", $value["date_to"]);
			$value["date_to"] = mktime(0, 0, 0, $mm, $dd, $yy);
		}
		
		if (!empty($value["lead_image"])) {
				
			if (!is_dir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'small/')) {
				@mkdir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'small/');
				@chmod(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'small/', 0777);
			}
				
			$in = AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . $value["lead_image"];
				
			$this->image = &loadLibClass('image');
				
			if (file_exists(mkFileName($in, '', 'small/'))) {
				list($w, $h) = getimagesize(mkFileName($in, '', 'small/'));
				if ($w > $this->imagesConfig['small']['width']) {
					$this->image->resizeImg($in, mkFileName($in, '', 'small/'), $this->imagesConfig['small']['width'], $this->imagesConfig['small']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
					// $this->image->resizeImg($in, mkFileName($in, '', 'small/'), $this->imagesConfig['small']['width'], $this->imagesConfig['small']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_INSIDE);
				}
			} else {
				$this->image->resizeImg($in, mkFileName($in, '', 'small/'), $this->imagesConfig['small']['width'], $this->imagesConfig['small']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_OUTSIDE | IR_CROP);
				// $this->image->resizeImg($in, mkFileName($in, '', 'small/'), $this->imagesConfig['small']['width'], $this->imagesConfig['small']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_INSIDE);
			}	
				
		} else {
			$value["lead_image"] = '';
		}
		
		if (!empty($value["text_image"])) {
				
				
			if (!is_dir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'big/')) {
				@mkdir(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'big/');
				@chmod(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'big/', 0777);
			}
				
			$in = AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . $value["text_image"];
		
			$this->image = &loadLibClass('image');
				
			if (file_exists(mkFileName($in, '', 'big/'))) {
				list($w, $h) = getimagesize(mkFileName($in, '', 'big/'));
				if ($w > $this->imagesConfig['big']['width']) {
					$this->image->resizeImg($in, mkFileName($in, '', 'big/'), $this->imagesConfig['big']['width'], $this->imagesConfig['big']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_INSIDE | IR_CONST_WIDTH);
				}
			} else {
				$this->image->resizeImg($in, mkFileName($in, '', 'big/'), $this->imagesConfig['big']['width'], $this->imagesConfig['big']['height'], IR_DONT_RESIZE_SMALLER | IR_TOUCH_FROM_INSIDE | IR_CONST_WIDTH);
			}	
		} else {
			$value["text_image"] = '';
		}
		
		$id = saveValuesInDb($this->dbTable, $value, $id);
		$siteLangs = getSiteLangs();	
		deleteFromDbById($this->dbTableLang, $id, 'coupon_id');
		foreach ($siteLangs as $key => $values) {
			
			$pageTitle = (empty($langValues['page_url'][$values['lang']]) ? convertUrl($langValues["title"][$values['lang']]) : $langValues['page_url'][$values['lang']]);
			
			$data = array(
				'coupon_id' => $id,
				'lang' => $values['lang'],
				'title' => $langValues['title'][$values['lang']],
				'page_url' => $this->checkForUniqUrl($pageTitle, $id, $values['lang']),
				'lead' => $langValues['lead'][$values['lang']],
				'text' => $langValues['text'][$values['lang']],
				'page_title' => $langValues['page_title'][$values['lang']],
				'page_keywords' => $langValues['page_keywords'][$values['lang']],
				'page_description' => $langValues['page_description'][$values['lang']],
				'company' => $langValues['company'][$values['lang']],
			);
			saveValuesInDb($this->dbTableLang, $data);	
		}
		return $id;
	}
    
	public function checkForUniqUrl($url, $id, $lang, $i = 1) {
		$dbQuery = "SELECT `coupon_id` FROM `" . $this->dbTableLang . "` WHERE `page_url` = '" . mres($url) . "' AND `lang` = '" . $lang . "'";
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows()) {
			if ($query->getOne() == $id) {
				return $url;
			} else {
				return $this->checkForUniqUrl($url . '-' . $i, $id, $lang, $i++);
			}
		} else {
			return $url;
		}
	
	}
    
}
?>