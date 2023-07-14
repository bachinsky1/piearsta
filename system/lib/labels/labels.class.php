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
 * Labels class
 * 06.05.2010
 */
class Labels {

	private $labels;
	private $ids = array();
	private $all = false;
	
	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->db = &loadLibClass('db');
		$this->cfg = &loadLibClass('config');
	}
	
	/**
	 * Get module labels
	 * By country and lang 
	 * 
	 * @param int		module id
	 * @param int		country id
	 * @param string	language
	 * @param bool		load all messages or not
	 */
	public function getLabels($m, $c, $l, $all = false) {
		
		if ($this->all) {
			return;
		}
		
		if ($all) {
			$this->all = true;
		}
		
		if ($this->all) {
			$where = "";
		} else {
			if (is_array($m)) {
				$where = "AND m.module_id IN (" . implode(",", $m) . ") ";
			} else {
				
				if (in_array($m, $this->ids)) {
					return false;
				}
				
				$where = "AND m.module_id = '" . $m . "' ";
				
				$this->ids[] = $m;
			}
		}
		
		$dbQuery = "
			SELECT `value`, `enable`, `name` 
			FROM `ad_messages` m, `ad_messages_info` mi 
			WHERE 1
				" . $where . "
				AND ((m.type = 'l' AND mi.country = 0) OR (mi.country = '" . $c . "' AND m.type = 'c')) 
				AND m.id = mi.id 
				AND ((mi.lang = '" . $l . "' AND mi.value <> ''))";
		$query = new query($this->db, $dbQuery);

		while ($query->getrow()) {
			
			if ($query->field("enable")) {	
				$this->labels[$query->field("name")] = $query->field("value");	
			} else {
				$this->labels[$query->field("name")] = false;
			}
			
		}		
	}
	
	/**
	 * Get labels by name
	 * 
	 * @param string		label name
	 */
	public function getLabel($name, $params = array()) {
		if (isset($this->labels[$name])) {
			return $this->returnLabelValue($this->labels[$name], $name, $params);
		}
		
		return false;
	}
	
	/**
	 * Get one label from db
	 * If not exist: insert new one to db
	 * 
	 * @param string	label name
	 * @param string	label value
	 * @param string	lang ident
	 * @param int	 	country id
	 * @param int		module id
	 */
	public function getOneLabel($name, $value, $lang, $country, $moduleId, $params = array()) {
		
		$value = $value ? $value : $name;
		
		$dbQuery = "
			SELECT `id`, `type`, `enable` 
			FROM `ad_messages` m
			WHERE m.name = '" . $name . "' 
			LIMIT 0,1
		";
		$query = new query($this->db, $dbQuery);

		if ($query->num_rows() > 0) {

			$query->getrow();
			$id = $query->field('id');
			
			if ($query->field('type') == 'l') {
				
			}
			
			if (!$query->field('enable')) {
				return false;
			}

            $labelTranslated = $this->getMessageValue($query, $id, $country, $lang, $name, $params);

            // If label is not translated in requested language, should return label in default language
            // If no translation in default language found, returns from html value that is set

            if (!$labelTranslated) {

                // Please find comments in site.func.php in gL() function why we add country = 0 here
                $country = 0;

                $labelTranslatedLv = '';

                if ($lang !== getDefaultLang()) {
                    $labelTranslatedLv = $this->getMessageValue($query, $id,  $country, getDefaultLang(), $name, $params);
                }

                $value = $labelTranslatedLv ? $labelTranslatedLv : $value;

                $dbQuery = "
					INSERT INTO `ad_messages_info` 
					SET `id` = '" . $id . "',
						`lang` = '" . $lang . "',
						`value` = '" . mres($value) . "'
				";
                if (intval($country) and $country != 0) {
                    $dbQuery .= ", `country` = '" . $country . "'";
                }
                $query->query($this->db, $dbQuery);

                $labelTranslated = $this->returnLabelValue($value, $name, $params);

            }

            return $labelTranslated;

		} else {
			
			$dbQuery = "
				INSERT INTO `ad_messages` 
				SET `module_id` = '" . $moduleId . "',
					`name` = '" . mres($name) . "',
					`enable` = '1',
					`date` = '" . time() . "'";
			$query = new query($this->db, $dbQuery);
			$insId = $this->db->get_insert_id();
			
			if ($insId) {
				$langs = getSiteLangs();
				$cnt = count($langs);
				
				for ($i = 0; $i < $cnt; $i++) {

					$dbQuery = "
						INSERT INTO `ad_messages_info` 
						SET `id` = '" . $insId . "',
							`lang` = '" . $langs[$i]["lang"] . "',
							`value` = '" . mres($value) . "'";
					$query->query($this->db, $dbQuery);
				}
			}		
			
			return $this->returnLabelValue($value, $name, $params);
		}
	}
	
	/**
	 * Check if label edit mode is enable
	 * AND check if admin mode + admin session is
	 * Else return simple value
	 * 
	 * @param string	label value
	 */
	private function returnLabelValue($value, $name, $params) {
		
		if (!empty($params) && is_array($params)) {
			$value = str_replace(array_keys($params), array_values($params), $value);
		}
		
		return $value;
	}

    private function getMessageValue($query, $id, $country, $lang, $name, $params)
    {

        $dbQuery = "SELECT `value` FROM `ad_messages_info` 
				WHERE 
					`id` = '" . $id . "'
					AND `country` = '" . $country . "'
					AND `lang` = '" . $lang . "'";

        $query->query($this->db, $dbQuery);

        if ($query->num_rows() > 0) {

            return $this->returnLabelValue($query->getOne(), $name, $params);

        }
    }
	
}

?>