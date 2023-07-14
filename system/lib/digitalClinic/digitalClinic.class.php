<?php

/**
 * Class digitalClinic
 * Author: Andrey Voroshnin
 * Used in DC api communication
 * 2021
 */
class digitalClinic {

    /** @var config */
    private $cfg;

    /** @var db  */
    private $db;

    private $dcApiUrl = null;
    private $dcApiToken = null;
    private $dcApiMethods = array();
    private $curlConnectionTimeout = null;
    private $curlTimeout = null;

    /**
     * ServiceDetails constructor.
     */
    public function __construct()
    {
        $this->db = &loadLibClass('db');
        $this->cfg = &loadLibClass('config');

        /** @var array $dcApiCfg */
        $dcApiCfg = $this->cfg->get('dcApiConfig');
        $env = $this->cfg->get('env');

        $this->dcApiUrl = $dcApiCfg[$env . 'ApiUrl'];
        $this->dcApiToken = $dcApiCfg[$env . 'Token'];
        $this->dcApiMethods = $dcApiCfg['methods'];

        $this->curlConnectionTimeout = $dcApiCfg['curlConnectionTimeout'];
        $this->curlTimeout = $dcApiCfg['curlTimeout'];
    }

    /**
     * @param $method
     * @param array $data
     * @return array
     */
    public function requestApi($method, array $data)
    {

        if(!isset($this->dcApiMethods[$method])) {

            return array(
                'success' => false,
                'message' => 'wrong method',
            );
        }

        // request to DC

        $payload = json_encode($data);

        $url = $this->dcApiUrl . $this->dcApiMethods[$method];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->dcApiToken,
            'Content-Type: application/json',
        ));

        curl_setopt($ch, CURLOPT_VERBOSE, 0);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curlConnectionTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curlTimeout);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        $checkSsl = 1;

        if(DEBUG) {
            $checkSsl = 0;
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $checkSsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $checkSsl);

        // show headers in info
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

        $result = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);

        $headers = array(
            'headers' => curl_getinfo($ch, CURLINFO_HEADER_OUT),
            'body' => '',
        );

        $httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(!$result || $httpResponseCode !== 200)
        {
            $retArray = array(
                'success' => false,
                'message' => 'Request error',
                'result' => null,
                'httpCode' => $httpResponseCode,
            );

            if($this->cfg->get('debug')) {
                $retArray['debug'] = array(
                    'url' => $url,
                    'method' => $this->dcApiMethods[$method],
                    'data' => $data,
                    'curl_info' => $curlInfo,
                    'request_headers' => $headers,
                    'curl_error' => curl_error($ch),
                );
            }

        } else {

            $retArray = array(
                'success' => true,
                'result' => $result,
                'debug' => array(
                    'url' => $url,
                    'method' => $this->dcApiMethods[$method],
                    'data' => $data,
                    'curl_info' => $curlInfo,
                    'request_headers' => $headers,
                    'curl_error' => curl_error($ch),
                ),
            );
        }

        curl_close($ch);

        return $retArray;
    }

    /**
     * @param array $slots
     * @return array
     */
    public function unlockCachedSlots(array $slots)
    {
        $data = array(
            'slots' => $slots,
        );

        return $this->requestApi('unlockCachedSlots', $data);
    }
}

?>
