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
 * CSS Packer
 * 13.06.2011
 */

class CSSPacker {
	
	public $CSSName = NULL;
	protected $_options = null;    
    protected $_inHack = false;
	
    public function process($css) {
		
    	$css = str_replace("\r\n", "\n", $css);
		$css = preg_replace('@>/\\*\\s*\\*/@', '>/*keep*/', $css);
		$css = preg_replace('@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $css);
		$css = preg_replace('@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $css);
		$css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@', array($this, '_commentCB'), $css);
		$css = preg_replace('/\\s*{\\s*/', '{', $css);
		$css = preg_replace('/;?\\s*}\\s*/', '}', $css);
		$css = preg_replace('/\\s*;\\s*/', ';', $css);
		$css = preg_replace('/
				url\\(      # url(
				\\s*
				([^\\)]+?)  # 1 = the URL (really just a bunch of non right parenthesis)
				\\s*
				\\)         # )
				/x', 'url($1)', $css);
		$css = preg_replace('/
				\\s*
				([{;])              # 1 = beginning of block or rule separator 
				\\s*
				([\\*_]?[\\w\\-]+)  # 2 = property (and maybe IE filter)
				\\s*
				:
				\\s*
				(\\b|[#\'"])        # 3 = first character of a value
				/x', '$1$2:$3', $css);
		$css = preg_replace_callback('/
				(?:              # non-capture
				\\s*
				[^~>+,\\s]+  # selector part
				\\s*
				[,>+~]       # combinators
				)+
				\\s*
				[^~>+,\\s]+      # selector part
				{                # open declaration block
				/x'
				, array($this, '_selectorsCB'), $css);
		$css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i', '$1#$2$3$4$5', $css);
		$css = preg_replace_callback('/font-family:([^;}]+)([;}])/', array($this, '_fontFamilyCB'), $css);        
		$css = preg_replace('/@import\\s+url/', '@import url', $css);
		$css = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $css);
		$css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/', "$1\n$2{", $css);
		$css = preg_replace('/
			((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
			\\s+
			/x'
			, "$1\n", $css);
		$css = preg_replace('/:first-l(etter|ine)\\{/', ':first-l$1 {', $css);
		
		// TODO
		//$css = preg_replace_callback('#url\(([a-z0-9/_\.]+)\)#', array($this, '_image2base64'), $css);
              
		return trim($css);
    }
    
	protected function _selectorsCB($m) {
		return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
	}
    
	protected function _commentCB($m) {
		$hasSurroundingWs = (trim($m[0]) !== $m[1]);
		$m = $m[1]; 
        
		if ($m === 'keep') {
			return '/**/';
		}
        
		if ($m === '" "') {
			return '/*" "*/';
		}
		if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $m)) {
			return '/*";}}/* */';
		}
        
		if ($this->_inHack) {
			if (preg_match('@
					^/               # comment started like /*/
					\\s*
					(\\S[\\s\\S]+?)  # has at least some non-ws content
					\\s*
					/\\*             # ends like /*/ or /**/
				@x', $m, $n)) {
				$this->_inHack = false;
				
				return "/*/{$n[1]}/**/";
			}
		}
		if (substr($m, -1) === '\\') { // comment ends like \*/
			$this->_inHack = true;
			return '/*\\*/';
		}
		if ($m !== '' && $m[0] === '/') { // comment looks like /*/ foo */
		$this->_inHack = true;
			return '/*/*/';
		}
		if ($this->_inHack) {
			$this->_inHack = false;
            return '/**/';
		}
		return $hasSurroundingWs // remove all other comments
			? ' '
			: '';
	}
    
	protected function _fontFamilyCB($m) {
        
		$m[1] = preg_replace('/
				\\s*
				(
				"[^"]+"      # 1 = family in double qutoes
				|\'[^\']+\'  # or 1 = family in single quotes
				|[\\w\\-]+   # or 1 = unquoted family
				)
				\\s*
				/x', '$1', $m[1]);
		return 'font-family:' . $m[1] . $m[2];
	}
    
	protected function _image2base64($data) {

		if(file_exists(AD_SRV_ROOT . AD_WEB_FOLDER . str_replace('../', '', $data[1])) && $mime = getimagesize(AD_SRV_ROOT . AD_WEB_FOLDER . str_replace('../', '', $data[1]))) {

			if(in_array($mime['mime'], array('image/jpeg','image/png','image/gif','image/jpg'))) {
    			return 'url(data:' . $mime['mime'] . ';base64,' . base64_encode(file_get_contents(AD_SRV_ROOT . AD_WEB_FOLDER . str_replace('../', '', $data[1]))) . ')';
    		} else {
    			return $data[0];
    		}
    	}
    }
}
?>