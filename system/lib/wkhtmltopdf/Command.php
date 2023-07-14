<?php

/**
 * Command
 *
 * This class is an extension of mikehaertl\shellcommand\Command and adds
 * wk* specific features like xvfb support and proper argument handling.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 2.0.2-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class Command extends BaseCommand
{
    /**
     * @var bool whether to enable the built in Xvfb support (uses xvfb-run)
     */
    public $enableXvfb = false;

    /**
     * @var string the name of the xvfb-run comand. Default is `xvfb-run`.
     * You can also configure a full path here.
     */
    public $xvfbRunBinary = 'xvfb-run';

    /**
     * @var string options to pass to the xfvb-run command. Default is `--server-args="-screen 0, 1024x768x24"`.
     */
    public $xvfbRunOptions = '--server-args="-screen 0, 1024x768x24"';

    /**
     * @param array $args args to add to the command. These can be:
     *     array(
     *       // Special argument 'input' will not get prepended with '--'.
     *       'input' => 'cover',
     *
     *       // Special argument 'inputArg' is treated like 'input' but will get escaped
     *       // Both 'input' and 'inputArg' can be used in combination
     *       'inputArg' => '/tmp/tmpFileName.html',
     *
     *       'no-outline',           // option without argument
     *       'encoding' => 'UTF-8',  // option with argument
     *
     *       // Option with 2 arguments
     *       'cookie' => array('name'=>'value'),
     *
     *       // Repeatable options with single argument
     *       'run-script' => array(
     *           'local1.js',
     *           'local2.js',
     *       ),
     *
     *       // Repeatable options with 2 arguments
     *       'replace' => array(
     *           '{page}' => $page++,
     *           '{title}' => $pageTitle,
     *       ),
     */
    public function addArgs($args)
    {
        if (isset($args['input'])) {
            // Typecasts TmpFile to filename
            $this->addArg((string) $args['input']);
            unset($args['input']);
        }
        if (isset($args['inputArg'])) {
            // Typecasts TmpFile to filename and escapes argument
            $this->addArg((string) $args['inputArg'], null, true);
            unset($args['inputArg']);
        }
        foreach($args as $key=>$val) {
            if (is_numeric($key)) {
                $this->addArg("--$val");
            } elseif (is_array($val)) {
                foreach($val as $vkey => $vval) {
                    if (is_int($vkey)) {
                        $this->addArg("--$key", $vval);
                    } else {
                        $this->addArg("--$key", array($vkey, $vval));
                    }
                }
            } else {
                $this->addArg("--$key", $val);
            }
        }
    }

    /**
     * @return string|bool the command to execute with optional Xfvb wrapper applied. Null if none set.
     */
    public function getExecCommand()
    {
        $command = parent::getExecCommand();
        if ($this->enableXvfb) {
            return $this->xvfbRunBinary.' '.$this->xvfbRunOptions.' '.$command;
        }
        return $command;
    }
}

/**
 * Command
 *
 * This class represents a shell command.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.0.5-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class BaseCommand
{
	/**
	 * @var bool whether to escape any argument passed through addArg(). Default is true.
	 */
	public $escapeArgs = true;
	/**
	 * @var bool whether to escape the command passed to setCommand() or the constructor.
	 * This is only useful if $escapeArgs is false. Default is false.
	 */
	public $escapeCommand = false;
	/**
	 * @var bool whether to use `exec()` instead of `proc_open()`. This can be used on Windows system
	 * to workaround some quirks there. Note, that any errors from your command will be output directly
	 * to the PHP output stream. `getStdErr()` will also not work anymore and thus you also won't get
	 * the error output from `getError()` in this case. You also can't pass any environment
	 * variables to the command if this is enabled. Default is false.
	 */
	public $useExec = false;
	/**
	 * @var bool whether to capture stderr (2>&1) when `useExec` is true. This will try to redirect the
	 * stderr to stdout and provide the complete output of both in `getStdErr()` and `getError()`.
	 * Default is `true`.
	 */
	public $captureStdErr = true;
	/**
	 * @var string|null the initial working dir for proc_open(). Default is null for current PHP working dir.
	 */
	public $procCwd;
	/**
	 * @var array|null an array with environment variables to pass to proc_open(). Default is null for none.
	 */
	public $procEnv;
	/**
	 * @var array|null an array of other_options for proc_open(). Default is null for none.
	 */
	public $procOptions;
	/**
	 * @var string the command to execute
	 */
	protected $_command;
	/**
	 * @var array the list of command arguments
	 */
	protected $_args = array();
	/**
	 * @var string the full command string to execute
	*/
	protected $_execCommand;
	/**
	 * @var string the stdout output
	 */
	protected $_stdOut = '';
	/**
	 * @var string the stderr output
	 */
	protected $_stdErr = '';
	/**
	 * @var int the exit code
	 */
	protected $_exitCode;
	/**
	 * @var string the error message
	 */
	protected $_error = '';
	/**
	 * @var bool whether the command was successfully executed
	 */
	protected $_executed = false;
	/**
	 * @param string|array $options either a command string or an options array (see setOptions())
	 */
	public function __construct($options = null)
	{
		if (is_array($options)) {
			$this->setOptions($options);
		} elseif (is_string($options)) {
			$this->setCommand($options);
		}
	}
	/**
	 * @param array $options array of name => value options that should be applied to the object
	 * You can also pass options that use a setter, e.g. you can pass a 'fileName' option which
	 * will be passed to setFileName().
	 * @return Command for method chaining
	 */
	public function setOptions($options)
	{
		foreach ($options as $key => $value) {
			if (property_exists($this, $key)) {
				$this->$key = $value;
			} else {
				$method = 'set'.ucfirst($key);
				if (method_exists($this, $method)) {
					call_user_func(array($this,$method), $value);
				} else {
					throw new \Exception("Unknown configuration option '$key'");
				}
			}
		}
		return $this;
	}
	/**
	 * @param string $command the command or full command string to execute, like 'gzip' or 'gzip -d'.
	 * You can still call addArg() to add more arguments to the command. If $escapeCommand was set to true,
	 * the command gets escaped through escapeshellcmd().
	 * @return Command for method chaining
	 */
	public function setCommand($command)
	{
		$this->_command = $this->escapeCommand ? escapeshellcmd($command) : $command;
		return $this;
	}
	/**
	 * @return string|null the command that was set through setCommand() or passed to the constructor. Null if none.
	 */
	public function getCommand()
	{
		return $this->_command;
	}
	/**
	 * @return string|bool the full command string to execute. If no command was set with setCommand()
	 * or passed to the constructor it will return false.
	 */
	public function getExecCommand()
	{
		if ($this->_execCommand===null) {
			$command = $this->getCommand();
			if (!$command) {
				$this->_error = 'Could not locate any executable command';
				return false;
			}
			$args = $this->getArgs();
			$this->_execCommand = $args ? $command.' '.$args : $command;
		}
		return $this->_execCommand;
	}
	/**
	 * @param string $args the command arguments as string. Note that these will not get escaped!
	 * @return Command for method chaining
	 */
	public function setArgs($args)
	{
		$this->_args = array($args);
		return $this;
	}
	/**
	 * @return string the command args that where set through setArgs() or added with addArg() separated by spaces
	 */
	public function getArgs()
	{
		return implode(' ', $this->_args);
	}
	/**
	 * @param string $key the argument key to add e.g. `--feature` or `--name=`. If the key does not end with
	 * and `=`, the $value will be separated by a space, if any. Keys are not escaped unless $value is null
	 * and $escape is `true`.
	 * @param string|array|null $value the optional argument value which will get escaped if $escapeArgs is true.
	 * An array can be passed to add more than one value for a key, e.g. `addArg('--exclude', array('val1','val2'))`
	 * which will create the option `--exclude 'val1' 'val2'`.
	 * @param bool|null $escape if set, this overrides the $escapeArgs setting and enforces escaping/no escaping
	 * @return Command for method chaining
	 */
	public function addArg($key, $value = null, $escape = null)
	{
		$doEscape = $escape!==null ? $escape : $this->escapeArgs;
		if ($value===null) {
			// Only escape single arguments if explicitely requested
			$this->_args[] = $escape ? escapeshellarg($key) : $key;
		} else {
			$separator = substr($key, -1)==='=' ? '' : ' ';
			if (is_array($value)) {
				$params = array();
				foreach ($value as $v) {
					$params[] = $doEscape ? escapeshellarg($v) : $v;
				}
				$this->_args[] = $key.$separator.implode(' ',$params);
			} else {
				$this->_args[] = $key.$separator.($doEscape ? escapeshellarg($value) : $value);
			}
		}
		return $this;
	}
	/**
	 * @return string the command output (stdout). Empty if none.
	 */
	public function getOutput()
	{
		return $this->_stdOut;
	}
	/**
	 * @return string the error message, either stderr or internal message. Empty if none.
	 */
	public function getError()
	{
		return $this->_error;
	}
	/**
	 * @return string the stderr output. Empty if none.
	 */
	public function getStdErr()
	{
		return $this->_stdErr;
	}
	/**
	 * @return int|null the exit code or null if command was not executed yet
	 */
	public function getExitCode()
	{
		return $this->_exitCode;
	}
	/**
	 * @return string whether the command was successfully executed
	 */
	public function getExecuted()
	{
		return $this->_executed;
	}
	/**
	 * Execute the command
	 *
	 * @return bool whether execution was successful. If false, error details can be obtained through
	 * getError(), getStdErr() and getExitCode().
	 */
	public function execute()
	{
		$command = $this->getExecCommand();
		if (!$command) {
			return false;
		}
		if ($this->useExec) {
			$execCommand = $this->captureStdErr ? "$command 2>&1" : $command;
			exec($execCommand, $output, $this->_exitCode);
			$this->_stdOut = trim(implode("\n", $output));
			if ($this->_exitCode!==0) {
				$this->_stdErr = $this->_stdOut;
				$this->_error = empty($this->_stdErr) ? 'Command failed' : $this->_stdErr;
				return false;
			}
		} else {
			$descriptors = array(
					1   => array('pipe','w'),
					2   => array('pipe','w'),
			);
			$process = proc_open($command, $descriptors, $pipes, $this->procCwd, $this->procEnv, $this->procOptions);
			if (is_resource($process)) {
				$this->_stdOut = trim(stream_get_contents($pipes[1]));
				$this->_stdErr = trim(stream_get_contents($pipes[2]));
				fclose($pipes[1]);
				fclose($pipes[2]);
				$this->_exitCode = proc_close($process);
				if ($this->_exitCode!==0) {
					$this->_error = $this->_stdErr ? $this->_stdErr : "Failed without error message: $command";
					return false;
				}
			} else {
				$this->_error = "Could not run command $command";
				return false;
			}
		}
		$this->_executed = true;
		return true;
	}
	/**
	 * @return string the current command string to execute
	 */
	public function __toString()
	{
		return (string)$this->getExecCommand();
	}
}

/**
 * File
 *
 * A convenience class for temporary files.
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 * @version 1.0.1-dev
 * @license http://www.opensource.org/licenses/MIT
 */
class File
{
	/**
	 * @var string the name of this file
	 */
	protected $_fileName;
	/**
	 * Constructor
	 *
	 * @param string $content the tmp file content
	 * @param string|null $suffix the optional suffix for the tmp file
	 * @param string|null $suffix the optional prefix for the tmp file. If null 'php_tmpfile_' is used.
	 * @param string|null $directory directory where the file should be created. Autodetected if not provided.
	 */
	public function __construct($content, $suffix = null, $prefix = null, $directory = null)
	{
		if ($directory===null) {
			$directory = self::getTempDir();
		}
		if ($prefix===null) {
			$prefix = 'php_tmpfile_';
		}
		$this->_fileName = tempnam($directory,$prefix);
		if ($suffix!==null) {
			$newName = $this->_fileName.$suffix;
			rename($this->_fileName, $newName);
			$this->_fileName = $newName;
		}
		file_put_contents($this->_fileName, $content);
	}
	/**
	 * Delete tmp file on shutdown
	 */
	public function __destruct()
	{
		unlink($this->_fileName);
	}
	/**
	 * Send tmp file to client, either inline or as download
	 *
	 * @param string|null $filename the filename to send. If empty, the file is streamed inline.
	 * @param string the Content-Type header
	 * @param bool $inline whether to force inline display of the file, even if filename is present.
	 */
	public function send($name = null, $contentType, $inline = false)
	{
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Type: '.$contentType);
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($this->_fileName));
		if ($name!==null || $inline) {
			$disposition = $inline ? 'inline' : 'attachment';
			header("Content-Disposition: $disposition; filename=\"$name\"");
		}
		readfile($this->_fileName);
	}
	/**
	 * @param string $name the name to save the file as
	 * @return bool whether the file could be saved
	 */
	public function saveAs($name)
	{
		return copy($this->_fileName, $name);
	}
	/**
	 * @return string the full file name
	 */
	public function getFileName()
	{
		return $this->_fileName;
	}
	/**
	 * @return string the path to the temp directory
	 */
	public static function getTempDir()
	{
		if (function_exists('sys_get_temp_dir')) {
			return sys_get_temp_dir();
		} elseif ( ($tmp = getenv('TMP')) || ($tmp = getenv('TEMP')) || ($tmp = getenv('TMPDIR')) ) {
			return realpath($tmp);
		} else {
			return '/tmp';
		}
	}
	/**
	 * @return string the full file name
	 */
	public function __toString()
	{
		return $this->_fileName;
	}
}