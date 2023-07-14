<?php

use Jumbojett\OpenIDConnectClient;

class profileData extends Module
{

    protected $config = array(
        'couponsFolder' => 'profile/coupons/',
        'reservationFolder' => 'profile/reservations/',
        'reservation_states' => array(
            0 => 'profile_reservation_status_0', // Gaida apstiprinajumu
            4 => 'profile_reservation_status_4', // Arhiva
            1 => 'profile_reservation_status_1', // Noraidits
            3 => 'profile_reservation_status_3', // Atcelts
            9 => 'form_select_all',
        ),
        'consultation_states' => array(
            9 => 'form_select_all',
            0 => 'profile_reservation_status_0', // Gaida apstiprinajumu
            1 => 'profile_reservation_status_1', // Noraidits
            4 => 'profile_reservation_status_4', // Arhiva
        ),
        'order_states' => array(
//            0 => 'profile_order_status_0',
            1 => 'profile_order_status_1',
            2 => 'profile_order_status_2',
//            3 => 'profile_order_status_3',
            4 => 'profile_order_status_4',
//            5 => 'profile_order_status_5',
//            6 => 'profile_order_status_6',
        ),
        'months' => array(
            '1' => "month_January",
            '2' => "month_February",
            '3' => "month_March",
            '4' => "month_April",
            '5' => "month_May",
            '6' => "month_June",
            '7' => "month_July",
            '8' => "month_August",
            '9' => "month_September",
            '10' => "month_October",
            '11' => "month_November",
            '12' => "month_December",
        )
    );

    public $defaultAction = false;

    protected $sals = '!Y#@dbfaidsj;1e;288(!JKhdshda;aasd9@@!aad#joaasfg..1324y';

    /** @var null | SessionManager */
    private $sessionManager = null;

    /** @var null | string */
    private $sessionFailPopup = null;

    /** @var billingSystem */
    private $billingSystem;

    /** @var null | order */
    public $order = null;

    /** @var null | reservation */
    public $reservation = null;

    /** @var null | transaction */
    public $transaction = null;

    /** @var null | lockRecord */
    public $lockRecord = null;

    /** @var null | dcSubscription */
    public $dcSubscription = null;

    /** @var null | insurance */
    public $insurance = null;

    /** @var Tfa $tfa  */
    public $tfa;

    /** @var tmpl */
    public $tpl;

    public $dbTable;

    private $return = [];
    private $cl = false;

    /** @var bool | array */
    private $userData = false;
    private $userId = false;
    private $fieldsArray = array(
        'arstiemForm' => array(
            'full_name' => array(
                'required' => true,
            ),
            'a_email' => array(
                'required' => true,
                'errors' => array('checkEmail'),
            ),
            'phone' => array(
                'required' => true,
                'errors' => array('checkPhoneNumber'),
            ),
            'captcha_code' => array(
                'required' => true,
                'errors' => array('checkCaptcha'),
            ),
        ),
        'piesakiArstuForm' => array(
            'doctor_name' => array(
                'required' => true,
            ),
            'specialty' => array(
                'required' => true,
            ),
        ),
        'register' => array(
            'email' => array(
                'required' => true,
                'errors' => array('checkEmail', 'isUniqEmail'),
            ),
            'name' => array(
                'required' => true,
            ),
            'surname' => array(
                'required' => true,
            ),
            'person_id' => array(
                'required' => true,
                'errors' => array('checkPersCode', 'isUniqPersonId'),
            ),
            'country' => array(
                'required' => true,
                'errors' => array('checkCountryString'),
            ),
            'gender' => array(
                'required' => true,
            ),
            'phone' => array(
                'required' => true,
                'errors' => array('checkPhoneNumber'),
            ),
            'password' => array(
                'required' => true,
                'errors' => array('checkPass'),
            ),
            'password2' => array(
                'required' => true,
                'errors' => array('checkPassConfirm'),
            ),
            'newsletter' => array(
                'required' => true,
            ),
        ),
        'edit' => array(
            'email' => array(
                'required' => true,
                'errors' => array('checkEmail', 'isUniqEmail'),
            ),
            'name' => array(
                'required' => true,
            ),
            'surname' => array(
                'required' => true,
            ),
            'person_id' => array(
                'required' => true,
                'errors' => array('checkPersCode', 'isUniqPersonId'),
            ),
            'country' => array(
                'required' => true,
                'errors' => array('checkCountryString'),
            ),
            'gender' => array(
                'required' => true,
            ),
            'phone' => array(
                'required' => true,
                'errors' => array('checkPhoneNumber'),
            ),
        ),
        'add_person' => array(
            'name' => array(
                'required' => true,
            ),
            'surname' => array(
                'required' => true,
            ),
            'person_id' => array(
                'required' => true,
                'errors' => array('checkPersCode'),
            ),
            'gender' => array(
                'required' => true,
            ),
            'phone' => array(
                'required' => true,
                'errors' => array('checkPhoneNumber'),
            ),
        ),
        'perform_payment' => array(
            'order-agree' => array(
                'required' => true,
            ),
            'method' => array(
                'required' => true,
            ),
        ),
    );

    /** @var array|null  */
    private $subscription = null;


    /**
     * Class constructor
     */
    public function __construct()
    {

        parent:: __construct();

        $this->name = 'profile';
        $this->loadConfig();
        $this->cl = loadLibClass('cl');
        $this->dbTable = $this->cfg->getDbTable('profiles', 'self');
        $this->sessionManager = &loadLibClass('sessionManager');
        $this->dcSubscription = &loadLibClass('dcSubscription');
        $this->insurance = &loadLibClass('insurance');

        if(!empty($_SESSION['user']) && !empty($_SESSION['user']['dcSubscription'])) {

            if(!empty($_SESSION['user']['dcSubscription']['product_clinic'])) {

                $this->subscription = array(
                    'clinicId' => $_SESSION['user']['dcSubscription']['product_clinic'],
                    'network' => null,
                );

            } elseif (!empty($_SESSION['user']['dcSubscription']['product_network'])) {

                $this->subscription = array(
                    'clinicId' => null,
                    'network' => $_SESSION['user']['dcSubscription']['product_network'],
                );
            }
        }

        if (isset($_SESSION['profileDisabled']) && $_SESSION['profileDisabled']) {
            unset($_SESSION['profileDisabled']);
            $this->logout(true, gL('profile_session_profile_disabled', 'Your account has been disabled.'));
        }

        if ($this->cfg->get('profileVerificationEnabled') == false) {
            unset($this->fieldsArray['register']['country']);
            unset($this->fieldsArray['edit']['country']);
        }

        $this->billingSystem = loadLibClass('billingSystem');

        // instantiate service classes for order if orderId passed in post, get or in session
        $orderId = null;

        /** @var  tfa */
        $this->tfa = loadLibClass('tfa');

        if (isset($_SESSION['PaymentInfo']) && isset($_SESSION['PaymentInfo']['orderId'])) {
            $orderId = $_SESSION['PaymentInfo']['orderId'];
        } elseif (getP('orderId')) {
            $orderId = getP('orderId');
        } elseif (getG('orderId')) {
            $orderId = getG('orderId');
        }

        if ($orderId) {
            $this->order = loadLibClass('order');
            $this->order->setOrder($orderId);
            $orderData = $this->order->getOrder();

            $this->reservation = loadLibClass('reservation');
            $this->reservation->setReservation($orderData['reservation_id']);

            $this->lockRecord = loadLibClass('lockRecord');
            $this->lockRecord->setLockRecordByOrderId($orderId);

            $this->transaction = loadLibClass('transaction');
            $this->transaction->setTransactionByOrderId($orderId);
        }

        // registration may be free and may have no order, transaction and so on
        if (!$this->reservation && getP('reservationId')) {
            $this->reservation = loadLibClass('reservation');
            $this->reservation->setReservation(getP('reservationId'));

            $this->lockRecord = loadLibClass('lockRecord');
            $this->lockRecord->setLockRecordByReservationId(getP('reservationId'));
        }

        // lock record may be created, but reservation and order still not
        if (!$this->lockRecord && getP('lockId')) {
            $this->lockRecord = loadLibClass('lockRecord');
            $this->lockRecord->setLockRecord(getP('lockId'));
        }
        $this->allowed_clinics = $this->setAllowedClinics();
    }

    // TODO: To allow users to choose preferred web language (for web content, e-mails etc), need to add language choice in user profile or when new user is registering

    private function loadConfig()
    {
        $this->setPData($this->config, 'profileConfig');
    }

    public function getReturn()
    {
        return $this->return;
    }

    public function generateUserHash($pid, $pnum)
    {
        return md5(time() . $pid . $pnum);
    }

    public function saveUser()
    {
        $dbData = array();
        $dbData['name'] = getP('fields/name');
        $dbData['surname'] = getP('fields/surname');
        $dbData['email'] = getP('fields/email');
        $dbData['phone'] = getP('fields/phone');
        $dbData['password'] = md5(getP('fields/password') . $this->sals);
        $dbData['passwordLastChanged'] = date('Y-m-d H:i:s', time());
        $dbData['resident'] = getP('fields/resident') ? '1' : '0';
        $dbData['gender'] = getP('fields/gender');
        $dbData['created'] = time();
        $dbData['updated'] = time();
        $dbData['lang'] = getP('fields/userSelectedLang');
        $dbData['enable'] = 1;
        $dbData['hash_confirm'] = $hash = $this->generateUserHash(getP('fields/person_id'), getP('fields/person_number'));
        $dbData['agree_terms'] = time();
        $dbData['confirm_personal_data'] = time();

        if(getP('fields/isDmssReg') == '1') {
            $dbData['verified_at'] = time();
            $dbData['verification_method'] = getP('fields/dmssRegMethod');
        }

        if (getP("fields/bd_year") && getP("fields/bd_month") && getP("fields/bd_date")) {
            $dbData['date_of_birth'] = getP("fields/bd_year") . "-" . str_pad(getP("fields/bd_month"), 2, "0", STR_PAD_LEFT) . "-" . getP("fields/bd_date");
        }
        if (getP('fields/resident') == 1) {
            $dbData['person_id'] = getP('fields/person_id');
            $dbData['person_number'] = null;
        } else {
            $dbData['person_id'] = null;
            $dbData['person_number'] = getP('fields/person_number');
        }

        if ($dbData['resident'] == '1') {

            $dbData['country'] = 'LV';

        } else {

            if ($this->cfg->get('profileVerificationEnabled') == true) {
                $dbData['country'] = substr(getP('fields/country'), -3, 2);
            } else {
                $dbData['country'] = 'null';
            }
        }

        $id = saveValuesInDb($this->dbTable, $dbData);

        if ($id) {

            if (getP('fields/newsletter') === 'Y') {
                $dbData = array();

                $dbData[] = " `email` = '" . mres(getP('fields/email')) . "' ";
                $dbData[] = " `created` = '" . time() . "' ";
                $dbData[] = " `lang` = '" . $this->getLang() . "' ";

                $dbQuery = "INSERT INTO `mod_newsletter` SET " . implode(',', $dbData) .
                    "ON DUPLICATE KEY UPDATE " . implode(',', $dbData);
                doQuery($this->db, $dbQuery);
            }

            $res = $this->loginUser(getP('fields/email'), getP('fields/password'));

            if ($res['success']) {
                $this->sendRegistrationEmail(getP('fields/email'), $hash, $dbData['lang']);
                $_SESSION['useraction']['registration'] = true;
                return $id;
            } else {
                return false;
            }
        }

        return false;
    }

    public function sendRegistrationEmail($email, $hash, $lang='')
    {

        $url = 'http://' . $_SERVER['HTTP_HOST'] . getLM($this->cfg->getData('mirros_default_profile_page'), '', $lang) . '?hash=' . $hash . '&email=' . $email;

        if (!empty($_SESSION['dcReturn'])) {
            $url .= '&dcReturn=' . urlencode($_SESSION['dcReturn']);
            unset($_SESSION['dcReturn']);
        }

        $body = getTranslation($this->cfg, 'mailBodyWS', $lang);
        $body = str_replace(
            array('{{activization_link}}'),
            array($url),
            $body
        );

        $this->addMessage(getTranslation($this->cfg, 'mailSubjWS', $lang), $body);
        sendMail($email, getTranslation($this->cfg, 'mailSubjWS', $lang), $body, array(), getTranslation($this->cfg, 'mailFromWS', $lang), true);

    }

    public function resendActivationLink()
    {

        $this->userData['hash_confirm'] = $this->generateUserHash($this->userData['person_id'], $this->userData['person_number']);
        $dbQuery = "UPDATE " . $this->dbTable . " 
                    SET
                        `hash_confirm` = '" . $this->userData['hash_confirm'] . "',
                        `updated` = '" . time() . "' 
                    WHERE email = '" . $this->userData['email'] . "'";
        doQuery($this->db, $dbQuery);
        $this->sendRegistrationEmail($this->userData['email'], $this->userData['hash_confirm'], $this->userData['lang']);
    }

    public function activateProfile($email, $hash)
    {
        $dbQuery = "SELECT id
							FROM `" . $this->dbTable . "`
							WHERE 1
								AND `hash_confirm` = '" . mres($hash) . "'
								AND `email` = '" . mres($email) . "'
							LIMIT 1";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows() > 0) {

            $dbData = array();
            $dbData['hash_confirm'] = '';

            saveValuesInDb($this->dbTable, $dbData, $query->getOne());
        }

        unset($_GET['email']);
        unset($_GET['hash']);
        unset($_SESSION['activation_link']);
        unset($_SESSION['profileActivationRequired']);

        $_SESSION['justActivated'] = true;
    }

    public function getMessages()
    {
        $this->setPData($this->userData, 'userData');
    }

    public function getUnreadMessagesCount()
    {
        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND clinic_id in (" . $this->allowed_clinics . ")";
        }
        $dbQuery = "SELECT COUNT(`id`) as unread
							FROM `" . $this->cfg->getDbTable('profiles', 'messages') . "`
							WHERE 1
							" . $clinicIdFilter . "
								AND `profile_id` = '" . mres($this->userId) . "'
								AND `readed` = 0";
        $query = new query($this->db, $dbQuery);

        $this->userData['unreadMessages'] = $query->getrow()['unread'];

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['unreadMessages'] = $this->userData['unreadMessages'];
        }
    }

    public function getReservationsCount()
    {

        $where = $this->getReservationsWhereClause(false, NULl, NULL, true);

        $dbQuery = "SELECT COUNT(r.id) AS resCount
                FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r
                WHERE " . $where;

        $query = new query($this->db, $dbQuery);

        $this->userData['reservationsCount'] = $query->getrow()['resCount'];

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['reservationsCount'] = $this->userData['reservationsCount'];
        }

        return $this->userData['reservationsCount'];
    }

    public function getConsultationsCount()
    {

        $where = $this->getReservationsWhereClause(true, NULL, NULL, true);

        $daysBefore = $this->cfg->get('showConsultationsDaysBefore');
        $dateBefore = date('Y-m-d H:i:s', time() - ($daysBefore * 24 * 60 * 60));

        $dbQuery = "SELECT COUNT(r.id) AS consCount
                FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r
                WHERE " . $where . " AND `created` > '" . $dateBefore . "'";

        $query = new query($this->db, $dbQuery);

        $this->userData['consultationsCount'] = $query->getrow()['consCount'];

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['consultationsCount'] = $this->userData['consultationsCount'];
        }

        return $this->userData['consultationsCount'];
    }

    // We should to collect user related data in separate method

    /**
     * @param $userId
     * @return bool
     */
    public function collectUserData($userId)
    {

        $row = getUserData($userId, $this->getLang());

        if (!empty($row)) {

            $row['country_string'] = $this->getCountryByCode($row['country']);

            $row['isVerifyable'] = $this->isVerifiable($row['country']);

            $verify = isProfileVerified($row);
            $row['verified'] = $verify['verified'];
            $row['verificationExpired'] = $verify['expired'];

            $dmssCfg = $this->cfg->get('dmss');
            $verificationMethods = $dmssCfg['methods'];

            $row['verifiedBy'] = $verificationMethods[$row['verification_method']];

            // get dcSubscription info
            $row['dcSubscription'] = $this->dcSubscription->init($userId);

            $this->userData = $_SESSION['user'] = $row;

            $this->userData['pc'] = explode('-', $this->userData['person_id']);

            if ($this->userData['date_of_birth']) {
                $this->userData['date_of_birth_splited'] = explode('-', $this->userData['date_of_birth']);
            }

            if ($this->userData['hash_confirm']) {
                $this->userData['activation_link'] = 'http://' .
                    $_SERVER['HTTP_HOST'] .
                    getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang()) .
                    '?hash=' . $this->userData['hash_confirm'] .
                    '&email=' . $this->userData['email'];
                $_SESSION['profileActivationRequired'] = true;
                $_SESSION['activation_link'] = $this->userData['activation_link'];
            }

            if($this->userData['insurance_start_date'] && $this->userData['insurance_start_date'] != '0000-00-00 00:00:00') {
                $this->userData['insurance_start_date_formated'] = date('d.m.Y', strtotime($this->userData['insurance_start_date']));
            } else {
                $this->userData['insurance_start_date_formated'] = null;
            }

            if($this->userData['insurance_end_date'] && $this->userData['insurance_end_date'] != '0000-00-00 00:00:00') {
                $this->userData['insurance_end_date_formated'] = date('d.m.Y', strtotime($this->userData['insurance_end_date']));
            } else {
                $this->userData['insurance_end_date_formated'] = null;
            }

            // additional insurance data

            $nowDT = date('Y-m-d H:i:s', time());

            $this->userData['insuranceArray'] = array(
                'hasInsurance' => $this->userData['insurance_number'] && $this->userData['insurancePaId'],
                'incomplete' => !$this->userData['insurance_number'] || !$this->userData['insurancePaId'] && !empty($this->userData['insurance_start_date']) && !empty($this->userData['insurance_end_date']),
                'notStarted' => $nowDT < $this->userData['insurance_start_date'],
                'expired' => $nowDT >= $this->userData['insurance_end_date'],
            );

            $this->setUserDataMandatoryPassChangePolicy();

            $this->userId = $this->userData['id'];

            $_SESSION['user'] = $this->userData;

            // set count info
            $this->getUnreadMessagesCount();
            $this->getReservationsCount();
            $this->getConsultationsCount();

            // TODO: these coupons not used for now, but if we will use these in the future, we need to move this to separate method
//            $dbQuery = "SELECT *
//							FROM `" . $this->cfg->getDbTable('profiles', 'coupons')	 . "` p
//								LEFT JOIN `" . $this->cfg->getDbTable('coupons', 'self')	 . "` c ON (p.coupon_id = c.id)
//								LEFT JOIN `" . $this->cfg->getDbTable('coupons', 'details')	 . "` cd ON (c.id = cd.coupon_id AND cd.lang = '" . $this->getLang() . "')
//							WHERE 1
//								AND p.`profile_id` = '" . mres($this->userId) . "'";
//            $query = new query($this->db, $dbQuery);
//
//            $this->userData['coupons'] = $query->getArray('coupon_id');

            $dbQuery = "SELECT 1
							FROM `mod_newsletter`
							WHERE 1
								AND `blocked` = '0'
								AND `email` = '" . mres($this->userData['email']) . "'
							LIMIT 1";
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0) {
                $this->userData['newsletter'] = 1;
            } else {
                $this->userData['newsletter'] = 0;
            }

            $_SESSION['user'] = $this->userData;

            $this->setPData($this->userData, 'userData');

            return true;

        } else {

            return false;
        }
    }

    private function setUserDataMandatoryPassChangePolicy()
    {
        $cfg = loadLibClass('config');

        // Init result
        $result = array(
            '_continue' => true,
            '_error' => '',

            'userId' => $this->userData['id'],
            'registerDatetime' => date('Y-m-d H:i:s', $this->userData['created']),
            'passwordLastChangedDatetime' => $this->userData['passwordLastChanged'],

            'configChangePassEveryNDays' => (int)$cfg->get('patientChangePassEveryNDays'),
            'patientChangePassExistingReg' => (int)$cfg->get('patientChangePassExistingReg'),
            'patientChangePassExistingNow' => (int)$cfg->get('patientChangePassExistingNow'),
            'configChangePassWriteLogs' => (bool)$cfg->get('patientChangePassWriteLogs'),

            'needChangePassDatetime' => null,
            'needChangePass' => false,

            'registerTimestamp' => $this->userData['created'],
            'passwordLastChangedTimestamp' => ($this->userData['passwordLastChanged'] !== null)
                ? strtotime($this->userData['passwordLastChanged']) : null,
            'needChangePassTimestamp' => null,

            'baseUrl' => '',
            'requestedUrlPart' => '',
            'changePassUrlPart' => '',
        );

        if ($result['_continue'] === true
            && ($result['configChangePassEveryNDays'] <= 0 || $result['patientChangePassExistingReg'] <= 0 || $result['patientChangePassExistingNow'] <= 0)) {
            $result['_continue'] = false;
            $result['_error'] = 'Invalid config values';
        }

        // Set need change pass
        if ($result['_continue'] === true) {
            if ($result['passwordLastChangedDatetime'] !== null) {
                $result['needChangePassTimestamp'] = ($result['passwordLastChangedTimestamp'] + ($result['configChangePassEveryNDays'] * 24 * 60 * 60));
                $result['needChangePassDatetime'] = date('Y-m-d H:i:s', $result['needChangePassTimestamp']);
            } else {
                // All existing patients have password expiration date registration date + 1 year or NOW() + 90 days which dates is bigger.
                $regTimestamp = ($result['registerTimestamp'] + ($result['patientChangePassExistingReg'] * 24 * 60 * 60));
                $nowTimestamp = (time() + ($result['patientChangePassExistingNow'] * 24 * 60 * 60));
                $biggerTimestamp = ($regTimestamp > $nowTimestamp) ? $regTimestamp : $nowTimestamp;

                $result['needChangePassTimestamp'] = $biggerTimestamp;
                $result['needChangePassDatetime'] = date('Y-m-d H:i:s', $biggerTimestamp);
            }

            $result['needChangePass'] = ($result['needChangePassTimestamp'] <= time()) ? true : false;
        }

        // Set uri data
        if ($result['_continue'] === true && $result['needChangePass'] === true) {
            $result['baseUrl'] = $cfg->get('piearstaUrl');
            $result['requestedUrlPart'] = trim($_SERVER['REQUEST_URI'], '/');
            $result['changePassUrlPart'] = trim(getLM($cfg->getData('mirros_profile_change_password_page'), '', $this->getLang()), '/');
        }

        // Log
        if ($result['_continue'] === true && $result['configChangePassWriteLogs'] === true) {
            logDebug('profile.class setUserDataMandatoryPassChangePolicy(): ' . json_encode($result, JSON_PRETTY_PRINT));
        }

        // Add data to userData
        $this->userData['mandatoryPassChangePolicy'] = array(
            'needChangePass' => $result['needChangePass'],
            'changePassUrl' => $result['baseUrl'] . $result['changePassUrlPart'],
        );
    }

    public function loginUser($email = null, $password = null, $dontCrypt = false, $afterTfaCheck = false, $dmssAuth = false)
    {
        $isPasswordLogin = !empty(trim($email)) && !empty(trim($password)) && !$dmssAuth;


        if ( ((trim($email) && trim($password)) || $afterTfaCheck) || $dmssAuth) {

            $query = null;

            // if we proceed login after tfa check we omit user data query

            if(!$afterTfaCheck && !$dmssAuth) {

                $dbQuery = "SELECT p.*, t.tfa_key AS tfa
							FROM `" . $this->dbTable . "` p
							LEFT JOIN mod_tfa t ON (t.profile_id = p.id)
							WHERE 1
								AND p.`enable` = '1'
								AND p.deleted = '0'
								AND p.`email` = '" . mres($email) . "'
								AND p.`password` = '" . ($dontCrypt ? $password : md5($password . $this->sals)) . "'
							LIMIT 1";

                $query = new query($this->db, $dbQuery);
            }

            // logic for dmss login

            if($dmssAuth) {

                $pk = !empty($_SESSION['dmss_auth']['personCode']) ? $_SESSION['dmss_auth']['personCode'] : null;

                if(!empty($pk)) {

                    $dbQuery = "SELECT p.*, t.tfa_key AS tfa
							FROM `" . $this->dbTable . "` p
							LEFT JOIN mod_tfa t ON (t.profile_id = p.id)
							WHERE 1
								AND p.`enable` = '1'
								AND p.deleted = '0'
								AND (p.`person_id` = '" . mres($pk) . "' OR p.person_number = '".mres($pk)."')
							LIMIT 1";

                    $query = new query($this->db, $dbQuery);
                }
            }

            if ($afterTfaCheck || (!empty($query) && $query->num_rows() > 0) ) {

                // if we proceed after tfa check we get user data from tmp_user in session

                $row = $afterTfaCheck ? $_SESSION['tmp_user'] : $query->getrow();

                // if loginUser called after tfa check we omit session and tfa check since they already were checked

                if(!$afterTfaCheck) {

                    // recreate session with data from existing session
                    refreshSession();
                    $lifeTime = (int)$this->cfg->get('apacheSessionLifetime');
                    $_SESSION['sessionExpirationTime'] = date(PIEARSTA_DT_FORMAT, time() + $lifeTime);

                    // logged in, so try to create session record
                    $checkSession = $this->sessionManager->createSessionRecord($row['id']);

                    if (!$checkSession['success']) {

                        // something wrong...
                        // 2 cases: session limit exceeded or user profile deleted
                        // Unknown reason -- if no status returned

                        if ($checkSession['status'] == 'limit') {
                            $msg = gL('profile_session_limit_exceeded', 'You have reached max connections limit.');
                        } elseif ($checkSession['status'] == 'canceled') {
                            $msg = gL('profile_session_profile_disabled', 'Your account has been disabled.');
                        } else {
                            $stString = $checkSession['status'] ? ' Status: ' . $checkSession['status'] : '';
                            $msg = gL('profile_session_login_error', 'Can not login due to unknown error.') . $stString;
                        }

                        $this->sessionFailPopup = $this->popupMessage($msg);

                        return false;
                    };

                    if(!empty($row['tfa']) && $isPasswordLogin) {

                        $tfaCfg = $this->cfg->get('tfa');

                        if($tfaCfg['strictMode']) {

                            // set temp user object to session

                            $_SESSION['tmp_user'] = $row;
                            $_SESSION['tfa_login_attempts'] = intval($tfaCfg['maxAttempts']);

                            return array(
                                'tfaRequired' => true,
                            );

                        } else {

                            // some logic for 'soft' mode

                            // TODO:

                            // // //
                            // //
                            //


                        }
                    }
                }

                // we proceed after session and tfa check...

                $this->userId = $row['id'];

                if ($this->collectUserData($this->userId)) {

                    $this->userData['passwordLogin'] = $isPasswordLogin;
                    $_SESSION['user']['passwordLogin'] = $isPasswordLogin;

                    // This handles situation, when user was not logged in, but selected the doctor and schedule

                    if (!empty($_POST['fields']['url']) || !empty($_SESSION['loginFieldUrl']) || !empty($_SESSION['url'])) {

                        $url = getP('fields/url');

                        if(!$url && !empty($_SESSION['loginFieldUrl'])) {
                            $url = $_SESSION['loginFieldUrl'];
                            unset($_SESSION['loginFieldUrl']);
                        }

                        if(!$url && !empty($_SESSION['url'])) {
                            $url = $_SESSION['url'];
                            unset($_SESSION['url']);
                        }

                        if (!empty($_SESSION['schedule_id'])) {

                            $_SESSION['redirectTo'] = rtrim($url, '/') . '?schedule_id=' . $_SESSION['schedule_id'];

                            if (isset($_SESSION['calendarData'])) {
                                $_SESSION['redirectTo'] .= '&cdata=' . $_SESSION['calendarData'];
                            }

                        } elseif (!empty($_SESSION['cons_doctor_id']) && !empty($_SESSION['cons_clinic_id'])) {

                            $_SESSION['redirectTo'] =
                                rtrim($url, '/') .
                                '?cons_doctor_id=' . $_SESSION['cons_doctor_id'] .
                                '&cons_clinic_id=' . $_SESSION['cons_clinic_id'];

                        } else {

                            // we have url param but have no special params in session (digital clinic reservation)
                            // we parse url and obtain query string params

                            parse_str(parse_url($url)['query'], $query);

                            if (isset($query['dc']) && $query['dc'] == true) {
                                $_SESSION['dc'] = true;
                                $_SESSION['dcScheduleId'] = $query['schedule_id'];
                                $_SESSION['dcServiceId'] = $query['service_id'];
                            }
                        }
                    }

                    // Mandatory pass change policy
                    if (empty($_SESSION['redirectTo']) && $this->userData['mandatoryPassChangePolicy']['needChangePass'] === true && $isPasswordLogin) {
                        $_SESSION['redirectTo'] = getLM($this->cfg->getData('mirros_profile_change_password_page'),'',$this->getUserData('lang'));
                    }

                    if (!$this->defaultAction) {
                        if (!$this->getNoLayout() && $row['temp_password'] && $this->getCData("id") != getMirror($this->cfg->getData('mirros_profile_change_password_page'), '', $this->getLang()) && $isPasswordLogin) {
                            redirect(getLM($this->cfg->getData('mirros_profile_change_password_page'), '', $this->getLang()));
                        }

                        if (!$this->getNoLayout() && $row['hash_confirm'] && $this->getCData("id") != getMirror($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang())) {
                            redirect(getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang()));
                        }
                    }

                    // if this is DMSS login (personal identity auth) and user profile was verified
                    // we automatically set the user profile is verified at current timestamp

                    if(
                        mb_strtolower($_SESSION['dmss_auth']['firstName']) == mb_strtolower($this->userData['name']) &&
                        mb_strtolower($_SESSION['dmss_auth']['lastName']) == mb_strtolower($this->userData['surname'])
                    ) {

                        $vd = array(
                            'verified_at' => time(),
                            'verification_method' => 1,
                        );

                        saveValuesInDb('mod_profiles', $vd, $this->userId);
                    }

                    return array(
                        'success' => true,
                    );
                }
            }
        }

        if($dmssAuth) {
            $_SESSION['user_not_exists_message'] = true;
        }

        return array(
            'success' => false,
        );
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getUserData($field = '')
    {
        if ($field) {
            if (isset($this->userData[$field])) {
                return $this->userData[$field];
            } else {
                return false;
            }
        } else {
            return $this->userData;
        }
    }

    public function getPatientById($id)
    {
        if (empty($id)) {
            return null;
        }

        $dbQuery = "SELECT * FROM mod_profiles WHERE id = " . mres($id) . " LIMIT 1";
        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {
            return $query->getRow();
        }

        return null;
    }

    // We just check whether user is logged in
    // and call addCountersToMenu
    /**
     * @return bool
     */
    public function isLogged()
    {

        if (session_status() !== 2 || !getS('user')) {
            return false;
        }

        if (getS('user/email') && getS('user/password')) {

            if (isset($_SESSION['user']['id']) && isset($_SESSION['user']['email'])) {
                // init userData from session
                $this->userData = $_SESSION['user'];
                $this->userId = $_SESSION['user']['id'];

                $this->getUnreadMessagesCount();
                $this->getReservationsCount();
                $this->getConsultationsCount();

                return true;
            }
        }

        return false;
    }

    public function logout($redirect = true, $popupMessage = false)
    {

        if (isset($_SESSION['user'])) {
            // if there is no popupMessage received, this is conventional logout
            if (!$popupMessage) {
                $this->sessionManager->sessionAbort($_SESSION['user']['id'], SESSION_CANCEL_LOGOUT, session_id());
            }

            $checkManiDati = $this->cfg->get('checkManiDatiOnLogout');

            if($checkManiDati) {
                if ($this->isManiDatiAvailable(true)) {
                    @remove_session_file();
                }
            }

            unset($_SESSION['user']);
            unset($_COOKIE['PHPSESSID']);
            session_destroy();
        }
        $this->userId = null;
        $this->userData = null;

        unset($_SESSION['sessionExpirationTime']);

        // if this was dmss login...
        // we call signOut on dmss portal

        if(isset($_SESSION['dmss_auth'])) {

            unset($_SESSION['dmss_auth']);
            $redirectUrl = $this->cfg->get('piearstaUrl') . 'autorizacija/';
            redirect($redirectUrl);
            exit;
        }

        if ($popupMessage) {
            session_start();
            $_SESSION['popupMessage'] = $this->popupMessage($popupMessage);

            if ($this->getNoLayout()) {
                $this->return['location'] = '/'. $this->getLang() . '/';
                return;
            }
        }

        if ($redirect) {
            openDefaultPage();
        } else {
            $this->return['location'] = getLM($this->cfg->getData('mirros_signin_page'));
        }
    }

    public function loginForm($afterTfaCheck = false)
    {
        // if afterTfaCheck is true -- user already logged in and passed tfa check
        // so we set res success to true

        if($afterTfaCheck) {
            $res = array(
                'success' => true,
            );
        }

        if (getP('action') == 'login' || $afterTfaCheck) {

            if(!$afterTfaCheck) {

                $res = $this->loginUser(getP('fields/email'), getP('fields/password'));

                if($res['tfaRequired']) {

                    if(getP('fields/url')) {
                        $_SESSION['loginFieldUrl'] = getP('fields/url');
                    }

                    if(getP('fields/vroomId')) {
                        $_SESSION['loginFieldVroomId'] = getP('fields/url');
                    }

                    $this->return['tfaRequired'] = true;
                    return;
                }
            }

            if ($res['success']) {

                $this->return['agree_terms'] = $this->userData['agree_terms'];
                $this->return['confirm_personal_data'] = $this->userData['confirm_personal_data'];

                $vroomId = getP('fields/vroomId');

                if(!$vroomId && !empty($_SESSION['loginFieldVroomId'])) {
                    $vroomId = $_SESSION['loginFieldVroomId'];
                    unset($_SESSION['loginFieldVroomId']);
                }

                if (!empty($vroomId)) {
                    /** @var consultation $consLib */
                    $consLib = loadLibClass('consultation');
                    $redirectUrlResult = $consLib->checkVroomIdAndGetRedirectUrl($vroomId, $this->getUserId());
                }

                if (!empty($redirectUrlResult['redirectUrl'])) {

                    $redirectUrl = $redirectUrlResult['redirectUrl'];

                    if (
                        isset($redirectUrlResult['systemData']) &&
                        isset($redirectUrlResult['systemData']['vroomId']) &&
                        !empty($redirectUrlResult['systemData']['vroomId'])
                    ) {
                        /** @var Vroom $vroomObj */
                        $vroomObj = loadLibClass('vroom');
                        $vroomObj->sendSession();

                        $sessId = session_id();

                        // we add sessId to url
                        $redirectUrl .= '?s=' . $sessId;

                        $lang = !empty($this->getUserData('lang')) ?
                            $this->getUserData('lang') : getDefaultLang();

                        $redirectUrl .= '&lang=' . $lang;
                    }

                    $this->return['location'] = $redirectUrl;

                } else if (@getP('fields/url') || !empty($_SESSION['loginFieldUrl'])) {

                    $url = getP('fields/url');
                    if(!$url && !empty($_SESSION['loginFieldUrl'])) {
                        $url = $_SESSION['loginFieldUrl'];
                        unset($_SESSION['loginFieldUrl']);
                    }

                    $this->return['location'] = $url;

                    $returnUrl = explode( '/', $this->return['location']);
                    if (isset($returnUrl[3]) && checkLangEnabled($returnUrl[3])){
                        if (!empty($this->getUserData('lang')) && checkLangEnabled($this->getUserData('lang'))){
                            $returnUrl[3] =  $this->getUserData('lang');
                        }
                        $this->return['location'] = implode('/',$returnUrl);
                    }

                    $_SESSION['redirectTo'] = $this->return['location'];

                } elseif (!empty($_SESSION['dcReturn'])) {

                    $this->return['location'] = $_SESSION['dcReturn'];
                    unset($_SESSION['dcReturn']);

                } else {
                    $_SESSION['ad_language'] = $this->getUserData('lang');
                    $this->return['location'] = getLM($this->cfg->getData('mirros_default_profile_page'),'',$this->getUserData('lang'));
                }

            } else {

                if ($this->sessionFailPopup) {
                    $this->return['html'] = $this->sessionFailPopup;
                    return false;
                }

                $this->return['errors']['global'] = gL('login_global_error');
                return false;
            }

        } else {

            if(!empty($_SESSION['user_not_exists_message'])) {
                $this->setPData(true, 'user_not_exists_message');
                unset($_SESSION['user_not_exists_message']);
            }

            if(!empty($_SESSION['user_exists_message'])) {
                $this->setPData(true, 'user_exists_message');
                unset($_SESSION['user_exists_message']);
            }

            $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('login', $this->getPData()));
        }
    }

    /**
     * @param $msg
     * @return String
     */
    private function popupMessage($msg)
    {
        $tmplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/profile/tmpl/');
        $this->setPData($msg, 'message');
        $popupHtml = $this->tpl->output('popup-message', $this->getPData());
        $this->tpl->setTmplDir($tmplDir);
        return $popupHtml;
    }

    public function agreeTermsForm()
    {

        if (getP('action') == 'agree_terms') {

            $valid = getP('agreement') == 1 && getP('privacy_policy') == 1;

            if ($valid) {
                // db record
                if (updateUserProfileDateBasedValues(time(), 'agree_terms')) {

                    //check passed
                    $_SESSION['checkAgreementAccept'] = true;
                    $this->return['location'] = $_SESSION['redirectTo'];
                    unset($_SESSION['isChecking']);
                }
            } else {
                // return (array 'return') errors
                $this->return['errors'] = array(
                    'agreement' => getP('agreement') == 1,
                    'privacy_policy' => getP('privacy_policy') == 1,
                );
            }

        } else {
            $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('agree-terms', $this->getPData()));
        }
    }

    public function registrationForm()
    {

        $this->setAllowedLanguages();

        $this->setSwitcherLanguage();

        if (getP('action') == 'register') {

            /** @var validator $validator */
            $validator = loadLibClass('validator');
            $validator->setFieldsArray($this->fieldsArray['register']);

            foreach ($this->fieldsArray['register'] as $field => $data) {
                $validator->checkValue($field, getP('fields/' . $field));
            }

            $result = $validator->returnData();

            if (!getP('fields/agreement')) {
                $result['errorFields']['agreement'] = gL('order_Errors_Fields_Agreement');
            }

            if (!getP('fields/bd_date') || !getP('fields/bd_month') || !getP('fields/bd_year')) {
                $result['errorFields']['date_of_birth'] = gL('order_Errors_Fields_DateOfBirth');
            }

            if (empty($result['error'])) {

                if (getP('fields/agreement')) {

                    if ($userId = $this->saveUser()) {

                        $url = getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang()) . '?registration-submited';
                        unset($_SESSION['regData']);
                        $this->return['location'] = getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang()) . '?registration-submited';

                    } else {

                        if ($this->sessionFailPopup) {
                            $this->return['html'] = $this->sessionFailPopup;
                        } else {
                            $this->return['errors']['global'] = gL('order_Errors_Global_RegisterUser');
                        }

                        return false;
                    }
                } else {
                    $this->return['errors']['fields']['agreement'] = gL('order_Errors_Fields_Agreement');
                    return false;
                }


            } else {
                $this->return['errors']['fields'] = $result['errorFields'];
                return false;
            }

        } elseif (getP('action') == 'changeLang'){

            $_SESSION['regData'] = $_POST['fields'];

            if ($_SESSION['regData']["person_id"] != '-'){

                $personId = $_SESSION['regData']["person_id"];
                $parts = explode('-', $personId);
                $_SESSION['regData']['personIdFirst'] = !empty($parts[0]) ? $parts[0] : '';
                $_SESSION['regData']['personIdSecond'] = !empty($parts[1]) ? $parts[1] : '';
            }

            $this->return['location'] = getLM($this->cfg->getData('mirros_signup_page'), '', getP('lang'));

        } else {

            if(!empty($_SESSION['dmss_reg'])) {

                $dmssRegData = $_SESSION['dmss_reg'];
                unset($_SESSION['dmss_reg']);

                $dmssRegData['country_value'] = null;

                // get proper country value

                if(!empty($dmssRegData['country'])) {

                    $countryDbQuery = "SELECT * FROM kl_valstis WHERE code2 = '".$dmssRegData['country']."'";
                    $countryQuery = new query($this->db, $countryDbQuery);

                    if($countryQuery->num_rows()) {

                        $row = $countryQuery->getrow();
                        $dmssRegData['country_value'] = $row['title'] . " (" . $row['code2'] . ")";
                    }
                }


                $this->setPData(true, 'dmssReg');
                $this->setPData($dmssRegData, 'dmssRegData');
            }

            $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('registration', $this->getPData()));
            unset( $_SESSION['regData']);
        }

    }

    public function registrationCancel($userId)
    {

        $this->setPData($userId, "userId");
        $this->return['html'] = $this->tpl->output('registration-cancel-confirm', $this->getPData());
    }

    public function registrationCancelConfirm($userId)
    {

        $this->logout(false);

        disableRegistrationByUserId($userId);

        $this->return['location'] = '/' . $this->getLang() . '/';

        return true;
    }

    public function setNewPassword()
    {
        if (md5(getP('current_password') . $this->sals) != getS('user/password')) {
            $this->return['errors']['fields']['current_password'] = gL('profile_Errors_Fields_CurrentPassword');
            return false;
        }

        $validator = loadLibClass('validator');

        if (!$validator->checkPass(getP('password'), 'password')) {
            $this->return['errors']['fields']['password'] = gL('profile_Errors_Fields_Password');
            return false;
        }

        if (getP('password') != getP('confirm_password')) {
            $this->return['errors']['fields']['confirm_password'] = gL('profile_Errors_Fields_ConfirmPassword');
            return false;
        }

        $_SESSION['user']['mandatoryPassChangePolicy']['needChangePass'] = false;

        $dbData['password'] = $_SESSION['user']['password'] = md5(getP('password') . $this->sals);
        $dbData['passwordLastChanged'] = date('Y-m-d H:i:s', time());
        $dbData['temp_password'] = '0';

        $this->tfaRemoveCode($this->userId);

        return saveValuesInDb($this->dbTable, $dbData, $this->userId);
    }

    public function edit()
    {
        if (getP('action') == 'save') {

            /** @var validator $validator */
            $validator = loadLibClass('validator');
            $validator->setFieldsArray($this->fieldsArray['edit']);

            foreach ($this->fieldsArray['edit'] as $field => $data) {
                $validator->checkValue($field, getP('fields/' . $field));
            }

            $result = $validator->returnData();

            if (empty($result['error'])) {

                if (getP('fields/userSelectedLang')) {
                    $user = $this->getPatientById($this->userId);
                }

                $reload = false;

                $dbData = array();
                $dbData['name'] = getP('fields/name');
                $dbData['surname'] = getP('fields/surname');
                $dbData['email'] = getP('fields/email');
                $dbData['phone'] = getP('fields/phone');
                $dbData['resident'] = getP('fields/resident') ? '1' : '0';
                $dbData['gender'] = getP('fields/gender');
                $dbData['insurance_id'] = getP('fields/insurance_id');
                $dbData['insurance_number'] = getP('fields/insurance_number');
                $dbData['city_id'] = getP('fields/city_id');
                $dbData['lang'] = getP('fields/userSelectedLang');
                $dbData['email_notifications'] = getP('fields/email_notifications') ? '1' : '0';
                $dbData['sms_notifications'] = getP('fields/sms_notifications') ? '1' : '0';
                $dbData['insurance_start_date'] = getP('fields/insurance_start_date') ? (date('Y-m-d', strtotime(getP('fields/insurance_start_date'))) . ' 00:00:00') : null;
                $dbData['insurance_end_date'] = getP('fields/insurance_end_date') ? (date('Y-m-d', strtotime(getP('fields/insurance_end_date'))) . ' 23:59:59') : null;

                if ($dbData['resident'] == '1') {
                    $dbData['country'] = 'LV';
                } else {
                    if ($this->cfg->get('profileVerificationEnabled') == true) {
                        $dbData['country'] = substr(getP('fields/country'), -3, 2);
                    } else {
                        $dbData['country'] = 'null';
                    }

                }

                if (getP("fields/bd_year") && getP("fields/bd_month") && getP("fields/bd_date")) {
                    $dbData['date_of_birth'] = getP("fields/bd_year") . "-" . str_pad(getP("fields/bd_month"), 2, "0", STR_PAD_LEFT) . "-" . getP("fields/bd_date");
                }
                if (getP('fields/resident') == 1) {
                    $dbData['person_id'] = getP('fields/person_id');
                    $dbData['person_number'] = '';
                } else {
                    $dbData['person_id'] = '';
                    $dbData['person_number'] = getP('fields/person_number');
                }

                // check user
                $dbQuery = "SELECT * FROM mod_profiles WHERE id = " . $this->userId;
                $query = new query($this->db, $dbQuery);
                if ($query->num_rows()) {

                    $row = $query->getrow();

                    if ($row['enable'] == 1) {

                        if (
                            $dbData['country'] != $row['country'] ||
                            $dbData['name'] != $row['name'] ||
                            $dbData['surname'] != $row['surname'] ||
                            trim($dbData['person_id']) != trim($row['person_id']) ||
                            trim($dbData['person_number']) != trim($row['person_number'])
                        ) {
                            // if some of important fields changed
                            // we remove verification

                            $dbData['verified_at'] = 'null';
                            $dbData['verification_method'] = 'null';
                            $reload = true;
                        }

                        $id = saveValuesInDb($this->dbTable, $dbData, $this->userId);

                        if ($id && !isset($_SESSION['confirm_personal_data']) || (isset($_SESSION['confirm_personal_data']) && $_SESSION['confirm_personal_data'] == null)) {

                            if (updateUserProfileDateBasedValues(time(), 'confirm_personal_data')) {

                                //check passed
                                $_SESSION['checkConfirmPersonalData'] = true;
                                $this->return['confirm_personal_data'] = 'confirmed';
                                $this->return['location'] = $_SESSION['redirectTo'];
                                unset($_SESSION['isChecking']);
                            }
                        }

                        if (getP('fields/newsletter_notifications')) {

                            if (!$this->userData['newsletter']) {
                                $dbData = array();
                                $dbData['email'] = getP('fields/email');
                                $dbData['created'] = time();
                                $dbData['lang'] = $this->getLang();

                                saveValuesInDb('mod_newsletter', $dbData);
                            }

                        } else {
                            deleteFromDbById('mod_newsletter', $this->userData['email'], "email");
                        }

                        //$this->sendRegistrationEmail(getP('fields/email'));

                        if ($id) {
                            $this->collectUserData($this->userId);

                            if ($reload) {
                                $this->return['reload'] = true;
                                $_SESSION['showSuccessMsg'] = true;
                            }

                            $this->return['result'] = 'OK';
                            return $id;
                        } else {
                            return false;
                        }
                    }
                }

                return false;

            } else {
                $this->return['errors']['fields'] = $result['errorFields'];
                return false;
            }

        } else {

            $classificators['city'] = $this->cl->getListByType(CLASSIF_CITY);
            $classificators['ic'] = $this->cl->getListByType(CLASSIF_IC);
            $this->setPData($classificators, "cl");

            $dbQuery = "SELECT * FROM ad_languages WHERE enable=1";
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {
                $allowedLanguages = $query->getArray();
                $this->setPData($allowedLanguages, "allowedLanguages");
            }
        }
    }

    public function getChangePasswordForm()
    {
        return $this->tpl->output('change-password', $this->getPData());
    }

    public function passwordRecovery($email)
    {

        if ($email) {

            $dbQuery = "SELECT *
							FROM `" . $this->dbTable . "`
							WHERE 1
								AND `enable` = '1'
								AND `email` = '" . mres($email) . "'
							LIMIT 1";
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0) {

                $row = $query->getrow();

                $this->userId = $row['id'];

                if (is_string($row['hash_confirm']) && $row['hash_confirm'] != '') {
                    $return['errors']['fields']['password_reminder'] = gL('profile_Errors_Fields_PasswordRecovery_EmailConfirm');

                    return $return;
                }

                $password = $this->generatePassword();

                $dbData[] = " `temp_password` = '1' ";
                $dbData[] = " `password` = '" . md5($password . $this->sals) . "' ";
                $dbData[] = " `email` = '" . mres($email) . "' ";

                $dbQuery = "INSERT INTO `" . $this->dbTable . "` SET " . implode(',', $dbData) .
                    " ON DUPLICATE KEY UPDATE " . implode(',', $dbData);
                doQuery($this->db, $dbQuery);

                $lang = !empty($row['lang']) ? $row['lang'] : $this->getLang();

                $this->tfaRemoveCode($this->userId);

                $body = str_replace(array('{{password}}'), array($password), getTranslation($this->cfg, 'mailBodyPassword', $lang));

                $this->addMessage(getTranslation($this->cfg, 'mailSubjPassword', $lang), $body);

                return sendMail($email, getTranslation($this->cfg, 'mailSubjPassword', $lang), $body, array(), getTranslation($this->cfg, 'mailFromPassword', $lang), true);
            } else {

                $return['errors']['fields']['password_reminder'] = gL('profile_Errors_Fields_PasswordRecovery');

                return $return;
            }
        } else {
            $return['errors']['fields']['password_reminder'] = gL('profile_Errors_Fields_PasswordRecovery');

            return $return;
        }
    }

    public function generatePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

    public function openMessage()
    {
        if (getP('message')) {
            $dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('profiles', 'messages') . "`
							WHERE 1
								AND `profile_id` = '" . mres($this->userId) . "'
								AND `id` = '" . mres(getP('message')) . "'
							LIMIT 1";

            $query = new query($this->db, $dbQuery);

            if ($query->num_rows()) {

                $message = $query->getrow();

                $dbQuery = "UPDATE `" . $this->cfg->getDbTable('profiles', 'messages') . "`
    						SET `readed` = 1
							WHERE 1
								AND `id` = '" . $message['id'] . "'
							LIMIT 1";
                doQuery($this->db, $dbQuery);

                $this->getUnreadMessagesCount();

                $this->setPData($message, "message");
                $this->return['html'] = $this->tpl->output('open-message', $this->getPData());
            }
        }
    }

    public function deleteMessageConfirm()
    {
        $this->return['html'] = $this->tpl->output('delete-message-confirm', $this->getPData());
    }

    public function deleteMessage()
    {

        $messages = getP('messages');

        if (is_array($messages) && count($messages) > 0) {
            $mString = implode(',', $messages);

            $dbQuery = "DELETE FROM " . $this->cfg->getDbTable('profiles', 'messages') . "
                    WHERE id IN (" . $mString . ")";
            doQuery($this->db, $dbQuery);

            $this->getUnreadMessagesCount();

            $this->return['ok'] = 1;
        } else {
            $this->return['ok'] = 0;
        }
    }

    public function deletePerson()
    {
        $this->return['html'] = $this->tpl->output('delete-person-confirm', $this->getPData());
    }

    public function deleteProfile()
    {
        $this->return['html'] = $this->tpl->output('delete-profile-confirm', $this->getPData());
    }

    public function deleteProfileConfirm()
    {
        if (getP('password') && md5(getP('password') . $this->sals) == $this->userData['password']) {

            $this->sessionManager->sessionAbort($this->userId, SESSION_CANCEL_DISABLED_BY_USER);

            $dbData = array();
            $dbData['deleted'] = 1;
            $dbData['email'] = '*****' . date('d.m.Y H:i:s');
            $dbData['name'] = '*****';
            $dbData['surname'] = '*****';
            $dbData['phone'] = '*****';

            $dbData['deleted_at'] = time();
            saveValuesInDb($this->dbTable, $dbData, $this->userId);

            $this->return['ok'] = 1;
            $this->return['location'] = getLM($this->cfg->getData('mirros_profile_logout_page'));

        } else {
            $this->return['errors']['fields']['password'] = gL('form_delete_profile_password_error');
        }
    }

    public function deletePersonConfirm()
    {
        if (getP('id')) {

            $dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('profiles', 'persons') . "`
							WHERE 1
								AND `profile_id` = '" . mres($this->userId) . "'
								AND `id` = '" . mres(getP('id')) . "'
							LIMIT 1";
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {

                deleteFromDbById($this->cfg->getDbTable('profiles', 'persons'), getP('id'));

                $this->return['ok'] = 1;
                $this->return['location'] = getLM($this->cfg->getData('mirros_persons_page'));
            } else {
                $this->return['ok'] = 0;
            }

        } else {
            $this->return['ok'] = 0;
        }
    }

    public function removeDoctor()
    {
        if (getP('doctorId') && getP('clinicId')) {
            $dbQuery = "DELETE
							FROM `" . $this->cfg->getDbTable('profiles', 'doctors') . "`
							WHERE 1
								AND `profile_id` = '" . mres($this->userId) . "'
								AND `doctor_id` = '" . mres(getP('doctorId')) . "'
								AND `clinic_id` = '" . mres(getP('clinicId')) . "'
							LIMIT 1";
            doQuery($this->db, $dbQuery);

            $this->return['ok'] = 1;

        } else {
            $this->return['ok'] = 0;
        }

    }

    public function getProfileDoctors()
    {

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND c.id in (" . $this->allowed_clinics . ")";
        }
        $dbQuery = "SELECT DISTINCT 
    						d.*, di.*, d.id AS id, 
    						d.url as doctor_url,
    						c.name AS clinic_name, 
    						ci.address AS clinic_address, 			
    						c.id AS clinic_id,
    						c.url as clinic_url
							FROM `" . $this->cfg->getDbTable('profiles', 'doctors') . "` p
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self') . "` d ON (p.doctor_id = d.id)
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info') . "` AS di ON 
								di.id = (SELECT d1.id FROM `" . $this->cfg->getDbTable('doctors', 'info') . "` AS d1 WHERE p.doctor_id = d1.doctor_id LIMIT 1)		
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self') . "` c ON (p.clinic_id = c.id)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci ON
								ci.id = (SELECT ci1.id FROM `" . $this->cfg->getDbTable('clinics', 'info') . "` AS ci1 WHERE c.id = ci1.clinic_id LIMIT 1)								
							WHERE 1
							" . $clinicIdFilter . "  
								AND p.`profile_id` = '" . mres($this->userId) . "'
								AND d.is_hidden_on_piearsta = 0
								AND d.deleted = 0";
        $query = new query($this->db, $dbQuery);
        $this->userData['doctors'] = $query->getArray();


        $this->setPData($this->userData, 'userData');
    }

    public function getNearestReservation()
    {

        $today = null;
        $nearest = array();
        $past = array();

        // get last reservations in past

        $dbQuery = "SELECT r.id as resId, r.start, d.id as docId, d.photo, di.name, di.surname, c.url as clinicUrl, d.url as doctorUrl  
							FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r
							LEFT JOIN mod_doctors d ON (r.doctor_id = d.id) 
							LEFT JOIN mod_doctors_info di ON (d.id = di.doctor_id) 
							LEFT JOIN mod_clinics c ON (r.clinic_id = c.id) 
							WHERE 1
								AND r.`profile_id` = '" . mres($this->userId) . "'
								AND r.start < '" . date(PIEARSTA_DT_FORMAT) . "'
								AND r.status IN (0, 2, 4)		
							ORDER BY r.start DESC
							LIMIT 3";
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            $reservations = $query->getArray();

            foreach ($reservations as $res) {

                $past[] = array(
                    'id' => $res['resId'],
                    'weekday' => date('w', strtotime($res['start'])),
                    'date' => date('d.m', strtotime($res['start'])),
                    'time' => date('H:i', strtotime($res['start'])),
                    'doctor' => $res['name'] . ' ' . $res['surname'],
                    'photo' => $res['photo'],
                    'clinicUrl' => $res['clinicUrl'],
                    'doctorUrl' => $res['doctorUrl'],
                );
            }
        }


        // get nearest future reservations incl today

        $dbQuery = "SELECT r.id as resId, r.start, d.id as docId, d.photo, di.name, di.surname 
							FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` r
							LEFT JOIN mod_doctors d ON (r.doctor_id = d.id) 
							LEFT JOIN mod_doctors_info di ON (d.id = di.doctor_id)
							WHERE 1
								AND r.`profile_id` = '" . mres($this->userId) . "'
								AND r.start >= '" . date(PIEARSTA_DT_FORMAT) . "'
								AND r.status IN (0, 2)		
							ORDER BY r.start ASC
							LIMIT 3";
        $query = new query($this->db, $dbQuery);

        if ($query->num_rows()) {

            $reservations = $query->getArray();

            foreach ($reservations as $res) {

                if(date('Y-m-d', strtotime($res['start'])) == date('Y-m-d') && empty($today)) {
                    $today = array(
                        'id' => $res['resId'],
                        'weekday' => date('w', strtotime($res['start'])),
                        'date' => date('d.m', strtotime($res['start'])),
                        'time' => date('H:i', strtotime($res['start'])),
                        'doctor' => $res['name'] . ' ' . $res['surname'],
                        'photo' => $res['photo'],
                    );
                }

                if(count($nearest) < 2 && (empty($today) || $today['id'] != $res['resId'])) {
                    $nearest[] = array(
                        'id' => $res['resId'],
                        'weekday' => date('w', strtotime($res['start'])),
                        'date' => date('d.m', strtotime($res['start'])),
                        'time' => date('H:i', strtotime($res['start'])),
                        'doctor' => $res['name'] . ' ' . $res['surname'],
                        'photo' => $res['photo'],
                    );
                }
            }

            $days = (strtotime($query->field('start')) - time()) / 86400;

            $this->userData['nearest'] = array(
                'date' => date("d.m.Y.", strtotime($query->field('start'))),
                'days' => gL('after', 'pēc') . ' ' . ceil($days) . ' ' . gL('days', 'dienām'),
            );
        }

        $this->userData['past'] = $past;
        $this->userData['today'] = $today;
        $this->userData['nearest'] = $nearest;

        $this->setPData($this->userData, 'userData');

//        pre($this->userData['today']);
//        pre($this->userData['nearest']);
//        pre($this->userData['past']);
    }

    public function getProfileReservations()
    {
        $this->setPData($this->userData, 'userData');
    }

    public function getProfileConsultations()
    {
        $this->setPData($this->userData, 'userData');
    }

    public function getProfileOrders()
    {

        // filter by status
        $where = "";

        $statusesToShow = array();

        if (getG('status') !== false && getG('status') != 'all') {

            $selectedStatus = getG('status');

            if (in_array($selectedStatus, array(ORDER_STATUS_PENDING, ORDER_STATUS_PAID, ORDER_STATUS_CANCELED))) {

                $statusesToShow[] = $selectedStatus;

                if ($selectedStatus === ORDER_STATUS_PAID) {
                    $statusesToShow[] = ORDER_STATUS_PRELIMINARY_PAID;
                }
            }

        } else {

            $_GET['status'] = 'all';

            $statusesToShow = array(
                ORDER_STATUS_PENDING,
                ORDER_STATUS_CANCELED,
                ORDER_STATUS_PRELIMINARY_PAID,
                ORDER_STATUS_PAID,
            );
        }

        $where .= " AND o.status IN (" . implode(',', $statusesToShow) . ") ";

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = "AND o.clinic_id in (" . $this->allowed_clinics . ")";
        }
        $dbQuery = "SELECT o.id, o.patient_id, oi.creator_id, o.date, o.status, od.service_name, od.item_total
                        FROM " . $this->cfg->getDbTable('orders', 'self') . " o
                            LEFT JOIN " . $this->cfg->getDbTable('orders', 'details') . " od ON (o.id = od.order_id)
                            LEFT JOIN " . $this->cfg->getDbTable('orders', 'info') . " oi ON (o.id = oi.order_id)
                        WHERE 1
                            " . $where . "
                            AND oi.creator_id = " . $this->userId . "
							" . $clinicIdFilter . "					   
                        ORDER BY o.date DESC";

        $query = new query($this->db, $dbQuery);

        $this->userData['orders'] = array();

        if ($query->num_rows()) {
            while ($row = $query->getrow()) {
                $this->userData['orders'][] = $row;
            }
        }

        $this->setPData($this->userData, 'userData');
    }

    public function cancelReservationPopup($id)
    {

        $reservation = $this->openReservation($id, false);

        if ($reservation) {

            if ($reservation['order_id']) {

                /** @var order $ord */
                $ord = loadLibClass('order');
                $ord->setOrder($reservation['order_id']);
                $order = $ord->getOrder();

                if(!empty($order) && !empty($order['transaction_id'])) {

                    /** @var transaction $tr */
                    $tr = loadLibClass('transaction');
                    $tr->setTransaction($order['transaction_id']);
                    $transaction = $tr->getTransaction();

                    if(!empty($order['payment_reference'])){

                        if($this->cfg->get('everyPayRefundEnabled')){
                            $this->setPData(true, 'everyPayRefundEnabled');
                            $this->setPData(true, 'everyPayCard');
                            $amount = calculateAmount($reservation['start'], $reservation['service_type'], $reservation['service_price']);

                            if ($amount > 0){
                                $currency = gL('currency', 'Eur');
                                $this->setPData($amount . ' ' . $currency, 'amount');
                            }
                        }

                        if(substr($transaction['payment_method'] , 0, 8) == 'everyPay'){
                            $transaction['payment_method'] = 'card';
                        }

                        if ($order['status'] === ORDER_STATUS_PRELIMINARY_PAID) {
                            $this->setPData(true, 'orderPreliminaryPaid');
                            $this->setPData($this->cfg->get('supportEmail'), 'supportEmail');
                        }
                    }

                    $payment = array(
                        'method' => $transaction['payment_method'],
                        'pan' => null,
                    );

                    if ($transaction['payment_method'] == 'cards') {
                        $payment['pan'] = $transaction['pan'];
                    }

                    $this->setPData($payment, 'payment');
                }

                $this->setPData(array(
                    'warning' => gL('iban_warning', 'Bank account looks like invalid!'),
                    'ok' => gL('iban_ok', 'Bank account is OK!'),
                ), 'iban');
            }

            $translation = getTranslatedCancellationReasons($this->cfg,  $this->getLang());

            $this->setPData($translation, 'cancelationReasons');
            $this->return['html'] = $this->tpl->output('reservations-cancel', $this->getPData());
        }
    }

    /**
     * @param null $sheduleId
     * @param null $serviceId
     * @param null $source
     * @param null $note
     * @param null $personId
     * @param null $clinicId
     * @param null $doctorId
     * @param false $dc
     * @param null $resOptions
     * @throws Exception
     */
    public function addReservationPopup($sheduleId = null, $serviceId = null, $source = null, $note = null, $personId = null, $clinicId = null, $doctorId = null, $dc = false, $resOptions = null)
    {

        if ($this->userData['hash_confirm']) {
            $this->return['location'] = getLM($this->cfg->getData('mirros_default_profile_page'));
            return;
        }

        if (!empty($resOptions) && is_array($resOptions)) {

            foreach ($resOptions as $k => $v) {
                $_SESSION[$k] = $v;
            }
        }

        $dcDuration = null;
        $dcPrice = null;
        $dcServicesList = null;

        if ($dc) {
            $this->setPData(true, "dcAppointment");
            $this->setPData($this->cfg->get('dcUrl'), "dcUrl");

            // optional params for DC reservation

            if ($_SESSION['dcChannelType']) {
                $this->setPData($_SESSION['dcChannelType'], 'dc_channel_type');
                unset($_SESSION['dcChannelType']);
            }

            if ($_SESSION['dcEntityName']) {
                $this->setPData($_SESSION['dcEntityName'], 'dc_entity_name');
                unset($_SESSION['dcEntityName']);
            }

            if ($_SESSION['dcConsultationType']) {
                $this->setPData($_SESSION['dcConsultationType'], 'dc_consultation_type');
                unset($_SESSION['dcConsultationType']);
            }

            if ($_SESSION['dcKid']) {
                $this->setPData($_SESSION['dcKid'], 'dc_for_kid');
                unset($_SESSION['dcKid']);
            }

            if ($_SESSION['dcPhone']) {
                $this->setPData($_SESSION['dcPhone'], 'dc_phone_number');
                unset($_SESSION['dcPhone']);
            }

            if ($_SESSION['dcPrefferedLangs']) {
                $langs = $_SESSION['dcPrefferedLangs'];
                $this->setPData($langs, 'dc_preffered_langs');
                unset($_SESSION['dcPrefferedLangs']);
            }

            if ($_SESSION['dcLang']) {
                $this->setPData($_SESSION['dcLang'], 'dc_lang');
                unset($_SESSION['dcLang']);
            }

            if ($_SESSION['dcServicesList']) {
                $this->setPData($_SESSION['dcServicesList'], 'dc_servicesList');
                $dcServicesList = $_SESSION['dcServicesList'];
                unset($_SESSION['dcServicesList']);
            }

            // try to unlock slots before proceed

            /** @var lockRecord lockRecord */
            $this->lockRecord = loadLibClass('lockRecord');

            if ($this->lockRecord->setLockRecordByScheduleId($sheduleId)) {

                $dcLockData = $this->lockRecord->getLockRecord();

                $dcDuration = $dcLockData['dc_duration'] ? $dcLockData['dc_duration'] : null;
                $dcPrice = $dcLockData['dc_price'] ? $dcLockData['dc_price'] : null;

                $this->lockRecord->deleteLockRecord(true);
            }
        }

        // if no schedule id passed -- this means popup called by addConsultationPopup method

        $isConsultation = (getP('isConsultation') || $sheduleId == null || $sheduleId == 'null') && !$dc;

        $this->setPData($isConsultation, 'isConsultation');

        // if no serviceId passed and this is not a consultation -- this means, the popup opened by click on schedule, not by back button in orderDetails
        if (!$serviceId && !$isConsultation && !$dcServicesList) {

            // We check whether this time already booked

            if (!$this->reservation) {
                /** @var reservation reservation */
                $this->reservation = loadLibClass('reservation');
            }

            if ($this->reservation->isSlotAlreadyBooked($sheduleId)) {

                $data = array();
                $data['already_locked'] = true;

                $this->setPData($data, "item");

                $this->return['error'] = array(
                    'scheduleId' => $sheduleId,
                    'alreadyLocked' => true,
                );
                $this->return['html'] = $this->tpl->output('reservations-add', $this->getPData());
                return;
            }
        }

        // if this is reservation we have to get get all slot related info including all available services

        if (!$isConsultation) {

            // collectSlotInfo

            if (!$this->reservation) {
                /** @var reservation reservation */
                $this->reservation = loadLibClass('reservation');
            }

            $row = $this->reservation->getTimeSlotRelatedInfo($sheduleId, $dc, $serviceId, $dcServicesList);

            // collected
            if ($row) {

                // No services can be booked for selected time slot
                // first user gets error message if no available services and slot set to booked
                if (count($row['services']) < 1) {
                    $data = array();
                    $data['not_enough_time'] = true;

                    $this->return['slotsToBook'] = !empty($row['availableSlots']) ? array_column($row['availableSlots'], 'id') : array();
                    $this->return['noCancelRes'] = true;
                    $this->setPData($data, "item");
                    $this->return['html'] = $this->tpl->output('reservations-add', $this->getPData());
                    return;
                }

                $row['service_max_duration'] = max(array_column($row['services'], 'length_minutes'));

                if ($dc && $dcDuration) {
                    $row['service_max_duration'] = $dcDuration;
                }

                // proverka 4to v etot denj u etogo  vra4a etot klient uzhe  bral uslugi

                $userData = $this->getPData('userData');
                $row['another_reservation_exists'] = $this->reservation->doesAnotherReservationExist($row, $userData['id']);


                // lock slots

                $lockResult = array();

                try {
                    $lockResult = $this->lockSlot($sheduleId, $row['doctor_id'], $row['hsp_doctor_id'], $row['clinic_id'], $row['start_time'], $row['service_max_duration']);
                } catch (Exception $e) {
                    $lockResult['error'] = $e->getMessage();
                }

                if (isset($lockResult['error'])) {
                    // return error -- can't book this time!
                    $row['another_reservation_exists'] = true;
                    $row['book_error'] = $lockResult['error'];
                    // this error will be handled in popup!
                    //
                } else {

                    $row['lockRecordId'] = $lockResult['lockRecordId'];
                    $row['slots'] = $lockResult['slots'];

                    // if this is dc appointment we set dcDuration and dcPrice - to override standard logic on addReservation

                    if ($dc && $this->lockRecord->setLockRecord($lockResult['lockRecordId'])) {

                        $newLockData = array();

                        if ($dcDuration) {
                            $newLockData['dc_duration'] = $dcDuration;
                        }

                        if ($dcPrice) {
                            $newLockData['dc_price'] = $dcPrice;
                        }

                        if (!empty($newLockData)) {
                            $this->lockRecord->updateLockRecord($lockResult['lockRecordId'], $newLockData);
                        }
                    }
                }

                $clinicData = $this->getClinicById($row['clinic_id']);

                $row['sm_confirmation_timeout'] = SM_CONFIRMATION_TIMEOUT;
                $row['check_sm'] = CHECK_SM;
                $row['scheduleId'] = $sheduleId;
                $row['notice'] = $note;
                $row['personId'] = $personId;
                $row['profile_person_id'] = $personId;
                $row['payments_enabled'] = !empty($clinicData['payments_enabled']) && $clinicData['payments_enabled'] == 1 ? 1 : 0;

                $row['DC_PRICE'] = $dcPrice;

            } else {

                // handle empty slot error

                $data = array();
                $data['already_booked'] = true;

                $this->setPData($data, "item");

                $this->return['error'] = array(
                    'scheduleId' => $sheduleId,
                    'alreadyBooked' => true,
                );

                $this->return['html'] = $this->tpl->output('reservations-add', $this->getPData());
                return;
            }

        } else {

            // this is consultation
            // so we should collect services for doctor / clinic

            $clinicId = $clinicId ? $clinicId : getP('clinicId');
            $doctorId = $doctorId ? $doctorId : getP('doctorId');

            $doctorData = $this->getDoctorById($doctorId);
            $clinicData = $this->getClinicById($clinicId);

            $row = array();
            $row['doctor_id'] = $doctorId;
            $row['clinic_id'] = $clinicId;
            $row['name'] = $doctorData['name'];
            $row['surname'] = $doctorData['surname'];
            $row['doctor_url'] = $doctorData['url'];
            $row['hsp_doctor_id'] = $doctorData['hsp_resource_id'];
            $row['clinic_name'] = $clinicData['name'];
            $row['clinic_phone'] = $clinicData['phone'];
            $row['clinic_email'] = $clinicData['email'];
            $row['clinic_address'] = $clinicData['address'];
            $row['clinic_url'] = $clinicData['url'];
            $row['payments_enabled'] = !empty($clinicData['payments_enabled']) && $clinicData['payments_enabled'] == 1 ? 1 : 0;
            $row['personId'] = $personId;
            $row['profile_person_id'] = $personId;

            $row['anyTime'] = getP('anyTime');
            $row['selectedTime'] = getP('selectedTime');
            $row['notice'] = getP('notice');

            $row['availableSlots'] = array();

            /** @var cl $cl */
            $cl = loadLibClass('cl');
            $row['specialties'] = $cl->getSpecialtiesByDoctor($clinicId, $doctorId);
            $row['services'] = $cl->getRemoteServicesByDoctor($clinicId, $doctorId);

            $selectedService = count($row['services']) == 1 ? $row['services'][0] : null;

            if ($selectedService) {
                if (
                    $selectedService['price'] &&
                    $selectedService['price'] != '0.00' &&
                    $selectedService['price'] > 0
                ) {
                    $price = $selectedService['price'];
                }
            }

            require_once(AD_APP_FOLDER . $this->app . '/doctors/inc/doctors.class.php');
            /** @var doctorsData $docModule */
            $docModule = new doctorsData();

            $_POST['remote_services'] = true;

            $schedule = array(
                'shedule' => $docModule->getShedule($doctorId, $clinicId),
            );

            $row['sheduleData'] = $schedule;


            // this is used for standard calendar ...
            //
//            $prev = $schedule['shedule']['prev'];
//            $next = $schedule['shedule']['next'];

//            $this->setPData($prev, "sheduleDataPrev");
//            $this->setPData($next, "sheduleDataNext");
//            $this->setPData($schedule, 'doctor');
//            $this->setPData(array($schedule), 'doctors');

//            $tplDir = $this->tpl->getTmplDir();
//            $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/doctors/tmpl/');
//            $this->tpl->assign("TEMPLATE_DOCTORS_MODULE_DATA", $this->tpl->output("calendar_list_body", $this->getPData()));
//            $this->tpl->setTmplDir($tplDir);

            if (DEBUG) {

                $this->return['consData'] = array(
                    'doctor' => $doctorData,
                    'clinic' => $clinicData,
                    'price' => $price,
                    'scheduleData' => $schedule,
                );
            }

//            $this->return['html'] = $this->tpl->output('consultation-add', $this->getPData());
        }

        if ($dc) {

            $row['dc'] = true;

            if ($dcDuration) {
                $row['dcDuration'] = $dcDuration;
                $row['services'][0]['sd_duration'] = $dcDuration;
            }

            if ($dcPrice) {
                $row['dcPrice'] = $dcPrice;
                $row['services'][0]['price'] = $dcPrice;
            }

            if($dcServicesList) {
                $row['dc_servicesList'] = $dcServicesList;
            }
        }

        // Check if user has dc subscription
        // and set corrected dc prices for services

        $dcSubscription = false;
        $network = $row['network'];

        if(!empty($this->subscription['clinicId'])) {

            $dcSubscription = $this->subscription['clinicId'] == $row['clinic_id'];

        } elseif (!empty($this->subscription['network'])) {

            $dcSubscription = $this->subscription['network'] == $network;
        }

        // Check if user has insurance police
        // and set corrected insurance prices for services
        // if not set new corrected price for dc subscription

        $insurancePolice = false; // does user have a police?
        $insurancePoliceNotStarted = true; // police not expired?
        $insurancePoliceExpired = true; // police started?
        $insuranceIncompleteData = false; // is it enough data about insurance in user profile?
        $insuranceCompanyAllowedInClinic = false; // is user's insurance company allowed in this clinic?

        $nowDT = date('Y-m-d H:i:s', time());
        $startDT = date('Y-m-d H:i:s', $row['start_time']);

        $clinicWorksWithInsurance = !empty($row['clinicAdditionalData']['insurancePayment']) && $row['clinicAdditionalData']['insurancePayment'];

        if($clinicWorksWithInsurance) {

            // DO we check insurance expiration now by user entered data
            // check for now datetime and for reservation start datetime

            if($this->userData['insurance_number'] && $this->userData['insurancePaId']) {

                $insurancePolice = true;

                if(empty($this->userData['insurance_start_date']) || empty($this->userData['insurance_end_date'])) {

                    $insuranceIncompleteData = true;

                } else {

                    if(
                        $nowDT >= $this->userData['insurance_start_date'] &&
                        $startDT >= $this->userData['insurance_start_date']
                    ) {
                        $insurancePoliceNotStarted = false;
                    }

                    if(
                        $nowDT < $this->userData['insurance_end_date'] &&
                        $startDT < $this->userData['insurance_end_date']
                    ) {
                        $insurancePoliceExpired = false;
                    }
                }
            }

            if($insurancePolice) {

                // change insurance allowed check -- get value from mod_clinics table or from mod_clinics_to_classificators

                $dbQuery = "
                    SELECT * FROM mod_clinics_to_classificators 
                    WHERE
                        clinic_id = ".$row['clinic_id']." AND 
                        cl_type = 5 AND 
                        cl_id = ".$this->userData['insurance_id']."
                ";

                $query = new query($this->db, $dbQuery);

                if($query->num_rows()) {
                    $insuranceCompanyAllowedInClinic = true;
                }
            }
        }

        // set flag to detect whether it is need to check local insurance data before proceed
        $row['needLocalInsuranceCheck'] = $insuranceIncompleteData || $insurancePoliceNotStarted || $insurancePoliceExpired;

        // set insurance allowed flag
        $row['insuranceAllowed'] = $insurancePolice;

        // set flag if clinic don't work with this insurance
        $row['insuranceCompDontWorkWithClinic'] = !$insuranceCompanyAllowedInClinic;

        // get corrected prices for services

        $services = $row['services'];

        foreach ($services as $k => $service) {

            if(empty($service['price'])) {
                continue;
            }

            $services[$k]['priceWithoutCorrections'] = $services[$k]['price'];

            if($dcSubscription) {

                // dc subscription price check and update services with corrected prices

                if(!empty($this->subscription['clinicId'])) {

                    $where = " ins.clinic_id = " . $this->subscription['clinicId'] . " AND ";

                } elseif (!empty($this->subscription['network'])) {

                    $where = " CASE WHEN ins.network_id IS NOT NULL THEN ins.network_id = $network ELSE ins.clinic_id = ".$row['clinic_id']." END AND ";
                }

                $dbQuery = "SELECT * FROM ins_network_clinic_special_prices ins 
                            WHERE
                                ins.service_id = ".$services[$k]['c_id']." AND 
                                $where
                                ins.start_datetime <= '$startDT' AND 
                                ins.end_datetime > '$startDT'
                                ";

                $query = new query($this->db, $dbQuery);

                if($query->num_rows()) {

                    /** @var array $servRow */
                    $servRow = $query->getrow();

                    if($servRow['price'] !== null) {

//                        $services[$k]['correctedDcPrice'] = $servRow['price'];

                        // we apply corrected price only if it is less than original price

                        if($servRow['price'] < $services[$k]['price']) {
                            $services[$k]['price'] = $servRow['price'];
                        }

                        // we always will show as correctedPrice a price that user actually have paid
                        $services[$k]['correctedDcPrice'] = $services[$k]['price'];
                    }
                }

            } else {

                $services[$k]['correctedInsPrice'] = null;

                if($insurancePolice && !$insurancePoliceExpired && !$insurancePoliceNotStarted && !$insuranceIncompleteData && $insuranceCompanyAllowedInClinic) {

                    // insurance price check and update services with corrected prices

                    $dbQuery = "
                            SELECT a.* 
                            FROM 
                            (
                                SELECT sp.price, 1 priority FROM ins_insurance_special_prices AS sp
                                WHERE 
                                    sp.comp_id = ".$this->userData['insurance_id']." AND 
                                    sp.clinic_id = " . $row['clinic_id'] . " AND 
                                    sp.service_id = " . $services[$k]['c_id'] . " AND 
                                    '" . $startDT . "' >= sp.start_datetime AND 
                                    '" . $startDT . "' <= sp.end_datetime
                                UNION ALL
                                    SELECT min(sp.price), 0 priority FROM ins_insurance_special_prices AS sp
                                    INNER JOIN ins_clinic_to_networks AS c2n ON ( c2n.clinic_id = " . $row['clinic_id'] . " AND c2n.start_datetime <= '" . $startDT . "' AND c2n.end_datetime >= '" . $startDT . "' )
                                    WHERE 
                                        sp.comp_id = ".$this->userData['insurance_id']." AND
                                        sp.clinic_id IS NULL AND 
                                        sp.service_id = " . $services[$k]['c_id'] . " AND 
                                        c2n.network_id = sp.network_id AND 
                                        '" . $startDT . "' >= sp.start_datetime AND 
                                        '" . $startDT . "' <= sp.end_datetime
                            ) a
                            ORDER BY a.priority DESC, a.price ASC
                            LIMIT 1
                        ";

                    $query = new query($this->db, $dbQuery);

                    if($query->num_rows()) {

                        /** @var array $servRow */
                        $servRow = $query->getrow();

                        if($servRow['price'] !== null) {

                            $services[$k]['correctedInsPrice'] = $servRow['price'];

                            // we apply corrected price only if it is less than original price

                            if($servRow['price'] < $services[$k]['price']) {
                                $services[$k]['price'] = $servRow['price'];
                            }
                        }
                    }
                }
            }
        }

        $row['services'] = $services;

        // get services custom warnings and messages

        /** @var serviceDetails $serviceDetails */
        $serviceDetails = loadLibClass('serviceDetails');

        $custMess = $serviceDetails->getServiceWarningsAndMessages($row['services'], $row['payments_enabled']);
        $row['infoWarnings'] = json_encode($this->getServiceInfoWarnings($custMess, $row));

        $_SESSION['services'] = $row['services'];

        // common for both reservation and consultation
        unset($_SESSION['schedule_id']);
        unset($_SESSION['calendarData']);

        if ($serviceId) {
            $this->return['serviceId'] = $serviceId;
        }

        $row['serviceIds'] = implode(',', array_column($row['services'], 'c_id'));

        if (!empty($_SESSION['appointmentInTheNameOfPatient'])) {

            $otherPatient = $this->getPatientById($_SESSION['appointmentInTheNameOfPatient']);

            if (!empty($otherPatient)) {
                $row['appointmentInTheNameOfPatient'] = $otherPatient;
            } else {
                unset($_SESSION['appointmentInTheNameOfPatient']);
            }
        }

//        pre($row);
//        exit;

        $this->getProfilePersons();
        $this->setPData($row, "item");
        $this->return['slotsToBook'] = explode(',', $row['slots']);
        $this->return['data'] = $row;
        $this->return['html'] = $this->tpl->output('reservations-add', $this->getPData());
    }

    // get html strings for info warnings to show in addReservationPopup
    private function getServiceInfoWarnings(array $customWarnings, array $item)
    {

        $result = array(
            'remote' => array(),
        );

        if (count($customWarnings) > 0) {
            foreach ($customWarnings as $service => $warning) {
                $result[$service] = urlencode($warning);
            }
        }

        foreach ($item['services'] as $k => $service) {
            if ($service['isRemote']) {
                $result['remote'][$service['c_id']] = true;
            }
        }

        // save current template dir and change to service_warnings dir
        $tplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/service_warnings/');

        $item['setLang'] = $this->getLang();

        // Add default agreement checkbox
        $this->setPData($item, 'item');
        $result['defaultCheckbox'] = urlencode($this->tpl->output('default-checkbox', $this->getPData()));

        // Add free service warning
        $this->setPData($item, 'item');
        $result['free'] = urlencode($this->tpl->output('free-service', $this->getPData()));

        // Add paid service warning
        $this->setPData($item, 'item');
        $result['paid'] = urlencode($this->tpl->output('paid-service', $this->getPData()));

        // Add pre-paid service warning
        $this->setPData($item, 'item');
        $result['prePaid'] = urlencode($this->tpl->output('pre-paid-service', $this->getPData()));

        // Add both-paid-and-free service warning
        $this->setPData($item, 'item');
        $result['both'] = urlencode($this->tpl->output('both-service', $this->getPData()));

        // Add select service warning
        $this->setPData($item, 'item');
        $result['select'] = urlencode($this->tpl->output('select-service', $this->getPData()));

        $this->tpl->setTmplDir($tplDir);

        return $result;
    }

    //
    public function finishReservationPopup($resId)
    {
        if(!$this->reservation) {
            $this->reservation = loadLibClass('reservation');
        }

        $this->reservation->setReservation($resId);
        $resData = $this->openReservation($resId, false, false);

        $orderData = null;

        if(!$this->order && !empty($resData['order_id'])) {
            $this->order = loadLibClass('order');
            $this->order->setOrder($resData['order_id']);
            $orderData = $this->order->getOrder();
        }

        if(!$this->lockRecord) {
            $this->lockRecord = loadLibClass('lockRecord');
        }

        $this->lockRecord->setLockRecordByReservationId($resId);
        $lockData = $this->lockRecord->getLockRecord();

        $fullResData = $this->openReservation($resId, false, false);

        if(!empty($fullResData) && !empty($fullResData['profile_id']) && $fullResData['profile_id'] == $this->userId) {

            $this->return['fullResData'] = $fullResData;
            $this->return['lockData'] = $lockData;
            $this->return['orderData'] = $orderData;
            $this->return['resData'] = $resData;

            if($resData['status'] != '6') {
                $this->setPData(true, 'finalStatus');
            }

            $resData['start_time_date_month'] = date('d. ', strtotime($resData['start'])) . gL("month_" . date('F', strtotime($resData['start'])));

            $this->setPData($resId, 'resId');
            $this->setPData($resData, 'reservation');

            $this->return['html'] = $this->tpl->output('reservation-finish', $this->getPData());

        } else {
            // actually no such reservation or reservation is not for current user
            throw new Exception('Reservation not found', 404);
        }
    }

    public function finishReservation($resId)
    {
        if(!$this->reservation) {
            $this->reservation = loadLibClass('reservation');
        }

        $this->reservation->setReservation($resId);
        $resData = $this->openReservation($resId, false, false);

        if(!empty($resData['order_id']) && !empty($resData['service_price'])) {

            $this->return['orderId'] = $resData['order_id'];

        } else {

            if(!$this->lockRecord) {
                $this->lockRecord = loadLibClass('lockRecord');
            }

            $this->lockRecord->setLockRecordByReservationId($resId);
            $lockData = $this->lockRecord->getLockRecord();

            $params = array(
                'reservationId' => $resId,
                'lockId' => $lockData['id'],
                'item' => $resData,
                'slots' => $lockData['slots'],
                'SMCheckResult' => array(
                    'request' => 'Not available',
                    'lockStatus' => $lockData['status'],
                    'hsp_reservation_id' => $lockData['hsp_reservation_id'],
                ),
                'lockData' => $lockData,
            );

            $this->freeServiceReservation($params);
        }
    }

    /**
     * @param $lockId
     * @param $status
     * @param null $reservationId
     * @param null $hspReservationId
     */
    public function setLockStatus($lockId, $status, $reservationId = null, $hspReservationId = null) {

        $lockData = array(
            'status' => $status,
        );

        $reservationData = null;
        $reservation = null;

        if($reservationId) {
            $lockData['reservation_id'] = $reservationId;
            $reservationData = array();

            if(!$this->reservation) {
                $this->reservation = loadLibClass('reservation');
                $this->reservation->setReservation($reservationId);
            }
        }

        if($hspReservationId) {
            $lockData['hsp_reservation_id'] = $hspReservationId;
            if(is_array($reservationData)) {
                $reservationData['hsp_reservation_id'] = $hspReservationId;
            }
        }

        $this->lockRecord->updateLockRecord($lockId, $lockData);

        if($this->reservation) {
            $this->reservation->updateReservation($reservationId, $reservationData);
        }

        $this->return['result'] = array(
            'success' => true,
            'newStatus' => $status,
        );
    }

    /**
     * check on SmartMedical
     */
    public function checkSM() {

        $timeout = (SM_CONFIRMATION_TIMEOUT / 1000);

        $lockData = $this->lockRecord->getLockRecord();
        $reservationRecord = $this->reservation->getReservation();

        // call check method
        $response = $this->checkForSlotsBookingAbility(null, $timeout);

        $result = array(
            'success' => $response['response']['confirmed'] == LOCK_STATUS_CONFIRMED || $response['confirmed'] == LOCK_STATUS_AUTOCONFIRMED,
            'status' => $response['response']['confirmed'],
            'hsp_reservation_id' => $response['response']['hsp_reservation_id'],
            'error_message' => $response['response']['error'],
        );

        // write to reservations table - hsp_reservation_id

        if($result['success'] && in_array($result['status'], array(LOCK_STATUS_CONFIRMED, LOCK_STATUS_AUTOCONFIRMED))) {

            $reservationData = array(
                'hsp_reservation_id' => $result['hsp_reservation_id'],
            );

            $this->reservation->updateReservation($lockData['reservation_id'], $reservationData);
            $this->lockRecord->setStatus($result['status']);

        } elseif ($result['success'] && in_array($result['status'], array(LOCK_STATUS_NON_CONFIRMED))) {

            $reservationData = array(
                'hsp_reservation_id' => null,
                'status' => RESERVATION_ABORTED_BY_SM,
                'status_reason' => 'SM not confirmed',
            );

            $this->reservation->updateReservation($lockData['reservation_id'], $reservationData);
            $this->lockRecord->setStatus($result['status']);

            $orderId = $lockData['order_id'];

            $this->order = loadLibClass('order');
            $this->order->setOrder($orderId);
            $this->order->deleteOrder();
        }

        $this->return['debugData'] = array(
            'args' => array(
                'slots' => $lockData['slots'],
                'lockId' => $lockData['id'],
                'reservationId' => $lockData['reservation_id'],
                'timeout' => $timeout,
            ),
            'debug' => $response['debug'],
        );
        $this->return['response'] = $result;
    }

    public function cancelAddReservation() {

        $slots = getP('slots');

        if(getP('backUrl')) {
            $this->return['backUrl'] = getP('backUrl');
        }

        if(!empty($slots)) {
            $this->return['slotsToUnBook'] = explode(',', $slots);
        }

        if(getP('dcAppointment')) {

            /** @var digitalClinic $dcApi */
            $dcApi = loadLibClass('digitalClinic');

            $dcUnlockRes = $dcApi->unlockCachedSlots(explode(',', $slots));
        }

        if(!$this->reservation) {

            if($this->lockRecord && $this->lockRecord->getLockRecordId()) {
                $this->lockRecord->deleteLockRecord();
            }

            $this->return['canceled'] = true;
            return true;
        }

        if($this->reservation && $this->reservation->getReservationId()) {
            $resData = $this->reservation->getReservation();
            if($resData['status'] != RESERVATION_WAITS_PAYMENT) {
                $this->return['finalStatus'] = $resData['status'];
                return false;
            }
        }

        $anyTime = !$resData['start'] && !$resData['end'];

        // Protect from hanging payment
//        if($this->order && $this->order->getOrderId() && $this->order->getStatus() == ORDER_STATUS_PENDING) {
//            return false;
//        }

        // delete lockRecord and unlock slots

        if(!$anyTime && $this->lockRecord) {
            $this->lockRecord->deleteLockRecord();
        }

        // if present order id (called from order info)
        if($this->order) {
            $this->order->deleteOrder();
        };

        $res = null;

        // if present reservation id (called from order info)
        if($this->reservation) {
            $this->reservation->deleteReservation();
        }

        // if transaction started set its status to Canceled
        if($this->transaction) {

            if($this->transaction->getTransactionId()) {
                $this->transaction->setStatus(TRANSACTION_STATUS_CANCELED);
            }
        }

        unset($_SESSION['services']);

        $this->return['canceled'] = true;
    }

    public function addReservation() {

        $isConsultation = getP('isConsultation');
        $scheduleId = getP('scheduleId');
        $selectedTime = getP('selectedTime');
        $serviceId = getP('serviceId');
        $anyTime = $selectedTime === '*';
        $doctorId = getP('doctorId');
        $clinicId = getP('clinicId');
        $fromTSWidget = getP('fromTSWidget');
        $dc = getP('dc') ? mres(getP('dc')) : null;
        $inTheNameOfPatient = getP('inTheNameOfPatient') ? mres(getP('inTheNameOfPatient')) : null;
        $haveInsurance = getP('haveInsurance');
        $needLocalInsuranceCheck = getP('needLocalInsuranceCheck');
        $otherPatient = null;
        $person = getP('profile_person_id');
        $needApproval = false;

        if(!empty($inTheNameOfPatient)) {
            $otherPatient = $this->getPatientById($inTheNameOfPatient);
        }

        // DC params
        $dcChannelType = getP('dc_channel_type') ? mres(getP('dc_channel_type')) : null;
        $dcEntityName = getP('dc_entity_name') ? mres(getP('dc_entity_name')) : null;
        $dcKid = getP('dc_for_kid') !== null ? (mres(getP('dc_for_kid')) ? '1' : '0') : null;
        $dcConsType = getP('dc_consultation_type') ? mres(getP('dc_consultation_type')) : null;
        $dcPhoneNumber = getP('dc_phone_number') ? mres(getP('dc_phone_number')) : null;
        $dcLang = getP('dc_lang') ? mres(getP('dc_lang')) : null;
        $dcPrefferedLangs = getP('dc_preffered_langs') ? mres(getP('dc_preffered_langs')) : null;
        $dcServicesList = getP('dc_services_list') ? mres(getP('dc_services_list')) : null;

        // this is error if no lockId passed and this is not consultation!
        if(!$this->lockRecord && !$isConsultation) {
            return false;
        }

        // no service selected -- Error!
        if(!$serviceId) {
            $this->return['errors']['fields']['service_id'] = gL('order_Errors_Fields_serviceId_Empty');
            return false;
        }

        // We already have services info!
        $services = $_SESSION['services'];
        $serviceId = getP('serviceId');
        $selectedService = $services[array_search($serviceId, array_column($services, 'c_id'))];

        // corrected price not for reservation for other person!

        if($person && !empty($selectedService['priceWithoutCorrections'])) {
            $selectedService['price'] = $selectedService['priceWithoutCorrections'];
        }

        // If service is remote then notice field is required
        if (isset($selectedService['isRemote']) && $selectedService['isRemote'] && !getP('notice')) {
            $this->return['errors']['fields']['notice'] = gL('order_Errors_Fields_sudzibas_Empty');
            return false;
        }

        $ld = null;
        $dcDuration = null;
        $dcPrice = null;

        if($this->lockRecord) {
            $ld = $this->lockRecord->getLockRecord();
            $dcDuration = !empty($ld['dc_duration']) ? $ld['dc_duration'] : null;
            $dcPrice = !empty($ld['dc_price']) ? $ld['dc_price'] : null;
        }

        if($isConsultation && !$ld && $scheduleId) {

            $this->reservation = loadLibClass('reservation');
            $tsInfo = $this->reservation->getTimeSlotRelatedInfo($scheduleId);

            /** @var serviceDetails $sdClass */
            $sdClass = loadLibClass('serviceDetails');

            $serviceInfo = $this->getServiceById($serviceId);
            $serviceInfo['serviceDetails'] = $sdClass->getDetailsByClinicIdAndServiseId($tsInfo['clinicId'], $serviceId, $tsInfo['doctorId']);
            $duration = $sdClass->getServiceDurationByDoctor($serviceId, getP('clinicId'), getP('doctorId'));
            $duration = $duration[$serviceId];

            if(!$duration) {
                $duration = $tsInfo['interval'];
            }

            try {
                $lockResult = $this->lockSlot($scheduleId, $tsInfo['doctor_id'], $tsInfo['hsp_doctor_id'], $tsInfo['clinic_id'], $tsInfo['start_time'], $duration, LOCK_STATUS_LOCALLY, null, null, $serviceId);
            } catch (Exception $e) {
                $lockResult['error'] = $e->getMessage();
            }

            if(!isset($lockResult['error'])) {

                $this->lockRecord = loadLibClass('lockRecord');
                $this->lockRecord->setLockRecord($lockResult['lockRecordId']);
                $ld = $this->lockRecord->getLockRecord();
            }
        }

        // handle person data if provided

        if (getP('profile_person_id')) {

            if (getP('profile_person_id') == 'add') {

                /** @var validator $validator */
                $validator = loadLibClass('validator');
                $validator->setFieldsArray($this->fieldsArray['add_person']);

                foreach ($this->fieldsArray['add_person'] AS $field => $data) {
                    $validator->checkValue($field, getP('fields/' . $field));
                }

                $result = $validator->returnData();

                if (!getP('fields/bd_date') || !getP('fields/bd_month') || !getP('fields/bd_year')) {
                    $result['errorFields']['date_of_birth'] = gL('order_Errors_Fields_DateOfBirth');
                }

                if (empty($result['error'])) {

                    $dbData = array();
                    $dbData['profile_id'] = $this->userId;
                    $dbData['name'] = getP('fields/name');
                    $dbData['surname'] = getP('fields/surname');
                    $dbData['phone'] = getP('fields/phone');
                    $dbData['resident'] = getP('fields/resident') ? '1' : '0';
                    $dbData['gender'] = getP('fields/gender');
                    $dbData['date_of_birth'] = getP('fields/date_of_birth');
                    $dbData['created'] = time();

                    if (getP("fields/bd_year") && getP("fields/bd_month") && getP("fields/bd_date")) {
                        $dbData['date_of_birth'] = getP("fields/bd_year") . "-" . str_pad(getP("fields/bd_month"), 2, "0", STR_PAD_LEFT) . "-" . getP("fields/bd_date");
                    }
                    if (getP('fields/resident') == 1) {
                        $dbData['person_id'] = getP('fields/person_id');
                        $dbData['person_number'] = null;
                    } else {
                        $dbData['person_id'] = null;
                        $dbData['person_number'] = getP('fields/person_number');
                    }

                    $personId = saveValuesInDb($this->cfg->getDbTable('profiles', 'persons'), $dbData);

                } else {

                    $this->return['errors']['fields'] = $result['errorFields'];
                    return false;
                }

            } else {
                $personId = getP('profile_person_id');

                if(getP('profileId')) {
                    $personId = getP('profileId');
                }
            }
        } else {
            $personId = NULL;
        }

        if(getP('personId')) {
            $personId = getP('personId');
        }

        $preparedPersonData = null;

        if($anyTime) {

            $dbQuery = "SELECT
		    			di.name,
		    			di.surname,
		    			c.name as clinic_name,
		    			c.payments_enabled as payments_enabled,
                        c.async_exchange_enabled,
    					cc.phone as clinic_phone,
    					cc.email as clinic_email,
		    			ci.address as clinic_address,
		    			c.terminal_id as terminal_id,
		    			d.hsp_resource_id as hsp_doctor_id
							FROM `" . $this->cfg->getDbTable('doctors', 'self')	 . "` d
    							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info')	 . "` di ON (d.id = di.doctor_id AND di.lang = '" . getDefaultLang() . "')
    							LEFT JOIN `mod_doctors_to_clinics` d2c ON (d2c.d_id = d.id AND di.lang = '" . getDefaultLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self')	 . "` c ON (c.id = d2c.c_id)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info')	 . "` ci ON (ci.clinic_id = c.id AND ci.lang = '" . getDefaultLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts')	 . "` cc ON (c.id = cc.clinic_id AND cc.default = 1)				
							WHERE 1
								AND d.id = " . mres($doctorId) . "
								AND c.id = " . mres($clinicId);
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows()) {

                /** @var array $row */
                $row = $query->getrow();

                // if appointment in the name of other patient

                if(!empty($otherPatient)) {
                    $row['appointmentMadeBy'] = $this->userData;
                    $row['inTheNameOfPatient'] = $otherPatient;
                }

                // for any time reservation we set start and end time to NULL

                $row['id'] = 'null';
                $row['start_time'] = 'null';
                $row['end_time'] = 'null';
                $row['doctor_id'] = $doctorId;
                $row['clinic_id'] = $clinicId;
                $row['serviceData'] = $selectedService;
                $row['notice'] = getP('notice');

                // create reservation

                $row['service_type'] = $isConsultation ? '1' : '0';

                // is this paid service?
                $isPaidService = $row['payments_enabled'] && isset($row['serviceData']['price']) && $row['serviceData']['price'] > 0 && $row['serviceData']['price'] != '0.00';

                $row['isPaidService'] = $isPaidService;

                // here also check if price was not nulled by subscription

                if (
                    ($row['payment_type'] == '1' && !getP('country_agreement') && !$isPaidService && !isset($row['serviceData']['correctedDcPrice'])) ||
                    (!$isPaidService && !getP('country_agreement') && !isset($row['serviceData']['correctedDcPrice']))
                ) {
                    $this->return['errors']['fields']['country_agreement'] = gL('order_Errors_Fields_country_agreement_Empty');
                    return false;
                }

                // add person info if provided to $row data
                if($personId) {

                    $row = $this->addPersonData($personId, $row);

                } else {

                    $row['profile_person_id'] = null;
                }

                // add dc params to resOptions

                $resOptions = array();

                if($dc) {
                    $resOptions['dcAppointment'] = $dc;
                }

                if($dcChannelType) {
                    $resOptions['dcChannelType'] = $dcChannelType;
                }

                if($dcEntityName) {
                    $resOptions['dcEntityName'] = $dcEntityName;
                }

                if($dcDuration) {
                    $resOptions['dcDuration'] = $dcDuration;
                }

                if($dc) {
                    $resOptions['dcKid'] = $dcKid ? '1' : '0';
                }

                if($dcConsType) {
                    $resOptions['dcConsType'] = $dcConsType;
                }

                if($dcPhoneNumber) {
                    $resOptions['dcPhoneNumber'] = $dcPhoneNumber;
                }

                if($dcPrefferedLangs) {
                    $resOptions['dcPreferredLangs'] = $dcPrefferedLangs;
                }

                if($dcLang) {
                    $resOptions['dcLang'] = $dcLang;
                }

                if($dcServicesList) {
                    $resOptions['dcServicesList'] = $dcServicesList;
                }

                $reservationId = $this->createReservation($row);

                if(!empty($resOptions)) {
                    $this->createReservationOptions($reservationId, $resOptions);
                }

                // if subscription special price -- set usage record

                if(isset($row['serviceData']['correctedDcPrice'])) {
                    $this->addSubscriptionUsageRecord($row, $reservationId);
                }
            }

            $needApproval = true;
            $row['need_approval'] = $needApproval;

        } else {

            $lockId = $ld['id'];
            $sheduleId = $ld['schedule_id'];

            $this->lockRecord->unlockSlots();

            $dbQuery = "SELECT
    					s.*,
		    			di.name,
		    			di.surname,
		    			c.name as clinic_name,
		    			c.payments_enabled as payments_enabled,
                        c.additional_data as clinicAdditionalData,
                        c.async_exchange_enabled,
    					cc.phone as clinic_phone,
    					cc.email as clinic_email,
		    			ci.address as clinic_address,
		    			c.terminal_id as terminal_id,
		    			d.hsp_resource_id as hsp_doctor_id,
		    			d.person_code as d_pk
							FROM `" . $this->cfg->getDbTable('shedule', 'self')	 . "` s
    							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self')	 . "` d ON (d.id = s.doctor_id)
    							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info')	 . "` di ON (d.id = di.doctor_id AND di.lang = '" . getDefaultLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self')	 . "` c ON (c.id = s.clinic_id)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info')	 . "` ci ON (ci.clinic_id = c.id AND ci.lang = '" . getDefaultLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts')	 . "` cc ON (c.id = cc.clinic_id AND cc.default = 1)				
							WHERE 1
								AND s.id = '" . mres($sheduleId) . "'
								AND (s.booked IS NULL OR s.booked = 0)
								AND s.start_time > '" . date(PIEARSTA_DT_FORMAT) . "'";
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows()) {

                /** @var array $row */
                $row = $query->getrow();

                $row['clinicAdditionalData'] = $row['clinicAdditionalData'] ? json_decode($row['clinicAdditionalData'], true) : null;

                // if appointment in the name of other patient

                if(!empty($otherPatient)) {
                    $row['appointmentMadeBy'] = $this->userData;
                    $row['inTheNameOfPatient'] = $otherPatient;
                }

                $row['serviceData'] = $selectedService;

                if (!$row['serviceData']['length_minutes'] || $row['serviceData']['length_minutes'] == 0) {
                    $row['serviceData']['length_minutes'] = $row['interval'];
                }

                if($dcDuration) {
                    $row['serviceData']['length_minutes'] = $dcDuration;
                    $slotsCount = ceil($dcDuration / $row['interval']);
                } else {
                    $slotsCount = ceil($row['serviceData']['length_minutes'] / $row['interval']);
                }

                $row['duration'] = $dcDuration ? $dcDuration : $row['serviceData']['length_minutes'];
                $row['shedule_slot_end_time'] = $row['end_time'];
                $endTime = new DateTime($row['start_time']);
                $endTime->modify("+" . $row['duration'] . " minutes");
                $row['end_time'] = $endTime->format(PIEARSTA_DT_FORMAT); // set new end_time = start_time + duration
                $row['notice'] = getP('notice');
                $row['profile_person_id'] = getP('personId');

                $slots = array();

                if ($slotsCount > 1) {

                    $dbQuery = "SELECT s.id, s.end_time, s.booked, s.need_approval
							FROM `" . $this->cfg->getDbTable('shedule', 'self') . "` s
							WHERE 1
								#AND s.booked IS NULL
								AND s.doctor_id = '" . $row['doctor_id'] . "'
								AND s.clinic_id = '" . $row['clinic_id'] . "'	
								AND s.start_time >= '" . $row['start_time'] . "'
								AND s.date = '" . $row['date'] . "'		
							ORDER BY start_time ASC
							LIMIT 0, " . $slotsCount;
                    $query = new query($this->db, $dbQuery);

                    if ($query->num_rows() != $slotsCount) {
                        $this->return['errors']['fields']['service_id'] = gL('order_Errors_Fields_serviceId_SlotsCount');
                        return false;
                    }

                    while ($slotRow = $query->getrow()) {
                        $slots[] = $slotRow['id'];

                        if($slotRow['need_approval'] !== '0' && $slotRow['need_approval'] !== 0) {
                            $needApproval = true;
                        }
                    }

                } else {

                    $slots[] = $sheduleId;

                    $dbQuery = "SELECT s.need_approval
							FROM `" . $this->cfg->getDbTable('shedule', 'self') . "` s
							WHERE s.id = $sheduleId";
                    $query = new query($this->db, $dbQuery);

                    if($query->num_rows()) {

                        $slotRow = $query->getrow();

                        if($slotRow['need_approval'] !== '0' && $slotRow['need_approval'] !== 0) {
                            $needApproval = true;
                        }
                    }
                }

                $row['need_approval'] = $needApproval;

                // // //
                // Another reservation existing check

                $checkConflictingBookings = $this->cfg->get('CheckConflictingBookings');

                if ($checkConflictingBookings) {

                    if ($personId) {
                        $where = " r.profile_person_id = '" . $personId . "' AND ";
                    } elseif (!empty($otherPatient)) {
                        $where = " (r.profile_id = '" . $otherPatient['id'] . "' AND (r.profile_person_id = '' OR r.profile_person_id IS NULL)) AND ";
                    } else {
                        $where = " (r.profile_id = '" . $this->userId . "' AND (r.profile_person_id = '' OR r.profile_person_id IS NULL)) AND ";
                    }

                    $resQuery = "SELECT r.id FROM mod_reservations r
                                            WHERE
                                                " . $where . " 
                                                r.start >= NOW() AND
                                                r.`status` IN ( 0, 2 ) AND
                                                (
                                                    ( r.start >= '" . $row['start_time'] . "' AND r.start < '" . $row['end_time'] . "' ) OR
                                                    ( r.end <= '" . $row['end_time'] . "' AND r.end > '" . $row['start_time'] . "' ) OR
                                                    ( r.start >= '" . $row['start_time'] . "' AND r.end <= '" . $row['end_time'] . "' ) OR
                                                    ( r.start <= '" . $row['start_time'] . "' AND r.end >= '" . $row['end_time'] . "' )
                                                )";
                    $resQ = new query($this->db, $resQuery);

                    if ($resQ->num_rows()) {
                        // user or his profile person already has reservation with time overlapping selected
                        $this->setPData(array(
                            'profilePerson' => $personId,
                            'resId' => $resQ->getrow()['id'],
                            'lockId' => $lockId,
                            'slots' => implode(',', $slots),
                        ), 'resData');

                        $this->lockRecord->deleteLockRecord(false);

                        $this->return['html'] = $this->tpl->output('reservation_time_already_reserved', $this->getPData());

                        if (DEBUG) {
                            $this->return['resQuery'] = $resQuery;
                        }

                        $this->return['warning_html'] = $this->tpl->output('reservation_time_already_reserved', $this->getPData());
                        return;
                    }
                }

                $needConfirmation = $this->needSmConfirmation($row['clinic_id']);

                if(!$needConfirmation) {
                    $needApproval = true;
                    $row['need_approval'] = $needApproval;
                }

                $lockStatus = LOCK_STATUS_CONFIRMED;
                $hspReservationId = null;

                $timeSlotsStatus = array(
                    'confirmed' => $lockStatus,
                    'hsp_reservation_id' => $hspReservationId,
                );

                // create reservation
//                $row['service_type'] = $isConsultation ? '1' : '0';
                $row['service_type'] = ($isConsultation || ($row['d_pk'] && $row['serviceData']['isRemote'])) ? '1' : '0';

                if($row['service_type'] == '1') {
                    $isConsultation = true;
                }

                // is this paid service?
                $isPaidService = $row['payments_enabled'] && isset($row['serviceData']['price']) && $row['serviceData']['price'] > 0 && $row['serviceData']['price'] != '0.00';

                $row['isPaidService'] = $isPaidService;

                // here we also check if price was not nulled by subscription

                if (
                    ($row['payment_type'] == '1' && !getP('country_agreement') && !$isPaidService && !isset($row['serviceData']['correctedDcPrice'])) ||
                    (!$isPaidService && !getP('country_agreement') && !isset($row['serviceData']['correctedDcPrice']))
                ) {
                    $this->return['errors']['fields']['country_agreement'] = gL('order_Errors_Fields_country_agreement_Empty');
                    return false;
                }

                // add person info if provided to $row data
                if($personId) {

                    $row = $this->addPersonData($personId, $row);

                } else {

                    $row['profile_person_id'] = null;
                }

                // add dc params to resOptions

                $resOptions = array();

                if($dc) {
                    $resOptions['dcAppointment'] = $dc;
                }

                if($dcChannelType) {
                    $resOptions['dcChannelType'] = $dcChannelType;
                }

                if($dcEntityName) {
                    $resOptions['dcEntityName'] = $dcEntityName;
                }

                if($dcDuration) {
                    $resOptions['dcDuration'] = $dcDuration;
                }

                if($dc) {
                    $resOptions['dcKid'] = $dcKid ? '1' : '0';
                }

                if($dcConsType) {
                    $resOptions['dcConsType'] = $dcConsType;
                }

                if($dcPhoneNumber) {
                    $resOptions['dcPhoneNumber'] = $dcPhoneNumber;
                }

                if($dcPrefferedLangs) {
                    $resOptions['dcPreferredLangs'] = $dcPrefferedLangs;
                }

                if($dcLang) {
                    $resOptions['dcLang'] = $dcLang;
                }

                if($dcServicesList) {
                    $resOptions['dcServicesList'] = $dcServicesList;
                }

                // we create new reservation only if we have not obtained ones id in request
                // this is necessary to  prevent creating res duplicates when using insurance or dc subscriptions

                if(!getP('resId')) {
                    $reservationId = $this->createReservation($row);
                } else {
                    $reservationId = getP('resId');

                    /** @var reservation $reservation */
                    $this->reservation = loadLibClass('reservation');
                    $this->reservation->setReservation($reservationId);
                }

                if(!empty($resOptions)) {
                    $this->createReservationOptions($reservationId, $resOptions);
                }

                if(isset($row['serviceData']['correctedDcPrice'])) {
                    $this->addSubscriptionUsageRecord($row, $reservationId);
                }

                // // //

                // if haveInsurance flag is set to true -- this means user has insurance police valid for this clinic
                // and has checked 'have insurance' chkBox

                $insuranceReservationOptions = null;

                if($isPaidService && $haveInsurance && !$person) {

                    $coveredByInsurance = false;

                    // TAKE THIS KEY FROM ADDITIONAL DATA OF LOCK RECORD
                    $isInsPoliceChecked = false;

                    if($this->lockRecord) {

                        $lr = $this->lockRecord->getLockRecord();

                        if($lr) {

                            $additionalData = $lr['additional_data'];

                            if($additionalData) {

                                $decodedAD = json_decode($additionalData, true);
                                $isInsPoliceChecked = $decodedAD['insuranceCoverageChecked'];

                                if($isInsPoliceChecked && !$needLocalInsuranceCheck) {

                                    $coveredByInsurance = $decodedAD['covers'];

                                    if($coveredByInsurance) {

                                        $this->addInsuranceReservationOptions($reservationId, $decodedAD);
                                    }
                                }
                            }
                        }
                    }

                    if(!$needLocalInsuranceCheck) {
                        $insuranceCompDontWorkWithClinic = !$this->clinicInsuranceCheck($row['clinic_id'], $this->userData['insurance_id']);
                    }

                    if(!$isInsPoliceChecked && $haveInsurance && !$insuranceCompDontWorkWithClinic) {

                        // Если полис есть и еще не проверен, то...
                        // Дополнительно проверяем годность полиса, если есть страховка и сервис платный
                        // только в случае, если в lockRecord additional_data не установлено, что полис проверен, покрывает данную услугу и пользователь выбрал использовать полис
                        // Если так, то идем по пути бесплатной резервации
                        // иначе, если флаг usePolice не установлен -- то обычная платная резервация -- отдаем order details popup

                        //

                        // if is paid service and user has insurance police we call check

                        // info for popup insurance

                        // we need to update lock record

                        // we should get min allowed coverage percentage fot service/clinic or for clinic

                        $minAllowedPcnt = null;

                        $pcntDbQuery = "
                            SELECT min_copay_pcnt FROM ins_min_allowed_copay_percentage mpcnt
                            LEFT JOIN ins_clinic_to_networks c2n ON (c2n.network_id = ".$row['clinic_id'].") 
                            WHERE
                                CASE
                                WHEN mpcnt.service_id IS NOT NULL AND mpcnt.clinic_id IS NOT NULL
                                THEN  
                                    (
                                        mpcnt.service_id = ".getP('serviceId')." AND 
                                        mpcnt.clinic_id = ".$row['clinic_id']."
                                    )
                                WHEN mpcnt.service_id IS NULL AND mpcnt.clinic_id IS NOT NULL
                                THEN  
                                    (
                                        mpcnt.service_id IS NULL AND 
                                        mpcnt.clinic_id = ".$row['clinic_id']."
                                    )
                                WHEN mpcnt.service_id IS NOT NULL AND mpcnt.clinic_id IS NULL AND mpcnt.network_id IS NOT NULL
                                THEN  
                                    (
                                        mpcnt.service_id = ".getP('serviceId')." AND
                                        mpcnt.clinic_id IS NULL AND 
                                        mpcnt.network_id = c2n.network_id AND 
                                        EXISTS ( SELECT id FROM ins_networks n WHERE n.id = mpcnt.network_id)  
                                    )
                                WHEN mpcnt.service_id IS NULL AND mpcnt.clinic_id IS NULL AND mpcnt.network_id IS NOT NULL
                                THEN  
                                    (
                                        mpcnt.service_id IS NULL AND
                                        mpcnt.clinic_id IS NULL AND 
                                        mpcnt.network_id = c2n.network_id AND 
                                        EXISTS ( SELECT id FROM ins_networks n WHERE n.id = mpcnt.network_id)  
                                    )
                                END
                        ";

                        $pcntQuery = new query($this->db, $pcntDbQuery);

                        if($pcntQuery->num_rows()) {
                            /** @var array $pcntRow */
                            $pcntRow = $pcntQuery->getrow();
                            $minAllowedPcnt = $pcntRow['min_copay_pcnt'];
                            $minAllowedPcnt = ($minAllowedPcnt === 0 || $minAllowedPcnt === '0' || $minAllowedPcnt === '0.00') ? '0.00' : $minAllowedPcnt;
                        }

                        // special field in lock record with insurance related info

                        $insFields = array(
                            'insuranceNumber' => $this->userData['insurance_number'],
                            'insuranceCompanyId' => $this->userData['insurance_id'],
                            'insuranceCompanyPAId' => $this->userData['insurancePaId'],
                            'insuranceCompany' => $this->userData['insurance'],
                            'insuranceStart' => $this->userData['insurance_start_date'],
                            'insuranceEnd' => $this->userData['insurance_end_date'],
                            'streetPrice' => $selectedService['priceWithoutCorrections'],
                            'insurancePrice' => $selectedService['price'],
                            'serviceData' => $selectedService,
                            'minCopayPcnt' => $minAllowedPcnt,
                            'agreeToUseInsurancePolice' => null,
                            'coveragePercentage' => null,
                            'additionalPayment' => null,
                            'insuranceCoverageChecked' => false,
                            'needLocalInsuranceCheck' => $needLocalInsuranceCheck,
                        );

                        // prepare data to update lock record
                        $lockData = array(
                            'reservation_id' => $reservationId,
                            'datetime_from' => $row['start_time'],
                            'datetime_thru' => $row['end_time'],
                            'slots' => implode(',', $slots),
                            'service_id' => getP('serviceId'),
                            'service_name' => $row['serviceData']['title'],
                            'additional_data' => json_encode($insFields),
                        );

                        if($personId) {
                            $lockData['third_person_id'] = $row['personData']['id'];
                            $lockData['third_person_name'] = $row['personData']['name'];
                            $lockData['third_person_surname'] = $row['personData']['surname'];
                            $lockData['third_person_phone'] = $row['personData']['phone'];
                            $lockData['third_person_email'] = $row['personData']['email'];
                            $lockData['third_person_lv_resident'] = $row['personData']['resident'];
                            $lockData['third_person_person_id'] = $row['personData']['person_id'];
                            $lockData['third_person_date_of_birth'] = $row['personData']['date_of_birth'];
                            $lockData['third_person_gender'] = $row['personData']['gender'];
                        }

                        $this->lockRecord->updateLockRecord($lockId, $lockData);

                        $row['isConsultation'] = $isConsultation;
                        $row['lockId'] = $lockId;
                        $row['reservationId'] = $reservationId;
                        $row['slots'] = implode(',', $slots);
                        $row['anyTime'] = $anyTime ? 1 : 0;
                        $row['selectedTime'] = $selectedTime;
                        $row['fromTSWidget'] = $fromTSWidget ? 1 : 0;
                        $row['needLocalInsuranceCheck'] = $needLocalInsuranceCheck ? 1 : 0;
                        $row['insuranceCompDontWorkWithClinic'] = $insuranceCompDontWorkWithClinic ? 1 : 0;


                        // we finish method and show insurance popup to user

                        $this->setPData($row, 'item');

                        $this->return['insurance_popup_html'] = $this->tpl->output('insurance_popup', $this->getPData());
                        $this->return['insurance_start_html'] = $this->tpl->output('insurance-start', $this->getPData());
                        $this->return['slots'] = $anyTime ? null : $slots;
                        $this->return['slotsString'] = $anyTime ? null : implode(',', $slots);
                        $this->return['orderInfo'] = $row;

                        return true;
                    }
                }

                // continue normal reservation process

                if($needConfirmation) {

                    $this->lockRecord->setStatus(LOCK_STATUS_PENDING);

                    // Run check
                    // Request to SM -- createReservation in method checkForSlotsBookingAbility

                    // in checkForSlotsBookingAbility we send request to SM to check reservation ability
                    // and to create blocker record on SM
                    // In the case of insurance payment we should pass insurance info as well

                    if($person) {
                        $coveredByInsurance = false;
                    }

                    $response = $this->checkForSlotsBookingAbility($row['terminal_id'], null, $coveredByInsurance);
                    $timeSlotsStatus = $response['response'];

                    if(DEBUG) {
                        $timeSlotsStatus['debug'] = $response;
                    }

                    // This is an error!!!
                    // we should free lock and show the message

                    if(!$row['async_exchange_enabled'] && !$timeSlotsStatus['hsp_reservation_id']) {

                        $delDbQuery = "DELETE FROM mod_reservations WHERE id = $reservationId";
                        doQuery($this->db, $delDbQuery);

                        $retArr = array(
                            'message' => gL('profile_schedule_not_available', 'This time is not available. Try to make an appointment to the other time'),
                            'lockId' => $lockId,
                            'reservationId' => $reservationId,
                            'dontCancelReservation' => true,
                        );

                        if(!empty($timeSlotsStatus['system_unavailable'])) {
                            // if there is not available external system we don't mark slot as booked
                            $retArr['message'] = gL('profile_schedule_system_not_available', 'Currently system is not available. Please try again later');
                            $retArr['dontCancelReservation'] = false;

                        } else {

                            // we should mark slot as booked, because the system is available, but this time can not be booked

                            $slotToBook = $scheduleId ? $scheduleId : (!empty($slots[0]) ? $slots[0] : null);

                            if($slotToBook) {
                                $retArr['slotToBook'] = $slotToBook;
                                $bookDbQuery = "UPDATE mod_shedules SET booked = 1 WHERE id = $slotToBook";
                                doQuery($this->db, $bookDbQuery);
                            }
                        }

                        if(DEBUG) {

                            $retArr['SMCheckResult'] = $timeSlotsStatus;
                        }

                        $this->return['error'] = $retArr;

                        return true;
                    }
                }

                // prepare data to update lock record
                $lockData = array(
                    'reservation_id' => null,
                    'hsp_reservation_id' => $timeSlotsStatus['hsp_reservation_id'],
                    'datetime_from' => $row['start_time'],
                    'datetime_thru' => $row['end_time'],
                    'slots' => implode(',', $slots),
                    'service_id' => getP('serviceId'),
                    'service_name' => $row['serviceData']['title'],
                    'status' => $timeSlotsStatus['confirmed'],
                );

                if($personId) {
                    $lockData['third_person_id'] = $row['personData']['id'];
                    $lockData['third_person_name'] = $row['personData']['name'];
                    $lockData['third_person_surname'] = $row['personData']['surname'];
                    $lockData['third_person_phone'] = $row['personData']['phone'];
                    $lockData['third_person_email'] = $row['personData']['email'];
                    $lockData['third_person_lv_resident'] = $row['personData']['resident'];
                    $lockData['third_person_person_id'] = $row['personData']['person_id'];
                    $lockData['third_person_date_of_birth'] = $row['personData']['date_of_birth'];
                    $lockData['third_person_gender'] = $row['personData']['gender'];
                }

                $this->lockRecord->updateLockRecord($lockId, $lockData);
            }
        }

        // update lock data
        if(!$isConsultation || !$anyTime) {
            saveValuesInDb('mod_shedules_lock', array('reservation_id' => $reservationId), $lockId);
        }

        $row['isConsultation'] = $isConsultation;
        $row['anyTime'] = $anyTime;
        $row['selectedTime'] = $selectedTime;
        $row['fromTSWidget'] = $fromTSWidget;

        // PAID SERVICE

//      При нажатии на кнопку (Pierakstīties или Turpināt ) cоздаем запись в таблице mod_reservations и обновляем mod_shedules_lock (время, reservation_id).
//      В случае если не установлен CHECK_SM,  то меняем статус в mod_shedules_lock на confirmed.
//      В случае если CHECK_SM установлен, отправляем запрос save_booking на API SmartMedical и обрабатываем полученный результат.

        // if paid service (with price) selected and not covered by insurance, we show order details popup

        if($isPaidService && !$coveredByInsurance) {

            if(!$isConsultation || !$anyTime) {

                // locking

                if($lockId) {

                    $lc = $this->lockRecord->getLockRecord();

                    if($lc) {
                        $lockResult = array(
                            'success' => true,
                            'slotCount' => count(explode(',', $lc['slots'])),
                            'slots' => $lc['slots'],
                            'lockRecordId' => $lockId,
                            'start_time' => $lc['datetime_from'],
                            'end_time' => $lc['datetime_thru'],
                        );
                    } else {
                        $lockResult = array(
                            'success' => false,
                            'slotCount' => 0,
                            'slots' => '',
                            'error' => 'Can\'t book this time!',
                            'lockRecordId' => null,
                        );
                    }

                } else {
                    $lockResult = array(
                        'success' => false,
                        'slotCount' => 0,
                        'slots' => '',
                        'error' => 'Can\'t book this time!',
                        'lockRecordId' => null,
                    );
                }

                // Check whether SM confirmation needed
                if($needConfirmation) {

                    // Check SM confirmation
                    if($timeSlotsStatus) {

                        // if there is no lockId -- this is error actually
                        if(!$lockId) {
                            try {
                                $lockResult = $this->lockSlot($sheduleId, $row['doctor_id'], $row['hsp_doctor_id'], $row['clinic_id'], strtotime($row['start_time']), $row['serviceData']['length_minutes'], $timeSlotsStatus, $preparedPersonData);
                            } catch (Exception $e) {
                                $lockResult['error'] = $e->getMessage();
                            }
                        } else {
                            lockSheduleData($row['start_time'], $row['end_time'], $row['doctor_id'], $row['clinic_id'], 1);
                        }
                    } else {
                        $this->return['error'] = array(
                            'message' => gL('profile_select_other_time'),
                        );
                    }
                } else {

                    // if there is no lockId -- this is consultation
                    if(!$lockId) {
                        try {
                            $lockResult = $this->lockSlot($sheduleId, $row['doctor_id'], $row['hsp_doctor_id'], $row['clinic_id'], strtotime($row['start_time']), $row['service_max_duration']);
                        } catch (Exception $e) {
                            $lockResult['error'] = $e->getMessage();
                        }
                    } else {
                        lockSheduleData($row['start_time'], $row['end_time'], $row['doctor_id'], $row['clinic_id'], 1);
                    }
                }

                if(!isset($lockResult['error'])) {
                    $row['lockRecordId'] = $lockResult['lockRecordId'];
                } else {
                    $this->return['error'] = $lockResult['error'];
                    return true;
                }
            }

            $row['price'] = $selectedService['price'];
            $row['service_description'] = $selectedService['service_description'];

            if(!$isConsultation || !$anyTime) {
                $row['sheduleId'] = $sheduleId;
                $row['start_time_date_month'] = date('d. ', strtotime($row['start_time'])) . gL("month_" . date('F', strtotime($row['start_time'])));
            } else {
                $row['sheduleId'] = null;
                $row['start_time_date_month'] = null;
            }

            $currDate = date(PIEARSTA_DT_FORMAT, time());
            $row['order_date'] = date('d. ', strtotime($currDate)) . gL("month_" . date('F', strtotime($currDate)));
            $row['userData'] = !empty($otherPatient) ? $otherPatient : $_SESSION['user'];
            $row['reservationId'] = $reservationId;

            // Prepare details data for all items
            $orderTotal = 0;
            $preparedDetailsData = array();
//                foreach ($row['serviceData'] as $key => $service) {
            $service = $row['serviceData'];
            $service['service_duration'] = $dcDuration ? $dcDuration : $service['length_minutes'];

            if(!$isConsultation || !$anyTime) {
                $service['start_time'] = $row['start_time'];
                $service['end_time'] = $row['end_time'];
            } else {
                $service['start_time'] = null;
                $service['end_time'] = null;
            }

            $service['service_type'] = $isConsultation ? '1' : '0';
            $itemData = $this->prepareOrderDetailsItemData($service);
            $preparedDetailsData[] = $itemData;
            $orderTotal += floatval($itemData['item_total']);
//                }
            $row['order_total'] = $orderTotal;

            // Create new order and order info
            // Set all fields for new order and order_info
            $preparedOrderData = $this->prepareOrderData($row);

            // get instance of Order class and call it's constructor with prepared order data
            /** @var order $order */
            $this->order = $order = &loadLibClass('order', true, $preparedOrderData['orders']);

            $order->setOrderInfo($preparedOrderData['order_info']);

            // Create order_details for all items in order
            foreach ($preparedDetailsData as $itemData) {
                $order->setOrderDetails($itemData);
            }

            $row['orderId'] = $order->getOrderId();
            $row['orderItems'] = $preparedDetailsData;
            $row['order_total'] = $orderTotal;
            $row['slots'] = $anyTime ? null : implode(',', $slots);
            $row['lockId'] = $anyTime ? null : getP('lockId');
            $row['bb'] = $this->cfg->get('bb');

            $lockData = array(
                'order_id' => $row['orderId'],
            );

            $reservationData = array(
                'service_price' => $row['order_total'],
                'order_id' => $row['orderId'],
            );

            if(!$anyTime) {
                $this->lockRecord->updateLockRecord($lockId, $lockData);
            }

            // if autoconfirmed received in sync check -- we set reservation to active status 2
            if(!empty($timeSlotsStatus['autoconfirmed']) && $timeSlotsStatus['autoconfirmed']) {
                $reservationData['status'] = 2;
                $reservationData['confirmed_at'] = date('Y-m-d H:i:s', time());
            }

            $this->reservation->updateReservation($reservationId, $reservationData);

            if(DEBUG) {
                $this->return['reservation'] = $this->reservation->getReservation();
            }

            /** @var array $billingSystemCfg */
            $billingSystemCfg = $this->cfg->get('billing_system');

            $row['banklinks'] = array_filter($billingSystemCfg['banklinks'], function ($bank) {
                return ($bank['active'] == 1);
            });

            $row['oldPaymentType'] = $this->cfg->get('oldPaymentType');

            $row['date'] = $order->getOrder()['date'];
            $row['serviceDuration'] = $order->getServiceDuration();
            $row['doctor_name'] = $row['name'];
            $row['doctor_surname'] = $row['surname'];

            $orderData = $this->getOrderInfoPopupData($row['orderId']);

            $row['order_html'] = $this->getOrderHtml($orderData);

            if($inTheNameOfPatient && !empty($otherPatient)) {

                // set other expiration time for lock record

                $this->lockRecord->setInTheNameExpirationTime(); // sets exp time based on config value
                lockSheduleData($row['start_time'], $row['end_time'], $row['doctor_id'], $row['clinic_id'], 1);

                // FOR InTheNameOfPatient Appointment for paid reservation
                // here we construct a link and send it to patient for whom reservation created
                // and return info popup to initiator that reservation created and is waiting for patient confirmation
                // and payment

                $result = $this->createAndSendReservationResumeLink($reservationId);
                $this->setPData($result, "inTheNameAppointmentResult");
                $this->setPData( ($this->cfg->get('shedule_lock_time_in_the_name_of') / 3600), "inTheNameAppointmentLockTime");

                $this->return['inTheNameResultPopup'] = true;
                $this->return['html'] = $this->tpl->output('in-the-name-result-popup', $this->getPData());

            } else {

                // // // PROMO CODE // // //
                // check for promo code availability

                $promoAvailable = false;

                if(
                    empty($selectedService['priceWithoutCorrections']) ||
                    ($selectedService['price'] == $selectedService['priceWithoutCorrections'])
                ) {

                    $promoDbQuery = "
                                    SELECT SUM(a.c) as codes_count FROM
                                    (SELECT COUNT(p.id) as c FROM promo_codes p
                                    WHERE
                                        p.clinic_id = " . $row['clinic_id'] . " AND 
                                        '" . $row['start_time'] . "' >= p.start_datetime AND 
                                        '" . $row['start_time'] . "' <= p.end_datetime AND 
                                        (
                                            JSON_CONTAINS(p.services, '" . $selectedService['c_id'] . "', '$') OR
                                            JSON_CONTAINS(p.services, '\"*\"', '$') 
                                        ) 
                                    UNION ALL
                                        SELECT COUNT(p.id) as c FROM promo_codes p
                                        INNER JOIN ins_clinic_to_networks AS c2n ON ( c2n.clinic_id = " . $row['clinic_id'] . " AND c2n.start_datetime <= '" . $row['start_time'] . "' AND c2n.end_datetime >= '" . $row['start_time'] . "' )
                                        WHERE
                                            p.clinic_id IS NULL AND
                                            c2n.network_id = p.network_id AND
                                            '" . $row['start_time'] . "' >= p.start_datetime AND 
                                            '" . $row['start_time'] . "' <= p.end_datetime AND
                                            (
                                            JSON_CONTAINS(p.services, '" . $selectedService['c_id'] . "', '$') OR
                                            JSON_CONTAINS(p.services, '\"*\"', '$') 
                                        )) a
                                    ";

                    $promoQuery = new query($this->db, $promoDbQuery);

                    $codesCount = 0;

                    if($promoQuery->num_rows()) {
                        /** @var array $cRow */
                        $cRow = $promoQuery->getrow();
                        $codesCount = intval($cRow['codes_count']);
                    }

                    $promoAvailable = $codesCount > 0;
                }

                if($promoAvailable) {
                    $row['promoAvailable'] = $promoAvailable;
                }

                $this->setPData($row, "item");

                if(!$anyTime) {
                    $this->setPData(implode(',', $slots), "slots");
                }

                if($dc) {
                    $this->setPData($dc, "dcAppointment");
                }

                if(!empty($resOptions) && is_array($resOptions)) {
                    $this->setPData($resOptions, "resOptions");
                }

                //

                $this->return['html'] = $this->tpl->output('order-details-popup', $this->getPData());
                $this->return['slots'] = $anyTime ? null : $slots;
                $this->return['slotsString'] = $anyTime ? null : implode(',', $slots);
                $this->return['orderInfo'] = $row;
                //$this->return['pData'] = $this->getPData();
                $this->return['SMCheckResult'] = $timeSlotsStatus;
            }

        } else {

            // FREE SERVICE

            $row['slots'] = $anyTime ? null : $slots;

            if($inTheNameOfPatient && !empty($otherPatient)) {

                // set other expiration time for lock record

                $this->lockRecord->setInTheNameExpirationTime(); // sets exp time based on config value
                lockSheduleData($row['start_time'], $row['end_time'], $row['doctor_id'], $row['clinic_id'], 1);

                // FOR InTheNameOfPatient Appointment for free reservation
                // here we construct a link and send it to patient for whom reservation created
                // and return info popup to initiator that reservation created and is waiting for patient confirmation

                $result = $this->createAndSendReservationResumeLink($reservationId);
                $this->setPData($result, "inTheNameAppointmentResult");
                $this->setPData( ($this->cfg->get('shedule_lock_time_in_the_name_of') / 3600), "inTheNameAppointmentLockTime");

                $this->return['inTheNameResultPopup'] = true;
                $this->return['html'] = $this->tpl->output('in-the-name-result-popup', $this->getPData());

            } else {

                // normal reservation process

                if(
                    !$anyTime &&
                    isset($timeSlotsStatus) &&
                    (
                        $timeSlotsStatus['confirmed'] == LOCK_STATUS_AUTOCONFIRMED ||
                        $timeSlotsStatus['confirmed'] == LOCK_STATUS_CONFIRMED
                    )
                ) {

                    $params = array(
                        'sheduleId' => $sheduleId,
                        'reservationId' => $reservationId,
                        'lockedSlots' => getP('lockedSlots'),
                        'lockId' => getP('lockId'),
                        'slots' => $slots,
                        'item' => $row,
                        'SMCheckResult' => $timeSlotsStatus,
                        'anyTime' => $anyTime,
                    );

                    $this->freeServiceReservation($params);

                    if($isConsultation) {

                        $this->reservation->setReservation($params['reservationId']);
                        $resData = $this->reservation->getReservation();

                        if (
                            (empty($resData['consultation_vroom']) && empty($resData['consultation_vroom_doctor'])) &&
                            empty($resData['vroom_create_required'])
                        ) {

                            // here we should only set the flag 'vroom_create_required' to '1'
                            // the logic that creates vroom is moved to cronjob 'vrooms_create.php'

                            $data = array(
                                'vroom_create_required' => 1,
                            );

                            $this->reservation->updateReservation($this->reservation->getReservationId(), $data);
                        }

//                        /** @var consultation $consObj */
//                        $consObj = loadLibClass('consultation');
//                        $this->userData['lang'] = !empty($this->userData['lang']) ? $this->userData['lang'] : $this->getLang();
//                        $response = $consObj->createVroom($params['reservationId']);
//
//                        $this->return['createVroom'] = $response;
                    }


                } elseif ($anyTime) {

                    $params = array(
                        'sheduleId' => null,
                        'reservationId' => $reservationId,
                        'lockedSlots' => null,
                        'lockId' => null,
                        'slots' => null,
                        'item' => $row,
                        'SMCheckResult' => null,
                        'anyTime' => $anyTime,
                    );

                    $this->freeServiceReservation($params);

                    if($isConsultation) {

                        $this->reservation->setReservation($params['reservationId']);
                        $resData = $this->reservation->getReservation();

                        if (
                            (empty($resData['consultation_vroom']) && empty($resData['consultation_vroom_doctor'])) &&
                            empty($resData['vroom_create_required'])
                        ) {

                            // here we should only set the flag 'vroom_create_required' to '1'
                            // the logic that creates vroom is moved to cronjob 'vrooms_create.php'

                            $data = array(
                                'vroom_create_required' => 1,
                            );

                            $this->reservation->updateReservation($this->reservation->getReservationId(), $data);
                        }

//                        /** @var consultation $consObj */
//                        $consObj = loadLibClass('consultation');
//                        $this->userData['lang'] = !empty($this->userData['lang']) ? $this->userData['lang'] : $this->getLang();
//                        $response = $consObj->createVroom($params['reservationId']);
//
//                        $this->return['createVroom'] = $response;
                    }

                } else {

                    $this->return['error'] = array(
                        'sheduleId' => $sheduleId,
                        'reservationId' => $reservationId,
                        'lockId' => getP('lock_id'),
                        'lockedSlots' => getP('lockedSlots'),
                        'slots' => implode(',', $slots),
                        'item' => $row,
                        'message' => gL('profile_select_other_time'),
                        'SMCheckResult' => $timeSlotsStatus,
                    );
                }
            }
        }
    }

    /**
     * @param array $row
     * @return bool|string
     */
    private function createReservation(array $row) {

        $patId = $this->userId;
        $madeByProfileId = null;
        $status = $row['isPaidService'] ? RESERVATION_WAITS_PAYMENT : RESERVATION_WAITS_CONFIRMATION;

        // patient make an appointment in the name of other patient

        if(
            (!empty($row['inTheNameOfPatient']) && !empty($row['appointmentMadeBy'])) &&
            (!empty($row['inTheNameOfPatient']['id']) && !empty($row['appointmentMadeBy']['id'])) &&
            ($row['inTheNameOfPatient']['id'] != $row['appointmentMadeBy']['id'])
        ) {
            $patId = $row['inTheNameOfPatient']['id'];
            $madeByProfileId = $row['appointmentMadeBy']['id'];
            $status = RESERVATION_WAITS_PATIENT_CONFIRMATION;
        }

        // create reservation
        // prepare params
        $reservationData = array(
            'shedule_id' => $row['id'],
            'profile_id' => $patId,
            'created' => time(),
            'updated' => time(),
            'status_changed_at' => time(),
            'doctor_id' => $row['doctor_id'],
            'clinic_id' => $row['clinic_id'],
            'service_id' => getP('serviceId'),
            'service_type' => $row['service_type'],
            'profile_person_id' => $row['profile_person_id'],
            'payment_type' => $row['payment_type'],
            'start' => $row['start_time'],
            'end' => $row['end_time'],
            'sms_notification' => getP('sms_notification'),
            'notice' => $row['notice'],
            'status' => $status,
            'sended' => '1',
            'need_approval' => $row['need_approval'] ? '1' : '0',
        );

        if(!empty($madeByProfileId)) {
            $reservationData['made_by_profile_id'] = $madeByProfileId;
        }

        $row['notice'] = getP('notice');
        $row['profile_person_id'] = $row['personId'];

        /** @var reservation $reservation */
        $this->reservation = loadLibClass('reservation');
        return $this->reservation->createReservation($reservationData);
    }

    /**
     * @param $resId
     * @param $options
     * @return bool|int|string
     */
    private function createReservationOptions($resId, $options) {

        $data = array(
            'reservation_id' => $resId,
            'options' => json_encode($options),
        );

        return saveValuesInDb('mod_reservation_options', $data);
    }

    private function addSubscriptionUsageRecord($item, $reservationId)
    {
        // create subscription usage record (we use the same table as for promo codes usage)

        $usageRecord = array(
            'reservation_id' => $reservationId,
            'profile_id' => $this->userData['id'],
            'subscription_id' => $this->userData['dcSubscription']['id'],
            'status' => '0',
            'service_id' => $item['serviceData']['c_id'],
            'discounted_price' => $item['serviceData']['correctedDcPrice'],
            'regular_price' => $item['serviceData']['priceWithoutCorrections'],
            'created_at' => date('Y-m-d H:i:s', time()),
            'updated_at' => date('Y-m-d H:i:s', time()),
        );

        saveValuesInDb('promo_usage', $usageRecord);
    }

    private function addInsuranceReservationOptions($resId, $options) {

        $dbQuery = "SELECT * FROM mod_reservation_options WHERE reservation_id = " . $resId . " ORDER BY id DESC LIMIT 1";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            $row = $query->getrow();

            try {
                $existingOpts = json_decode($row['options'], true);
            } catch (Exception $e) {
                $existingOpts = array();
            }

            $existingOpts['insuranceOptions'] = $options;
            $data = json_encode($existingOpts);

            return saveValuesInDb('mod_reservation_options', $data, $resId);

        } else {

            $existingOpts = array(
                'insuranceOptions' => $options,
            );

            $data = array(
                'reservation_id' => $resId,
                'options' => json_encode($existingOpts),
            );

            return saveValuesInDb('mod_reservation_options', $data);
        }
    }

    public function showInsuranceEditForm() {

        $classificators['ic'] = $this->cl->getListByType(CLASSIF_IC);
        $this->setPData($classificators, 'cl');

        $res = $this->prepareInsurancePopupData();
        $item = $res['item'];
        $dcAppointment = $res['dcAppointment'];

        $this->setPData($item, 'hiddens');
        $this->setPData($dcAppointment, 'dcAppointment');

        $this->return['insurance_edit_html'] = $this->tpl->output('insurance-edit-form', $this->getPData());
    }

    public function showInsuranceStart() {

        $res = $this->prepareInsurancePopupData();
        $item = $res['item'];
        $dcAppointment = $res['dcAppointment'];

        $this->setPData($item, 'item');
        $this->setPData($dcAppointment, 'dcAppointment');

        $this->return['insurance_start_html'] = $this->tpl->output('insurance-start', $this->getPData());
    }

    public function saveInsuranceData() {

        $insNumber = getP('insurance_number');
        $insCompId = getP('insurance_id');
        $insStartDate = getP('insurance_start_date');
        $insEndDate = getP('insurance_end_date');
        $lockId = getP('lockId');
        $lr = null;

        $data = array();

        $data['insurance_id'] = $insCompId;
        $data['insurance_number'] = $insNumber;

        if($insStartDate) {
            $data['insurance_start_date'] = date('Y-m-d', strtotime($insStartDate)) . ' 00:00:00';
        } else {
            $data['insurance_start_date'] = 'null';
        }

        if($insEndDate) {
            $data['insurance_end_date'] = date('Y-m-d', strtotime($insEndDate)) . ' 23:59:59';
        } else {
            $data['insurance_end_date'] = 'null';
        }

        saveValuesInDb('mod_profiles', $data, $this->userData['id']);

        $this->collectUserData($this->userData['id']);

        // local check again

        $item['insuranceCompDontWorkWithClinic'] = !$this->clinicInsuranceCheck(getP('clinic_id'), $insCompId);
        $clinicWorksWithInsurance = !$item['insuranceCompDontWorkWithClinic'];

        $insurancePoliceNotStarted = true; // police not expired?
        $insurancePoliceExpired = true; // police started?
        $insuranceIncompleteData = false; // is it enough data about insurance in user profile?

        if($lockId) {

            if (!$this->lockRecord) {

                /** @var  lockRecord */
                $this->lockRecord = loadLibClass('lockRecord');
            }

            $this->lockRecord->setLockRecord($lockId);
            $lr = $this->lockRecord->getLockRecord();
        }

        $nowDT = date('Y-m-d H:i:s', time());
        $startDT = date('Y-m-d H:i:s', strtotime($lr['datetime_from']));

        if($clinicWorksWithInsurance) {

            // DO we check insurance expiration now by user entered data
            // check for now datetime and for reservation start datetime

            if(empty($this->userData['insurance_start_date']) || empty($this->userData['insurance_end_date'])) {

                $insuranceIncompleteData = true;

            } else {

                if(
                    $nowDT >= $this->userData['insurance_start_date'] &&
                    $startDT >= $this->userData['insurance_start_date']
                ) {
                    $insurancePoliceNotStarted = false;
                }

                if(
                    $nowDT < $this->userData['insurance_end_date'] &&
                    $startDT < $this->userData['insurance_end_date']
                ) {
                    $insurancePoliceExpired = false;
                }
            }
        }

        // update insData in lock table with new values

        if($lockId) {

            if(!empty($lr['additional_data'])) {

                $insData = json_decode($lr['additional_data'], true);

                // update ins company info if changed

                if($insCompId != $insData['insuranceCompanyId']) {

                    $dbQuery = "
                        SELECT c.id, c.piearstaId, ci.title FROM mod_classificators c
                        LEFT JOIN mod_classificators_info ci ON (ci.c_id = c.id)
                        WHERE
                            c.id = $insCompId
                    ";

                    $query = new query($this->db, $dbQuery);

                    if($query->num_rows()) {

                        $row = $query->getrow();

                        $insData['insuranceCompanyId'] = $row['id'];
                        $insData['insuranceCompanyPAId'] = $row['piearstaId'];
                        $insData['insuranceCompany'] = $row['title'];
                    }
                }

                // update other insurance police related values

                $insData['insuranceNumber'] = $insNumber;
                $insData['insuranceStart'] = $insStartDate;
                $insData['insuranceEnd'] = $insEndDate;

                // check for special price

                $debug = array();
                $debug['$insurancePoliceExpired'] = $insurancePoliceExpired;
                $debug['$insurancePoliceNotStarted'] = $insurancePoliceNotStarted;
                $debug['$insuranceIncompleteData'] = $insuranceIncompleteData;

                $debug['userData_insurance_start_date'] = $this->userData['insurance_start_date'];
                $debug['nowDT'] = $nowDT;
                $debug['startDT'] = $startDT;


                if(!$insuranceIncompleteData && !$insurancePoliceNotStarted && !$insurancePoliceExpired) {

                    $dbQuery = "
                            SELECT a.* 
                            FROM 
                            (
                                SELECT sp.price, 1 priority FROM ins_insurance_special_prices AS sp
                                WHERE 
                                    sp.comp_id = ".$insData['insuranceCompanyId']." AND 
                                    sp.clinic_id = " . $lr['clinic_id'] . " AND 
                                    sp.service_id = " . $insData['serviceData']['c_id'] . " AND 
                                    '" . $startDT . "' >= sp.start_datetime AND 
                                    '" . $startDT . "' <= sp.end_datetime
                                UNION ALL
                                    SELECT min(sp.price), 0 priority FROM ins_insurance_special_prices AS sp
                                    INNER JOIN ins_clinic_to_networks AS c2n ON ( c2n.clinic_id = " . $lr['clinic_id'] . " AND c2n.start_datetime <= '" . $startDT . "' AND c2n.end_datetime >= '" . $startDT . "' )
                                    WHERE 
                                        sp.comp_id = ".$insData['insuranceCompanyId']." AND
                                        sp.clinic_id IS NULL AND 
                                        sp.service_id = " . $insData['serviceData']['c_id'] . " AND 
                                        c2n.network_id = sp.network_id AND 
                                        '" . $startDT . "' >= sp.start_datetime AND 
                                        '" . $startDT . "' <= sp.end_datetime
                            ) a
                            ORDER BY a.priority DESC, a.price ASC
                            LIMIT 1
                        ";

                    $query = new query($this->db, $dbQuery);

                    $debug['checkSpecialPrice'] = 'here';
                    $debug['checkSpecialPriceSQL'] = $dbQuery;

                    if($query->num_rows()) {

                        /** @var array $servRow */
                        $servRow = $query->getrow();

                        $debug['checkSpecialPriceRESULT'] = $servRow;

                        if($servRow['price'] !== null) {

                            $insData['serviceData']['correctedInsPrice'] = $servRow['price'];

                            // we apply corrected price only if it is less than original price

                            if($servRow['price'] < $insData['serviceData']['price']) {
                                $insData['serviceData']['price'] = $servRow['price'];
                                $insData['insurancePrice'] = $servRow['price'];

                                // We already have services info in session and we should update it with new price!
                                $services = $_SESSION['services'];
                                $serviceId = $insData['serviceData']['c_id'];
                                $selectedService = $services[array_search($serviceId, array_column($services, 'c_id'))];
                                $selectedService['price'] = $servRow['price'];

                                $services[array_search($serviceId, array_column($services, 'c_id'))] = $selectedService;
                                $_SESSION['services'] = $services;
                            }
                        }
                    }
                }

                $debug['afterCheck_InsData'] = $insData;

                $lrData = array(
                    'additional_data' => json_encode($insData),
                );

                // save updated additional data to lock record

                saveValuesInDb('mod_shedules_lock', $lrData, $lockId);
            }
        }

        $res = $this->prepareInsurancePopupData();
        $item = $res['item'];
        $dcAppointment = $res['dcAppointment'];

        // set flag to detect whether it is need to check local insurance data before proceed
        $item['needLocalInsuranceCheck'] = $insuranceIncompleteData || $insurancePoliceNotStarted || $insurancePoliceExpired;

        $this->setPData($item, 'item');
        $this->setPData($dcAppointment, 'dcAppointment');

        if(DEBUG) {
            $this->return['debug'] = $debug;
            $this->return['item'] = $item;
        }

        $this->return['insurance_start_html'] = $this->tpl->output('insurance-start', $this->getPData());
    }

    /**
     * @return array
     */
    private function prepareInsurancePopupData() {

        $item = array(
            'id' => getP('sheduleId'),
            'clinic_id' => getP('clinic_id'),
            'doctor_id' => getP('doctor_id'),
            'streetPrice' => getP('serviceStreetPrice'),
            'insPrice' => getP('serviceInsurancePrice'),
            'serviceData' => array(
                'c_id' => getP('serviceId'),
                'title' => getP('serviceName'),
            ),
            'slots' => getP('slots'),
            'lockId' => getP('lock_id'),
            'reservationId' => getP('reservation_id'),
            'notice' => getP('note'),
            'isConsultation' => getP('isConsultation'),
            'anyTime' => getP('anyTime'),
            'selectedTime' => getP('selectedTime'),
            'profile_person_id' => getP('personId'),
            'fromTSWidget' => getP('fromTSWidget'),
            'insurance' => 1,
            'needLocalInsuranceCheck' => getP('needLocalInsuranceCheck'),
            'insuranceCompDontWorkWithClinic' => getP('insuranceCompDontWorkWithClinic'),
        );

        $dcAppointment = getP('dc');

        return array(
            'item' => $item,
            'dcAppointment' => $dcAppointment,
        );
    }

    /**
     * @param $clinicId
     * @param $insCompId
     * @return bool
     */
    private function clinicInsuranceCheck($clinicId, $insCompId)
    {
        $dbQuery = "
            SELECT * FROM mod_clinics_to_classificators 
            WHERE
                clinic_id = ".$clinicId." AND 
                cl_id = ".$insCompId." AND 
                cl_type = 5
        ";

        $query = new query($this->db, $dbQuery);

        return $query->num_rows() > 0;
    }

    public function checkInsurance()
    {
        // get lock data

        if(!$this->lockRecord) {

            /** @var  lockRecord */
            $this->lockRecord = loadLibClass('lockRecord');
            $this->lockRecord->setLockRecord(getP('lock_id'));
        }

        $lr = $this->lockRecord->getLockRecord();
        $insData = json_decode($lr['additional_data'], true);

        $insData['hsp_resource_id'] = $lr['hsp_doctor_id'];
        $insData['clinic_id'] = $lr['clinic_id'];

        /** @var insurance $insObj */
        $insObj = loadLibClass('insurance');

        $resRaw = $insObj->insuranceCheck($insData, $this->userData);
        $resResult = $resRaw['result'];

        $res = $this->prepareInsurancePopupData();
        $item = $res['item'];
        $dcAppointment = $res['dcAppointment'];

        $item['insCheck'] = array(
            'insCheckStatus' => !empty($resResult['status']) ? $resResult['status'] : 'error',
            'coverage' => null,
            'servicePrice' => number_format(floatval($insData['insurancePrice']), 2),
            'minCopayFloat' => number_format(floatval($insData['minCopayPcnt']), 2),
            'pcnt' => null,
            'addPay' => null,
            'covers' => false,
            'serviceSM' => null,
            'checkDatetime' => null,
        );

        $insData['insuranceCoverageChecked'] = true;

        if($resResult['success'] && $resResult['policeOK']) {

            // calculate percentage of coverage

            $coverage = floatval($resResult['coverage']);
            $price = floatval($insData['insurancePrice']);
            $minCopayPcnt = floatval($insData['minCopayPcnt']);
            $pcnt = $coverage / $price * 100;
            $covers = $pcnt >= $minCopayPcnt;

            // if no percentage record or percentage is null
            // we don't allow to pay with insurance
            if($insData['minCopayPcnt'] === null) {
                $covers = false;
            }

            $addPay = $price - $coverage;
            $addPay = $addPay > 0 ? $addPay : 0;
            $serviceSM = $resResult['serviceSM'];
            $checkDatetime = $resResult['checkDatetime'];
            $insCheckDate = date('d.m.Y', strtotime($checkDatetime));
            $insCheckTime = date('H:i', strtotime($checkDatetime));

            $item['insCheck'] = array(
                'insCheckStatus' => !empty($resResult['status']) ? $resResult['status'] : 'error',
                'coverage' => number_format($coverage, 2),
                'servicePrice' => number_format($price, 2),
                'minCopayFloat' => number_format($minCopayPcnt, 2),
                'pcnt' => number_format($pcnt, 2),
                'addPay' => number_format($addPay, 2),
                'covers' => $covers,
                'servicePiearstaId' => $resResult['servicePiearstaId'],
                'serviceSM' => $serviceSM,
                'checkDatetime' => $checkDatetime,
                'insCheckDate' => $insCheckDate,
                'insCheckTime' => $insCheckTime,
            );

            // Update additional data of lock record with obtained ins data

            $insData['additionalPayment'] = $item['insCheck']['addPay'];
            $insData['coveragePercentage'] = $item['insCheck']['pcnt'];
            $insData['insuranceCompensation'] = $item['insCheck']['coverage'];
            $insData['covers'] = $item['insCheck']['covers'];
            $insData['servicePiearstaId'] = $item['insCheck']['servicePiearstaId'];
            $insData['serviceSM'] = $item['insCheck']['serviceSM'];
            $insData['checkDatetime'] = $item['insCheck']['checkDatetime'];
            $insData['insCheckDate'] = $item['insCheck']['insCheckDate'];
            $insData['insCheckTime'] = $item['insCheck']['insCheckTime'];
        }

        $data = array(
            'additional_data' => json_encode($insData),
        );

        saveValuesInDb('mod_shedules_lock', $data, $lr['id']);

        $this->return['lockData'] = $lr;
        $this->return['insData'] = $insData;
        $this->return['requestResult'] = $resResult;
        $this->return['coverageCheckResult'] = $item['insCheck'];

        // show check result popup

        $this->setPData($item, 'item');
        $this->setPData($dcAppointment, 'dcAppointment');

        $this->return['insurance_result_html'] = $this->tpl->output('insurance-result', $this->getPData());
    }


    // PROMO CODES

    public function checkPromoCode()
    {
        $promo = getP('promoCode');
        $resId = getP('reservationId');

        if(!$promo || !$resId) {
            $this->return = array(
                'success' => false,
                'message' => 'Incomplete data',
            );
            return;
        }

        if(!$this->reservation) {

            /** @var  reservation */
            $this->reservation = loadLibClass('reservation');
        }

        $this->reservation->setReservation($resId);
        $resData = $this->reservation->getReservation();

        $promoCodeRecord = null;
        $setFree = false;
        $success = false;

        $dbQuery = "SELECT pc.* FROM promo_codes pc
                    LEFT JOIN ins_clinic_to_networks AS c2n ON ( c2n.clinic_id = " . $resData['clinic_id'] . " AND c2n.start_datetime <= '" . $resData['start'] . "' AND c2n.end_datetime >= '" . $resData['start'] . "' )
                    WHERE 
                          pc.code = '" . $promo . "' AND 
                          pc.status = 1 AND 
                            (
                                pc.clinic_id = " . $resData['clinic_id'] . " OR 
                                (
                                    pc.clinic_id IS NULL AND
                                    c2n.network_id = pc.network_id
                                )
                            ) AND 
                        '" . $resData['start'] . "' >= pc.start_datetime AND 
                        '" . $resData['start'] . "' <= pc.end_datetime AND 
                        (
                            JSON_CONTAINS(pc.services, '" . $resData['service_id'] . "', '$') OR
                            JSON_CONTAINS(pc.services, '\"*\"', '$') 
                        )";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            /** @var array $promoCodeRecord */
            $promoCodeRecord = $query->getrow();
        }

        // for fast debug only -- accepts promo codes like XXX_<discount_percent_integer> (0-100)

        if(DEBUG) {

            if(strpos($promo, 'XXX_') !== false) {

                $cheatCodeArr = explode('_', $promo);

                $promoCodeRecord = array(
                    'discount' => $cheatCodeArr[1],
                    'id' => 99999,
                );
            }
        }

        if(!empty($promoCodeRecord)) {

            $success = true;

            // calculate new price

            $price = (float)$resData['service_price'];
            $discount = (float)$promoCodeRecord['discount'];
            $newPrice = number_format($price - (($price / 100) * $discount), 2, '.', '');

            // create promocode usage record

            $usageRecord = array(
                'reservation_id' => $resId,
                'profile_id' => $this->userData['id'],
                'promocode_id' => $promoCodeRecord['id'],
                'status' => '0',
                'service_id' => $resData['service_id'],
                'discounted_price' => $newPrice,
                'regular_price' => $resData['service_price'],
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time()),
            );

            saveValuesInDb('promo_usage', $usageRecord);

            if($newPrice == '0.00') {

                // remove order and set 0 price to reservation and lock record

                $setFree = true;

                $dbQuery = "DELETE FROM mod_orders WHERE id = " . $resData['order_id'];
                doQuery($this->db, $dbQuery);

                $data = array(
                    'order_id' => 'null',
                    'service_price' => 'null',
                );

                saveValuesInDb('mod_reservations', $data, $resId);

                $dbQuery = "UPDATE mod_shedules_lock SET order_id = null WHERE reservation_id = " . $resId;
                doQuery($this->db, $dbQuery);

            } else {

                // set new price to reservation

                $data = array(
                    'service_price' => $newPrice,
                );

                saveValuesInDb('mod_reservations', $data, $resId);

                // update order

                $data = array(
                    'order_total' => $newPrice,
                );

                saveValuesInDb('mod_orders', $data, $resData['order_id']);

                // update order details

                $dbQuery = "UPDATE mod_order_details 
                            SET
                                price = $newPrice,
                                item_total = $newPrice
                            WHERE
                                order_id = " . $resData['order_id'];

                doQuery($this->db, $dbQuery);
            }
        }

        $this->return = array(
            'success' => $success,
            'newPrice' => $newPrice,
            'setFree' => $setFree,
        );

        if(DEBUG) {

            $this->return['debug'] = array(
                'promoRecord' => $promoCodeRecord,
                'reservation' => $resData,
            );
        }
    }

    // // ///
    // TFA methods
    // // ///

    public function showTfaConfigurePopup()
    {
        if(!$this->tfa) {
            $this->tfa = loadLibClass('Tfa');
        }

        $this->tfa->setUserData($this->userData);
        $this->tfaRemoveCode();
        $res = $this->tfa->generateNewTfa();

        $item = array(
            'secret' => $res['secret'],
            'qr' => $res['qr'],
        );

        $this->return['item'] = $item;
        $this->setPData($item, 'item');

        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
        $this->return['html_popup'] = $this->tpl->output('tfa-popup', $this->getPData());
        $this->return['html_content'] = $this->tpl->output('tfa-configure-content', $this->getPData());
    }

    public function showTfaConfigurePopupCodeInput()
    {
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
        $this->return['html_content'] = $this->tpl->output('tfa-configure-content-code-input', $this->getPData());
    }

    public function tfaConfigureCheckCode()
    {
        if(!$this->tfa) {
            $this->tfa = loadLibClass('Tfa');
        }

        $code = getP('code');

        $this->tfa->setUserData($this->userData);
        $res = $this->tfa->checkCodeOnConfigure($code);

        $error = !$res['success'];

        $item = array(
            'checked' => true,
            'code' => $code,
            'message' => 'Checked!',
            'wrongCode' => $error,
        );

        if(DEBUG) {
            $item['debug'] = $res;
        }

        $this->return['item'] = $item;
        $this->setPData($item, 'item');
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');

        if($error) {

            $this->return['html_content'] = $this->tpl->output('tfa-configure-content-code-input', $this->getPData());

        } else {

            $this->return['html_content'] = $this->tpl->output('tfa-configure-result-content', $this->getPData());
        }
    }

    public function tfaRemoveCode($userId = null)
    {
        if(!$this->tfa) {
            $this->tfa = loadLibClass('Tfa');
        }

        if(empty($this->userData) && empty($userId)) {
            return false;
        }

        if(!empty($this->userData)) {
            $this->tfa->setUserData($this->userData);
            $res = $this->tfa->removeKey();
            unset($this->userData['tfa']);
            unset($_SESSION['user']['tfa']);
        } else {
            $res = $this->tfa->removeKey($userId);
        }

        $this->return['result'] = $res;
    }

    public function tfaShowAuthPopup()
    {

        if(!$this->tfa) {
            $this->tfa = loadLibClass('Tfa');
        }

        $this->tfa->setUserData($_SESSION['tmp_user']);

        if(DEBUG) {
            $this->return['debug'] = $_SESSION['tmp_user'];
        }

        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
        $this->return['html_popup'] = $this->tpl->output('tfa-popup', $this->getPData());
        $this->return['html_content'] = $this->tpl->output('tfa-auth-content', $this->getPData());
    }

    public function tfaCheckAuth()
    {
        if(!$this->tfa) {
            $this->tfa = loadLibClass('Tfa');
        }

        $this->tfa->setUserData($_SESSION['tmp_user']);

        $code = getP('code');

        $res = $this->tfa->checkCodeOnLogin($code);

        if($res['success']) {
            $usrLoginRes = $this->loginUser(null, null, false, true);

            if($usrLoginRes['success']) {
                $this->loginForm(true);
                return;
            }
        }

        $item = $res;
        $this->return['item'] = $item;
        $this->setPData($item, 'item');
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
        $this->return['html_popup'] = $this->tpl->output('tfa-popup', $this->getPData());
        $this->return['html_content'] = $this->tpl->output('tfa-auth-content', $this->getPData());
    }


    // // ///
    // This is called when reservation made in the name of other patient
    // // ///

    public function createAndSendReservationResumeLink($resId)
    {

        $error = false;
        $result = array(
            'success' => true,
            'message' => '',
        );

        $reservation = null;
        $sendRes = null;

        try {
            $reservation = $this->openReservation($resId, false, false);
        } catch (Exception $e) {
            $error = true;
            $result['message'] = 'Open reservation exception';
            $result['exception'] = array(
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            );
        }

        if(!empty($reservation)) {

            // if needed we can add lang param as well

            $params = array(
                'finish_res' => $resId,
            );

            $q = http_build_query($params);

            $link = $this->cfg->get('piearstaUrl') . 'arstu-katalogs/' . $reservation['doctor_url'] . '/' . $reservation['clinic_url'] . '/?' . $q;

            $reservation['completeReservationLink'] = $link;

        } else {

            if(!$error) {
                $result['message'] = 'Reservation can not be opened';
                $error = true;
            }
        }

        if(!$error) {

            try {
                $sendRes = sendReservationEmail($reservation, RESERVATION_WAITS_PATIENT_CONFIRMATION);
            } catch (Exception $e) {
                $error = true;
                $result['message'] = 'Send email exception';
                $result['exception'] = array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                );
            }
        }

        if($error) {
            $result['success'] = false;
        }

        return $result;
    }

    /**
     * @param $personId
     * @param array $row
     * @return array
     */
    private function addPersonData($personId, array $row) {

        $personData = $this->getPersonDataById($personId);

        if($personData) {

            $preparedPersonData = array(
                'id' => $personData['id'],
                'name' => $personData['name'],
                'surname' => $personData['surname'],
                'phone' => $personData['phone'],
                'email' => '',
                'resident' => $personData['resident'],
                'person_id' => $personData['person_id'],
                'date_of_birth' => $personData['date_of_birth'],
                'gender' => $personData['gender'],
            );

            $row['profile_person_id'] = $personId;
            $row['personData'] = $personData;
        }

        return $row;
    }


    // Selects necessary data from already created order and shows orderDetailsPopup

    /**
     * @param $orderId
     * @return bool
     */
    public function showOrderDetailsPopup($orderId) {

        unset($_SESSION['PaymentInfo']);

        $data = $this->getOrderInfoPopupData($orderId);
        $resData = $this->reservation->getReservation();

        if($resData['status'] != RESERVATION_WAITS_PAYMENT && $resData['status'] != RESERVATION_WAITS_PATIENT_CONFIRMATION) {
            $this->return['finalStatus'] = $resData['status'];
            return false;
        }

        $sType = $data['orderItems'][0]['service_type'];

        if(!$this->lockRecord) {
            return false;
        }

        $resId = $this->reservation->getReservationId();

        if($resId) {
            $resData = array(
                'status' => RESERVATION_WAITS_PAYMENT,
                'status_reason' => '',
                'status_changed_at' => time(),
                'updated' => time(),
                'cancelled_at' => NULL,
                'cancelled_by' => NULL,
                'sended' => '1',
            );

            $resData = $this->reservation->getReservation();

            if(DEBUG) {
                $this->return['resData'] = $resData;
            }

            // check if lock record exists
            $getLockQuery = "SELECT id FROM mod_shedules_lock WHERE reservation_id  = " . $resId;
            $gLQuery = new query($this->db, $getLockQuery);

            if(!$gLQuery->num_rows()) {

                // create lock record if not exists and lock slots

                $orderDetails = $this->order->getOrderDetails();

                $sheduleId = $resData['shedule_id'];
                $doctorId = $resData['doctor_id'];
                $hspDoctorId = $this->getDoctorById($doctorId)['hsp_resource_id'];
                $clinicId = $resData['clinic_id'];
                $startTimeStamp = strtotime($resData['start']);
                $duration = $orderDetails[0]['service_duration'];

                $thirdPersonData = null;

                if($resData['profile_person_id']) {

                    // if reservation has third person id, we should get person data
                    $persData = $this->getPersonById($resData['profile_person_id']);

                    if($persData) {
                        $thirdPersonData = $persData;
                    }
                }

                $lockRes = null;

                try {

                    $lockRes = $this->lockSlot($sheduleId, $doctorId, $hspDoctorId, $clinicId, $startTimeStamp, $duration, LOCK_STATUS_LOCALLY, $thirdPersonData, $resData);

                } catch (Exception $e) {

                    $lockRes = array(
                        'success' => false,
                        'errCode' => $e->getCode(),
                        'errMessage' => $e->getMessage(),
                    );
                }

                if(!$lockRes['success']) {
                    // error occurred
                    $this->return['error'] = array(
                        'lockError' => $lockRes,
                    );
                    return false;
                }
            }

            $resData = array(
                'status' => RESERVATION_WAITS_PAYMENT,
                'status_reason' => '',
                'status_changed_at' => time(),
                'updated' => time(),
                'cancelled_at' => 'null',
                'cancelled_by' => 'null',
                'sended' => '1',
            );

            $this->reservation->updateReservation($resId, $resData);
        }

        $this->return['slots'] = $data['slots'];

        $dbQuery = "UPDATE mod_orders 
                    SET
                        transaction_id = NULL,
                        status = 0,
                        status_reason = '',
                        status_datetime = '" . date(PIEARSTA_DT_FORMAT, time()) . "' 
                    WHERE id = " . $orderId;

        doQuery($this->db, $dbQuery);

        if(DEBUG) {
            $this->return['debug'] = $data;
        }

        $data['order_html'] = $this->getOrderHtml($data);

        $this->setPData($data, "item");
        $this->return['slots'] = $data['slots'];
        $this->return['html'] = $this->tpl->output('order-details-popup', $this->getPData());
    }

    /**
     * @param $orderId
     * @return array
     */
    private function getOrderInfoPopupData($orderId) {

        if(!$this->order) {
            $this->order = loadLibClass('order');
            $this->order->setOrder($orderId);
        }
        $orderData = $this->order->getOrder();
        $orderDetails = $this->order->getOrderDetails();
        $orderInfo = $this->order->getOrderInfo();

        if(!$orderData || !$orderDetails || !$orderInfo) {
            return false;
        }

        $serviceType = intval($orderDetails[0]['service_type']);

        $orderData['serviceDuration'] = $serviceType == 0 ? $this->order->getServiceDuration() : 0;

        $data = array_merge($orderData, $orderInfo);

        if(!$this->reservation) {
            $this->reservation = loadLibClass('reservation');
            $this->reservation->setReservation($data['reservation_id']);
        }

        $reservationData = $this->reservation->getReservation();

        // get correct doctor data in lv locale

        if($reservationData['doctor_id']) {

            $docDbQuery = "
                SELECT * FROM mod_doctors_info 
                WHERE
                    lang = 'lv' AND
                    doctor_id = ".$reservationData['doctor_id'];

            $docQuery = new query($this->db, $docDbQuery);

            $docData = $docQuery->getrow();

            if(!empty($docData)) {

                $data['doctor_name'] = $docData['name'];
                $data['doctor_surname'] = $docData['surname'];
            }

        }

        if(!$reservationData['doctor_id']) {
            $data['doctor_id'] = null;
            $data['doctor_name'] = null;
            $data['doctor_surname'] = null;

            $filename = $orderInfo['invoice_filename'];

            // set file path and filename
            $folder = 'profile/invoices/';
            $filepath = AD_SERVER_UPLOAD_FOLDER . $folder;

            if($filename && file_exists($filepath . $filename)) {
                unlink($filepath . $filename);
            }

            $newData = array();
            $newData['invoice_filename'] = '';

            saveValuesInDb('mod_order_info', $newData, $orderInfo['id']);
        }

        // add lv title for service

        if($orderDetails[0]['service_id']) {

            $sDbQuery = "
                SELECT ci.title FROM mod_classificators_info ci
                WHERE
                    IF(EXISTS(SELECT id FROM mod_classificators_info ci2 WHERE ci2.c_id = ".$orderDetails[0]['service_id']." AND ci2.lang = '".$this->getLang()."'), ci.lang = '".$this->getLang()."', ci.lang = 'lv') AND 
                    ci.c_id = " . $orderDetails[0]['service_id'];

            $sQuery = new query($this->db, $sDbQuery);

            $sData = $sQuery->getrow();

            if(!empty($sData)) {

                $orderDetails[0]['service_name'] = $sData['title'];
            }
        }

        $data['orderItems'] = array();
        $data['orderItems'] = $orderDetails;
        $data['bb'] = $this->cfg->get('bb');

        /** @var lockRecord $lockRecord */
        if(!$this->lockRecord) {
            $this->lockRecord = loadLibClass('lockRecord');
        }

        $this->lockRecord->setLockRecordByOrderId($orderId);
        $lockData = $this->lockRecord->getLockRecord();

        $data['date'] = $orderData['date'];
        $data['orderId'] = $data['order_id'];

        if($serviceType == 0) {

            /** @var lockRecord $lockRecord */
            if(!$this->lockRecord) {
                $this->lockRecord = loadLibClass('lockRecord');
                $this->lockRecord->setLockRecordByOrderId($orderId);
            }
            $lockData = $this->lockRecord->getLockRecord();

            $data['slots'] = $lockData['slots'];
            $data['id'] = $lockData['schedule_id'];
            $data['lockId'] = $lockData['id'];
            $data['reservationId'] = $orderData['reservation_id'];

        } else {

            if(!empty($lockData)) {
                $data['slots'] = $lockData['slots'];
                $data['id'] = $lockData['schedule_id'];
                $data['lockId'] = $lockData['id'];
            }

            $data['reservationId'] = $orderData['reservation_id'];
        }


        $billingSystemCfg = $this->cfg->get('billing_system');

        $data['banklinks'] = array_filter($billingSystemCfg['banklinks'], function ($bank) {
            return ($bank['active'] == 1);
        });

        $data['oldPaymentType'] = $this->cfg->get('oldPaymentType');

        return $data;
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function getOrderHtml(array $data) {

        $dbQuery = "SELECT c.reg_nr, c.zip, cl.title FROM mod_clinics c 
                    LEFT JOIN mod_classificators_info cl ON(c.city = cl.c_id) 
                    WHERE
                        c.id = " . $data['clinic_id'];
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $row = $query->getrow();
            $data['clinic_reg_num'] = $row['reg_nr'];
            $data['clinic_zip'] = $row['zip'];
            $data['clinic_city'] = $row['title'];
        }

        $data['serviceDuration'] = isset($data['serviceDuration']) ? $data['serviceDuration'] : $this->order->getServiceDuration();

        $patient = array();

        if(!empty($data['person_id']) && !empty($data['person_name']) && !empty($data['person_surname'])) {

            $patient['name'] = $data['person_name'];
            $patient['surname'] = $data['person_surname'];
            $patient['pk'] = !empty($data['person_person_id']) ? $data['person_person_id'] : $data['person_person_number'];
            $patient['phone'] = !empty($data['person_phone']) ? $data['person_phone'] : null;

        } else {

            $patient['name'] = $data['creator_name'];
            $patient['surname'] = $data['creator_surname'];
            $patient['pk'] = !empty($data['creator_person_id']) ? $data['creator_person_id'] : $data['creator_person_number'];
            $patient['phone'] = !empty($data['creator_phone']) ? $data['creator_phone'] : null;
        }

        $data['patient'] = $patient;

        $tmplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/order/');
        $this->setPData($data, "item");
        $html =  $this->tpl->output('order', $this->getPData());
        $this->tpl->setTmplDir($tmplDir);
        return $html;
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function getPaymentDataHtml(array $data) {

        $orderInfo = $this->order->getOrderInfo();
        $orderData = $this->order->getOrder();

        $pk = $orderInfo['creator_person_id'] ? $orderInfo['creator_person_id'] : $orderInfo['creator_person_number'];
        $payer = $orderInfo['creator_name'] . ' ' . $orderInfo['creator_surname'];

        $data['payer'] = $payer;
        $data['pk'] = $pk;
        $data['date'] = $data['fulfill_date'] ? $data['fulfill_date'] : $data['updated'];

        $data['banklink'] = (
            $data['payment_method'] != 'cards' &&
            $data['payment_method'] != 'dccard' &&
            $data['payment_method'] != 'insurance' &&
            substr($data['payment_method'] , 0, 8) != 'everyPay'
        );


        $dbQuery = "SELECT * FROM mod_profiles 
                    WHERE
                        id = " . $orderInfo['creator_id'];

        $query = new query($this->db, $dbQuery);

        $userData = $query->getrow();

        if(!empty($userData['insurance_id'])) {

            $insDbQuery = "SELECT * FROM mod_classificators_info 
                            WHERE
                                c_id = " . $userData['insurance_id'];

            $query = new query($this->db, $insDbQuery);

            if($query->num_rows()) {
                $row = $query->getrow();
                $userData['insurance'] = $row['title'];
            }
        }

        $insurance = $this->insurance->getInsuranceData($userData, $orderData['clinic_id']);

        $data['insurance'] = $data['payment_method'] == 'insurance';
        $data['insuranceName'] = !empty($insurance['companyName']) ? $insurance['companyName'] : '';
        $data['insuranceNumber'] = !empty($insurance['insuranceNumber']) ? $insurance['insuranceNumber'] : '';

        // we show 6 asterisks and 4 last digits from pan
        if(isset($data['pan'])) {
            $newPan = '******' . substr($data['pan'], -4);
            $data['pan'] = $newPan;
        }

        if (substr($data['payment_method'], 0, 8) == 'everyPay') {
            if (($pos = strpos($data['payment_method'], "/")) !== false) {
                $data['payment_method'] = substr($data['payment_method'], $pos + 1);
                $data['card_everyPay'] = true;
            }
        }

        $templDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
        $this->setPData($data, "item");
        $html =  $this->tpl->output('payment-data', $this->getPData());
        $this->tpl->setTmplDir($templDir);
        return $html;
    }

    /**
     * @param array $params
     */
    private function freeServiceReservation(array $params) {

        // set sent to 0 to allow api send reservation to SM

        /** @var reservation $reservation */
        $reservation = loadLibClass('reservation');
        $reservation->setReservation($params['reservationId']);

        $resData = array(
            'sended' => '0',
        );

        if(
            (!empty($params['status']) && $params['status'] == 2) &&
            (!empty($params['confirmed_at']))
        ) {
            $resData['status'] = '2';
            $resData['confirmed_at'] = $params['confirmed_at'];
        }

        $reservation->updateReservation($params['reservationId'], $resData);

        // Unlock slots locked before and book slots needed for selected service

        /** @var lockRecord $lockRecord */
        $lockRecord = loadLibClass('lockRecord');
        $lockRecord->setLockRecord($params['lockId']);

        $lockRecord->bookSlots();

        // add record to doctor's google calendar
        $resData = $reservation->getReservation();
        $clinicId = $resData['clinic_id'];
        $doctorId = $resData['doctor_id'];


        // // // GAPI

        $gsyncDebug = array();

        // check if doctor should sync google calendar
        // end create new event in his calendar if so.

        /** @var googleApi $gApi */
        $gApi = loadLibClass('googleApi');

        if(DEBUG) {
            $gsyncDebug['gapi_Obj'] = $gApi;
        }

        // get token info for given clinic/doctor
        // this method also set up token into google client and instantiate calendar service
        // making it available with getService() method
        $token = $gApi->getDoctorsApiToken($clinicId, $doctorId);

        if(DEBUG) {
            $gsyncDebug['token'] = $token;
        }

        // check if token exists
        if(!empty($token) && isValidJson($token)) {

            // Create event in doctor's gc from reservation data

            $g_CreateResult = $gApi->createEvent($this->openReservation($resData['id'], false));
            $gsyncDebug['g_CreateResult'] = $g_CreateResult;
        }

        // // // end of GAPI

        $this->getReservationsCount();

        // send email

        $mailStatus = $params['item']['need_approval'] ? $resData['status'] : 2;

//        pre('Need approval');
//        pre($params['item']['need_approval']);
//        pre('Mail status');
//        pre($mailStatus);
//        exit;

        $lang = !empty($this->userData['lang']) ? $this->userData['lang'] : $this->getLang();
        sendReservationEmail($this->openReservation($params['reservationId'], false, true, $lang), $mailStatus, $lang);

        $params['item']['reservationId'] = $params['reservationId'];

        $this->setPData($params['item'], "item");

        if(!empty($resData['options']) && !empty($resData['options']['dcAppointment']) && $resData['options']['dcAppointment'] == '1') {
            $this->setPData(true, "dcAppointment");
            $lang = !empty($_SESSION['userLang']) ? $_SESSION['userLang'] : getDefaultLang();
            $dcUrl = $this->cfg->get('dcUrl') . '/' . $lang . '/';
            $this->setPData($dcUrl, "dcUrl");
        }

        // update promo-code usage record if exists

        $promoDbQuery = "UPDATE promo_usage SET status = 1 WHERE reservation_id = " . $params['reservationId'];
        doQuery($this->db, $promoDbQuery);

        // Reservation successfully created
        // show success popup

        $this->return['html'] = $this->tpl->output('reservations-add-ok', $this->getPData());
        $this->return['slots'] = $params['slots'];
        $this->return['orderInfo'] = $params['item'];
        $this->return['reservation'] = $resData;
        $this->return['params'] = $params;
        $this->return['SMCheckResult'] = $params['SMCheckResult'];
        if(DEBUG) {
            $this->return['gcyncDebug'] = $gsyncDebug;
        }
    }

    public function clearReservationData() {

        if($this->order) {
            $this->order->deleteOrder();
        }

        $lockId = getP('lockId');
        $resId = getP('reservationId');

        $this->lockRecord->setLockRecord($lockId);
        $this->reservation->setReservation($resId);

        $this->lockRecord->deleteLockRecord();
        $this->reservation->deleteReservation();
        $this->return['result'] = 'cleaned';
    }

    // Check if we need SM confirmation
    public function needSmConfirmation($clinicId) {

        $dbQuery = "SELECT id, sm_check_necessary FROM " . $this->cfg->getDbTable('clinics', 'self') . " 
                    WHERE id = " . $clinicId . " LIMIT 1";
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $row = $query->getrow();
            return $row['sm_check_necessary'] == 1;
        }
        return false;
    }

    // Check if able to book these slots

    /**
     * @param null $terminalId
     * @param float|int $timeout
     * @param false $insurance
     * @return array[]|null[]
     */
    public function checkForSlotsBookingAbility($terminalId = null, $timeout = SM_CONFIRMATION_TIMEOUT, $insurance = false) {

        $timeout = !empty($timeout) ? $timeout : SM_CONFIRMATION_TIMEOUT;

        $reservationData = $this->reservation->getReservation();
        $serviceData = $this->getServiceById($reservationData['service_id']);
        $doctorData = $this->getDoctorById($reservationData['doctor_id']);
        $patientData = $this->userData;

        $clinic = $this->getClinicById($reservationData['clinic_id']);

        if($clinic['clinic_type'] == 'egl_queue') {

            $result = array(
                'confirmed' => LOCK_STATUS_NON_CONFIRMED,
                'hsp_reservation_id' => null,
            );

            $dbQuery = "SELECT * FROM mod_shedules WHERE id = " . $reservationData['shedule_id'];
            $query = new query($this->db, $dbQuery);

            if($query->num_rows()) {

                $resId = $reservationData['id'];
                $slot = $query->getrow();
                $slotExtId = $slot['slot_ext_id'];
                $notice = 'Piearsta.lv.';

                if(!empty($reservationData['notice'])) {
                    $notice .= ' Notice: ' . $reservationData['notice'];
                }

                $dataPrepared = array(
                    'soap_method' => 'Appoint',
                    'UID' => $patientData['id'] . '_' . $slotExtId,
                    'Res' => $slotExtId,
                    'Name' => trim($patientData['name'] . ' ' . $patientData['surname']),
                    'Phone' => $patientData['phone'],
                    'Email' => $patientData['email'],
                    'Info' => $notice,
                );

                /** @var eglReservation $egl */
                $egl = loadLibClass('eglReservation');

                $res = $egl->createAppointment($dataPrepared, $resId);

                if($res['success'] && $res['hsp_reservation_id']) {

                    $updData = array(
                        'hsp_reservation_id' => $res['hsp_reservation_id'],
                        'res_uid' => $dataPrepared['UID'],
                        'sended' => '1',
                        'status' => '2',
                    );

                    $this->reservation->updateReservation($reservationData['id'], $updData);

                    $result = array(
                        'confirmed' => LOCK_STATUS_CONFIRMED,
                        'hsp_reservation_id' => $res['hsp_reservation_id'],
                        'autoconfirmed' => true,
                    );
                }

            }

            if(DEBUG) {
                $result['EGLcheckResult'] = $res;
                $result['data'] = $dataPrepared;
            }

            return array(
                'response' => $result,
            );
        }

        $insData = null;

        if($insurance) {

            if(!$this->lockRecord) {
                /** @var  lockRecord */
                $this->lockRecord = loadLibClass('lockRecord');
            }

            $this->lockRecord->setLockRecordByReservationId($reservationData['id']);
            $lr = $this->lockRecord->getLockRecord();
            $insData = json_decode($lr['additional_data'], true);
        }

        if(!$terminalId) {
            $clinicData = $this->getClinicById($reservationData['clinic_id']);
            $terminalId = $clinicData['terminal_id'];
        }

        $data = array(
            'datetime_from' => $reservationData['start'],
            'datetime_thru' => $reservationData['end'],
            'hsp_doctor_id' => $doctorData['hsp_resource_id'],
            'doctor_id' => $reservationData['doctor_id'],
            'clinic_id' => $terminalId,
            'reservation_id' => $reservationData['id'],
            'hsp_reservation_id' => null,
            'service_id' => $reservationData['service_id'],
            'service_name' => array(
                '@cdata' => $serviceData['title'],
            ),
            'patient' => array(
                'id' => $reservationData['profile_id'],
                'name' => array(
                    '@cdata' => $patientData['name'],
                ),
                'surname' => array(
                    '@cdata' => $patientData['surname'],
                ),
                'person_code' => $patientData['person_id'],
                'person_number' => $patientData['person_number'],
                'phone' => $patientData['phone'],
                'email' => $patientData['email'],
                'birthdate' => $patientData['date_of_birth'],
                'gender' => $patientData['gender'],
                'lv_resident' => $patientData['resident'],
                'country_code' => $patientData['country'],
            ),
            'person' => array(),
            'status' => $reservationData['status'],

            'finished' => in_array(
                $reservationData['status'],
                array(RESERVATION_WAITS_PAYMENT, RESERVATION_WAITS_PATIENT_CONFIRMATION)
            ) ? '0' : '1',

            'notes' => array('@cdata' => $reservationData['notice']),
        );

        if(isset($reservationData['profile_person_id']) && $reservationData['profile_person_id']) {
            $personData = $this->getPersonById($reservationData['profile_person_id']);
            $data['person'] = array(
                'id' => $reservationData['profile_person_id'],
                'name' => array(
                    '@cdata' => $personData['name'],
                ),
                'surname' => array(
                    '@cdata' => $personData['surname'],
                ),
                'person_code' => $personData['person_id'],
                'person_number' => $personData['person_number'],
                'phone' => $personData['phone'],
                'email' => isset($personData['email']) ? $personData['email'] : '',
                'birthdate' => $personData['date_of_birth'],
                'gender' => $personData['gender'],
                'lv_resident' => $personData['resident'],
            );
        }

        if($insurance && !empty($insData)) {

            $data['insurance'] = array(
                'checked' => $insData['checkDatetime'],
                'ic_id' => $insData['insuranceCompanyPAId'],
                'ic_title' => array(
                    '@cdata' => $insData['insuranceCompany'],
                ),
                'police_number' => $insData['insuranceNumber'],
                'service_code' => $insData['servicePiearstaId'],
                'price' => $insData['insurancePrice'],
                'compensation' => $insData['insuranceCompensation'],
                //'created' => $insData['checkDatetime'],
            );
        }

        /** @var RequestSm $smRequest */
        $requestSmClass = loadLibClass('requestSm', true, $reservationData['clinic_id']);

        // Default response -- if no response received from curl in $timeout time
        $confirmed = array(
            'confirmed' => LOCK_STATUS_AUTOCONFIRMED,
            'hsp_reservation_id' => null,
            'system_unavailable' => true,
        );

        $endTime = microtime(true) + (intval($timeout) / 1000);

        // Timer cycle
        while(true) {

            $result = null;

            // sync request SM
            // confirm or not confirm

            // Responses:
            // confirmed:true, hsp_reservation_id = int -- confirmed
            // confirmed:false, hsp_reservation_id = null -- non confirmed
            // no response -- null (we set status to autoconfirmed in that case)
            // We should return array with status and hsp_reservation_id

            // CURL request with timeout 5s
            // check timeout

            if((microtime(true) > $endTime) && !$result) {
                break;
            }

            // REQUEST SM
            $smResult = $requestSmClass->requestSm('saveReservation', $data);

            if(is_array($smResult) && isset($smResult['success'])) {

                if(!$smResult['success']) {
                    break;
                }

                unset($confirmed['system_unavailable']);

                if(isset($smResult['result'])) {

                    if($smResult['result']) {

                        $xml = $smResult['result'];
                        /** @var xml $xmlClass */
                        $xmlClass = loadLibClass('xml');
                        $xmlClass->loadSimple(false, $xml, false);

                        // is valid xml?
                        if ($reader = $xmlClass->getReader()) {

                            $status = (string)$reader->statuss->attributes()->error;
                            $hspResId = (string)$reader->hsp_reservation_id;
                            $autoConfirm = (string)$reader->confirmed;

                            if($status >= 1 && $hspResId) {

                                $sended = $reservationData['status'] == RESERVATION_WAITS_PAYMENT ? '1' : '0';

                                $updData = array(
                                    'hsp_reservation_id' => intval($hspResId),
                                    'status' => ($reservationData['status'] == RESERVATION_WAITS_CONFIRMATION && $autoConfirm) ? '2' : $reservationData['status'],
                                    'sended' => $autoConfirm ? '1' : $sended,
                                );

                                // reservation successfully sended to SM
                                // so we update hsp_reservation_id and sended fields in reservation record
                                $this->reservation->updateReservation($data['reservation_id'], $updData);

                                $result = array(
                                    'confirmed' => LOCK_STATUS_CONFIRMED,
                                    'hsp_reservation_id' => intval($hspResId),
                                );

                                if($autoConfirm && $autoConfirm == 1) {
                                    $result['autoconfirmed'] = true;
                                }

                            } else {
                                $result = array(
                                    'confirmed' => LOCK_STATUS_NON_CONFIRMED,
                                    'hsp_reservation_id' => null,
                                );
                            }
                        }

                    } else {
                        $result = array(
                            'confirmed' => LOCK_STATUS_NON_CONFIRMED,
                            'hsp_reservation_id' => null
                        );
                    }
                }

                // if result received before timeout -- we exit cycle and stop curl requests execution
                $confirmed = $result;
                break;
            }
        }

        $result = array(
            'response' => $confirmed,
        );

        if(DEBUG) {
            $result['data'] = $data;
            $result['debug'] = $smResult;
        }

        return $result;
    }

    // lock slot

    /**
     * @param $sheduleId
     * @param $doctorId
     * @param $hspDoctorId
     * @param $clinicId
     * @param $startTimeStamp
     * @param $duration
     * @param string $status
     * @param null $thirdPersonData
     * @param null $reservationData
     * @param null $serviceId
     * @return array
     * @throws Exception
     */
    private function lockSlot($sheduleId, $doctorId, $hspDoctorId, $clinicId, $startTimeStamp, $duration, $status = LOCK_STATUS_LOCALLY, $thirdPersonData = null, $reservationData = null, $serviceId = null) {
        global $config;
        $time = new DateTime();
        $interval = new DateInterval('PT' . $config['shedule_lock_time'] . 'S');
        $expireTimeObj = $time->add($interval);

        $maxSlotInterval = 0;
        $dbQuery = "SELECT MAX(`interval`) AS max_interval FROM " . $this->cfg->getDbTable('shedule', 'self') . " 
                    WHERE clinic_id = " . $clinicId . " AND doctor_id = " . $doctorId;
        $query = new query($this->db, $dbQuery);
        if($query->num_rows()) {
            $result = $query->getrow();
            $maxSlotInterval = intval($result['max_interval']);
        }

        // calc end time

        $startTime = new DateTime();
        $startTime->setTimestamp($startTimeStamp);
        $endTime = new DateTime();
        $endTime->setTimestamp($startTimeStamp);
        $endTime->modify("+" . $duration . " minutes");

        if(!$serviceId) {
            $endTime->modify("+" . $maxSlotInterval . " minutes");
        }

        $startTimeString = $startTime->format(PIEARSTA_DT_FORMAT);
        $endTimeString = $endTime->format(PIEARSTA_DT_FORMAT);

        // get necessary slots

        $slotsIdArray = array();
        $slots = '';

        $slotsArray = getSlots($startTimeString, $endTimeString, $doctorId, $clinicId);

        $summOfIntervals = 0;

        if(count($slotsArray)) {

            foreach ($slotsArray as $k => $v) {

                // if summ of selected intervals < selected service duration
                // we need to lock another one slot
                if($summOfIntervals < intval($duration)) {
                    $slotsIdArray[] = $v['id'];

                    // set locked field to 1
                    $data = array();
                    $data['locked'] = 1;
                    saveValuesInDb($this->cfg->getDbTable('shedule', 'self'), $data, $v['id']);
                }
                $summOfIntervals += intval($v['interval']);
            }

            $slots = implode(',', $slotsIdArray);

            // lock schedules time slots
            $lockDbData = array();

            if($reservationData) {
                $lockDbData['order_id'] = $reservationData['order_id'];
                $lockDbData['reservation_id'] = $reservationData['id'];
                $lockDbData['hsp_reservation_id'] = $reservationData['hsp_reservation_id'] ? $reservationData['hsp_reservation_id'] : 'null';
                $lockDbData['service_id'] = $reservationData['service_id'];
            }

            $lockDbData['doctor_id'] = $doctorId;
            $lockDbData['hsp_doctor_id'] = $hspDoctorId;
            $lockDbData['clinic_id'] = $clinicId;
            $lockDbData['session_id'] = session_id();
            $lockDbData['expire_time'] = $expireTimeObj->format(PIEARSTA_DT_FORMAT);
            $lockDbData['schedule_id'] = $sheduleId;
            $lockDbData['slots'] = $slots;
            $lockDbData['status'] = $status;

            $lockDbData['datetime_from'] = $startTimeString;
            $lockDbData['datetime_thru'] = $endTimeString;

            $lockDbData['patient_id'] = $this->userData['id'];
            $lockDbData['patient_name'] = $this->userData['name'];
            $lockDbData['patient_surname'] = $this->userData['surname'];
            $lockDbData['patient_phone'] = $this->userData['phone'];
            $lockDbData['patient_email'] = $this->userData['email'];
            $lockDbData['patient_lv_resident'] = $this->userData['resident'];
            $lockDbData['patient_person_id'] = $this->userData['person_id'];
            $lockDbData['patient_date_of_birth'] = $this->userData['date_of_birth'];
            $lockDbData['patient_gender'] = $this->userData['gender'];

            if($thirdPersonData) {
                $lockDbData['third_person_id'] = $thirdPersonData['id'];
                $lockDbData['third_person_name'] = $thirdPersonData['name'];
                $lockDbData['third_person_surname'] = $thirdPersonData['surname'];
                $lockDbData['third_person_phone'] = $thirdPersonData['phone'];
                $lockDbData['third_person_email'] = $thirdPersonData['email'];
                $lockDbData['third_person_lv_resident'] = $thirdPersonData['resident'];
                $lockDbData['third_person_person_id'] = $thirdPersonData['person_id'];
                $lockDbData['third_person_date_of_birth'] = $thirdPersonData['date_of_birth'];
                $lockDbData['third_person_gender'] = $thirdPersonData['gender'];
            }

            $lockRecordId = saveValuesInDb($this->cfg->getDbTable('shedule', 'lock'), $lockDbData);

            return array(
                'success' => true,
                'slotCount' => count($slotsIdArray),
                'slots' => $slots,
                'lockRecordId' => $lockRecordId,
                'start_time' => $startTimeString,
                'end_time' => $endTimeString,
            );

        } else {

            return array(
                'success' => false,
                'slotCount' => 0,
                'slots' => $slots,
                'error' => 'Can\'t book this time!',
                'lockRecordId' => null,
            );
        }
    }

    /**
     * @param $id
     * @return array|int|null
     */
    private function getServiceById($id) {

        $dbQuery = "SELECT c.*, ci.title, ci.description FROM " . $this->cfg->getDbTable('classificators', 'self') . " c
                       LEFT JOIN " . $this->cfg->getDbTable('classificators', 'details') . " ci ON(c.id = ci.c_id) 
                            WHERE 1 AND ci.lang = '". getDefaultLang()."' AND c.id = " . $id;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return null;
    }

    /**
     * @param $id
     * @return array|int|null
     */
    private function getDoctorById($id) {

        $dbQuery = "SELECT d.*, di.name, di.surname, di.description, di.notify_phone, di.notify_email
                    FROM " . $this->cfg->getDbTable('doctors', 'self') . " d
                    LEFT JOIN " . $this->cfg->getDbTable('doctors', 'info') . " di ON(d.id = di.doctor_id) 
                    WHERE 1 AND 
                        d.id = " . $id;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return null;
    }

    /**
     * @param $id
     * @return array|int|null
     */
    private function getClinicById($id) {

        $currDate = date('Y-m-d H:i:s', time());
        $network = !empty($_SESSION['user']['dcSubscription']['product_network']) ? $_SESSION['user']['dcSubscription']['product_network'] : null;

        $selNetw = ", 0 as network ";
        $joinNetw = "";

        if($network) {
            $selNetw = ", c2n.id IS NOT NULL as network ";
            $joinNetw = " LEFT JOIN ins_clinic_to_networks c2n ON (c2n.clinic_id = cl.id AND c2n.network_id = $network AND c2n.start_datetime <= '$currDate' AND c2n.end_datetime > '$currDate') ";
        }

        $dbQuery = "SELECT cl.*, ci.address, ci.description, cc.phone, cc.email $selNetw  FROM " . $this->cfg->getDbTable('clinics', 'self') . " cl 
                            LEFT JOIN mod_clinics_info ci ON (cl.id = ci.clinic_id)
                            LEFT JOIN mod_clinics_contacts cc ON (cl.id = cc.clinic_id)
                            $joinNetw
                            WHERE 1 AND 
                                cl.id = " . $id;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return null;
    }

    /**
     * @param $id
     * @return array|int|null
     */
    private function getPersonById($id) {

        $dbQuery = "SELECT * FROM " . $this->cfg->getDbTable('profiles', 'persons') . " 
                            WHERE 1 AND id = " . $id;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return null;
    }

    // Create new order and order info
    /**
     * @param $data
     * @return array
     */
    private function prepareOrderData($data) {
        // Orders table
        $dbData = array();
        $dbData['orders'] = array();
        $dbData['orders']['patient_id'] =   (isset($data['personData']['id']) && $data['personData']['id']) ?
            $data['personData']['id'] :
            $data['userData']['id'];
        $dbData['orders']['clinic_id'] = $data['clinic_id'];
        $dbData['orders']['doctor_id'] = $data['doctor_id'];
        $dbData['orders']['status'] = '0';
        $dbData['orders']['status_datetime'] = date(PIEARSTA_DT_FORMAT, time());
        $dbData['orders']['order_total'] = $data['order_total'];
        $dbData['orders']['reservation_id'] = isset($data['reservationId']) ? $data['reservationId'] : 'null';

        // Order_info table
        $dbData['order_info'] = array();
        $dbData['order_info']['creator_id'] = $data['userData']['id'];
        $dbData['order_info']['creator_name'] = $data['userData']['name'];
        $dbData['order_info']['creator_surname'] = $data['userData']['surname'];
        $dbData['order_info']['creator_resident'] = $data['userData']['resident'];
        $dbData['order_info']['creator_person_id'] = $data['userData']['person_id'];
        $dbData['order_info']['creator_person_number'] = $data['userData']['person_number'];
        $dbData['order_info']['creator_phone'] = $data['userData']['phone'];
        $dbData['order_info']['creator_email'] = $data['userData']['email'];

        if(isset($data['personData'])) {
            $dbData['order_info']['person_id'] = $data['personData']['id'];
            $dbData['order_info']['person_name'] = $data['personData']['name'];
            $dbData['order_info']['person_surname'] = $data['personData']['surname'];
            $dbData['order_info']['person_resident'] = $data['personData']['resident'];
            $dbData['order_info']['person_person_id'] = $data['personData']['person_id'];
            $dbData['order_info']['person_person_number'] = $data['personData']['person_number'];
            $dbData['order_info']['person_phone'] = $data['personData']['phone'];
            $dbData['order_info']['person_profile_id'] = $data['personData']['profile_id'];
        }

        $dbData['order_info']['clinic_id'] = $data['clinic_id'];
        $dbData['order_info']['clinic_name'] = $data['clinic_name'];
        $dbData['order_info']['clinic_address'] = $data['clinic_address'];
        $dbData['order_info']['doctor_id'] = $data['doctor_id'];
        $dbData['order_info']['doctor_name'] = $data['name'];
        $dbData['order_info']['doctor_surname'] = $data['surname'];
        $dbData['order_info']['invoice_filename'] = '';

        return $dbData;
    }

    private function prepareOrderDetailsItemData($data) {
        // Order_details table
        $dbData = array();
        $dbData['service_id'] = $data['c_id'];
        $dbData['service_name'] = $data['title'];
        $dbData['service_type'] = isset($data['service_type']) ? $data['service_type'] : 0;
        $dbData['service_duration'] = $data['service_duration'];
        $dbData['price'] = $data['price'];
        $dbData['quantity'] = 1;
        $dbData['start_time'] = $data['start_time'];
        $dbData['end_time'] = $data['end_time'];
        $total = $dbData['price'] * $dbData['quantity'];
        $dbData['item_total'] = $total;
        return $dbData;
    }

    // Gets and returns service description text by description id
    public function getServiceDescription($descId) {
        /** @var serviceDetails $serviceDetailsClass */
        $serviceDetailsClass = loadLibClass('serviceDetails');
        $descr = $serviceDetailsClass->getServiceDescriptionById($descId);
        $descr = removeTags($descr, '');
        $this->return['html'] = $descr;
    }

    // sets Order and Reservation statuses to canceled, removes lock record, unlocks slots

    public function backToReservationPopup() {

        $sheduleId = getP('sheduleId');
        $serviceId = getP('serviceId');
        $note = getP('notice');
        $personId = getP('personId');
        $source = getP('source');
        $dc = getP('dc');
        $insurance = getP('insurance');

        $source = $source ? $source : null;

        if($this->order && $this->order->getOrderId() && $this->order->getStatus() == ORDER_STATUS_PENDING) {
            return false;
        }

        if( (!$dc && !$insurance) && $this->lockRecord && $this->lockRecord->getLockRecordId()) {
            $this->lockRecord->deleteLockRecord();
        }

        $resData = null;

        if($this->reservation) {
            $resData = $this->reservation->getReservation();
        }

        $clinicId = isset($resData['clinic_id']) ? $resData['clinic_id'] : null;
        $doctorId = isset($resData['doctor_id']) ? $resData['doctor_id'] : null;

        if($this->reservation && $this->order) {
            $res = $this->reservation->deleteReservation();

            if(in_array($this->order->getStatus(), array(ORDER_STATUS_NON_PAID))) {
                $this->order->updateOrder(array('reservation_id' => 'null'));
            } else {
                $this->order->deleteOrder();
            }
        }

        $dc = $dc ? $dc : null;
        $resOptions = null;

        if($resData && !empty($resData['options'])) {
            $resOptions = $resData['options'];
        }

        $this->addReservationPopup($sheduleId, $serviceId, $source, $note, $personId, $clinicId, $doctorId, $dc, $resOptions);
    }

    // sets all necessary cancel values in reservation record

    /**
     * @param $reservationId
     * @param null $reason
     */
    private function setReservationCanceled($reservationId, $reason = null) {

        /** @var reservation $res */
        $res = loadLibClass('reservation');
        $res->setReservation($reservationId);
        $resData = $res->getReservation();

        $result = setReservationCanceled($reservationId, $this->userId, $resData['status'], $reason);
        $this->getReservationsCount();

        return $result;
    }

    // Construct data for payment and send request to billing portal api
    // Redirect user to billing portal
    public function performPayment() {

        // validate
        /** @var validator $validator */
        $validator = loadLibClass('validator');
        $validator->setFieldsArray($this->fieldsArray['perform_payment']);

        foreach ($this->fieldsArray['perform_payment'] as $field => $data) {
            $validator->checkValue($field, getP('fields/' . $field));
        }

        $result = $validator->returnData();

        if (empty($result['error'])) {

            $paymentMethod = getP('fields/method');
            $backUrl = getP('backUrl');
            $calendarData = getP('calendarData');

            // Possible methods:
            // cards -- bank payment cards
            // <bank_link_name> -- internet bank

            switch ($paymentMethod) {
                case 'cards' :
                    $method = 'payment_card';
                    break;
                case 'everyPay' :
                    $method = 'everyPay';
                    break;
                default :
                    $method = 'banklink_lv_' . $paymentMethod;
            }

            // ip
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            $orderData = $this->order->getOrder();
            $orderDetails = $this->order->getOrderDetails()[0];
            $orderId = $orderData['id'];

            $doctor = $this->getDoctorById($orderData['doctor_id']);
            $hspResourceId = $doctor['hsp_resource_id'];

            $serviceType = intval($orderDetails['service_type']);

            //
            // Pasutijums Nr. {Order_no}, {service_name}. Service_name: E-KONSULTACIJA, E-PIERAKSTS
            //$serviceName = $orderDetails['service_type'] == 0 ? 'E-PIERAKSTS' : 'E-KONSULTACIJA';
            $serviceName = $this->cfg->get('service_types')[$serviceType];
            $description = 'Pasutijums Nr. ' . $orderId . ', ' . $serviceName;

            // Construct request data

            $post_data = array(
                "payment_method" => $method,
                "payment_amount" => $orderData['order_total'],
                "payment_description" => $description,
                "service_provider_transaction_id" => $orderId, // order id
                "service_provider_partner_id" => 'Transaction id', // client_id from clinics table
                "service_provider_product_type" => 'appointment',
                "language" =>  $this->getLang(),
                "user_data" => array(
                    'id' => $this->userData['id'],
                    'name' => $this->userData['name'],
                    'surname' => $this->userData['surname'],
                    'phone_number' => $this->userData['phone'],
                    'ip_address' => $ip,
                    'email' => $this->userData['email'],
                ),
                'hsp_resource_id' => $hspResourceId,
                'paServiceId' => $orderDetails['service_id'],
                'paServiceName' => $orderDetails['service_name'],
            );

            // Initiate payment

            // Start transaction
            // payment_create => /payments/create
            $paymentNonce = '';
            if ($method == 'everyPay'){
                $odata = array(
                    'status' => ORDER_STATUS_PENDING,
                    'status_reason' => 'Payment started',
                    'status_datetime' => date(PIEARSTA_DT_FORMAT),
                );

                $this->order->updateOrder($odata);

                $this->billingSystem->setEveryPayConfig();
                $this->billingSystem->setPostData($post_data, $backUrl, $orderId);

                $paymentNonce = $this->billingSystem->getGeneratedNonce();
                $result = $this->billingSystem->requestEveryPay('payments_create');

                $odata = array(
                    'payment_nonce' => $paymentNonce,
                    'payment_description' => $description,
                );

                $this->order->updateOrder($odata);

            } else {
                $result = $this->billingSystem->requestBillingSystem('payments_create', $post_data);
            }

            $response = json_decode($result['result']);

            if(DEBUG) {
                $this->return['debug'] = $result;
            }

            if($result['success']) {

                if($response->error || !empty((array)$response->warnings)) {

                    if(DEBUG) {
                        $this->return['debug']['result'] = is_string($this->return['debug']['result']) ?
                            json_decode($this->return['debug']['result']) :
                            $this->return['debug']['result'];
                    }
                }

                // everyPay response
                $paymentReference = '';

                if (!empty($response->payment_reference) && !empty($response->payment_link)){
                    $paymentReference = $response->payment_reference;
                    $response->payment_uuid = $paymentReference;
                    $response->payment_url = $response->payment_link;
                }

                $trId = '';
                if ($method != 'everyPay'){

                    $trData = array(
                        'order_id' => $orderId,
                        'payment_id' => $response->payment_id,
                        'payment_uuid' => $response->payment_uuid,
                        'payment_url' => $response->payment_url,
                        'payment_method' => $paymentMethod,
                        'payment_description' => $description,
                        'payment_nonce' => $paymentNonce,
                    );

                    // Create new transaction record
                    $trId = $this->transaction->createTransaction($trData);

                }

                // set order status to pending and set transaction id to order record
                $odata = array(
                    'status' => ORDER_STATUS_PENDING,
                    'status_reason' => 'Payment started',
                    'status_datetime' => date(PIEARSTA_DT_FORMAT),
                    'payment_reference' => $paymentReference,
                );

                if (!empty($trId)){
                    $odata['transaction_id'] = $trId;
                }

                $this->order->updateOrder($odata);

                if($serviceType == 0) {

                    // getLock data
                    $lockData = $this->lockRecord->getLockRecord();
                    $this->lockRecord->prolongateExpirationTime();

                    $_SESSION['PaymentInfo'] = array(
                        'paymentMethod' => $method,
                        'sheduleId' => $lockData['schedule_id'],
                        'lockId' => $lockData['id'],
                        'reservationId' => $orderData['reservation_id'],
                        'orderId' => $orderId,
                        'payment_id' => $response->payment_id,
                        'payment_uuid' => $response->payment_uuid,
                        'orderTotal' => $orderData['order_total'],
                        'serviceId' => $lockData['service_id'],
                        'serviceName' => $lockData['service_name'],
                        'serviceType' => $serviceType,
                        'slots' => $lockData['slots'],
                        'backUrl' => $backUrl,
                        'calendarData' => $calendarData,
                        'referer' => $_SERVER['HTTP_REFERER'],
                        'lastWebLang' => $this->getLang(),
                        'payment_reference' => $paymentReference,
                        'payment_nonce' => $paymentNonce,
                        'payment_description' => $description,
                    );

                    if(DEBUG) {

                        $_SESSION['PaymentInfo']['debug'] = array(
                            'result' => $result,
                            'response' => $response,
                        );
                    }

                } else {

                    $lockData = null;

                    if($this->lockRecord) {
                        // getLock data
                        $lockData = $this->lockRecord->getLockRecord();

                        if($lockData) {
                            $this->lockRecord->prolongateExpirationTime();
                        }
                    }


                    $_SESSION['PaymentInfo'] = array(
                        'paymentMethod' => $method,
                        'consultationId' => $orderData['reservation_id'],
                        'reservationId' => $orderData['reservation_id'],
                        'orderId' => $orderId,
                        'payment_id' => $response->payment_id,
                        'payment_uuid' => $response->payment_uuid,
                        'orderTotal' => $orderData['order_total'],
                        'serviceName' => $orderDetails['service_name'],
                        'serviceType' => $serviceType,
                        'doctorId' => $orderData['doctor_id'],
                        'clinicId' => $orderData['clinic_id'],
                        'slots' => ($lockData && !empty($lockData['slots'])) ? $lockData['slots'] : null,
                        'backUrl' => $backUrl,
                        'calendarData' => $calendarData,
                        'referer' => $_SERVER['HTTP_REFERER'],
                        'lastWebLang' => $this->getLang(),
                        'payment_reference' => $paymentReference,
                        'payment_nonce' => $paymentNonce,
                        'payment_description' => $description,
                    );

                    if(DEBUG) {

                        $_SESSION['PaymentInfo']['debug'] = array(
                            'result' => $result,
                            'response' => $response,
                        );
                    }
                }

                // go ahead!
                redirectRequestHandler($response->payment_url);

            } else {

                // error connection to billingSystem
                $this->return['error'] = true;
            }

        } else {

            $this->return['errors']['fields'] = $result['errorFields'];
            return false;
        }
    }

    // Payment  Pending handler
    public function paymentPending($info) {

        // this called only for old billing system (was before everyPay)
        // TODO: probably should be removed completely

        // get order html
        $info['order_html'] = $this->getOrderHtml($this->getOrderInfoPopupData($this->order->getOrderId()));

        $_SESSION['orderId'] = $this->order->getOrderId();

        $_SESSION['calendarData'] = $info['calendarData'];
        $_SESSION['returning'] = true;

        if(strpos($info['backUrl'], '?')) {
            $info['backUrl'] .= '&orderId=' . $_SESSION['orderId'];
        } else {
            $info['backUrl'] .= '?orderId=' . $_SESSION['orderId'];
        }

        unset($_SESSION['PaymentInfo']);

        // order status -- 1 (PENDING)
        // transaction status -- 1 (PENDING)
        // reservation status -- 5 (WAIT)
        // slots remaining locked
        // expiration time remains extended

        $this->order->setStatus(ORDER_STATUS_NEW, 'Payment pending');
        $this->transaction->setStatus(TRANSACTION_STATUS_PENDING);
        $this->reservation->setStatus(RESERVATION_WAITS_PAYMENT);

        $this->setPData($info, 'info');
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile' . '/tmpl/payment/');
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('payment-pending', $this->getPData()));
    }


    // Payment in process handler
    // when we receive sent_for_processing state from everyPay this means the same as 'success'
    // but we set _PRELIMINARY_PAID statuses for order and transaction
    // use separate template for page and send to user special email to notify him about successful payment waiting for banking system confirmation

    public function paymentInProcess($info) {

        paymentSuccess($info, 'success_preliminary');

        $info['needPaymentConfirmation'] = true;

        $_SESSION['calendarData'] = $info['calendarData'];
        $_SESSION['returning'] = true;

        $this->getReservationsCount();

        unset($_SESSION['PaymentInfo']);

        $this->setPData($info, 'info');

        if(!empty($resData['options']) && !empty($resData['options']['dcAppointment']) && $resData['options']['dcAppointment'] == '1') {
            $this->setPData(true, "dcAppointment");
            $lang = !empty($_SESSION['userLang']) ? $_SESSION['userLang'] : $this->getLang();
            $dcUrl = $this->cfg->get('dcUrl') . '/' . $lang . '/';
            $this->setPData($dcUrl, "dcUrl");
        }

        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile' . '/tmpl/payment/');
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('payment-in-process', $this->getPData()));
    }

    // Payment Success handler
    public function paymentSuccess($info) {

        paymentSuccess($info, 'success');

        $_SESSION['calendarData'] = $info['calendarData'];
        $_SESSION['returning'] = true;

        $this->getReservationsCount();

        $this->reservation->setReservation($info['reservationId']);
        $resData = $this->reservation->getReservation();

        unset($_SESSION['PaymentInfo']);

        $this->setPData($info, 'info');

        if(!empty($resData['options']) && !empty($resData['options']['dcAppointment']) && $resData['options']['dcAppointment'] == '1') {
            $this->setPData(true, "dcAppointment");
            $lang = !empty($_SESSION['userLang']) ? $_SESSION['userLang'] : $this->getLang();
            $dcUrl = $this->cfg->get('dcUrl') . '/' . $lang . '/';
            $this->setPData($dcUrl, "dcUrl");
        }

        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile' . '/tmpl/payment/');
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('payment-success', $this->getPData()));
    }

    // Payment Fail handler
    public function paymentFail($info) {

        $paymentInfo = $_SESSION['paymentInfo'];

        // create new transaction or get ID of existing one
        // init transaction object with this ID

        $trId = createTransaction($info);

        if($trId) {

            if(!$this->transaction) {

                /** @var Transaction $transaction */
                $this->transaction = loadLibClass('transaction');
            }

            $this->transaction->reInitClass();
            $this->transaction->setTransaction($trId);
        }

        $errorCode = isset($_GET['payment_provider_reason_code']) ? $_GET['payment_provider_reason_code'] : null;
        $billingSystemCfg = $this->cfg->get('billing_system');
        $failReason = $errorCode ? $billingSystemCfg['errors'][$errorCode] : '';

        // get order html
        $info['order_html'] = $this->getOrderHtml($this->getOrderInfoPopupData($this->order->getOrderId()));

        $_SESSION['orderId'] = $this->order->getOrderId();
        $_SESSION['calendarData'] = $info['calendarData'];
        $_SESSION['returning'] = true;

        if(strpos($info['backUrl'], '?')) {
            $info['backUrl'] .= '&orderId=' . $_SESSION['orderId'];
        } else {
            $info['backUrl'] .= '?orderId=' . $_SESSION['orderId'];
        }

        unset($_SESSION['PaymentInfo']);

        // get error code and message
        // set order status to not paid
        // set reservation status to 5 (wait payment)
        // set transaction status to non paid and add error code and message
        // shorten expire time in lock record

        // update reservation
        $resData = array(
            'sended' => '1',
            'cancelled_by' => 'piearsta',
            'cancelled_at' => date('Y-m-d H:i:s', time()),
            'updated' => time(),
            'status' => RESERVATION_WAITS_PAYMENT,
            'status_changed_at' => time(),
            'status_reason' => 'Payment preliminary failed',
        );

        $errorCode = empty($errorCode) ?
            (!empty($_SESSION['paymentError']['code']) ? $_SESSION['paymentError']['code'] : '') : '';

        $failReason = empty($failReason) ?
            (!empty($_SESSION['paymentError']['message']) ? $_SESSION['paymentError']['message'] : '') : '';

        $this->reservation->updateReservation($this->reservation->getReservationId(), $resData);
        $this->order->setStatus(ORDER_STATUS_PENDING, 'Payment preliminary denied by bank: ' . $failReason);

        $updTrData = array(
            'status' => TRANSACTION_STATUS_NON_PAID,
            'error_code' => $errorCode,
            'error_message' => $failReason,
        );

        if (!empty($_SESSION['PaymentInfo']['paid_by'])) {

            $updTrData['payment_method'] = $_SESSION['PaymentInfo']['paid_by'];
        }

        $this->transaction->updateTransaction($trId, $updTrData);

        unset($_SESSION['paymentError']);

        $this->reservation->setStatus(RESERVATION_WAITS_PAYMENT);
        $this->lockRecord->reduceExpirationTime();
        $this->getReservationsCount();

        if ($info['serviceType'] == 1) {
            $this->getConsultationsCount();
        }

        $this->setPData($info, 'info');
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile' . '/tmpl/payment/');
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('payment-fail', $this->getPData()));
    }

    // Payment Cancel handler
    public function paymentCancel($info) {

        // get order html
        $info['order_html'] = $this->getOrderHtml($this->getOrderInfoPopupData($this->order->getOrderId()));

        // set order to new status
        $this->order->setStatus(ORDER_STATUS_NEW, 'Canceled by user');

        // update reservation
        $resData = array(
            'sended' => '1',
            'cancelled_by' => 'profile',
            'cancelled_at' => date(PIEARSTA_DT_FORMAT, time()),
            'updated' => time(),
            'status' => RESERVATION_WAITS_PAYMENT,
            'status_changed_at' => time(),
            'status_reason' => 'Canceled by user',
        );

        $this->reservation->updateReservation($this->reservation->getReservationId(), $resData);

        unset($_SESSION['PaymentInfo']);

        $_SESSION['orderId'] = $this->order->getOrderId();
        $_SESSION['calendarData'] = $info['calendarData'];
        $_SESSION['returning'] = true;

        if(strpos($info['backUrl'], '?')) {
            $info['backUrl'] .= '&orderId=' . $_SESSION['orderId'];
        } else {
            $info['backUrl'] .= '?orderId=' . $_SESSION['orderId'];
        }

        $this->reservation->setStatus(RESERVATION_WAITS_PAYMENT, 'Canceled by user');
        $this->lockRecord->reduceExpirationTime();
        $this->getReservationsCount();

        if ($info['serviceType'] == 1) {
            $this->getConsultationsCount();
        }

        $this->setPData($info, 'info');
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile' . '/tmpl/payment/');
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('payment-cancel', $this->getPData()));
    }

    public function getTransaction() {

        $this->return = array(
            'status' => 0,
            'data' => null,
        );

        if(getP('orderId')) {

            $transData = $this->transaction->getTransaction();

            if($transData['status'] == TRANSACTION_STATUS_PAID || $transData['status'] == TRANSACTION_STATUS_PRELIMINARY_PAID) {
                $this->return = array(
                    'status' => 1,
                    'data' => $transData,
                    'html' => $this->getPaymentDataHtml($transData),
                );
            }
        }
    }

    /**
     * @param $id
     * @throws Exception
     */
    public function cancelReservation($id) {

        if ($id && $reservation = $this->openReservation($id, false)) {

            if(getP('reason') == '') {
                $this->return['errors']['fields']['reason'] = gL('Error_reason_select_Empty');
            } else {

                if(getP('reason') == 'other' && getP('status_reason') == '') {
                    $this->return['errors']['fields']['status_reason_other'] = gL('Error_status_reason_Empty');
                }
            }

            if(getP('refund_account') === '') {
                $this->return['errors']['fields']['refund_account'] = gL('Error_refund_account_Empty');
            }

            if(getP('refund_account') != '') {

//    		    if(!isIBAN(getP('refund_account'))) {
//                    $this->return['errors']['fields']['refund_account'] = gL('Error_refund_account_Invalid');
//                }
            }

            if (empty($this->return['errors'])) {

                $cancellationReason = $this->getCancellationReason(clear(trim(getP('status_reason'))));

                if($reservation['order_id']) {

                    /** @var order order */
                    $this->order = loadLibClass('order');
                    $this->order->setOrder($reservation['order_id']);
                    $order = $this->order->getOrder();
                    $reservation['orderData'] = $order;

                    if(getP('refund')) {

                        $method = '';
                        if($order['transaction_id']) {
                            /** @var transaction transaction */
                            $this->transaction = loadLibClass('transaction');
                            $this->transaction->setTransaction($order['transaction_id']);
                            $transaction = $this->transaction->getTransaction();
                            $reservation['transactionData'] = $transaction;
                            $method = $transaction['payment_method'];
                        }


                        $amount = calculateAmount($reservation['start'], $reservation['service_type'], $reservation['service_price']);

                        if(getP('refund_account_error_case') == '' && $this->cfg->get('everyPayRefundEnabled') && !empty($order['payment_reference']) && !empty($transaction['payment_nonce'])){

                            $refundInfo = '';
                            $everyPayMethod = '';

                            if (substr($method, 0, 8) == 'everyPay') {
                                $everyPayMethod = 'card';
                            }

                            if ($everyPayMethod == 'card') {

                                if ($amount > 0) {
                                    $refund_data = [
                                        "amount" => $amount,
                                        "payment_reference" => $order['payment_reference']
                                    ];

                                    $this->billingSystem->setEveryPayConfig();
                                    $this->billingSystem->setRefundPostData($refund_data);
                                    $result = $this->billingSystem->requestEveryPay('payments_refund');
                                    $this->transaction->saveEveryPayResponse($result);

                                    if ($result['success']) {
                                        $response = json_decode($result['result']);

                                        if ($response->payment_state == 'refunded') {

                                            if (!empty($response->initial_amount) && isset($response->standing_amount)) {
                                                $amount = $response->initial_amount - $response->standing_amount;
                                                $this->transaction->setRefundAmount($amount);
                                            }
                                        }

                                    } else {
                                        $this->return['errors']['fields']['refund_account_error_case'] =
                                            gL('refund_request_error_account_number_manually', 'Lūdzu, norādiet konta numuru, uz kuru veikt naudas atmaksu');
                                        return false;
                                    }
                                } else {
                                    $this->transaction->setRefundAmount('0');
                                }

                                $refundInfo = $method . ':' . $transaction['pan'];

                                $reservation['refundInfo'] = $refundInfo;
                                $reservation['cancellation_reason'] = $cancellationReason . ' | Atmaksājamā summa: 0.00 Eur';
                                $reservation['refundRequested'] = false;
                                $reservation['amountToRefund'] = $amount . 'Eur';

                                // Only information about refund, refund has done by everyPay
                                if ($this->cfg->get('everyPayRefundInformationToSupport')) {
                                    sendRefundInfoEmailToSupport($reservation, $this->tpl);
                                }
                            } else {

                                $refundInfo = $method . ':' . getP('refund_account');
                                $reservation['refundInfo'] = $refundInfo;
                                $reservation['cancellation_reason'] = $cancellationReason . ' | Atmaksājamā summa: ' . $amount . 'Eur';
                                $reservation['refundRequested'] = true;
                                $reservation['amountToRefund'] = $amount . 'Eur';
                                sendRefundEmailToSupport($reservation, $this->tpl);

                            }

                            $this->transaction->setStatus(TRANSACTION_STATUS_REFUNDED);
                            $this->order->setStatus(ORDER_STATUS_REFUNDED, 'Payment refunded');

                        } else {

                            $refundInfo = $method . ':';
                            $reservation['cancellation_reason'] = $cancellationReason;

                            if($method == 'cards') {

                                $refundInfo .= $transaction['pan'];

                            } else {
                                if (getP('refund_account_error_case') != ''){
                                    $refundInfo .= getP('refund_account_error_case');
                                    $reservation['cancellation_reason'] = $cancellationReason . ' | Atmaksājamā summa: ' . $amount . 'Eur';
                                    $reservation['amountToRefund'] = $amount . 'Eur';
                                } else {
                                    $refundInfo .= getP('refund_account');
                                }
                            }

                            $reservation['refundInfo'] = $refundInfo;

                            $reservation['refundRequested'] = true;

                            if(!in_array($method, array('dccard', 'insurance'))) {
                                sendRefundEmailToSupport($reservation, $this->tpl);
                            } else {

                            }

                        }

                        saveValuesInDb('mod_reservations', array(
                            'refund_info' => $refundInfo,
                        ), $id);

                        $this->setPData($reservation, 'prepaidReservation');
                    }
                }

                $delResult = $this->setReservationCanceled($id, $cancellationReason);

                $debugInfo = array(
                    'resData' => $reservation,
                    'deleteResult' => $delResult,
                );

                if(!empty($cancellationResult)) {
                    $debugInfo['cancellationResult'] = $cancellationResult;
                }

                // type is consultation?
                if($reservation['service_type'] == 1 && !empty($reservation['consultation_vroom'])) {

                    // so we should request consultations to update cons status there

                    /** @var consultation $consObj */
                    $consObj = loadLibClass('consultation');
                    $vroomCancelRes = $consObj->cancelVroom($reservation['id'], 3);
                }

                $lang = !empty($this->userData['lang']) ? $this->userData['lang'] : $this->getLang();
                sendReservationEmail($reservation, '3', $lang);

                $freeSlots = false;

                if(!empty($delResult['freeSlots']) && $delResult['freeSlots']) {
                    $freeSlots = true;
                }

                updateSlots($reservation, $freeSlots);

                $this->getReservationsCount();
                $this->getUnreadMessagesCount();


                if (!empty($this->order)){
                    if ($this->order->getStatus() === ORDER_STATUS_PRELIMINARY_PAID){
                        $this->setPData(true, 'orderPreliminaryPaid');
                        $this->setPData($this->cfg->get('supportEmail'), 'supportEmail');
                    }
                }

                $this->return['ok'] = true;
                $this->return['html'] = $this->tpl->output('reservations-cancel-ok', $this->getPData());

                if(DEBUG) {
                    $this->return['vroomCancelRes'] = isset($vroomCancelRes) ? $vroomCancelRes : null;
                    $this->return['debugInfo'] = $debugInfo;
                }
            }
        }
    }

    /**
     * @param $id
     * @param bool $setHtml
     * @return array|bool|int
     */
    public function openReservation($id, $setHtml = true, $withCurrentUser = true, $lang = null) {

        $lang = !empty($lang) ? $lang : $this->getLang();

        $usr = $withCurrentUser ? $this->userId : null;
        $row = createResArray($id, $usr, $lang);

        if(!empty($row)) {

            $template = 'reservations-open';
            if ($row['status'] == 1) {
                $template = 'reservations-open-canceled';
            } elseif ($row['status'] == 3) {
                $template = 'reservations-open-canceled';
            }

            $tmplDir = $this->tpl->getTmplDir();
            $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');

            $row['cssFolder'] = APP_ROOT . '/' . AD_CSS_SRC_FOLDER;
            $row['imageFolder'] = APP_ROOT . '/img/';

            $this->setPData($row, "item");

            if ($setHtml) {
                $this->return['html'] = $this->tpl->output($template, $this->getPData());
            }
            $this->tpl->setTmplDir($tmplDir);

            return $row;
        }

        return false;
    }

    public function openOrder() {

        $dataOrder = $this->order->getOrder();
        $orderDetails = $this->order->getOrderDetails();

        $data = array();
        $data['orderId'] = $dataOrder['id'];
        $data['reservationId'] = $dataOrder['reservation_id'];
        $data['date'] = $dataOrder['date'];
        $data['serviceName'] = $orderDetails[0]['service_name'];
        $data['total'] = $dataOrder['order_total'];
        $data['status'] = $this->config['order_states'][$dataOrder['status']];
        $data['showInvoice'] = in_array($dataOrder['status'], array(ORDER_STATUS_PRELIMINARY_PAID, ORDER_STATUS_PAID, ORDER_STATUS_REFUNDED));

        $this->setPData($data, "item");

        $template = 'order-open';
        $this->return['html'] = $this->tpl->output($template, $this->getPData());
    }

    public function openInvoice() {

        $data = $this->order->getOrder();
        $data['bb'] = $this->cfg->get('paymentReceiver')['title'];
        $data['orderId'] = $this->order->getOrderId();

        $transactionData = $this->transaction->getTransaction();

        $data['payment_data'] = $this->getPaymentDataHtml($transactionData);
        $data['order_html'] = $this->getOrderHtml($this->getOrderInfoPopupData($data['orderId']));

        $method = null;

        switch ($transactionData['payment_method']) {
            case 'dccard' :
                $method = gL('profile_reservation_dc_subscription', 'Digital Clinic abonements');
                break;
            case 'insurance' :
                $method = gL('profile_reservation_insurance', 'Apdrošināšanas polise');
                break;
            case 'cards' :
                $method = gL('profile_rezervation_payment_status_card', 'card');
                break;
            case substr($transactionData['payment_method'] , 0, 8) == 'everyPay' :
                $method = gL('profile_rezervation_payment_status_card', 'card');
                break;
            default :
                $method = gL('profile_rezervation_payment_status_bank', 'bank transfer') . ' (' . $transactionData['payment_method'] . ' internetbanka)';
        }

        $data['payment_status'] = gL('profile_rezervation_payment_status_1', 'Paid by') . ': ' . $method;

        $this->setPData($data, "item");

        $template = 'invoice-open';
        $this->return['html'] = $this->tpl->output($template, $this->getPData());
    }

    public function getProfilePersons() {

        $dbQuery = "SELECT p.*
							FROM `" . $this->cfg->getDbTable('profiles', 'persons')	 . "` p
							WHERE 1
								AND p.`profile_id` = '" . mres($this->userId) . "'
							ORDER BY created DESC";
        $query = new query($this->db, $dbQuery);
        $this->userData['persons'] = $query->getArray('id');
        $_SESSION['user']['persons'] = $this->userData['persons'];

        $this->setPData($this->userData, 'userData');
    }

    /**
     * @param $id
     * @return array|int
     */
    private function getPersonDataById($id) {
        $dbQuery = "SELECT p.*
							FROM `" . $this->cfg->getDbTable('profiles', 'persons')	 . "` p
							WHERE 1
								AND p.`profile_id` = '" . mres($this->userId) . "'
								AND p.id = '" . mres($id) . "'";
        $query = new query($this->db, $dbQuery);
        return $query->getrow();
    }

    public function getProfilePersonsAddForm() {

        if (getG('id')) {
            $dbQuery = "SELECT p.*
							FROM `" . $this->cfg->getDbTable('profiles', 'persons')	 . "` p
							WHERE 1
								AND p.`profile_id` = '" . mres($this->userId) . "'
								AND p.id = '" . mres(getG('id')) . "'
							LIMIT 1";
            $query = new query($this->db, $dbQuery);
            if ($query->num_rows()) {
                $row = $query->getrow();
                $row['pc'] = explode('-', $row['person_id']);

                if ($row['date_of_birth']) {
                    $row['date_of_birth_splited'] = explode('-', $row['date_of_birth']);
                }

                $this->setPData($row, "person");
            } else {
                openDefaultPage();
            }
        }

        if (getP('action') == 'add') {

            $validator = loadLibClass('validator');
            $validator->setFieldsArray($this->fieldsArray['add_person']);

            foreach ($this->fieldsArray['add_person'] AS $field => $data) {
                $validator->checkValue($field, getP('fields/' . $field));
            }

            $result = $validator->returnData();

            if (!getP('fields/bd_date') || !getP('fields/bd_month') || !getP('fields/bd_year')) {
                $result['errorFields']['date_of_birth'] = gL('order_Errors_Fields_DateOfBirth');
            }

            if (empty($result['error'])) {

                $dbData = array();
                $dbData['profile_id'] = $this->userId;
                $dbData['name'] = getP('fields/name');
                $dbData['surname'] = getP('fields/surname');
                $dbData['person_id'] = getP('fields/person_id');
                $dbData['phone'] = getP('fields/phone');
                $dbData['resident'] = getP('fields/resident');
                $dbData['gender'] = getP('fields/gender');
                $dbData['person_number'] = getP('fields/person_number');
                $dbData['created'] = time();

                if (getP('fields/resident') == 1) {
                    $dbData['person_id'] = getP('fields/person_id');
                    $dbData['person_number'] = null;
                } else {
                    $dbData['person_id'] = null;
                    $dbData['person_number'] = getP('fields/person_number');
                }

                if (getP("fields/bd_year") && getP("fields/bd_month") && getP("fields/bd_date")) {
                    $dbData['date_of_birth'] = getP("fields/bd_year") . "-" . str_pad(getP("fields/bd_month"), 2, "0", STR_PAD_LEFT) . "-" . getP("fields/bd_date");
                }

                $saveRes = saveValuesInDb($this->cfg->getDbTable('profiles', 'persons'), $dbData, getP('fields/id'));

                if(DEBUG) {
                    $this->return['debug'] = array(
                        'dataSent' => $dbData,
                        'saveRes' => $saveRes,
                    );
                }

                $this->return['location'] = getLM($this->cfg->getData('mirros_persons_page')) . '?person-added';

            } else {
                $this->return['errors']['fields'] = $result['errorFields'];
                return false;
            }

        }
    }

    public function generateCouponNumber($id) {
        $dbQuery = "SELECT count(profile_id)
							FROM `" . $this->cfg->getDbTable('profiles', 'coupons')	 . "` p
							WHERE 1
								AND p.`coupon_id` = '" . mres($id) . "'";
        $query = new query($this->db, $dbQuery);
        return $query->getOne() + 1;
    }

    public function generateCouponCode($number, $prefix = 'PC') {

        return $prefix . $this->userId . $number . rand(100, 999);
    }

    public function pdfCoupon($id) {
        $dbQuery = "SELECT p.*
							FROM `" . $this->cfg->getDbTable('profiles', 'coupons')	 . "` p
							WHERE 1
								AND p.`profile_id` = '" . mres($this->userId) . "'
								AND p.`coupon_id` = '" . mres($id) . "'
							LIMIT 1";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $query->getrow();
            if (file_exists(AD_SERVER_UPLOAD_FOLDER . $this->config['couponsFolder'] . $query->field('filename'))) {
                $this->return['location'] = AD_UPLOAD_FOLDER . $this->config['couponsFolder'] . $query->field('filename');
                return true;
            }

        }

        $dbQuery = "SELECT c.*
							FROM `" . $this->cfg->getDbTable('coupons', 'self')	 . "` c
							WHERE 1
								AND c.`id` = '" . mres($id) . "'
							LIMIT 1";
        $query = new query($this->db, $dbQuery);
        $coupon = $query->getrow();

        $dbData = array();
        $dbData['profile_id'] = $this->userId;
        $dbData['coupon_id'] = $id;
        $dbData['pcreated'] = time();
        $dbData['code'] = $coupon['number'] . '-' . $this->generateCouponNumber($id) . '-' . date('d.m.Y', $coupon['date_to']);
        $dbData['filename'] = $dbData['code'] . '.pdf';

        saveValuesInDb($this->cfg->getDbTable('profiles', 'coupons'), $dbData);

        $dbQuery = "SELECT p.*, c.*, cd.*
							FROM `" . $this->cfg->getDbTable('profiles', 'coupons')	 . "` p
								LEFT JOIN `" . $this->cfg->getDbTable('coupons', 'self')	 . "` c ON (p.coupon_id = c.id)
								LEFT JOIN `" . $this->cfg->getDbTable('coupons', 'details')	 . "` cd ON (c.id = cd.coupon_id AND cd.lang = '" . $this->getLang() . "')
							WHERE 1
								AND p.`profile_id` = '" . mres($this->userId) . "'
								AND p.`coupon_id` = '" . mres($id) . "'
							LIMIT 1";
        $query = new query($this->db, $dbQuery);
        $coupon = $query->getrow();
        $this->setPData($coupon, "coupon");
        $html = $this->tpl->output('coupon/coupons_pdf.html', $this->getPData());
        $footer = $this->tpl->output('coupon/footer-pdf.html', $this->getPData());

//      	unlink(AD_SERVER_UPLOAD_FOLDER . $this->config['couponsFolder'] . 'test.html');
//      	file_put_contents(AD_SERVER_UPLOAD_FOLDER . $this->config['couponsFolder'] . 'test.html', $html);
//      	deleteFromDbById($this->cfg->getDbTable('profiles', 'coupons'), $this->userId, "profile_id");

        $customOptions = array(
            'page-size' => 'A4',
            'dpi' => '300',
            'zoom' => '1',
            'margin-top' => '5mm',
            'margin-bottom' => '20mm',
            'margin-left' => '5mm',
            'margin-right' => '5mm',
            'footer-html' => $footer,
        );


        require_once(AD_LIB_FOLDER . 'wkhtmltopdf/Pdf.php');
        $pdf = new Pdf($customOptions);
        $pdf->setBinary($this->cfg->get('wkhtmltopdf'));
        $pdf->addPage($html);
        $pdf->saveAs(AD_SERVER_UPLOAD_FOLDER . $this->config['couponsFolder'] . $dbData['filename']);

        $this->return['location'] = AD_UPLOAD_FOLDER . $this->config['couponsFolder'] . $dbData['filename'];
        return true;

    }

    public function subscribe($email)
    {
        $validator = loadLibClass('validator');
        if ($validator->checkEmail($email, 'subscribe_email')) {
            $dbData = array();
            $dbData[] = " `blocked` = 0 ";
            $dbData[] = " `lang` = '" . $this->getLang() . "' ";
            $dbData[] = " `email` = '" . mres($email) . "' ";

            $dbQuery = "INSERT INTO `mod_newsletter` SET `created` = '" . time() . "', " . implode(',', $dbData) .
                " ON DUPLICATE KEY UPDATE " . implode(',', $dbData);
            $query = new query($this->db, $dbQuery);
            $this->return['ok'] = true;
        } else {
            $this->return['error'] = true;
        }
    }

    public function addMessage($subject, $body)
    {
        if ($this->userId) {

            $dbData = array();
            $dbData['profile_id'] = $this->userId;
            $dbData['message'] = $body;
            $dbData['subject'] = $subject;
            $dbData['created'] = time();

            saveValuesInDb($this->cfg->getDbTable('profiles', 'messages'), $dbData);

            $this->getUnreadMessagesCount();
        }
    }

    public function pdfReservation($id) {
        $dbQuery = "SELECT r.*
							FROM `" . $this->cfg->getDbTable('reservations', 'self')	 . "` r
							WHERE 1
								AND r.`profile_id` = '" . mres($this->userId) . "'
								AND r.`id` = '" . mres($id) . "'
							LIMIT 1";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $query->getrow();
            if ($query->field('filename') &&
                file_exists(AD_SERVER_UPLOAD_FOLDER . $this->config['reservationFolder'] . $query->field('filename'))) {
                $this->return['location'] = AD_UPLOAD_FOLDER . $this->config['reservationFolder'] . $query->field('filename');
                return true;
            }

            $filename = base64_encode(md5($id . time() . $this->userId . "jUk2Nk2$31") . "iHn3bnj34") . ".pdf";

            $dbData = array();
            $dbData['filename'] = $filename;

            saveValuesInDb($this->cfg->getDbTable('reservations', 'self'), $dbData, $id);

            $this->openReservation($id);
            $html = $this->tpl->output('reservation/pdf.html', $this->getPData());
            $footer = $this->tpl->output('reservation/footer-pdf.html', $this->getPData());

            unlink(AD_SERVER_UPLOAD_FOLDER . $this->config['reservationFolder'] . 'test.html');
            file_put_contents(AD_SERVER_UPLOAD_FOLDER . $this->config['reservationFolder'] . 'test.html', $html);
            //deleteFromDbById($this->cfg->getDbTable('profiles', 'coupons'), $this->userId, "profile_id");

            $customOptions = array(
                'page-size' => 'A4',
                'dpi' => '300',
                'zoom' => '1',
                'margin-top' => '5mm',
                'margin-bottom' => '15mm',
                'margin-left' => '5mm',
                'margin-right' => '5mm',
                'footer-html' => $footer,
            );


            require_once(AD_LIB_FOLDER . 'wkhtmltopdf/Pdf.php');
            $pdf = new Pdf($customOptions);
            $pdf->setBinary($this->cfg->get('wkhtmltopdf'));
            $pdf->addPage($html);
            $result = $pdf->saveAs(AD_SERVER_UPLOAD_FOLDER . $this->config['reservationFolder'] . $dbData['filename']);

            if(!$result) {
                $this->return['pdfError'] = $pdf->getError();
                return false;
            } else {
                $this->return['location'] = AD_UPLOAD_FOLDER . $this->config['reservationFolder'] . $dbData['filename'];
                return true;
            }


        } else {
            return false;
        }
    }

    /**
     * @return bool|string
     */
    private function generatePdfInvoice() {

        if(!$this->order) {
            return false;
        }

        // collect order data
        $orderData = $this->order->getOrder();
        $orderId = $orderData['id'];
        $orderDetails = $this->order->getOrderDetails();
        $orderInfo = $this->order->getOrderInfo();

        $serviceType = intval($orderDetails[0]['service_type']);

        // set file path and filename
        $folder = 'profile/invoices/';
        $filepath = AD_SERVER_UPLOAD_FOLDER . $folder;
        // invoice-[userId]-[orderId]-[timestamp].pdf
        $filename = 'invoice-' . $orderInfo['creator_id'] . '-' . $orderId . '-' . time() . '.pdf';
        $filepath .= $filename;

        // if pdf file exists we delete it first
        //
        if($orderInfo['invoice_filename'] && $orderInfo['invoice_filename'] != '') {

            $filename = $orderInfo['invoice_filename'];

            if(file_exists($filepath . $filename)) {
                unlink($filepath . $filename);
            }
        }

        // then we generate new one

        // prepare data
        $data = $transactionData = $this->transaction->getTransaction();

        $paymentReceiver = $this->cfg->get('paymentReceiver');
        $receiverName = $paymentReceiver['title'];
        $showTitle = $paymentReceiver['showTitle'];
        $receiverLogo = $paymentReceiver['logo'];

        //$data['receiverName'] = $receiverName;

        // collect payment data

        $pk = $orderInfo['creator_person_id'] ? $orderInfo['creator_person_id'] : $orderInfo['creator_person_number'];
        $payer = $orderInfo['creator_name'] . ' ' . $orderInfo['creator_surname'];

        if (!empty($_SESSION['PaymentInfo']['paid_by'])) {
            $paymentMethod = $_SESSION['PaymentInfo']['paid_by'];
        } else {
            $paymentMethod = $transactionData['payment_method'];
        }

        if (substr($paymentMethod, 0, 8) == 'everyPay') {
            if (($pos = strpos($paymentMethod, "/")) !== false) {
                $transactionData['payment_method'] = $paymentMethod;
                $data['payment_method'] = substr($paymentMethod, $pos + 1);
                $data['card_everyPay'] = true;
            }
        } else {
            $transactionData['payment_method'] = $paymentMethod;
            $data['payment_method'] = $paymentMethod;
        }

        $data['banklink'] = (
            $transactionData['payment_method'] != 'cards' &&
            $transactionData['payment_method'] != 'dccard' &&
            $transactionData['payment_method'] != 'insurance' &&
            substr($paymentMethod , 0, 8) != 'everyPay'
        );

        if(empty($data['pan'])) {
            $data['pan'] =  $_SESSION['PaymentInfo']['last_four_digits'] ?  $_SESSION['PaymentInfo']['last_four_digits'] : null;
        }

        if(empty($data['auth_code'])) {
            $data['auth_code'] =  $_SESSION['PaymentInfo']['stan'] ?  $_SESSION['PaymentInfo']['stan'] : null;
        }

        $dbQuery = "SELECT * FROM mod_profiles 
                    WHERE
                        id = " . $orderInfo['creator_id'];

        $query = new query($this->db, $dbQuery);

        $userData = $query->getrow();

        if(!empty($userData['insurance_id'])) {

            $insDbQuery = "SELECT * FROM mod_classificators_info 
                            WHERE
                                c_id = " . $userData['insurance_id'];

            $query = new query($this->db, $insDbQuery);

            if($query->num_rows()) {
                $row = $query->getrow();
                $userData['insurance'] = $row['title'];
            }
        }

        $insurance = $this->insurance->getInsuranceData($userData, $orderData['clinic_id']);

        $data['insurance'] = $transactionData['payment_method'] == 'insurance';
        $data['insuranceName'] = !empty($insurance['companyName']) ? $insurance['companyName'] : '';
        $data['insuranceNumber'] = !empty($insurance['insuranceNumber']) ? $insurance['insuranceNumber'] : '';

        $data['payer'] = $payer;
        $data['date'] = $transactionData['fulfill_date'] ? $transactionData['fulfill_date'] : $transactionData['updated'];
        $data['pk'] = $pk;

        // we show 6 asterisks and 4 last digits from pan
        if(isset($data['pan'])) {
            $newPan = '******' . substr($data['pan'], -4);
            $data['pan'] = $newPan;
        }

        // save current tmplDir
        $tmplDir = $this->tpl->getTmplDir();

        // get payment html
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
        $this->setPData($data, "item");
        $paymentHtml =  $this->tpl->output('payment-data', $this->getPData());

        $data = array_merge($orderData, $orderInfo);
        $data['payment_data'] = $paymentHtml;
        $data['orderItems'] = array();
        $data['orderItems'] = $orderDetails;
        $data['bb'] = $this->cfg->get('bb');
        $data['date'] = $orderData['date'];
        $data['orderId'] = $data['order_id'];
        $data['reservationId'] = $orderData['reservation_id'];

        // check doctor
        if(!$this->reservation) {
            $this->reservation = loadLibClass('reservation');
            $this->reservation->setReservation($orderData['reservation_id']);
        }

        $resData = $this->reservation->getReservation();

        if(!$resData['doctor_id']) {
            $data['doctor_id'] = null;
            $data['doctor_name'] = null;
            $data['doctor_surname'] = null;
        }

        $patient = array();

        if(!empty($data['person_id']) && !empty($data['person_name']) && !empty($data['person_surname'])) {

            $patient['name'] = $data['person_name'];
            $patient['surname'] = $data['person_surname'];
            $patient['pk'] = !empty($data['person_person_id']) ? $data['person_person_id'] : $data['person_person_number'];
            $patient['phone'] = !empty($data['person_phone']) ? $data['person_phone'] : null;

        } else {

            $patient['name'] = $data['creator_name'];
            $patient['surname'] = $data['creator_surname'];
            $patient['pk'] = !empty($data['creator_person_id']) ? $data['creator_person_id'] : $data['creator_person_number'];
            $patient['phone'] = !empty($data['creator_phone']) ? $data['creator_phone'] : null;
        }

        $data['patient'] = $patient;

        // get order html
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/order/');
        $this->setPData($data, "item");
        $orderHtml =  $this->tpl->output('to-pdf', $this->getPData());

        $invoiceData = array();

        if($showTitle) {
            $invoiceData['receiverName'] = $receiverName;
        }

        if($receiverLogo) {
            $invoiceData['receiverLogo'] = $receiverLogo;
        }

        if(!$receiverLogo && !$showTitle) {
            $invoiceData['receiverName'] = $receiverName;
        }

        $invoiceData['payment_data'] = $paymentHtml;
        $invoiceData['order_html'] = $orderHtml;

        $method = null;

        switch ($transactionData['payment_method']) {
            case 'dccard' :
                $method = gL('profile_reservation_dc_subscription', 'Digital Clinic abonements');
                break;
            case 'insurance' :
                $method = gL('profile_reservation_insurance', 'Apdrošināšanas polise');
                break;
            case 'cards' :
                $method = gL('profile_rezervation_payment_status_card', 'card');
                break;
            case substr($transactionData['payment_method'] , 0, 8) == 'everyPay' :
                $method = gL('profile_rezervation_payment_status_card', 'card');
                break;
            default :
                $method = gL('profile_rezervation_payment_status_bank', 'bank transfer') . ' (' . $transactionData['payment_method'] . ' internetbanka)';
        }

        $invoiceData['payment_status'] = gL('profile_rezervation_payment_status_1', 'Paid by') . ': ' . $method;

        // set css path for wkhtmltopdf
        $invoiceData['cssFolder'] = APP_ROOT . '/' . AD_CSS_SRC_FOLDER;
        $invoiceData['imageFolder'] = APP_ROOT . '/img/';

        // template will come from config - ?
        $template = 'invoice-pdf.html';

        // get invoice html
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/order/');
        $this->setPData($invoiceData, "item");
        $invoiceHtml = $this->tpl->output($template, $this->getPData());
        $footer = '';

        $customOptions = array(
            'page-size' => 'A4',
            'dpi' => '300',
            'zoom' => '1',
            'margin-top' => '15mm',
            'margin-bottom' => '15mm',
            'margin-left' => '5mm',
            'margin-right' => '20mm',
            'footer-html' => $footer,
        );

        // generate pdf
        require_once(AD_LIB_FOLDER . 'wkhtmltopdf/Pdf.php');
        $pdf = new Pdf($customOptions);
        $pdf->setBinary($this->cfg->get('wkhtmltopdf'));
        $pdf->addPage($invoiceHtml);
        $result = $pdf->saveAs($filepath);

        // restore tmpl dir
        $this->tpl->setTmplDir($tmplDir);

        if(!$result) {
            // return error
            return $pdf->getError();
        } else {
            $infData = array(
                'invoice_filename' => $filename,
            );

            $this->order->setOrderInfo($infData);

            // return filename on success
            return $filename;
        }
    }

    // DEBUG function
    public function testPdfInvoice() {
        if(!$this->order) {
            return false;
        }

        $this->return = array();

        $orderData = $this->order->getOrder();
        $orderInfo = $this->order->getOrderInfo();

        $orderId = $orderData['id'];

        // prepare module and tmpl instances
        /** @var module $module */
        $module = loadLibClass('module');
        /** @var tmpl $tpl */
        $tpl = loadLibClass('tmpl');


        $receiverName = $this->cfg->get('paymentReceiver')['title'];

        $data['receiverName'] = $receiverName;
        $data['order_html'] = $this->getOrderHtml($this->getOrderInfoPopupData($orderId));
        $data['payment_data'] = $this->getPaymentDataHtml($this->transaction->getTransaction());

        $data['cssFolder'] = APP_ROOT . '/' . AD_CSS_SRC_FOLDER;

        $template = 'invoice-pdf.html';

        $tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/order/');
        $module->setPData($data, "item");
        $html = $tpl->output($template, $module->getPData());
        $footer = '';

//      	unlink(AD_SERVER_UPLOAD_FOLDER . $this->config['couponsFolder'] . 'test.html');
//      	file_put_contents(AD_SERVER_UPLOAD_FOLDER . $this->config['couponsFolder'] . 'test.html', $html);
//      	deleteFromDbById($this->cfg->getDbTable('profiles', 'coupons'), $this->userId, "profile_id");

        $customOptions = array(
            'page-size' => 'A4',
            'dpi' => '300',
            'zoom' => '1',
            'margin-top' => '15mm',
            'margin-bottom' => '20mm',
            'margin-left' => '5mm',
            'margin-right' => '20mm',
            'footer-html' => $footer,
        );



        $folder = 'profile/invoices/';
        $filename = AD_SERVER_UPLOAD_FOLDER . $folder;
        // invoice-[userId]-[orderId]-[timestamp].pdf
        $filename .= 'invoice-' . $orderInfo['creator_id'] . '-' . $orderId . '-' . time() . '.pdf';

        $file = 'invoice-' . $orderInfo['creator_id'] . '-' . $orderId . '-' . time() . '.pdf';

        require_once(AD_LIB_FOLDER . 'wkhtmltopdf/Pdf.php');
        $pdf = new Pdf($customOptions);
        $pdf->setBinary($this->cfg->get('wkhtmltopdf'));
        $pdf->addPage($html);

        $result = $pdf->saveAs($filename);

        if(!$result) {
            $error = $pdf->getError();
            $this->return['error'] = true;
            $this->return['error_message'] = $error;
        } else {
            $this->return['result'] = 'OK';
            $this->return['filename'] = $filename;
            $this->return['file'] = $file;
        }
    }

    /**
     * @param $order_id
     * @return bool|false[]
     */
    public function setRequestedOrder($order_id) {

        if(!$order_id) {
            $this->return['result'] = false;
        }

        $this->order->setOrder($order_id);
        $orderInfoData = $this->order->getOrderInfo();

        if(
            !$order_id ||
            $order_id != $orderInfoData['order_id'] ||
            $_SESSION['user']['id'] != $orderInfoData['creator_id']
        ) {
            $this->return['result'] = false;
        }

        $_SESSION['reqOrd'] = md5($order_id . '_' . $_SESSION['user']['id']);

        $this->return['result'] = !empty($_SESSION['reqOrd']);
    }

    public function openPdfInvoice() {

        $folder = 'profile/invoices/';
        $filepath = AD_SERVER_UPLOAD_FOLDER . $folder;
        $this->order->setOrder(getG('orderId'));
        $orderInfoData = $this->order->getOrderInfo();
        $filename = $orderInfoData['invoice_filename'];
        $tokenFromSession = isset($_SESSION['reqOrd']) && $_SESSION['reqOrd'] ? $_SESSION['reqOrd'] : null;

        unset($_SESSION['reqOrd']);
        unset($_SESSION['reqOrdStr']);

        $token = md5($orderInfoData['order_id'] . '_' . $_SESSION['user']['id']);

        if(
            $orderInfoData['creator_id'] != $_SESSION['user']['id'] ||
            !$tokenFromSession ||
            $token != $tokenFromSession
        ) {
            header("404 Not Found", true, 404);

            // TODO: change to template 404
            echo "<h1>404 Page not found</h1>";
            echo "<br>";
            echo "<h3><a href='/'>Return to Home Page</a></h3>";
            exit;
        }

        if(!$filename || !file_exists($filepath . $filename)) {
            $filename = $this->generatePdfInvoice();

            $data = array();
            $data['invoice_filename'] = $filename;
            saveValuesInDb('mod_order_info', $data, $orderInfoData['id']);
        }

        $filepath .= $filename;

        header('Content-Type: application/pdf');
        header(sprintf("Content-disposition: inline;filename=%s", basename($filepath)));
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($filepath));
        header('Accept-Ranges: bytes');

        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        ini_set('zlib.output_compression','0');

        @readfile($filepath);

        exit;
    }

    public function arstiemForm()
    {
        $tplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
        $form = $this->tpl->output('arstiem_form', $this->getPData());
        $this->setPData($form, 'form');
        $this->tpl->setTmplDir($tplDir);
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('arstiem', $this->getPData()));
    }

    public function arstiemAddDoctor() {

        if(isset($_POST['action']) && $_POST['action'] = 'arstiemAddDoctor') {
            // validate

            /** @var validator $validator */
            $validator = loadLibClass('validator');
            $validator->setFieldsArray($this->fieldsArray['arstiemForm']);

            foreach ($this->fieldsArray['arstiemForm'] as $field => $data) {
                $validator->checkValue($field, getP($field));
            }

            $result = $validator->returnData();

            if (empty($result['error'])) {

                // if success
                // send email to sales@smartmedical.lv

                $email = $this->cfg->get('arstiem')['email'];

//                $to = "andrejs.vorosnins@bb-tech.eu";
                $to = $email;

                $subject = "Ārstiem: Add doctor request";

                $message = "<p>Jūsu vārds un uzvārds: " . getP('full_name') . "</p>";
                $message .= "<p>E-pasta adrese: " . getP('a_email') . "</p>";
                $message .= "<p>Telefona numurs: " . getP('phone') . "</p>";

                $headers = array();

//                $sender = getP('a_email');
                $sender = 'piearsta@piearsta.lv';

                $result = $this->sendAddDoctorMail($to, $subject, $message, $headers, $sender);

                if(DEBUG) {
                    $this->return['mailResult'] = $result;
                }

                // Show paldies message
                $tmplDir = $this->tpl->getTmplDir();
                $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/content/tmpl/');
                $this->return['html'] = $this->tpl->output('arstiem-paldies.html');
                $this->tpl->setTmplDir($tmplDir);

            } else {
                $this->return['errors']['fields'] = $result['errorFields'];
            }
        }
    }

    public function piesakiArstuForm() {
        $form = $this->tpl->output('pieasaki_arstu_form', $this->getPData());
        $this->setPData($form, 'form');
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('piesaki_arstu', $this->getPData()));
    }

    public function piesakiArstuAddDoctor() {

        if(isset($_POST['action']) && $_POST['action'] = 'piesakiArstuForm') {
            // validate

            /** @var validator $validator */
            $validator = loadLibClass('validator');
            $validator->setFieldsArray($this->fieldsArray['piesakiArstuForm']);

            foreach ($this->fieldsArray['piesakiArstuForm'] as $field => $data) {
                $validator->checkValue($field, getP($field));
            }

            $result = $validator->returnData();

            if (empty($result['error'])) {

                // if success
                // send email to sales@smartmedical.lv

                $email = $this->cfg->get('pieasakiArstu')['email'];

                $this->return['debug'] = $email;

//                $to = "andrejs.vorosnins@bb-tech.eu";
                $to = $email;

                $subject = "Piesaki arstu: Add doctor request";

                $message = "<p>No: " . $this->userData['email'] . "</p>";
                $message .= "<p>[" . $this->userData['name'] . ' ' . $this->userData['surname'] . "]</p>";

                $message .= "<br>";

                $message .= "<p>Ārsta vārds un uzvārds: " . getP('doctor_name') . "</p>";
                $message .= "<p>Ārsta specialitāte: " . getP('specialty') . "</p>";

                if(getP('clinic')) {
                    $message .= "<p>Iestāde, kur pieņem ārsts: " . getP('clinic') . "</p>";
                }

                if(getP('note')) {
                    $message .= "<br>";
                    $message .= "<p>Piezīme: " . getP('note') . "</p>";
                }

                $headers = array();

//                $sender = $this->userData['email'];
                $sender = 'piearsta@piearsta.lv';

                $result = $this->sendAddDoctorMail($to, $subject, $message, $headers, $sender);

                if(DEBUG) {
                    $this->return['mailResult'] = $result;
                }

                // And show paldies message
                $tmplDir = $this->tpl->getTmplDir();
                $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
                $this->return['html'] = $this->tpl->output('piesaki-arstu-paldies.html');
                $this->tpl->setTmplDir($tmplDir);

            } else {
                $this->return['errors']['fields'] = $result['errorFields'];
            }
        }
    }

    /**
     * @param $to
     * @param $subject
     * @param $message
     * @param array $headers
     * @param string $sender
     * @return array|bool
     */
    private function sendAddDoctorMail($to, $subject, $message, $headers = array(), $sender = '') {

        $result = sendMail($to, $subject, $message, $headers, $sender);

        if(DEBUG) {
            return array(
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'sendMailResult' => $result,
            );
        }

        return $result;
    }

    public function countryAutocomplete($q) {
        $q = trim(mres($q));
        $result = array();
        if($q) {

            $dbQuery = "SELECT * FROM kl_valstis
                        WHERE
                            title LIKE '%" . $q . "%'";
            $query = new query($this->db, $dbQuery);

            $countries = $query->getArray();

            if (count($countries) > 0) {
                foreach ($countries AS $country) {
                    $result[] = $country['title'] . " (" . $country['code2'] . ")";
                }
            }
        }

        return $result;
    }

    // get country title by 2 letter code

    /**
     * @param $code
     * @return string|null
     */
    public function getCountryByCode($code) {

        $dbQuery = "SELECT title FROM kl_valstis WHERE code2 = '" . mres($code) . "' LIMIT 1";
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $row = $query->getrow();
            return $row['title'] . " (". $code . ")";
        }

        return null;
    }

    // profile is verifiable only if user's residence country is in allowed country list

    /**
     * @param $countryCode
     * @return bool
     */
    public function isVerifiable($countryCode) {

        /** @var array $allowedCountries */
        $allowedCountries = $this->cfg->get('verifiableCountries');

        return in_array(strtoupper($countryCode), $allowedCountries);
    }


    // check if profile is verified

    /**
     * @param $data
     * @return array
     */
    public function isProfileVerified($data) {

        $expired = false;
        $verified = $data['verified_at'] && $data['verification_method'];

        // check expiration
        if($verified) {
            $expiresAfter = $this->cfg->get('verification_expires_after');
            $expirationDate = strtotime('+' . $expiresAfter, $data['verified_at']);

            if($expirationDate > time()) {
                $verified = true;
            } else {
                $expired = true;
                $verified = false;
            }
        }

        return array(
            'verified' => $verified,
            'expired' => $expired,
        );
    }

    /**
     * @param bool $returnBool
     * @return bool
     */
    public function isManiDatiAvailable($returnBool = false) {

        $url = $this->cfg->get('maniDatiUrl');

        if(isDomainAvailible($url)) {

            @sync_session();

            if($returnBool) {

                return true;

            } else {

                $this->return['success'] = true;
                $this->return['url'] = $url;
            }

        } else {

            if($returnBool) {

                return false;

            } else {

                $this->return['success'] = false;
            }
        }
    }

    public function moreReservations() {
        $res = $this->getReservationList();
        $this->userData['reservations'] = $res['reservations'];
        $count = $res['resCount'];
        $showMore = $res['showMore'];

        $this->setPData($count, 'resCount');
        $this->setPData($showMore, 'more');
        $this->setPData($this->userData, 'userData');

        $tmplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');

        $this->return = array(
            'items' => $res['reservations'],
            'showMore' => $showMore,
            'count' => $count,
            'html' => $this->tpl->output('reservations_list', $this->getPData()),
        );

        $this->tpl->setTmplDir($tmplDir);
    }

    public function moreConsultations() {
        $cons = $this->getReservationList(true);
        $this->userData['consultations'] = $cons['reservations'];
        $count = $cons['resCount'];
        $showMore = $cons['showMore'];

        $this->setPData($count, 'resCount');
        $this->setPData($showMore, 'more');
        $this->setPData($this->userData, 'userData');

        $tmplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');

        $this->return = array(
            'items' => $cons['reservations'],
            'showMore' => $showMore,
            'count' => $count,
            'html' => $this->tpl->output('consultations_list', $this->getPData()),
        );

        $this->tpl->setTmplDir($tmplDir);
    }

    public function moreMessages() {
        $mess = $this->getMessageList();
        $this->userData['messages'] = $mess['messages'];
        $count = $mess['messCount'];
        $showMore = $mess['showMore'];

        $this->setPData($count, 'messCount');
        $this->setPData($showMore, 'more');
        $this->setPData($mess['messages'], 'messages');

        $tmplDir = $this->tpl->getTmplDir();
        $this->tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');

        $this->return = array(
            'items' => $mess['messages'],
            'showMore' => $showMore,
            'count' => $count,
            'html' => $this->tpl->output('messages_list', $this->getPData()),
        );

        $this->tpl->setTmplDir($tmplDir);
    }

    /**
     * Use same sql where clause in getReservationList, getReservationsCount, getConsultationsCount
     * to get same items and count
     *
     * @param bool $cons
     * @param int $profilePersonId
     * @param int $statusSelected
     * @param bool $forSiderbar
     *
     * @return string $where
     */
    private function getReservationsWhereClause($cons = false, $profilePersonId = null, $statusSelected = null, $forSiderbar = false) {

        $whereArr = array();

        $whereArr[] = "r.profile_id = '" . mres($this->userId) . "'";

        if($cons) {
            $whereArr[] = "r.service_type = 1";
        }

        if ($profilePersonId && $profilePersonId != 'all') {
            if ($profilePersonId == 'my') {
                $whereArr[] = "(r.profile_person_id = '0' OR r.profile_person_id IS NULL OR r.profile_person_id = '')";
            } else {
                $whereArr[] = "r.profile_person_id = '" . mres($profilePersonId) . "'";
            }
        }

        if ($statusSelected != null && in_array((int) $statusSelected, array(0, 1, 2, 3, 4))) {
            $whereArr[] = "r.status = " . (int) $statusSelected;
        }
        else if ($forSiderbar === true) {
            $whereArr[] = "r.status IN (0, 2)";
        }
        else {
            $whereArr[] = "r.status IN (0, 1, 2, 3, 4)";
        }

        // exclude piearsta system aborted reservations - canceled from popup, or by watchdog
        $whereArr[] = "r.status <> 5";
        $whereArr[] = "(r.status_reason <> '@/toBeDeleted' OR r.status_reason IS NULL)";

        // special condition to check if it is not an interrupted paid reservation that was occasionally confirmed from SM api

        $whereArr[] = "
            (r.order_id IS NULL OR (r.order_id IS NOT NULL AND (SELECT ord_check.status FROM mod_orders ord_check WHERE ord_check.id = r.order_id) NOT IN(0,1) ) )
        ";

        if ($this->allowed_clinics) {
            $whereArr[] = "r.clinic_id in (" . $this->allowed_clinics . ")";
        }
        if (! empty($whereArr)) {
            $where = implode(' AND ', $whereArr);
        }

        return $where;
    }

    private function getReservationList($cons = false) {

        $isAjax = getP('ajax') == '1';

        $itemsByPage = $this->cfg->get('reservations_by_page');
        $itemsByPage = $itemsByPage ? $itemsByPage : 10;

        $currentPage = $isAjax ? getP('page') : getG('page');
        $currentPage = $currentPage ? intval($currentPage) : 1;
        $limitStart = $currentPage == 1 ? 0 : (($currentPage * $itemsByPage) - $itemsByPage);
        $showedItems = $currentPage * $itemsByPage;

        $limit = " LIMIT " . $limitStart . ", " . $itemsByPage;

        $personId = $isAjax ? getP('person_id') : getG('person_id');
        $statusSelected = $isAjax ? getP('status') : getG('status');

        $where = $this->getReservationsWhereClause($cons, $personId, $statusSelected);
        $dbQuery = "SELECT COUNT(r.id) AS count FROM mod_reservations r
                    WHERE " . $where;

        $query = new query($this->db, $dbQuery);

        $count = intval($query->getrow()['count']);



        $dbQuery = "SELECT r.id, IF(r.start IS NULL, '9999-12-31 23:59:59', r.start) AS start, r.consultation_vroom, r.consultation_vroom_doctor, r.vchat_room, di.name, di.surname, d.url as doctor_url,
    					c.name as clinic_name, cld.title, c.url as clinic_url,
    					r.status, r.status_before_archive, r.status_reason, r.service_type
							FROM `" . $this->cfg->getDbTable('reservations', 'self')	 . "` r
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self')	 . "` d ON (r.doctor_id = d.id)
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info')	 . "` di ON (d.id = di.doctor_id AND di.lang = '". getDefaultLang() ."')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self')	 . "` c ON (r.clinic_id = c.id)
								INNER JOIN `" . $this->cfg->getDbTable('classificators', 'details')	 . "` cld ON (
								r.service_id = cld.c_id
								AND IF(EXISTS(SELECT id FROM mod_classificators_info ci2 WHERE ci2.c_id = r.service_id AND ci2.lang = '".$this->getLang()."'), cld.lang = '".$this->getLang()."', cld.lang = 'lv')
								)
							WHERE " . $where . "
							ORDER BY start DESC, r.created DESC" . $limit;

        $query = new query($this->db, $dbQuery);

        $reservations = array();

        while ($row = $query->getrow()) {

            if($row['service_type'] == 0 && empty($row['consultation_vroom'])) {
                if (strtotime($row['start']) <= time() && $row['status'] != 4) {
                    $dbQuery = "UPDATE  `" . $this->cfg->getDbTable('reservations', 'self')	 . "`
    							SET 
    								`status` = '4', 
    								`status_before_archive` = '" . mres($row['status']) . "', 
    								`status_changed_at` = '" . time() . "'
    							WHERE 1
    							    AND start IS NOT NULL 
    								AND id = '" . $row['id'] . "'";
                    doQuery($this->db, $dbQuery);

                    $row['status_before_archive'] = mres($row['status']);
                    $row['status'] = 4;
                }
            }

            $reservations[] = $row;
        }

        return array(
            'reservations' => $reservations,
            'resCount' => $count,
            'showMore' => $count > $showedItems,
        );
    }

    private function getMessageList() {

        $isAjax = getP('ajax') == '1';

        $itemsByPage = $this->cfg->get('messages_by_page');
        $itemsByPage = $itemsByPage ? $itemsByPage : 10;

        $currentPage = $isAjax ? getP('page') : getG('page');
        $currentPage = $currentPage ? intval($currentPage) : 1;
        $limitStart = $currentPage == 1 ? 0 : (($currentPage * $itemsByPage) - $itemsByPage);
        $showedItems = $currentPage * $itemsByPage;

        $limit = " LIMIT " . $limitStart . ", " . $itemsByPage;

        $clinicIdFilter = '';
        if ($this->allowed_clinics) {
            $clinicIdFilter = " AND m.clinic_id in (" . $this->allowed_clinics . ")";
        }
        $where = "";

        $dbQuery = "SELECT COUNT(m.id) AS count FROM mod_profiles_messages m
                    WHERE 
                        m.profile_id = '" . mres($this->userId) . "' 
                        " . $where . $clinicIdFilter;
        $query = new query($this->db, $dbQuery);

        $count = intval($query->getrow()['count']);

        $dbQuery = "SELECT * FROM mod_profiles_messages m	
                    WHERE 1
                        AND m.`profile_id` = '" . mres($this->userId) . "' 
                        " . $where . "	
                       " . $clinicIdFilter . "	
                    ORDER BY created DESC" . $limit;
        $query = new query($this->db, $dbQuery);

        $messages = array();

        while ($row = $query->getrow()) {

            $messages[] = $row;
        }

        return array(
            'messages' => $messages,
            'messCount' => $count,
            'showMore' => $count > $showedItems,
        );
    }

    public function enterVroom() {

        $resId = getP('resId');

        if(!$resId) {

            $this->return = array(
                'error' => 'no reservation id',
            );
        }

        //
        if(!$this->reservation) {
            /** @var reservation reservation */
            $this->reservation = loadLibClass('reservation');
        }

        $this->reservation->setReservation($resId);
        $resData = $this->reservation->getReservation();

        // send session to konsultacijas site api

        /** @var Vroom $vroomObj */
        $vroomObj = loadLibClass('vroom');
        $sessSendRes = $vroomObj->sendSession();

        $sessId = session_id();

        // construct url
        /** @var array $vroomCfg */
        $vroomCfg = $this->cfg->get('vroom');
        $env = $this->cfg->get('env');
        $lang = !empty($this->getUserData('lang')) ? $this->getUserData('lang') : getDefaultLang();
        $vroomUrl = $vroomCfg[$env . 'BaseUrl'] . $resData['consultation_vroom'] . '?s=' . $sessId . '&lang=' . $lang;

        $this->return = array(
            'resId' => $resId,
            'resData' => $resData,
            'cons_link' => $resData['consultation_vroom'],
            'sessionSendResult' => $sessSendRes,
            'location' => $vroomUrl,
        );
    }

    public function  showSubscriptionPage()
    {

        $start = date('d.m.Y', strtotime($this->userData['dcSubscription']['start_datetime']));
        $end = date('d.m.Y', strtotime($this->userData['dcSubscription']['end_datetime']));
        $subscriptionData = array(
            'startDate' => $this->userData['dcSubscription']['start_datetime'],
            'endDate' => $this->userData['dcSubscription']['end_datetime'],
            'payThruDate' => $this->userData['dcSubscription']['pay_thru_date'],
            'productClinic' => $this->userData['dcSubscription']['product_clinic'],
            'productNetwork' => $this->userData['dcSubscription']['product_network'],
            'dateRange' => $start . '-' . $end,
            'productTitle' => $this->userData['dcSubscription']['productTitle'],
        );
        $this->setPData($subscriptionData, "subscription");

        $this->setPData('subscription.html', 'template');

        $this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));
    }

    /*
     *  DMSS Auth
     *
     * */

    //

    public function dmssAuth()
    {

        $localDebug = false;

        $exception = false;

        $state = getG('state');
        $sessionState = getG('session_state');
        $code = getG('code');

        if(
            (empty($state) && empty($sessionState) && empty($code)) &&
            empty($_SESSION['allowDmssAuthRoute']) &&
            empty($_SESSION['afterLvrtc'])
        ) {
            redirect('/');
        }

        if(!empty($_SESSION['allowDmssAuthRoute'])) {
            unset($_SESSION['allowDmssAuthRoute']);
        }

        $justVerify = !empty($_GET['verif']) && $_GET['verif'];
        $registration = !empty($_GET['dmss_registration']) && $_GET['dmss_registration'];

        if($justVerify || !empty($_SESSION['dmss_verification'])) {
            $justVerify = true;
            $_SESSION['dmss_verification'] = true;
        }

        if($registration || !empty($_SESSION['dmss_registration'])) {
            $registration = true;
            $_SESSION['dmss_registration'] = true;
        }

        // for DEBUG
        if($localDebug) {
            if(getG('reset') && getG('reset') == true) {
                $_SESSION['resetDMSS'] = true;
            }
        }

        // JUST FOR INFO
        $aaa = '

          "issuer": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm",
          "authorization_endpoint": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/auth",
          "token_endpoint": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/token",
          "introspection_endpoint": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/token/introspect",
          "userinfo_endpoint": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/userinfo",
          "end_session_endpoint": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/logout",
          "frontchannel_logout_session_supported": true,
          "frontchannel_logout_supported": true,
          "jwks_uri": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/certs",
          "check_session_iframe": "https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak/auth/realms/dm-realm/protocol/openid-connect/login-status-iframe.html",
        
        ';

        /** @var array $dmssCfg */
        $dmssCfg = $this->cfg->get('dmss');

        $oidc = new OpenIDConnectClient($dmssCfg['dm_provider'], $dmssCfg['dm_client_id']);

        //settings for DEV environment
        $oidc->setVerifyHost(false);
        $oidc->setVerifyPeer(false);
        $oidc->setHttpUpgradeInsecureRequests(false);
        $oidc->providerConfigParam(array('token_endpoint' => $dmssCfg['token_endpoint']));
        $oidc->providerConfigParam(array('authorization_endpoint' => $dmssCfg['authorization_endpoint']));
        $oidc->providerConfigParam(array('userinfo_endpoint' => $dmssCfg['userinfo_endpoint']));
        $oidc->providerConfigParam(array('end_session_endpoint' => $dmssCfg['end_session_endpoint']));

        // change to current user locale!
        $oidc->addAuthParam(array('ui_locales' => 'lv'));

        try {

            $oidc->authenticate();

        } catch(Exception $e) {

            $exception = true;
        }

        if($exception) {

            $redirectUrl = $this->cfg->get('piearstaUrl') . 'profils/mani-dati/';
            $oidc->signOut($idToken, $redirectUrl);
        }

        $token = $oidc->getAccessToken();
        $idToken = $oidc->getIdToken();

        $decoded = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $token)[1]))), true);

        // error in dmss
        if(empty($decoded)) {
            pre('ERROR: decoded data is empty! No token or wrong token received.');
            exit;
        }

        // if chosen method is LVRTC we need to logout LVRTC to reset person code caching

        if($decoded['authProvider'] == 'LVRTC_MOBILE_ID') {

            if(empty($_SESSION['afterLvrtc'])) {

                $_SESSION['afterLvrtc'] = true;

                $serviceBaseUrl = $this->cfg->get('dmss_service_base_url');
                $lvrtcResetLoginPath = $this->cfg->get('dmss_service_reset_lvrtc_login');

                $returnUrl = $this->cfg->get('piearstaUrl') . $this->getLang() . '/dmss-auth/';

                $lvrtcResetUrl = $serviceBaseUrl . $lvrtcResetLoginPath . '?return_url=' . $returnUrl;

                $cURLConnection = curl_init();

                curl_setopt($cURLConnection, CURLOPT_URL, $lvrtcResetUrl);
                curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($cURLConnection, CURLOPT_VERBOSE, 0);
                curl_setopt($cURLConnection, CURLOPT_HEADER, 1);
                curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($cURLConnection, CURLOPT_SSL_VERIFYPEER, 0);
//                curl_setopt($cURLConnection, CURLOPT_FOLLOWLOCATION, true);

                $result = curl_exec($cURLConnection);


                $headerSize = curl_getinfo($cURLConnection, CURLINFO_HEADER_SIZE);
                $header = substr($result, 0, $headerSize);
                $info = curl_getinfo($cURLConnection);
                $error = curl_error($cURLConnection);

                curl_close($cURLConnection);

                if($info['redirect_url']) {
                    redirect($info['redirect_url']);
                }

            } else {

                unset($_SESSION['afterLvrtc']);
            }
        }



        // !!!! TODO: remove after
        // DEBUG

        if($localDebug) {
            if(!empty($_SESSION['resetDMSS']) && $_SESSION['resetDMSS'] == true) {
                unset($_SESSION['resetDMSS']);
                unset($_SESSION['dmss_verification']);
                unset($_SESSION['dmss_auth']);

                $redirectUrl = $this->cfg->get('piearstaUrl') . 'profils/mani-dati/';

                $oidc->signOut($idToken, $redirectUrl);
                exit;
            }

            if($decoded['authProvider'] == 'LVRTC_MOBILE_ID') {
                pre($header);
                pre($result);
                pre($error);
                pre($info);
            }

            // !!!! TODO: remove after
            // DEBUG
            if(!empty($decoded)) {
                pre($decoded);
                echo '<div><a href="/dmss-auth?reset=true">SignOut</a></div>';
                exit;
            }
        }

        // // // // //

        // method

        $method = null;

        switch ($decoded['authProvider']) {
            case 'DM_SMART_ID' :
                $method = 1;
                break;
            case 'WEB_EID' :
                $method = 2;
                break;
            case 'LVRTC_MOBILE_ID' :
                $method = 3;
                break;
        }

        // further result processing

        if($justVerify || !empty($_SESSION['dmss_verification'])) {

            // verification

            unset($_SESSION['dmss_verification']);

            if ($this->userData['person_id'] != $decoded['personCode']) {

                // person codes not equal -- verification failed!
                // logout user from dmss

                $_SESSION['verification_failed_message'] = true;
                $redirectUrl = $this->cfg->get('piearstaUrl') . getLM($this->cfg->getData('mirros_profile_edit_page'));
                $oidc->signOut($idToken, $redirectUrl);
                exit;
            }

            if (
                mb_strtolower(trim($this->userData['name'])) != mb_strtolower(trim($decoded['firstName'])) ||
                mb_strtolower(trim($this->userData['surname'])) != mb_strtolower(trim($decoded['lastName']))
            ) {

                // name or surname mismatch -- verification failed!

                $_SESSION['verification_mismatch_message'] = true;
                $redirectUrl = $this->cfg->get('piearstaUrl') . getLM($this->cfg->getData('mirros_profile_edit_page'));
                $oidc->signOut($idToken, $redirectUrl);
                exit;
            }

            // verification success
            // set dmss_auth param to session like in user login

            $_SESSION['verification_success_message'] = true;
            $redirectUrl = $this->cfg->get('piearstaUrl') . getLM($this->cfg->getData('mirros_profile_edit_page'));

            $_SESSION['dmss_auth'] = $decoded;

            // update user profile

            $vd = array(
                'verified_at' => time(),
                'verification_method' => $method,
            );

            saveValuesInDb('mod_profiles', $vd, $this->userId);

            // recollect user data
            $this->collectUserData($this->userId);


            // logout anyway except if user was logged in via dmss

            $oidc->signOut($idToken, $redirectUrl);
            exit;

        } elseif ($registration || !empty($_SESSION['dmss_registration'])) {

            // registration

            unset($_SESSION['dmss_registration']);

            // get new user data from decoded openId token
            // only 4 fields always available

            $pk = $decoded['personCode'];
            $name = $decoded['firstName'];
            $surname = $decoded['lastName'];
            $country = $decoded['country'];

            // check if user with this pk already exists
            // if so make sign out of openId and redirect to login page

            $userDbQuery = "SELECT * FROM mod_profiles 
                            WHERE 
                                  (person_id = '$pk' OR person_number = '$pk') AND
                                  enable > 0 AND
                                  deleted < 1 AND 
                                  deleted_at < 1
                          ";

            $userQuery = new query($this->db, $userDbQuery);

            if($userQuery->num_rows() > 0) {
                $_SESSION['user_exists_message'] = true;
                $redirectUrl = $this->cfg->get('piearstaUrl') . getLM($this->cfg->getData('mirros_signin_page'));
                $oidc->signOut($idToken, $redirectUrl);
                exit;
            }

            // registration available
            // set dmss_auth param to session like in user login

            $redirectUrl = $this->cfg->get('piearstaUrl') . getLM($this->cfg->getData('mirros_signup_page')) . '?dmss_reg=true';

            $_SESSION['dmss_reg'] = array(
                'pk' => $pk,
                'name' => $name,
                'surname' => $surname,
                'country' => $country,
                'method' => $method,
            );

            // sign out openId and redirect to Registration page (with param)

            $oidc->signOut($idToken, $redirectUrl);
            exit;


        } else {

            // login user

            $_SESSION['dmss_auth'] = $decoded;

            try {

                $res = $this->loginUser(null, null, false, false, true);

            } catch (Exception $e) {

                unset($_SESSION['dmss_auth']);

                pre(array(
                    'LoginException' => true,
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ));

                exit;
            }

            if (!empty($res) && $res['success']) {

                $this->return['agree_terms'] = $this->userData['agree_terms'];
                $this->return['confirm_personal_data'] = $this->userData['confirm_personal_data'];

                $vroomId = $_SESSION['vroomId'];

                if (!$vroomId && !empty($_SESSION['loginFieldVroomId'])) {
                    $vroomId = $_SESSION['loginFieldVroomId'];
                    unset($_SESSION['loginFieldVroomId']);
                    unset($_SESSION['vroomId']);
                }

                if (!empty($vroomId)) {
                    /** @var consultation $consLib */
                    $consLib = loadLibClass('consultation');
                    $redirectUrlResult = $consLib->checkVroomIdAndGetRedirectUrl($vroomId, $this->getUserId());
                }

                if (!empty($redirectUrlResult['redirectUrl'])) {

                    $redirectUrl = $redirectUrlResult['redirectUrl'];

                    if (
                        isset($redirectUrlResult['systemData']) &&
                        isset($redirectUrlResult['systemData']['vroomId']) &&
                        !empty($redirectUrlResult['systemData']['vroomId'])
                    ) {
                        /** @var Vroom $vroomObj */
                        $vroomObj = loadLibClass('vroom');
                        $vroomObj->sendSession();

                        $sessId = session_id();

                        // we add sessId to url
                        $redirectUrl .= '?s=' . $sessId;
                    }

                } else if (!empty($_SESSION['url']) || !empty($_SESSION['loginFieldUrl'])) {

                    $url = $_SESSION['url'];
                    unset($_SESSION['url']);

                    if (!$url && !empty($_SESSION['loginFieldUrl'])) {
                        $url = $_SESSION['loginFieldUrl'];
                        unset($_SESSION['loginFieldUrl']);
                    }

                    $redirectUrl = $url;
                    $_SESSION['redirectTo'] = $this->return['location'];

                } elseif (!empty($_SESSION['dcReturn'])) {

                    $redirectUrl = $_SESSION['dcReturn'];
                    unset($_SESSION['dcReturn']);

                } else {

                    $redirectUrl = $this->cfg->get('piearstaUrl') . getLM($this->cfg->getData('mirros_default_profile_page'));
                }

                $oidc->signOut($idToken, $redirectUrl);
                exit;

            } else {

                unset($_SESSION['dmss_auth']);
                $redirectUrl = $this->cfg->get('piearstaUrl') . 'autorizacija/';
                $oidc->signOut($idToken, $redirectUrl);
                exit;
            }
        }
    }

    // Show registration start page
    public function registrationStart()
    {
        $this->tpl->assign("MODULE_CONTENT", $this->tpl->output('registration_start', $this->getPData()));
    }

    private function getCancellationReason($cancellationReason)
    {
        $cancellationReasonLv = $cancellationReason;
        $dbQuery = " SELECT * FROM ad_sitedata_values WHERE `value` = '" . $cancellationReason . "'";

        $query = new query($this->db, $dbQuery);

        if ($query->num_rows() > 0) {
            $query->getrow();
            if ($query->field('lang') != getDefaultLang()) {
                $dbQuery = " SELECT `value` FROM ad_sitedata_values WHERE `fid` = " . $query->field('fid') . " AND `lang` = '" . getDefaultLang() . "'";
                $query = new query($this->db, $dbQuery);
                if ($query->num_rows() > 0) {
                    $query->getrow();
                    $cancellationReasonLv = $query->field('value');
                }
            }
        }
        return $cancellationReasonLv;
    }

    public function getDashboardPage()
    {
        $page = null;

        $dbQuery = "SELECT * FROM ad_content WHERE url LIKE '%profils/mans-profils/%'";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $page = $query->getrow();
        }

        return $page;

    }

    private function setAllowedLanguages()
    {
        $dbQuery = "SELECT * FROM ad_languages WHERE enable=1";
        $query = new query($this->db, $dbQuery);
        if ($query->num_rows()) {
            $allowedLanguages = $query->getArray();
            $this->setPData($allowedLanguages, "allowedLanguages");
        }
    }

    private function setSwitcherLanguage()
    {
        $switcherLanguage = $this->getLang();
        $this->setPData($switcherLanguage, "switcherLanguage");
    }

    private function setAllowedClinics(){
        $result = false;
        if (defined('ALLOWED_CLINICS')){
            if (ALLOWED_CLINICS === '/'){
                $result = true;
            } else {
                $result = ALLOWED_CLINICS;
            }
        }
        return $result;
    }
}

?>