#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../system/func/_pid_exec_funcs.php");

if (posix_getuid() !== 0) {
    echo 'Run this script under ROOT user only!!!. Exiting.'."\n\n";
    exit;
}

$runner = new execScript();

$suff = $runner->getFileSuffix();
$sessFile = $runner->getSessFile(true);
$dir = null;
$files = TMPfile::getTempList($dir,$suff,array(),true);

if(empty($files)) {
    echo 'No files to process. Existsing. (suffix: '.$suff.')'."\n\n";
    exit;
}

$killStats = array('X','Z','X+','Z+');

foreach($files as $file) {
    if($file == $sessFile) continue;
    echo "\n".'Start checking '.basename($file)."\n";
    $json = file_get_contents($file);
    if(!empty($json)) $json = json_decode($json,true);
    if(!is_array($json)) {
        echo 'Malformed JSON data. File skipped.'."\n\n";
        continue;
    }
    foreach($json as $mpid => $cpData) {
        $isRan = $runner->_is_pid_running($mpid);
        if($isRan) {
            $pStat = exec('ps -p '.$mpid.' -eo stat');
            if(in_array($pStat,$killStats)) $pStat .= ' - stucked process. stop manually first';
            echo 'Main process PID '.$mpid.' is still active. STATUS: '.$pStat.'; Checking childs...'."\n";
            foreach($cpData as $cpid) {
                $isRan = $runner->_is_pid_running($cpid);
                if(!$isRan) {
                    echo 'Main process PID '.$mpid.' child PID: '.$cpid.' - incative or stopped. Recheck status manually.'."\n";
                } else {
                    $pStat = exec('ps -p '.$cpid.' -eo stat');
                    if(in_array($pStat,$killStats)) {
                        $pStat .= ' - stucked process';
                    } else {
                        $pStat .= ' - active process';
                    }
                    echo 'Main process PID '.$mpid.' child PID: '.$cpid.'; STATUS: '.$pStat.';'."\n";
                }
            }
            echo "\n";
            continue;
        } else {
            if(!empty($cpData)) {
                foreach($cpData as $cpid) {
                    $runner->_kill_pid(9);
                    echo 'Main process PID '.$mpid.' child PID '.$cpid.' killed.'."\n";
                    $ind = array_search($cpid,$json[$mpid]);
                    if(is_int($ind)) unset($json[$mpid][$ind]);
                }
                if(empty($json[$mpid])) unset($json[$mpid]);
            } else {
                echo 'Main process PID '.$mpid.' has NO child processes. Skipped.'."\n\n";
                unset($json[$mpid]);
                continue;
            }
        }
        if(empty($json[$mpid])) {
            unset($json[$mpid]);
            echo 'Main process PID '.$mpid.' all childs killed. Empty item removed.'."\n";
        }
    }
    if(empty($json)) {
        echo 'All main processes in file '.basename($file).' are inactive, all childs killed. File removed.'."\n\n";
        unlink($file);
    }
}

if(file_exists($sessFile)) unlink($sessFile);

exit;

?>