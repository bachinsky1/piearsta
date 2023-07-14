<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2019, BlueBridge Technologies.
 */

// ------------------------------------------------------------------------

/**
 * Class logFile
 */
class logFile
{
    /** @var int */
    private $clinicId;
    /** @var config  */
    private $cfg;
    /** @var db */
    private $db;

    private $logDir;

    /**
     * logFile constructor.
     */
    public function __construct()
    {
        /** @var db */
        $this->db = loadLibClass('db');

        /** @var config */
        $this->cfg = loadLibClass('config');

        $env = $this->cfg->get('env');

        $this->logDir = $this->logFile = AD_CMS_FOLDER . "log/";
    }

    /**
     * @param $file
     * @param $string
     */
    public function log($file, $string)
    {
        $fh = fopen($this->logDir . $file, 'a');
        fwrite($fh, "\n". $string);
        fclose($fh);
    }

}

?>