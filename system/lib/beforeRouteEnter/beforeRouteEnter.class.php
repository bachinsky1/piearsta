<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2021, BBT.
 * @link		http://adweb.lv
 * @version		2
 */

// ------------------------------------------------------------------------

/** 
 * Before route enter
 */
class BeforeRouteEnter
{
    public $params = array();

    /** @var Config $cfg */
    private $cfg;

    /** @var array */
    private $allovedLanguages = array('lv');

    private $selectedLang;

    /** @var BillingSystem $billingSystem */
    private $billingSystem;

    /**
     * BeforeRouteEnter constructor.
     * @param array $params
     */
    public function __construct($params = array())
    {

        $this->params = $params;

        /** @var Config cfg */
        $this->cfg = loadLibClass('config');
        /** @var logFile logger */
        $this->logger = loadLibClass('logFile');

        $this->billingSystem = loadLibClass('billingSystem');

        // get allowed languages from db

        $dbQuery = "SELECT lang FROM ad_languages WHERE `enable` = 1";
        $query = new query($this->cfg->db, $dbQuery);

        $allLangs = array();

        if($query->num_rows()) {

            while($row = $query->getrow()) {
                $allLangs[] = $row['lang'];
            }
        }

        if(!empty($allLangs) && is_array($allLangs)) {
            $this->allovedLanguages = $allLangs;
        }
    }

    /**
     *  This method is called before any public route enter
     */
    public function beforeRouteEnter()
    {
        $this->selectedLang = getDefaultLang();

        // init some static params

        // allowed types of consultation
        $allowedTypes = array(
            'video-call',
            'phone-call',
            'offline-written-communication',
        );

        // allowed languages
        $allowedLanguages = $this->allovedLanguages;


        //Check if request uri contains language and it is allowed language ,if no happens redirect to def lang

        $urlToCheck = explode('/', $_SERVER['REQUEST_URI']);

        if (!empty($urlToCheck[1])){
            if (strlen($urlToCheck[1]) == 2){
                $languageIsAllowed = in_array($urlToCheck[1], $allowedLanguages);
                if (!$languageIsAllowed){
                    $urlToCheck[1] = getDefaultLang();
                    $url = implode('/', $urlToCheck);
                    redirect($url);
                }
            }
        }


        // if lang is in session but not in the url we add it and redirect -- temp we comment it out!

//        if(!empty($_SESSION['userLang']) && !getG('lang')) {
//            $_GET['lang'] = $_SESSION['userLang'];
//
//            $url = strtok($_SERVER["REQUEST_URI"], '?') . '?'. http_build_query($_GET);
//
//            redirect($url);
//        }

        // set in session user preferred language if GET contains parameter - lang

//        var_dump(221);
//        exit;

        if (getG('lang')) {

            if (checkLangEnabled(getG('lang'))) {
                $_SESSION['userLang'] = trim(mres(getG('lang')));

                $this->selectedLang = $_SESSION['userLang'];

                if (isset($_SESSION['user']['id']) && getG('lang') != $_SESSION['user']['lang']) {
                    $_SESSION['user']['lang'] = getG('lang');
                    updateUserProfileLang(getG('lang'));
                }
            } else {
                $lang = getG('lang');
                $url =
                    str_replace('lang=' . $lang, 'lang=' . getDefaultLang(), $_SERVER['REQUEST_URI']);

                if (!empty(getG('preferredLangs'))) {
                    $preferredLang = getG('preferredLangs');
                    $isAllowedLang = checkLangEnabled($preferredLang);
                    if (!$isAllowedLang) {
                        $url =
                            str_replace('preferredLangs=' . $preferredLang, 'preferredLangs=' . getDefaultLang(), $url);
                    }
                }
                redirect($url);
            }
        }

        // pa session request -- redirect from dc


        $strToLog = PHP_EOL . date('Y-m-d H:i:s') . PHP_EOL;
        $strToLog .= 'LOG STRING' . PHP_EOL;
        $strToLog .= print_r($allowedTypes, true) . PHP_EOL;
        
        if(getG('getSess') && getG('backUrl')) {

            $ipRequest = trim(mres(getG('getSess')));
            $backUrl = trim(mres(getG('backUrl')));

            $strToLog .= 'getSess => ' .  getG('getSess') .PHP_EOL;
            $strToLog .= 'backUrl => ' .  getG('backUrl') .PHP_EOL;

            if(!empty($ipRequest)) {

                $strToLog .= '$ipRequestDecoded => ' .  base64_decode($ipRequest) .PHP_EOL;
                $strToLog .= 'Server Remote ADDR => ' .  $_SERVER['REMOTE_ADDR'] .PHP_EOL;

                if(base64_encode($_SERVER['REMOTE_ADDR']) === $ipRequest || (isset($_GET['skipIpCheck']) === true && DEBUG === true))  {
                    $strToLog .= 'skipIpCheck ? => ' .  isset($_GET['skipIpCheck']) .PHP_EOL;
                    $strToLog .= 'DEBUG true ? => ' .  DEBUG.PHP_EOL;

                    // get and parse referer
                    $ref = parse_url($backUrl);

                    $strToLog .= 'Parse $backUrl  => ' .  print_r($ref, true) . PHP_EOL;

                    $dcUrls = $this->cfg->get('dcAllowedUrls');

                    $strToLog .= 'Allowed DC url ?  => ' .  print_r($dcUrls, true) . PHP_EOL;

                    if(is_array($dcUrls) && in_array($ref['host'], $dcUrls)) {

                        // get session id
                        $sId = session_id();

                        $strToLog .= 'Session id    --->>  => ' .  $sId . PHP_EOL;

                        // generate and save session secret key
                        $key = generateRandomString(12);

                        $strToLog .= 'Session KEY   => ' .  $key . PHP_EOL;

                        $sessExistsDbQuery = "SELECT * FROM mod_users_sessions WHERE session_id = '" . $sId . "'";
                        $sessExistsQuery = new query($this->cfg->db, $sessExistsDbQuery);

                        if($sessExistsQuery->num_rows() < 1) {

                            $strToLog .= 'New user session, fill data in mod_user_session    ' . PHP_EOL;

                            $sessData = array(
                                'user_id' => '0',
                                'session_id' => $sId,
                                'ip_address' => $_SERVER['REMOTE_ADDR'],
                                'session_key' => $key,
                                'is_canceled' => '1',
                                'cancelation_reason' => '1',
                            );

                            $strToLog .= 'New filled session data =>   ' . print_r($sessData, true) . PHP_EOL;

                            saveValuesInDb('mod_users_sessions', $sessData);

                        } else {
                            $strToLog .= 'Existing Session update  =>   ' .'New key: ' . $key .'New Remote_ADR: ' . $_SERVER['REMOTE_ADDR'] .'New sId: ' . $sId . PHP_EOL;

                            $sessDbQuery = "UPDATE mod_users_sessions 
                                            SET 
                                                session_key = '" . $key . "',
                                                ip_address = '" . $_SERVER['REMOTE_ADDR'] . "' 
                                            WHERE session_id = '" . $sId . "'";
                            doQuery($this->cfg->db, $sessDbQuery);
                        }

                        // construct return url and redirect

                        $strToLog .= 'Create Redirect to =>   ' . $backUrl . '?s=' . $sId . '&k=' . $key . PHP_EOL;

                        $strToLog .= '**************' . PHP_EOL . PHP_EOL . PHP_EOL;

                        if (defined('ENABLE_LOGS_SSO_API') && ENABLE_LOGS_SSO_API === true)
                        {
                            $this->logger->log('BeforeRouteEnterClassLogging.txt','Result =>    ' . $strToLog);
                        }

                        redirect($backUrl . '?s=' . $sId . '&k=' . $key);
                        exit;
                    }
                }

                $result = 'Redirect did not happened because: ';
                if (base64_encode($_SERVER['REMOTE_ADDR']) !== $ipRequest ){
                    $result .= 'REMOTE_ADDR != $ipRequest';
                } elseif (isset($_GET['skipIpCheck']) === false ){
                    $result .= 'Was not skipIpCheck in get param';
                } elseif (DEBUG === true){
                    $result .= 'DEBUG is false ';
                } else {
                    $result .= 'Other reason why redirect did not happened';
                }

                $strToLog .= 'Redirect was not succesfull =>  ' . $result . PHP_EOL;
                $strToLog .= '**************' . PHP_EOL . PHP_EOL . PHP_EOL;
                if (defined('ENABLE_LOGS_SSO_API') && ENABLE_LOGS_SSO_API === true)
                {
                    $this->logger->log('BeforeRouteEnterClassLogging.txt','Result =>    ' . $strToLog);
                }
                exit;

            }
            $strToLog .= 'Redirect to HTTP/1.0 404 Not Found'. PHP_EOL;
            $strToLog .= '**************' . PHP_EOL . PHP_EOL . PHP_EOL;
            if (defined('ENABLE_LOGS_SSO_API') && ENABLE_LOGS_SSO_API === true)
            {
                $this->logger->log('BeforeRouteEnterClassLogging.txt','Result =>    ' . $strToLog);
            }
            header('HTTP/1.0 404 Not Found', true, 404);
            exit;
        }


        // check for Patient Appointment to another doctor
        // (TMP functionality for DC-appointments that allows doctor to make an appointment of patient to another doctor)

        if(getG('piearsta_patient_user_id') && getG('request_access_token')) {

            // check if patient user exists

            $dbQuery = "SELECT * FROM mod_profiles WHERE id = " . mres(getG('piearsta_patient_user_id'));
            $query = new query($this->cfg->db, $dbQuery);

            if($query->num_rows()) {
                $user = $query->getrow();
            } else {
                //redirect(strtok($_SERVER["REQUEST_URI"], '?'));
                throw new Exception('Patient profile not found');
            }

            // check if token is correct (consists of patient id, underscore and secret salt, hashed by sha256 algorithm)

            $salt = $this->cfg->get('dcAppointmentSalt');
            $constructedToken = hash('sha256', $user['id'] . '_' . $salt);

            if($constructedToken != trim(mres(getG('request_access_token')))) {
                throw new Exception('Wrong access token');
            }

            // if everithing is ok, we save special param to session

            $_SESSION['appointmentInTheNameOfPatient'] = $user['id'];

            // and redirect to url without params (except lang)

            redirect('/' . $this->selectedLang . strtok($_SERVER["REQUEST_URI"], '?'));
//            redirect(strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }



        // check for DigitalClinic redirect

        if(getG('source') && getG('source') == 'dc' && getG('schedule_id')) {

            // if user logged in

            if(isset($_SESSION['user']) && isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {

                // validate and save query string params to session

                $continue = true;
                $_SESSION['dc'] = true;
                $errorMsg = '';

                $schedId = intval(mres(getG('schedule_id')));

                if($schedId) {
                    $_SESSION['dcScheduleId'] = $schedId;
                } else {
                    $errorMsg .= 'Missing param: schedule_id. ';
                    $continue = false;
                }

                $servId = intval(mres(getG('service_id')));

                if($servId) {
                    if($continue) {
                        $_SESSION['dcServiceId'] = $servId;
                    }
                }

                // we could obtain services as a comma delimited list of ids
                // In the case when user goes thru the doctor tab, we pass all the services
                // from this particular slot

                $servList = explode(',', mres(getG('service_list')));

                if($servList && is_array($servList)) {
                    if($continue) {
                        $_SESSION['dcServicesList'] = implode(',', $servList);
                    }
                }

                // channelType Not required
                if(getG('channelType') && is_string(trim(mres(getG('channelType')))) && trim(mres(getG('channelType'))) != '') {
                    if($continue) {
                        $_SESSION['dcChannelType'] = trim(mres(getG('channelType')));
                    }
                }

                // entityName Not required
                if(getG('entityName') && is_string(trim(mres(getG('entityName')))) && trim(mres(getG('entityName'))) != '') {
                    if($continue) {
                        $_SESSION['dcEntityName'] = trim(mres(getG('entityName')));
                    }
                }

                if(getG('kid') && (intval(mres(getG('kid'))) === 0 || intval(mres(getG('kid'))) === 1)) {
                    if($continue) {
                        $_SESSION['dcKid'] = intval(mres(getG('kid'))); // 1,0, not passed (default 0)
                    }
                } else {
                    $_SESSION['dcKid'] = 0; // if not passed we set default -- for adults ( 0 )
                }

                if(
                    getG('consultation_type') &&
                    is_string(trim(mres(getG('consultation_type')))) &&
                    trim(mres(getG('consultation_type'))) != '' &&
                    in_array(trim(mres(getG('consultation_type'))), $allowedTypes)
                ) {
                    if($continue) {
                        $_SESSION['dcConsultationType'] = trim(mres(getG('consultation_type'))); // should be one of the allowed values
                    }
                } else {
                    $errorMsg .= 'Missing or wrong param: consultation_type. ';
                    $continue = false;
                }

                if(getG('duration') && intval(mres(getG('duration'))) > 0) {
                    if($continue) {
                        $_SESSION['dcDuration'] = intval(mres(getG('duration'))); // int > 0
                    }
                } else {
                    $errorMsg .= 'Missing or wrong param: duration. ';
                    $continue = false;
                }

                // only if type is phone call we validate phone param

                if(
                    $_SESSION['dcConsultationType'] == 'phone-call'
                ) {

                    if( $continue &&
                        getG('phone') &&
                        is_string(trim(mres(getG('phone')))) &&
                        strlen(trim(mres(getG('phone')))) >= 8
                    ) {
                        $_SESSION['dcPhone'] = trim(mres(getG('phone'))); // string, patient's phone number
                    } else {
                        $errorMsg .= 'Missing or wrong param: phone. ';
                        $continue = false;
                    }
                }

                $langReceived = getG('preferredLangs');
                $validLangs = null;

                if($langReceived) {
                    $langReceived = explode(',', trim(mres(getG('preferredLangs'))));

                    $validLangs = array_intersect($langReceived, $allowedLanguages);

                    if (empty($validLangs)){
                        $validLangs = array_intersect(explode(',', getDefaultLang()), $allowedLanguages);
                    }
                }

                if(
                    is_array($validLangs) &&
                    count($validLangs) > 0
                ) {
                    if($continue) {
                        $_SESSION['dcPrefferedLangs'] = implode(',', $validLangs); // comma separated list of valid languages
                    }
                } else {
                    $errorMsg .= 'Missing or wrong param: preferedLangs. ';
                    $continue = false;
                }

                $_SESSION['dcLang'] = $this->selectedLang;

                // if something wrong we throw an exception with error message

                if(!$continue) {
                    throw new Exception('DC appointment link error: ' . $errorMsg);
                }

                // go to requested url without query string params since we already have them in session


                $url = '/' . $this->selectedLang . strtok($_SERVER["REQUEST_URI"], '?');

                redirect($url);
                exit;

            } else {

                // if not logged -- we redirect to login page with url = current url as param

                $loginUrl = '/' . $this->selectedLang . '/autorizacija/?url=' . urlencode($_SERVER["REQUEST_URI"]);

//                if(!empty($_SESSION['userLang'])) {
//                    $loginUrl .= '&lang=' . $_SESSION['userLang'];
//                }

                redirect($loginUrl);
                exit;
            }
        }

        if(getG('finish_res')) {

            // if user logged in

            if(isset($_SESSION['user']) && isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {

                $resId = trim(mres(getG('finish_res')));

                if(!empty($resId)) {
                    $_SESSION['finish_res'] = $resId;
                }

                // go to requested url without query string params since we already have them in session

                $url = '/' . $this->selectedLang . strtok($_SERVER["REQUEST_URI"], '?');

                redirect($url);
                exit;

            } else {

                // if not logged -- we redirect to login page with url = current url as param

                $loginUrl = '/' . $this->selectedLang . '/autorizacija/?url=' . urlencode($_SERVER["REQUEST_URI"]);

//                if(!empty($_SESSION['userLang'])) {
//                    $loginUrl .= '&lang=' . $_SESSION['userLang'];
//                }

                redirect($loginUrl);
                exit;
            }
        }

        if(getG('find')) {

            $find = mres(getG('find'));

            if($find) {
                $_SESSION['find'] = $find;
            }

            redirect('/' . $this->selectedLang . strtok($_SERVER["REQUEST_URI"], '?'));
//            redirect(strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }



        // Call this only if user logged in

        if(isset($_SESSION['user']) && isset($_SESSION['user']['id']) && $_SESSION['user']['id']) {

            // Check user agreement accept and profile data confirm
            checkProfileActivation();
            checkUserAgreements();

            // if user enters any route, except paymentResultRoutes we unset PaymentInfo from session
            if(isset($_SESSION['PaymentInfo'])) {

                /** @var config $cfg */
                $cfg = loadLibClass('config');

                $paymentUrls = $cfg->get('payment_request_uri');

                if (getG('order_reference') && getG('payment_reference')) {

                    if (!empty($_SESSION['PaymentInfo']['payment_reference']) ){
                        if ($_SESSION['PaymentInfo']['payment_reference'] == getG('payment_reference')){

                            $this->billingSystem->setEveryPayConfig();

                            $result = $this->billingSystem->requestEveryPay('get_payment_methods');
                            $paymentMethods = $this->getPaymentMethods($result);

                            $this->billingSystem->setPaymentReference(getG('payment_reference'));
                            $result = $this->billingSystem->requestEveryPay('payments_check');
                            $response = json_decode($result['result'], true);

                            $_SESSION['PaymentInfo']['response'] = $response;

                            if (!empty($paymentMethods) && !empty($response['payment_method'])){
                                if (!empty($paymentMethods[$response['payment_method']])){
                                    $prefix = '';
                                    if ($response['payment_method'] == 'card'){
                                        $prefix = 'everyPay/';
                                    }
                                    $_SESSION['PaymentInfo']['paid_by'] = $prefix . $paymentMethods[$response['payment_method']];
                                }
                            }

                            $billingSystem = $cfg->get('billing_system')['everyPay'];

                            // payments_check response processing

                            if ($result['success']){

                                if (
                                    !empty($response['payment_state']) &&
                                    $_SESSION['PaymentInfo']['payment_reference'] == $response['payment_reference']
                                ){

                                    // This is fo testing purposes ,to test if we receive that payment status is sent_for_processing
                                    if ($cfg->get('test_payment_state')){
                                        $response['payment_state'] = 'sent_for_processing';
                                    }

                                    $paymentsUnfinishedStatuses = $billingSystem['payment_unfinished_statuses'];

                                    $_SESSION['PaymentInfo']['payment_status'] = $response['payment_state'];

                                    if (!empty($response['cc_details']['last_four_digits'])){
                                        $_SESSION['PaymentInfo']['last_four_digits'] = $response['cc_details']['last_four_digits'];
                                    }

                                    if (!empty($response['stan'])){
                                        $_SESSION['PaymentInfo']['stan'] = $response['stan'];
                                    }

                                    if ($response['payment_state'] == 'settled'){

                                        // if we've got settled state -- this is successful payment and here we redirect to p-success page
//
                                        redirect('/'. $_SESSION['PaymentInfo']['lastWebLang'] . $paymentUrls['successUri']);
                                        exit();

                                    } elseif ($response['payment_state'] == 'sent_for_processing') {

                                        // we-ve got sent_for_processing state, so redirect to p-in-process
                                        
                                        redirect('/'. $_SESSION['PaymentInfo']['lastWebLang'] .  $paymentUrls['paymentInProcessUri']);
                                        exit();

                                    } elseif (in_array($response['payment_state'], $paymentsUnfinishedStatuses)){

                                        //
                                        // This is impossible situation I guess
                                        // everyPay doesn't return with unfinished states except of 'sent_for_processing' state


                                    }

                                }
                            }

                            // Payment unsuccessful

                            // check for error messages

                            if (!empty($response['processing_error'])) {

                                $error = $response['processing_error'];
                                $_SESSION['paymentError']['code'] = !empty($error['code']) ? $error['code'] : '';
                                $_SESSION['paymentError']['message'] = !empty($error['message']) ? $error['message'] : '';

                                if (!empty($error['message'])){

                                    $errorCanceledByUser = $billingSystem['error_messages']['cancelled_by_user'];

                                    if (in_array($error['message'], $errorCanceledByUser)){

                                        // we've got one of the cancelled_by_user messages, so we redirect to backUri

                                        redirect('/'. $_SESSION['PaymentInfo']['lastWebLang'] . $paymentUrls['backUri']);
                                        exit();
                                    }
                                }
                            }

                            // if this payment wasn't cancelled by user but unsuccessful for another reason
                            // we redirect to p-fail page

                            redirect('/'. $_SESSION['PaymentInfo']['lastWebLang'] . $paymentUrls['failUri']);
                            exit();
                        }
                    }
                }

                if ($_SESSION['PaymentInfo']['paymentMethod'] == 'everyPay' &&
                    $_SESSION['PaymentInfo']['backUrl'] == "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"
                ){
                    redirect('/'. $_SESSION['PaymentInfo']['lastWebLang'] . $paymentUrls['backUri']);
                    exit();
                }

                $urlsArr = array(
                    $paymentUrls['backUri'],
                    $paymentUrls['successUri'],
                    $paymentUrls['failUri'],
                    $paymentUrls['paymentInProcessUri']
                );

                $requestUrl = explode('/', $_SERVER["REQUEST_URI"]);

                $requestUrlWithoutLangParam = $_SERVER["REQUEST_URI"];
                if (isset($requestUrl[2]) && !empty($requestUrl[2])){
                    if (checkLangEnabled($requestUrl[1])){
                        $requestUrlWithoutLangParam = '/' . $requestUrl[2] . '/';
                    }
                }

                if (in_array(strtok($_SERVER["REQUEST_URI"], '?'), $urlsArr)){
                    redirect('/'. $_SESSION['PaymentInfo']['lastWebLang'] . $_SERVER["REQUEST_URI"]);
                    exit();
                }
                if (!in_array(strtok($requestUrlWithoutLangParam, '?'), $urlsArr)) {
                    $_SESSION['orderId'] = $_SESSION['PaymentInfo']['orderId'];
                    unset($_SESSION['PaymentInfo']);
                }
            }


            // VROOM processing

            // If user is logged and has valid vroomid then redirect

            $vroomId = trim(mres(getG('vroomid')));

            if ( ! empty($vroomId)) {

                /** @var consultation $consLib */
                $consLib = loadLibClass('consultation');
                $redirectUrlResult = $consLib->checkVroomIdAndGetRedirectUrl($vroomId, $_SESSION['user']['id']);

                if ( ! empty($redirectUrlResult['redirectUrl'])) {

                    /** @var Vroom $vroomObj */
                    $vroomObj = loadLibClass('vroom');
                    $sessRes = $vroomObj->sendSession();

                    $sessId = session_id();

                    // we add sessId to url
                    $vroomUrl = $redirectUrlResult['redirectUrl'] . '?s=' . $sessId;

                    $lang = !empty($_SESSION['user']['lang']) ? $_SESSION['user']['lang'] : getDefaultLang();

                    if(!empty($_SESSION['userLang'])) {
                        $vroomUrl .= '&lang=' . $_SESSION['userLang'];
                    } else {
                        $vroomUrl .= '&lang=' . $lang;
                    }

                    redirect($vroomUrl);
                    exit();
                }
            }

            return true;
        }
    }

    /**
     * @param $result
     * @return array
     */
    private function getPaymentMethods($result)
    {
        $paymentMethods = [];

        if ($result['success']){
            $response = json_decode($result['result'], true);
            if (!empty($response['payment_methods'])){
                foreach ($response['payment_methods'] as $method){
                    $paymentMethods[$method['source']] = $method['display_name'];
                }
            }
        }
        return $paymentMethods;
    }
}

?>