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
 * Main config class
 * With this class can get, set config values
 * 15.02.2010
 */
class Config extends Base {
 	
	public $config = array();
	public $siteData = array();
	
	/** 
	 * Constructor
	 */
	public function __construct($config) {
		$this->db = &loadLibClass('db');
		
		$this->config = $config;
		
		// Url parser
		$this->set('parseUrl', array('page', 'sort', 'city', 'street'));
		$this->set('langInTheEnd', true);
		
		$this->set('permitted_uri_chars', 'a-z 0-9~%.:_\-?=&!');
		$this->set('mirrors', true);
		$this->set('debug', true);
		$this->set('debugIp', array('159.148.41.210', '212.93.115.184', '213.175.120.26', '87.110.84.138', '127.0.0.1', '192.168.1.30','192.168.1.31','192.168.1.5','192.168.1.32'));
		$this->set('compress', false);
		$this->set('labelEditMode', false);
		
		// 404 page
		$this->set('404', false);
		
		// .html Remove
		$this->set('removeHtmlExt', true);
		
		// Replace symbols
		$this->set('urlCollation', array('�?','č','ē','ģ','ī','ķ','ļ','ņ','�?','ū','ž', ' ', '!', '?', '"', '%', '^', '$', '#', '@', '*', "'", '&',
											'а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', '�?', 'т', '�?', 'ф', 'х', 'ц', 'ч', '�?', 'щ', '�?', 'ю', 'я', '�?', 'ы'));
		$this->set('asciCollation', array('a','c','e','g','i','k','l','n','s','u','z','-', '', '', '', '', '', '', '', '', '', '', '',
											'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', '', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sh', 'j', 'ju', 'ja', '', 'y'));
		
		// ConvertURL symbols
		$this->set('deniedUrlChars', array('~', '`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '+', '{', '}', '[', ']', '|', ':', ';', ',', '.', '?', '-', '=', '\'', '"', '<', '>'));
		
		$this->set('mimes', array( 'hqx'	=>	'application/mac-binhex40',
								'cpt'	=>	'application/mac-compactpro',
								'csv'	=>	array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
								'bin'	=>	'application/macbinary',
								'dms'	=>	'application/octet-stream',
								'lha'	=>	'application/octet-stream',
								'lzh'	=>	'application/octet-stream',
								'exe'	=>	array('application/octet-stream', 'application/x-msdownload'),
								'class'	=>	'application/octet-stream',
								'psd'	=>	'application/x-photoshop',
								'so'	=>	'application/octet-stream',
								'sea'	=>	'application/octet-stream',
								'dll'	=>	'application/octet-stream',
								'oda'	=>	'application/oda',
								'pdf'	=>	array('application/pdf', 'application/x-download'),
								'ai'	=>	'application/postscript',
								'eps'	=>	'application/postscript',
								'ps'	=>	'application/postscript',
								'smi'	=>	'application/smil',
								'smil'	=>	'application/smil',
								'mif'	=>	'application/vnd.mif',
								'xls'	=>	array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
								'ppt'	=>	array('application/powerpoint', 'application/vnd.ms-powerpoint'),
								'wbxml'	=>	'application/wbxml',
								'wmlc'	=>	'application/wmlc',
								'dcr'	=>	'application/x-director',
								'dir'	=>	'application/x-director',
								'dxr'	=>	'application/x-director',
								'dvi'	=>	'application/x-dvi',
								'gtar'	=>	'application/x-gtar',
								'gz'	=>	'application/x-gzip',
								'php'	=>	'application/x-httpd-php',
								'php4'	=>	'application/x-httpd-php',
								'php3'	=>	'application/x-httpd-php',
								'phtml'	=>	'application/x-httpd-php',
								'phps'	=>	'application/x-httpd-php-source',
								'js'	=>	'application/x-javascript',
								'swf'	=>	'application/x-shockwave-flash',
								'sit'	=>	'application/x-stuffit',
								'tar'	=>	'application/x-tar',
								'tgz'	=>	array('application/x-tar', 'application/x-gzip-compressed'),
								'xhtml'	=>	'application/xhtml+xml',
								'xht'	=>	'application/xhtml+xml',
								'zip'	=>  array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
								'mid'	=>	'audio/midi',
								'midi'	=>	'audio/midi',
								'mpga'	=>	'audio/mpeg',
								'mp2'	=>	'audio/mpeg',
								'mp3'	=>	array('audio/mpeg', 'audio/mpg', 'audio/mpeg3'),
								'aif'	=>	'audio/x-aiff',
								'aiff'	=>	'audio/x-aiff',
								'aifc'	=>	'audio/x-aiff',
								'ram'	=>	'audio/x-pn-realaudio',
								'rm'	=>	'audio/x-pn-realaudio',
								'rpm'	=>	'audio/x-pn-realaudio-plugin',
								'ra'	=>	'audio/x-realaudio',
								'rv'	=>	'video/vnd.rn-realvideo',
								'wav'	=>	'audio/x-wav',
								'bmp'	=>	'image/bmp',
								'gif'	=>	'image/gif',
								'jpeg'	=>	array('image/jpeg', 'image/pjpeg'),
								'jpg'	=>	array('image/jpeg', 'image/pjpeg'),
								'jpe'	=>	array('image/jpeg', 'image/pjpeg'),
								'png'	=>	array('image/png',  'image/x-png'),
								'tiff'	=>	'image/tiff',
								'tif'	=>	'image/tiff',
								'css'	=>	'text/css',
								'html'	=>	'text/html',
								'htm'	=>	'text/html',
								'shtml'	=>	'text/html',
								'txt'	=>	'text/plain',
								'text'	=>	'text/plain',
								'log'	=>	array('text/plain', 'text/x-log'),
								'rtx'	=>	'text/richtext',
								'rtf'	=>	'text/rtf',
								'xml'	=>	'text/xml',
								'xsl'	=>	'text/xml',
								'mpeg'	=>	'video/mpeg',
								'mpg'	=>	'video/mpeg',
								'mpe'	=>	'video/mpeg',
								'qt'	=>	'video/quicktime',
								'mov'	=>	'video/quicktime',
								'avi'	=>	'video/x-msvideo',
								'movie'	=>	'video/x-sgi-movie',
								'doc'	=>	'application/msword',
								'docx'	=>	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
								'xlsx'	=>	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
								'word'	=>	array('application/msword', 'application/octet-stream'),
								'xl'	=>	'application/excel',
								'eml'	=>	'message/rfc822'
							));
	}
	
	/** 
	 * Gets sitedata values in all languages of course if field its set to multilanguage 
	 */
	public function getSiteData() {

		$dbQuery = "
			SELECT sd.`name` name, sd.`mlang` mlang, sdv.`lang`, sdv.`value` value, sd.mcountry, sdv.country 
			FROM `ad_sitedata` sd, `ad_sitedata_values` sdv 
			WHERE sd.`id` = sdv.`fid`
		";

		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			if($query->field('mlang') == 1){
				$this->siteData[$query->field('name')][$query->field('lang')] = $query->field('value');	
			} elseif ($query->field('mcountry') == 1) {
				$this->siteData[$query->field('name')][$query->field('country')][$query->field('lang')] = $query->field('value');
			} else{
				$this->siteData[$query->field('name')] = $query->field('value');	
			}
		}//pR($this->siteData);
	}

	/**
	 * @param $tab
	 * @param $lang
	 * @return array
	 */
	public function getSiteDataTab($tab, $nonEmptyValues = false, $lang = null)
	{
		if(!$tab || !is_string($tab)) {
			return array();
		}

		$result = array();

		$queryLang = null;

		if (!empty($lang)){
			$queryLang =  "sdv.`lang` = '". $lang . "' AND";
		}

		$dbQuery = "SELECT sd.`name` name, sd.`mlang` mlang, sdv.`lang`, sdv.`value` value, sd.mcountry, sdv.country 
					FROM `ad_sitedata` sd, `ad_sitedata_values` sdv 
					WHERE 
						sd.`id` = sdv.`fid` AND 
					    " . $queryLang . "
						sd.tab = '" . mres($tab) . "'";

		$query = new query($this->db, $dbQuery);

		if($query->num_rows()) {
			while ($row = $query->getrow()) {

				if(empty($row['value'])) {

					if($nonEmptyValues) {
						continue;
					}
				}

				$result[$row['name']] = $row['value'];
			}
		}

		return $result;
	}
	
	/** 
	 * Get site data value
	 *
 	 * @param	string	array key
	 */
	public function getData($name) {
		$value = false;
		
		$keys = explode("/", $name);

		if (($cnt = count($keys)) > 0) {
			for ($i = 0; $i < $cnt; $i++) {
				if ($i == 0) {
					if (isset($this->siteData[$keys[$i]])) {
						$value = $this->siteData[$keys[$i]];
					} else {
						return false;
					}
				} else {
					if (isset($value[$keys[$i]])) {
						$value = $value[$keys[$i]];
					} else {
						return false;
					}
				}

			}
		}
		
		return $value;
	}

	/** 
	 * Get config value
	 *
 	 * @param	string	array key
 	 * @return	string
	 */
	public function get($name, $key = false) {
		
		if ($key) {
			if (isset($this->config[$key][$name])) {
				return $this->config[$key][$name];
			}
			else {
				return false;
			}
		}
		else {
			if (isset($this->config[$name])) {
				return $this->config[$name];
			}
			else {
				return false;
			}
		}	
	}
	
	/** 
	 * Set config value
	 *
	 * @access	public
 	 * @param	mix 	name or array with names&values
 	 * @param	string 	value 
 	 * @return	string
	 */
	public function set($config, $value = '') {
		
		if (is_array($config)) {
			$this->config = array_merge($this->config, $config);
		}
		elseif (!empty($config)) {
			$this->config[$config] = $value;
		}
	}
	
	public function getDbTable($group = false, $table = false){
	
		if($group & $table){
			if(isset($this->config['db_tables'][$group][$table])){
				return $this->config['db_tables'][$group][$table];
			}
			return 'undefined_db_group_or_table:'.$group.'|'.$table;
		}
		else if($group){
			if(isset($this->config['db_tables'][$group])){
				return $this->config['db_tables'][$group];
			}
	
			return 'invalid_db_group:'.$group;
		}
	
		return 'invalid_group_and_table:'.$group.'|'.$table;
	
	}
	
	public function getImageConfig($module){
	
		if(isset($this->config['image_config'][$module])){
			return $this->config['image_config'][$module];
		}
	
		return 'image size not found';
	}
}

?>
