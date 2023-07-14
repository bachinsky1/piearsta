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
 * News module
 * 30.06.2010
 */
class couponsData extends Module {

    private $config = array('uploadFolder' => 'coupons/');
    private $itemsPerPage = 10;
    private $countStartpage = 3;

    /**
     * Class constructor
     */
    public function __construct() {

        parent :: __construct();
        $this->name = 'coupons';
        
        if ($this->cfg->getData('docPerPg')) {
        	$this->itemsPerPage = $this->cfg->getData('docPerPg');
        }
        
    }

    /**
     * Load startpage news
     */
    public function loadStarpage() {

    	$dbQuery = "SELECT c.*, cd.*
							FROM `mod_coupons` c
    							LEFT JOIN mod_coupons_data cd ON (c.id = cd.coupon_id AND cd.lang = '" . $this->getLang() . "')
							WHERE 1 
								AND c.enable = 1 
                         	ORDER BY c.`created` DESC
							LIMIT 0," . $this->countStartpage;
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {

            	$coupons = $query->getArray();
  
            	$this->setPData($coupons, "coupons");
            	$this->setPData($this->config, "couponConfig");
            	
            	$this->tpl->assign("COUPONS_MODULE", $this->tpl->output("startpage", $this->getPData()));
            }
        
        return $this;
    }

    public function showOne() {
		
    	$dbQuery = "SELECT SQL_CALC_FOUND_ROWS cd.*, c.*, c.id AS id
        						FROM `mod_coupons` c
    								LEFT JOIN mod_coupons_data cd ON (c.id = cd.coupon_id AND cd.lang = '" . $this->getLang() . "')
								WHERE 1
									#AND c.enable = 1
    								AND cd.page_url = '" . mres(getG('docUrl')) . "'
								LIMIT 1";
    	$query = new query($this->db, $dbQuery);
    	if ($query->num_rows() > 0) {
    		$coupon = $query->getrow();
    		
    		$coupon['files'] = unserialize($coupon['files']);
    		if (!empty($coupon['files'])) {
    			for ($i = 0; $i < count($coupon['files']); $i++) {
    				$coupon['files'][$i]['ext'] = substr(strtolower(strrchr($coupon['files'][$i]['fileName'], ".")), 1);
    				$coupon['files'][$i]['size'] = @showFileSize(AD_SERVER_UPLOAD_FOLDER . $this->config['uploadFolder'] . $coupon['files'][$i]['fileName'], $this->cfg->siteData['showFileSize']);
    				$coupon['files'][$i]['path'] = $this->config['uploadFolder'];
    			}
    		}
    		
    		
    		$coupon['links'] = unserialize($coupon['links']);
    		
    		if ($coupon['page_title']) {
    			$this->setPData(array('pageTitle' => $coupon['page_title']), 'web');
    		} 

    		if ($coupon['page_keywords']) {
    			$this->setPData(array('pageKeywords' => $coupon['page_keywords']), 'web');
    		}

    		if ($coupon['page_description']) {
    			$this->setPData(array('pageDescription' => $coupon['page_description']), 'web');
    		}
    		
    		$this->setPData($coupon, "coupon");
    		$this->setPData($this->config, "couponConfig");
    		 
    		$this->tpl->assign("TEMPLATE_COUPONS_MODULE", $this->tpl->output("item", $this->getPData()));
    	} else {
    		openDefaultPage();
    	}
    }

    public function showList() {
        $curPage = 1;
		if (getG('page') && getG('page') > 0) {
		
			$curPage = (int)getG('page');
		}
		
		$limit = " LIMIT " . ($curPage - 1) * $this->itemsPerPage . "," . $this->itemsPerPage;
        
        $dbQuery = "SELECT SQL_CALC_FOUND_ROWS c.*, cd.*
        						FROM `mod_coupons` c
    								LEFT JOIN mod_coupons_data cd ON (c.id = cd.coupon_id AND cd.lang = '" . $this->getLang() . "')
								WHERE 1
									AND c.enable = 1 
								ORDER BY `created` DESC" . $limit;
		$query = new query($this->db, $dbQuery);

		$coupons = $query->getArray();
		
		$this->setPData($coupons, "coupons");
		$this->setPData($this->config, "couponConfig");
		$this->setPData($this->setPager(), "pager");
		
		$this->tpl->assign("TEMPLATE_COUPONS_MODULE", $this->tpl->output("list", $this->getPData()));
        
        return $this;
    }
    
	/**
	 * 
	 * Set products pager
	 */
	public function setPager() {
		
		$curPage = 1;
		if (getG('page') && getG('page') > 0) {
		
			$curPage = (int)getG('page');
		}

		$query = new query($this->db, "SELECT FOUND_ROWS()");
		
		// Load paginator lib
		$pager = &loadLibClass('paginator');
		$this->pager = $pager
							->set_items_on_page($this->itemsPerPage)
							->advancedPages($query->getOne(), $curPage);

		return $this->pager;
	}
    
}

?>