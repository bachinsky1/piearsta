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
 * Adweb simple loggin class
 *
 * Supports DEBUG, INFO, WARN levels, NONE does not log at all
 * writes all data to /{adminFolder}/log/logger.txt file,
 * which might be changed. 
 * Logger creates logfile if needed and checks is it writable.  
 */

/**
* define DEBUG, INFO, WARN LEVELS 
*/
define('C_LOG_DEBUG', 'DEBUG');
define('C_LOG_INFO', 'INFO');
define('C_LOG_WARN', 'WARN');
define('C_LOG_NONE', 'NONE');

// defing file size
define('FILE_SIZE', 1048576); // 1 Mb
	 
/**
* Log class
*/ 
class Log {

	/**
	* File to write log information (String).
	*/
	public $logFile; 
	
	/**
	* Can write to log file, is file writable?
	*/
	public $canWrite = false;
	
	/**
	* Current debug level 
	* @access private
	*/
	private $logLevel;
		
	/**
	* Array holding number to string representation of log levels
	*/
	public $allLevels = Array();
	
    /**
     * Integer holding the file handle.
     */
    public $fp;
    	
	/**
	* Constructor for initialization routine 
	* 
	* Constructor builds int values for each string level constant.<br>
	* Checks and opens log file for writing.
	* @param String $logFileName file with full path where to write log information
	* @param String $level one of log level constants, default NONE
	*/ 
	function __construct($level = C_LOG_DEBUG, $logFileName = "") {

		$this->allLogLevels[C_LOG_DEBUG] = 1;
		$this->allLogLevels[C_LOG_INFO] = 2;
		$this->allLogLevels[C_LOG_WARN] = 3;
		$this->allLogLevels[C_LOG_NONE] = 4;

		//ensure default log level to be set
		$this->setLogLevel($level ? $level : C_LOG_DEBUG);
	   
		//open/create log file
		if ($logFileName) {
			$this->logFile = $logFileName;
		}
		else {
			$this->logFile = AD_CMS_FOLDER . "log/logger.txt";
		}
	}
	
	/**
     * This method enforces the singleton pattern for this class.
     *
	 * @param String $logFileName file with full path where to write log information
	 * @param String $level one of log level constants, default NONE
     * @return  object  Reference to the global Log object.
     * @access  public
     * @static
     */	
	public static function &getInstance($level = C_LOG_DEBUG, $logFileName = "") {
        static $instance = null;

        if ($instance === null) {
            $instance = new Log($level, $logFileName);
        }
        return $instance;
    }
	
	
	/**
	* Set logging level form cfg
	* 
	* Level order is DEBUG (all), INFO (except DEBUG), WARN, NONE - nothing at all
	*   
	* @param String $level one of level constants LOG_DEBUG, LOG_INFO, LOG_WARN, LOG_NONE
	*/
	function setLogLevel($level) {
		$this->logLevel = $this->allLogLevels[$level];
	}	
	
	
	/**
	* Check is debug level enabaled in configuration
	*
	* May be used to speed up things <code> if (isDebug()) log.debug('bla bla');</code>
	*/ 
	function isDebug(){
		return $this->_isLevelEnabled(C_LOG_DEBUG);
	}
	
	/**
	* Info level
	*/ 
	function isInfo(){
		return $this->_isLevelEnabled(C_LOG_INFO);
	}

	/**
	* WARN level
	*/
	function isWarn(){
		return $this->_isLevelEnabled(C_LOG_WARN);
	}
	
	/**
	* Debug out string
	*/
	function debug($str, $file = "", $line = "") {

		if ($this->isDebug()) {
			$this->_write($str, $file, $line, C_LOG_DEBUG);
		}	
	}	

	/**
	* Info out string
	*/
	function info($str, $file = "", $line = "") {
		if ($this->isInfo()) {
			$this->_write($str, $file, $line, C_LOG_INFO);
		}	
	}	

	/**
	* Warn string
	*/
	function warn($str, $file = "", $line = "") {
		if ($this->isWarn()) {
			$this->_write($str, $file, $line, C_LOG_WARN);
		}
	}

	/**
	* Close file and end log
	*/
	function close(){
		if ($this->fp) {
			fclose($this->fp);
		}
		
		$this->canWrite = false;
	}
	
	/**
	* Check is log level enabaled against current configuration
	* 
	* @param String $level one of level constants LOG_DEBUG, LOG_INFO, LOG_WARN, LOG_NONE
	* @return boolean
	* @access private   
	*/
	function _isLevelEnabled($level){
		if ($this->logLevel <= $this->allLogLevels[$level]) {
			return true;
		}
		else {
			return false;
		}
	}


	/**
	* Open log file to append info
	* 
	* @return boolean true on success
	* @access private
	*/
	function _openlogfile() {

		//second time?	
        if ($this->fp && $this->canWrite) {
            return true;
        }
        
		if (!isWritable($this->logFile)) {
			$this->canWrite = false;
        	return false;
		}

		//open file append mode, if does not exists create it
        if (($this->fp = fopen($this->logFile, "a")) == false) {
        	$this->canWrite = false;
        	return false;
        } 
        else {
        	
        	$this->canWrite = true;
        	if (filesize($this->logFile) > FILE_SIZE) {
        		ftruncate($this->fp, 0);
				fseek($this->fp, 0, SEEK_SET); 
        	}
        }	

        return true;
	}	
	
	/**
	* write string to log file
	*
	* @param String $str log string 
	* @param String $file file submited a log, log must submit it with __FILE__
	* @param String $line line in calling file
	* @param String $level log level 
	* @access private
	*/	
	function _write($str, $file, $line, $level) {

		$this->_openlogfile();

		if ($this->canWrite){
			$time = date(PIEARSTA_DT_FORMAT);
			$stackTrace = "";
			if (!$file && !$line && ($level != C_LOG_DEBUG)) $stackTrace = $this->_getStackTrace();
			$outStr = "$time $level $str $file $line $stackTrace\n"; 
			fwrite($this->fp, $outStr);	
		}
		else {
			//showError("Log file have not writable.");
		}
		$this->close();
	}
	
	/*
	 * Get stack trace
	 */
	function _getStackTrace() {
		
		$result = "call trace:\n";
		$trace = debug_backtrace();
		for ($i = 2; $i < sizeof($trace); $i++) {
			$result .= "at " . $trace[$i]['file'] . ":".$trace[$i]['line'] . "\n";
		}	
		return $result;
	}
	
	/*
	 * Get stack trace
	 */
	function _clearLogFile() {
		
		ftruncate($this->fp, 0);
		fseek($this->fp, 0, SEEK_SET);
	}

}	

?>
