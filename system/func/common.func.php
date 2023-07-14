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
 * Adweb common functions file
 * 15.02.2010
 */

/**
 * Determines if the current version of PHP is greater then the supplied value
 *
 * @access	public
 * @param	string
 * @return	bool
 */
function is_php($version = '5.0.0') {
	static $_is_php;
	$version = (string)$version;
	
	if (!isset($_is_php[$version])) {
		$_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? false : true;
	}

	return $_is_php[$version];
}

/**
 * Tests for file writability
 *
 * is_writable() returns TRUE on Windows servers when you really can't write to
 * the file, based on the read-only attribute.  is_writable() is also unreliable
 * on Unix servers if safe_mode is on.
 *
 * @param $file
 * @return bool
 */
function isWritable($file) {	
	
	// If we're on a Unix server with safe_mode off we call is_writable
	if (DIRECTORY_SEPARATOR == '/' AND @ini_get("safe_mode") == false) {
		return is_writable($file);
	}

	// For windows servers and safe_mode "on" installations we'll actually
	// write a file then read it.
	if (is_dir($file)) {
		$file = rtrim($file, '/') . '/' . md5(rand(1,100));

		if (($fp = @fopen($file, 'ab')) === false) {
			return false;
		}

		fclose($fp);
		@chmod($file, 0777);
		@unlink($file);
		
		return true;
		
	} elseif (($fp = @fopen($file, 'ab')) === false) {
		return false;
	}

	fclose($fp);
	return true;
}

/**
 * Class registry
 *
 * This function acts as a singleton.  If the requested class does not
 * exist it is instantiated and set to a static variable.  If it has
 * previously been instantiated the variable is returned.
 *
 * @access	public
 * @param	string	the class name being requested
 * @param	bool	optional flag that lets classes get loaded but not instantiated
 * @return	object
 */
function &loadLibClass($class, $instantiate = true, $settings = '', $folder = false) {
	
	static $objects = array();

	// Check for the class exist
	if (isset($objects[$class])) {
		return $objects[$class];
	}

	// If the requested class does not exist in the lib folder, function
	// return false.	
	if (file_exists(AD_LIB_FOLDER . ($folder ? $folder : $class) . '/' . $class . '.class.php')) {
		require_once(AD_LIB_FOLDER . ($folder ? $folder : $class) . '/' . $class . '.class.php');
		
		if ($instantiate == false) {
			
			$objects[$class] = true;
			return $objects[$class];
		}
		
		$name = ucfirst(str_replace(".", "_", $class));
		$objects[$class] = &instantiateClass(new $name($settings));
		return $objects[$class];
	}
	else {
		$objects[$class] = &instantiateClass(new Base());
		logDebug("Have not found the library file: " . $class);
		return $objects[$class];
	}
}

/**
 * Class registry
 *
 * This function acts as a singleton.  If the requested class does not
 * exist it is instantiated and set to a static variable.  If it has
 * previously been instantiated the variable is returned.
 *
 * @access	public
 * @param	string	the class name being requested
 * @param	bool	optional flag that lets classes get loaded but not instantiated
 * @return	object
 */
function &loadLibSubClass($class, $instantiate = true, $settings = '') {

	static $objects = array();

	// Check for the class exist
	if (isset($objects[$class])) {
		return $objects[$class];
	}

	$data = explode('.', $class);
	if (count($data) != 2) {
		return false;
	}
	$parent = $data[0];
	$child = $data[1];

	loadLibClass($parent, false);

	// If the requested class does not exist in the lib folder, function
	// return false.
	if (file_exists(AD_LIB_FOLDER . $parent . '/' . $child . '/' . $parent . '.' . $child . '.class.php')) {
		require_once(AD_LIB_FOLDER . $parent . '/' . $child . '/' . $parent . '.' . $child . '.class.php');

		if ($instantiate == false) {
				
			$objects[$class] = true;
			return $objects[$class];
		}

		$name = ucfirst(str_replace(".", "_", $class));
		$objects[$class] = &instantiateClass(new $name($settings));
		return $objects[$class];
	}
	else {
		$objects[$class] = &instantiateClass(new Base());
		logDebug("Have not found the library file: " . $class);
		return $objects[$class];
	}
}

/**
 * Modules registry
 *
 * This function acts as a singleton.  If the requested class does not
 * exist it is instantiated and set to a static variable.  If it has
 * previously been instantiated the variable is returned.
 * 
 * @access	public
 * @param	string	the class name being requested
 * @param	bool	optional flag that lets classes get loaded but not instantiated
 * @return	object
 */
function &loadAppClass($class, $app, $instantiate = true, $settings = '') {
	
	static $objects = array();

	// Check for the class exist
	if (isset($objects[$class])) {
		return $objects[$class];
	}

	// If the requested class does not exist in the lib folder, function
	// return false.	
	if (file_exists(AD_APP_FOLDER . $app . '/' . $class . '/' . $class . '.php')) {
		require_once(AD_APP_FOLDER . $app . '/' . $class . '/' . $class . '.php');

		if ($instantiate == false) {
			
			$objects[$class] = true;
			return $objects[$class];
		}
		
		$name = ucfirst(str_replace(".", "_", $class));
		$objects[$class] = &instantiateClass(new $name($settings));
		return $objects[$class];
	}
	else {

		logDebug("Have not found the App file: " . $class);
		showError("Have not found the App file: " . $class);
	}
}

/**
 * Functions file loader
 *
 *
 * @access	public
 * @param	string	the file path name
 * @return	void
 */
function loadFunc($func) {

	// If the requested file does not exist in the lib folder,  	
	// function return false.	
	if (file_exists(AD_FUNC_FOLDER . $func . '.func.php')) {
		require_once(AD_FUNC_FOLDER . $func . '.func.php');
	}
	else {
		logDebug("Have not found the function file: " . $func);
		return false;
	}
}

/**
 * Instantiate Class
 *
 * Returns a new class object by reference.
 * Required to retain PHP 4 compatibility and also not make PHP 5.3 cry.
 *
 * Use: $obj = &instantiateClass(new Foo());
 * 
 * @access	public
 * @param	object
 * @return	object
 */
function &instantiateClass(&$class_object) {
	return $class_object;
}

/**
 * Error Handler
 *
 * This function lets us invoke the exception class and
 * display errors
 * This function will send the error page directly to the
 * browser and exit.
 *
 * @access	public
 * @param	string	the message
 * @param	int 	the status code
 * @return	void
 */
function showError($message, $statusCode = 500) {

	/** @var Error $error */
	$error = &loadLibClass('error');
	echo $error->showError('An ADWeb error was encountered', $message, 'general', $statusCode);
	exit;
}

/**
 * 404 Page Handler
 *
 * This function is similar to the showError() function above
 * However, instead of the standard error template it displays
 * 404 errors.
 *
 * @access	public
 * @param	string	page url
 * @return	void
 */
function show_404($page = '') {
	$error = &loadLibClass('error');
	$error->show_404($page);
	exit;
}

/**
 * Error Logging Interface
 *
 * We use this as a simple mechanism to access the logging
 * class and send messages to be logged.
 *
 * @access	public
 * @param	string 	error message
 * @return	void
 */
function logInfo($message, $file = '', $line = '') {
	static $log;
	
	$log = &loadLibClass('log');
	$log->info($message, $file, $line);
}

/**
 * Error Logging Interface
 *
 * We use this as a simple mechanism to access the logging
 * class and send messages to be logged.
 *
 * @access	public
 * @param	string 	error message
 * @return	void
 */
function logWarn($message, $file = '', $line = '') {
	static $log;
	/** @var log $log */
	$log = &loadLibClass('log');
	$log->warn($message, $file, $line);
}

/**
 * Error Logging Interface
 *
 * We use this as a simple mechanism to access the logging
 * class and send messages to be logged.
 *
 * @access	public
 * @param	string 	error message
 * @return	void
 */
function logDebug($message, $file = '', $line = '') {
	static $log;
	/** @var Log $log */
	$log = &loadLibClass('log');
	$log->debug($message, $file, $line);
}

/**
 * Set HTTP Status Header
 *
 * @access	public
 * @param	int 	the status code
 * @param	string	
 * @return	void
 */
function setStatusHeader($code = 200, $text = '') {
	
	$stats = array(
						200	=> 'OK',
						201	=> 'Created',
						202	=> 'Accepted',
						203	=> 'Non-Authoritative Information',
						204	=> 'No Content',
						205	=> 'Reset Content',
						206	=> 'Partial Content',

						300	=> 'Multiple Choices',
						301	=> 'Moved Permanently',
						302	=> 'Found',
						304	=> 'Not Modified',
						305	=> 'Use Proxy',
						307	=> 'Temporary Redirect',

						400	=> 'Bad Request',
						401	=> 'Unauthorized',
						403	=> 'Forbidden',
						404	=> 'Not Found',
						405	=> 'Method Not Allowed',
						406	=> 'Not Acceptable',
						407	=> 'Proxy Authentication Required',
						408	=> 'Request Timeout',
						409	=> 'Conflict',
						410	=> 'Gone',
						411	=> 'Length Required',
						412	=> 'Precondition Failed',
						413	=> 'Request Entity Too Large',
						414	=> 'Request-URI Too Long',
						415	=> 'Unsupported Media Type',
						416	=> 'Requested Range Not Satisfiable',
						417	=> 'Expectation Failed',

						500	=> 'Internal Server Error',
						501	=> 'Not Implemented',
						502	=> 'Bad Gateway',
						503	=> 'Service Unavailable',
						504	=> 'Gateway Timeout',
						505	=> 'HTTP Version Not Supported'
					);

	if ($code == '' OR !is_numeric($code)) {
		showError('Status codes must be numeric', 500);
	}

	if (isset($stats[$code]) AND $text == '') {				
		$text = $stats[$code];
	}
	
	if ($text == '') {
		showError('No status text available. Please check your status code number or supply your own message text.', 500);
	}
	
	$server_protocol = (isset($_SERVER['SERVER_PROTOCOL'])) ? $_SERVER['SERVER_PROTOCOL'] : FALSE;

	if (substr(php_sapi_name(), 0, 3) == 'cgi') {
		header("Status: {$code} {$text}", true);
	}
	elseif ($server_protocol == 'HTTP/1.1' OR $server_protocol == 'HTTP/1.0') {
		header($server_protocol . " {$code} {$text}", true, $code);
	}
	else {
		header("HTTP/1.1 {$code} {$text}", true, $code);
	}
}

/**
 * Analog: print_r function with <pre>
 * 
 * @access	public
 * @param	array	show array
 * @param	bool	return array or not: default false
 * @param 	bool	die after or not
 * @param 	array	ip's
 * 
 * @return	mix
 */
function pR($value, $return = false, $die = false, $ip = '') {
	
	if (is_array($ip) && !in_array($_SERVER['REMOTE_ADDR'], $ip)) {
		
		return false;
		
	} else {
		
		$result = '<pre style="font-family:courier new; font-size:11px; line-height:1em; border:1px solid #bbbbbb;">' . print_r($value, true) . '</pre>';
		
		if ($return) {
			return $result;
		} else {
			echo $result;
			
			if ($die) {
				die();
			} else {
				return true;
			}	
		}
	}
	
}

/**
 * Check that the input value has a valid International Bank Account Number IBAN syntax
 * Requirements are uppercase, no whitespaces, max length 34, country code and checksum exist at right spots,
 * body matches against checksum via Mod97-10 algorithm
 *
 * @param string $check The value to check
 *
 * @return bool Success
 */
function isIBAN($check)
{
    if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', $check)) {
        return false;
    }

    $country = substr($check, 0, 2);
    $checkInt = intval(substr($check, 2, 2));
    $account = substr($check, 4);
    $search = range('A', 'Z');
    $replace = [];
    foreach (range(10, 35) as $tmp) {
        $replace[] = strval($tmp);
    }
    $numStr = str_replace($search, $replace, $account . $country . '00');
    $checksum = intval(substr($numStr, 0, 1));
    $numStrLength = strlen($numStr);
    for ($pos = 1; $pos < $numStrLength; $pos++) {
        $checksum *= 10;
        $checksum += intval(substr($numStr, $pos, 1));
        $checksum %= 97;
    }

    return ((98 - $checksum) === $checkInt);
}

?>
