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
 * CMS sitemap module
 * Admin path. show sitemap
 * This module is used in popup window to select page
 * 22.10.2008
 */

class sitemapData extends Module_cms {
	
	/**
	 * $result - Mixed, used with return in functions
	 */
	public $result;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "sitemap";
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
		$fSearch = getP("filterSearch");
		
		$sqlWhere = $fLang ? " AND `lang` = '" . $fLang . "'" : "";
		$sqlWhere .= $fCountry ? " AND `country` = '" . $fCountry . "'" : "";
		$sqlWhere .= $fSearch ? " AND (`name` LIKE '%" . $fSearch . "%' OR `description` LIKE '%" . $fSearch . "%' OR `page_title` LIKE '%" . $fSearch . "%' OR `title` LIKE '%" . $fSearch . "%' OR `content` LIKE '%" . $fSearch . "%' OR `keywords` LIKE '%" . $fSearch . "%')" : "";
		
		
		/**
		 * Getting all information from DB about this module
		 */

		$dbQuery = "
			SELECT * 
			FROM `ad_content` 
			WHERE
		";
		$dbQuery .= " `parent_id` = '" . $parentId . "'" ;
		$dbQuery .= $sqlWhere ;
		$dbQuery .= "ORDER BY `sort`, `title` ASC";
		$query = new query($this->db, $dbQuery);
		
		if ($query->num_rows() > 0) {
			
			$returnHtml .= ($parentId == 0 ? '<ul id="sitemapTree">' : '<ul>');
			
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
					
				$returnHtml .= '<li id="node' . $query->field('id') . '" title="' . $query->field('title') . '">';			
				
				$returnHtml .= '<a href="#"' . $class . '>' . $query->field('title') . '</a>';
				
				$returnHtml .= $this->showList($query->field('id'));
				$returnHtml .= '</li>';
				
			}
			
			$returnHtml .= '</ul>';
		}
		
		
		return $returnHtml;
	}
	
}
?>