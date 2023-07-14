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
 * @subpackage	siteData
 * @author		Maris Melnikovs <maris.melnikovs@efumo.lv>
 * @copyright	Copyright (c) 2012, Efumo.
 * @link		http://www.efumo.lv
 * @version		2
 * 17.01.2013
 */
	
	/**
	 * Generates siteData output
	 * @return string HTML
	 */
	class siteDataData extends Module_cms {
		
		public $result;
		
		public function __construct() {
			
			parent :: __construct();
			$this->name 	= 'siteData';
			$this->sdTable 	= 'ad_sitedata';
			$this->sdvTable = 'ad_sitedata_values';
			$this->conTable = 'ad_content';
		}
		
		/**
		 * Get all siteData from Db 
		 * @return string HTML
		 */
		public function getSiteData () {	
			
			$dbQuery = "SELECT * FROM `" . $this->sdTable . "` GROUP BY `tab` ORDER BY `id` ASC";
			$query = new query($this->db, $dbQuery);

			$tabs = '';
			$content = '';
			while ($query->getrow()) {
				
				$tab = str_replace(' ', '', strtolower($query->field('tab')));
				$tabs .= '<li><a href="#'. $tab .'" rel="'. $tab  .'">'. $query->field('tab') .'</a></li>';
				
				$content .= '<div id="'. $tab .'"><div class="error" style="display: none; margin-bottom: 10px;padding: 10px;"></div><table>';
				$content .= $this->getContent($query->field('tab'));
				$content .= '</table></div>';
				
			}

			if(!empty($tabs)){
				$tabs_html = '<ul>';
				$tabs_html .= $tabs;
				$tabs_html .= '</ul>';	

				$this->result['tabs'] = $tabs_html;
			}

			if(!empty($content)){
				$this->result['content'] = $content;	
			}

			return $this->result;
		}
		
		/**
		 * Get current tab content
		 * @param string Tab name
		 * @return string HTML
		 */
		public function getContent ($tab) {
			
			$content = '';
			
			$dbQuery = "SELECT * FROM `" . $this->sdTable . "` WHERE `tab` = '". $tab ."' GROUP BY `block` ORDER BY `id` ASC";
			$query = new query($this->db, $dbQuery);
			while ($query->getrow()) {
				$content .= '<tr class="block"><td>'. $query->field('block') .'</td><td>'. $this->getFields($query->field('tab'), $query->field('block')) .'</td></tr>';
			}
			
			return $content;
		}
		
		/**
		 * Get current block fields
		 * @param string Block name
		 * @return string HTML
		 */
		public function getFields ($tab, $block) {
			
			$field = '';
			
			$dbQuery = "SELECT * FROM `" . $this->sdTable . "` WHERE `tab` = '". $tab ."' AND `block` = '". $block ."'";
			$query = new query($this->db, $dbQuery);

			while ($query->getrow()) {

				$type = explode('|', $query->field('type'));
				$fName = strtolower($query->field('name'));
				(isset($type[0])) ? $ftype = $type[0] : false;
				
				
				if (!$query->field('mcountry')) {
					$field .= '<div class="block-holder open">';
				}
				$field .= $query->field('title');
				$field .= ($query->field('mlang') == true) ? '<div class="inner-block">'. $this->langTabs() .'<div class="areaBlock">' : '';
				$field .= ($query->field('mcountry') == true) ? $this->countryTabs() : '';

				switch ($ftype) {
					case 'text':
						if ($query->field('mlang') == true) {
							foreach (getSiteLangs() as $key => $value) {
								$open = ($key == 0) ? ' open' : '';
								$field .= '<div class="text-block'. $open .'">'. $this->fieldText($query->field('id'), $fName, $query->field('title'), $ftype, $value['lang'], $query->field('required')) .'</div>';
							}
						} elseif ($query->field('mcountry') == true) {
							$countries = getSiteLAndC();
							foreach ($countries as $ctr => $data) {
								$field2 = '';	
								foreach ($data['langs'] as $l => $lng) {
									$field2 .= '<div class="text-block' . ($lng['default'] ? ' open' : '') . '">';
									$field2 .= $this->fieldText($query->field('id'), $fName, $query->field('title'), $ftype, $lng['lang'], $query->field('required'), $data['id']);
									$field2 .= '</div>';
									
									$field = str_replace('{{BLOCK_' . $data['id'] . '_' . $lng['lang'] . '}}', $field2, $field);
									
								}	
								
							}
							
							
							
						} else {
							$field .= $this->fieldText($query->field('id'), $fName, $query->field('title'), $ftype, false, $query->field('required'));
						}
					break;
					case 'checkbox':
						if ($query->field('mlang') == true) {
							foreach (getSiteLangs() as $key => $value) {
								$open = ($key == 0) ? ' open' : '';
								$field .= '<div class="text-block'. $open .'">'. $this->fieldCheckbox($query->field('id'), $fName, $query->field('title'), $ftype, $value['lang'], $query->field('required')) .'</div>';
							}
						} elseif ($query->field('mcountry') == true) {
							$countries = getSiteLAndC();

							foreach ($countries as $ctr => $data) {
								$field2 = '';	
								foreach ($data['langs'] as $l => $lng) {
									$field2 .= '<div class="text-block' . ($lng['default'] ? ' open' : '') . '">';
									$field2 .= $this->fieldCheckbox($query->field('id'), $fName, $query->field('title'), $ftype, $lng['lang'], $query->field('required'), $data['id']);
									$field2 .= '</div>';
									
									$field = str_replace('{{BLOCK_' . $data['id'] . '_' . $lng['lang'] . '}}', $field2, $field);
								}	
							}
							
							
							
						} else {
							$field .= $this->fieldCheckbox($query->field('id'), $fName, $query->field('title'), $ftype, false, $query->field('required'));
						}
					break;
					case 'radio':
						if ($query->field('mlang') == true) {
							foreach (getSiteLangs() as $key => $value) {
								$open = ($key == 0) ? ' open' : '';
								$field .= '<div class="text-block'. $open .'">'. $this->fieldRadio($query->field('id'), $fName, $query->field('title'), $ftype, $value['lang'], $query->field('required')) .'</div>';
							}
						}
						else {
							$field .= $this->fieldRadio($query->field('id'), $fName, $query->field('title'), $ftype, false , $query->field('required'));
						}
					break;
					case 'textarea':
						if ($query->field('mlang') == true) {
							foreach (getSiteLangs() as $key => $value) {
								$open = ($key == 0) ? ' open' : '';
								$field .= '<div class="text-block'. $open .'">'. $this->fieldTextarea($query->field('id'), $fName, $query->field('title'), $ftype, $value['lang'], $query->field('required')) .'</div>';
							}
						} elseif ($query->field('mcountry') == true) {
							$countries = getSiteLAndC();
							
							foreach ($countries as $ctr => $data) {
								$field2 = '';	
								foreach ($data['langs'] as $l => $lng) {
									$field2 .= '<div class="text-block' . ($lng['default'] ? ' open' : '') . '">';
									$field2 .= $this->fieldTextarea($query->field('id'), $fName, $query->field('title'), $ftype, $lng['lang'], $query->field('required'), $data['id']);
									$field2 .= '</div>';
									
									$field = str_replace('{{BLOCK_' . $data['id'] . '_' . $lng['lang'] . '}}', $field2, $field);
								}	
							}
							
							
							
						} else {
							$field .= $this->fieldTextarea($query->field('id'), $fName, $query->field('title'), $ftype, $query->field('mlang'), $query->field('required'));
						}
					break;
					case 'selcat':
						if ($query->field('mlang') == true) {
							foreach (getSiteLangs() as $key => $value) {
								$open = ($key == 0) ? ' open' : '';
								$field .= '<div class="text-block'. $open .'">'. $this->fieldSelCat($query->field('id'), $fName, $query->field('title'), $ftype, $value['lang'], '', $query->field('required')) .'</div>';
							}
						} elseif ($query->field('mcountry') == true) {
							$countries = getSiteLAndC();
							
							foreach ($countries as $ctr => $data) {
								$field2 = '';
								foreach ($data['langs'] as $l => $lng) {
									$field2 .= '<div class="text-block' . ($lng['default'] ? ' open' : '') . '">';
									$field2 .= $this->fieldSelCat($query->field('id'), $fName, $query->field('title'), $ftype, $lng['lang'], $data['id'], $query->field('required'));
									$field2 .= '</div>';
									
									$field = str_replace('{{BLOCK_' . $data['id'] . '_' . $lng['lang'] . '}}', $field2, $field);
								}
							}
							
	
						} else {
							$field .= $this->fieldSelCat($query->field('id'), $fName, $query->field('title'), $ftype, $query->field('mlang'), '', $query->field('required'));
						}
					break;
				}
				
				$field .= ($query->field('mlang') == true) ? '</div></div>' : '';
				if (!$query->field('mcountry')) {
					$field .= '</div>';	
				}
				
				
			}
			
			return $field;
		}
		
		/**
		 * Get values of given field
		 * @param Int $id field id
		 * @param string lang two symbol language code
		 */
		public function getValues($id, $lang, $getContentName = "", $country = false) {
			
			$dbQuery = "SELECT * FROM `" . $this->sdvTable . "` WHERE `fid` = '". $id ."'";
			$dbQuery .= (!empty($lang)) ? " AND `lang` = '". $lang ."'" : "";
			$dbQuery .= (!empty($country)) ? " AND `country` = '". $country ."'" : "";
			$query = new query($this->db, $dbQuery);
			$query->getrow();
			
			if ($getContentName) {
				return $this->getContentName($query->field('value'));
			}
			
			return $query->field('value');
		}
		
		public function getContentName($id) {
			
			$dbQuery = "SELECT title FROM `" . $this->conTable . "` WHERE `id` = '". $id ."'";
			$query = new query($this->db, $dbQuery);
			if ($query->num_rows() > 0) {
				return $query->getOne();	
			} else {
				return $id;
			}
			
		}
		
		/**
		 * Draw input type="text" field
		 * @param Int $id
		 * @param string $name
		 * @param string $type
		 * @param string lang
		 * @return string HTML
		 */
		public function fieldText ($id, $name, $title, $type, $lang, $required, $country = false) {
			
			$requiredHTML = ($required == 1) ? '<b style="color: red" title="required">*</b>' : '';
			$requiredClass = ($required == 1) ? ' class="required long"' : ' class="long"';
			
			$output = '<input type="'. $type .'" name="'. $name .'" value="'. htmlentities($this->getValues($id, $lang, '', $country)) .'"';
			if ($country == true) {
				$output .= ' id="'. $name . '_' . $country .'_'. $lang .'" data="' .$lang  . '" rel="'. $country .'"';	
			} elseif ($lang == true) {
				$output .= ' id="'. $name .'_'. $lang .'" rel="'. $lang .'"';
			} else {
				$output .= ' id="'. $name .'"';
			}
			$output .= ($lang == true) ? ' id="'. $name .'_'. $lang .'" rel="'. $lang .'"' : ' id="'. $name .'"';
			$output .= (!empty($title)) ? ' title="'. $title .'"' : '';
			$output .= $requiredClass .'>'. $requiredHTML;
			
			return $output;
		}
		
		/**
		 * Draw input type="checkbox" field
		 * @param Int $id
		 * @param string $name
		 * @param string $type
		 * @param string lang
		 * @return string HTML
		 */
		public function fieldCheckbox ($id, $name, $title, $type, $lang, $required, $country = false) {
			
			$checked = ($this->getValues($id, $lang, '', $country) == true) ? 'checked' : '';
			
			$requiredHTML = ($required == 1) ? '<b style="color: red" title="required">*</b>' : '';
			$requiredClass = ($required == 1) ? ' class="required"' : '';
			
			$output = '<input type="'. $type .'" name="'. $name .'"';
			if ($country == true) {
				$output .= ' id="'. $name . '_' . $country .'_'. $lang .'" data="' .$lang  . '" rel="'. $country .'"';	
			} elseif ($lang == true) {
				$output .= ' id="'. $name .'_'. $lang .'" rel="'. $lang .'"';
			} else {
				$output .= ' id="'. $name .'"';
			}
			$output .= (!empty($title)) ? ' title="'. $title .'"' : '';
			$output .= ' '. $checked . $requiredClass .'>'. $requiredHTML;
			
			return $output;
		}
		
		/**
		 * Draw input type="radio" field
		 * @param Int $id
		 * @param string $name
		 * @param string $type
		 * @param string lang
		 * @return string HTML
		 */
		public function fieldRadio ($id, $name, $title, $type, $lang, $required) {
			
			$checked = ($this->getValues($id, $lang) == true) ? 'checked' : '';
			
			$requiredHTML = ($required == 1) ? '<b style="color: red" title="required">*</b>' : '';
			$requiredClass = ($required == 1) ? ' class="required"' : '';
			
			$output = '<input type="'. $type .'" name="'. $name .'"';
			$output .= ($lang == true) ? ' id="'. $name .'_'. $lang .'" rel="'. $lang .'"' : ' id="'. $name .'"';
			$output .= (!empty($title)) ? ' title="'. $title .'"' : '';
			$output .= ' '. $checked . $requiredClass .'>'. $requiredHTML;
			
			return $output;
		}
		
		/**
		 * Draw <textarea>...</textarea> field
		 * @param Int $id
		 * @param string $name
		 * @param string $type
		 * @param string lang
		 * @return string HTML
		 */
		public function fieldTextarea ($id, $name, $title, $type, $lang, $required, $country = false) {
			
			$requiredHTML = ($required == 1) ? '<b style="color: red" title="required">*</b>' : '';
			$requiredClass = ($required == 1) ? ' required' : '';
			
			$output = '<textarea cols="100" rows="6" type="'. $type .'" name="'. $name .'" class="long simple'. $requiredClass .'"';
			if ($country == true) {
				$obId = $name . '_' . $country .'_'. $lang;
				$output .= ' id="'. $name . '_' . $country .'_'. $lang .'" data="' .$lang  . '" rel="'. $country .'"';	
			} elseif ($lang == true) {
				$obId = $name .'_'. $lang;
				$output .= ' id="'. $name .'_'. $lang .'" rel="'. $lang .'"';
			} else {
				$obId = $name;
				$output .= ' id="'. $name .'"';
			}
			
			$output .= (!empty($title)) ? 'title="'. $title .'"' : '';
			$output .= '>'. $this->getValues($id, $lang, '', $country) .'</textarea>'. $requiredHTML;
			$output .= '<p class="wys"><a href="#" onclick="openCkEditor(\''. $obId .'\', \'advanced\'); return false;">'. gLA('wysiwyg','WYSIWYG editor') .'</a></p><div class="clr"></div>';
			return $output;
		}
		
		/**
		 * Draw select category list field
		 * @param Int $id
		 * @param string $name
		 * @param string $type
		 * @param string lang
		 * @return string HTML
		 */
		public function fieldSelCat ($id, $name, $title, $type, $lang, $country = false, $required) {
			
			$requiredHTML = ($required == 1) ? '<b style="color: red" title="required">*</b>' : '';
			$requiredClass = ($required == 1) ? 'required' : '';
			
			if ($country) {
				
				$output = '<p><input name="'. $name .'" id="'. $name .'_' . $country . '_' . $lang . '" data="' . $lang . '" rel="' . $country . '" value="'. $this->getValues($id, $lang, '', $country) .'" type="hidden">
				<input onkeyup="$(\'#' . $name .'_' . $country . '_' . $lang . '\').val($(this).val());" class="'. $requiredClass .'" value="'. $this->getValues($id, $lang, 1, $country) .'" id="'. $name .'_' . $country . '_' . $lang . 'Title" data="' . $lang . '" rel="' . $country . '" name="'. $name .'Title" type="text">'. $requiredHTML .'
				<a href="#" onclick="openSiteMapDialog(\''. $name .'_' . $country . '_' . $lang . '\', \''. $name .'_' . $country . '_' . $lang . 'Title\', \'\'); return false;" class="select-btn">Select</a>
				<a href="#" onclick="$(\'#'. $name .'_' . $country . '_' . $lang . '\').val(\'\'); $(\'#'. $name .'_' . $country . '_' . $lang . 'Title\').val(\'\'); return false;">Clear</a>
				</p>';
				
			} elseif ($lang) {
				
				$output = '<p><input name="'. $name .'" id="'. $name .'_' . $lang . '" rel="' . $lang . '" value="'. $this->getValues($id, $lang) .'" type="hidden">
				<input  onkeyup="$(\'#' . $name . '_' . $lang . '\').val($(this).val());" class="'. $requiredClass .'" value="'. $this->getValues($id, $lang, 1) .'" id="'. $name .'_' . $lang . 'Title" rel="' . $lang . '" name="'. $name .'Title" type="text">'. $requiredHTML .'
				<a href="#" onclick="openSiteMapDialog(\''. $name .'_' . $lang . '\', \''. $name .'_' . $lang . 'Title\', \'\'); return false;" class="select-btn">Select</a>
				<a href="#" onclick="$(\'#'. $name .'_' . $lang . '\').val(\'\'); $(\'#'. $name .'_' . $lang . 'Title\').val(\'\'); return false;">Clear</a>
				</p>';
				
			} else {
				$output = '<p><input name="'. $name .'" id="'. $name .'" value="'. $this->getValues($id, $lang) .'" type="hidden">
				<input  onkeyup="$(\'#' . $name . '\').val($(this).val());" class="'. $requiredClass .'" value="'. $this->getValues($id, $lang, 1) .'" id="'. $name .'Title" name="'. $name .'Title" type="text">'. $requiredHTML .'
				<a href="#" onclick="openSiteMapDialog(\''. $name .'\', \''. $name .'Title\', \'\'); return false;" class="select-btn">Select</a>
				<a href="#" onclick="$(\'#'. $name .'\').val(\'\'); $(\'#'. $name .'Title\').val(\'\'); return false;">Clear</a>
				</p>';
			}
			
			
			
			return $output;
		} 
			
		/**
		 * Generates language tabs
		 * @return string HTML
		 */
		public function langTabs (){
			
			$langTabs = '<div class="inner-block"><ul class="lang-tabs">';
			foreach (getSiteLangs() as $key => $value) {
				$active = ($key == 0) ? ' class="active"' : '';
				$langTabs .= '<li'. $active .'><a href="#">'. $value['title'] .'</a></li>';
			}
			$langTabs .= '</ul>';
			
			return $langTabs;
		}
		
		/**
		 * Generates country tabs
		 * @return string HTML
		 */
		public function countryTabs (){
			$countries = getSiteLAndC();
			
			$Tabs = '';
			foreach ($countries AS $key => $data) {
				$Tabs .= '<div class="block-holder' . ($key == 0 ? ' open' : '') . '">';
				$Tabs .= '<h4><a href="#">' . $data['title'] . '</a></h4>';
				$Tabs .= '<div class="inner-block">';
				$Tabs .= '<ul class="lang-tabs">';
				foreach ($data['langs'] AS $l => $lng) {	
					$Tabs .= '<li'. ($lng['default'] ? ' class="active"' : '') .'><a href="#">'. $lng['title'] .'</a></li>';
				}	
				
				$Tabs .= '</ul>';
				$Tabs .= '<div class="areaBlock">';
				$Tabs .= '{{BLOCK_' . $data['id'] . '_' . $lng['lang'] . '}}';
				$Tabs .= '</div>';
				$Tabs .= '</div>';
				$Tabs .= '</div>';
						
			}
			
			return $Tabs;						                                
                            
		}
		
		/**
		 * Save all site data
		 * @param array posted values
		 */
		public function save($values) {
		
					
			foreach ($values as $name => $value) {
				$dbQuery = "SELECT * FROM `" . $this->sdTable . "` WHERE `name` = '". $name ."'";
				$query = new query($this->db, $dbQuery);
				$query->getrow();
				
				if($query->field('mlang') == true) {
					foreach($values[$name] as $name_2 => $value_2) {
						doQuery($this->db, "DELETE FROM `" . $this->sdvTable . "` WHERE `fid` = '". $query->field('id') ."' AND `lang` = '". $name_2 ."'");
						doQuery($this->db, "INSERT INTO `" . $this->sdvTable . "` (`fid`, `lang`, `value`) VALUES('". $query->field('id') ."', '". $name_2 ."', '". mres($value_2) ."')");
					}
				} elseif ($query->field('mcountry') == true) {
					foreach($values[$name] as $name_2 => $value_2) {
						foreach($value_2 AS $lang => $val) {
							doQuery($this->db, "DELETE FROM `" . $this->sdvTable . "` WHERE `fid` = '". $query->field('id') ."' AND `country` = '". $name_2 ."' AND `lang` = '" . $lang . "'");
							doQuery($this->db, "INSERT INTO `" . $this->sdvTable . "` (`fid`, `country`, `lang`, `value`) VALUES('". $query->field('id') ."', '". $name_2 ."', '". $lang ."', '". mres($val) ."')");
						}
					}
				} else {
					doQuery($this->db, "DELETE FROM `" . $this->sdvTable . "` WHERE `fid` = '". $query->field('id') ."'");
					doQuery($this->db, "INSERT INTO `" . $this->sdvTable . "` SET `value` = '". mres($value) ."', `fid` = '". $query->field('id') ."'");
				}
			}
		}
	}

?>