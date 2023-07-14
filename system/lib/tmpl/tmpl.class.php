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
 * General templates class.
 * Use smarty template engine. 
 * 19.02.2010
 */
class Tmpl {
		
	private $tmplDir;
	private $smarty;
	private $template;

    /**
     * Tmpl constructor.
     */
	public function __construct() {
		$this->smarty = new SmartySetup();
	}

    /**
     * @return array
     */
	public function get_template_vars() {
	    return $this->smarty->get_template_vars();
    }

    /**
     * Get template directory
     *
     * @return string
     */
    public function getTmplDir() {
	    return $this->smarty->template_dir;
    }
		
	/**
	 * Set Template directory
	 *
	 * @param string	 directory
	 */
	public function setTmplDir($dir) {
		$this->smarty->template_dir = $this->tmplDir = $dir;
	}
	
	/**
	 * Assing template values
	 *
	 * @param string
	 * @param string
	 */
	public function assign($name, $value = false) {
		if(is_array($name)) {
			$this->smarty->assign($name);
		}
		else {
			$this->smarty->assign($name, $value);
		}  
	}
	
	/**
	 * Smarty display() function
	 *
	 */
	public function display(){
		$this->smarty->display($this->tmplDir . $this->template);
	}
	
	/**
	 * Smarty fetch() function
	 *
	 */
	public function fetch(){
		if ($this->template) {
			return $this->smarty->fetch($this->tmplDir . $this->template);
		}		
	}
	
	/**
	 * Set template
	 *
	 * @param string
	 */
	public function setTmpl($tmpl) {
		if($tmpl == '') {
			return;
		}
		
		if (strpos($tmpl, '.html') === false){
			$tmpl .= '.html';
		}
		
		$this->template = $tmpl;
	}
	
		
	/**
	 * Read new style (Smarty) template and assign appropriate values to the template
	 * Return fetched template data.
	 *
	 * @param string $template
	 * @param array $args
	 * @param bool
	 * @return String parsed template
	 */
	public function output($template, $args = NULL, $clear = false, $useDir = true) {

		$template = $useDir ? $this->tmplDir . $template : $template;
		
		if (strpos($template, '.html') === false){
			$template .= '.html';
		}

		if (!file_exists($template)) {
			showError('File ' . $template . ' does not exist!', 500);
		}
			
		if (filesize($template) == 0) {
			return;
		}

		if ($clear) {
			$this->smarty->clear_all_assign();	
		}
				
		if($args !== NULL) {
			$this->smarty->assign($args);
		} 

		$r = $this->smarty->fetch($template);

		return $r;
	}		
}

class SmartySetup extends Smarty {
		
	public function __construct() {

		$this->compile_dir = AD_SMARTY_FOLDER . 'tmpl_c';	
        $this->template_dir = AD_SMARTY_FOLDER . 'tmpl';
        $this->config_dir = AD_SMARTY_FOLDER . 'config';
        $this->cache_dir = AD_SMARTY_FOLDER . 'cache';
        
        if(!file_exists($this->compile_dir)){
        	mkdir($this->compile_dir, 0777, true);
        }
        
        if(!file_exists($this->config_dir)){
          	mkdir($this->config_dir, 0777, true);
        }
        
        if(!file_exists($this->cache_dir)){
			mkdir($this->cache_dir, 0777, true);
        }

		$this->left_delimiter  = "{{";
        $this->right_delimiter = "}}";

        // Adding some custom functions to smarty
        $this->register_modifier('gL', 'gL');
        $this->register_modifier('gLParam', 'gLParam');
		$this->register_modifier('gLA', 'gLA');
        $this->register_modifier('getLink', 'getLink');
        $this->register_modifier('getMirror', 'getMirror');
        $this->register_modifier('getLM', 'getLinkByMirror');
        $this->register_modifier('getLM2', 'getLM');
        $this->register_modifier("clear", "clear");
        $this->register_modifier("hsc", "hsc");
        $this->register_modifier("uec", "urlencode");
        $this->register_modifier("getSD", "getSiteData");
        $this->register_modifier("getDocUrl", "getDocUrl");
	}		
}