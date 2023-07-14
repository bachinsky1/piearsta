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

/**
 * Draw paginator class.
 * Use it in module where need page switches
 * 15.04.2008
 */

class Paginator {


	private $cur_item_on_page = 25;
	private $pager = array();
	
	/**
	 * $defaultMsgOnPage - int of default value of count msg on a page
	 */ 
	public $defaultMsgOnPage = 20;
	
	/**
	 * Draw simple pages block
	 * Example: next 40-48 / 48 previous
	 * 
	 * @param int		total msg count
	 * @param int		current page number
	 * @param int		show msg on a page
	 */
	public function simplePages($totalCnt, $curPage, $showMsg = '') {
		
		$pager = array();
		
		if ($totalCnt > 0) {
			
			$pager['showMsg'] = $showMsg ? $showMsg : $this->defaultMsgOnPage;
			$pager['records'] = $totalCnt;

			$pager['from'] = ($curPage - 1) * $pager['showMsg'] + 1;
			$pager['to'] = ($pager['from'] + $pager['showMsg'] - 1) > $totalCnt ? $totalCnt : ($pager['from'] + $pager['showMsg'] - 1);
			
			if ($curPage > 1) {
				$pager['previous'] = $curPage - 1;
			}
			
			if ($pager['records'] > $pager['to']) {
				$pager['next'] = $curPage + 1;
			}
		}
		
		return $pager;
	}
	
	/**
	 * 
	 * Get items on page
	 */
	public function get_items_on_page() {
		
		return $this->cur_item_on_page;
	}
	
	/**
	 * 
	 * Setting items on page
	 * 
	 * @param int	items per page
	 */
	public function set_items_on_page($items) {
			
		$this->cur_item_on_page = (int)$items;
		
		return $this;
	}
	
	/**
	 * Draw advanced pages block
	 * 
	 * @param int		total msg count
	 * @param int		current page number
	 */
	public function advancedPages($totalCnt, $curPage) {
		
		if ($totalCnt > 0) {
			
			if (ceil($totalCnt / $this->cur_item_on_page) < $curPage) {
				redirect(getLM(AD_MAINPAGE_MIRROR_ID));
			}
			
			$this->pager['showMsg'] = $this->cur_item_on_page;
			$this->pager['records'] = $totalCnt;

			$this->pager['from'] = ($curPage - 1) * $this->cur_item_on_page + 1;
			$this->pager['to'] = ($this->pager['from'] + $this->cur_item_on_page - 1) > $totalCnt ? $totalCnt : ($this->pager['from'] + $this->cur_item_on_page - 1);
			
			if ($curPage > 1) {
				$this->pager['previous'] = $curPage - 1;
			}
			
			if ($this->pager['records'] > $this->pager['to']) {
				$this->pager['next'] = $curPage + 1;
			}
			
			//math total pages.
			$this->pager['total_pages']  = ceil( $totalCnt / $this->cur_item_on_page );
			$this->pager['current_page'] = $curPage;
		}
		
		return $this->pager;
		
	}
	
}
?>