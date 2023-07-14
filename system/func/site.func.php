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
 * Adweb site functions
 * 21.04.2010
 */

/**
 * Get simple label value from msg table
 *
 * @param string	laybel name
 * @param string	add value
 * @param string	language
 * @param string	description
 */
function gL($name, $value = '', $lang = '', $params = array()) {

	$mdb = &loadLibClass('db');

	/** @var labels $labels */
	$labels = &loadLibClass('labels');

	if(class_exists('Module')) {

		if($lang == "") {
			$lang = Module :: $lang;
		}

		$country = Module :: $country;
		$cLang = Module :: $lang;
		$moduleId = Module :: $moduleId;

	} else {

		$moduleId = 1;

		if($lang == 'lv') {
		    $country = 0;
        } else {
            $country = getCountry();
            $lang = $cLang = getDefLangInCountry($country);
        }

	}

    // if GET contains parameter - lang, find labels in preferred language
    // Note that requests to gl() coming from "web views" (e.g. modals generated via AJAX)
    // are using webLang instead of GET paramater to set the language
    if (getG('lang')) {
        $lang = getG('lang');
    }

    // For the new languages "ru" and "en" we start with a "clean slate" - always expect country to be "0"
    // Primarily based on the fact that CMS adds translations with country = 0 if country = 1 or other, CMS does not take it.
    // If there is situation when 2 translations with same id, but for one country = 0, for second country = 1, CMS and website
    // will show one with country = 0 only, and after some correction in CMS for that translation, this one with country = 1
    // will be deleted from DB
    // TODO: Migrate existing translations mapping them to correct country codes

    if ($lang !== getDefaultLang()) {
        $country = 0;
    }

	if ($labels->getLabel($name) && $cLang == $lang) {

		return removeLineBreaks($labels->getLabel($name, $params));

	} else {

	        return removeLineBreaks($labels->getOneLabel($name, $value, $lang, $country, $moduleId, $params));

	}

}

function gLParam($value, $name)
{
	return array($name => $value);
}

/**
 * Getting default site language
 */
function getDefLangInCountry($c) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `lang` FROM `ad_languages` l, `ad_languages_to_ct` lc WHERE lc.lang_id = l.id  AND lc.country_id = '" . $c . "' AND lc.default = '1'";
	$query = new query($mdb, $dbQuery);
	$lang = $query->getOne();
	if($lang) {
		return $lang;
	}
}

/**
 * Get simple label value from msg table
 *
 * @param string	label name
 * @param int		country id
 */
function gL2($name, $c, $lang = '') {

	$mdb = &loadLibClass('db');

	$lang = $lang ? $lang : getDefLangInCountry($c);

	$dbQuery = "SELECT `id`, `type` FROM `ad_messages` m
				WHERE m.name = '" . $name . "'
				LIMIT 0,1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows() > 0) {
		$query->getrow();
		$id = $query->field('id');
		$type = $query->field('type');

		if ($type == 'l') {
			$country = '0';
		} else {
			$country = $c;
		}

		$dbQuery = "SELECT `value` FROM `ad_messages_info`
								WHERE
									`id` = '" . $id . "'
									AND `country` = '" . $country . "'
									AND `lang` = '" . $lang . "'";
		$query->query($mdb, $dbQuery);
		if ($query->num_rows() > 0) {
			return $query->getOne();
		} else {
			return $name;
		}
	} else {
		return $name;
	}

}

function gLA($name, $value  = '', $lang = '', $global = false, $js = false) {
	$mdb = &loadLibClass('db');
	$cfg = &loadLibClass('config.cms');

	$lang = $cfg->getCmsLang();

	$moduleId = false ;
	if(class_exists('Module') && !$global) {
		$moduleId = Module::$moduleId;

	}

	$dbQuery = "
		SELECT `id` FROM `ad_messages_backend` m
		WHERE m.`name` = '" . $name . "'
	";

	$dbQuery .= " LIMIT 0 , 1 ";
	$query = new query($mdb, $dbQuery);

	if ($query->num_rows() > 0) {
		$query->getrow();
		$id = $query->field('id');

		$dbQuery = "
			SELECT `value` FROM `ad_messages_backend_info`
			WHERE
				`id` = '" . $id . "'
				AND `lang` = '" . $lang . "'
		";
		$query->query($mdb, $dbQuery);

		if ($query->num_rows() > 0) {
			return $query->getOne();
		}
		else {
			return $value;
		}
	} else {

		$message_data = array(
			'name' => $name,
			'enable' => 1,
			'date' => time()
		);

		if($moduleId){
			$message_data['module_id'] = $moduleId;
		}

		if($js){
			$message_data['js'] = 1;
		}

		$mId_new = saveValuesInDb('ad_messages_backend', $message_data);

		if($mId_new){
			$message_values = array(
				'id' => $mId_new,
				'lang' => 'en',
				'value' => $value
			);
			saveValuesInDb('ad_messages_backend_info', $message_values);
		}

		return $value;
	}

}

/**
 * Getting default site language
 */
function getDefaultLang($c = '') {
	$mdb = &loadLibClass('db');
	$cfg = &loadLibClass('config');

	if ($cfg->get('defaultLang')) {
		return $cfg->get('defaultLang');
	}

	$dbQuery = "SELECT `lang` FROM `ad_languages` l, `ad_languages_to_ct` lc
						WHERE lc.lang_id = l.id
								AND lc.country_id = '" . ($c ? $c : getCountry()) . "'
								AND lc.default = '1'";

	$query = new query($mdb, $dbQuery);

	if($lang = $query->getOne()) {
		$cfg->set('defaultLang', $lang);
		return $lang;
	} else {

        // we should return 'lv' to avoid site and api breaks
        $cfg->set('defaultLang', 'lv');
        return 'lv';

		//logWarn("Error: no set default language!", __FILE__, __LINE__);
		//showError("Error: no set default language!");
	}
}

/**
 * Checking for enabled current site language
 *
 * @param string	current site language
 */
function checkLangEnabled($lang) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `id` FROM `ad_languages` WHERE `enable` = '1' AND `lang` = '" . mres($lang) . "' LIMIT 0,1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows() > 0) {

		return true;
	}

	return false;
}

/**
 * Redirect to default site page
 *
 */
function openDefaultPage() {

	$pageId = getDefaultPageId(getS('ad_language'));

    $url = getLink($pageId);

    // if there is no url for default page on given language,
    // we set lang to default and try to get def page url again

    if(!$url) {

        $_SESSION['ad_language'] = getDefaultLang();
        $id = getDefaultPageId(getS('ad_language'));
        $url = getLink($id);
    }

    // Show error if unsuccessful

    if(!$url) {

        showError("ERROR! Have not founded default page!");
    }

    redirect($url);
}


/**
 * Getting default site language page id
 */
function getDefaultPageId($lang = '') {
	$mdb = &loadLibClass('db');

	if ($lang && $id = getLangMainPage($lang)) {
		return $id;
	}

	$dbQuery = "SELECT `main_id` FROM `ad_languages_to_ct` WHERE `default` = '1' AND `country_id` = '" . getCountry() . "'";
	$query = new query($mdb, $dbQuery);

	if ($query->num_rows() > 0) {
		$id = $query->getOne();
		if (!$id) {
			showError("ERROR! Have not founded default page!");
		}

		return $id;
	} else {
		showError("ERROR! Have not founded default page!");
	}
}

/**
 * Getting default page url
 */
function getDefaultPageUrl() {
	$mdb = &loadLibClass('db');

	if (getS('ad_language') && getS('ad_language') != getDefaultLang() && $id = getLangMainPage(getS('ad_language'))) {

        $link = getLink($id);

        if(!$link) {
            return $_SERVER["REQUEST_URI"];
        }

		redirect($link . '?' . http_build_query($_GET));
	}

	$dbQuery = "SELECT `url` FROM `ad_languages_to_ct` ct, `ad_content` c WHERE
				`ct`.`default` = '1' AND `ct`.`country_id` = '" . getCountry() . "'
				AND `c`.`id` = `ct`.`main_id`";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows() > 0) {
		$url = $query->getOne();
		if (!$url) {
			showError("ERROR! Have not founded default page!");
		}

		return makeUrlWithLangInTheEnd($url, false);
	} else {
		showError("ERROR! Have not founded default page!");
	}
}

/**
 * Get page url by id
 *
 * @param int	page id
 */
function getLink($id) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `url` FROM `ad_content` WHERE `id` = '" . $id . "' LIMIT 0,1";
	$query = new query($mdb, $dbQuery);

	if ($query->num_rows() > 0) {

		return AD_WEB_FOLDER . makeUrlWithLangInTheEnd($query->getOne());
	}
	else {
		logWarn("ERROR! Have not founded this page! ID: " . $id);
	}
}

/**
 * Get page url by id
 *
 * @param int	page id
 */
function getDocUrl($id) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `page_url` FROM `mod_news` WHERE `id` = '" . $id . "' LIMIT 0,1";
	$query = new query($mdb, $dbQuery);

	if ($query->num_rows() > 0) {

		return $query->getOne();
	}

}

/**
 * Get page mirror
 *
 * @param int		page id
 * @param int		country id
 * @param string	language
 */
function getMirror($id, $c = '', $l = '') {
	$mdb = &loadLibClass('db');
	$m = &loadLibClass('mirrors');

	$c = $c ? $c : (class_exists('Module') ? Module :: getCountry() : getDefLangInCountry($c));
	$l = $l ? $l : (class_exists('Module') ? Module :: getLang() : getDefLangInCountry($c));

	if (@isset($m->alias[$id]) && @isset($m->mirrors[$m->alias[$id]][$c][$l]["id"])) {
		return $m->mirrors[$m->alias[$id]][$c][$l]["id"];
	}
	else {

		$dbQuery = "
			SELECT `mirror_id`
			FROM `ad_content`
			WHERE
				`id` = '" . $id . "' AND `mirror_id` <> ''
		";
		$query = new query($mdb, $dbQuery);

		if ($query->num_rows() > 0) {

			$m->alias[$id] = $query->getOne();

			$dbQuery = "
				SELECT `id`, `url`
				FROM `ad_content`
				WHERE
					`mirror_id` = '" . $m->alias[$id] . "'
					AND `lang` = '" . $l . "'
					AND `country` = '" . $c . "' LIMIT 1";
			$query = new query($mdb, $dbQuery);

			if ($query->num_rows() > 0) {
				$query->getrow();

				$m->mirrors[$m->alias[$id]][$c][$l]["id"] = $query->field("id");
				$m->mirrors[$m->alias[$id]][$c][$l]["url"] = makeUrlWithLangInTheEnd($query->field("url"));

				return $query->field("id");
			}
			else {
				return $id;
			}
        } else {
            $dbQuery = "
			SELECT `url`
			FROM `ad_content`
			WHERE
				`id` = '" . $id . "'";
            $query = new query($mdb, $dbQuery);

            if ($query->num_rows() > 0) {
                $query->getrow();
                $urlWithNoMirror = explode('/', $query->field("url"));

                if (checkLangEnabled($urlWithNoMirror[0])){
                    $urlWithNoMirror[0] = getDefaultLang();
                    $defLangUrlMirror = implode('/', $urlWithNoMirror);

                    $dbQuery = "
			SELECT `id`
			FROM `ad_content`
			WHERE
				`url` = '" . $defLangUrlMirror . "'";
                    $query = new query($mdb, $dbQuery);

                    if ($query->num_rows() > 0) {
                        $query->getrow();
                        $m->alias[$id] = $query->field("id");
                        $m->mirrors[$m->alias[$id]][$c][$l]["id"] = $id;
                    }
                }
            }
            return $id;
        }
	}
}

/**
 * Make url with lang in the end
 * if enabled this
 *
 * @param string	url
 */
function makeUrlWithLangInTheEnd($url, $remove = false) {


	$cfg = &loadLibClass('config');

	if ($cfg->get("langInTheEnd")) {

		if ($url[strlen($url) - 1] == '/') {
			$url = substr($url, 0, -1);
		}

		$url = explode("/", $url);

		if (checkLangEnabled($url[0]) && $remove) {
			$lang = '';

		} else {
			$lang = $url[0] . '/';
		}

		unset($url[0]);

		$path = '';
		if (count($url) > 0) {
			$path = implode("/", $url) . '/';
		}

		return $lang . $path;

	} else {
		return $url;
	}
}

/**
 * Get link by mirror
 *
 * @param int		page id
 * @param int		country id
 * @param string	language
 */
function getLM($id, $c = '', $l = '') {

	return getLink(getMirror($id, $c, $l));
}

/**
 * Get link by mirror
 * Used only in smarty. Collect all ids in array.
 * In the end assign all URL's
 *
 * @param int		page id
 */
function getLinkByMirror($id) {

	if ($id) {
		if (!in_array($id, Module :: $linkIds)) {
			Module :: $linkIds[] = $id;
		}
		return '{{' . $id . '}}';
	}

}

/**
 * Get default country link
 *
 * @param int	country id
 */
function getDCountryLink($id) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `domain` FROM `ad_countries_domains` WHERE `country_id` = '" . $id . "' AND `default` = '1' LIMIT 0,1";
	$query = new query($mdb, $dbQuery);

	return 'http://' . $query->getOne();
}

/**
 * Get language main page
 *
 * @param string 	language value
 */
function getLangMainPage($lang) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `main_id` FROM `ad_languages` l, `ad_languages_to_ct` lc WHERE lc.lang_id = l.id  AND lc.country_id = '" . getCountry() . "' AND l.lang = '" . $lang . "' AND l.enable = '1' LIMIT 0,1";
	$query = new query($mdb, $dbQuery);

	if ($query->num_rows() > 0) {
		return $query->getOne();
	}

	return false;
}

/**
 * Get current country
 *
 */
function getCountry() {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `country_id` FROM `ad_countries_domains` WHERE `domain` = '" . (isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "") . "' ORDER BY `id` ASC LIMIT 1";

	$query = new query($mdb, $dbQuery);

	if(!$query->num_rows()) {
	    return 1;
    }

	return $query->getOne();
}


/**
 * Print out value from siteData. If given name exist in db gets it, if name doesn't exist function will create one
 * @param string $name Field naem
 * @param string $tab Tab where to put this field
 * @param string $block Blpck where to put this field
 * @param string $value = '' Field value for all languages
 * @param string $type = 'text' Field type f.e. text textarea checkbox radio etc
 * @param int $mlang = '0' Multilanguage support for current field on = 1, off = 0
 * @return string $value Current field value
 * @author Maris Melnikovs <maris.melnikovs@efumo.lv>
 */
function getSiteData($name, $tab, $block, $value = '', $type = 'text', $mlang = '0', $required = '0', $title = ''){

	if (!empty($name) || !empty($tab) || !empty($block)) {
		$db = &loadLibClass('db');
		$check = new query($db, "SELECT `id` FROM `ad_sitedata` WHERE `name` = '$name'");

		if ($check->num_rows() > 0) {
			$id = $check->getOne();
			if ($mlang == true) {
				$select = new query($db, "SELECT `value` FROM `ad_sitedata_values` WHERE `fid` = '". $id ."' AND `lang` = '". Module :: getLang() ."'");
			}
			else {
				$select = new query($db, "SELECT `value` FROM `ad_sitedata_values` WHERE `fid` = '". $id ."'");
			}
			$select->num_rows();
			$value = $select->getOne();
			return $value;
		}
		else {
			$type = (empty($type)) ? 'text' : $type;
			$insert = new query($db, "
				INSERT INTO `ad_sitedata`
				( `name`, `tab`, `block`, `title`, `type`, `mlang`, `required`)
				VALUES( '".$name."', '".$tab."', '".$block."', '".$title."', '".$type."', '".$mlang."', '".$required."')
			");
			$last_id = $db->get_insert_id();

			if ($mlang == true) {
				$values_query = "INSERT INTO `ad_sitedata_values` ( `fid`, `lang`, `value`) VALUES";
				$count = 1;
				foreach (getSiteLangs() as $lng_key => $lng_value) {
					$values_query .= "('". $last_id ."', '". $lng_value['lang'] ."', '".$value."')";
					if($count < count(getSiteLangs())) {
						$values_query .= ", ";
					}
					$count++;
				}
				$values = new query($db, $values_query);
			}
			else {
				$values = new query($db, "
					INSERT INTO `ad_sitedata_values`
					( `fid`,  `value`)
					VALUES('". $last_id ."', '".$value."')
				");
			}

			return $value;
		}
	}
	else {
		return false;
	}

}


/**
 *  Get languages from database
 * @author Rolands Eņģelis <rolands@efumo.lv>
 * @return array
 */
function getLanguagesForAdmin($return_keys=false){
    $languages=array();
    global $mdb;
    $dbQuery="SELECT `id`, `lang`, `title` FROM `ad_languages` WHERE `enable`='1' ORDER BY sort ASC";
    $query=new query($mdb, $dbQuery);
    $languages_data=$query->getArray();
    if($languages_data){
        foreach($languages_data as $l){
            $key=$return_keys!==false ? $l["lang"] : $l["id"];
            $languages[$key]=$l["title"];
        }
    }
    return $languages;
}

/**
 * @param null $lang
 * @return mixed|null
 */
function getLang($lang = null) {
    if (!$lang) {

        if (isset($_SESSION['ad_language']) && $lang = $_SESSION['ad_language']) {
            return $lang;
        }

        // TODO $this not exist in this context.
        // TODO Only in some cases this part is reached
        // TODO     lib/api.base consultation_vcrCreated
        // TODO         Assumption: mails/mails prepare_mailPatientVroomShared() -> getLM()
        // $lang = $this->setLang(getDefaultLanguage($this->getCountry()));
        $lang = 'lv';
    }

    return $lang;
}

/*
 * Function to strip only selected (passed) HTML tags from input HTML text string including it's content
 *
 * Examples:
 *
 * // The tags from the $leave_only_tags array will be returned, the rest of tags will be removed
 * $input_html = '<h1>H1 example</h1><h4>Correct title</h4><p>Main body<br>Second line.</p>';
 * $leave_only_tags = array('p','br','h4','a','span','b','i','em');
 * $result = strip_html_tags($input_html, $leave_only_tags);
 *
 * // The tags from the $remove_tags array will be removed, the rest of tags will be returned
 * $input_html = '<h1>H1 example</h1><h4>Correct title</h4><p>Main body<br>Second line.</p>';
 * $remove_tags = array('p','br','h4','a','span','b','i','em');
 * $result = strip_html_tags($input_html, $remove_tags, TRUE);
 *
 * // The tags from the $remove_tags array will be stripped, them content and the rest of tags will be returned
 * $input_html = '<h1>H1 example</h1><h4>Correct title</h4><p>Main body<br>Second line.</p>';
 * $remove_tags = array('p','br','h4','a','span','b','i','em');
 * $result = strip_html_tags($input_html, $remove_tags, TRUE, TRUE);
 *
*/

function strip_html_tags($text, $tags = '', $invert = FALSE, $leave_removed_content = FALSE) {

    if(empty($text)) return $text;
    if(empty($tags) && $leave_removed_content) return strip_tags($text);
    if(is_string($tags)) $tags = explode(',',$tags);
    $tags = array_map(function ($v) { return str_replace(array('<','>','/','&',';'),'',$v); }, $tags);
    if(empty($tags) && $leave_removed_content) return strip_tags($text);
    $tags = '<'.implode('><',$tags).'>';
    if($leave_removed_content) return strip_tags($text,$tags);

    preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
    $tags = array_unique($tags[1]);

    if(is_array($tags) AND count($tags) > 0) {
        if($invert == FALSE) {
            return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
        } else {
            return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text);
        }
    } elseif($invert == FALSE) {
        return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
    }
    return $text;
}

function refreshSession()
{
    $cfg = &loadLibClass('config');
    $lifetime = (int)$cfg->get('apacheSessionLifetime');
    $sessData = $_SESSION;

    session_abort();
    session_unset();
    session_write_close();
    session_start();
    setcookie(session_name(),session_id(),time() + $lifetime);

    $_SESSION = $sessData;
    unset($cfg, $lifetime, $sessData);
}

// Function to detect the passed array is a hash
// Returns true or false
function isHash($arr)
{
    if(!is_array($arr)) return false;
    if(sizeof($arr) == 0) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

// Function to compose "Multi Insert" SQL statement from input data array
//
// example:
// 1.1 - OLD code
// foreach($arr as $item) {
//    $SQL->mquery('INSERT INTO table_name SET created = NOW(), user_id = '.$item['user_id']);
// }
//
// 1.2 - NEW code
//
// $insert_data = array();
// foreach($arr as $item) {
//    $data_arr = array();
//    $data_arr['created'] = 'NOW()';
//    $data_arr['user_id'] = $item['user_id'];
//    array_push($insert_data,$data_arr);
// }
//
// if(sizeof($insert_data) > 0)
//    COMMON::execMultiInsertSQL($SQL, 'table_name', $insert_data, false);
// ------------------------------------------------------------------------
// Executing SQL will looks like the following:
//
// INSERT INTO table_name (created, user_id) VALUES (NOW(), 1), (NOW(), 2), ..., (NOW(), N);
//
// and will be executed only 1 time, unstead of "sizeof($arr)" times.
//
function execMultiInsertSQL($db, $table_name, $data_arr, $insert_limit = 100, $ins_ignore = false)
{
    if(empty($insert_limit) && $insert_limit !== 0) $insert_limit = 100;
    if(!is_object($db)) return 'SQLexec is not an object';
    if(empty($table_name)) return 'Table name is empty';
    if(!is_array($data_arr)) return 'Insert data not an array';
    if(empty($data_arr)) return 'Insert data array is empty';
    if(!isHash(@$data_arr[0])) return 'Insert data array elements are not a hash'; // the $data_arr element is not a hash

    $fld_list = '('. implode(',', array_keys($data_arr[0])) . ')';
    $sql_header = "INSERT ".(($ins_ignore) ? 'IGNORE' : '')." INTO ".$table_name. ' '. $fld_list ." VALUES ";
    $data_list = array();

    foreach($data_arr as $i => $v) {

        if(!isset($data_arr[$i]) || empty($data_arr[$i])) {
            continue;
        }

        $values_arr = array();

        foreach($data_arr[$i] as $key => $val) {

            if($val===NULL) {
                $values_arr[] = 'NULL';
                continue;
            }

            preg_match("/^[A-Z0-9_]\((.*)?\)/", $val, $match);
            $values_arr[] = (sizeof($match) > 0) ? $val : '"' . addslashes($val) . '"';
        }

        array_push($data_list, "(".implode(", ",array_values($values_arr)).")");
    }

    if(sizeof($data_list) > 0) {

        if($insert_limit == 0) {

            try {

                $sql = $sql_header . implode(',',$data_list) . ';';

                if(is_array($sql)) {
                    $sql = implode(';', $sql);
                }

                doQuery($db, $sql);

            } catch (Exception $e) {
                print_r(__FILE__.' ('.__LINE__.') '.__FUNCTION__.': '. $e->getMessage());
                return $e->getMessage();
            }

        } else {

            $exec_count = intval(sizeof($data_list) / $insert_limit);
            $exec_count = $exec_count < 1 ? 1 : $exec_count;

            for($j = 0; $j < $exec_count; $j++) {

                $partial_list = $data_list;
                $data_list = array_splice($partial_list, $insert_limit);
                $sql = $sql_header . implode(',',$partial_list);

                try {

                    if(is_array($sql)) {
                        $sql = implode(';', $sql);
                    }

                    doQuery($db, $sql);

                } catch (Exception $e) {
                    print_r(__FILE__.' ('.__LINE__.') '.__FUNCTION__.': '. $e->getMessage());
                    return $e->getMessage();
                }
            }
        }
    }
    return null;
}

// check if url available
function isDomainAvailible($domain)
{
    //check, if a valid url is provided
    if(!filter_var($domain, FILTER_VALIDATE_URL))
    {
        return false;
    }

    //initialize curl
    $curlInit = curl_init($domain);

    curl_setopt($curlInit, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, 1);

    curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,5);
    curl_setopt($curlInit,CURLOPT_HEADER,true);
    curl_setopt($curlInit,CURLOPT_NOBODY,true);
    curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

    $response = curl_exec($curlInit);
    curl_close($curlInit);

    if ($response) {
        return true;
    }

    return false;
}

function getUUID()
{
    if (function_exists('com_create_guid') === true)
        return trim(com_create_guid(), '{}');

    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * @param $id
 * @return array|int|null
 */
function getProfileById($id)
{
    global $mdb;

    $dbQuery = "SELECT * FROM mod_profiles 
                WHERE
                    id = " . mres($id);
    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {
        return $query->getrow();
    }

    return null;
}

function removeTags($html, $tags = '') {

    if(!empty($html)) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $purifier = new HTMLPurifier($config);
        $html = $purifier->purify($html);
    }

    return $html;

//    $html = trim($html);
//
//    if(empty($html)) {
//        return $html;
//    }
//
//    $tagsArr = explode(',', $tags);
//    $filteredString = $html;
//
//    if(!empty($tagsArr)) {
//
//        foreach ($tagsArr as $tag) {
//
//            $tag = trim($tag);
//
//            $dom = new DOMDocument();
//            $dom->loadHTML(mb_convert_encoding($filteredString, 'HTML-ENTITIES', 'UTF-8'));
//
//            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $item) {
//                $item->parentNode->removeChild($item);
//            }
//
//            $filteredString = $dom->saveHTML();
//        }
//
//    }
//
//    return $filteredString;
}

function getCurrentUrl()
{
    // get the https or http protocol of the url

    $getProtocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

    // get the name of the current page's domain

    $getDomain =  $_SERVER['HTTP_HOST'];

    //Read the requested resource

    // get the uri of the requested location of the resource

    $getResource = $_SERVER['REQUEST_URI'];

    // get the value of the query string

    $getQuery = $_SERVER['QUERY_STRING'];

    // now append all variables storing the URL

    // address in parts

    $getUrl = $getProtocol.$getDomain.$getResource;

    return $getUrl;
}

?>