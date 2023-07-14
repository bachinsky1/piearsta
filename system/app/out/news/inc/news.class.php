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
class newsData extends Module {

    private $newsConfig = array('uploadFolder' => 'news/');
    private $DescWordCount = 25;
    private $firstChars = 250;
    private $itemsPerPage = 10;
    private $newsCountStartpage = 3;

    /**
     * Class constructor
     */
    public function __construct() {

        parent :: __construct();
        $this->name = 'news';
        
        if ($this->cfg->getData('docPerPg')) {
        	$this->itemsPerPage = $this->cfg->getData('docPerPg');
        }
        
    }

    /**
     * Load startpage news
     */
    public function loadStarpage() {

        $enabled = $this->cfg->getData('newsEnable');
        $page = $this->cfg->getData('newsCat');
        
        if ($enabled && $page) {

            $dbQuery = "SELECT n.*
							FROM `mod_news` n, `ad_content` c
							WHERE 1 
            					AND n.enable = '1'
								AND c.id = n.content_id
								AND c.enable = 1 
								AND c.active = 1
								AND c.lang = '" . $this->getLang() . "'
								AND c.country = '" . $this->getCountry() . "'
								AND c.id = '" . getMirror($page) . "'
                         	ORDER BY `created` DESC
							LIMIT 0," . $this->newsCountStartpage;
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {
                $this->setPData($page, 'newsPageId');
                while ($row = $query->getrow()) {
                	
                	if ($row['date_to']) {
                		$row['date_to_formated'] = date('Y.', $row['date_to']). gL('gada') . ' ' . date('d.', $row['date_to']) . gL('month_' . date('F', $row['date_to']));
                	}
                	
                	$news[] = $row;
                }
                $this->setPData($news, "news");
                $this->setPData($this->newsConfig, "newsConfig");

                $this->tpl->assign("TEMPLATE_NEWS_MODULE", $this->tpl->output("startpage", $this->getPData()));
            }
        }
        
        return $this;
    }


    /**
     * Load selected new
     */
    public function showOne() {

        $dbQuery = "SELECT * FROM `mod_news` n
        					WHERE 1
								AND n.enable = '1'
								AND n.content_id = '" . $this->getCData('id') . "'
								AND n.page_url = '" . mres(getG('docUrl')) . "'";

        $query = new query($this->db, $dbQuery);
        if ($query->num_rows() > 0) {

            $news = $query->getrow();
            $news['files'] = unserialize($news['files']);
            if (!empty($news['files'])) {
            	for ($i = 0; $i < count($news['files']); $i++) {
            		$news['files'][$i]['ext'] = substr(strtolower(strrchr($news['files'][$i]['fileName'], ".")), 1);
            		$news['files'][$i]['size'] = @showFileSize(AD_SERVER_UPLOAD_FOLDER . $this->newsConfig['uploadFolder'] . $news['files'][$i]['fileName'], $this->cfg->siteData['showFileSize']);
            		$news['files'][$i]['path'] = $this->newsConfig['uploadFolder'];
            	}
            }
            
            if ($news['date_to']) {
            	$news['date_to_formated'] = date('Y.', $news['date_to']). gL('gada') . ' ' . date('d.', $news['date_to']) . gL('month_' . date('F', $news['date_to']));
            }
            

            $news['links'] = unserialize($news['links']);

            if ($news['page_title']) {
                $this->setPData(array('pageTitle' => $news['page_title']), 'web');
            }  else {
                $this->setPData(array('pageTitle' => $news['title'].", ".gL("defaultPageTitle")), 'web');
            }
			
			/* KEYWORDS */
            if ($news['page_keywords']) {
                $this->setPData(array('pageKeywords' => $news['page_keywords']), 'web');
            }
			else if (($news['lead'] != '') || ($news['text'] != '')) {
                $text = $news['lead'] . $news['text'];
                $text = trim(strip_tags($text));
                if ($text) {
                	$this->setPData(array('pageKeywords' => generateLimitedWordsText($text, $this->DescWordCount, ',')), 'web');	
                } else {
                	$this->setPData(array('pageKeywords' => gL("defaultPageKeywords")), 'web');
                }
				
			}
			else {
				$this->setPData(array('pageKeywords' => gL("defaultPageKeywords")), 'web');
			}
			
			/* PAGE DESCRIPTION */
			if ($news['page_description']) {
				$this->setPData(array('pageDescription' => $news['page_description']), 'web');
			}
			else if ($news['lead'] != '' || $news['text'] != '') {
				$text = strip_tags($news['lead'] . $news['text']);
				$text = trim($text);
				if ($text) {
					$this->setPData(array('pageDescription' => generateLimitedWordsText($text, $this->DescWordCount) ), 'web');	
				} else {
					$this->setPData(array('pageDescription' => gL("defaultPageDescription")), 'web');
				}
				
			}
			else {
				$this->setPData(array('pageDescription' => gL("defaultPageDescription")), 'web');
			}

            $this->setPData($news, "news");
            $this->setPData(true, "opendoc");
            $this->setPData($this->newsConfig, "newsConfig");
        } else {
            openDefaultPage();
        }

        $this->tpl->assign("TEMPLATE_NEWS_MODULE", $this->tpl->output("item", $this->getPData()));
        
        return $this;
    }

    /**
     * Get all news by category/content id
     * 
     */
    public function showList() {
        $curPage = 1;
		if (getG('page') && getG('page') > 0) {
		
			$curPage = (int)getG('page');
		}
		
		$limit = " LIMIT " . ($curPage - 1) * $this->itemsPerPage . "," . $this->itemsPerPage;
        
        $dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `mod_news` n
								WHERE 1
									AND n.enable = '1'
									AND n.content_id = '" . $this->getCData('id') . "'
								ORDER BY `created` DESC" . $limit;
		$query = new query($this->db, $dbQuery);

        if ($query->num_rows() == 1 && $curPage < 2) {
            $query->getrow();

            $_GET['docUrl'] = $query->field('page_url');
            $this->showOne();
        } else {
            
            $news = $query->getArray();

            foreach($news as $k=>$newsItem){
            	$news[$k]['lead'] = $this->limitWords($newsItem['lead'], 50);
            	
            	if ($news[$k]['date_to']) {
            		$news[$k]['date_to_formated'] = date('Y.', $news[$k]['date_to']). gL('gada') . ' ' . date('d.', $news[$k]['date_to']) . gL('month_' . date('F', $news[$k]['date_to']));
            	}
            }

            $this->setPData($news, "news");
            $this->setPData($this->newsConfig, "newsConfig");
            $this->setPData($this->setPager(), "pager");

            $this->tpl->assign("TEMPLATE_NEWS_MODULE", $this->tpl->output("list", $this->getPData()));
        }
        
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
    
	function limitWords($string, $length = 25, $endstring = ""){
		$string = str_replace('"', "", $string);
		$string = str_replace("'", "", $string);
		$string = str_replace(array("\n", "\r", "\t"), array(" ", "", ""), strip_tags($string));
		
		// Explode text in words array
		$words = explode(' ', $string);
		
		// Simple check for empty or whitespace word, if exist remove them
		foreach ($words as $key => $value) {
			if ($value == '' || $value == ' ') unset($words[$key]);
		}
		
		// If contains more than specified length return max length words
		if (count($words) > $length) {
			return implode(' ', array_slice($words, 0, $length)) . $endstring;
		}
		// Else return all text string
		else {
			return $string;
		}
	}
}

?>