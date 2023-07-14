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
 * Adweb error file
 * Show error msg, load, log, etc...
 * 15.02.2010
 */

class Error {
	
	public $action;
	public $severity;
	public $message;
	public $filename;
	public $line;
	public $obLevel;
    /** @var Config $cfg */
    public $cfg;

	public $levels = array(
						E_ERROR				=>	'Error',
						E_WARNING			=>	'Warning',
						E_PARSE				=>	'Parsing Error',
						E_NOTICE			=>	'Notice',
						E_CORE_ERROR		=>	'Core Error',
						E_CORE_WARNING		=>	'Core Warning',
						E_COMPILE_ERROR		=>	'Compile Error',
						E_COMPILE_WARNING	=>	'Compile Warning',
						E_USER_ERROR		=>	'User Error',
						E_USER_WARNING		=>	'User Warning',
						E_USER_NOTICE		=>	'User Notice',
						E_STRICT			=>	'Runtime Notice'
					);


	/**
	 * Constructor
	 */	
	function __construct() {
		$this->ob_level = ob_get_level();
		$this->cfg = &loadLibClass('config');
	}

	/**
	 * Exception Logger
	 *
	 * This function logs PHP generated error messages
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function logError($severity, $message, $filepath, $line) {	
		$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
		logMessage('error', 'Severity: ' . $severity . '  --> ' . $message . ' ' . $filepath . ' ' . $line, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function show_404($page = '') {	
		
		$heading = "404 Page Not Found";
		$message = "The page you requested was not found.";

		logMessage('error', '404 Page Not Found --> ' . $page);
		if (in_array(getIp(), $this->cfg->get('debugIp'))) {
			echo $this->showError($heading, $message, '404', 404);
		}
		
		exit;
	}
  	
	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	private
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @param	string	the status code
	 * @return	string
	 */
	function showError($heading, $message, $template = 'general', $statusCode = 500) {
		
		if (in_array(getIp(), $this->cfg->get('debugIp'))) {

			setStatusHeader($statusCode);
			
			$message = '<p>' . implode('</p><p>', (!is_array($message)) ? array($message) : $message) . '</p>';
	
			if (ob_get_level() > $this->obLevel + 1) {
				ob_end_flush();	
			}
	
			ob_start();

			if (file_exists(AD_LIB_FOLDER . strtolower(get_class($this)) . '/tpl/' . $template . '.php')) {

				require(AD_LIB_FOLDER . strtolower(get_class($this)) . '/tpl/' . $template . '.php');
				$buffer = ob_get_contents();
				ob_end_clean();

				return $buffer;

			} else {

				die("Error template have not found.");
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function showPhpError($severity, $message, $filepath, $line) {	
		
		if (in_array(getIp(), $this->cfg->get('debugIp'))) {
			$severity = (!isset($this->levels[$severity])) ? $severity : $this->levels[$severity];
		
			$filepath = str_replace("\\", "/", $filepath);
			
			// For safety reasons we do not show the full file path
			if (FALSE !== strpos($filepath, '/')) {
				$x = explode('/', $filepath);
				$filepath = $x[count($x) - 2] . '/' . end($x);
			}
			
			if (ob_get_level() > $this->ob_level + 1) {
				ob_end_flush();	
			}
			
			ob_start();
			if (file_exists(AD_LIB_FOLDER . strtolower(get_class($this)) . '/' . strtolower(get_class($this)) . 'tpl/php.php')) {
				require(AD_LIB_FOLDER . strtolower(get_class($this)) . '/' . strtolower(get_class($this)) . 'tpl/php.php');
				$buffer = ob_get_contents();
				ob_end_clean();
				return $buffer;
			}
			else {
				die("Error template have not found.");
			}
		}	
	}

}