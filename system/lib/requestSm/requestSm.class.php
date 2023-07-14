<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2019, BlueBridge Technologies.
 */

// ------------------------------------------------------------------------

/**
 * RequestSm class
 */
class RequestSm
{
    /** @var int */
    private $clinicId;
    /** @var config  */
    private $cfg;
    /** @var db */
    private $db;
    /** @var string  */
    private $url;
    /** @var string  */
    private $login;
    /** @var string  */
    private $password;
    /** @var string  */
    private $terminalId;
    /** @var string */
    private $clientId;
    /** @var array  */
    private $methods;
    /** @var xml */
    private $xmlClass;

    /**
     * RequestSm constructor.
     * @param $clinicId
     */
    public function __construct($clinicId = null)
    {
        /** @var db */
        $this->db = loadLibClass('db');

        /** @var config */
        $this->cfg = loadLibClass('config');

        $env = $this->cfg->get('env');

        /** @var array $smApiCfg */
        $smApiCfg = $this->cfg->get('smartMedicalApi');

        if(!empty($clinicId)) {
            $this->clinicId = $clinicId;
            $clinicData = $this->getClinicData();
            $this->login = $clinicData['api_login'];
            $this->password = $clinicData['api_password'];
            $this->url = $clinicData['api_url'] . 'API/';
            $this->terminalId = $clinicData['terminal_id'];
            $this->clientId = $clinicData['client_id'];
        }

        $this->methods = $smApiCfg['methods'];

        $this->xmlClass = loadLibClass('xml');
    }

    /**
     * @param $method
     * @param $data
     * @param string $format
     * @param null $clinicId
     * @return array
     */
    public function requestSm($method, $data, $format = 'xml', $clinicId = null, $reqRowIDs = array())
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestSm::requestSm");
        }

//        pre($method);
//        pre($data);
//        pre($format);
//        pre($clinicId);
//        exit;

        $startSend = microtime(true);

        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $login = null;
        $password = null;
        $url = null;
        $terminalId = null;

        $mt = explode('.', explode(' ', microtime(false))[0])[1];
        $dtString = date('Y-m-d_H-i-s.') . $mt;
        $logFile = AD_CMS_FOLDER . 'log/requestSm/' . ($clinicId ?:'NULL') . '_' . $dtString . '.log';

        file_put_contents($logFile,
            'backtrace:'. print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),true).PHP_EOL
        , FILE_APPEND);
        file_put_contents($logFile,
            'clientID:'.$clinicId.PHP_EOL
        , FILE_APPEND);


        if(!empty($clinicId)) {

            $clinicData = $this->getClinicById($clinicId);

            if(!empty($clinicData)) {

                $login = $clinicData['api_login'];
                $password = $clinicData['api_password'];
                $url = $clinicData['api_url'] . 'API/';

                // DEBUG on Zhenja terminal
//                $url = 'https://sem-ev.smartmedical.eu/' . 'API/';

                $terminalId = $clinicData['terminal_id'];
                $clientId = $clinicData['client_id'];
            }

        } else {

            $login = $this->login;
            $password = $this->password;
            $url = $this->url;
            $terminalId = $this->terminalId;
            $clientId = $this->clientId;
        }

        file_put_contents($logFile,
            'login:'.$login.PHP_EOL.
            'password:'.$password.PHP_EOL.
            'url:'.$url.PHP_EOL.
            'terminalId:'.$terminalId.PHP_EOL
        , FILE_APPEND);

        if(empty($login) || empty($password)) {

            file_put_contents($logFile, 'No credentials', FILE_APPEND);
            return array(
                'success' => false,
                'message' => 'No credentials',
                'result' => null,
                'logData' => array(
                    'message' => 'No credentials',
                ),
            );
        }

        if(empty($url)) {

            file_put_contents($logFile, 'No api url', FILE_APPEND);
            return array(
                'success' => false,
                'message' => 'No api url',
                'result' => null,
                'logData' => array(
                    'message' => 'No api url',
                ),
            );
        }

        if(key_exists($method, $this->methods)) {

            $postData = array(
                'request' => $this->methods[$method],
                'login' => $login,
                'password' => $password,
            );

            if($format == 'xml') {

                $postData['xml'] = $this->constructXml($data);

            } else {

                $postData['terminal_id'] = $terminalId;
                $postData['client_id'] = $clientId;
                $postData['xml'] = 'xml';

                // try to add inside data
//                $data['request'] = $this->methods[$method];
//                $data['login'] = $login;
//                $data['password'] = $password;
//                $data['terminal_id'] = $terminalId;
//                $data['client_id'] = $clientId;

                $postData['data'] = json_encode($data);
            }

            $debugPostData = $postData;

            $postData = http_build_query($postData);

            $parsed = parse_url($url);

            if($parsed) {
                $port = isset($parsed['port']) ? $parsed['port'] : null;
                $newUrl = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
            } else {
                $port = null;
                $newUrl = $url;
            }

            // !!!!!!!!!
            // mock server -- debug:
//            if($method == 'insurancePayment') {
//                $newUrl = 'https://00a87ae1-cacc-4f2b-96ef-a8f16ec7b7ec.mock.pstmn.io';
//            }


            $ch = curl_init($newUrl);

            if($port) {
                curl_setopt($ch, CURLOPT_PORT, $port);
            }

            $sslCheck = 1;

            if(DEBUG) {
                $sslCheck = 0;
            }

            curl_setopt($ch, CURLOPT_POST, 1);

            // connection and exchange timeouts
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, SM_CURL_CONNECTION_TIMEOUT);
            curl_setopt($ch, CURLOPT_TIMEOUT, SM_CURL_TIMEOUT);

            file_put_contents($logFile,
                'request:'. print_r($postData,true).PHP_EOL
            , FILE_APPEND);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslCheck);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslCheck);

            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_HEADER, 1);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            //
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

            $result = curl_exec($ch);

            // extract header
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($result, 0, $headerSize);
            $body = substr($result, $headerSize);

            file_put_contents($logFile,
                'curl_info:'. print_r(curl_getinfo($ch),true).PHP_EOL.
                'curl_error:'.curl_error($ch).PHP_EOL.
                'response:'.$result.PHP_EOL
            , FILE_APPEND);

            $endSend = microtime(true);
            if(!empty($reqRowIDs)) {
                // filter requests row ids data if any
                $reqRowIDs = array_unique($reqRowIDs);
                $reqRowIDs = array_filter($reqRowIDs);
                if(!empty($reqRowIDs)) {
                    $upd = "UPDATE vivat_booking_requests SET sm_response_time = ".($endSend - $startSend)." WHERE id IN (".implode(',',$reqRowIDs).")";
                    $updQuery = new query($this->db, $upd);
                }
            }

//            if($method == 'insurancePayment') {
//                pre('HERE 555!');
//                pre($method);
//                pre($format);
//                pre(array(
//                    'newUrl' => $newUrl,
//                    'request' => $this->methods[$method],
//                    'clinicId' => $clinicId,
//                    'terminalId' => $terminalId,
//                    'login' => $login,
//                    'password' => $password,
//                    'postData' => $postData,
//                    'responseHeaders' => $header,
//                    'responseBody' => $body,
//                    'data' => $data,
//                ));
//                exit;
//            }

//            var_dump('request to SM');
//            var_dump($format);
//            var_dump($debugPostData);
//            var_dump($result);
//            var_dump(curl_getinfo($ch));
//            var_dump('End of request to SM info');
//            var_dump('---');

//            if( !$result || !isset($result['response']))

            $httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if( !$result || $httpResponseCode !== 200)
            {

                $retArray = array(
                    'success' => false,
                    'message' => 'Request error',
                    'httpCode' => $httpResponseCode,
                    'result' => $result,
                );

                if(DEBUG) {

                    $retArray['debug'] = array(
                        'request' => $url . '?' . $postData,
                        'data' => $data,
                        'xml' => $this->constructXml($data),
                        'post_data' => $postData,
                        'curl_info' => curl_getinfo($ch),
                        'curl_error' => curl_error($ch),
                    );
                }

                $retArray['logData'] = array(
                    'requestJson' => json_encode($debugPostData),
                    'responseRaw' => $result,
                    'httpCode' => $retArray['httpCode'],
                    'curl_info' => curl_getinfo($ch),
                    'curl_error' => curl_error($ch),
                );

                curl_close($ch);

                return $retArray;
            }

            $responseStatus = null;

            if($format == 'xml') {
                $parsedBody = !empty($body) ? $this->parseXml($body) : array();
                $responseStatus = !empty($parsedBody) ? $parsedBody['response']['statuss']['@attributes']['error'] : 1;
            } else {
                $parsedBody = json_decode($body, true);
                $responseStatus = '1';
            }

            $retArray = array(
                'success' => true,
                'httpCode' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
                'message' => '',
                'responseStatus' => $responseStatus,
                'result' => $body,
                'parsedResult' => $parsedBody,
            );

            if(DEBUG) {

                $retArray['debug'] = array(
                    'request' => $url . '?' . $postData,
                    'httpCode' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
                    'data' => $data,
                    'rawResult' => $result,
                    'url' => $url,
                    'xml' => $this->constructXml($data),
                    'curl_info' => curl_getinfo($ch),
                    'curl_error' => curl_error($ch),
                    'postData' => $postData,
                );
            }

            curl_close($ch);

            $retArray['logData'] = array(
                'requestJson' => json_encode($debugPostData),
                'responseRaw' => $result,
                'httpCode' => $retArray['httpCode'],
            );

            return $retArray;

        } else {

            return array(
                'success' => false,
                'message' => 'Method not exists',
                'result' => null,
                'logDdata' => array('message' => 'Method not exists'),
            );
        }
    }

    /**
     * @return bool|string
     */
    private function getClinicData()
    {
        $dbQuery = "SELECT * FROM " . $this->cfg->getDbTable('clinics', 'self') . "
                    WHERE id = " . $this->clinicId;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }

        return false;
    }

    /**
     * @param $clinicId
     * @return array|false|int
     */
    private function getClinicById($clinicId)
    {
        $dbQuery = "SELECT * FROM " . $this->cfg->getDbTable('clinics', 'self') . "
                    WHERE id = " . $clinicId;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }

        return false;
    }

    /**
     * @param $data
     * @return string|null
     */
    private function constructXml($data)
    {
        if(!is_array($data)) {
            return null;
        }

        try {

            $xml = Array2XML::createXML('request', $data);
            $xmlData = $xml->saveXML();

        } catch (Exception $e) {

            $xmlData = '<?xml version="1.0" encoding="UTF-8"?><errors><code>' . $e->getCode() . '</code></errors>';
        }


        return $xmlData;
    }

    private function parseXml($xml)
    {

        try {

            $result = XML2Array::createArray($xml);

        } catch (Exception $e) {

            $result = array(
                'error' => array(
                    'code' => $e->getCode(),
                ),
            );
        }

        return $result;
    }
}

?>
