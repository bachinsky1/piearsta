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
 * CMS news/textlist module
 * Admin path. Edit/Add/Delete and other actions.
 * 27.07.2010
 */

class newsData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 * $uploadFolder - string	upload folder
	 */
	public $result;
	public $uploadFolder = 'news/';
	public $imagesConfig = array(
								'small' => array('width' => '220', 'height' => '140'),
								'big' => array('width' => '700', 'height' => '296'),
							);
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "news";
		
		$this->dbTable = 'mod_news';
		
	}
	
	/**
	 * Get all data from db and create module table
	 */
	public function showTable() {
		
		if (getP('content_id')) {
				
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
					'params' => array('news')
				),
				"title" => array(
					'sort' => false,
					'title' => gLA('m_title','Title'),
					'function' => 'clear',
					'fields'	=> array('title')
				),
				"date_to" => array(
					'sort' => false,
					'title' => gLA('m_date','Date'),
					'function' => 'convertDate',
					'fields'	=> array('date_to'),
					'params' => array('d-m-Y')
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
				)
			);

			if (getP("itemsFrom") !== false) {
				$_SESSION['ad_' . $this->getModuleName()]["itemsFrom"] = getP("itemsFrom");
			}	
			elseif (isset($_SESSION['ad_' . $this->getModuleName()]["itemsFrom"])) {
				$_POST["itemsFrom"] = $_SESSION['ad_' . $this->getModuleName()]["itemsFrom"];
			}			
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . $this->dbTable . "` WHERE `content_id` = '" . mres(getP('content_id')) . "'" . $this->moduleTableSqlParms("created", "DESC");
			$query = new query($this->db, $dbQuery);
			
			
			$result["rCounts"] = $this->getTotalRecordsCount(false);

			// Create module table
			$this->cmsTable->createTable($table, $query->getArray());
			$result["html"] = $this->cmsTable->returnTable;
				
			return $result;
		}	
		
	}
	
	public function getDocuments($id) {
		if ($id) {
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . $this->dbTable . "` n
									WHERE 1
										AND ((n.enable = '1' AND n.date_from = '' AND n.date_to = '') OR (n.date_from <= '" . time() . "' AND (n.date_to >= '" . time() . "' OR n.date_to = '')))
									 	AND n.`content_id` = '" . mres($id) . "'";
			$query = new query($this->db, $dbQuery);
			
			return $query->getArray();
		}
	}
	
	/**
	 * Enable or disable
	 * 
	 * @param int/array 	news id
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
	 * Delete news from DB
	 * 
	 * @param int/Array 	news id
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
	 * Edit news in DB
	 * 
	 * @param int 	news id, it's need if we are editing
	 */
	public function edit($id = "") {
		
		$data = array();

		if(isset($id) && intval($id)) {
			
			/**
			 * Getting all information from DB about this module
			 */
			$dbQuery = "
				SELECT * 
				FROM `" . $this->dbTable . "` 
				WHERE `id` = '" . $id . "'
				LIMIT 0,1
			";
			$query = new query($this->db, $dbQuery);		
			
			$data["edit"] = $query->getrow();
			$data["edit"]["files"] = unserialize($data["edit"]["files"]);
			$data["edit"]["links"] = unserialize($data["edit"]["links"]);
			
		} 
		else {
			$data["edit"]["content_id"] = getG('content_id'); 
		}
		
		$data['edit']['uploadFolder'] = $this->uploadFolder;
		
		return $data;
	}
	
	/**
	 * Saving information in DB
	 * 
	 * @param int	 id, it's need if we are editing language
	 * @param array  information values
	 */
	public function save($id, $value) {

		$files = getP('files');
		$links = getP('links');
		
		$value["updated"] = time();
		$value["files"] = serialize($files);
		$value['links'] = serialize($links);
		
		if (!$id) {
			$value["created"] = time();
			$value["owner"] = $this->cmsUser->userData['id'];
			$value["sort"] = $this->getNextSort($value["content_id"]);
		}
        
        if(!$id){
            $id = 0;
        }
		
		if ($value["page_url"]) {
			$value["page_url"] = $this->checkForUniqUrl($id, convertUrl($value["page_url"]));
		} else {
			$value["page_url"] = $this->checkForUniqUrl($id, convertUrl($value["title"]));
		}
		
		if ($value["date_to"]) {
			$value["date_to"] = strtotime($value["date_to"]);
		}
	
		if (isset($value["lead_image"]) && $value["lead_image"]) {
			
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
			
			$extension = strrchr($in, '.');
			rename(mkFileName($in, '', 'small/'), AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'small/' . $value["page_url"] . $extension);
			$value["lead_image"] = $value["page_url"] . $extension;		
			
		} else {			
			$value["lead_image"] = '';
			$value["lead_image_alt"] = '';
		}
		
		if (isset($value["text_image"]) && $value["text_image"]) {
			
			
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
			
			$extension = strrchr($in, '.');
			rename(mkFileName($in, '', 'big/'), AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . 'big/' . $value["page_url"] . $extension);
			$value["text_image"] = $value["page_url"] . $extension;
			
			
		} else {
			$value["text_image"] = '';
			$value["text_image_alt"] = '';
		}
		
		//delete old images from folder
		if($id != 0){
			$dbQuery = "SELECT `lead_image`, `text_image` FROM `" . $this->dbTable . "` WHERE `id` = " . $id;
			$query = new query($this->db, $dbQuery);
			$images = $query->getRow();
			
			if($images["lead_image"] != '' && $images["lead_image"] != $value["lead_image"]){
				deleteFileFromFolder(AD_SERVER_UPLOAD_FOLDER.$this->uploadFolder.$images["lead_image"]);
				deleteFileFromFolder(AD_SERVER_UPLOAD_FOLDER.$this->uploadFolder.'small/'.$images["lead_image"]);
			}
			if($images["text_image"] != '' && $images["text_image"] != $value["text_image"]){
				deleteFileFromFolder(AD_SERVER_UPLOAD_FOLDER.$this->uploadFolder.$images["text_image"]);
				deleteFileFromFolder(AD_SERVER_UPLOAD_FOLDER.$this->uploadFolder.'big/'.$images["text_image"]);
			}

            saveValuesInDb($this->dbTable, $value, $id);
		} else {

            $id = saveValuesInDb($this->dbTable, $value);
        }

		return $id;
	}
	
	/**
	 * Check for uniq url in documents
	 * If url already exist, add next number for it.
	 * Example url wil be changed to url-2
	 * 
	 * @param string	page url
	 * @param int		next number
	 */
	private function checkForUniqUrl($id, $u, $number = '') {
		$dbQuery = "SELECT 1 FROM `" . $this->dbTable . "` 
			WHERE `page_url` = '" . $u . "' AND `id`!=".$id;
		$query = new query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			
			if ($number) {
				
				$u = explode("-", $u);
				unset($u[count($u) - 1]);
				$u = implode("-", $u) . '-' . ++$number;
				return $this->checkForUniqUrl($id, $u, $number);
				
			} else {
				$u .= '-2';
				return $this->checkForUniqUrl($id, $u, 2);
			}
			
		} else {
			return $u;
		}
	}
	
	/**
	 * Getting next sort id for add new new
	 * 
	 * @param int		content news id, default value = 0
	 */
	private function getNextSort($cId = "0") {
		
		$dbQuery = "SELECT MAX(sort) AS sort FROM `" . $this->dbTable . "` 
													WHERE `content_id` = '" . $cId . "'";
		$query = new query($this->db, $dbQuery);
		
		return $query->getOne() + 1;
	}
	
	/**
	 * Changing news sort order
	 * 
	 * @param int		news id
	 * @param string	sort changing value
	 */
	public function changeSort($id, $value) {
		
		$dbQuery = "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = '" . $id . "'";
                $query = new query($this->db, $dbQuery);
		$content = $query->getrow();
		
		if ($value == "down") {
			$sqlParm = "<";
			$sqlParm2 = "DESC";	
		}
		else {
			$sqlParm = ">";
			$sqlParm2 = "ASC";
		}
		
		$dbQuery = "SELECT `id`, `sort` FROM `" . $this->dbTable . "` WHERE `sort` " . $sqlParm . " '" . $content['sort'] . "' AND `content_id` = '" . $content['content_id'] . "' ORDER BY `sort` " . $sqlParm2 . " LIMIT 0,1";
		$query->query($this->db, $dbQuery);
		if ($query->num_rows() > 0) {
			$info = $query->getrow();
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . $content['sort'] . "' WHERE `id` = '" . $info['id'] . "'";
			$query->query($this->db, $dbQuery);
			
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `sort` = '" . $info['sort'] . "' WHERE `id` = '" . $id . "'";
			$query->query($this->db, $dbQuery);
			
		}
	}
	
}
?>