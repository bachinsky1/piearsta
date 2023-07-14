<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2023, BlueBridge Technologies.
 */

// ------------------------------------------------------------------------

/**
 * RequestSm class
 */
class eglReservation
{
    /** @var config  */
    private $cfg;
    /** @var db */
    private $db;

    /** @var array */
    private $eglCfg;

    /**
     * RequestSm constructor.
     * @param $clinicId
     */
    public function __construct()
    {
        /** @var db */
        $this->db = loadLibClass('db');

        /** @var config */
        $this->cfg = loadLibClass('config');

        $env = $this->cfg->get('env');

        /** @var array $eglConfig */
        $eglCfg = $this->cfg->get('eglConfig');
        $this->eglCfg = $eglCfg[$env];
    }

    public function createAppointment($data, $resId)
    {
        $methodName = 'Appoint';

        $str = http_build_query($data);
        $url = $this->eglCfg['endpoint'] . '?' . $str;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);

        $res = $this->parseResponse($response, $methodName);

        $success = $res == '1 OK';

        $result = array(
            'success' => $success,
            'hsp_reservation_id' => $success ? $resId : null,
        );

        if(DEBUG) {
            $result['debug'] = array(
                'data' => $data,
                'url' => $url,
                'eglResponse' => $response,
            );
        }

        return $result;
    }

    public function deleteAppointment($data)
    {
        $methodName = 'AppointKill';

        $str = http_build_query($data);
        $url = $this->eglCfg['endpoint'] . '?' . $str;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);

        $res = $this->parseResponse($response, $methodName);

        $success = $res == '1 OK';

        $result = array(
            'success' => $success,
        );

        if(DEBUG) {
            $result['debug'] = array(
                'data' => $data,
                'url' => $url,
                'eglResponse' => $response,
            );
        }

        return $result;
    }

    private function parseResponse($body, $methodName)
    {
        $responseProperty = $methodName . 'Response';
        $resultProperty = $methodName . 'Result';

        $response = str_replace('xmlns="app.egl.lv"', 'xmlns="http://app.egl.lv"', $body);
        $response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
        $xml = simplexml_load_string($response);

        $resp = $xml->children('http://schemas.xmlsoap.org/soap/envelope/')
            ->Body->children()->{$responseProperty}->children()->{$resultProperty};

        $array = (array)$resp;

        if(!empty($array[0])) {
            return $array[0];
        }

        return false;
    }
}

?>
