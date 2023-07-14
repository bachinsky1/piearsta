<?php

// This is web application!
if (php_sapi_name() == 'cli') {
    print 'This is web-application and cannot be run from command line.' . PHP_EOL;
    exit;
}

define('APP_ROOT', dirname(__FILE__) . '/..');

// Bootstrap Piearsta.lv framework
require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

/** @var array $gApiCfg */
$gApiCfg = $cfg->get('g_api');
$gApiSecret = $gApiCfg['secret'];

// debug vars
$debug = DEBUG;
$debug = false;

$gapiDebug = false;

// Init vendor libs (google api client, etc ...)
require __DIR__ . '/../vendor/autoload.php';

// // //
// Process received data

// Validate request
$state = json_decode( base64_decode($_GET['state']), true );

// if no data in state or secret key mismatch -- 403 error
if(
    !is_array($state) ||
    !isset($state['apiSecret']) ||
    $state['apiSecret'] != $gApiSecret
) {
    header('HTTP/1.0 403 Forbidden');
    echo '<h2>403   Access denied</h2>';
    exit;
}

// if absent param -- 404 error
if(
    !isset($state['doctorId']) ||
    !isset($state['clinicId']) ||
    !isset($state['doctorData']) ||
    !is_array($state['doctorData'])
) {
    header('HTTP/1.0 404 Not found');
    echo '<h2>404   Not found</h2>';
    exit;
}

// get doctor and validate received data

$dbQuery = "SELECT d.id, d.hsp_resource_id, di.name, di.surname, di.notify_email FROM mod_doctors d
            LEFT JOIN mod_doctors_info di ON (d.id = di.doctor_id) 
            LEFT JOIN mod_doctors_to_clinics d2c ON (d.id = d2c.d_id) 
            WHERE 
                d.id = " . mres($state['doctorId']) . " AND
                d.deleted = 0 AND
                d.enabled = 1 AND
                d2c.c_id = " . mres($state['clinicId']);

$query = new query($mdb, $dbQuery);

// set doctorData if exists or exit
// and validate doctor data
if($query->num_rows()) {
    $doctorData = $query->getrow();

    if($debug) {
        echo '<h3>Doctor found:</h3>';
        pre($doctorData);
        echo '<br><br>';
        echo '<p>Validating doctor ...</p>';
        echo '<hr>';
    }

    if(
        $doctorData['hsp_resource_id'] != $state['doctorData']['hsp_resource_id'] ||
        $doctorData['name'] != $state['doctorData']['name'] ||
        $doctorData['surname'] != $state['doctorData']['surname']
    ) {
        if($debug) {
            print PHP_EOL . 'Invalid doctor data!' . PHP_EOL . PHP_EOL;
            exit;
        } else {
            header("HTTP/1.0 404 Not Found");
            exit;
        }

    }

    if($debug) {

        echo '<br><br>';
        echo '<p>Doctor validated!</p>';
        echo '<hr>';
    }

} else {

    if($debug) {
        print PHP_EOL . 'No such doctor in this clinic!' . PHP_EOL . PHP_EOL;
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

}

// // //
// check code received

//

if($debug) {
    echo '<h1>Google auth test callback</h1>';

    echo '<h4>GET:</h4>';
    echo '<pre>';
    var_dump($_GET);
    echo '</pre>';
    echo '<br>';

    echo '<h4>SESSION:</h4>';
    echo '<pre>';
    var_dump($_SESSION);
    echo '</pre>';
    echo '<br>';
}

if($_GET['code']) {

    if($debug) {
        echo '<h2>Authorization code received</h2>';
        echo '<p>'.$_GET['code'].'</p>';
        echo '<br>';
        echo '<h3>Following scopes available:</h3>';

        $scopes = explode(' ', $_GET['scope']);

        if(count($scopes) > 0) {
            echo '<ul>';
            foreach ($scopes as $scope) {
                echo '<li>' . $scope . '</li>';
            }
            echo '</ul>';
        }
    }

} else {

    echo '<h2>ERROR: Authorization code NOT received</h2>';
    exit;
}

/** @var googleApi $googleApi */
$googleApi = loadLibClass('googleApi');
$accessToken = $googleApi->getClient()->fetchAccessTokenWithAuthCode($_GET['code']);

$error = false;

if(!isset($accessToken['error']) && !empty($accessToken['access_token'])) {

    // write fetched token to db
    $dbQuery = "INSERT INTO mod_google_access_tokens 
            (clinic_id, doctor_id, token, calendar_title) VALUES 
            (" . $state['clinicId'] . ", " . $state['doctorId'] . ", '" . json_encode($accessToken) . "', '" . $state['calendarTitle'] . "')
            ON DUPLICATE KEY UPDATE 
                clinic_id = " . $state['clinicId'] . ", 
                doctor_id = " . $state['doctorId'] . ",
                calendar_title = '" . $state['calendarTitle'] . "',
                token = '" . json_encode($accessToken) . "'";

    doQuery($mdb, $dbQuery);

} else {

    // error fetchong access token

    if($debug) {
        echo '<h2>ERROR: fetching access token error occurred!</h2>';
    }

    $error = true;

}

if($gapiDebug) {

    // debug
    //$client->setAccessToken($accessToken);

    $googleApi->setToken($accessToken);
    $service = new Google_Service_Calendar($googleApi->getClient());

    $newEvent = new Google_Service_Calendar_Event(array(
        'summary' => 'AAA-BBB-ddd',
        'location' => 'Āraišu iela 37, Vidzemes priekšpilsēta, Rīga, LV-1039',
        'description' => 'Description of new event AAA-BBB-ddddddddddddddddddd.',
        "extendedProperties" => array(
            "shared" => array(
                "name" => "John",
                "surname" => "Smith",
                "createdBy" => "Piearsta_lv",
            ),
        ),
        'end' => array(
            'dateTime' => '2020-07-17T11:00:00',
            //'dateTime' => '2020-07-14T17:00:00+02:00',
            'timeZone' => 'Europe/Riga'
        ),
        'start' => array(
            'dateTime' => '2020-07-17T10:00:00',
            //'dateTime' => '2020-07-14T16:00:00+02:00',
            'timeZone' => 'Europe/Riga'
        )
//    'reminders' => array(
//        'useDefault' => FALSE,
//        'overrides' => array(
//            array('method' => 'email', 'minutes' => 24 * 60),
//            array('method' => 'popup', 'minutes' => 10),
//        ),
//    ),
    ));

    $calendarId = 'primary';

    //$service->events->insert($calendarId, $newEvent);

    $events = $service->events->listEvents('primary', array(
        'timeMin' => '2020-07-17T00:00:00Z',
        'timeMax' => '2020-07-20T23:59:00Z',
//    'showDeleted' => true,
//    'timeZone' => 'Europe/Riga'
    ));

    $evCount = $events->count();
    $evItems = $events->getItems();
    $evSumm = $events->getSummary();

    echo '<h2>Doctor\'s ' . $doctorData['name'] . ' ' . $doctorData['surname'] . 'calendar events</h2>';
    echo '<p>events found: ' . $evCount . '</p>';

    /** @var Google_Service_Calendar_Event $eventObj */
    $eventObj = new Google_Service_Calendar_Event();

    foreach ($events->getItems() as $key => $eventObj) {
        /** Google_Service_Calendar_Event */

        if($eventObj->getStatus() != 'cancelled') {
            echo '<h3>' . $key . '. Event: ' . $eventObj->getSummary() . '</h3>';
            echo '<p>Status: ' . $eventObj->getStatus() . '</p>';
            echo '<p>Description: ' . $eventObj->getDescription() . '</p>';
            echo '<p>Start: ' . $eventObj->getStart()->getDateTime() . '</p>';
            echo '<p>Start converted: ' . date(PIEARSTA_DT_FORMAT, strtotime($eventObj->getStart()->getDateTime())) . '</p>';
            echo '<p>End: ' . $eventObj->getEnd()->getDateTime() . '</p>';
            echo '<p>ColorId: ' . $eventObj->getColorId() . '</p>';

            $extProps = $eventObj->getExtendedProperties();

            if($extProps) {
                echo '<h4>extended properties:</h4>';
                pre($eventObj->getExtendedProperties()->getPrivate());
            }

            echo '<hr>';
            echo '<h4>raw event obj</h4>';
            pre($eventObj->toSimpleObject());
            echo '<hr>';
        }
    }


    //pre($evItems);
    //pre($evSumm);
    //pre($events);
        exit;
    // end of debug

}


if($debug) {
    echo '<br>';
    echo '<h1>Access token:</h1>';
    pre($accessToken);
    echo '<br>';
}


// texts for template

$t = array(
    'success_header' => gL('gapi_success_header', 'Thank you!', 'lv'),
    'success_text' => gL('gapi_success_text', 'New reservations on Piearsta.lv portal will be added to your google calendar.', 'lv'),
    'fail_header' => gL('gapi_fail_header', 'Error occurred!', 'lv'),
    'fail_text' => gL('gapi_fail_text', 'Please contact Piearsta.lv support to get new api request link:', 'lv'),
);

?>
<!DOCTYPE html>
<html lang="lv">

<head>
    <title>Piearsta.lv</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
    <link rel="icon" href="/favicon.ico?v=1">
    <link rel="shortcut icon" href="/favicon.ico?v=1">
</head>

<body style="">

<section id="page">

    <header class="nolang">
        <section class="line1">
            <div class="wrapd wrapt">
                <a href="/" class="logo">
                    <h2>
                        <img alt="Logo Title" src="/img/piearsta-logo.png" class="ver1ib ver2ib">
                    </h2>
                </a>
            </div>
        </section>

    </header>

    <div id="content">
        <div class="wrapd">
            <?php if($error): ?>
                <h1><?=$t['fail_header']?></h1>
                <h2><?=$t['fail_text']?></h2>
                <h2><a href="mailto:palidziba@piearsta.lv">palidziba@piearsta.lv</a></h2>
            <?php else: ?>
                <h1><?=$t['success_header']?></h1>
                <h2><?=$t['success_text']?></h2>
            <?php endif; ?>
        </div>
    </div>

</section>

<footer>
    <div class="copyline"><div class="wrapd">
            <div class="cont">
                <div class="copy ver1 ver2">© 2015  Visas autortiesības aizsargātas</div>
                <div class="links">
                    <div class="item"><a href="/lietosanas-noteikumi/">Lietošanas noteikumi</a></div>
                    <div class="item"><a href="/privatuma-politika/">Privātuma politika</a></div>
                </div>
            </div>
            <div class="to_the_top" data-goto="page"><i class="fa fa-angle-up"></i></div>
        </div></div>
</footer>

<style type="text/css">

    html, body, div, span, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, abbr, address, cite, code, del, dfn, em, img, ins, kbd, q, samp, small, strong, sub, sup, var, b, i, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, figure, footer, header, hgroup, menu, nav, section, time, mark, audio, video {
        margin: 0;
        padding: 0;
        border: 0;
        outline: 0;
        font-size: 100%;
        vertical-align: baseline;
        background: transparent;
        -moz-text-size-adjust: none;
        -webkit-text-size-adjust: none;
        -ms-text-size-adjust: none;
    }

    html {}

    html * {
        box-sizing: border-box;
    }

    body {
        position: relative;
        height: 100%;
        font-family: Arial, Helvetica, 'Trebuchet MS', sans-serif;
        background-color: #ffffff;
        color: #404446;
        font-size: 16px;
        line-height: 1.22;
    }

    #page {
        position: relative;
        padding: 0 30px;
    }

    header section > div:after {
        content: " ";
        display: block;
        height: 0;
        clear: both;
        visibility: hidden;
    }

    #content a {
        text-decoration: none;
        color: #009fe5;
        cursor: pointer;
    }

    footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        padding: 18px 30px;
        background-color: #009fe5;
    }

    footer .wrapd .cont > div {
        display: inline-block;
        padding-right: 16px;
        color: #b4e8ff;
        font-size: 12px;
        line-height: 14px;
    }

    footer .wrapd .cont .links {
        padding-left: 16px;
        border-left: 1px solid #0091d1;
    }

    footer .wrapd .cont .links > div {
        display: inline-block;
        padding-right: 16px;
    }

    footer .wrapd .cont .links > div a {
        color: #fff;
        cursor: pointer;
        text-decoration: none;
    }

    h1 {
        font-size: 32px;
        margin-bottom: 20px;
    }

    h2 {
        font-size: 18px;
        margin-bottom: 10px;
    }

    header {
        position: relative;
        z-index: 500;
        padding-top: 30px;
        margin: 0 0 30px 0;
    }

    header .line1 > div {
        overflow: visible;
    }

    header .line1 .logo {
        float: left;
        width: 250px;
        height: 133px;
        font-size: 0;
        line-height: 133px;
    }

    .wrapd {
        width: 100%;
        max-width: 940px;
        margin: 0 auto;
    }

</style>

</body>
</html>

