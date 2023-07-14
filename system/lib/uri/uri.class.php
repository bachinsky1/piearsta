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
 * URI Class
 *
 * Parses URIs
 */
class Uri {

	var $uri_string;
	var $segments = array();
	var $cfg;
	var $orig_uri;

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function Uri() {

		$this->cfg = &loadLibClass('config');
		$this->db  = &loadLibClass('db');

		// Language values
		$this->cfg->set('defaultLang', getDefaultLang());

		$this->parseRequestUri();
		$this->explodeSegments();

	}

	/**
	 * Parse the REQUEST_URI
	 *
	 * Due to the way REQUEST_URI works it usually contains path info
	 * that makes it unusable as URI data.  We'll trim off the unnecessary
	 * data, hopefully arriving at a valid URI that we can use.
	 *
	 * @access	private
	 * @return	string
	 */
	public function parseRequestUri() {

		if (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] == '') {
			return '';
		}

		if (strpos($_SERVER['REQUEST_URI'], "index.php") !== false) {
            header("Location: " . str_replace("index.php", "", $_SERVER['REQUEST_URI']));
			exit;
		}

		if ($_SERVER['REMOTE_ADDR'] == '178.16.24.46') {
			$_SERVER['REQUEST_URI'] = '/api/';
		}

		$request_uri = preg_replace("|/(.*)|", "\\1", str_replace("\\", "/", $_SERVER['REQUEST_URI']));

		if (strpos($request_uri, '?') !== FALSE) {
			$request_uri = substr($request_uri, 0, strpos($request_uri, '?'));
		}

		if ($request_uri == '') {
			return $this->uri_string = $this->checkIfLangInTheEnd($request_uri);
		}

		$this->orig_uri = $request_uri;

		$paramIDs = array();
		$clinic = false;
		$doctor = false;
		if ($this->cfg->get("parseUrl") && !defined('AD_CMS')) {
			$uri = $uriSeg = explode("/", $request_uri);

			foreach ($uriSeg AS $k => $v) {
				$tmp = explode(":", $v);

				if ((count($tmp) > 1) && in_array($tmp[0], $this->cfg->get("parseUrl"))) {

					$_GET[clearText($tmp[0])] = clearText($tmp[1]);
					unset($uri[$k]);
				} else {
					if ($v) {
						if (!$doctor) {
							$dbQuery = "SELECT `id`
									FROM `" . $this->cfg->getDbTable('doctors', 'self') . "`
									WHERE `url` = '" . mres($v) . "'";
							$query = new query($this->db, $dbQuery);
							if ($query->num_rows() > 0) {
								$doctor = $v;
								$_GET['paramID'] = $query->getOne();
								unset($uri[$k]);
								continue;

							}
						}

						if (!$clinic) {
							$dbQuery = "SELECT `id`
									FROM `" . $this->cfg->getDbTable('clinics', 'self') . "`
									WHERE `url` = '" . mres($v) . "'";
							$query = new query($this->db, $dbQuery);
							if ($query->num_rows() > 0) {
								$clinic = true;
								if ($doctor) {

									$_GET['paramID1'] = $query->getOne();

									$dbQuery = "SELECT d.`id`
												FROM `" . $this->cfg->getDbTable('doctors', 'self') . "` d
													LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'clinics') . "` dtc ON (dtc.d_id = d.id)
												WHERE 1
													AND d.`url` = '" . mres($doctor) . "'
													AND dtc.c_id = '" . mres($_GET['paramID1']) . "'";
									$query = new query($this->db, $dbQuery);
									if ($query->num_rows() > 0) {
										$_GET['paramID'] = $query->getOne();
									}

								} else {
									$_GET['paramID'] = $query->getOne();
								}
								unset($uri[$k]);
								continue;
							}
						}
					}

				}
			}

			$request_uri = implode("/", $uri);
		}

		if ($this->cfg->get("removeHtmlExt")) {
			$uri = explode("/", $request_uri);

			if (preg_match("/.html/i", $uri[count($uri) - 1])) {

				$itemUri = explode(".", $uri[count($uri) - 1]);
				$_GET['docUrl'] = clearText($itemUri[0]);
				unset($uri[count($uri) - 1]);

				$request_uri = implode("/", $uri) . '/';
			}
		}

//		if (strlen($request_uri) > 0 && $request_uri[strlen($request_uri) - 1] != "/" && !defined('AD_CMS')) {
//            redirect('/' . $request_uri . '/');
//        }

		$request_uri = $this->checkIfLangInTheEnd($request_uri);

		return $this->uri_string = $request_uri;
	}

	/**
	 * Check if enabled lang in the end
	 * parse uri string for that exception
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
    private function checkIfLangInTheEnd($request_uri)
    {

        if ($this->cfg->get("langInTheEnd") && !defined('AD_CMS')) {

            if ($request_uri == '') {

                $domains = $this->cfg->get('domainsLang');

                if (isset($_SERVER["HTTP_HOST"]) && isset($domains[$_SERVER["HTTP_HOST"]]) && empty($_SERVER['QUERY_STRING'])) {
                    redirect("/" . $domains[$_SERVER["HTTP_HOST"]]);
                } else {
                    return getDefaultPageUrl();
                }

            }

            if ($request_uri[strlen($request_uri) - 1] == '/') {
                $request_uri = substr($request_uri, 0, -1);
            }

            $uri = explode("/", $request_uri);

            if (strlen(end($uri)) == 2 && checkLangEnabled(end($uri))) {

                if (count($uri) == 1) {

                    return implode("/", $uri) . '/';

                } else {
                    // If 2 languages are set, like piearsta.eu/ru/lv -> lv will be removed

                    if (end($uri) == $this->cfg->get("defaultLang")) {
                        unset($uri[count($uri) - 1]);
                        redirect(implode("/", $uri) . '/');
                    } else {
                        $lang = end($uri);
                        unset($uri[count($uri) - 1]);

                        if ($lang === implode($uri)) {
                            $uri = '';
                        } else {
                            $uri = implode("/", $uri) . '/';
                        }
                        $request_uri = $lang . '/' . $uri;
                    }
                }
            } else {

                if (checkLangEnabled($uri[0])) {
                    $lang = $uri[0];
                    unset($uri[0]);

                } else {
                    $lang = getDefaultLang();
                }

                $request_uri = $lang . '/' . implode("/", $uri) . '/';
            }
        } else {
            return $request_uri;
        }
        return $request_uri;
    }

	/**
	 * Filter segments for malicious characters
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	public function filterUri ($str) {
		$str = urlencode(urldecode($str));
		if ($str != '' && $this->cfg->get('permitted_uri_chars') != '') {
			// preg_quote() in PHP 5.3 escapes -, so the str_replace() and addition of - to preg_quote() is to maintain backwards
			// compatibility as many are unaware of how characters in the permitted_uri_chars will be parsed as a regex pattern
			/*if (!preg_match("|^[".str_replace(array('\\-', '\-'), '-', preg_quote($this->cfg->get('permitted_uri_chars'), '-'))."]+$|i", $str)) {
				redirect(getLink(AD_MAINPAGE_MIRROR_ID));
			}*/
			if (!preg_match("|^[" . str_replace(array('\\-', '\-'), '-', preg_quote($this->cfg->get('permitted_uri_chars'), '-')) . "]+$|i", $str)) {
				openDefaultPage();
				//showError('The URI you submitted has disallowed characters.', 400);
				//redirect(getLink(AD_MAINPAGE_MIRROR_ID));
 			}
		}

		// Convert programatic characters to entities
		$bad	= array('$', 		'(', 		')',	 	'%28', 		'%29');
		$good	= array('&#36;',	'&#40;',	'&#41;',	'&#40;',	'&#41;');

		return str_replace($bad, $good, $str);
	}


	/**
	 * Remove Html extention from Url
	 */
	private function removeHtmlExt() {

	}

	/**
	 * Explode the URI Segments. The individual segments will
	 * be stored in the $this->segments array.
	 *
	 * @access	private
	 * @return	void
	 */
	public function explodeSegments() {
		foreach(explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $this->uri_string)) as $val) {
			// Filter segments for security
			$val = trim($this->filterUri($val));

			if ($val != '') {
				$this->segments[] = $val;
			}
		}
	}

	/**
	 * Re-index Segments
	 *
	 * This function re-indexes the $this->segment array so that it
	 * starts at 1 rather than 0.  Doing so makes it simpler to
	 * use functions like $this->uri->segment(n) since there is
	 * a 1:1 relationship between the segment array and the actual segments.
	 *
	 * @access	private
	 * @return	void
	 */
	public function reindexSegments() {
		array_unshift($this->segments, NULL);
		unset($this->segments[0]);
	}

	/**
	 * Fetch a URI Segment
	 *
	 * This function returns the URI segment based on the number provided.
	 *
	 * @access	public
	 * @param	integer
	 * @param	bool
	 * @return	string
	 */
	public function segment($n, $no_result = false) {
		return (!isset($this->segments[$n]) ? $no_result : $this->segments[$n]);
	}

	/**
	 * Generate a key value pair from the URI string
	 *
	 * This function generates and associative array of URI data starting
	 * at the supplied segment. For example, if this is your URI:
	 *
	 *	example.com/user/search/name/joe/location/UK/gender/male
	 *
	 * You can use this function to generate an array with this prototype:
	 *
	 * array (
	 *			name => joe
	 *			location => UK
	 *			gender => male
	 *		 )
	 *
	 * @access	public
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @return	array
	 */
	public function uriToAssoc($n = 3, $default = array()) {
	 	return $this->_uriToAssoc($n, $default);
	}

	/**
	 * Generate a key value pair from the URI string
	 *
	 * @access	private
	 * @param	integer	the starting segment number
	 * @param	array	an array of default values
	 * @return	array
	 */
	public function _uriToAssoc($n = 3, $default = array()) {

		if (!is_numeric($n)) {
			return $default;
		}

		if (isset($this->keyval[$n])) {
			return $this->keyval[$n];
		}

		if ($this->totalSegments() < $n) {
			if (count($default) == 0) {
				return array();
			}

			$retval = array();
			foreach ($default as $val) {
				$retval[$val] = false;
			}

			return $retval;
		}

		$segments = array_slice($this->segmentArray(), ($n - 1));

		$i = 0;
		$lastval = '';
		$retval  = array();
		foreach ($segments as $seg) {
			if ($i % 2) {
				$retval[$lastval] = $seg;
			}
			else {
				$retval[$seg] = false;
				$lastval = $seg;
			}

			$i++;
		}

		if (count($default) > 0) {
			foreach ($default as $val) {
				if (!array_key_exists($val, $retval)) {
					$retval[$val] = false;
				}
			}
		}

		// Cache the array for reuse
		$this->keyval[$n] = $retval;
		return $retval;
	}

	/**
	 * Generate a URI string from an associative array
	 *
	 *
	 * @access	public
	 * @param	array	an associative array of key/values
	 * @return	array
	 */
	public function assocToUri($array) {
		$temp = array();
		foreach ((array)$array as $key => $val) {
			$temp[] = $key;
			$temp[] = $val;
		}

		return implode('/', $temp);
	}

	/**
	 * Fetch a URI Segment and add a trailing slash
	 *
	 * @access	public
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	public function slashSegment($n, $where = 'trailing') {
		return $this->_slashSegment($n, $where);
	}

	/**
	 * Fetch a URI Segment and add a trailing slash - helper function
	 *
	 * @access	private
	 * @param	integer
	 * @param	string
	 * @return	string
	 */
	public function _slashSegment($n, $where = 'trailing') {
		if ($where == 'trailing') {
			$trailing	= '/';
			$leading	= '';
		}
		elseif ($where == 'leading') {
			$leading	= '/';
			$trailing	= '';
		}
		else {
			$leading	= '/';
			$trailing	= '/';
		}
		return $leading . $this->segment($n) . $trailing;
	}

	/**
	 * Segment Array
	 *
	 * @access	public
	 * @return	array
	 */
	public function segmentArray() {
		return $this->segments;
	}

	/**
	 * Total number of segments
	 *
	 * @access	public
	 * @return	integer
	 */
	public function totalSegments() {
		return count($this->segments);
	}

	/**
	 * Fetch the entire URI string
	 *
	 * @access	public
	 * @return	string
	 */
	public function uriString() {
		return $this->uri_string;
	}

}