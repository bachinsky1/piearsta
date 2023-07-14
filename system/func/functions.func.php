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
 * Adweb functions file
 * 15.02.2010
 */

/**
 * Clear input GET, POST, COOKIE data.
 */
// $_POST = cleanInputData($_POST);
// $_GET = cleanInputData($_GET);
// $_COOKIE = cleanInputData($_COOKIE);

/**
 * Saving or updating DB data from array
 *
 * @param string	 table name in DB
 * @param array 	 key: field name, value: field value
 * @param int		 record id value if updating
 */

/**
 * @param $tableName
 * @param $varsArray
 * @param string $recId
 * @param bool $multiInsert
 * @param bool $first
 * @return bool|string
 */
function saveValuesInDb($tableName, $varsArray, $recId = "", $multiInsert = false, $first = false) {

    /** @var db $mdb */
	$mdb = &loadLibClass('db');

	// Checking $recId, if isset updating, else saving

	if ($recId === 0 || $recId > 0) {

		// Updating

		$dbQuery = "UPDATE " . $tableName . " SET ";
		reset($varsArray);

		while (list($fieldName, $fieldValue) = each($varsArray)) {

            if($fieldValue === 0) {
                $fieldValue = '0';
            }

			if ($fieldValue === 'null') {
                $dbQuery .= " `" . $fieldName . "` = null,";
			} else {
				$dbQuery .= " `" . $fieldName . "` = '" . mres($fieldValue) . "',";
			}
		}

		// Removing last ',' from DB query

		$dbQuery = substr($dbQuery, 0, (strlen($dbQuery) - 1));

		// Creating where string

		$dbQuery .= " WHERE `id` = '" . mres($recId) . "'";

	} else {

		// Saving

		$fieldsLists = "";
		$fieldsValues = "";

		if ($multiInsert) {

			$dbQuery = "";
			reset($varsArray);
			while (list($fieldName, $fieldValue) = each($varsArray)) {
				$fieldsValues .= "'" . mres($fieldValue) . "',";
			}

			$fieldsValues = substr($fieldsValues, 0, (strlen($fieldsValues) - 1));

			$dbQuery .= " , (" . $fieldsValues . ") ";
			return $dbQuery;

		} else {

			$dbQuery = "INSERT INTO " . $tableName . " (";
			reset($varsArray);

			while (list($fieldName, $fieldValue) = each($varsArray)) {
				$fieldsLists .= "`" . $fieldName . "`,";

				if ($fieldValue == 'null') {
					$fieldsValues .= "null,";
				} else {
					$fieldsValues .= "'" . mres($fieldValue) . "',";
				}

			}

			// Removing last ',' from fields lists query and from fields values query

			$fieldsLists = substr($fieldsLists, 0, (strlen($fieldsLists) - 1));
			$fieldsValues = substr($fieldsValues, 0, (strlen($fieldsValues) - 1));

			$dbQuery .= $fieldsLists . ") VALUES (" . $fieldsValues . ")";

			if ($first) {
				return $dbQuery;
			}
		}
	}

    $query = new query($mdb, $dbQuery);
	$query->free();

	if ($recId)
		$returnId = $recId;
	else
		$returnId = $mdb->get_insert_id();

	return $returnId;
}

/**
 * Deleting data from DB by id
 *
 * @param string 	table name in DB
 * @param int/array		Record id
 * @param string	field name, default value: id
 * @param string	sql exceptions
 */
function deleteFromDbById($tableName, $recId, $field = "id", $exSqlParams = "") {
	$mdb = &loadLibClass('db');

	if ($recId && $tableName) {
		$dbQuery = "DELETE FROM `" . $tableName . "` WHERE " . (is_array($recId) ? "`" . $field . "` IN (" . implode(",", $recId) . ")" : "`" . $field . "` = '" . $recId . "'") . $exSqlParams;
		$query = new query($mdb, $dbQuery);
		$query->free();
	}
}

/**
 * Clear text: htmlspecialchars and spaces
 *
 * @param  string	text
 */
function clearText($text) {

	if (is_array($text)) {
		array_map('clearText', $text);
	} else {
		return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
	}
}

/**
 * Clear text: htmlentities and stripslashes
 *
 * @param  string	text
 */
function clear($text, $strip = true) {

	if (is_array($text)) {
		array_map('clear', $text);
	} else {
		return htmlentities(trim(($strip ? stripslashes($text) : $text)), ENT_QUOTES, 'UTF-8');
	}
}

function hsc($text){
    if (is_array($text)) {
		array_map('hsc', $text);
	} else {
		return htmlspecialchars($text);
	}
}

/**
 * Check for valid email address
 *
 * @param string	 email address
 */
function isValidEmail($email) {
	$emailRegEx = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$/i";
	return preg_match($emailRegEx, $email);
}

/**
 * Checking if on server is json_decode function
 * else creating own json_decode function
 *
 */
if (!function_exists('json_decode')) {
	function json_decode($content, $assoc = false){
		loadLibClass('json', false);

		if ($assoc) {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		}
		else {
			$json = new Services_JSON;
		}

		return $json->decode($content);
    }
}

/**
 * Checking if on server is json_encode function
 * else creating own json_encode function
 *
 */
if (!function_exists('json_encode')) {
	function json_encode($content){
		loadLibClass('json', false);
		$json = new Services_JSON;

		return $json->encode($content);
	}
}

/**
 * Free MySQL or other resources on the end of the script
 */
function freeResources() {

	$mdb = &loadLibClass('db');
	$mdb->close();
}

/**
 * Adding slashes in data from outside array such GET, POST etc
 *
 * @param	string value
 */
function addSlashesDeep($value) {

	$value = is_array($value) ? array_map('addSlashesDeep', $value) : addslashes($value);
	return $value;
}

/**
 * Clean Input Data
 *
 * This is a helper function. It escapes data and
 * standardizes newline characters to \n
 *
 * @access	private
 * @param	mix
 * @return	mix
 */
function cleanInputData($str) {

	if (is_array($str)) {
		$new_array = array();
		foreach ($str as $key => $val) {
			$new_array[cleanInputKeys($key)] = cleanInputData($val);
		}
		return $new_array;
	}

	// We strip slashes if magic quotes is on to keep things consistent
	if (get_magic_quotes_gpc()) {
		$str = stripslashes($str);
	}
	else {
		$str = addSlashesDeep($str);
	}

	// Should we filter the input data?
	$cfg = &loadLibClass('config');
	if ($cfg->get("use_xss_clean") === true) {
		// TODO
		//$str = xssClean($str);
	}

	// Standardize newlines
	if (strpos($str, "\r") !== false) {
		$str = str_replace(array("\r\n", "\r"), "\n", $str);
	}

	return $str;
}

/**
 * Clean Keys
 *
 * This is a helper function. To prevent malicious users
 * from trying to exploit keys we make sure that keys are
 * only named with alpha-numeric text and a few other items.
 *
 * @access	private
 * @param	string
 * @return	string
*/
function cleanInputKeys($str) {

	if (!preg_match("/^[a-z0-9:_\/-]+$/i", $str)) {
		showError('Disallowed Key Characters.', 500);
	}

	return $str;
}

/**
 * Fetch from array
 *
 * This is a helper function to retrieve values from global arrays
 *
 * @access	private
 * @param	array
 * @param	string
 * @param	bool
 * @param	string	default value
 * @return	string
 */
function fetchFromArray(&$array, $index = '', $xss_clean = false, $default = '') {

	$value = false;

	$keys = explode("/", $index);

	if (($cnt = count($keys)) > 0) {
		for ($i = 0; $i < $cnt; $i++) {
			if ($i == 0) {
				if (isset($array[$keys[$i]])) {
					$value = $array[$keys[$i]];
				} else {

					if (!empty($default)) {
						return $default;
					}

					return false;
				}
			} else {
				if (isset($value[$keys[$i]])) {
					$value = $value[$keys[$i]];
				} else {
					if (!empty($default)) {
						return $default;
					}

					return false;
				}
			}

		}
	}

	if ($xss_clean === true) {

		return strip_tags($value);
	}

	return $value;
}

/**
 * Fetch an item from the GET array
 *
 * @access	public
 * @param	string
 * @param	bool
 * @param	string	default value
 * @return	string
 */
function getG($index = '', $xss_clean = false, $default = '') {
	return fetchFromArray($_GET, $index, $xss_clean, $default);
}

/**
 * Fetch an item from the POST array
 *
 * @access	public
 * @param	string
 * @param	bool
 * @param	string	default value
 * @return	string
 */
function getP($index = '', $xss_clean = false, $default = '') {
	return fetchFromArray($_POST, $index, $xss_clean, $default);
}

/**
 * Fetch an item from either the GET array or the POST
 *
 * @access	public
 * @param	string	The index key
 * @param	bool	XSS cleaning
 * @param	string	default value
 * @return	string
 */
function getGP($index = '', $xss_clean = false, $default = '') {
	if (!isset($_POST[$index])) {
		return getG($index, $xss_clean, $default);
	} else {
		return getP($index, $xss_clean, $default);
	}
}

/**
 * Fetch an item from the COOKIE array
 *
 * @access	public
 * @param	string
 * @param	bool
 * @param	string	default value
 * @return	string
 */
function getC($index = '', $xss_clean = false, $default = '') {
	return fetchFromArray($_COOKIE, $index, $xss_clean, $default);
}

/**
 * Fetch an item from the SESSION array
 *
 * @access	public
 * @param	string
 * @param	bool
 * @param	string	default value
 * @return	string
 */
function getS($index = '', $xss_clean = false) {
	return fetchFromArray($_SESSION, $index, $xss_clean);
}

/**
 * Fetch an item from the SERVER array
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	string
*/
function server($index = '', $xss_clean = false) {
	return fetchFromArray($_SERVER, $index, $xss_clean);
}

/**
 * Fetch the IP Address
 *
 * @access	public
 * @return	string
 */
function getIp() {

	if (isset($_SERVER['REMOTE_ADDR'])) {
		 return $_SERVER['REMOTE_ADDR'];
	} else {
		return false;
	}
}

/**
 * Validate IP Address
 *
 * @access	public
 * @param	string
 * @return	string
 */
function validIp($ip) {

	$ip_segments = explode('.', $ip);

	// Always 4 segments needed
	if (count($ip_segments) != 4) {
		return false;
	}

	// IP can not start with 0
	if ($ip_segments[0][0] == '0') {
		return false;
	}

	// Check each segment
	foreach ($ip_segments as $segment) {
		// IP segments must be digits and can not be
		// longer than 3 digits or greater then 255
		if ($segment == '' OR preg_match("/[^0-9]/", $segment) OR $segment > 255 OR strlen($segment) > 3) {
			return false;
		}
	}

	return true;
}

function sync_session() {

        // return if no config and session
        $sync_config = check_sync_config();
        if(!isset($sync_config)) return null;
        $sess_file_name = get_sess_file_name($sync_config);
        if(!isset($sess_file_name)) return null;

        if(!empty($sync_config)) {
            // set default ssh port and linux file mode
            $ssh_port = 22; $file_mode = 0777;
            // make local and remote file names
            $local_file = $sync_config['sync_local_path'].'/'.$sess_file_name;
            $remote_file = $sync_config['sync_remote_path'].'/'.$sess_file_name;

            // start sending if session file exists locally
            $check = checkURL($sync_config['sync_remote_host'].':'.$ssh_port,'tcp',1);

            if(file_exists($local_file) && ($check > 0)) {
                // ssh connect
                $con = ssh2_connect($sync_config['sync_remote_host'],$ssh_port);
                if(isset($con)) {
                    // ssh auth
                    if(ssh2_auth_password($con,$sync_config['sync_remote_user'],$sync_config['sync_remote_pass'])) {
                        // ssh scp session file to the remote host
                        ssh2_scp_send($con,$local_file,$remote_file,$file_mode);
                        // close remote connection
                        ssh2_exec($con, 'exit');
                        unset($con);
                    }
                }
            }
        }
}

function remove_session_file() {
        // return if no config and session
        $sync_config = check_sync_config();
        if(!isset($sync_config)) return null;

        $sess_file_name = get_sess_file_name($sync_config);
        if(!isset($sess_file_name)) return null;

        if(!empty($sync_config)) {
            // set default ssh port and linux file mode
            $ssh_port = 22;
            // make remote session file name
            $remote_file = $sync_config['sync_remote_path'].'/'.$sess_file_name;

            // start sending if session file exists locally
            $check = checkURL($sync_config['sync_remote_host'].':'.$ssh_port,'tcp',1);
            if($check > 0) {
                // ssh connect
                $con = ssh2_connect($sync_config['sync_remote_host'],$ssh_port);
                if(isset($con)) {
                    // ssh auth
                    if(ssh2_auth_password($con,$sync_config['sync_remote_user'],$sync_config['sync_remote_pass'])) {
                        // ssh remove session file from the remote host
                        if($sftp = ssh2_sftp($con)) ssh2_sftp_unlink($sftp,$remote_file);
                        // close remote connections
                        if($sftp) unset($sftp);
                        ssh2_exec($con, 'exit');
                        unset($con);
                    }
                }
            }
        }
}

function check_sync_config()
{
    // return if no session ID
    if(!isset($_SESSION)) return null;
    // set current session ID
    $sess_id = session_id();
    if(!isset($sess_id) || ($sess_id == '')) return null;
    // process only filled session
    if(!isset($_SESSION['user'])) return null;

    // load and check sync config
    $cfg = &loadLibClass('config');
    $check_keys = array('sync_remote_host','sync_remote_user','sync_remote_pass','sync_remote_path','sync_local_path');
    foreach($check_keys as $cfg_key) { $sync_config[$cfg_key] = $cfg->get($cfg_key); }
    $sync_config = array_filter($sync_config);
    foreach($check_keys as $cfg_key) { if(!isset($sync_config[$cfg_key])) return null; }

    // make session file name
    $sync_config['sync_session_prefix'] = $cfg->get('sync_session_prefix');
    $sync_config['sync_session_suffix'] = $cfg->get('sync_session_suffix');
    return $sync_config;
}

function get_sess_file_name($sync_config = null)
{
    if(empty($sync_config)) return null;
    $sess_id = session_id();
    $sess_file_name = (isset($sync_config['sync_session_prefix'])) ? $sync_config['sync_session_prefix'].$sess_id : $sess_id;
    if(isset($sync_config['sync_session_suffix']) && !empty($sync_config['sync_session_suffix']))
        $sess_file_name = $sess_file_name.$sync_config['sync_session_suffix'];
    return $sess_file_name;
}

function checkURL($host, $proto = 'tcp', $timeout = 2)
{
        $url_data = parseURL($host);
        if($url_data['scheme'] == 'http') $port = 80;
        if($url_data['scheme'] == 'https') $port = 443;
        if(!isset($url_data['port'])) $url_data['port'] = $port;
        $url_data['scheme'] = (isset($proto)) ? $proto : 'tcp';
        if(preg_match('/\./',$url_data['path'])) {
            $fn = pathinfo($url_data['path'], PATHINFO_BASENAME);
            $url_data['path'] = preg_replace("/$fn/",'',$url_data['path']);
        }
        $host = glueURL($url_data);
        $tB = microtime(true);
        $oldErrorReporting = error_reporting();
        error_reporting($oldErrorReporting ^ E_WARNING);
        $fP = stream_socket_client($host, $errno, $errstr, $timeout);
        error_reporting($oldErrorReporting);
        if (!$fP) { return -1; }
        $tA = microtime(true);
        return round((($tA - $tB) * 1000), 4);
}

function parseURL($url)
{
        $r  = "(?:([a-z0-9+-._]+)://)?";
        $r .= "(?:";
        $r .=   "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
        $r .=   "(?:\[((?:[a-z0-9:])*)\])?";
        $r .=   "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
        $r .=   "(?::(\d*))?";
        $r .=   "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
        $r .=   "|";
        $r .=   "(/?";
        $r .=     "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
        $r .=     "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
        $r .=    ")?";
        $r .= ")";
        $r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
        $r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
        preg_match("`$r`i", $url, $match);
        $parts = array(
            "scheme"=>'',
            "userinfo"=>'',
            "authority"=>'',
            "host"=> '',
            "port"=>'',
            "path"=>'',
            "query"=>'',
            "fragment"=>'');
        switch (count ($match)) {
            case 10: $parts['fragment'] = $match[9];
            case 9: $parts['query'] = $match[8];
            case 8: $parts['path'] =  $match[7];
            case 7: $parts['path'] =  $match[6] . $parts['path'];
            case 6: $parts['port'] =  $match[5];
            case 5: $parts['host'] =  $match[3]?"[".$match[3]."]":$match[4];
            case 4: $parts['userinfo'] =  $match[2];
            case 3: $parts['scheme'] =  $match[1];
        }
        $parts['authority'] = ($parts['userinfo']?$parts['userinfo']."@":"").
                               $parts['host'].
                              ($parts['port']?":".$parts['port']:"");
        return array_filter($parts);
}

function glueURL($parsed_url)
{
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
}

/**
 * Check str for int
 *
 * @param string
 */
function int($int) {

	// First check if it's a numeric value as either a string or number
	if(is_numeric($int) === true){

		// It's a number, but it has to be an integer
		if((int)$int == $int){

			return true;

		// It's a number, but not an integer, so we fail
		} else {

			return false;
		}

	// Not a number
	} else {

		return false;
	}
}

/**
 * Make new file name
 *
 * @param string	file name
 * @param string	add before extention
 * @param string	add before file name
 */
function mkFileName($fileName, $add_before_ext, $add_before_filename = '') {

	$extension = strrchr($fileName, '.');
	if ( $extension === '' || strpos($extension, '/') !== false || strpos($extension, '\\') !== false ) {
		$fileName .= $add_before_ext;
	}
	else {
		$fileName = substr($fileName, 0, -strlen($extension)) . $add_before_ext . $extension;
	}

	$fileName = preg_split('#[\\\/]#', $fileName);
	$fileName[count($fileName) - 1] = $add_before_filename . $fileName[count($fileName) - 1];
	$fileName = implode('/', $fileName);

	return $fileName;
}

/**
 * Redirecting function
 *
 * @param string	redirect link
 * @param bool		checking local or global redirect
 */
function redirect($page) {

	header("Location: " . $page, true, 301);

	freeResources();
	exit();
}

/**
 * Json encode and send data
 *
 * @param mixed
 */
function jsonSend($output) {
	 echo json_encode($output, JSON_HEX_QUOT | JSON_HEX_TAG);
	 die();
}

/**
 * Send data by ajax, not array send. If is sending array - must use jsonSend function
 *
 * @param string
 */
function ajaxSend($output) {
	 echo ($output);
	 die();
}

/**
 * Json encode and send data in header
 *
 * @param mixed
 */
function jsonSendInHeader($output) {
	 header('X-JSON: (' . json_encode($output) . ')');
	 die();
}

/**
 * Json decode data
 *
 * @param mixed
 */
function jsonDecode($js) {
	return json_decode(stripcslashes($js), true);
}

/**
 * Getting default site language
 *
 * @param int	country id
 */
function getDefaultLanguage($c) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `lang` FROM `ad_languages` l, `ad_languages_to_ct` lc WHERE lc.lang_id = l.id  AND lc.country_id = '" . $c . "' AND lc.default = '1'";
	$query = new query($mdb, $dbQuery);
	if($query->num_rows() > 0) {
		return $query->getOne();
	}
	else {

		logWarn("Error: no set default language!", __FILE__, __LINE__);
		showError("Error: no set default language!", 500);
	}
}

/**
 * Getting default site country
 */
function getDefaultCountry() {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `id` FROM `ad_countries` ORDER BY `id` ASC LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if($query->num_rows() == 0) {

		logWarn("Error: no set default country!", __FILE__, __LINE__);
		showError("Error: no set default country!", 500);
	}
	else {
		return $query->getOne();
	}
}

/**
 * Get all site countries
 */
function getSiteCountries() {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `id`, `title` FROM `ad_countries` ORDER BY `id` ASC";
	$query = new query($mdb, $dbQuery);

	return $query->getArray();
}

/**
 * Get all site languages
 */
function getSiteLangs() {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `lang`, `title`, `enable` FROM `ad_languages` WHERE `enable` = '1' ORDER BY `sort` ASC";
	$query = new query($mdb, $dbQuery);

	return $query->getArray();
}

/**
 * Get all countries and languages in one array
 *
 * @param int	country id, not required param
 */
function getSiteLAndC($c = 0) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT `id`, `title` FROM `ad_countries` " . ($c ? " id = '" . mres($c) . "'" : "") . " ORDER BY `id` ASC";
	$query = new query($mdb, $dbQuery);
	$result = array();
	while ($query->getrow()) {
		$result[] = array("id" => $query->field('id'),
							"title" => $query->field('title'),
							"langs" => getSiteLangsByCountry($query->field('id')));
	}
	$query->free();

	return $result;
}

/**
 * Get all languages by country
 *
 * @param int	country id
 */
function getSiteLangsByCountry($cId) {
	$mdb = &loadLibClass('db');

	$dbQuery = "SELECT l.`lang`, l.`title`, lc.default FROM `ad_languages` l, `ad_languages_to_ct` lc WHERE lc.lang_id = l.id AND lc.country_id = '" . $cId . "' ORDER BY lc.`default` DESC, l.sort ASC";
	$query = new query($mdb, $dbQuery);

	return $query->getArray();
}

/**
 * Get all languages by country
 *
 * @param int	country id
 */
function getSiteLangsByCountryDD($cId, $lang = '') {
	$mdb = &loadLibClass('db');
	$values = array();

	$dbQuery = "SELECT l.`lang`, l.`title`, lc.default FROM `ad_languages` l, `ad_languages_to_ct` lc WHERE lc.lang_id = l.id AND lc.country_id = '" . $cId . "' ORDER BY lc.`default` DESC, l.sort ASC";
	$query = new query($mdb, $dbQuery);
	while($query->getrow()) {
        $values[$query->field('lang')] = $query->field('title');
    }

	return dropDownFieldOptions($values, $lang, true);
}

/**
 * mysql_real_escape_string function
 *
 * @param string
 */
function mres($param) {

    // simplified replacement for mysql_real_escape_string

    if(is_array($param))
        return array_map(__METHOD__, $param);

    if(!empty($param) && is_string($param)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $param);
    }

    return $param;
}

function sanitize($param)
{
    $param = str_replace('\\', '', $param);
    $param = str_replace('/', '', $param);

    $param = htmlspecialchars($param);
    $param = stripslashes($param);
    $param = trim($param);

    return $param;
}

/**
 * Send mail
 * Use htmlMimeMail class from Lib
 * @uses htmlMimeMail class from Lib
 *
 * @global array $config
 *
 * @param  string $to          Receiver address
 * @param  string $subject     Message subject
 * @param  string $message     Message content
 * @param  array  $aHeaders    Additional headers
 * @param  string $sender      Sender <email>
 * @param  bool   $html        Use HTML mail or text format
 * @param  array  $attachments Attachments (array with filenames or array
 *                             key=filename value=file contents
 * @param  string $type        mail (default) or smtp
 * @param  bool   $reset       Reset e-mail class contents for repeated sending
 * @param  bool   $filesAdv    Embed files (?)
 * @param  string $bcc         BCC receiver
 *
 * @return bool
  */
function sendMail($to, $subject, $message, $aHeaders = array(), $sender = '', $html = true, $attachments = null, $type = 'mail', $reset = true, $filesAdv = false, $bcc = false) {
    global $config;
	/** @var htmlMimeMail $mail */
	$mail = &loadLibClass('htmlMimeMail');
	if ($reset) {
		$mail->resetIsBuild();
		$mail->clearAttachment();
	}


	if ($attachments) {
		if (is_string($attachments)) {
			$attachments = array($attachments);
		}

		foreach ($attachments AS $k => $file ) {

			if ($filesAdv) {
				$fileInfo = array('content' => $file, 'filename' => $k);

				$mail->addAttachment(new fileAttachment($fileInfo, false));
			} else {
				$mail->addAttachment(new fileAttachment($file));
			}

		}
	}

	if ($sender) {
		$mail->setFrom($sender);
	}
	if ($bcc){
		$mail->setBcc($bcc);
	}

	$mail->setSubject($subject);
	$mail->setPriority('normal');

	if ($html) {
		$mail->setHTML($message);
	} else {
		$mail->setText($message);
	}

    if (!empty($aHeaders)) {
        foreach($aHeaders AS $name => $value) {
         $mail->setHeader($name, $value);
        }
    }

    // setSMTPParams(string $host, int $port, string $helo, bool $auth, string $user, string $pass)
    if (isset($config["MAIL_SMTP"])) {

    	$type = 'smtp';

    	$mail->setSMTPParams(
           $config["MAIL_SMTP"],
           25,
           null,
           $config["MAIL_AUTH"],
           '',
           ''
       );
    }

    if (is_array($to)) {
        return $mail->send($to, $type);
    } else {
        return $mail->send(array($to), $type);
    }
}

/**
 * Creating drop down options for html select
 *
 * @param array		key: field name, value: field value
 * @param mixed		if isset: select needed option
 * @param bool		use keys or not
 */
function dropDownFieldOptions($values, $fieldValue = "", $useKeys = false,$debug = true) {


	$outHtml = '';
	if (is_array($values)) {
		reset($values);
		while (list ($key, $value) = each($values)) {
			$key = ($useKeys ? $key : $value);
			$selected = ( ( !empty($fieldValue) && $key == $fieldValue) ? ' selected="selected" ' : '');
			$outHtml .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}
	}

	return $outHtml;
}

function dropDownFieldOptionsLang($values, $fieldValue = "") {

	$outHtml = '';
	if (is_array($values)) {
		foreach ($values as $key => $value ){
			$selected = (( !empty($fieldValue) && $key == $fieldValue) ? ' selected="selected"' : '');
			if ($value["enabled"] == 1)
				$outHtml .= '<option value="' . $key . '"' . $selected. '>' . $value["label"] . '</option>';

		}
	}
	return $outHtml;
}

/**
 * Empty folder
 *
 * @param string	directory
 */
function emptyDir($dir, $recrusive = false) {
	$mydir = opendir($dir);
	while(false !== ($file = readdir($mydir))) {
		if($file != "." && $file != "..") {
			chmod($dir . $file, 0777);
			if(is_dir($dir . $file) && $recrusive) {
				chdir('.');
				emptyDir($dir . $file.'/', $recrusive);
				rmdir($dir . $file);
			} else {
				unlink($dir . $file);
			}
		}
	}
	closedir($mydir);
}

/**
 * Empty cache folder
 *
 * @param string	directory
 */
function emptyCacheDir($dir) {
	foreach(glob($dir . '*.cache') as $v){

		unlink($v);
	}
}

/**
 * Check for exist url
 *
 * @param string	url
 */
function isValidUrl($url) {

	$url = @parse_url($url);

	if (!$url) {
		return false;
	}

	$url = array_map('trim', $url);
	$url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];
	$path = (isset($url['path'])) ? $url['path'] : '';

	if ($path == '') {
		$path = '/';
	}

	$path .= (isset($url['query'])) ? "?" . $url['query'] : '';

	if (isset($url['host']) && $url['host'] != @gethostbyname($url['host'])) {
		$headers = @get_headers($url['scheme'] . "://" . $url['host'] . ":" . $url['port'] . $path);
		$headers = (is_array($headers)) ? implode("\n", $headers) : $headers;
		return (bool)preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers);
    }

    return false;
}

/**
 *
 * Remove Line Breaks
 * @param string
 */
function removeLineBreaks($input) {
	// Order of replacement
	$order   = array("\r\n", "\n", "\r");
	$replace = '';

	// Processes \r\n's first so they aren't converted twice.
	$output = str_replace($order, $replace, $input);

	return $output;
}

/**
 * Cut string to n symbols and add delim but do not break words.
 *
 * Example:
 * <code>
 *  $string = 'this sentence is way too long';
 *  echo neat_trim($string, 16);
 * </code>
 *
 * Output: 'this sentence is...'
 *
 * @access public
 * @param string string we are operating with
 * @param integer character count to cut to
 * @param string|NULL delimiter. Default: '...'
 * @return string processed string
 **/
function cleanTextCut($str, $n, $delim = '...') {
	$len = strlen($str);
	if ($len > $n) {
		return preg_replace("/^(.{1,$n})(\s.*|$)/s", '\\1' . $delim, $str);;
	} else {
		return $str;
	}
}

/**
 * Convert url to correct symbols
 *
 * @param string url
 */
function convertUrl($url) {
	$config = &loadLibClass('config');
	$utftoasci = &loadLibClass('utftoasci');

	// replace all spec symbols to spaces
	$url = str_replace($config->get('deniedUrlChars'), ' ', stripslashes(mb_strtolower(trim($url), 'UTF-8')));

	// transliteration
	$url = $utftoasci->convert($url);

	// replace all spec symbols to spaces (remove translit spec chars)
	$url = str_replace($config->get('deniedUrlChars'), ' ', stripslashes(mb_strtolower(trim($url), 'UTF-8')));

	// remove multiple spaces
	$url = preg_replace('/\s\s+/', ' ', $url);

	// remove  '/'
	$url = str_replace('/', '', $url);

	// trim spaces from beggining and end
	$url = trim($url, ' ');

	// replace spaces with '-'
	$url = str_replace(' ', '-', $url);

	return $url;
}

/**
 * Delete file by given url
 *
 * @param string 	full file url
 */
function deleteFileFromFolder($filrUrl){
	if($filrUrl != '' && file_exists($filrUrl)){
		@chmod($filrUrl, 0777);
		return @unlink($filrUrl);
	}
	return false;
}

function pre($var)
{
	echo '<pre>';
	var_dump($var);
	echo '</pre>';
}

/**
 * @param $datetime
 * @return false|string
 */
function dateFromDatetime($datetime)
{
    if(!$datetime) {
        return false;
    }

    return date('Y-m-d', strtotime($datetime));
}

?>