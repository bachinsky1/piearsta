<?php

/**
 * Class Vroom
 * Author: Andrey Voroshnin
 * Used in vroom api communication
 * 2020
 */
class Vroom {

    /** @var config */
    private $cfg;

    /** @var db  */
    private $db;

    /** @var string */
    private $table;

    private $vroomUrl = null;
    private $vroomToken = null;
    private $vroomMethods = array();

    /**
     * ServiceDetails constructor.
     */
    public function __construct()
    {
        $this->db = &loadLibClass('db');
        $this->cfg = &loadLibClass('config');
        $this->table = $this->cfg->getDbTable('reservations', 'self');

        /** @var array $vroomCfg */
        $vroomCfg = $this->cfg->get('vroom');
        $env = $this->cfg->get('env');

        $this->vroomUrl = $vroomCfg[$env . 'ApiUrl'];
        $this->vroomToken = $vroomCfg[$env . 'Token'];
        $this->vroomMethods = $vroomCfg['methods'];
        $this->curlConnectionTimeout = $vroomCfg['curlConnectionTimeout'];
        $this->curlTimeout = $vroomCfg['curlTimeout'];
    }

    /**
     * @param $method
     * @param array $data
     * @return array
     */
    public function requestApi($method, array $data)
    {

        if(!isset($this->vroomMethods[$method])) {

            return array(
                'success' => false,
                'message' => 'wrong method',
            );
        }

        // request to vroom ( konsultacijas )

        $payload = json_encode($data);

        $url = $this->vroomUrl . $this->vroomMethods[$method];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $this->vroomToken,
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
                    'method' => $this->vroomMethods[$method],
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
                    'method' => $this->vroomMethods[$method],
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
     * @return array
     */
    public function sendSession()
    {
        if(!isset($_SESSION['user'])) {

            return array(
                'success' => false,
                'message' => 'no logged user',
            );
        }

        $data = array(
            'paSessId' => session_id(),
            'sessData' => $_SESSION['user'],
        );

        return $this->requestApi('sendSession', $data);
    }

    public function saveDoctorImage($id, $image)
    {
        $data = array(
            'id' => $id,
            'image' => $image,
        );

        return $this->requestApi('saveDoctorImage', $data);
    }

}

?>
