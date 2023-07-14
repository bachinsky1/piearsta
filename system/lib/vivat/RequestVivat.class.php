<?php

class RequestVivat
{
    private $libConfig = null;
    private $db = null;

    // Swagger Format: Y-m-d\TH:i:s.v\Z | 2021-03-11T14:42:17.679Z
    //      T = Just a literal to separate the date from the time
    //      v = Milliseconds (added in PHP 7.0.0 TODO)
    //      Z = "zero hour offset" also known as "Zulu time" (UTC)
    // private $apiDateTimeFormat = 'Y-m-d\TH:i:s.v\Z';

    // Actual response has:
    //      [requestDate] => 2021-03-11T17:29:50+00:00
    //      [responseDateTime] => 2021-03-11T15:29:50+00:00
    //      Sending "requestDate":"2021-03-11T17:40:56+02:00"
    //          Receive "requestDate":"2021-03-11T15:40:56+00:00"
//    private $apiDateTimeFormat = 'Y-m-d\TH:i:sP';
    private $apiDateTimeFormat = 'Y-m-d\TH:i:s\Z';

    private $apiSex = array(
        1 => 'Male',
        2 => 'Female',
    );

    /** @var monitoringFlags $flag */
    private $flags = null;

    /**
     * RequestVivat constructor.
     */
    public function __construct()
    {
        $this->libConfig = loadLibClass('config');
        $this->db = loadLibClass('db');

        // Set vivat api config
        $configEnv = $this->libConfig->get('env');
        $configVivatApi = $this->libConfig->get('vivatApi');
        $this->configVivatApi = $configVivatApi[$configEnv];
    }

    /**
     * @param array $params
     *      array data
     */
    public function calendarUploadSlots($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::calendarUploadSlots");
        }

        $result = $params['_result'];

        // Set auth token
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->setAuthToken($tempParams);
        }

        // Set current request data
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );

            $result = $this->makeCallStartNewRequest($tempParams);

            $result['currentRequest']['action'] = 'calendarUploadSlots';
            $result['currentRequest']['requestData'] = array(
                'requestId' => $this->helperGetNewRequestId(),
                'requestDate' => $this->helperGetNewRequestDate(),
                'source' => $this->configVivatApi['calendarUploadSlots']['source'],
            );

            $result['currentRequest']['requestDataLocal'] = $params['data'];

            $tempParams = array(
                '_result' => $result,
            );

            $result = $this->localToExternalCalendarSlots($tempParams);
        }

        // Upload slots
        if ($result['_continue'] === true)
        {
            $makeCallParams = array(
                '_result' => $result,
            );

            $result = $this->makeCall($makeCallParams);
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    /**
     * @param array $params
     *      array _result
     */
    public function getVaccinationAppointmentRequests($params)
    {

        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::getVaccinationAppointmentRequests");
        }

        $result = $params['_result'];

        // Set auth token
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->setAuthToken($tempParams);
        }

        // Set current request data
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->makeCallStartNewRequest($tempParams);

            $result['currentRequest']['action'] = 'appointmentRequests';
            $result['currentRequest']['requestData'] = array(
                'requestId' => $this->helperGetNewRequestId(),
                'requestDate' => $this->helperGetNewRequestDate(),
                'source' => $this->configVivatApi['appointmentRequests']['source'],
                'pageSize' => $this->configVivatApi['appointmentRequests']['pageSize'],
            );

            // Set optional filters
            if ( ! empty($result['localData']['dateFrom']))
            {
                $result['currentRequest']['requestData']['dateFrom'] = $this->helperFromLocalDateTimeToExternalDateTime($result['localData']['dateFrom']);
            }
            if ( ! empty($result['localData']['dateTo']))
            {
                $result['currentRequest']['requestData']['dateTo'] = $this->helperFromLocalDateTimeToExternalDateTime($result['localData']['dateTo']);
            }
            if ( ! empty($result['localData']['vpIds']))
            {
                $result['currentRequest']['requestData']['vaccinationPlaceId'] = $result['localData']['vpIds'];
            }
        }

        // Get vaccination requests
        if ($result['_continue'] === true)
        {
            $makeCallParams = array(
                '_result' => $result,
            );

            $result = $this->makeCall($makeCallParams);
        }

        // External data to local data
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->externalToLocalVaccinationAppointmentRequests($tempParams);
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    // ---

    /**
     * @param array $params
     *      array _result
     * @return array $result
     */
    private function makeCall($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::makeCall");
        }

        $result = $params['_result'];
        $result['logData']['RequestResult'] = array();

        $method = $result['currentRequest']['action'];

        $result['currentRequest']['config'] = $this->configVivatApi[$result['currentRequest']['action']];

        $baseConfigKeys = array('apiBaseUrl', 'stopOnResponseCodes');
        foreach ($baseConfigKeys as $key)
        {
            if ( ! isset($result['currentRequest']['config'][$key]))
            {
                $result['currentRequest']['config'][$key] = $this->configVivatApi[$key];
            }
        }

        // Fake api call
        if ($result['currentRequest']['config']['fakeApiCall'] === true)
        {
            $result['_continue'] = false;
        }

        // Init curl
        if ($result['_continue'] === true)
        {
            $fullUrl = trim($result['currentRequest']['config']['apiBaseUrl'], '/') . '/' . trim($result['currentRequest']['config']['apiPath'], '/');
            $result['currentRequest']['curlInitFullUrl'] = $fullUrl;
            $ch = curl_init(trim($fullUrl));

            if ($ch === false)
            {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to init curl';
            }
        }

        // Set curl options
        if ($result['_continue'] === true)
        {
            $jsonString = json_encode($result['currentRequest']['requestData']);
            $result['currentRequest']['curlOptions'] = array(
                CURLOPT_POST => 1,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_VERBOSE => 0,
                CURLOPT_HEADER => 1,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_POSTFIELDS => $jsonString,
                CURLOPT_SSL_VERIFYHOST => ($result['currentRequest']['config']['sslOn'] === true) ? 2 : 0,
                CURLOPT_SSL_VERIFYPEER => ($result['currentRequest']['config']['sslOn'] === true) ? 1 : 0,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonString),
                ),
            );

            // Add auth token
            if ( ! empty($result['dataAuth']['token']))
            {
                $result['currentRequest']['curlOptions'][CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $result['dataAuth']['token'];
            }

            $tempResult = curl_setopt_array($ch, $result['currentRequest']['curlOptions']);

            if ($tempResult === false)
            {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to set curl option';
            }
        }

        // Exec curl
        if ($result['_continue'] === true)
        {
            $result['currentRequest']['requestJSON'] = json_encode($result['currentRequest']['requestData']);
            $result['currentRequest']['curlResponseRaw'] = curl_exec($ch);
            $result['currentRequest']['curlInfo'] = curl_getinfo($ch);
            $result['currentRequest']['curlError'] = curl_error($ch);

            if(DEBUG) {
                var_dump(array(
                    'request' => $result['currentRequest']['requestData'],
                    'requestJSON' => json_encode($result['currentRequest']['requestData']),
                    'result' => $result['currentRequest']['curlResponseRaw'],
                    'curlInfo' => $result['currentRequest']['curlInfo'],
                    'curlError' => $result['currentRequest']['curlError'],
                ));

                var_dump('=====================================================');

                var_dump(array(
                    'rawRequest' => json_encode($result['currentRequest']['requestData']),
                    'rawResponse' => json_encode($result['currentRequest']['curlResponseRaw']),
                ));
            }

            if ($result['currentRequest']['curlResponseRaw'] === false)
            {
                $result['_continue'] = false;
                $result['_error'] = 'Curl exec return false';
            }

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($result['currentRequest']['curlResponseRaw'], 0, $header_size);
            $body = json_decode(substr($result['currentRequest']['curlResponseRaw'], $header_size), true);

            $logData = array(
                'requestData' => array(
                    'url' => $result['currentRequest']['curlInfo']['url'],
                    'method' => $method,
                    'requestHeader' => $result['currentRequest']['curlOptions'][CURLOPT_HTTPHEADER],
                    'requestData' => json_decode($jsonString, true),
                ),
                'responseData' => array(
                    'httpCode' => $result['currentRequest']['curlInfo']['http_code'],
                    'responseHeader' => $header,
                    'body' => $body,
                ),
            );

            $result['logData']['RequestResult'] = $logData;
        }

        // Parse response
        if ($result['_continue'] === true)
        {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $result['currentRequest']['curlResponseHeader'] = substr($result['currentRequest']['curlResponseRaw'], 0, $headerSize);
            $result['currentRequest']['curlResponseBody'] = substr($result['currentRequest']['curlResponseRaw'], $headerSize);

            $result['currentRequest']['responseData'] = json_decode($result['currentRequest']['curlResponseBody'], true);
            $result['currentRequest']['responseCode'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

            // TODO When response code is 503 Service Unavailable
            //      its send html with css and its mess-up visual result-structure
            //      Should remove when they start sending back just response-code or short json
            if ($result['currentRequest']['responseCode'] === 503)
            {
                $result['currentRequest']['curlResponseRaw'] = '_removed_503_html';
                $result['currentRequest']['curlResponseBody'] = '_removed_503_html';
            }

            // Close curl
            curl_close($ch);
        }

        // Fake api call
        if ($result['currentRequest']['config']['fakeApiCall'] === true)
        {
            $result = $this->addFakeApiCallResult($result);
            $result['_continue'] = true;
        }

        // Stop on response codes
        if ($result['_continue'] === true)
        {
            if (in_array($result['currentRequest']['responseCode'], $result['currentRequest']['config']['stopOnResponseCodes']))
            {
                $result['_continue'] = false;
                $result['_error'] = 'Config stopOnResponseCodes';
            }
        }

        // Auth token has expired
        // Note: error="invalid_token", error_description="The token expired at '03/19/2021 08:39:55'"
        if ($result['_continue'] === true
            && in_array($result['currentRequest']['responseCode'], $this->configVivatApi['auth']['getNewTokenResponseCodes']))
        {
            $currentRequestToResend = $result['currentRequest'];

            $tempParams = array(
                '_result' => $result,
                'markCurrentTokenAsExpired' => true,
            );
            $result = $this->setAuthToken($tempParams);

            if ($result['_continue'] === true)
            {
                $tempParams = array(
                    '_result' => $result,
                );
                $result = $this->makeCallStartNewRequest($tempParams);

                $result['currentRequest'] = $currentRequestToResend;
                $tempParams = array(
                    '_result' => $result,
                );
                $result = $this->makeCall($tempParams);
            }
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    /**
     * @param array $params
     *      array _result
     * @return array $result
     */
    private function makeCallStartNewRequest($params)
    {
        $result = $params['_result'];

        if ( ! isset($result['requests']))
        {
            $result['currentRequest'] = array();
            $result['requests'] = array();
        }

        if ( ! empty($result['currentRequest']))
        {
            $result['requests'][] = $result['currentRequest'];
            $result['currentRequest'] = array();
        }

        return $result;
    }

    /**
     * @param array $params
     *      array _result
     *      bool markCurrentTokenAsExpired = false
     * @return array $result
     */
    private function setAuthToken($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::setAuthToken");
        }

        $result = $params['_result'];

        if ( ! isset($result['dataAuth']))
        {
            $result['dataAuth'] = array(
                'token' => '',
                'tokenDbRow' => array(),
                'refreshTokenDateH' => null,
                'refreshTokenAttempts' => array(),
            );
        }

        if ( ! isset($result['dataAuthAll']))
        {
            $result['dataAuthAll'] = array();
        }

        $param['markCurrentTokenAsExpired'] = (isset($params['markCurrentTokenAsExpired'])) ? $params['markCurrentTokenAsExpired'] : false;

        $result['dataAuth']['refreshTokenDateH'] = date('Y-m-d h');
        if ( ! isset($result['dataAuth']['refreshTokenAttempts'][$result['dataAuth']['refreshTokenDateH']]))
        {
            $result['dataAuth']['refreshTokenAttempts'][$result['dataAuth']['refreshTokenDateH']] = 0;
        }

        // Mark current token as expired
        if ($result['_continue'] === true && $param['markCurrentTokenAsExpired'] === true)
        {
            $authTokenRow = array(
                'is_expired' => 1,
                'expired_at' => date('Y-m-d H:i:s'),
            );
            saveValuesInDb('vivat_auth_tokens', $authTokenRow, $result['dataAuth']['tokenDbRow']['id']);

            $result['dataAuth']['tokenDbRow']['is_expired'] = $authTokenRow['is_expired'];
            $result['dataAuth']['tokenDbRow']['expired_at'] = $authTokenRow['expired_at'];
        }

        // If token is empty or it is expired get non-expired from db if any
        if ($result['_continue'] === true && (empty($result['dataAuth']['token']) || $result['dataAuth']['tokenDbRow']['is_expired'] === 1))
        {
            $dbQuery = 'SELECT * FROM vivat_auth_tokens WHERE is_expired = 0 LIMIT 1';
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0)
            {
                $tempRows = $query->getArray();
                if ( ! empty($tempRows[0]))
                {
                    if ( ! empty($result['dataAuth']['token']))
                    {
                        $result['dataAuthAll'][] = $result['dataAuth'];
                    }

                    $result['dataAuth']['token'] = $tempRows[0]['token'];
                    $result['dataAuth']['tokenDbRow'] = $tempRows[0];
                }
            }
        }

        // If token is empty or it is expired check if max refresh token attempts per hour has been reached
        if ($result['_continue'] === true && (empty($result['dataAuth']['token']) || $result['dataAuth']['tokenDbRow']['is_expired'] === 1))
        {
            if ($result['dataAuth']['refreshTokenAttempts'][$result['dataAuth']['refreshTokenDateH']] >= $this->configVivatApi['auth']['maxRefreshTokenAttemptsPerHour'])
            {
                $result['_continue'] = false;
                $result['_error'] = 'Max refresh token attempts per hour reached';
            }
        }

        // If token is empty or it is expired get token from vivat and save in db
        if ($result['_continue'] === true && (empty($result['dataAuth']['token']) || $result['dataAuth']['tokenDbRow']['is_expired'] === 1))
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->getAuthTokenFromVivat($tempParams);

            // Save token in db
            if ($result['_continue'] === true)
            {
                $authTokenRow = array(
                    'token' => $result['currentRequest']['responseData']['token'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_expired' => '0',
                    'expired_at' => 'null',
                );
                $authTokenRow['id'] = saveValuesInDb('vivat_auth_tokens', $authTokenRow);

                if ( ! empty($authTokenRow['id']))
                {
                    if ( ! empty($result['dataAuth']['token']))
                    {
                        $result['dataAuthAll'][] = $result['dataAuth'];
                    }

                    $result['dataAuth']['token'] = $authTokenRow['token'];
                    $result['dataAuth']['tokenDbRow'] = $authTokenRow;

                    $result['dataAuth']['refreshTokenAttempts'][$result['dataAuth']['refreshTokenDateH']]++;
                }
                else
                {
                    $result['_continue'] = false;
                    $result['_error'] = 'Failed to save new token in vivat_auth_tokens table';
                }
            }
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    /**
     * @param array $params
     *      array _result
     * @return array $result
     */
    private function getAuthTokenFromVivat($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::getAuthTokenFromVivat");
        }

        $result = $params['_result'];

        // Set current request data
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->makeCallStartNewRequest($tempParams);

            $result['currentRequest']['action'] = 'auth';
            $result['currentRequest']['requestData'] = array(
                'apiKey' => $this->configVivatApi['apiKey'],
            );
        }

        // Make call
        if ($result['_continue'] === true)
        {
            $makeCallParams = array(
                '_result' => $result,
            );

            $result = $this->makeCall($makeCallParams);

            if ($result['currentRequest']['responseCode'] !== 200 || empty($result['currentRequest']['responseData']['token']))
            {
                $result['_continue'] = false;
                $result['_error'] = 'Auth token responseCode is not 200 OR empty token';
            }
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    /**
     * @param array $result
     * @return array $result
     */
    private function addFakeApiCallResult($result)
    {
        if ($result['currentRequest']['action'] === 'auth')
        {
            $result['currentRequest']['responseCode'] = $this->configVivatApi['auth']['fakeApiCallResponseCode'];
            if ($result['currentRequest']['responseCode'] === 200)
            {
                $result['currentRequest']['responseData'] = array(
                    'token' => 'fake-token-here-' . rand(1000, 9999),
                );
            }
        }

        if ($result['currentRequest']['action'] !== 'auth')
        {
            if ( ! empty($result['dataAuth']['token']))
            {
                $result['currentRequest']['curlOptions'] = array(
                    CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $result['dataAuth']['token']),
                );
            }
        }

        if ($result['currentRequest']['action'] === 'calendarUploadSlots')
        {
            if ( ! empty($result['dataAuth']['token']))
            {
                $result['currentRequest']['curlOptions'] = array(
                    CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $result['dataAuth']['token']),
                );
            }

            $result['currentRequest']['responseCode'] = $this->configVivatApi['calendarUploadSlots']['fakeApiCallResponseCode'];
            if ($result['currentRequest']['responseCode'] === 200)
            {
                $result['currentRequest']['responseData'] = array(
                    'requestId' => $result['currentRequest']['requestData']['requestId'],
                    'requestDate' => $result['currentRequest']['requestData']['requestDate'],
                    'responseGuid' => $this->helperGetNewRequestId(),
                    'responseDateTime' => $this->helperGetNewRequestDate(),
                );
            }
        }

        if ($result['currentRequest']['action'] === 'appointmentRequests')
        {
            $result['currentRequest']['responseCode'] = $this->configVivatApi['appointmentRequests']['fakeApiCallResponseCode'];
            if ($result['currentRequest']['responseCode'] === 200)
            {
                $result['currentRequest']['responseData'] = array(
                    'requestId' => $result['currentRequest']['requestData']['requestId'],
                    'requestDate' => $result['currentRequest']['requestData']['requestDate'],
                    'responseGuid' => $this->helperGetNewRequestId(),
                    'responseDateTime' => $this->helperGetNewRequestDate(),
                    'source' => 'piearsta',
                    'collectionOfRequests' => array(),
                );

                $count = $this->configVivatApi['appointmentRequests']['fakeApiCallResultRequestsCount'];

                // add specific (not random) item to test

                $useSpecific = true;
                $fixedTime = false;

                if($useSpecific) {

                    $cancelAppointment = true;

                    $appDate = date('Y-m-d', strtotime('+' . (4 * 2) . 'hours'));
                    $appFrom = date('H:i', strtotime('+' . (4 * 2) . 'hours'));
                    $appTo = date('H:i', strtotime('+' . ((4 * 2) + 2) . 'hours'));

                    if($fixedTime) {
                        $appDate = '2021-04-29';
                        $appFrom = '08:00';
                        $appTo = '10:00';
                    }

                    $specificItem = array(
                        'vaccinationPlaceId' => '111111-11',
                        'personCode' => date('dmy', (time() - ( (10 * 4 + 4) * 365 * 24 * 60 * 60))) . '13227',
                        'queueId' => $cancelAppointment ? null : 222,
                        'firstName' => 'Дядя4',
                        'lastName' => 'Федя4',
                        'dateOfBirth' => date('Y-m-d', (time() - ( (10 * 4 + 4) * 365 * 24 * 60 * 60))),
                        'sex' => 1,
                        'personPhone' => array(
                            array('phone' => '0000000' . 4),
                            array('phone' => 4 . '0000000'),
                        ),
                        'personEmail' => array(
                            array('email' => 'test' . 4 . '@test.com'),
                            array('email' => 4 . 'test@test.com'),
                        ),
                        'appointment' => array(
                            array(
                                'vaccination' => array(
//                                    array('vaccinationType' => 'COVID-19'),
//                                    array('vaccinationType' => 'FLU'),
                                ),
                                'appointmentDate'=> $appDate,
                                'timeFrom' => $appFrom,
                                'timeTo' => $appTo,
                                'anyOtherTime' => false,
                            ),
                        ),
                        'recordId' => $cancelAppointment ? ('fakeRecordId' . 4) : '',
                        'cancelAppointment' => $cancelAppointment,
                    );

                    $result['currentRequest']['responseData']['collectionOfRequests'][] = $specificItem;

                } else {

                    for ($i = 1; $i <= $count; $i++)
                    {
                        $cancelAppointment = (rand(0, 1) === 1);
                        $item = array(
                            //'vaccinationPlaceId' => 'FAKE-' . $i,
                            'vaccinationPlaceId' => '10064114-01',
                            'personCode' => date('dmy', (time() - ( (10 * $i + $i) * 365 * 24 * 60 * 60))) . '13225',
//                            'queueId' => $i,
                            'queueId' => rand(1, 10000),
                            'firstName' => 'āēīņļč-first-name-' . $i,
                            'lastName' => 'āēīņļč-last-name-' . $i,
                            'dateOfBirth' => date('Y-m-d', (time() - ( (10 * $i + $i) * 365 * 24 * 60 * 60))),
                            'sex' => rand(0, 1),
                            'personPhone' => array(
                                array('phone' => '0000000' . $i),
                                array('phone' => $i . '0000000'),
                            ),
                            'personEmail' => array(
                                array('email' => 'test' . $i . '@test.com'),
                                array('email' => $i . 'test@test.com'),
                            ),
                            'vaccination' => array(
                                array(
                                    'vaccinationType' => 'COVID-19',
                                    'appointmentDate'=> date('Y-m-d', strtotime('+' . ($i * 2) . 'hours')),
                                    'timeFrom' => date('H:i', strtotime('+' . ($i * 2) . 'hours')),
                                    'timeTo' => date('H:i', strtotime('+' . (($i * 2) + 2) . 'hours')),
                                    'anyOtherTime' => (rand(0, 1) === 1),
                                ),
                            ),
                            'recordId' => ($cancelAppointment === true) ? 'fakeRecordId' . $i : '',
                            'cancelAppointment' => $cancelAppointment,
                        );

                        $result['currentRequest']['responseData']['collectionOfRequests'][] = $item;
                    }
                }


            }
        }

        return $result;
    }

    // ---

    private function helperGetNewRequestId()
    {
        // Note: cant use any id else there will be error
        // "$.requestId": [
        //     "The JSON value could not be converted to System.Guid. Path: $.requestId | LineNumber: 0 | BytePositionInLine: 18."
        //	]

        // Ensure that you have "php_com_dotnet" extension loaded in your php.ini:
        // extension=php_com_dotnet.dll
        if (function_exists('com_create_guid'))
        {
            return com_create_guid();
        }

        // @see https://www.php.net/manual/en/function.com-create-guid.php
        if ( ! function_exists('com_create_guid'))
        {
            return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535),
                mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535),
                mt_rand(0, 65535), mt_rand(0, 65535));
        }
    }

    private function helperGetNewRequestDate()
    {
        // we should pass to vivat current datetime in UTC
        $objDateTime = new DateTime('NOW');
        $objDateTime->setTimezone(new DateTimeZone('UTC'));
        return $objDateTime->format($this->apiDateTimeFormat);
    }

    private function helperFromLocalDateTimeToExternalDateTime($localDateTime)
    {
        $objDateTime = new DateTime($localDateTime);
        $objDateTime->setTimezone(new DateTimeZone('UTC'));

        return $objDateTime->format($this->apiDateTimeFormat);
    }

    private function helperFromExternalDateTimeToLocalDateTime($externalDateTime)
    {

        if(isset($externalDateTime) && $externalDateTime) {

            $objDateTime = new DateTime($externalDateTime);
            $objDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));

            return $objDateTime->format('Y-m-d H:i:s');
        }

        return null;
    }

    private function helperFromExternalDateTimeToLocalDate($externalDateTime)
    {
        if(isset($externalDateTime) && $externalDateTime) {

            $objDateTime = new DateTime($externalDateTime);
            $objDateTime->setTimezone(new DateTimeZone(date_default_timezone_get()));

            return $objDateTime->format('Y-m-d');
        }

        return null;
    }

    public function helperConvertTo2hInterval($intervalStart, $intervalEnd)
    {
        $startDateTime = new DateTime($intervalStart);
        $startHour = $startDateTime->format('G');
        $startHour = ($startHour % 2) === 0 ? $startHour : ($startHour - 1);
        $startAs2hInterval = $startDateTime->format('Y-m-d') . ' ' . str_pad($startHour, 2, '0', STR_PAD_LEFT) .  ':00:00';

        $endDateTime = new DateTime($intervalEnd);
        $endHour = $endDateTime->format('G');
        $endHour = ($endHour % 2) === 0 ? $endHour : ($endHour + 1);
        if ($endHour === $startHour)
        {
            $endHour += 2;
        }
        if ($endHour >= 24)
        {
            $endHour = 0;
            $endDateTime->modify('+2 hour');
        }
        $endAs2hInterval = $endDateTime->format('Y-m-d') . ' ' . str_pad($endHour, 2, '0', STR_PAD_LEFT) .  ':00:00';

        return array($startAs2hInterval, $endAs2hInterval);
    }

    // --- Mappers

    /**
     * @see table vivat_cache_data
     * @see https://vivat-api-tv.zzdats.lv/api-calendar/swagger/index.html
     *
     * @param array $params
     *      array _result
     * @return array $result
     */
    private function localToExternalCalendarSlots($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::localToExternalCalendarSlots");
        }

        $result = $params['_result'];

        $result['currentRequest']['failedTimeIntervals'] = array();

        // Group all rows by vp and date
        if ($result['_continue'] === true)
        {
            $localDataGroupedByVp = array();

            foreach ($result['currentRequest']['requestDataLocal'] as $row)
            {
                if ( ! isset($localDataGroupedByVp[$row['vp_id']]))
                {
                    $localDataGroupedByVp[$row['vp_id']] = array();
                }

                $date = $row['date'];

                if ( ! isset($localDataGroupedByVp[$row['vp_id']][$date]))
                {
                    $localDataGroupedByVp[$row['vp_id']][$date] = array();
                }

                $localDataGroupedByVp[$row['vp_id']][$date][] = $row;
            }
        }

        // Create external data structure
        if ($result['_continue'] === true)
        {

            $result['convertTo2hIntervals'] = array();

            foreach ($localDataGroupedByVp as $vpId => $rows) {

                // construct vp with data

                $availableSlot = array(
                    'vaccinationPlaceID' => $vpId,
                    'dates' => array(),
                );

                $rows = $localDataGroupedByVp[$vpId];

                foreach ($rows as $date => $rows2)
                {

                    $dateSlot = array(
                        'date' => $date . 'T00:00:00Z',
                        'timeSlots' => array(),
                    );

                    if($date != '9999-12-31') {

                        foreach ($rows2 as $row)
                        {
//                        $dateTimeFromTo = $this->helperConvertTo2hInterval($row['interval_start'], $row['interval_end']);
//
//                        $result['convertTo2hIntervals'][] = array(
//                            'before' => array($row['interval_start'], $row['interval_end']),
//                            'after' => $dateTimeFromTo,
//                        );

                            $dateTimeFromTo = array($row['interval_start'], $row['interval_end']);

                            $timeSlot = array(
                                'timeFrom' => $this->helperFromLocalDateTimeToExternalDateTime($dateTimeFromTo[0]),
                                'timeTo' => $this->helperFromLocalDateTimeToExternalDateTime($dateTimeFromTo[1]),
                                'availableSlots' => (int) $row['free_slots'],
                            );

                            $dateSlot['timeSlots'][] = $timeSlot;
                        }
                    }

                    if ( ! empty($dateSlot['timeSlots']))
                    {
                        $availableSlot['dates'][] = $dateSlot;
                    }
                }

                if(empty($availableSlot['dates'])) {
                    unset($availableSlot['dates']);
                }

                $result['currentRequest']['requestData']['slots'][] = $availableSlot;
            }
        }

        // Return
        return $result;
    }

    /**
     * @see table vivat_booking_requests
     * @see https://vivat-api-tv.zzdats.lv/api-appointment/swagger/index.html
     *
     * @param array $params
     *      array _parentResult
     *      array externalData
     */
    private function externalToLocalVaccinationAppointmentRequests($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("RequestVivat::externalToLocalVaccinationAppointmentRequests");
        }

        $result = $params['_result'];

        $result['logData']['processedRecords'] = array(
            'records' => 0,
            'duplicates' => 0,
            'nonDuplicateRecordsIds' => array(),
            'duplicateRecordsIds' => array(),
        );

        if ($result['_continue'] === true && ! empty($result['currentRequest']['responseData']['collectionOfRequests']))
        {
            $result['currentRequest']['responseDataLocal'] = array();

            $localItemBase = array();

            $localItemBase['request_guid'] = $result['currentRequest']['responseData']['requestId'];
//            $localItemBase['request_datetime'] = $this->helperFromExternalDateTimeToLocalDateTime($result['currentRequest']['responseData']['requestDate']);
            $localItemBase['request_datetime'] = date('Y-m-d H:i:s', strtotime($result['currentRequest']['responseData']['requestDate']));
            $localItemBase['response_guid'] = isset($result['currentRequest']['responseData']['responseGuid']) ?
                $result['currentRequest']['responseData']['responseGuid'] : NULL;

            $localItemBase['source'] = null;
            if ( ! empty($result['currentRequest']['responseData']['source']))
            {
                $localItemBase['source'] = $result['currentRequest']['responseData']['source'];
            }

            foreach ($result['currentRequest']['responseData']['collectionOfRequests'] as $vacciantionRequestItem)
            {
                $localItem = $localItemBase;

                $localItem['vp_id'] = null;
                if ( ! empty($vacciantionRequestItem['vaccinationPlaceId']))
                {
                    $localItem['vp_id'] = $vacciantionRequestItem['vaccinationPlaceId'];

                    if(
                        !empty($localItem['vp_id'])
                        && isset($result['localData']['vpIdsDoctors'][$localItem['vp_id']])
                        && !empty($result['localData']['vpIdsDoctors'][$localItem['vp_id']])
                    ) {
                        $localItem['doctors_list'] = json_encode($result['localData']['vpIdsDoctors'][$localItem['vp_id']]);
                    }
                }

                $localItem['queue_id'] = null;

                if ( ! empty($vacciantionRequestItem['queueId']))
                {
                    $localItem['queue_id'] = $vacciantionRequestItem['queueId'];
                } else {
                    unset($localItem['queue_id']);
                }

                $localItem['cancel_appointment'] = '0';
                if ($vacciantionRequestItem['cancelAppointment'] === true)
                {
                    $localItem['cancel_appointment'] = '1';
                }

                $localItem['aiis_record_id'] = null;
                if ( ! empty($vacciantionRequestItem['recordId']))
                {
                    $localItem['aiis_record_id'] = $vacciantionRequestItem['recordId'];
                }

                $result['logData']['processedRecords']['records']++;

                $dupDbQuery = '';

                if(!empty($localItem['queue_id']) && $localItem['queue_id'] != 0) {

                    // Check and mark duplicate NOT CANCEL (processing_status = 7)

                    $dupDbQuery = "SELECT * FROM vivat_booking_requests 
                                WHERE
                                    queue_id IS NOT NULL AND
                                    queue_id = " . $localItem['queue_id'] . " AND 
                                    processing_status IN (0,1,2,7)
                                ORDER BY request_datetime ASC";
                } else {

                    $pk = $vacciantionRequestItem['personCode'];

                    if(!empty($pk) && !empty($localItem['vp_id']) && !empty($localItem['aiis_record_id'])) {

                        // Check and mark duplicate CANCEL (processing_status = 7)

                        $dupDbQuery = "SELECT * FROM vivat_booking_requests 
                                WHERE
                                    cancel_appointment = 1 AND
                                    vp_id = '" . $localItem['vp_id'] . "' AND  
                                    pk = '" . $pk . "' AND 
                                    aiis_record_id = '" . $localItem['aiis_record_id'] . "' AND 
                                    processing_status IN (0,1,2,7) 
                                ORDER BY request_datetime ASC";
                    }
                }

                if(!empty($dupDbQuery)) {

                    $dupQuery = new query($this->db, $dupDbQuery);

                    if($dupQuery->num_rows()) {

                        if(!$this->flags) {
                            /** @var monitoringFlags $flag */
                            $this->flags = loadLibClass('monitoringFlags');
                        }

                        // new logic: replace old dup record (status 7) with new one and increment count
                        // or create new dup record with status 7 and count = 1

                        $localItem['processing_status'] = 7;

                        $dupArray = $dupQuery->getArray();

                        if(count($dupArray) > 1) {

                            // logic for already existing duplicates
                            // normally we should have only one 7-status record if we already received dups

                            $status7Ids = array();
                            $status7count = array();
                            $statusOtherIds = array();

                            foreach ($dupArray as $dup) {
                                if($dup['processing_status'] == 7) {
                                    $status7Ids[] = $dup['id'];
                                    $status7count[$dup['id']] = $dup['count'];
                                } else {
                                    $statusOtherIds[] = $dup['id'];
                                }
                            }

                            if(count($status7Ids) > 0) {

                                //  new record will be added with 7 status and incremented count

                                if(count($status7Ids) > 1) {

                                    $summOfCounts = 0;

                                    foreach ($status7count as $rec) {
                                        $summOfCounts += $rec;
                                    }

                                    $localItem['count'] = $summOfCounts + 1;

                                } else {

                                    $localItem['count'] = $status7count[$status7Ids[0]] + 1;
                                }

                                // and other 7 status records will be deleted

                                $delDupDBQuery = "DELETE FROM vivat_booking_requests WHERE id IN (" . implode(',', $status7Ids) . ")";
                                doQuery($this->db, $delDupDBQuery);

                            }

                            // some dups found in 0,1,2 statuses

                            if(count($statusOtherIds) > 1) {

                                // we leave first (earlier) record intact and delete others
                                // incrementing count
                                array_shift($statusOtherIds);

                                if(!empty($localItem['count'])) {
                                    $localItem['count'] += count($statusOtherIds);
                                } else {
                                    $localItem['count'] = count($statusOtherIds);
                                }

                                $delDupDBQuery = "DELETE FROM vivat_booking_requests WHERE id IN (" . implode(',', $statusOtherIds) . ")";
                                doQuery($this->db, $delDupDBQuery);
                            }

                            $localItem['count'] = !empty($localItem['count']) ? $localItem['count'] : 1;

                        } else {

                            // first duplicate received

                            $localItem['count'] = 1;
                        }

                        $result['logData']['processedRecords']['duplicates']++;
                        $result['logData']['processedRecords']['duplicateRecordsIds'][] = !empty($localItem['queue_id']) ? $localItem['queue_id'] : 'NULL';

                        $this->flags->warning('NewIncomingDuplicates');

                    } else {

                        $result['logData']['processedRecords']['records']++;
                        $result['logData']['processedRecords']['nonDuplicateRecordsIds'][] = !empty($localItem['queue_id']) ? $localItem['queue_id'] : 'NULL';
                    }
                }

                // Set patient data
                $localItem['patient_data'] = array();
                $localItem['patient_data']['personCode'] = $vacciantionRequestItem['personCode'];
                $localItem['patient_data']['firstName'] = $vacciantionRequestItem['firstName'];
                $localItem['patient_data']['lastName'] = $vacciantionRequestItem['lastName'];

                $localItem['patient_data']['dateOfBirth'] = $this->helperFromExternalDateTimeToLocalDate($vacciantionRequestItem['dateOfBirth']);

                $localItem['patient_data']['gender'] = null;
                if (isset($this->apiSex[$vacciantionRequestItem['sex']]))
                {
                    $localItem['patient_data']['gender'] = $this->apiSex[$vacciantionRequestItem['sex']];
                }

                $localItem['patient_data']['phonesList'] = array();
                if ( ! empty($vacciantionRequestItem['personPhone']))
                {
                    foreach ($vacciantionRequestItem['personPhone'] as $tempItem)
                    {
                        if ( ! empty($tempItem['phone']))
                        {
                            $localItem['patient_data']['phonesList'][] = $tempItem['phone'];
                        }
                    }
                }

                $localItem['patient_data']['emailsList'] = array();
                if ( ! empty($vacciantionRequestItem['personEmail']))
                {
                    foreach ($vacciantionRequestItem['personEmail'] as $tempItem)
                    {
                        if ( ! empty($tempItem['email']))
                        {
                            $localItem['patient_data']['emailsList'][] = $tempItem['email'];
                        }
                    }
                }

                // Set vaccination data
                if ( ! empty($vacciantionRequestItem['appointment']))
                {
                    foreach ($vacciantionRequestItem['appointment'] as $tempItem)
                    {
                        $localItemVaccination = array();

                        if(!empty($tempItem['vaccination'])) {

                            $localItemVaccinationType = array();

                            foreach ($tempItem['vaccination'] as $vaccinationTypeItem) {

                                if(!empty($vaccinationTypeItem['vaccinationType'])) {

                                    $localItemVaccinationType[] = $vaccinationTypeItem['vaccinationType'];
                                }
                            }

                            if(!empty($localItemVaccinationType)) {

                                $localItemVaccination['vaccination_type'] = implode(',', $localItemVaccinationType);
                            }
                        }

                        $localItemVaccination['appointment_date'] = date('Y-m-d', strtotime($tempItem['appointmentDate']));
                        // TODO: remove 'Z' when they will start to pass it to us
                        $fullDtFrom = $localItemVaccination['appointment_date'] . ' ' . $tempItem['timeFrom'] . ':00Z';

                        $dtObj = new DateTime($fullDtFrom);
                        $dtObj->setTimezone(new DateTimeZone(date_default_timezone_get()));

                        $realDateFrom = $dtObj->format('Y-m-d H:i:s');
                        // TODO: remove 'Z' when they will start to pass it to us
                        $fullDtTo = $localItemVaccination['appointment_date'] . ' ' . $tempItem['timeTo'] . ':00Z';

                        $dtObj = new DateTime($fullDtTo);
                        $dtObj->setTimezone(new DateTimeZone(date_default_timezone_get()));

                        $realDateTo = $dtObj->format('Y-m-d H:i:s');

                        $localItemVaccination['appointment_time_from'] = $realDateFrom;
                        $localItemVaccination['appointment_time_to'] = $realDateTo;

                        $localItemVaccination['any_other_time'] = '0';
                        if ($tempItem['anyOtherTime'] === true)
                        {
                            $localItemVaccination['any_other_time'] = '1';
                        }

                        // Add final local data
                        $result['currentRequest']['responseDataLocal'][] = array_merge($localItemBase, $localItem, $localItemVaccination);
                    }
                } else {
                    $result['currentRequest']['responseDataLocal'][] = array_merge($localItemBase, $localItem, array());
                }
            }
        }

        // Return
        return $result;
    }
}
