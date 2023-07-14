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
 * banners module
 */

class bannersData extends Module {

	
	/**
	 * Class constructor
	 */
	public function __construct() {		
		
		parent :: __construct();
		$this->name = 'banners';
		$this->dbTable = $this->cfg->getDbTable('banners');
		$this->imagesConfig = $this->cfg->getImageConfig('banners');
	}
	
	public function getBanners() {
		$result = array();
		
		$dbQuery = "SELECT *
							FROM `" . $this->dbTable . "`
							WHERE 1 
								AND `enabled` = '1' 
								AND `lang` = '" . $this->getLang() . "'
							ORDER BY `created` DESC";
		$query = new query($this->db, $dbQuery);
		while ($row = $query->getrow()) {
			
			if ($row['doc_id']) {
				$row['url'] = getLink($row['url_id']) . getDocUrl($row['doc_id']) . '.html';
			} elseif ($row['url_id']) {
				$row['url'] = getLink($row['url_id']);
			}
			
			if ($row['image']) {
				$row['image'] = AD_UPLOAD_FOLDER . $this->imagesConfig['original']['upload_path'] . $row['image'];
			}
			
			$result[$row['slot']][] = $row;
			
		}
		
		$banners = array();
		foreach ($result AS $slot => $data) {
			
			if (count($result[$slot]) == 1) {
				$banners[$slot] = $data[0];
			} else {
				$cnt = count($result[$slot]) - 1;
				$randomId = rand(0, $cnt);
				$banners[$slot] = $data[$randomId];
			}
			
		}
		
		$this->tpl->assign("BANNERS", $banners);	
	
	}	
}

?>