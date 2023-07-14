<?php

class TMPfile {

    public static $tmpFile = null;

    public static function setFileName($tmpFile = null) { self::$tmpFile = $tmpFile; }
    public static function alreadyExists($tmpFile = null) { return self::_tempFileExists($tmpFile); }

    public static function getFileName()
    {
        $tmpFile = self::$tmpFile;
        if(basename($tmpFile) == $tmpFile) {
            $tmpPath = sys_get_temp_dir();
            if(substr($tmpPath, -1) != '/') $tmpPath .= '/';
            $tmpFile = $tmpPath.$tmpFile;
        }
        return $tmpFile;
    }


    public static function removeTempFile($tmpFile = null)
    {
        if(empty($tmpFile) && !empty(self::$tmpFile)) $tmpFile = self::$tmpFile;
        if(empty($tmpFile)) return false;
        if(basename($tmpFile) == $tmpFile) {
            $tmpPath = sys_get_temp_dir();
            if(substr($tmpPath, -1) != '/') $tmpPath .= '/';
            $tmpFile = $tmpPath.$tmpFile;
        }
        $isExists = self::_tempFileExists($tmpFile);
        if(!$isExists) return false;
        unlink($tmpFile);
        return true;
    }

    public static function setTempData($tmpData = array(),$useName = false)
    {
        if(empty($tmpData)) return false;
        $tmpPath = sys_get_temp_dir();
        if(substr($tmpPath, -1) != '/') $tmpPath .= '/';
        $tmpName = (empty(self::$tmpFile)) ? self::_getUniqueName() : self::$tmpFile;
        $file = $tmpPath.$tmpName;

//        if(file_exists($file)) unlink($file);
        if(!is_string($tmpData)) $tmpData = json_encode($tmpData,true);

        $mfp = fopen("php://memory", 'r+');
        fwrite($mfp,$tmpData);
        rewind($mfp);

        if ($fp = fopen($file,'wb')) {

            $startTime = microtime();
            do {
                $canWrite = flock($fp, LOCK_SH);
                if(!$canWrite) {
                    usleep(round(rand(0, 50) * 50));   // Release the cpu and give the cpu resources to other processes first
                }
                // If the lock is not acquired and the timeout has not expired, continue to acquire the lock
            } while((!$canWrite) && ((microtime() - $startTime) < 50));

            rewind($fp);
            if($mfp !== false) stream_copy_to_stream($mfp,$fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        fclose($mfp);
        if($useName) self::$tmpFile = $tmpName;
        return $tmpName;
    }

    public static function getTempData($tmpFile = null,$decode = true)
    {
        if(empty($tmpFile) && !empty(self::$tmpFile)) $tmpFile = self::$tmpFile;
        if(empty($tmpFile)) return false;
        if(basename($tmpFile) == $tmpFile) {
            $tmpPath = sys_get_temp_dir();
            if(substr($tmpPath, -1) != '/') $tmpPath .= '/';
            $tmpFile = $tmpPath.$tmpFile;
        }
        $isExists = self::_tempFileExists($tmpFile);
        if(!$isExists) return null;
        return self::_readTempFile($tmpFile,$decode);
    }

    public static function getTempList($directory,$tmpSuffix = false,$extentionFilter = array(),$includePath = false)
    {
        if(empty($tmpSuffix) && empty($extentionFilter)) return array();
        if(empty($directory)) $directory = sys_get_temp_dir();
        if(!empty($tmpSuffix)) $tmpSuffix = quotemeta($tmpSuffix);

        // echo $directory;
        // create an array to hold directory list
        $results = array();
        // create a handler for the directory
        $handler = opendir($directory);
        // open directory and walk through the filenames
        while ($file = readdir($handler)) {
            // if file isn't this directory or its parent, add it to the results
            if ($file != "." && $file != "..") {
                $arrInfo = pathinfo($directory.$file);
                //print_r($arrInfo);
                if (isset($extentionFilter) && !empty($extentionFilter)) {
                  if (!in_array(@$arrInfo['extension'],$extentionFilter)) { continue; }
                }
                if(isset($tmpSuffix) && !empty($tmpSuffix)) {
                    if(!preg_match('|'.$tmpSuffix.'$|',@$arrInfo['basename'])) { continue; }
                }
                if($includePath) {
                    $file = (substr($directory, -1) != DIRECTORY_SEPARATOR) ? $directory.DIRECTORY_SEPARATOR.$file : $directory.$file;
                    $file = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR,$file);
                }
                array_push($results, $file);
            }
        }
        closedir($handler);
        return array_unique($results);
    }

    private static function _getUniqueName()
    {
        $filename = false;
        $tmpPath = sys_get_temp_dir();
        if(substr($tmpPath, -1) != '/') $tmpPath .= '/';
        while (true) {
            $filename = md5(uniqid(time(), true) . '.eid');
            if (!file_exists($tmpPath . $filename)) break;
        }
        return $filename;
    }

    private static function _tempFileExists($tmpFile = null)
    {
        if(empty($tmpFile) && !empty(self::$tmpFile)) $tmpFile = self::$tmpFile;
        if(empty($tmpFile)) return false;
        if(!file_exists($tmpFile) || !is_readable($tmpFile)) return false;
        if(filesize($tmpFile) == 0) return false;
        return true;
    }

    private static function _readTempFile($tmpFile = null,$decode = true)
    {
        if(empty($tmpFile) && !empty(self::$tmpFile)) $tmpFile = self::$tmpFile;
        $isExists = self::_tempFileExists($tmpFile);
        if(!$isExists) return false;

        $ffp = fopen($tmpFile, 'r');
        $fp = false;
        if($ffp !== false) {
            $fp = fopen("php://memory", 'r+');
            if($fp !== false) stream_copy_to_stream($ffp,$fp);
            fclose($ffp);
        }
        if($fp !== false) {
            rewind($fp);
            $tmpValue = fread($fp,filesize($tmpFile));
            fclose($fp);
            return ($decode) ? json_decode($tmpValue,true) : $tmpValue;
        }
        return false;
    }
}

?>