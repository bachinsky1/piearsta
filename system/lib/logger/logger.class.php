<?php


class logger
{
    private $fileName;
    private $lines = array();

    public static function file($fileName)
    {
        $instance = new self();
        $instance->fileName = $fileName;
        $instance->lines = array();

        return $instance;
    }

    public function addString($string)
    {
        $this->lines[] = PHP_EOL.$string;
        return $this;
    }
    public function addArray($array, $title = '')
    {
        $text = print_r($array,true);
        if (! empty($title) ){
            $text = $title.'='.$text;
        }
        $this->lines[] = PHP_EOL.$text;
        return $this;
    }
    public function addArrayFilterEmpty($array, $title = '')
    {
        $array = array_filter($array);
        return $this->addArray($array, $title);
    }

    public function append()
    {
//        $cfg = &loadLibClass('config');
//        if (! in_array(getIp(), $cfg->get('debugIp'))) { return false; }
//
//	    $text = implode(PHP_EOL,$this->lines);
//	    file_put_contents(AD_LOG_FOLDER . $this->fileName . '.log', $text, FILE_APPEND);
    }

    public function rewrite()
    {
        $cfg = &loadLibClass('config');
        if (! in_array(getIp(), $cfg->get('debugIp'))) { return false; }

	    $text = implode(PHP_EOL,$this->lines);
	    file_put_contents(AD_LOG_FOLDER . $this->fileName . '.log', $text);
    }



}
