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
 * Adweb cms functions file
 * 15.02.2010
 */

/**
 * Create drop down menu of all site languages
 * 
 * @param string 	selected language
 */
function getSiteLanguageDropDown($lang = "") {
	$siteLangs = getSiteLanguages();
	$html = dropDownFieldOptions($siteLangs, $lang, true);
	
	return $html;
}

/**
 * Get all site languages from language module
 */
function getSiteLanguages() {
	$mdb = &loadLibClass('db');
	
	$dbQuery = "SELECT `lang`, `title` FROM `ad_languages` WHERE `enable` = '1' ORDER BY `sort` ASC";
	$query = new query($mdb, $dbQuery);
	$result = array();
	while ($query->getrow()) {
		$result[$query->field('lang')] = $query->field('title');
	}
	$query->free();
	
	return $result;
}

/**
 * Saving languages values information in DB
 * 
 * @param string 	sql table
 * @param int		id, it's need if we are editing
 * @param array 	languages values information
 * @param array 	sql table fields names
 * @param string 	field name, default value - id
 * @param bool		delete data before insert, true or false. default true
 */
function saveLanguageValues($table, $id, $langValues, $combArray, $fieldId = "id", $del = true) {
	if (isset($id) && $id != '' && $del) {
		deleteFromDbById($table, $id, $fieldId);
	}

	foreach ($langValues AS $lang => $lArray) {
		$sArray = array_combine($combArray, $lArray);
		$sArray[$fieldId] = $id;
		$sArray["lang"] = $lang;
				
		saveValuesInDb($table, $sArray);
	}
}

/**
 * Saving country values information in DB
 * 
 * @param string 	sql table
 * @param int		id, it's need if we are editing tmplparm
 * @param array 	languages values information
 * @param array 	sql table fields names
 * @param string 	field name, default value - id
 * @param bool		delete data before insert, true or false. default true
 */
function saveCountryValues($table, $id, $values, $fieldName, $fieldId = "id", $del = true) {
	if (isset($id) && $id != '' && $del) {
		deleteFromDbById($table, $id, $fieldId);
	}

	foreach ($values AS $key => $value) {
		
		$t = explode("_", $key);
		
		$sArray[$fieldId] = $id;
		$sArray[$fieldName] = $value;
		$sArray["lang"] = $t[2];
		$sArray["country"] = $t[1];
				
		saveValuesInDb($table, $sArray);
	}
}

function convertUrlOld($url) {
	$config = &loadLibClass('config');

	$url = str_replace($config->get('urlCollation'), $config->get('asciCollation'), mb_strtolower(trim($url), 'UTF-8'));

	$url = preg_replace("/[^" . str_replace('&', '', $config->get('permitted_uri_chars')) . "\/]/", "-", $url);

	$url = preg_replace("/[\s\-]+/", "-", $url);

	return $url;
}

/**
 * Convert seconds to date format
 * 
 * @param int 		time in seconds
 * @param string	date format
 */
function convertDate($time, $format) {
	if ($time) {
		return date($format, $time);	
	}
}

/**
 * Returns array of all site languages with language code.
 * This function is used for name, title, description etc. HTML view generation
 * @author  J�nis �akars <janis.sakars@efumo.lv>
 */
function getSiteLangsWithCode() {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `lang`, `title`, UPPER(`lang`) AS code FROM `ad_languages` WHERE `enable`='1' ORDER BY `sort` ASC";
	$query = new query($mdb, $dbQuery);

	return $query->getArray();
}

$mdb = &loadLibClass('db');

/**
 * Returns next sort value for table specified
 * @param   $table AS string
 * @param   $options AS array
 */
function getNextSortValue($table, $options = array()) {
    
    global $mdb;
    
    if($table) {
        $search = ' WHERE 1';
        if(sizeof($options)) {
            foreach($options as $field => $value) {
                $search .= " AND `" . $field . "` = '" . mres($value) . "'";
            }
        }
        $query = new query($mdb, "SELECT MAX(`sort`) FROM `" . mres($table) . "`" . $search);
		
        $result = $query->getOne();
        
        if(is_numeric($result)) {
			return ++$result;
        }
        
        return 1;
    }
    
    return 0;
}

/**
 * Chages sort values for table row specified
 * @param   $id AS int
 * @param   $table AS string
 * @param   $sort AS string(UP or DOWN)
 * @param   $options AS array(extra fields for sorting, example, category_id)
 */
function changeSortValues($id, $table, $sort, $options = array()) {
    
    global $mdb;
    
    $query = new query($mdb, "SELECT * FROM `" . mres($table) . "` WHERE `id` = " . intval($id));
    if($query->num_rows()) {
        $content = $query->getrow();

        // Set sorting parameters
        if(strtolower($sort) == 'down') {
            $sql_param = '<';
            $sql_param2 = 'DESC';
        } else {
            $sql_param = '>';
            $sql_param2 = 'ASC';
        }
        
        $search = '';
        if(sizeof($options)) {
            foreach($options as $field) {
                $search .= " AND `" . $field . "` = '" . mres($content[$field]) . "'";
            }
        }
        
        // Select next or previous row
        $query->query($mdb, "SELECT `id`, `sort` FROM `" . mres($table) . "` WHERE `sort` " . $sql_param . " '" . $content['sort'] . "' " . $search . " ORDER BY `sort` " . $sql_param2 . " LIMIT 0, 1");
        if($query->num_rows()) {
            $info = $query->getrow();

            doQuery($mdb, "UPDATE `" . mres($table) . "` SET `sort` = " . intval($content['sort']) . " WHERE `id` = " . intval($info['id']));
            doQuery($mdb, "UPDATE `" . mres($table) . "` SET `sort` = " . intval($info['sort']) . " WHERE `id` = " . intval($id));

            return true;
        }
    }

    return false;
}


/**
 * Checks whether language exists in DB
 * @param   $lang AS string
 * @author  Jānis Šakars <janis.sakars@efumo.lv>
 */
function langExists($lang) {
    
    $mdb = &loadLibClass('db');
    
    $query = new query($mdb, "SELECT `id` FROM `ad_languages` WHERE `lang` = '" . mres($lang) . "'");
    if($query->num_rows()) {
        return true;
    }

    return false;
}
?>