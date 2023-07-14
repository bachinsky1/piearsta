<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2021, BlueBridge.
 */

/**
 * Class lockRecord
 */
class monitoringFlags
{
    /** @var config  */
    private $cfg;

    private $path;

    private $ext = 'flag';

    /**
     * monitoringFlags constructor.
     */
    public function __construct()
    {
        $this->cfg = loadLibClass('config');

        $path = AD_SRV_ROOT . '/flags/';

        if(!file_exists($path) || !is_dir($path)) {
            mkdir($path);
        }

        $this->path = $path;
    }

    public function ok($method)
    {
        touch($this->path . $method . '.ok.' . $this->ext, (strtotime('now') + 1));
    }

    public function warning($method)
    {
        touch($this->path . $method . '.warning.' . $this->ext, (strtotime('now') + 1));
    }

    public function critical_error($method)
    {
        touch($this->path . $method . '.critical_error.' . $this->ext, (strtotime('now') + 1));
    }

}

?>