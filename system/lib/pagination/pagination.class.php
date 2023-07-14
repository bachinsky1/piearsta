<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		Nikita Malgins <nikita@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		2
 */
// ------------------------------------------------------------------------

/**
 * Pagination class
 * returns formatted html paginator
 * 17.06.2011
 */
class pagination {

    /**
     * @var int
     */
    public $perPage = 20;

    /**
     * @var int
     */
    public $sidePageCount = 3;
    public $url;
    public $docUrl = "";
    public $tpl;
    public $pager;

    /**
     * Class constructor
     */
    public function __construct() {

        $this->tpl = &loadLibClass('tmpl');
        $this->cfg = &loadLibClass('config');
        $this->name = get_class($this);
    }

    /**
     * Returns HTML for pager HTML, ready to be assigned to smarty variable
     *
     * @param int    $totalCnt
     * @param int    $curPage
     * @param array  $pager
     * @param string $showMsg
     * @param string $orderHTML
     * @param bool   $resetPager
     * 
     * @return string
     */
    public function getPagerHtml($totalCnt, $curPage, $pager = array(), $showMsg = '', $orderHTML = "", $resetPager = false) {

        if (!empty($pager)) {
            $this->pager = $pager;
        }

        if ((empty($this->pager) || $resetPager) && $totalCnt !== 0) {
            $this->pager = $this->getPager($totalCnt, $curPage, $showMsg);
        }

        $toTmpl = array(
            "pager" => $this->pager,
            "showOrder" => !empty($orderHTML) ? true : false,
            "orderHTML" => $orderHTML,
            "url" => $this->url
        );

        $html = $this->tpl->output(AD_LIB_FOLDER . $this->name . '/tmpl/pager', $toTmpl, false, false);
        return $html;
    }

    /**
     * Function returns array with pager parameters
     *
     * @param int    $totalCnt
     * @param int    $curPage
     * @param string $showMsg
     *
     * @return array
     */
    public function getPager($totalCnt, $curPage, $showMsg = '') {

        $this->pager = array();

        if ($totalCnt > 0) {

            // Parsing url params and leaving used
            $urlParams = $this->cfg->get('parseUrl');
            $addUrl = "";
            if (is_array($urlParams) && !empty($urlParams)) {
                foreach ($urlParams as $get) {
                    $value = getG($get);
                    if ($value !== false && strpos($addUrl, $get . ":" . $value . "/") === false) {
                        // param page is set always where is pager
                        // plus can be set param from message
                        $permitted = array(gL('urlPage'), 'page');
                        if (getG("category")) {
                            $permitted[] = gL("searchWhat");
                        }
                        if (!in_array($get, $permitted)) {
                            $addUrl .= $get . ":" . $value . "/";
                        }
                    }
                }
            }
            $this->pager['addUrl'] = $addUrl;

            $this->pager['docUrl'] = $this->docUrl;

            $this->pager['showMsg'] = $showMsg ? $showMsg : $this->perPage;
            $this->pager['records'] = $totalCnt;
            $this->pager['sidePageCount'] = $this->sidePageCount + 1;
            $this->pager['curPage'] = $curPage;

            $this->pager['from'] = ($curPage - 1) * $this->pager['showMsg'] + 1;
            $this->pager['to'] = ($this->pager['from'] + $this->pager['showMsg'] - 1) > $totalCnt ? $totalCnt : ($this->pager['from'] + $this->pager['showMsg'] - 1);
            $this->pager['pages'] = ceil($totalCnt / $this->pager['showMsg']);

            if ($curPage > 1) {
                $this->pager['previous'] = $curPage - 1;
            }

            if ($this->pager['records'] > $this->pager['to']) {
                $this->pager['next'] = $curPage + 1;
            }
        }

        return $this->pager;
    }

}

?>