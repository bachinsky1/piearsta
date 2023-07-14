<?php

ini_set('max_execution_time',0);

require_once(dirname(__FILE__).'/_file_cache_funcs.php');

class execScript {

    public static $suffSessFile = '_requests_runner.pids';
    public static $procSessFile = null;
    public static $procSess = null;
    public static $logFile = '_runner.log';

    protected $checkStats = false;
    protected $onlyOneCmd = false;
    protected $killSIG = 9;

    protected $maxExecTime = 0;

    protected $masterPID = null;
    protected $maxChilds = 0;
    protected $useMemory = false;

    protected static $threadPool = array();
    protected static $addInfoPool = array();

    function __construct($pid = false,$maxPids = 0, $useFile = false, $sameFile = false)
    {
        if(empty($pid)) $pid = getmypid();
        $this->masterPID = $pid;
        $this->useMemory = !$useFile;
        if(!empty($maxPids)) $this->maxChilds = (int)$maxPids;

        if(empty(self::$procSess) && $useFile) {
            self::$procSess = (!$sameFile) ? microtime(true) : '_';
            self::$procSessFile = self::$procSess.self::$suffSessFile;
            TMPFile::setFileName(self::$procSessFile);
        } elseif($useFile) {
            if(!empty(self::$procSessFile)) TMPFile::setFileName(self::$procSessFile);
        }

        // force recheck old file content
        if(!$this->useMemory) $this->recheckCacheFile();

        if($useFile) {
            if(empty($stored) || !is_array($stored)) $stored = array();
            $stored[$pid] = array();
            TMPFile::setTempData($stored);
        } else {
            self::$threadPool[$pid] = array();
            self::$addInfoPool[$pid] = array();
        }
    }

    function __destruct()
    {
        // force stop all children
        $this->stopAll();
        // force recheck old file content
        if(!$this->useMemory) $this->recheckCacheFile();
        // force remove temp pid file
        if(!$this->useMemory) TMPFile::removeTempFile(self::$procSessFile);
    }

    public function getFileSuffix() { return self::$suffSessFile; }
    public function getSessFile($full_path = false)
    {
        $sFile = TMPFile::getFileName();
        return ((!$full_path) ? basename($sFile) : $sFile);
    }
    public function getMaxChilds() { return $this->maxChilds; }
    public function setMaxChilds($maxPids = 0)
    {
        if(!empty($maxPids)) $this->maxChilds = $maxPids;
        return (int)$this->maxChilds;
    }

    public function setOnlyOneCmd($state = null)
    {
        if(!is_null($state)) $this->onlyOneCmd = !empty($state);
        return $this->onlyOneCmd;
    }

    public function setStopSIG($num = null)
    {
        if(!is_null($num)) $this->killSIG = (int)$num;
        if(empty($this->killSIG)) $this->killSIG = 9;
        return $this->killSIG;
    }

    public function setMaxExecTime($tmo = false)
    {
        if(empty($tmo)) {
            $this->maxExecTime = 0;
            return false;
        }
        if(preg_match('/^\d+$/',$tmo)) $tmo .= 'S';
        $maxTime = $this->_parseTimeout($tmo,'S');
        if(empty($maxTime)) {
            $this->maxExecTime = 0;
            return false;
        }
//        $this->maxExecTime = ((int)$maxTime * 1000);
        $this->maxExecTime = (int)$maxTime;
        return $this->maxExecTime;
    }

    public function checkStats($stat = null)
    {
        if(!is_null($stat)) $this->checkStats = !empty($stat);
        return $this->checkStats;
    }

    public function run($command = false,$nohup = false)
    {
        if(empty($command)) return null;
        $this->recheckPool();
        if(!empty($this->maxChilds)) {
            $cpool = $this->_getPool();
//echo var_export($cpool,true);
//            if(sizeof($cpool) >= $this->maxChilds) { print_r(sizeof($cpool).' -- exceeds'); return null; }
            if(sizeof($cpool) >= $this->maxChilds) return null;
        }
        if(!empty($this->onlyOneCmd)) {
            $cpid = $this->getPidByCmd($command);
//echo var_export($cpid,true);
//            if(!empty($cpid)) { print_r($cpid.' -- '.$command.' -- already run'); return null; }
            if(!empty($cpid)) return null;
        }
        $cpid = $this->_shell_exec_background($command,$nohup);
        $this->_addPid($cpid,$command);
        return $cpid;
    }

    public function stop($cpid = false)
    {
        if(empty($cpid)) return null;
        $this->recheckPool();
        $stored = $this->getStoredPids();
        if(empty($stored[$this->masterPID])) return null;
        $cpool = $stored[$this->masterPID];
        if(!in_array($cpid,$cpool)) return null;
        $isRan = $this->_is_pid_running($cpid);
        if($isRan) $this->_kill_pid($cpid);
        $ind = array_search($cpid,$cpool);
        if(is_int($ind)) unset($cpool[$ind]);
        $stored[$this->masterPID] = $cpool;
        return $this->setStoredPids($stored);
    }

    public function stopAll()
    {
        $stored = $this->getStoredPids();
        if(empty($stored[$this->masterPID])) return null;
        $cpool = $stored[$this->masterPID];
        foreach($cpool as $cpid) {
            $isRan = $this->_is_pid_running($cpid);
            if($isRan) $this->_kill_pid($cpid);
        }
        $stored[$this->masterPID] = array();
        return $this->setStoredPids($stored);
    }

    public function recheckCacheFile()
    {
        TMPFile::setFileName(self::$procSessFile);
        $stored = TMPFile::getTempData();
        if(empty($stored) || !is_array($stored)) return false;
        $newPool = array();
        foreach($stored as $mpid => $cpids) {
            $isRan = $this->_is_pid_running($mpid);
            if($isRan) $newPool[$mpid] = $cpids;
        }
        if(empty($newPool)) {
//            TMPFile::removeTempFile(self::$procSessFile);
        } else {
            TMPFile::setFileName(self::$procSessFile);
            TMPFile::setTempData($newPool);
        }
        return true;
    }

    public function recheckPool()
    {
        $killStats = array('X','Z','X+','Z+');
        $stored = $this->getStoredPids();
        if(empty($stored) || !is_array($stored)) return false;
        if(empty($stored[$this->masterPID])) $stored[$this->masterPID] = array();

        $cpool = $stored[$this->masterPID];
        $newPool = array();
        foreach($cpool as $cpid) {
            $isRan = $this->_is_pid_running($cpid);
            if($isRan) {
                $exceedMaxTime = $this->_isExceededTime($cpid);
                if($exceedMaxTime) {
                    $this->_kill_pid($cpid,9);
                    $this->_removeInfo($cpid);
                    continue;
                }
                if($this->checkStats) {
                    $stat = $this->getProcStats($cpid);
                    if(in_array($stat,$killStats)) {
                        $this->_kill_pid($cpid);
                        $this->_removeInfo($cpid);
                    } else {
                        $newPool[] = $cpid;
                    }
                } else {
                    $newPool[] = $cpid;
                }
            } else {
                $this->_removeInfo($cpid);
            }
        }
        $stored[$this->masterPID] = $newPool;
        return $this->setStoredPids($stored);
    }

    public function getProcStats($cpid = false)
    {
        $stored = $this->getStoredPids();
        if(empty($stored) || !is_array($stored)) return false;
        if(empty($stored[$this->masterPID])) $stored[$this->masterPID] = array();

        $cpool = $stored[$this->masterPID];
        if(empty($cpool)) return null;
        if(!empty($cpid)) return exec('ps -p '.$cpid.' -eo stat');
        $stats = array();
        foreach($cpool as $cpid) $stats[$cpid] = exec('ps -p '.$cpid.' -eo stat');
        return $stats;
    }

    public function getPidByCmd($command = false,$activeOnly = false)
    {
        if(empty($command)) return false;
        $isRan = false;
/*
        if(!empty(self::$addInfoPool[$this->masterPID])) {
            $sPool = self::$addInfoPool[$this->masterPID];
            $filtered = array_filter(array_keys($sPool), function($cpid) use($command,$sPool) {
                $cmd = quotemeta($command);
                return (preg_match('|'.$cmd.'|',$sPool[$cpid]['cmd']));
            });
            if(!empty($filtered)) {
                $pind = key($filtered);
                $cpid = $filtered[$pind];
                $isRan = $this->_is_pid_running($cpid);
                if(!$isRan) $cpid = null;
            }
        } else {
*/
            $cpid = exec('pgrep -f "'.$command.'"');
            $isRan = $this->_is_pid_running($cpid);
//        }
        if($activeOnly) return ($isRan) ? $cpid : false;
        return $cpid;
    }

    public function countChilds()
    {
        $cpool = $this->_getPool();
        return sizeof($cpool);
    }

    protected function _removeInfo($cpid = false)
    {
        if(empty($cpid)) return false;
        if(!empty(self::$addInfoPool[$this->masterPID])) {
            $sPool = self::$addInfoPool[$this->masterPID];
            if(!empty($sPool[$cpid])) unset(self::$addInfoPool[$this->masterPID][$cpid]);
        }
        return true;
    }

    protected function _getPidInfo($cpid = false,$key = false)
    {
        if(empty($cpid)) return array();
        if(empty(self::$addInfoPool[$this->masterPID][$cpid])) return array();
        return (!empty($key))
                ? @self::$addInfoPool[$this->masterPID][$cpid][$key]
                : @self::$addInfoPool[$this->masterPID][$cpid];
    }

    protected function _isExceededTime($cpid = false)
    {
        if(empty($cpid)) return false;
        if(empty($this->maxExecTime)) return false;
        if(!is_array(self::$addInfoPool[$this->masterPID])) return false;
        if(empty(self::$addInfoPool[$this->masterPID][$cpid])) return false;
        $startTime = $this->_getPidInfo($cpid,'ts');
        if(empty($startTime)) return false;
        $curr = microtime(true);
        return (($curr - $startTime) > $this->maxExecTime);
    }

    protected function setStoredPids($stored = array())
    {
        if(empty($stored)) return false;
        if(!$this->useMemory) {
            TMPFile::setFileName(self::$procSessFile);
            TMPFile::setTempData($stored);
        } else {
            self::$threadPool = $stored;
        }
        return true;
    }

    protected function getStoredPids()
    {
        $stored = array();
        if(!$this->useMemory) {
            TMPFile::setFileName(self::$procSessFile);
            $stored = TMPFile::getTempData();
        } else {
            $stored = self::$threadPool;
        }
        if(empty($stored)) $stored = array();
        return $stored;
    }

    protected function _getPool()
    {
        $stored = $this->getStoredPids();
        if(empty($stored) || !is_array($stored)) return array();
        if(empty($stored[$this->masterPID])) return array();
        return $stored[$this->masterPID];
    }

    protected function _addPid($pid = false,$command = false)
    {
        $stored = $this->getStoredPids();
        if(empty($stored) || !is_array($stored)) $stored = array();
        if(!isset($stored[$this->masterPID])) $stored[$this->masterPID] = array();
        if(in_array($pid,$stored[$this->masterPID])) return $stored[$this->masterPID];
        $stored[$this->masterPID][] = $pid;
        $this->setStoredPids($stored);
        if(!is_array(self::$addInfoPool[$this->masterPID]))
            self::$addInfoPool[$this->masterPID] = array();
        self::$addInfoPool[$this->masterPID][$pid] = array('ts' => microtime(true), 'cmd' => $command);
        return $stored[$this->masterPID];
    }

    protected function _shell_exec_background($command = false, $nohup = false)
    {
        if(empty($command)) return null;
        $prefix = 'env MAGICK_THREAD_LIMIT=1;';

        if($nohup) {
            $pid = (int)exec(
                $prefix.' '.sprintf(
                    'nohup %s 1> /dev/null 2> /dev/null & echo $!',
                    $command
                )
            );
        } else {
            $pid = (int)exec(
                sprintf(
                    '%s 1> /dev/null 2> /dev/null & echo $!',
                    $command
                )
            );
        }

        return $pid;
    }

    public function _is_pid_running($pid = false)
    {
        if(empty($pid)) return null;
        exec(
            sprintf(
                'kill -0 %d 1> /dev/null 2> /dev/null',
                $pid
            ),
            $output,
            $exit_code
        );
        return $exit_code === 0;
    }

    public function _kill_pid($pid = false,$forceSIG = false)
    {
        if(empty($pid)) return null;
        $kSIG = $this->killSIG;
        if(!empty($forceSIG)) $kSIG = $forceSIG;
        exec(
            sprintf(
                'kill -%d %d 1> /dev/null 2> /dev/null',
                $kSIG,$pid
            ),
            $output,
            $exit_code
        );
        return $exit_code === 0;
    }

    protected function _parseTimeout($tmo, $ret_format = 'S')
    {
        if(empty($tmo)) return 0;
        $val = 0; $dim = 'S';
        if(preg_match('/^\d+$/',$tmo)) {
            $val = $tmo; $tmo .= 'S';
        }
        if($val == 0) {
            list(,$dim) = preg_split('/(\d+)/',$tmo,2);
            $val = preg_replace('/^(\d+).*/','$1',$tmo);
        }
        if($val > 0) {
            $ret_format = strtoupper($ret_format);
            if($ret_format === $dim) return intval($val);
            if(($dim === 'M') && ($ret_format === 'S')) return intval($val * 60);
            if(($dim === 'S') && ($ret_format === 'M')) return intval($val / 60);
            if(($dim === 'H') && ($ret_format === 'S')) return intval($val * 3600);
            if(($dim === 'S') && ($ret_format === 'H')) return intval($val / 3600);
            if(($dim === 'M') && ($ret_format === 'H')) return intval($val * 60);
            if(($dim === 'H') && ($ret_format === 'M')) return intval($val / 60);
        }
        return intval($val);
    }


}

?>