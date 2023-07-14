<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		David Akopyan <davids@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		2
 */

// ------------------------------------------------------------------------

/**
 * Billing system
 */
class BillingSystem
{
    /** @var config $cfg */
    private $cfg;

    private $url;
    private $token;
    private $endpoints;
    private $timeout;
    private $userName;
    private $password;
    private $accountName;
    private $postData;
    private $paymentReference;
    private $stringGenerator;
    private $length;
    private $nonce;


    public function __construct()
    {

        $this->cfg = loadLibClass('config');
        $env = $this->cfg->get('env');

        /** @var array $billingCfg */
        $billingCfg = $this->cfg->get('billing_system');

        $this->url = $billingCfg[$env . 'ApiUrl'];
        $this->token = $billingCfg[$env . 'Token'];
        $this->endpoints = $billingCfg['endpoints'];
        $this->timeout = $billingCfg['timeout'];
        $this->stringGenerator = loadLibClass('stringGenerator');
    }


    public function requestBillingSystem($method, $data)
    {


        if(key_exists($method, $this->endpoints)) {

            $payload = json_encode($data);

            $url = $this->url . $this->endpoints[$method];

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ));

            curl_setopt($ch, CURLOPT_VERBOSE, 0);

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

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
                        'method' => $method,
                        'data' => $data,
                        'curl_info' => $curlInfo,
                        'request_headers' => $headers,
                        'curl_error' => curl_error($ch),
                    );
                }

                curl_close($ch);
                return $retArray;
            }

            curl_close($ch);

            $retArray = array(
                'success' => true,
                'message' => '',
                'result' => $result,
            );

            if($this->cfg->get('debug')) {
                $retArray['debug'] = array(
                    'url' => $url,
                    'method' => $method,
                    'data' => $data,
                    'curl_info' => $curlInfo,
                    'request_headers' => $headers,
                );
            }

            return $retArray;

        } else {
            return array(
                'success' => false,
                'message' => 'Method not exists',
                'result' => null,
            );
        }
    }

    public function setEveryPayConfig()
    {
        $env = $this->cfg->get('env');
        $billingCfg = $this->cfg->get('billing_system')['everyPay'];
        $this->url = $billingCfg[$env . 'ApiUrl'];
        $this->userName = $billingCfg['api_username'];
        $this->password = $billingCfg['api_password'];
        $this->accountName = $billingCfg['account_name'];
        $this->endpoints = $billingCfg['endpoints'];
        $this->length = $billingCfg['nonce_length'];
    }

    public function setPostData($data, $backUrl, $orderId)
    {
        $this->nonce = $this->stringGenerator->generate($this->length);

        $this->postData = array(
            "api_username" =>   $this->userName,
            "account_name" => $this->accountName,
            "amount" => $data['payment_amount'],
            "order_reference" => $orderId,
            "token_agreement" => "unscheduled",
            "nonce" =>  $this->nonce,
            "timestamp" => date('c'),
        //    "email" => $data['user_data']['email'],
            "customer_ip" => $data['user_data']['ip_address'],
            "customer_url" => $backUrl,
            "locale" => $data['language'],
            "preferred_country"=> "LV",
        );
    }

    public function setRefundPostData($data)
    {
        $this->nonce = $this->stringGenerator->generate($this->length);

        $this->postData = array(
            "api_username" =>   $this->userName,
            "amount" => $data['amount'],
            "payment_reference" => $data['payment_reference'],
            "nonce" =>  $this->nonce,
            "timestamp" => date('c'),
        );
    }

    public function setPaymentReference($paymentReference)
    {
        $this->paymentReference = $paymentReference;
    }

    public function requestEveryPay($method)
    {
        if(key_exists($method, $this->endpoints)) {

            $payload = json_encode($this->postData);

            $url = $this->url . $this->endpoints[$method];

            if ($method == 'payments_check'){
                $urlWithReference =
                    str_replace('reference', $this->paymentReference, $url);
                $url = str_replace('key', $this->userName, $urlWithReference);
            }

            if ($method == 'get_payment_methods'){
                $urlWithReference =
                    str_replace('account_name', $this->accountName, $url);
                $url = str_replace('key', $this->userName, $urlWithReference);
            }

            $ch = curl_init($url);

            $methodsWithPostParameters = [
                'payments_create',
                'payments_refund'
            ];

            if (in_array($method, $methodsWithPostParameters)){
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD,$this->userName .':'. $this->password);
            $headers = array(
                "Content-Type: application/json",
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);

            $curlInfo = curl_getinfo($ch);

            $headers = array(
                'headers' => curl_getinfo($ch, CURLINFO_HEADER_OUT),
                'body' => '',
            );

            $httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $successResponseCodes = [
                '200',
                '201'
            ];

            if(!$result)
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
                        'method' => $method,
                        'data' => $payload,
                        'curl_info' => $curlInfo,
                        'request_headers' => $headers,
                        'curl_error' => curl_error($ch),
                    );
                }

                curl_close($ch);
                return $retArray;
            }

            if(!in_array($httpResponseCode, $successResponseCodes))
            {
                $retArray = array(
                    'success' => false,
                    'message' => '',
                    'result' => $result,
                    'httpCode' => $httpResponseCode,
                );

                if($this->cfg->get('debug')) {
                    $retArray['debug'] = array(
                        'url' => $url,
                        'method' => $method,
                        'data' => $payload,
                        'curl_info' => $curlInfo,
                        'request_headers' => $headers,
                        'curl_error' => curl_error($ch),
                    );
                }

                curl_close($ch);
                return $retArray;
            }

            curl_close($ch);

            $retArray = array(
                'success' => true,
                'message' => '',
                'result' => $result,
            );

            if($this->cfg->get('debug')) {
                $retArray['debug'] = array(
                    'url' => $url,
                    'method' => $method,
                    'data' => $this->postData,
                    'curl_info' => $curlInfo,
                    'request_headers' => $headers,
                );
            }

            return $retArray;

        } else {
            return array(
                'success' => false,
                'message' => 'Method not exists',
                'result' => null,
            );
        }

    }

    public function getGeneratedNonce()
    {
        return $this->nonce;
    }
}

?>