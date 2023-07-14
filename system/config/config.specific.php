<?php

global $config;

/*
 *
 *  SET APP ENVIRONMENT
 *   dev | prod | stage
 *
 * */
$config['env'] = 'dev';

if($config['env'] == 'prod') {
    $config['supportEmail'] = 'palidziba@piearsta.lv';
} else {
    $config['supportEmail'] = 'aleksejs.urbanovics@bb-tech.eu';
    ini_set('opcache.enable',0);
}

// set debug mode
$config['debug'] = true;

define("DEBUG", $config['debug']); 

// Consultation statuses
//define("CONSULTATION_WAITS_CONFIRMATION", "0"); // Gaida apstiprinājumu
//define("CONSULTATION_ABORTED_BY_SM", "1"); // Noraidīts
//define("CONSULTATION_ACTIVE", "2"); // Aktīvs
//define("CONSULTATION_ABORTED_BY_USER", "3"); // Atcelts
//define("CONSULTATION_IN_ARCHIVE", "4"); // Arhīvā
//define("CONSULTATION_WAITS_PAYMENT", "5"); // Gaida apmaksu

// Consultation statuses
define("CONSULTATION_NEW", "0"); // New
define("CONSULTATION_ABORTED_BY_SM", "1"); // Declined
define("CONSULTATION_FEEDBACK", "2"); // Feedback
define("CONSULTATION_IN_ARCHIVE", "4"); // Arhīvā
define("CONSULTATION_WAITS_PAYMENT", "3"); // Waits payment

// Session cancelation reasons
define("SESSION_CANCEL_LOGOUT", "1");
define("SESSION_CANCEL_EXPIRED", "2");
define("SESSION_CANCEL_DISABLED_BY_USER", "3");
define("SESSION_CANCEL_DISABLED_BY_SYSTEM", "4");

// Profile verification methods
define("SMART_ID", "1");
define("E_ID", "2");

// Classificator types
define("CLASSIF_CITY", "1");
define("CLASSIF_DISTRICT", "2");
define("CLASSIF_SPECIALTY", "3");
define("CLASSIF_SERVICE", "4");
define("CLASSIF_IC", "5");

// Lock statuses
define("LOCK_STATUS_LOCALLY", "1");
define("LOCK_STATUS_PENDING", "2");
define("LOCK_STATUS_AUTOCONFIRMED", "3");
define("LOCK_STATUS_CONFIRMED", "4");
define("LOCK_STATUS_NON_CONFIRMED", "5");

// Reservation statuses
define("RESERVATION_WAITS_CONFIRMATION", "0"); // Gaida apstiprinājumu
define("RESERVATION_ABORTED_BY_SM", "1"); // Noraidīts
define("RESERVATION_ACTIVE", "2"); // Aktīvs
define("RESERVATION_ABORTED_BY_USER", "3"); // Atcelts
define("RESERVATION_IN_ARCHIVE", "4"); // Arhīvā
define("RESERVATION_WAITS_PAYMENT", "5"); // Gaida apmaksu
define("RESERVATION_WAITS_PATIENT_CONFIRMATION", "6"); // Waits for patient's confirmation

// Order statuses
define("ORDER_STATUS_NEW", "0");
define("ORDER_STATUS_PENDING", "1");
define("ORDER_STATUS_CANCELED", "2");
define("ORDER_STATUS_PRELIMINARY_PAID", "3");
define("ORDER_STATUS_PAID", "4");
define("ORDER_STATUS_NON_PAID", "5");
define("ORDER_STATUS_REFUNDED", "6");

// Transaction statuses
define("TRANSACTION_STATUS_NEW", "0");
define("TRANSACTION_STATUS_PENDING", "1");
define("TRANSACTION_STATUS_CANCELED", "2");
define("TRANSACTION_STATUS_PRELIMINARY_PAID", "3");
define("TRANSACTION_STATUS_PAID", "4");
define("TRANSACTION_STATUS_NON_PAID", "5");
define("TRANSACTION_STATUS_REFUNDED", "6");


$config['classificators_types'] = array(
    CLASSIF_CITY => 'City',
    CLASSIF_DISTRICT => 'District',
    CLASSIF_SPECIALTY => 'Specialty',
    CLASSIF_SERVICE => 'Service',
    CLASSIF_IC => 'Insurance company',
);

$config['classificators_filter_keys'] = array(
    CLASSIF_CITY => 'doctors_filter_city',
    CLASSIF_DISTRICT => 'doctors_filter_district',
    CLASSIF_SPECIALTY => 'doctors_filter_specialty',
    CLASSIF_SERVICE => 'doctors_filter_services',
    CLASSIF_IC => 'doctors_filter_ic',
);

$config['classificators_startpage'] = array(CLASSIF_CITY, CLASSIF_SPECIALTY, CLASSIF_IC);

if ( (! empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
    (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
    (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ) {
    $server_request_scheme = 'https';
} else {
    $server_request_scheme = 'http';
}

// construct url dynamically
$shopUrl = '';
if(isset($_SERVER['HTTP_HOST'])) {
    $shopUrl = $server_request_scheme . '://' . $_SERVER['HTTP_HOST'] . '/';
}

$config['piearstaUrl'] = $shopUrl;

$config['cron_piearstaUrl'] = 'https://piearsta2015.smartmedical.eu/';

//URL for create patient vroom
$config['vr_cron_piearstaUrl'] = 'https://piearsta2015.smartmedical.eu/';

// USER SESSIONS //
// max simultaneously active sessions per user allowed
$config['maxSessions'] = 10000;

// Piearsta urls for payments
$config['payment_urls'] = array(
    'shopUrl' => $shopUrl,
    'backURL' => $shopUrl . 'p-cancel/',
    'successURL' => $shopUrl . 'p-success/',
    'failURL' => $shopUrl . 'p-fail/',
    'deliveryURL' => $shopUrl . 'api/sDelivery/',
);

$config['payment_request_uri'] = array(
    'backUri' => '/p-cancel/',
    'successUri' => '/p-success/',
    'failUri' => '/p-fail/',
    'paymentInProcessUri' => '/p-in-process/',
);

// Payment receiver
$config['paymentReceiver'] = array(
    'title' => 'piearsta.lv',
    'logo' => 'piearsta-logo.png',
    'showTitle' => false,
);

// API
$config['piearsta_api'] = array(
    'billingSystem' => array(
        'devToken' => 'a243137c94139db873630442ac73e2a16b2c1b9525bb0558e8b053999fbccd86',
//        'devToken' => '881394b9c4bae539051551e0315b3a74d2a7cc0e7b3aa1653c9b97f3f3e0c2da',
        'stageToken' => '8bf95ef06847dca8aa093fab8956fdeca928cabf038b7b9af000e4a00f4458e4',
        'prodToken' => '7a6a70d047ea91c44c4cd7d85867afbe2fe281c7677e3ea23aeff14c136c5963',
    ),
    'log' => true,
);

// Billing system config
$config['billing_system'] = array(

    // Banklinks
    // only banks with active = 1 will be shown and available for payment requests
    'banklinks' => array(
        'swedbank' => array(
            'title' => 'swedbank',
            'image' => '/img/payments/banks/swed_res.png',
            'image_alt' => 'Swedbank logo',
            'active' => 1
        ),
        'seb' => array(
            'title' => 'seb',
            'image' => '/img/payments/banks/seb_res.png',
            'image_alt' => 'SEB logo',
            'active' => 0
        ),
        'citadele' => array(
            'title' => 'citadele',
            'image' => '/img/payments/banks/citadele_res.png',
            'image_alt' => 'Citadele logo',
            'active' => 0
        ),
    ),

    'devApiUrl' => 'https://web-test.piearsta.lv:4483/api',
//    'devApiUrl' => 'https://andrejs-piearsta.smartmedical.eu:4483/api',
//    'stageApiUrl' => 'https://83.99.201.47:4483/api',
    'stageApiUrl' => 'https://web-test.piearsta.lv:4483/api',
    'prodApiUrl' => 'https://billing.piearsta.lv/api',

    'devToken' => '6a12c64d-7f2a-4d5f-b590-23365fe35de7', // for piearsta2015
//    'devToken' => '6a12c64d-7f2a-4d5f-b590-23365fe35de8',
    'stageToken' => 'f6171884-56dd-4148-a0c5-0964eda74dfc',
    'prodToken' => 'c21f487d-84d8-4f85-a469-401a6a37b570',

    'endpoints' => array(
        'payments_create' => '/payments/create',
        'mark_service_as_delivered' => '/payments/mark-service-as-delivered',
        'refund' => '/payments/refund',
    ),

    'timeout' => 30,

    'ipsAllowed' => array(

        // these ips added for development/testing only
        '83.99.201.47',
        '192.168.1.14',
        '192.168.1.114',
        '10.0.2.114',
        '192.168.1.32',
        '10.0.1.14',
        '192.168.1.20',
        '10.0.1.20',
        '127.0.0.1',
        '192.168.200.95',

        // only this one should left in prod!
        '178.16.24.245',
    ),
    'errors' => array(
        '822' => 'Bank failure?',
        '823' => 'HPS: The merchants customer did not complete within the session timeout. (The flow for the generation of the payment was not completed within the expiration period allowed for the session generated.)',
        '1104' => 'Transactions cannot be authorized after time limit expired (The default timeout value is set to 6hours but can be amended per Vtid by contacting SPP Support.)',
    ),

    'everyPay' => array(
        'title' => 'everyPay',
        'image' => '',
        'image_alt' => 'EveryPay logo',
        'active' => 1,
        'api_username' => 'fe277143cb69492d',
        'account_name' => 'EUR3D1',
        'api_password' => '386c8398fdfe5194a949386f1b9b8f32',
        'devApiUrl' => 'https://igw-demo.every-pay.com/api/v4',
        'prodApiUrl' => 'https://pay.every-pay.eu/api/v4',
        'nonce_length' => 50,
        'endpoints' => array(
            'payments_create' => '/payments/oneoff',
            'payments_check' => '/payments/reference?api_username=key',
            'get_payment_methods' => '/processing_accounts/account_name?api_username=key',
            'payments_refund' => '/payments/refund',
        ),
        'error_messages' => array(
            'cancelled_by_user' => array(
                'Payment cancelled by user',
            )
        ),
        'payment_unfinished_statuses' => array(
            'initial',
            'authorised',
            'waiting_for_sca',
            //'sent_for_processing', -- this is special status that means 'success' but _PRELIMINARY_PAID statuses used for order and transaction
            'waiting_for_3ds_response',
        ),

        // in minutes
        'payment_unfinished_statuses_lock_time' => array(
            'initial' => 60,
            'authorised' => 120,
            'waiting_for_sca' => 30,
            'sent_for_processing' => 10,
            'waiting_for_3ds_response' => 5,
            'default_lock_time' => 35,
        ),

        // if set '*' => allowed all

        'ip_whitelist' => array(
            '*',
        ),
    ),
);
$config['everyPay_request'] = 'https://bbt.smartmedical.eu:8775/api';

// Before user could choose payment type in PA and than happened redirect to Swedbank or VISA,
// now happens redirect, after click to pay button, to everyPay platform where user choose payment type and pay,
// if oldPaymentType is set to false
$config['oldPaymentType'] = false;
$config['everyPayRefundEnabled'] = true;
$config['everyPayRefundInformationToSupport'] = true;

// This parameter rewrites payment_state to sent_for_processing in response from EveryPay
$config['test_payment_state'] = false;

// These two params help to debug payment check cronjob
$config['emulate_payment_state'] = 'sent_for_processing';
$config['cron_payment_debug'] = false;
//

$config['service_types'] = array(
    0 => 'E-PIERAKSTS',
    1 => 'E-KONSULTACIJA',
);

define('SERVICE_RESERVATION', '0');
define('SERVICE_CONSULTATION', '1');

// Limit of reservations for reservation_get (_get_last) api methods
$config['reservationGetLimit'] = 50;

// Non-confirmed reservations reminder
$config['reminder_confirmation_time'] = 24 * 60 * 60;
$config['reminder_warn_before'] = 72 * 60 * 60;

// SmartMedical API
$config['smartMedicalApi'] = array(
    'methods' => array(
        'deleteReservation' => 'delete_booking',
        'saveReservation' => 'save_booking',
        'vaccination_booking' => 'vaccination_booking',
        'insurancePayment' => 'process_insurance_check',
        'insuranceCancelPayment' => 'cancel_insurance_claim',
    ),
);

// Log requests from SM, method reservation_patient_showup?
$config['log_show_up'] = false;

// PA log cleaner config (mod_api_log table)

// period to store logs for ( days ), default -- 1000
$config['storeLogsFor'] = 2;
// limit of records cleaner will delete at a time, default -- no limit (null)
$config['logsCleanerLimit'] = 5;


// // //
//
//   Vaccination tables cleaner
//
// // //

// period to store vivat_cache_log for (days), default -- 30
$config['storeVivatCacheLogFor'] = 30;
// limit of records cleaner will delete at a time, default -- 10000
$config['vivatCacheLogCleanerLimit'] = 10000;

// period to store vivat_cache_upload_log for (days), default -- 30
$config['storeVivatCacheUploadLogFor'] = 30;
// limit of records cleaner will delete at a time, default -- 10000
$config['vivatCacheLogUploadCleanerLimit'] = 10000;

// period to store vivat_booking requests for (days), default -- 30
$config['storeVivatBookingRequestsFor'] = 30;
// limit of records cleaner will delete at a time, default -- 10000
$config['vivatBookingRequestsCleanerLimit'] = 10000;

// period to store vivat_auth_tokens for (days), default -- 30
$config['storeVivatAuthTokensFor'] = 30;
// limit of records cleaner will delete at a time, default -- 10000
$config['vivatAuthTokensCleanerLimit'] = 10000;

// period to store vaccination_cron_log for (days), default -- 30
$config['storeVaccinationCronLogFor'] = 30;
// limit of records cleaner will delete at a time, default -- 10000
$config['vaccinationCronLogCleanerLimit'] = 10000;

// period to store sm_booking_batches for (days), default -- 30
$config['storeSmBookingBatchesFor'] = 30;
// limit of records cleaner will delete at a time, default -- 10000
$config['smBookingBatchesCleanerLimit'] = 10000;

// // //


define("GENDER_MALE", "male");
define("GENDER_FEMALE", "female");

$config['genders'] = array(
    GENDER_MALE => 'Male',
    GENDER_FEMALE => 'Female',
);

$config['image_config'] = array(
    'news' => array(
        'big' => array(
            'width' => '700',
            'height' => '296',
            'upload_path' => 'news/700x296/'
        ),
        'small' => array(
            'width' => '220',
            'height' => '110',
            'upload_path' => 'news/220x110/'
        ),
        'original' => array(
            'upload_path' => 'news/original/'
        )
    ),
    'banners' => array(
        'original' => array(
            'upload_path' => 'banners/'
        )
    ),
);

// Parameter defines how long user agreement accept remains valid
// The string in relative format, for example:
// '1 year', '1 year + 6 month', '1 month + 6 day' etc.
$config['agreement_term'] = '1 year';

// Parameter defines how long user confirm personal data remains valid
// The string in relative format, for example:
// '1 year', '1 year + 6 month', '1 month + 6 day' etc.
$config['confirm_personal_data'] = '1 year';


// shedule_lock_time in seconds
// this time set on lock record create as lockRecord expiration time,
//the record will be deleted by cron script when expired if no transaction
//started during this time
//
$config['shedule_lock_time'] = 30 * 60; // 30 min

// LockRecord expiration time will be extended on success of transaction start
//
$config['extend_lock_time_by'] = 1 * 60 * 60; // 1 hour

// Lock time for reservations in the name of other patient
$config['shedule_lock_time_in_the_name_of'] = 2 * 60 * 60;												  
// Size of record batch to insert to mod_shedules_temp table (to minimize ineffective db queries in loop)
$config['schedulesInsertBatchSize'] = 10;

// Blue Bridge info
$config['bb'] = array(
    'title' => 'SIA "Blue Bridge Technologies"',
    'address' => 'Āraišu iela 37, 2. stāvs, Rīga, LV-1039, Latvija',
    'reg_number' => '40003932716',
    'phone' => '+371 67 615 159',
    'email' => 'info@bb-tech.eu',
    'pvn_number' => 'LVHABA0551017128657',
    'bank_account' => 'LV40003932716',
    'bank_info' => 'Banka Swedbank AS, HABALV22',
);

// key defines Latvias bounds in google coords
$config['latvia_bounds'] = array(
    'n' => 58.08,
    's' => 55.68,
    'w' => 20.90,
    'e' => 28.30,
);

// Do we need to check the ability to book selected time slots on SM?
define('CHECK_SM', 1);

// Time to wait for SM confirmation result (hsp_reservation_id)
define('SM_CONFIRMATION_TIMEOUT', 10 * 1000);

// SM curl connection timeout
define('SM_CURL_CONNECTION_TIMEOUT', 5);

// SM curl connection timeout
define('SM_CURL_TIMEOUT', 45);

// Watchdog limit defines quantity of lock records, watchdog cron handles at a time
$config['watchdog_limit'] = 50;

// limit of session records cron will process at a time
$config['cron_sessions_limit'] = 50;

// limit of reservations records cron will process at a time
$config['reservations_notifications_limit'] = 50;

// reservations by page
$config['reservations_by_page'] = 10;

// consultations by page
$config['consultations_by_page'] = 10;

// messages by page
$config['messages_by_page'] = 10;

$config['check_sm'] = CHECK_SM;
$config['sm_confirmation_timeout'] = SM_CONFIRMATION_TIMEOUT;

// How many payments we will check (requests to EveryPay)
$config['payment_check_limit'] = 3;

// How many hours before reservation start we should send info to support that payment status is still processing
$config['reservation_checking_time'] = 1;

// Is Piesaki arstu menu item and page available
// 'class' allows add classes to menu item
$config['pieasakiArstu'] = array(
    'active' => false,
    'class' => 'green-item',
    'right' => true,
    'email' => 'sales@smartmedical.lv',

);

// Is Arstiem menu item and page available
// 'class' allows add classes to menu item
$config['arstiem'] = array(
    'active' => false,
    'class' => '',
    'right' => true,
    'email' => 'sales@smartmedical.lv',

);

// E-Consultations
//
$config['showConsultationsDaysBefore'] = 2;

$config['vroom'] = array(

    'prodBaseUrl' => 'https://kons-urban-new.piearsta.lv/',

    'devBaseUrl' => 'https://kons-urban-new.piearsta.lv/',

    //
    'prodApiUrl' => 'https://kons-urban-new.piearsta.lv/api/',

    // 192.168.1.114
    'devApiUrl' => 'https://kons-urban-new.piearsta.lv/api/',

    'methods' => array(
        'createVroom' => 'createVroom',
        'confirmVroom' => 'confirmVroom',
        'cancelVroom' => 'cancelVroom',
        'updateVroom' => 'updateVroom',
        'sendSession' => 'sendSession',
        'saveDoctorImage' => 'saveDoctorImage',
    ),

    'prodToken' => 'e2b30ce5-9cf7-45b8-88e9-a6c8fc3594e4',
    'devToken' => 'e2b30ce5-9cf7-45b8-88e9-a6c8fc3594e4',

    'curlConnectionTimeout' => 20,
    'curlTimeout' => 30,

    'db' => [
        'db_host' => "127.0.0.1",
        'db_database' => "cons_annija",
        'db_username' => "",
        'db_password' => ""
    ]
);

// This parameter is for cons_status_check_cron, time in seconds, this is the minimum time we consider
// that consultation happened

$config['minConsultationDuration'] = 300;

$config['consultationsApiToken'] = 'ccc444dc12b56f7aeb84913981f8333eb85735f8863a19f89af5383990faa42d';

// sets the limit max reservations, for which there is needed to create vrooms at one cronjob run
$config['vrooms_check_limit'] = 20;


// // // // // // // // // // // //
//
//    Piersta Interator system config
//
// // // // // /// // // // // // //

$config['pai'] = array(

    'prodBaseUrl' => 'https://integrator-andrey.piearsta.lv/',

    'devBaseUrl' => 'https://integrator-andrey.piearsta.lv/',

    //
    'prodApiUrl' => 'https://integrator-andrey.piearsta.lv/api/',

    // 192.168.1.122
    'devApiUrl' => 'https://integrator-andrey.piearsta.lv/api/',

    'methods' => array(
//        'createVroom' => 'createVroom',
    ),

    'prodToken' => 'x8DXuPH5KV0zdfatgFiEsnwUTZ41kLQo',
    'devToken' => 'x8DXuPH5KV0zdfatgFiEsnwUTZ41kLQo',

    'paiApiToken' => 'x8DXuPH5KV0zdfatgFiEsnwUTZ41kLQo',

    'curlConnectionTimeout' => 20,
    'curlTimeout' => 30,
);


// // // //


// Days to store old SM reservations (used by clean_old_sm_reservations.php cron)
$config['store_SM_reservations_days'] = 1;

// This setting defines whether to show only free slots in calendar, or show all, but reserved grey and strikethrough
$config['ShowOnlyFreeSlots'] = true;

// Defines how far look to the future when searching for nearest free time slot
$config['nearestSlotLookForward'] = 90; // value shouldn't be too big, this will seriously slow down filtering performance

$config['google']['api_key'] = 'AIzaSyANH-263sFYkRkif-0KKyoBjrPYHn0h1sc';

if($config['env'] == 'dev') {
    $config['google']['api_key'] = 'AIzaSyAHVXfvciP0YpzcMJ-OU74F62BUFmM-dXs';
}

// We use Cuttly ( https://cutt.ly/ ) api to shorten urls, so we need to provide api access key
$config['url_shortener_api_key'] = '28e6c804cab1b4d38562093170bc4a8dde1b6';

// Google API config
$config['g_api'] = array(
    'secret' => 'bef604ee-e574-4105-944a-0b20503ea4ab', // secret key to protect return url
);


//
// Verification settings
//

$config['profileVerificationEnabled'] = true;

// Profile verification expiration
$config['verification_expires_after'] = '1 year';

// Verification gateway timeout in seconds
$config['verification_gateway_timeout'] = 5;
// smartId timeout // default 90 sec
$config['verification_smartid_timeout'] = 90;

// Verification gateway url
if($config['env'] == 'dev') {

    $config['verification_gateway_url'] = 'https://smartid-test.piearsta.lv';
    $config['verification_gateway_eid_url'] = 'https://eid-test.piearsta.lv/info.php';

} else {

    $config['verification_gateway_url'] = 'https://smartid-test.piearsta.lv';
    $config['verification_gateway_eid_url'] = 'https://eid-test.piearsta.lv/info.php';

}

$config['verification_gateway_token'] = 'b4f361ca24b3b04aea84912981f8333eb91735f8863a19f89af5383990fbb74c';

$config['verification_success_url'] = $shopUrl . 'api/user_valid/';
$config['verification_fail_url'] = $shopUrl . 'api/user_check_failed/';

$config['verification_success_page'] = $shopUrl . 'profils/mani-dati/';
$config['verification_fail_page'] = $shopUrl . 'profils/mani-dati/';

$config['verification_return_page'] = $shopUrl . 'profils/mani-dati/';

// The list of countries where we can verify user profile by any method
$config['verifiableCountries'] = array(
    'LV', // Latvia
    'EE', // Estonia
    'LT', // Lithuania
);

// should be in http header: Authorization: Bearer 3F939FC174D0A3096BDF10C8C8F9002461A1ED5572F5AF646749FE671FE512CB
// in verification gateway request2
$config['verificationApiToken'] = '3F939FC174D0A3096BDF10C8C8F9002461A1ED5572F5AF646749FE671FE512CB';

$config['verification_smartid_error_messages'] = array(
    503 => 'Limit exceeded',
    403 => 'Forbidden!',
    401 => 'Unauthorized',
    404 => 'User account not found for URI',
    580 => 'System is under maintenance, retry later',
    480 => 'The client is old and not supported any more. Relying Party must contact customer support.',
    472 => 'Person should view app or self-service portal now.',
    471 => 'No suitable account of requested type found, but user has some other accounts.',
    4 => 'Name or surname mismatch!',
    '-1' => 'Person code mismatch!',
);

// E-Consultations
$config['showConsultationsDaysBefore'] = 3;

// reservations by page
$config['reservations_by_page'] = 10;

// consultations by page
$config['consultations_by_page'] = 10;

// messages by page
$config['messages_by_page'] = 10;


// // //
if($config['env'] == 'prod') {
    $config['maniDatiUrl'] = 'https://manidati.piearsta.lv';
} else {
    $config['maniDatiUrl'] = 'https://piearsta2015-manidati.smartmedical.eu/';
}

// ANNOUNCEMENTS

$config['announcement'] = array(
    'tmplPath' => 'announcement/',
    'showAnnouncements' => true,
    'items' => array(
        'a1' => array(
            'from' => '2020-10-10 00:00:00',
            'to' => '2023-12-30 00:00:00',
            'template' => 'an1',
            'popup' => 'an1_popup',
            'active' => false,
        ),
        'a2' => array(
            'from' => '2020-10-10 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an2',
            'popup' => 'an2_popup',
            'active' => false,
        ),
        'a3' => array(
            'from' => '2020-10-10 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an3',
            'popup' => 'an3_popup',
            'active' => false,
        ),
        'a4' => array(
            'from' => '2020-10-10 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an4',
            'active' => false,
        ),
        'a5' => array(
            'from' => '2020-10-10 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an5',
            'active' => false,
        ),
        'a6' => array(
            'from' => '2020-10-10 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an6',
            'active' => false,
        ),
        'a7' => array(
            'from' => '2022-04-12 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an7',
            'active' => false,
        ),
        'a8' => array(
            'from' => '2022-04-12 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an8',
            'active' => false,
        ),
        'a9' => array(
            'from' => '2022-04-12 00:00:00',
            'to' => '2022-12-30 00:00:00',
            'template' => 'an-2fa',
            'active' => false,
        ),
        'a10' => array(
            'from' => '2023-01-01 00:00:00',
            'to' => '2023-12-30 00:00:00',
            'template' => 'an-dig-id',
            'active' => false,
        ),
        'a11' => array(
            'from' => '2023-01-01 00:00:00',
            'to' => '2023-12-30 00:00:00',
            'template' => 'an-mental',
            'active' => true,
        ),
    ),
);


// whether to check another reservation existance for profile or profile person
$config['CheckConflictingBookings'] = true;

// TeleMedicine Platform help (How to use) pages (used in api to get How to use page content from TMP app)

$config['TMP_help_pages'] = array(

    'patient' => array(
        'lv' => 'ka-lietot-telemedicinas-platformu-patients-lv',
        'ru' => 'ka-lietot-telemedicinas-platformu-patients-lv',
        'en' => 'ka-lietot-telemedicinas-platformu-patients-lv',
    ),

    'doctor' => array(
        'lv' => 'ka-lietot-telemedicinas-platformu-doctors-lv',
        'ru' => 'ka-lietot-telemedicinas-platformu-doctors-lv',
        'en' => 'ka-lietot-telemedicinas-platformu-doctors-lv',
    )
);

// Cookie policy pages (used in api to get PA Cookie policy content from TMP and DC apps)

$config['Cookie_policy_pages'] = array(
    'lv' => 'sikdatnu-politika',
    'ru' => 'sikdatnu-politika',
    'en' => 'sikdatnu-politika',
);

// Mandatory password change policy
// All existing patients have password expiration date registration date + 1 year or NOW() + 90 days which dates is bigger.
$config['patientChangePassEveryNDays'] = 365;
$config['patientChangePassExistingReg'] = 365; // Used as "registration date + 1 year"
$config['patientChangePassExistingNow'] = 90; // Used as "NOW() + 90 days"
$config['patientChangePassWriteLogs'] = false;



// Vivat api

// Swagger testing links
// https://vivat-api-tv.zzdats.lv/api-auth/swagger
// https://vivat-api-tv.zzdats.lv/api-calendar/swagger
// https://vivat-api-tv.zzdats.lv/api-appointment/swagger/index.html?urls.primaryName=v2.0

$config['vivatApi'] = array(
    'dev' => array(
        'apiBaseUrl' => 'https://vivat-api-tv.zzdats.lv',
        'apiKey' => '802caa7d57934abb9fd2397a6fb47d93',
        'stopOnResponseCodes' => array(
            400, // Bad Request
        ),
        'auth' => array(
            'apiPath' => 'api-auth/api/Auth/token',
            'maxRefreshTokenAttemptsPerHour' => 3,
            'getNewTokenResponseCodes' => array(
                401, // Unauthorized
            ),
            'fakeApiCall' => false,
            'fakeApiCallResponseCode' => 200,
        ),
        'calendarUploadSlots' => array(
            'apiPath' => 'api-calendar/api/Calendar/uploadslots',
            'source' => 'piearsta', // VVP-25 "source" has to be set to either "piearsta" or "smartmedical".
            'vpCountPerBatch' => 2,
            'maxUploadAttempts' => 1,
            'resendOnResponseCodes' => array(
                500, // Internal Server Error
                503, // Service Unavailable
            ),
            'dontResendOnResponseCodes' => array(),
            'resendOnUnknownResponseCode' => false,
            'sslOn' => false,
            'writelogs' => 'always', // always, onFailure
            'fakeApiCall' => false,
            'fakeApiCallResponseCode' => 200,
            'truncateVivatCacheUploadLog' => false,
            'retryCacheIdMaxTime' => 30,
        ),
        'appointmentRequests' => array(
//            'apiPath' => 'api-appointment/api/appointment/getvaccinationapointmentrequestscollection',
            'apiPath' => 'api-appointment/api/v2.0/appointment/getvaccinationapointmentrequestscollection',
            'source' => 'piearsta',
            'pageSize' => 3,
            'sslOn' => false,
            'writelogs' => 'always',
            'fakeApiCall' => false,
            'fakeApiCallResponseCode' => 200,
            'fakeApiCallResultRequestsCount' => 1,
        ),
    ),
    'prod' => array(
        'apiBaseUrl' => 'https://vivat-api-tv.zzdats.lv',
        'apiKey' => '802caa7d57934abb9fd2397a6fb47d93',
        'stopOnResponseCodes' => array(
            400, // Bad Request
        ),
        'auth' => array(
            'apiPath' => 'api-auth/api/Auth/token',
            'maxRefreshTokenAttemptsPerHour' => 3,
            'getNewTokenResponseCodes' => array(
                401, // Unauthorized
            ),
            'fakeApiCall' => false,
            'fakeApiCallResponseCode' => 200,
        ),
        'calendarUploadSlots' => array(
            'apiPath' => 'api-calendar/api/Calendar/uploadslots',
            'source' => 'piearsta', // VVP-25 "source" has to be set to either "piearsta" or "smartmedical".
            'vpCountPerBatch' => 10,
            'maxUploadAttempts' => 3,
            'resendOnResponseCodes' => array(
                500, // Internal Server Error
                503, // Service Unavailable
            ),
            'dontResendOnResponseCodes' => array(),
            'resendOnUnknownResponseCode' => false,
            'sslOn' => true,
            'writelogs' => 'onFailure', // always, onFailure
            'fakeApiCall' => false,
            'fakeApiCallResponseCode' => 200,
            'truncateVivatCacheUploadLog' => false,
            'retryCacheIdMaxTime' => 60,
        ),
        'appointmentRequests' => array(
//            'apiPath' => 'api-appointment/api/appointment/getvaccinationapointmentrequestscollection',
            'apiPath' => 'api-appointment/api/v2.0/appointment/getvaccinationapointmentrequestscollection',
            'source' => 'piearsta',
            'pageSize' => 100,
            'sslOn' => true,
            'writelogs' => 'onFailure',
            'fakeApiCall' => false,
            'fakeApiCallResponseCode' => 200,
            'fakeApiCallResultRequestsCount' => 3,
        ),
    ),
);


// Max number of simultaniously running jobs (background processes)
$config['maxRunningJobs'] = 20;


// Vaccination booking upload to SM
$config['vbSmUploadCronMaxExecutionTime'] = 1 * 30; // in sec

$config['vbSmUploadCronRunInterval'] = $config['vbSmUploadCronMaxExecutionTime'] + 5; // in sec
$config['vbSmUploadMaxRetryCount'] = 2;

$config['vbSmUploadRetryCheckInterval'] = 5 * 60; // in sec

// Limit max sm upload jobs executed simultaneously
$config['vbSmUploadMaxRunningJobs'] = 1;
$config['vbSmUploadRetryMaxRunningJobs'] = 1;

// Limit max download appointments jobs executed simultaneously
$config['vaccinationDownloadAppointmentsMaxRunningJobs'] = 1;

// Other vaccination related crons
$config['vaccinationUploadSlotsCronsMaxExecTime'] = 1 * 30;
$config['vaccinationCronsRunInterval'] = $config['vaccinationUploadSlotsCronsMaxExecTime'] + 5; // in sec

//
$config['vaccinationDownloadAppointmentsCronsMaxExecTime'] = 1 * 30; // in sec

//
$config['vaccinationPrepareSlotsMaxExecTime'] = 1 * 30; // in sec

// vaccinationJobsFileLog
$config['vaccinationJobsFileLog'] = true;

// We should clean cron logs regular
$config['vaccinationCronLogCleanerOlderThan'] = 5 * 60; // in sec
$config['vaccinationCronLogCleanerBatchSize'] = 1000;

// vivat download appointments run interval
$config['vaccinationDownloadAppointmentsRunInterval'] = 1; // in sec

// vivat download appointments run interval
$config['vbSmUploadRunInterval'] = 1; // in sec

// Clean old data
$config['cleanOldData'] = array(
    'dev' => array(
        'schedules' => array(
            'deleteOlderThenNDays' => 7,
            'debugUseTableCopy' => true,
        ),
        'reservations' => array(
            'deleteOlderThenNDays' => 7,
            'debugUseTableCopy' => true,
        ),
    ),
    'prod' => array(
        'schedules' => array(
            'deleteOlderThenNDays' => 7,
            'debugUseTableCopy' => true,
        ),
        'reservations' => array(
            'deleteOlderThenNDays' => 7,
            'debugUseTableCopy' => true,
        ),
    ),
);


/*
 *
 *  DIGITAL CLINIC settings
 *
 * */
$config['dcUrl'] = 'https://digitalClinic-urban.smartmedical.eu';
$config['dcApiToken'] = 'ea56dd7a-a591-47c5-8384-bd109f67ae39';
$config['dcLockSlotsForMinutes'] = 10; // lock slots duration for DC reservation try
$config['paSsoSalt'] = '0_fdOgEtu(uKAt8eqSM/JrJMlO!3JR1p8L4C';

// only hosts -- allowed dc urls
$config['dcAllowedUrls'] = [
    'dc-andrey.piearsta.lv',
    'dc-andrey.smartmedical.eu',
    'digitalClinic-urban.smartmedical.eu',
    'digitalclinic-urban.smartmedical.eu',
];

$config['dcAppointmentSalt'] = "E9171C1EF80BFA956B6585157E91DD9C82DA069B3D2FD2706C5D69EB83939C92";


// dc api

$config['dcApiConfig'] = array(

    //
    'prodApiUrl' => 'https://digitalclinic-urban.smartmedical.eu/api/',

    // 192.168.1.126
    'devApiUrl' => 'https://digitalclinic-urban.smartmedical.eu/api/',

    'methods' => array(
        'unlockCachedSlots' => 'unlockCachedSlots',
    ),

    'prodToken' => 'nDpvrpZHWjNqLSlhC3VGgeq7Hp6GBpmO',
    'devToken' => 'nDpvrpZHWjNqLSlhC3VGgeq7Hp6GBpmO',

    'curlConnectionTimeout' => 20,
    'curlTimeout' => 30,
);


// Does SmartMedical supports multi language? Default is false, and we send to SM only lang=lv names
$config['smMultiLang'] = false;

// 2-factor authentication

$config['tfa'] = array(
    'strictMode' => true,
    'maxAttempts' => 3,
);

/*
 *  DMSS Keycloak Portal settings
 * */

//$config['dmss_base_url'] = 'https://digitalmind.northeurope.cloudapp.azure.com/ext-portal-keycloak';
$config['dmss_service_base_url'] = 'https://auth-id.piearsta.lv:8089';
//$config['dmss_service_base_url'] = 'https://auth-dev.piearsta.lv:8089';
$config['dmss_service_reset_lvrtc_login'] = '/api/authentication/lvrtc/logout';

$config['dmss_base_url'] = 'https://auth-id.piearsta.lv:8444';
//$config['dmss_base_url'] = 'https://auth-dev.piearsta.lv:8444';
$config['dmss_realm'] = 'dm-realm';

$config['dmss_provider'] = $config['dmss_base_url'] . '/auth/realms/' . $config['dmss_realm'];

$config['dmss'] = array(
    'dm_provider' => $config['dmss_provider'],
    'dm_client_id' => 'dmss-signing-portal',
    'token_endpoint' => $config['dmss_provider'] . '/protocol/openid-connect/token',
    'authorization_endpoint' => $config['dmss_provider'] . '/protocol/openid-connect/auth',
    'userinfo_endpoint' => $config['dmss_provider'] . '/protocol/openid-connect/userinfo',
    'end_session_endpoint' => $config['dmss_provider'] . '/protocol/openid-connect/logout',
    'methods' => array(
        '1' => 'SmartID',
        '2' => 'E-ID',
        '3' => 'eParaksts Mobile',
    )
);

// END of dmss


// EGL config
$config['eglConfig'] = array(
    'dev' => array(
        'endpoint' => 'https://servisi.egl.lv:9443/csp/sandbox/app.service.cls',
    ),
    'prod' => array(
        'endpoint' => 'https://servisi.egl.lv:7443/csp/sarmite/app.service.cls',
    ),
);

// 'Hanging' reservations cron settings (reservations without hsp res id)
$config['hours_waiting_for_hsp_reservation_id'] = 24;
$config['hours_max_to_analyze_hanging_reservations'] = 48;

// check ManiDati session on PA logout
$config['checkManiDatiOnLogout'] = true;

// For BeforeRouteEnter class SSO_API logging
define("ENABLE_LOGS_SSO_API", false);

$current_dir = dirname(__FILE__) . '/';
require_once($current_dir . 'config.custom_dbtables.php');

$config['domainsLang'] = [
    'annija-piearsta.smartmedical.eu' => 'lv'
];
