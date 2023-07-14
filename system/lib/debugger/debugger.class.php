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
 * Debugger
 * 06.04.2010
 */
class Debugger extends Base {
 	
	public $returnHtml = '';
	private $phpDebug = array();
	
	/** 
	 * Constructor
	 */
	public function __construct() {
		$this->getCssStyles();
		$this->getJS();
	}
	
	/**
	 * Loading and creating debugger
	 *
	 */
	public function load() {
		
		$this->createDebuger();
	}
	
	/**
	 * Creating all css styles
	 *
	 */
	private function getCssStyles() {
		$this->returnHtml .= '<style type="text/css">
								.pos-top-right {
									position: absolute;
									top: 0;
									right: 0;
									z-index: 10000;
									border:1px solid #D7D7D7;
								} 
								.debTabs  {
									background:url("/admin/images/design/line-bg.gif") repeat-x scroll 0 bottom transparent;
									list-style:none outside none;
									margin:0 0 10px;
									overflow:hidden;
									padding:0 9px;
									float: right;
								}
								.debTabs li{
					                padding:0;
					                margin:0 1px 0 0;
					                float:left;
				                }
				                .debTabs li a{
				                    padding:4px 6px;
				                    margin:0 1px 0 0;
				                    float:left;
				                    display:block;
				                    color:#000;
				                    font-size:12px;
				                    text-decoration:none;
				                    background:#F1F1F1;
				                    border:1px solid #D7D7D7;
				                    border-bottom:1px solid #BBB;
			                    }
			                    .debTabs li.active a{
				                    background:#FFF;
				                    border:1px solid #BBB;
				                    border-bottom:1px solid #FFF;
			                    }
			                    .debTabs li a:hover{
			                   		background:#DDD;
			                    }
			                    .tabContainer {
									background:#FFF;
			                    	padding-top:24px;
								}
			                    .debContent {
			                    	display:none;
								}
								.debContent active {
									width: 800px;
									display:block;
								}
							</style>';
	}
	
	/**
	 * Creating all js scripts
	 *
	 */
	private function getJS() {
		$this->returnHtml .= '<script type="text/javascript">
								$(document).ready(function(){
									$(\'ul.debTabs a\').click(function() {
										
										var curChildIndex = $(this).parent().prevAll().length + 1;
										$(this).parent().parent().children(\'.active\').removeClass(\'active\');
										$(this).parent().addClass(\'active\');
										$(this).parent().parent().next(\'.tabContainer\').children(\'.active\').fadeOut(\'fast\',function() {
											$(this).removeClass(\'active\');
											$(this).parent().children(\'div:nth-child(\'+curChildIndex+\')\').fadeIn(\'normal\',function() {
												$(this).addClass(\'active\');
											});
										});
										return false;
									});
									document.onkeydown = debugKeyDown;
									
									function debugKeyDown(e) {
										if (!e) var e = window.event;
										if (e.altKey && e.shiftKey && e.keyCode == 68) {
											if ($(".pos-top-right").css("display") == "none") {
												$(".pos-top-right").show();
											} else {
												$(".pos-top-right").hide();
											}
											
										}
									}

								}); 
							</script>';
	}
	
	/**
	 * Creating and generating debugger
	 *
	 */
	private function createDebuger() {
		
		$this->returnHtml .= '<div class="pos-top-right" style="display:none">
						          <ul class="debTabs">
						              <li class="active"><a href="javascript:;">Time</a></li>
						              <li><a href="javascript:;">MySQL</a></li>
						              <li><a href="javascript:;">POST</a></li>
						              <li><a href="javascript:;">GET</a></li>
						              <li><a href="javascript:;">SESSION</a></li>
						              <li><a href="javascript:;">SERVER</a></li>
						              <li><a href="javascript:;">PHP</a></li>
						              <li><a href="javascript:;" onclick="$(\'.pos-top-right\').hide();">CLOSE</a></li>
						          </ul>
						          <div class="tabContainer">
						            <div class="debContent active">
						             ' . $this->getTimeDebug() . '
						            </div>
						            <div class="debContent">
						              ' . $this->getMysqlDebug() . '
						            </div>
						            <div class="debContent">
						              ' . $this->getPostDebug() . '
						            </div>
						            <div class="debContent">
						              ' . $this->getGetDebug() . '
						            </div>
						            <div class="debContent">
						               ' . $this->getSessionDebug() . '
						            </div>
						             <div class="debContent">
						               ' . $this->getServerDebug() . '
						            </div>
						            <div class="debContent">
						              ' . $this->getPhpDebug() . '
						            </div>
						           </div> 
							</div>';
	}
	
	/**
	 * Getting POST array info
	 *
	 */
	private function getPostDebug() {
		$r = '';
		
		if (isset($_POST) && count($_POST) > 0) {
			foreach ($_POST AS $k => $v) {
				$r .= '<b>' . $k . '</b>: ' . clearText($v) . '<br />';
			}
			return $r;
		}
	}
	
	/**
	 * Getting GET array info
	 *
	 */
	private function getGetDebug() {
		$r = '';
		
		if (isset($_GET) && count($_GET) > 0) {
			foreach ($_GET AS $k => $v) {
				$r .= '<b>' . $k . '</b>: ' . clearText($v) . '<br />';
			}
			return $r;
		}
	}
	
	/**
	 * Getting SESSION array info
	 *
	 */
	private function getSessionDebug() {
		if (count($_SESSION) > 0) {
			return pR($_SESSION, true);
		}
	}
	
	/**
	 * Getting SERVER array info
	 *
	 */
	private function getServerDebug() {
		if (isset($_SERVER) && count($_SERVER) > 0) {
			return pR($_SERVER, true);
		}
	}
	
	/**
	 * Adding php debbug
	 *
	 * @param mix	debug info
	 */
	public function addPhp($info) {
		$this->phpDebug[] = $info;
	}
	
	/**
	 * Getting php debbug
	 *
	 */
	private function getPhpDebug() {
		
		$r = '';
		$phpCnt = count($this->phpDebug); 
		if ($phpCnt > 0) {
			for ($i = 0; $i < $phpCnt; $i++) {
				$r .= $i . ' - ' . $this->phpDebug[$i] .  '<br />';
			}
		}

		return $r;
	}
	
	/**
	 * Getting time debbug
	 *
	 */
	private function getTimeDebug() {
		$wk = &loadLibClass('workTime');
		
		return "Total time: " . $wk->elapsedTime("start", "end");
	}
	
	/**
	 * Getting mysql debbug
	 *
	 */
	private function getMysqlDebug() {
		$mdb = &loadLibClass('db');
		
		$r = '';
		$totalTime = 0;
		$qCnt = count($mdb->queries);
		for ($i = 0; $i < $qCnt; $i++) {
			$totalTime += $mdb->qTimes[$i];
			$r .= $mdb->qTimes[$i] . '  -  ' . $i . ' - ' . $mdb->queries[$i] .  '<br />';
		}
		
		$r = 'Total Time: ' . $totalTime . '<br />' . 'Total Queries: ' . ++$i . '<br />' . $r;
		return $r;
	}
	
	/**
	 * toString method
	 *
	 */
	public function __toString() {
		return $this->returnHtml;
	}

}

?>