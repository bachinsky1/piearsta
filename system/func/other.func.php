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
 * Function that generates correct site backlink (even if opening a direct url)
 * @author Ņikita Maļgins
 */
function getBackLink() {
    $useExternal = false;
    $internal = "javascript:history.back();";

    $parent = getClosestParent();
    if ($parent) {
        $external = $parent;
    } else {
        $external = "";
    }

    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = $_SERVER['HTTP_REFERER'];
        $referer = parse_url($referer);
        if ($referer['host'] != $_SERVER['HTTP_HOST']) {
            // external link
            $useExternal = true;
        }
    } else {
        $useExternal = true;
    }
    if ($useExternal) {
        return $external;
    }
    return $internal;
}

/**
 * Function returns full link to previous page
 */
function getClosestParent() {
    $cfg = &loadLibClass('config');
    $current = $_SERVER['REQUEST_URI'];
    $current = explode("/", $current);

    if ($current[count($current) - 1] == "") {
        // if wasn't docUrl
        unset($current[count($current) - 1]);
    }

    if ($cfg->get("langInTheEnd") && checkLangEnabled($current[count($current) - 1])) {
        // removing last element
        unset($current[count($current) - 2]);
    } else {
        // removing last element
        unset($current[count($current) - 1]);
    }

    if (!empty($current)) {
        return implode("/", $current) . "/";
    }
    return false;
}

/**
 *
 */
function redirectToClosestParent() {
    $parent = getClosestParent();
    if ($parent) {
        redirect($parent);
    }
}

/**
 * @param $string
 * @param bool $chars
 * @param bool $words
 * @param string $end
 * @return string
 */
function cutString($string, $chars = false, $words = false, $end = "...") {
    if ($chars || $words) {
        if ($chars) {
            if (mb_strlen($string) > $chars) {
                $i = $chars;
                while (substr_unicode($string, $i, 1) != " ") {
                    $i--;
                }
                $string = substr_unicode($string, 0, $i) . ($end ? " " . $end : "");
            }
        }

        if ($words) {
            $explode = explode(" ", $string);
            if (count($explode) > $words) {
                $string = implode(" ", array_slice($explode, 0, $words));
            }
        }
    }
    return $string;
}

function clearTabs($string) {
    $string = preg_replace('/[\s\t\r\n]+/', ' ', $string);
    return $string;
}

/**
 * @param $str
 * @param $s
 * @param null $l
 * @return string
 */
function substr_unicode($str, $s, $l = null) {
    return join("", array_slice(preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY), $s, $l));
}

/**
 * Return curent page url
 * @author Rolands EĆ…ā€ Ć„Ā£elis <rolands@efumo.lv>
 * @return string
 */
function curPageURL() {
    $pageURL = 'http';
    if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

function curPageURL2() {
    return str_replace('?' . $_SERVER['QUERY_STRING'], '', curPageURL());
}

function curPostUrl(){
    $pageURL = $_SERVER["REQUEST_URI"];
    return $pageURL;
}

/**
 * Returns all enable site langs
 * @param   $lang AS string, selected value
 * @author  JÄ�nis Å akars <janis.sakars@efumo.lv>
 */
function getAllSiteLangs($lang = '') {

    $values = array();

    $mdb = &loadLibClass('db');

    $query = new query($mdb, "SELECT `lang`, `title` FROM `ad_languages` WHERE `enable`='1' ORDER BY `sort` ASC");
    while($query->getrow()) {
        $values[$query->field('lang')] = $query->field('title');
    }

    return dropDownFieldOptions($values, $lang, true);
}

function reSort($table, $id, $params = array(), $field = 'sort') {
    $mdb = &loadLibClass("db");
    $where = array();
    if(!empty($params)){
        foreach($params as $key => $val){
            $where[] = " `" . $key . "` = '".  mres($val)."' ";
        }
    }
    $where[] = "`id` != " . $id;
    $whereSql = implode(" AND ", $where);
    $dbQuery = "UPDATE `" . $table . "` SET `" . $field . "` =`" . $field . "`+1".(strlen($whereSql) ? " WHERE ".$whereSql : "");
    $query = new query($mdb, $dbQuery);
}

function showFileSize($filePath, $limit) {

    if(file_exists($filePath)) {

        $size = filesize($filePath);

        if($size <= $limit * 1048576) {
            return round($size / 1024, 2) . gL("fileSizeKb", "Kb");
        }
        else {
            return round($size / 1048576, 2) . gL("fileSizeMb", "Mb");
        }
    }
    else {
        return false;
    }
}

/**
 *  GET first words from string
 * @param string $string      given string
 * @param type $wordCount   word count
 * return string              first words
 *
 */
function getFirstWords($string, $wordCount = 25, $delimiter = '' ) {
    $string = strip_tags($string);
    $symbols = array(
        "\t" => ' ',
        "\n" => ' ',
        "\r" => ' ',
        ' '  => ' ',
        '_'  => ' ',
        '!'  => ' ',
        '@'  => ' ',
        '#'  => ' ',
        '$'  => ' ',
        '%'  => ' ',
        '^'  => ' ',
        '&'  => '&',
        '*'  => ' ',
        '('  => ' ',
        ')'  => ' ',
        '+'  => ' ',
        '{'  => ' ',
        '}'  => ' ',
        '['  => ' ',
        ']'  => ' ',
        '"'  => ' ',
        '"'  => ' ',
        '"'  => ' ',
        ';'  => ';',
        ','  => ' ',
        '.'  => ' ',
        '>'  => ' ',
        '<'  => ' ',
        '/'  => ' ',
        '\\' => ' ',
        '"'  => ' ',
        'ā€˛'  => ' ',
        'ā€¯'  => ' ',
        '&nbsp;'  => ' ',
        '&scaron;'  => 'Å�',
        '~'  => ' '
    );

    foreach($symbols as $replace => $cail){
        $string = str_replace($replace, $cail, $string);
    }
    $description = '';
    $words = explode(' ', trim($string));

    if(count($words)) {
        $i = 1;

        foreach($words as $key => $word) {
            if(trim($word) != "" ) {
                if($i > 1) {
                    $description .= $delimiter . ' ';
                }
                $description .= $word;
                if($i == $wordCount ) {
                    break;
                }

                $i++;
            }
        }
    }

    return $description;
}

/*
 * @param string $text      given string
 * @param type $words       word count
 * return string            limited words
 */
function generateLimitedWordsText($text, $words = 25,  $separator = '') {
    $newText = '';
    $text_array  = preg_split("/[\s]+/", $text);
    $word_count = count($text_array);
    if($word_count > $words){
        $word_count = $words;
    }
    for($i = 0; $i < $word_count; $i++){
        if($i == 0){
            $newText .= $text_array[$i];
        } else {
            $newText .= $separator.' '.$text_array[$i];
        }
    }
    return $newText;
}


/**
 * Send e-mail template
 * @param   $tmpl_key AS string
 * @param   $values AS array
 * @param   $lang AS string
 */
function sendEmailTemplate($tmpl_key, $values, $lang) {

    global $mdb, $config;

    $query = new query($mdb, "SELECT `id` FROM `mod_email_templates` WHERE `template_key` = '" . $tmpl_key .'" LIMIT 1');
    $tmpl_id = $query->getOne();

    $query = new query($mdb, "SELECT * FROM `mod_email_templates_data` WHERE `email_templates_id` = " . intval($tmpl_id) ." AND `lang`='". $lang ."' LIMIT 1");
    if($query->num_rows()) {
        $tmpl_values = $query->getrow();

        $to = $tmpl_values['to_email'];

        $from = $tmpl_values['from_email'];

        $subject = $tmpl_values['email_subject'];

        $content = stripslashes($tmpl_values['email_body']);

        $query = new query($mdb, "SELECT `variable_key` FROM `mod_email_templates_variable` WHERE `email_templates_id` = '" . intval($tmpl_id) .'"');
        $variables = $query->getArray();
        foreach($variables as $key => $val) {
            if(isset($values[$key])) {
                $to = str_replace('{var:' . $key . '}', stripslashes($values[$key]), $to);
                $from = str_replace('{var:' . $key . '}', stripslashes($values[$key]), $from);
                $subject = str_replace('{var:' . $key . '}', stripslashes($values[$key]), $subject);
                $content = str_replace('{var:' . $key . '}', stripslashes($values[$key]), $content);
            }
        }

        return sendMail($to, $subject, $content, array(), $from);
    }

    return false;
}

function getTranslation($cfg, $emailTemplateId, $lang)
{
    $translation = $cfg->getData($emailTemplateId . '/' . $lang);

    if (!$translation) {
        $translation = $cfg->getData($emailTemplateId . '/' . getDefaultLang());
    }

    return $translation;

}

function getTranslatedCancellationReasons($cfg, $lang)
{
    $translation = $cfg->getSiteDataTab('Cancellation reasons', true, $lang);

    if (!$translation) {
        $translation = $cfg->getSiteDataTab('Cancellation reasons', true, getDefaultLang());
        if (!$translation) {
            $translation = $cfg->getSiteDataTab('Cancellation reasons', true);
        }
    }
    return $translation;
}

function sendReservationEmail($reservation, $status, $lang = '')
{

    // statuses are:
    // 0 - waiting
    // 1 -

    /** @var config $cfg */
    $cfg = loadLibClass('config');
    $db = loadLibClass('db');

    $piearstaUrl = $cfg->get('cron_piearstaUrl');

    $usePaidTemplate = false;

    $webLang = !empty($lang) ? $lang : 'lv';

    if($reservation['payment_type'] == 1){
        //$payment_type = gL('profile_reservation_payment_type_country', '', 'lv');
        $message = gL('profile_reservation_payment_type_country_info_text1', '', $webLang);
    }
    elseif($reservation['payment_type'] == 2){
        //$payment_type = gL('profile_reservation_payment_type_pay', '', 'lv');
        $message = gL('profile_reservation_payment_type_pay_info_text1', '', $webLang);
        $usePaidTemplate = true;
    }
    else{
        //$payment_type = gL('profile_reservation_payment_type_country_pay', '', 'lv');
        $message = gL('profile_reservation_payment_type_country_pay_info_text1', '', $webLang);
        $usePaidTemplate = true;
    }

    $resLink = $cfg->get('cron_piearstaUrl') . $webLang . '/profils/mani-pieraksti/?openRes=' . $reservation['id'];

    $qrParams = array(
        'chs' => '300x300',
        'cht' => 'qr',
        'choe' => 'UTF-8',
        'chl' => $resLink,
    );

    $qrSrc = 'https://chart.googleapis.com/chart?' . http_build_query($qrParams);

    $reservation['clinic_citytitle'] = isset($reservation['clinic_citytitle']) && $reservation['clinic_citytitle'] ? $reservation['clinic_citytitle'] . ', ' : '';

    $reservation['clinic_address'] = 'Adrese: ' . $reservation['clinic_citytitle'] . $reservation['clinic_address'];

    $reservation['clinic_email'] = $reservation['clinic_email'] ?
        '' . str_replace('{clinic_email}', $reservation['clinic_email'],
            gL('profile_reservation_ok_clinic_email', '{clinic_email}')) . ''
        : '';

    if($reservation['status_reason'] == '@/deletedByGoogleSync') {
        $reservation['status_reason'] = gL('gapi_deleted_message', 'Reservation was canceled by doctor.', $webLang);
    }

    $startTime = (empty($reservation['start']))
        ? gL('profile_reservation_start_time_not_set', 'Laiks nav norādīts', $webLang)
        : date("d.m.Y H:i", strtotime($reservation['start']));

    if($status == '0') {

        $keys = array(
            '{start_time}',
            '{doctor_name}',
            '{clinic_name}',
            '{clinic_address}',
            '{clinic_zvani}',
            '{clinic_phone}',
            '{clinic_email}',
            '{service_name}',
            '{status}',
            '{notice}',
            '{message}',
            '{piearsta_url}',
            '{qr_src}',
            '{res_link}',
        );

        $reservation['clinic_zvani'] = $reservation['clinic_phone'] ?
            str_replace('{phone_number}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_phone', 'Ja pēc 24 stundām nesaņemsi pieraksta apstiprinājumu, lūdzu zvani: {phone_number} !', $webLang))
            : '';

        $reservation['clinic_phone'] = $reservation['clinic_phone'] ?
            '' . str_replace('{phone}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_clinic_phone', '{phone}', $webLang)) . ''
            : '';

        $values = array(
            $startTime,
            $reservation['name'] . ' ' . $reservation['surname'],
            $reservation['clinic_name'],
            $reservation['clinic_address'],
            $reservation['clinic_zvani'],
            $reservation['clinic_phone'],
            $reservation['clinic_email'],
            $reservation['title'],
            gL('profile_reservation_status_' . ($status == 'changed' ? $reservation['status'] : $status) , '', $webLang),
            $reservation['status_reason'],
            $message,
            $piearstaUrl,
            $qrSrc,
            $resLink,
        );

    } elseif ($status == '6') {

        $keys = array(
            '{start_time}',
            '{doctor_name}',
            '{clinic_name}',
            '{clinic_address}',
            '{clinic_zvani}',
            '{clinic_phone}',
            '{clinic_email}',
            '{service_name}',
            '{status}',
            '{notice}',
            '{message}',
            '{finishReservationLink}',
            '{piearsta_url}',
            '{qr_src}',
            '{res_link}',
        );

        $reservation['clinic_zvani'] = $reservation['clinic_phone'] ?
            str_replace('{phone_number}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_phone', 'Ja pēc 24 stundām nesaņemsi pieraksta apstiprinājumu, lūdzu zvani: {phone_number} !', $webLang))
            : '';

        $reservation['clinic_phone'] = $reservation['clinic_phone'] ?
            '<p>' . str_replace('{phone}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_clinic_phone', '{phone}', $webLang)) . '</p>'
            : '';

        $values = array(
            $startTime,
            $reservation['name'] . ' ' . $reservation['surname'],
            $reservation['clinic_name'],
            $reservation['clinic_address'],
            $reservation['clinic_zvani'],
            $reservation['clinic_phone'],
            $reservation['clinic_email'],
            $reservation['title'],
            gL('profile_reservation_status_' . ($status == 'changed' ? $reservation['status'] : $status), '', $webLang),
            $reservation['status_reason'],
            $message,
            $reservation['completeReservationLink'],
            $piearstaUrl,
            $qrSrc,
            $resLink,
        );

    } elseif ($status == '9' || $status == '10' || $status == '11' ) {

        $keys = array(
            '{start_time}',
            '{doctor_name}',
            '{clinic_name}',
            '{clinic_address}',
            '{clinic_zvani}',
            '{clinic_phone}',
            '{clinic_email}',
            '{service_name}',
            '{status}',
            '{notice}',
            '{message}',
            '{piearsta_url}',
            '{qr_src}',
            '{res_link}',
        );

        $reservation['clinic_zvani'] = $reservation['clinic_phone'] ?
            str_replace('{phone_number}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_phone', 'Ja pēc 24 stundām nesaņemsi pieraksta apstiprinājumu, lūdzu zvani: {phone_number} !', $webLang))
            : '';

        $reservation['clinic_phone'] = $reservation['clinic_phone'] ?
            '' . str_replace('{phone}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_clinic_phone', '{phone}', $webLang)) . ''
            : '';

        $values = array(
            $startTime,
            $reservation['name'] . ' ' . $reservation['surname'],
            $reservation['clinic_name'],
            $reservation['clinic_address'],
            $reservation['clinic_zvani'],
            $reservation['clinic_phone'],
            $reservation['clinic_email'],
            $reservation['title'],
            gL('profile_reservation_status_' . ($status == 'changed' ? $reservation['status'] : $status), '', $webLang),
            $reservation['status_reason'],
            $message,
            $piearstaUrl,
            $qrSrc,
            $resLink,
        );

    } else {

        $reservation['clinic_phone'] = $reservation['clinic_phone'] ?
            '' . str_replace('{phone}', $reservation['clinic_phone'],
                gL('profile_reservation_ok_clinic_phone', '{phone}', $webLang)) . ''
            : '';

        $keys = array(
            '{payment_refund}',
            '{refund_amount}',
            '{start_time}',
            '{doctor_name}',
            '{clinic_name}',
            '{clinic_address}',
            '{clinic_phone}',
            '{clinic_email}',
            '{service_name}',
            '{status}',
            '{notice}',
            '{message}',
            '{piearsta_url}',
            '{qr_src}',
            '{res_link}',
        );

        $refund = $status == '3' && (isset($reservation['refundRequested']) && $reservation['refundRequested']) ?
            '<p>' . gL('cancel_res_refund_message', 'You have requested payment refund.', $webLang) . '</p>' :
            '';
        $priceString = $status == '3' && (isset($reservation['refundRequested']) && $reservation['refundRequested']) ?
            '<p>' . gL('cancel_res_refund_price', 'Total amount to be refunded: ', $webLang) . $reservation['service_price']  . ' Eur.</p>':
            '';

        $values = array(
            $refund,
            $priceString,
            $startTime,
            $reservation['name'] . ' ' . $reservation['surname'],
            $reservation['clinic_name'],
            $reservation['clinic_address'],
            $reservation['clinic_phone'],
            $reservation['clinic_email'],
            $reservation['title'],
            gL('profile_reservation_status_' . ($status == 'changed' ? $reservation['status'] : $status), '', $webLang),
            $reservation['status_reason'],
            $message,
            $piearstaUrl,
            $qrSrc,
            $resLink,
        );
    }

//    if($reservation['service_type'] == '1' && !empty($reservation['consultation_vroom'])) {
//        $keys[] = '{consultation_link}';
//
//        // here we can construct string with consultation vroom link
//
//        $consString = $cfg['vroom'][$cfg['env'] . 'BaseUrl'] . $reservation['consultation_vroom'];
//
//        $values[] = $consString;
//    }


    $template = 'resMailBody_' . $status;

    // For 0 and 2 statuses (waiting and confirmed) we should use different templates for state and paid slot types
    // Template names are:
    // resMailBody_0_free
    // resMailBody_0_paid
    // resMailBody_2_free
    // resMailBody_2_paid
    if($status == '0' || $status == '2') {

        $template = 'resMailBody_' . $status . ($usePaidTemplate ? '_paid' : '_free');
    }

    $email = getTranslation($cfg, 'resMailFrom', $webLang);
    $subject = getTranslation($cfg, 'resMailSubject_' . $status, $webLang);
    $body = getTranslation($cfg, $template, $webLang);
    $body = str_replace($keys, $values, $body);

    $result = sendMail($reservation['email'], $subject, $body, array(), $email, true);
    //logDebug(pR($reservation, true));
    //logDebug($body);
    //logDebug($result);

    if ($reservation['profile_id']) {
        addMessageToProfile($reservation['profile_id'], $subject, $body, $reservation['clinic_id']);
    }

    return $result;
}

function sendConsultationEmail($reservation, $status)
{
    //
    //
    //

}

/**
 * @param $reservation
 * @param Tmpl $tmplObj
 * @return bool
 */
function sendRefundEmailToSupport($reservation, Tmpl $tmplObj)
{
    /** @var config $cfg */
    $cfg = loadLibClass('config');

    /** @var Module $module */
    $module = loadLibClass('module');

    /** @var Tmpl $tmpl */
    $tmpl = $tmplObj;

    $email = $cfg->getData('resMailFrom/lv');
    $supportEmail = $cfg->get('supportEmail');

    $amount = !empty($reservation['amountToRefund']) ? ' Amount to refund: ' . $reservation['amountToRefund'] : '';

    $subject = 'REFUND request. Res: ' . $reservation['id'] . '. User: ' . $reservation['profile_id'] . $amount;

    // DEBUG
    ob_start();
    pre($reservation);
    $resObj = ob_get_clean();

    $module->setPData($reservation, 'resData');
    $module->setPData($reservation['orderData'], 'orderData');
    $module->setPData($reservation['transactionData'], 'transactionData');
    $module->setPData($resObj, 'reservationObject');

    $oldDir = $tmpl->getTmplDir();
    $tmpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/reservation/');
    $body = $tmpl->output('refundEmail', $module->getPData());
    $tmpl->setTmplDir($oldDir);

    return sendMail($supportEmail, $subject, $body, array(), $email, true);
}

/**
 * @param $emailTo
 * @param $pathToTemplate
 * @param $template
 * @param $subject
 * @param $data
 * @return bool
 */
function sendEmailToPatient($emailTo, $pathToTemplate, $template, $subject, $data)
{
    /** @var config $cfg */
    $cfg = loadLibClass('config');

    /** @var Module $module */
    $module = loadLibClass('module');

    $emailFrom = $cfg->getData('resMailFrom/lv');
    $templateDir = AD_APP_FOLDER . $pathToTemplate;
    $module->setPData($data, 'data');
    $module->tpl->setTmplDir($templateDir);
    $body = $module->tpl->output($template, $module->getPData());

    return sendMail($emailTo, $subject, $body, array(), $emailFrom, true);
}

/**
 * @param array $emailData
 *      string emailTo
 *      [string pathToTemplate]
 *      [string templateName]
 *      [string subject]
 * @param array $dataForTemplate
 * @param array|null $dataForMessage
 *      bool sendMessage
 *      int profile_id
 *      [string subject]
 * @return bool
 */
function sendEmailToPatientAboutVroom($emailData, $dataForTemplate, $dataForMessage = null)
{
    $cfg = loadLibClass('config');
    $module = loadLibClass('module');
    $env = $cfg->get('env');

    if (empty($emailData['pathToTemplate'])) {
        $emailData['pathToTemplate'] = 'out/profile/tmpl/consultations/';
    }

    if (empty($emailData['templateName'])) {
        $emailData['templateName'] = 'vroomCreatedEmail.html';
    }

    if (empty($emailData['subject'])) {
        $emailData['subject'] = gl('email_subject_patient_vroom_created', 'LV Vroom has been created');
    }

    if ( ! empty($dataForTemplate['patientVroomStringId']) && empty($dataForTemplate['patientVroomFullUrl'])) {
        $baseUrl = $cfg->get('piearstaUrl');
        $signinUrlPart = trim(getLM($cfg->getData('mirros_signin_page')), '/');
        $dataForTemplate['patientVroomFullUrl'] = $baseUrl . $signinUrlPart . '/?vroomid=' .  $dataForTemplate['patientVroomStringId'];
    }

    // TODO REMOVE When done with template
    if ($env === 'dev') {
        $dataForTemplate['_debug'] = json_encode($dataForTemplate, JSON_PRETTY_PRINT);
    }
    // TODO REMOVE END

    // Send email
    $emailFrom = $cfg->getData('resMailFrom/lv'); // TODO Hardcoded "lv", if same email for all langs then its fine

    $templateDir = AD_APP_FOLDER . $emailData['pathToTemplate'];
    $module->tpl->setTmplDir($templateDir);
    $module->setPData($dataForTemplate, 'data');
    $body = $module->tpl->output($emailData['templateName'], $module->getPData());

    $emailSendResult = sendMail($emailData['emailTo'], $emailData['subject'], $body, array(), $emailFrom, true);

    $logData = array(
        'emailSendResult' => $emailSendResult,
    );
    logDebug('other.func sendEmailToPatientAboutVroom ' . json_encode($logData, JSON_PRETTY_PRINT));

    // Send message
    if (isset($dataForMessage['sendMessage']) && $dataForMessage['sendMessage'] === true)
    {
        if (empty($dataForMessage['subject'])) {
            $dataForMessage['subject'] = gl('email_subject_patient_vroom_created', 'LV Vroom has been created');
        }

        addMessageToProfile($dataForMessage['profile_id'], $dataForMessage['subject'], $body, $dataForMessage['clinic_id']);
    }

    return $emailSendResult;
}

/**
 * @param $userId
 * @param $subject
 * @param $body
 */

function addMessageToProfile($userId, $subject, $body, $clinicId = null)
{
    if ($userId) {

        $cfg = loadLibClass('config');

        $dbData = array();
        $dbData['profile_id'] = $userId;
        $dbData['message'] = $body;
        $dbData['subject'] = $subject;
        $dbData['clinic_id'] = $clinicId;
        $dbData['created'] = time();

        saveValuesInDb($cfg->getDbTable('profiles', 'messages'), $dbData);
    }
}

// Lock slots by given time range
function lockSheduleData($start, $end, $doctorId, $clinicId, $lock)
{
    global $mdb, $cfg;

    // Update slots

    $dbQuery = "UPDATE mod_shedules SET locked = " . $lock . "
                WHERE
                    clinic_id = " . $clinicId . " AND 
                    doctor_id = " . $doctorId . " AND
                    start_time >= NOW() AND
                    (
                        (start_time >= '" . $start . "' AND start_time < '" . $end . "') OR
                        (end_time > '" . $start . "' AND end_time <= '" . $end . "') OR
                        (start_time <= '" . $start . "' AND end_time >= '" . $end . "')
                    )";
    doQuery($mdb, $dbQuery);
}

/**
 * @param $reservation
 * @param false $dontLockSlots
 * @throws Exception
 */
function updateSlots($reservation, $dontLockSlots = false)
{
    global $config, $mdb;

    $slots = getSlots($reservation['start'], $reservation['end'], $reservation['doctor_id'], $reservation['clinic_id'], true);

    if(count($slots)) {

        $date = date('Y-m-d', strtotime($reservation['start']));

        if(
            (!$reservation['hsp_reservation_id'] || !$reservation['profile_id']) || $dontLockSlots
        ) {

            $dbQuery = "UPDATE `mod_shedules`
								SET `booked` = 0
								WHERE 1
									AND `id` IN(" . implode(',', $slots) . ")";

            doQuery($mdb, $dbQuery);

            refreshDaySlots($date, $reservation['clinic_id'], $reservation['doctor_id']);

        } else {
            //
            // if we have hsp_reservation_id, record exists on SM terminal
            // so we unbook but lock slots and create lockRecord
            //
            $dbQuery = "UPDATE `mod_shedules`
								SET `booked` = 0, locked = 1 
								WHERE 1
									AND `id` IN(" . implode(',', $slots) . ")";

            doQuery($mdb, $dbQuery);

            /** @var lockRecord $lock */
            $lock = loadLibClass('lockRecord');

            $time = new DateTime();
            $interval = new DateInterval('PT' . $config['shedule_lock_time'] . 'S');
            $expireTimeObj = $time->add($interval);
            $expireTime = $expireTimeObj->format(PIEARSTA_DT_FORMAT);

            $data = array(
                'reservation_id' => $reservation['id'],
                'hsp_reservation_id' => $reservation['hsp_reservation_id'],
                'doctor_id' => $reservation['doctor_id'],
                'clinic_id' => $reservation['clinic_id'],
                'hsp_doctor_id' => $reservation['hsp_doctor_id'],
                'datetime_from' => $reservation['start'],
                'datetime_thru' => $reservation['end'],
                'expire_time' => $expireTime,
                'slots' => implode(',', $slots),
                'status' => LOCK_STATUS_LOCALLY,
            );

            $lock->createLockRecord($data);
        }

        refreshDaySlots($date, $reservation['clinic_id'], $reservation['doctor_id']);
    }
}

/**
 * @param $reservationId
 * @param $profileId
 * @param $currentStatus
 * @param null $reason
 * @return array|bool|DOMDocument|void|null
 */
function setReservationCanceled($reservationId, $profileId, $currentStatus, $reason = null)
{
    $cfg = loadLibClass('config');

    $reason = $reason ? $reason : 'canceled from popup';

    /** @var reservation $res */
    $res = loadLibClass('reservation');
    $res->setReservation($reservationId);
    $resData = $res->getReservation();

    $delResult = null;

    $dbData = array();
    $dbData['status_reason'] = clear(trim($reason));
    $dbData['profile_id'] = $profileId;
    $dbData['status'] = RESERVATION_ABORTED_BY_USER;
    $dbData['status_changed_at'] = time();
    $dbData['updated'] = time();
    $dbData['cancelled_at'] = date(PIEARSTA_DT_FORMAT);
    $dbData['cancelled_by'] = 'profile';

    if($currentStatus != RESERVATION_WAITS_PAYMENT) {
        $dbData['sended'] = '0';
    }

    if($resData) {

        if(isset($resData['google_calendar_id']) && !empty($resData['google_calendar_id'])) {
            // GAPI calendar remove event
            /** @var googleApi $gApi */
            $gApi = loadLibClass('googleApi');
            $token = $gApi->getDoctorsApiToken($resData['clinic_id'], $resData['doctor_id']);

            if(!empty($token) && isValidJson($token)) {
                $gApi->removeEvent($resData['google_calendar_id']);
            }
            // end of GAPI
        }

        $delResult = $res->deleteReservation($reason);
    }

    if($reservationId) {
        saveValuesInDb($cfg->getDbTable('reservations', 'self'), $dbData, $reservationId);
    }

    return $delResult;
}

// Book slots by given time range
function bookSheduleData($start, $end, $doctorId, $clinicId, $book)
{
    global $mdb;

    // if slots are booked, they can not be locked, so...
    $lockSet = $book == 1 ? ' locked = 0, ' : '';

    $doUpdate = true;

    // Update slots

    if($book == '0' || $book == 0) {

        // if we do unbook, then we check if another reservation exists

        $resDbQuery = "SELECT r.id FROM mod_reservations r
                                            WHERE
                                                r.clinic_id = " . $clinicId . " AND
                                                r.doctor_id = " . $doctorId . " AND
                                                r.start >= NOW() AND
                                                r.`status` IN ( 0, 2 ) AND
                                                (
                                                    ( r.start >= '$start' AND r.start < '$end' ) OR
                                                    ( r.end <= '$end' AND r.end > '$start' ) OR
                                                    (r.start >= '$start' AND r.end <= '$end') OR
                                                    (r.start <= '$start' AND r.end >= '$end')
                                                )";

        $query = new query($mdb, $resDbQuery);

        if($query->num_rows() > 0) {
            $doUpdate = false;
        }
    }

    if($doUpdate) {

        $dbQuery = "UPDATE mod_shedules SET " . $lockSet . " booked = " . $book . "
                WHERE
                    clinic_id = " . $clinicId . " AND
                    doctor_id = " . $doctorId . " AND
                    start_time >= NOW() AND
                    (
                        (start_time >= '" . $start . "' AND start_time < '" . $end . "') OR
                        (end_time > '" . $start . "' AND end_time <= '" . $end . "') OR
                        (start_time <= '" . $start . "' AND end_time >= '" . $end . "')
                    )";

        doQuery($mdb, $dbQuery);
    }
}

// Free slots for given doctor, clinic and date
/**
 * @param $date
 * @param $clinicId
 * @param $doctorId
 */
function freeSlots($date, $clinicId, $doctorId)
{
    global $mdb;

    $dbQuery = "UPDATE mod_shedules s SET s.booked = 0 
                            WHERE   s.clinic_id = " . $clinicId . " AND
                                    s.doctor_id = " . $doctorId . " AND 
                                    s.date = '" . $date . "'";
    doQuery($mdb, $dbQuery);
}

/**
 * @param $clinicId
 * @param $doctorId
 * @param $start
 * @param $end
 */
function freeSlotsByTimeRange($clinicId, $doctorId, $start, $end)
{
    global $mdb;

    $dbQuery = "UPDATE mod_shedules
                SET booked = 0  
                WHERE
                    clinic_id = ".$clinicId." AND 
                    doctor_id = ".$doctorId." AND                
                    booked = 1 AND
                    (
                        (start_time <= '".$start."' AND end_time > '".$end."') OR 
                        (end_time >= '".$end."' AND start_time < '".$end."') OR                        
                        (start_time <= '".$start."' AND end_time >= '".$end."') OR                        
                        (start_time >= '".$start."' AND end_time <= '".$end."')                        
                    ) AND
                    NOT EXISTS (
                        SELECT r.id FROM mod_reservations r
                        WHERE
                            r.clinic_id = " . $clinicId . " AND
                            r.doctor_id = " . $doctorId . " AND 
                            r.start >= NOW() AND
                            r.`status` IN ( 0, 2 ) AND
                            (
                                ( r.start >= '".$start."' AND r.start < '".$end."' ) OR
                                ( r.end <= '".$end."' AND r.end > '".$start."' ) OR
                                (r.start >= '".$start."' AND r.end <= '".$end."') OR
                                (r.start <= '".$start."' AND r.end >= '".$end."')
                            ) 
                    )";
    doQuery($mdb, $dbQuery);
}

/**
 * @param $clinicId
 * @param $doctorId
 * @param $start
 * @param $end
 */
function bookSlotsByTimeRange($clinicId, $doctorId, $start, $end)
{
    global $mdb;

    $dbQuery = "UPDATE mod_shedules
                SET booked = 1  
                WHERE
                    clinic_id = ".$clinicId." AND 
                    doctor_id = ".$doctorId." AND                
                    (
                        (start_time <= '".$start."' AND end_time > '".$end."') OR 
                        (end_time >= '".$end."' AND start_time < '".$end."') OR                        
                        (start_time <= '".$start."' AND end_time >= '".$end."') OR                        
                        (start_time >= '".$start."' AND end_time <= '".$end."')                        
                    )";
    doQuery($mdb, $dbQuery);
}

// get all active reservations for day and book slots for them
// $date -- date in string format (Y-m-d)
/**
 * @param $date
 * @param $clinicId
 * @param $doctorId
 */
function refreshDaySlots($date, $clinicId, $doctorId)
{
    global $mdb;

    $startDate = $date . ' 00:00:00';
    $endDate = $date . ' 23:59:59';

    $dbQuery = "UPDATE mod_shedules s SET s.booked = 1 
                            WHERE   s.clinic_id = " . $clinicId . " AND
                                    s.doctor_id = " . $doctorId . " AND 
                                    s.date = '" . $date . "' AND  
                                    EXISTS ( 
                                            SELECT r.id FROM mod_reservations r
                                            WHERE
                                                r.clinic_id = " . $clinicId . " AND
                                                r.doctor_id = " . $doctorId . " AND 
                                                r.start >= '" . $startDate . "' AND
                                                r.end <= '" . $endDate . "' AND 
                                                r.`status` IN ( 0, 2 ) AND
                                                (
                                                    ( r.start >= s.start_time AND r.start < s.end_time ) OR
                                                    ( r.end <= s.end_time AND r.end > s.start_time ) OR
                                                    (r.start >= s.start_time AND r.end <= s.end_time) OR
                                                    (r.start <= s.start_time AND r.end >= s.end_time)
                                                )
                                        )";
    doQuery($mdb, $dbQuery);
}

// this is old uneffective function
function old_bookSheduleData($start, $end, $doctorId, $clinicId, $book) {
    global $mdb, $cfg;


    $dbQuery = "UPDATE `" . $cfg->getDbTable('shedule', 'self') . "`
                                                        SET `booked` = " . $book . "
                                                        WHERE 1
                                                                AND                                                                 
                                                                `start_time` >= '" . mres($start) . "' AND `end_time` <= '" . mres($end) . "'    
                                                                AND `clinic_id` = '" . mres($clinicId) . "'
                                                                AND `doctor_id` = '" . mres($doctorId) . "'";
    doQuery($mdb, $dbQuery);

    $ids = array();

    $date = date("Y-m-d", strtotime($start));

    $dbQuery = "SELECT start_time FROM `" . $cfg->getDbTable('shedule', 'self') . "`
						WHERE 1
							AND `clinic_id` = '" . mres($clinicId) . "'
							AND `doctor_id` = '" . mres($doctorId) . "'
							AND `start_time` <= '" . mres($start) . "'
							AND `date` = '" . mres($date) . "'
						ORDER BY `start_time` DESC
						LIMIT 1";
    $query = new query($mdb, $dbQuery);
    if ($query->num_rows()) {
        $startTime =  $query->getOne();
    }


    $dbQuery = "SELECT end_time FROM `" . $cfg->getDbTable('shedule', 'self') . "`
					WHERE 1
						AND `clinic_id` = '" . mres($clinicId) . "'
						AND `doctor_id` = '" . mres($doctorId) . "'
						AND `end_time` >= '" . mres($end) . "'
						AND `date` = '" . mres($date) . "'
					ORDER BY `end_time` ASC
					LIMIT 1";
    $query = new query($mdb, $dbQuery);
    while ($query->getrow()) {
        $endTime = $query->getOne();
    }



    if (isset($startTime, $endTime)) {
        $dbQuery = "UPDATE `" . $cfg->getDbTable('shedule', 'self') . "`
								SET `booked` = " . $book . "
								WHERE 1
									AND `start_time` >= '" . mres($startTime) . "'
									AND `end_time` <= '" . mres($endTime) . "'
									AND `clinic_id` = '" . mres($clinicId) . "'
									AND `doctor_id` = '" . mres($doctorId) . "'";
        doQuery($mdb, $dbQuery);
    } elseif (isset($startTime) && !isset($endTime) && $start <= $startTime) {
        $dbQuery = "UPDATE `" . $cfg->getDbTable('shedule', 'self') . "`
								SET `booked` = " . $book . "
								WHERE 1
									AND `start_time` >= '" . mres($startTime) . "'
									AND `clinic_id` = '" . mres($clinicId) . "'
									AND `doctor_id` = '" . mres($doctorId) . "'
									AND `date` = '" . mres($date) . "'";
        doQuery($mdb, $dbQuery);
    }

}

// Check if user activated his profile
function checkProfileActivation() {

    global $mdb;

    if(!isset($_SESSION['profileActivationRequired'])) {
        return true;
    }

    // re-check in db -- maybe user has already activated profile in other browser or device

    if(!empty($_SESSION['user']['id'])) {

        $dbQuery = "
            SELECT * FROM mod_profiles
            WHERE
                id = " . $_SESSION['user']['id'];

        $query = new query($mdb, $dbQuery);

        if($query->num_rows()) {

            /** @var array $row */
            $row = $query->getrow();

            if(empty($row['hash_confirm']) || $row['hash_confirm'] == '') {

                unset($_GET['email']);
                unset($_GET['hash']);
                unset($_SESSION['activation_link']);
                unset($_SESSION['profileActivationRequired']);

                return true;
            }
        }
    }

    $parts = parse_url($_SERVER['REQUEST_URI']);
    if (empty($parts['host']) && ! empty($parts['path']))
    {
        $requestedUrl = trim($parts['path'], '/');
    }
    else
    {
        $requestedUrl = trim($_SERVER['REQUEST_URI'], '/');
    }

    $allowedUrls = array(
        'profils/mans-profils',
        $_SESSION['activation_link'],
        'profile/resendActivationLink',
        'ka-lietot',
        'lietosanas-noteikumi',
        'privatuma-politika',
    );

    $languages = getSiteLangs();

    foreach ($languages as $lang){

        $allowedUrlsWithLang = array(
            $lang['lang'] . '/profils/mans-profils',
            $lang['lang'] . '/profile/resendActivationLink',
            $lang['lang'] . '/ka-lietot',
            $lang['lang'] . '/lietosanas-noteikumi',
            $lang['lang'] . '/privatuma-politika',
        );
        $mergeAllowedUrlsWithLang = array_merge($allowedUrlsWithLang);
        $allowedUrls = array_merge($allowedUrls,$mergeAllowedUrlsWithLang);
    }

    $isAllowed = in_array($requestedUrl, $allowedUrls);

    if(!$isAllowed) {
        redirectRequestHandler('/profils/mans-profils/');
    }

    return true;
}

// Check if current user agree terms and confirm personal data
function checkUserAgreements() {

    setCheckRedirectUrl();

    if($_SESSION['checkAgreementAccept'] && $_SESSION['checkConfirmPersonalData']) {
        unset($_SESSION['redirectTo']);
        return true;
    }

    if(isset($_SESSION['isChecking'])) {
        if($_SERVER['REQUEST_URI'] === $_SESSION['isChecking']) {
            return true;
        }

        $requestedUrl = trim($_SERVER['REQUEST_URI'], '/');
        $allowedUrls = $_SESSION['allowedUrls'];
        $isAllowed = in_array($requestedUrl, $allowedUrls);

        if(!$isAllowed) {
            redirectRequestHandler($_SESSION['isChecking']);
        }

        return true;
    }

    if(!$_SESSION['checkAgreementAccept']) {
        checkAgreementAccept();
    }

    if(!$_SESSION['checkConfirmPersonalData']) {
        checkConfirmPersonalData();
    }

    $url = $_SESSION['redirectTo'];
    unset($_SESSION['redirectTo']);

    redirectRequestHandler($url);
}

// Check if user agreement accept is still valid
// if no, we reset user agree_terms to null
function checkAgreementAccept() {
    global $cfg;
    global $config;

    $user = getS('user');

    // get and parse referer
    $ref = parse_url($_SERVER['HTTP_REFERER']);
    $dcUrls = $cfg->get('dcAllowedUrls');

    if(!empty($_SESSION['dc']) || (is_array($dcUrls) && in_array($ref['host'], $dcUrls))) {
        $_SESSION['redirectTo'] = $_SERVER["REQUEST_URI"];
    }

    $_SESSION['redirectTo'] = $_SESSION['redirectTo'] ? $_SESSION['redirectTo'] : getLM($cfg->getData('mirros_default_profile_page'));

    if(isset($user['agree_terms']) && null !== $user['agree_terms']) {
        // check date not expired
        $agreementTerm = isset($config['agreement_term']) ? $config['agreement_term'] : '1 year';
        $agreementDate = DateTime::createFromFormat('U', $user['agree_terms']);
        $expirationDate = $agreementDate->add(DateInterval::createFromDateString($agreementTerm));
        $expired = new DateTime() >= $expirationDate;

        if($expired !== false) {
            updateUserProfileDateBasedValues(null, 'agree_terms');
            $_SESSION['checkAgreementAccept'] = false;
        } else {
            // Check passed
            $_SESSION['checkAgreementAccept'] = true;
            return true;
        }
    }

    $_SESSION['isChecking'] = getLM($cfg->getData('mirros_agree_terms_page'));
    $_SESSION['allowedUrls'] = array(
        'agree-terms',
        'ka-lietot',
        'lietosanas-noteikumi',
        'privatuma-politika',
        'profils/iziet',
        'profile/deleteProfile',
        'profile/deleteProfileConfirm',
    );

    redirectRequestHandler(getLM($cfg->getData('mirros_agree_terms_page')));
}

// Check if user confirm personal data is still valid
// if no, we reset user confirm_personal_data to null
function checkConfirmPersonalData() {
    global $cfg;
    global $config;

    $user = getS('user');

    // get and parse referer
    $ref = parse_url($_SERVER['HTTP_REFERER']);
    $dcUrls = $cfg->get('dcAllowedUrls');

    if(!empty($_SESSION['dc']) || (is_array($dcUrls) && in_array($ref['host'], $dcUrls))) {
        $_SESSION['redirectTo'] = $_SERVER["REQUEST_URI"];
    }

    $_SESSION['redirectTo'] = $_SESSION['redirectTo'] ? $_SESSION['redirectTo'] : getLM($cfg->getData('mirros_default_profile_page'));

    if(isset($user['confirm_personal_data']) && null !== $user['confirm_personal_data']) {
        // check date not expired
        $confirmPersonalTerm = isset($config['agreement_term']) ? $config['agreement_term'] : '1 year';
        $confirmPersonalDate = DateTime::createFromFormat('U', $user['confirm_personal_data']);
        $expirationDate = $confirmPersonalDate->add(DateInterval::createFromDateString($confirmPersonalTerm));
        $expired = new DateTime() >= $expirationDate;

        if($expired) {
            updateUserProfileDateBasedValues(null, 'confirm_personal_data');
            $_SESSION['checkConfirmPersonalData'] = false;
        } else {
            // Check passed
            $_SESSION['checkConfirmPersonalData'] = true;
            return true;
        }
    }

    $_SESSION['isChecking'] = getLM($cfg->getData('mirros_profile_edit_page'));
    $_SESSION['allowedUrls'] = array(
        'profils/mani-dati',
        'ka-lietot',
        'lietosanas-noteikumi',
        'privatuma-politika',
        'profils/iziet',
        'profile/deleteProfile',
        'profile/deleteProfileConfirm',
    );

    redirectRequestHandler(getLM($cfg->getData('mirros_profile_edit_page')));
}

// If nothing passed or null passed we reset agree_term in db and session,
// otherwise if timestamp passed we store it to db and session
/**
 * @param null $date
 * @return bool
 */
function updateUserProfileDateBasedValues($date = null, $field = '') {
    global $mdb, $cfg;

    if($field === '') {
        echo 'Error! Empty field parameter.';
        exit;
    }

    $dbTable = $cfg->getDbTable('profiles', 'self');
    /** @var array $user */
    $user = getS('user');
    $uid = intval($user['id']);

    if(!$date) {
        $date = 'null';
    }

    // update db
    $dbQuery = "UPDATE $dbTable SET $field=$date WHERE `id`=$uid;";
    $result = new query($mdb, $dbQuery);

    if($result->result) {
        // update session
        $_SESSION['user'][$field] = is_null($date) ? null : strval($date);
        return true;
    }

    return false;
}

function redirectRequestHandler($url = '/') {

    // if ajax request, we send json response with location key set to redirect url
    if(strtolower(server('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest') {

        $response = array(
            'location' => $url
        );
        jsonSend($response);
    }

    // not ajax request - we redirect user to redirect url
    redirect($url);
}

// returns url with query string if any params passed
function buildUrl($url = '', $params = null) {
    $urlString = rtrim($url, '/\\');

    if($params && is_array($params) && count($params) > 0) {
        $i = 0;
        foreach ($params as $key => $value) {
            $urlString .= (($i > 0) ? '&' : '?') . $key . '=' . $value;
            $i++;
        }
    }
    return $urlString;
}

//
function setCheckRedirectUrl() {
    if(isset($_POST['url']) && $_POST['url'] && !isset($_SESSION['redirectTo'])) {

        $param = isset($_POST['redirect_params']) && count($_POST['redirect_params']) > 0 ?
            $_POST['redirect_params'] :
            null;

        $_SESSION['redirectTo'] = buildUrl($_POST['url'], $param);
        unset($_POST['url']);
    } elseif (isset($_GET['url']) && $_GET['url'] && !isset($_SESSION['redirectTo'])) {
        $_SESSION['redirectTo'] = $_GET['url'];
    }
}

// validate json string
function isValidJson($data) {
    if (!empty($data)) {
        @json_decode($data);
        return (json_last_error() === JSON_ERROR_NONE);
    }
    return false;
}

/**
 * @param $start
 * @param $end
 * @param $doctorId
 * @param $clinicId
 * @param bool $onlyIds
 * @return array
 */
function getSlots($start, $end, $doctorId, $clinicId, $onlyIds = false)
{
    global $mdb;

    $slots = array();

    $dbQuery = "SELECT * FROM mod_shedules 
                WHERE
                    clinic_id = " . $clinicId . " AND 
                    doctor_id = " . $doctorId . " AND
                    start_time >= NOW() AND
                    (
                        (start_time >= '" . $start . "' AND start_time < '" . $end . "') OR
                        (end_time > '" . $start . "' AND end_time <= '" . $end . "') OR
                        (start_time <= '" . $start . "' AND end_time >= '" . $end . "')
                    )";

    try {
        $query = new query($mdb, $dbQuery);
    } catch(Exception $e) {
        $query = null;
        $exc = array(
            'SQL' => $dbQuery,
            'msg' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
            'string' => $e->getTraceAsString(),
        );
    }

    if($query && $query->num_rows()) {

        while ($row = $query->getrow()) {

            if($onlyIds) {
                $slots[] = $row['id'];
            } else {
                $slots[] = $row;
            }
        }
    }

    if(!empty($exc)) {
        $slots = array(
            'Exception' => $exc,
        );
    }

    return $slots;
}

/**
 * @return bool
 */
function isIe()
{
    $ua = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
    if (preg_match('~MSIE|Internet Explorer~i', $ua) || (strpos($ua, 'Trident/7.0') !== false && strpos($ua, 'rv:11.0') !== false)) {
        // do stuff for IE
        return true;
    }

    return false;
}

/**
 * @param db $db
 * @param $dbQuery
 * @return int
 */
function doQuery(db $db, $dbQuery)
{
    $query = new query($db, $dbQuery);
    $count = $query->affected_rows();
    $query->free();
    return $count;
}

/**
 * @param SimpleXMLElement $xmlObject
 * @return array
 */
function simpleXmlToArray(SimpleXMLElement $xmlObject)
{
    $array = [];
    foreach ($xmlObject->children() as $node) {
        $array[$node->getName()] = is_array($node) ? simpleXmlToArray($node) : (string)$node;
    }

    return $array;
}

/**
 * @param $userId
 */
function disableRegistrationByUserId($userId)
{
    global $mdb;

    $dbQuery = "SELECT * FROM mod_profiles 
                WHERE 
                    id = " . mres($userId);
    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {

        $user = $query->getrow();

        $email = $user['email'];

        if(strpos($email, '=') === false) {
            $email = time() . '=' . $email;
        }

        $dbQuery = "UPDATE mod_profiles
                    SET
                        email = '" . $email . "',
                        enable = 0,
                        updated = " . time() . "
                    WHERE
                        id = " . $userId . " AND 
                        hash_confirm <> ''";

        doQuery($mdb, $dbQuery);
    }
}


// Returns array of matched elements from multi-dimensional array $arr
// empty array if no matches found
/**
 * @param array $arr
 * @param $field
 * @param $value
 * @return array
 */
function findByField(array $arr, $field, $value, $not = false)
{
    if($not) {
        return array_filter($arr, function ($item) use ($field, $value) {
            return $item[$field] != $value;
        });
    }

    return array_filter($arr, function ($item) use ($field, $value) {
        return $item[$field] == $value;
    });
}

// returns array of timetable records filtered by given start and end time
/**
 * @param array $ttRecords
 * @param $sTime
 * @param $eTime
 * @return array
 */
function filterByTime(array $ttRecords, $sTime, $eTime)
{
    $result = array();

    $sTime = strtotime($sTime) + 1;
    $eTime = strtotime($eTime) - 1;

    foreach ($ttRecords as $record) {

        $recSt = strtotime($record['start_time']);
        $recEt = strtotime($record['end_time']);

        if(
            (
                $sTime >= $recSt &&
                $sTime <= $recEt
            ) ||
            (
                $eTime >= $recSt &&
                $eTime <= $recEt
            )
        ) {
            $result[] = $record;
        }
    }

    return $result;
}

// Collects free slots from given start time
// and calculates time available for reservation
/**
 * @param $clinicId
 * @param $doctorId
 * @param $startTime
 * @return array
 */
function getAvailableTime($clinicId, $doctorId, $startTime)
{
    global $mdb;

    // calculate available time

    /** @var config $cfg */
    $cfg = loadLibClass('config');

    $date = date('Y-m-d', strtotime($startTime));

    $availableSlotsQuery = "SELECT * FROM " . $cfg->getDbTable('shedule', 'self') .
        " WHERE 1
                                    AND clinic_id = " . $clinicId . "
                                    AND doctor_id = " . $doctorId . "
                                    AND (booked = 0 OR booked IS NULL)
                                    AND (locked = 0 OR locked IS NULL)
                                    AND `date` = '" . $date . "'
                                    AND start_time >= '" . $startTime . "'
                            ORDER BY start_time ASC";

    $avQuery = new query($mdb, $availableSlotsQuery);

    $thisDaySlots = array();

    if($avQuery->num_rows()) {

        while ($avSlot = $avQuery->getrow()) {
            $thisDaySlots[] = $avSlot;
        }
    }

    $availableSlots = array();
    $availableTime = 0;

    foreach ($thisDaySlots as $key => $value) {

        if($key == 0) {

            $availableSlots[] = $value;
            $availableTime += intval($value['interval']);

        } else {

            if($value['start_time'] == $availableSlots[$key - 1]['end_time']) {

                $availableSlots[] = $value;
                $availableTime += intval($value['interval']);

            } else {

                break;
            }
        }
    }

    return array(
        'availableSlots' => $availableSlots,
        'availableTime' => $availableTime,
    );
}

/**
 * @param $email
 * @return bool
 */
function validateEmail($email)
{
    // validation
    return true;
}

/**
 * @param $phone
 * @return bool
 */
function validateMobilePhone($phone)
{
    // validation
    return true;
}

/**
 * @param null $notification
 * @param null $email
 * @param null $phone
 * @return array
 */
function notifyDoctor($notification = null, $email = null, $phone = null)
{
    $result = array();

    if(!$notification) {
        $result['success'] = false;
        $result['message'] = 'No notification';
        return $result;
    }

    if(!$email && !$phone) {
        $result['success'] = false;
        $result['message'] = 'No email and phone';
        return $result;
    }

    // TODO: email and phone notify
    //
    //

    return $result;
}

/** @param $attemptId
 * @param bool $returnAll
 * @return array|bool|int
 */
function getVerificationResult($attemptId, $returnAll = false)
{
    global $mdb;

    if(!$attemptId) {
        return false;
    }

    $dbQuery = "SELECT * FROM mod_verification_attempts 
                    WHERE id = '" . mres($attemptId) . "'";

    $query = new query($mdb, $dbQuery);

    $row = $query->getrow();

    return $returnAll ? $row : $row['result'];
}

/**
 * @param $attemptId
 * @param $request
 * @return bool
 */
function verificationTimeout($attemptId, $request = null)
{
    global $mdb;

    if(!$attemptId) {
        $attemptId = getP('attemptId');

        if(!$attemptId) {
            return false;
        }
    }

    if(intval(getVerificationResult($attemptId)) > 0) {
        return false;
    }

    $log_request = '';

    if($request && is_array($request) && count($request) > 0) {
        $log_request = ", log_request = '" . http_build_query($request) . "' ";
    }

    $dbQuery = "UPDATE mod_verification_attempts
                    SET
                        result = 1 
                        " . $log_request . "
                    WHERE
                        id = " . mres($attemptId);

    doQuery($mdb, $dbQuery);
}

/**
 * @param $attemptId
 * @param $request
 * @return bool
 */
function cancelVerification($attemptId, $request = null)
{
    global $mdb;

    if(!$attemptId) {
        $attemptId = getP('attemptId');

        if(!$attemptId) {
            return false;
        }
    }

    $log_request = '';

    if($request && is_array($request) && count($request) > 0) {
        $log_request = ", log_request = '" . http_build_query($request) . "' ";
    }

    $dbQuery = "UPDATE mod_verification_attempts
                    SET
                        result = 4 
                        " . $log_request . " 
                    WHERE
                        id = " . mres($attemptId);

    doQuery($mdb, $dbQuery);
}

/**
 * @param $attemptId
 * @return bool|null
 */
function checkVerificationResult($attemptId)
{
    if(!$attemptId) {
        $attemptId = getP('attemptId');

        if(!$attemptId) {
            return false;
        }
    }

    $result = null;
    $maxCheckTime = 10;
    $endTime = microtime(true) + (intval($maxCheckTime) / 1000);

    // Timer cycle
    while(true) {

        if(in_array(intval($result['result']), array(1,2,3,4))) {
            break;
        }

        // check timeout
        if ((microtime(true) > $endTime) && in_array(intval($result['result']), array(0))) {
            break;
        }

        $result = getVerificationResult($attemptId, true);
    }

    if(in_array(intval($result['result']), array(0,1))) {
        verificationTimeout($attemptId);
    }

    return $result;
}

function unique_key($array, $keyname){

    $new_array = array();
    foreach($array as $key => $value) {

        if(!isset($new_array[$value[$keyname]])) {
            $new_array[$value[$keyname]] = $value;
        }
    }
    $new_array = array_values($new_array);
    return $new_array;
}

// moves array element with $key to top of array
function move_to_top(&$array, $key) {
    $temp = array($key => $array[$key]);
    unset($array[$key]);
    $array = $temp + $array;
}

// run collect items for mod_homepage_items table
// this info used to sort homepage items and to sort clinics in clincs list as well
function fillHomepageItemsTable($noCliOutput = false)
{
    global $mdb;

    $debug = $noCliOutput ? false : DEBUG;

    $infoFromClassifier = array();

    $dbQuery = "CREATE TABLE IF NOT EXISTS `mod_homepage_items_temp` (
	`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	`type` TINYINT(2) UNSIGNED NOT NULL,
	`original_id` INT(11) UNSIGNED NOT NULL,
	`title` VARCHAR(255) NOT NULL,
	`title_clean` VARCHAR(255) NOT NULL,
	`doctors_with_schedules` INT(11) NOT NULL DEFAULT '0',
	`created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `type` (`type`),
	INDEX `title` (`title`),
	INDEX `doctors_with_schedules` (`doctors_with_schedules`)
    )
    COLLATE='utf8_general_ci'
    ENGINE=MyISAM";

    doQuery($mdb, $dbQuery);

    $dbQuery = "CREATE TABLE IF NOT EXISTS `mod_homepage_items_titles_temp` (
     `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     `item_id` INT(10) UNSIGNED NOT NULL,
     `title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
     `lang` VARCHAR(2) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
     PRIMARY KEY (`id`) USING BTREE,
     INDEX `lang` (`lang`) USING BTREE,
     INDEX `item_id` (`item_id`) USING BTREE
    )
    COLLATE='utf8_general_ci'
    ENGINE=InnoDB;";

    doQuery($mdb, $dbQuery);

    $dbQuery = "SELECT cl.type AS `type`, cli.title AS `title`, cl.id AS original_id, COUNT(d2cl.d_id) AS doctors_with_schedules, cli.lang FROM mod_classificators cl
            LEFT JOIN mod_classificators_info cli ON(cli.c_id = cl.id)
            LEFT JOIN mod_doctors_to_classificators d2cl ON(cli.c_id = d2cl.cl_id)
            LEFT OUTER JOIN mod_doctors d ON(d2cl.d_id = d.id)
            LEFT JOIN mod_doctors_to_clinics d2c ON(d.id = d2c.d_id)
            LEFT JOIN mod_clinics c ON(c.id = d2c.c_id)
            WHERE 
                c.enabled = 1 AND
                d.enabled = 1 AND 
                d.deleted = 0 AND 
                cl.enable = 1 AND
                cl.type = 3
            GROUP BY title, d2cl.cl_id
            UNION
            SELECT cl.type AS type, cli.title AS title, cl.id AS original_id, COUNT(d2c.d_id) AS doctors_with_schedules, cli.lang FROM mod_classificators cl
            LEFT JOIN mod_classificators_info cli ON(cli.c_id = cl.id)
            LEFT JOIN mod_clinics c ON(c.city = cl.id)
            LEFT JOIN mod_doctors_to_clinics d2c ON(c.id = d2c.c_id)
            LEFT OUTER JOIN mod_doctors d ON(d.id = d2c.d_id) 
            WHERE
                c.enabled = 1 AND
                d.enabled = 1 AND 
                d.deleted = 0 AND 
                cl.enable = 1 AND
                cl.type = 1
            GROUP BY cl.id
            UNION
            SELECT cl.type AS type, cli.title AS title, cl.id AS original_id, '1' AS doctors_with_schedules, cli.lang FROM mod_classificators cl
            LEFT JOIN mod_classificators_info cli ON(cli.c_id = cl.id)
            LEFT JOIN mod_clinics_to_classificators c2cl ON(c2cl.cl_id = cl.id)
            LEFT JOIN mod_clinics c ON(c.id = c2cl.clinic_id)
            LEFT JOIN mod_doctors_to_clinics d2c ON(c.id = d2c.c_id)
            LEFT OUTER JOIN mod_doctors d ON(d.id = d2c.d_id) 
            WHERE
                c.enabled = 1 AND
                d.enabled = 1 AND 
                d.deleted = 0 AND 
                cl.enable = 1 AND
                cl.type = 5
            GROUP BY cl.id
            UNION
            SELECT 9 AS type, c.name AS title, c.id AS original_id, COUNT(d2c.d_id) AS doctors_with_schedules, 'lv' AS lang FROM mod_clinics c
            LEFT JOIN mod_doctors_to_clinics d2c ON(c.id = d2c.c_id) 
            LEFT OUTER JOIN mod_doctors d ON(d.id = d2c.d_id)
            WHERE 
                  c.enabled = 1 AND
                  c.name > '' 
            GROUP BY c.id
            ORDER BY `type` ASC, original_id ASC, `doctors_with_schedules` DESC, `title` ASC";

    $query = new query($mdb, $dbQuery);

    $collectTime = date('Y-m-d', time()) . ' 00:00:00';
    $dataArray = array();
    $titlesDataArray = array();

    if($query->num_rows()) {

        while($row = $query->getrow()) {

            $infoFromClassifier[] = $row;
            $count = $row['doctors_with_schedules'];

            // clinics
            if($row['type'] == 9) {

                $docClinicsQuery = "SELECT d2c.c_id, COUNT(d2c.d_id) AS doc_with_shedules_count FROM mod_doctors d
                                LEFT JOIN mod_doctors_to_clinics d2c ON(d.id = d2c.d_id)
                                LEFT JOIN mod_clinics c ON(c.id = d2c.c_id) 
                                WHERE
                                    d2c.c_id = '" . $row['original_id'] . "' AND
                                    EXISTS (
                                        SELECT id FROM mod_shedules 
                                        WHERE 
                                            mod_shedules.doctor_id = d.id AND  
                                            mod_shedules.clinic_id = d2c.c_id AND
                                            mod_shedules.start_time >= ' " . $collectTime . "' 
                                    ) AND
                                    c.enabled = 1 AND 
                                    d.enabled = 1 AND
                                    d.deleted = 0
                                GROUP BY d2c.c_id";

                $sQuery = new query($mdb, $docClinicsQuery);

                if($sQuery->num_rows()) {
                    $sRow = $sQuery->getrow();
                    $count = $sRow['doc_with_shedules_count'];
                } else {
                    $count = 0;
                }

                // specialities
            } elseif($row['type'] == 3) {

                $docSpecQuery = "SELECT cl.id, COUNT(d2cl.d_id) AS doc_with_shedules_count FROM mod_classificators cl
                                    LEFT JOIN mod_classificators_info cli ON(cli.c_id = cl.id)
                                    LEFT JOIN mod_doctors_to_classificators d2cl ON(cli.c_id = d2cl.cl_id)
                                    LEFT OUTER JOIN mod_doctors d ON(d2cl.d_id = d.id) 
                                    LEFT JOIN mod_doctors_to_clinics d2c ON(d2c.d_id = d.id)
                                    LEFT JOIN mod_clinics c ON(c.id = d2c.c_id) 
                                    WHERE 
                                        EXISTS (
                                            SELECT id FROM mod_shedules 
                                            WHERE 
                                                mod_shedules.doctor_id = d.id AND  
                                                mod_shedules.clinic_id = d2c.c_id AND  
                                                mod_shedules.start_time >= ' " . $collectTime . "' 
                                        ) AND
                                        cl.id = " . $row['original_id'] . " AND
                                        c.enabled = 1 AND  
                                        d.enabled = 1 AND 
                                        d.deleted = 0 AND 
                                        d2cl.cl_type = 3
                                    GROUP BY title, d2cl.cl_id";

                $sQuery = new query($mdb, $docSpecQuery);

                if($sQuery->num_rows()) {
                    $sRow = $sQuery->getrow();
                    $count = $sRow['doc_with_shedules_count'];
                } else {
                    $count = 0;
                }

                // cities
            } elseif($row['type'] == 1) {

                $docCitiesQuery = "SELECT cl.id, COUNT(d2c.d_id) AS doc_with_shedules_count FROM mod_classificators cl
                                    LEFT JOIN mod_classificators_info cli ON(cli.c_id = cl.id)
                                    LEFT JOIN mod_clinics c ON(c.city = cl.id)
                                    LEFT JOIN mod_doctors_to_clinics d2c ON(c.id = d2c.c_id)
                                    LEFT OUTER JOIN mod_doctors d ON(d.id = d2c.d_id) 
                                    WHERE
                                        EXISTS (
                                            SELECT id FROM mod_shedules 
                                            WHERE 
                                                mod_shedules.doctor_id = d.id AND  
                                                mod_shedules.clinic_id = d2c.c_id AND
                                                mod_shedules.start_time >= ' " . $collectTime . "' 
                                        ) AND
                                        cl.id = " . $row['original_id'] . " AND
                                        c.enabled = 1 AND  
                                        d.enabled = 1 AND 
                                        d.deleted = 0 AND 
                                        cl.type = 1
                                    GROUP BY cl.id";

                $sQuery = new query($mdb, $docCitiesQuery);

                if($sQuery->num_rows()) {
                    $sRow = $sQuery->getrow();
                    $count = $sRow['doc_with_shedules_count'];
                } else {
                    $count = 0;
                }
            }

            $titlesDataArray[] = array(
                'item_id' => $row['original_id'],
                'title' =>  preg_replace("/[^\w\p{L}\p{N}\p{Pd} ]|[_]/u", '', $row['title']),
                'lang' => $row['lang'],
            );

            if($row['lang'] == 'lv') {

                $dataArray[] = array(
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'title_clean' => preg_replace("/[^\w\p{L}\p{N}\p{Pd} ]|[_]/u", '', $row['title']),
                    'original_id' => $row['original_id'],
                    'doctors_with_schedules' => $count,
                );
            }
        }
    }

    //function execMultiInsertSQL($SQLexec, $table_name, $data_arr, $insert_limit = 100, $ins_ignore = false)
    execMultiInsertSQL($mdb, 'mod_homepage_items_temp', $dataArray, 1);

    // drop main table if exists
    $dbQuery = "DROP TABLE IF EXISTS mod_homepage_items";
    doQuery($mdb, $dbQuery);

    // rename temp table
    $dbQuery = "ALTER TABLE mod_homepage_items_temp RENAME TO mod_homepage_items";
    doQuery($mdb, $dbQuery);


    //
    // Fill localized titles table

    //function execMultiInsertSQL($SQLexec, $table_name, $data_arr, $insert_limit = 100, $ins_ignore = false)
    execMultiInsertSQL($mdb, 'mod_homepage_items_titles_temp', $titlesDataArray, 1);

    // drop main table if exists
    $dbQuery = "DROP TABLE IF EXISTS mod_homepage_items_titles";
    doQuery($mdb, $dbQuery);

    // rename temp table
    $dbQuery = "ALTER TABLE mod_homepage_items_titles_temp RENAME TO mod_homepage_items_titles";
    doQuery($mdb, $dbQuery);



    if($debug) {

        echo PHP_EOL . 'Info selected:' . PHP_EOL;
        pre($dataArray);
        echo PHP_EOL . 'Cron finished.' . PHP_EOL;
    }
}

/**
 * @param $id
 * @param $userId
 * @return array|int|null
 */
function createResArray($id, $userId = null, $lang = 'lv')
{
    global $mdb;

    $usrQuery = '';

    if($userId) {
        $usrQuery = " AND r.`profile_id` = '" . mres($userId) . "' ";
    }

    $dbQuery = "SELECT
    					r.id,
    					r.hsp_reservation_id,
    					r.profile_id,
                        r.shedule_id,    			
    					r.start,
    					r.end,
    					r.profile_person_id,
    					r.doctor_id,
    					r.clinic_id,
    					r.service_id,
    					r.service_price,
    					r.order_id,
                        r.refund_info,
		    			r.status_reason,
		    			r.status_changed_at,
    					r.created,
    					r.notice,
    					r.google_calendar_id,
    					r.service_type,
    					r.consultation_vroom,
    					r.consultation_vroom_doctor,
    					r.vchat_room,
    					r.refund_info,
                        r.need_approval,
    					d.hsp_resource_id AS hsp_doctor_id,
    					d.phone AS doctor_phone,
		    			di.name,
		    			di.surname,
    					d.url as doctor_url,
		    			c.name as clinic_name,
    					cc.phone as clinic_phone,
						cc.email as clinic_email,
		    			ci.address as clinic_address,
    					c.url as clinic_url,
                        cldCity.title as clinic_citytitle,
		    			cld.title,
		    			r.status,
    					r.payment_type,
    					pp.name as ppname,
    					pp.surname as ppsurname,
    					pp.person_id as ppcode,
    					pp.person_number as ppnumber,
    					pp.gender as ppgender,
    					pp.date_of_birth as ppdate_of_birth,
						p.email,
						p.phone as p_phone,
						p.name as pname, 
						p.surname as psurname,
						p.person_id as pcode, 
						p.person_number as pnumber, 
						p.gender as pgender, 
						p.date_of_birth as pdate_of_birth,  
    					dtcl.length_minutes,
                        ro.options
							FROM `mod_reservations` r
								LEFT JOIN `mod_reservation_options` ro ON (r.id = ro.reservation_id)
								LEFT JOIN `mod_doctors` d ON (r.doctor_id = d.id)
								LEFT JOIN `mod_doctors_info` di ON (d.id = di.doctor_id AND di.lang = 'lv')
								LEFT JOIN `mod_doctors_to_classificators` dtcl ON (d.id = dtcl.d_id AND r.service_id = dtcl.cl_id)		
								LEFT JOIN `mod_clinics` c ON (r.clinic_id = c.id)
								LEFT JOIN `mod_clinics_info` ci ON (c.id = ci.clinic_id AND ci.lang = 'lv')
							    LEFT JOIN mod_classificators_info cldCity ON (cldCity.c_id = c.city) 
								LEFT JOIN `mod_clinics_contacts` cc ON (cc.clinic_id = c.id AND cc.default = 1)
							    INNER JOIN `mod_classificators_info` cld ON (
                                    r.service_id = cld.c_id
                                    AND IF(EXISTS(SELECT id FROM mod_classificators_info ci2 WHERE ci2.c_id = r.service_id AND ci2.lang = '".$lang."'), cld.lang = '".$lang."', cld.lang = 'lv')
								)
								LEFT JOIN `mod_profiles_persons` pp ON (r.profile_person_id = pp.id)
								LEFT JOIN `mod_profiles` p ON (r.profile_id = p.id)		
							WHERE 1
								" . $usrQuery . "
								AND r.id = '" . mres($id) . "'";

    $query = new query($mdb, $dbQuery);

    if ($query->num_rows()) {

        $row = $query->getrow();

        if($row['options']) {
            $row['options'] = json_decode($row['options'], true);

            if(!$row['options']) {
                unset($row['options']);
            }
        }

        if ($row['payment_type'] == 1) {
            $row['warning'] = gL('profile_reservation_payment_type_country_info_text', 'Jābūt līdzi nosūtījumam no ģimenes ārsta, personas apliecinošais dokuments. Citādi pacients netiks pieņemts un pakalpojums <b>nebūs apmaksāts no valsts puses</b>.');
        } else {
            $row['warning'] = gL('profile_reservation_payment_type_client_or_mix_info_text', 'Jābūt līdzi nosūtījumam no ģimenes ārsta, personas apliecinošais dokuments. Citādi pacients netiks pieņemts un pakalpojums <b>nebūs apmaksāts no valsts puses</b>.');
        }

        if (time() > ($row['created'] + 86400)) {
            $row['warning24'] = true;
        }

        if($row['status_reason'] == '@/deletedByGoogleSync') {
            $row['status_reason'] = gL('gapi_deleted_message', 'Reservation was canceled by doctor.', 'lv');
        }

        $row['start_gc'] = date("Ymd\\THi00", strtotime($row['start']));
        $row['end_gc'] = date("Ymd\\THi00", strtotime($row['end']));

        return $row;
    }

    return null;
}

/**
 * @return array|null
 */
function getContactThemes()
{
    global $mdb;

    $result = null;

    $dbQuery = "SELECT * FROM mod_contact_themes";
    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {

        $result = array();

        while ($row = $query->getrow()) {
            $result[$row['id']] = $row;
        }
    }

    return $result;
}

/**
 * @param $id
 * @return bool|string|null
 */
function getClinicById($id) {

    global $mdb, $cfg;

    if ($id == '') {
        return false;
    }

    $dbQuery = "SELECT *
                    FROM `" . $cfg->getDbTable('clinics', 'self') . "`
                    WHERE 1
                        AND id = '" . mres($id) . "'
                    LIMIT 1";

    $query = new query($mdb, $dbQuery);

    if ($query->num_rows()) {

        return $query->getrow();
    }

    return null;
}

/**
 * @param $id
 * @return bool|string|null
 */
function getDoctorById($id) {

    global $mdb, $cfg;

    if ($id == '') {
        return false;
    }

    $dbQuery = "SELECT d.*, di.name, di.surname, di.notify_phone, di.notify_email  
                    FROM `" . $cfg->getDbTable('doctors', 'self') . "` d
                    LEFT JOIN mod_doctors_info di ON ( di.doctor_id = d.id )  
                    WHERE 1
                        AND d.id = '" . mres($id) . "'
                    LIMIT 1";

    $query = new query($mdb, $dbQuery);

    if ($query->num_rows()) {

        $doc = $query->getrow();

        // we get one (first) doctor specialty -- this is needed for vroom

        $dbQuery = "SELECT * 
                    FROM mod_doctors_to_classificators d2c 
                    LEFT JOIN mod_classificators_info ci ON ( ci.c_id = d2c.cl_id ) 
                    WHERE
                        d2c.d_id = " . mres($id) . " AND 
                        d2c.cl_type = 3 
                    LIMIT 1";

        $query = new query($mdb, $dbQuery);

        $doc['specialty'] = $query->getrow()['title'];

        return $doc;
    }

    return null;
}

/**
 * @param $pk
 * @return array|int|null
 */
function getDoctorByPk($pk) {

    global $mdb, $cfg;

    if ($pk == '') {
        return null;
    }

    $dbQuery = "SELECT d.*, di.name, di.surname, di.notify_phone, di.notify_email  
                    FROM `" . $cfg->getDbTable('doctors', 'self') . "` d
                    LEFT JOIN mod_doctors_info di ON ( di.doctor_id = d.id )  
                    WHERE 1
                        AND d.person_code = '" . mres($pk) . "'
                    LIMIT 1";

    $query = new query($mdb, $dbQuery);

    if ($query->num_rows()) {

        $doc = $query->getrow();

        // we get one (first) doctor specialty -- this is needed for vroom

        $dbQuery = "SELECT * 
                    FROM mod_doctors_to_classificators d2c 
                    LEFT JOIN mod_classificators_info ci ON ( ci.c_id = d2c.cl_id ) 
                    WHERE
                        d2c.d_id = " . $doc['id'] . " AND 
                        d2c.cl_type = 3 
                    LIMIT 1";

        $query = new query($mdb, $dbQuery);

        $doc['specialty'] = $query->getrow()['title'];

        return $doc;
    }

    return null;
}

// check for maximum in-progress/prepared rows to process (non final statuses)
function maxProcessingExceeded($cronId = false,$maxRows = 10000, $saveLog = true)
{
    global $mdb, $cfg, $pid, $method;

    if(empty($cronId) || empty($maxRows)) return false;

    $dbQuery = "SELECT * FROM sm_booking_batches WHERE cron_id = " . $cronId . " AND status IN (0,1)";
    $query = new query($mdb, $dbQuery);

    if($query->num_rows() > $maxRows) {
        if(!$saveLog) return true;
        $data = array(
            'sys_process_id' => $pid,
            'method' => $method,
            'status' => '5',
            'exec_status' => '0',
            'end_time' => date('Y-m-d H:i:s', time()),
            'exec_time' => '0',
            'error_message' => 'More, than '.$maxRows.' batches to process at the same time',
        );
        saveValuesInDb('vaccination_cron_log', $data);
        return true;
    }
    return false;
}


function prepareVaccinationSlots($cfg)
{
    global $mdb;

    // Trace the method
    if (extension_loaded('newrelic')) {
        newrelic_add_custom_tracer("prepareVaccinationSlots");
    }

    $debug = DEBUG;

    $params = array();

    $st = time();
    $method = 'prepareSlotsCache';
    $pid = getmypid();
    $maxExecTime = $cfg->get('vaccinationPrepareSlotsMaxExecTime');

    $data = array(
        'sys_process_id' => $pid,
        'method' => $method,
        'params' => json_encode($params),
        'status' => '1',
        'start_time' => date('Y-m-d H:i:s', time()),
        'expiration_time' => date('Y-m-d H:i:s', (time() + $maxExecTime)),
        'error_message' => '',
    );

    $cronLogId = saveValuesInDb('vaccination_cron_log', $data);

    /** @var monitoringFlags $flag */
    $flag = loadLibClass('monitoringFlags');
    $error = false;
    $warning = false;

// Get slots from mod_shedules table

// create cache log and obtain new cache_id

    $startTime = date('Y-m-d H:i:s', time());

    $expQuery = "UPDATE vivat_cache_log 
                    SET
                        status = '2'";
    $eq = new query($mdb, $expQuery);

    $cacheQuery = "INSERT INTO vivat_cache_log (generation_start, status) VALUES ('" . $startTime . "', '0')";
    $query = new query($mdb, $cacheQuery);

    $cacheId = $mdb->get_insert_id();

    $trQuery = "TRUNCATE TABLE vivat_cache_vplist";
    doQuery($mdb, $trQuery);

    $trQuery = "TRUNCATE TABLE vivat_cache_data";
    doQuery($mdb, $trQuery);


// get vaccination points and save to vplist table under current cacheId

    $vpQuery = "SELECT DISTINCT dtc.vp_id FROM mod_doctors_to_clinics dtc 
                    WHERE dtc.vp_id IS NOT NULL";

    $vpq = new query($mdb, $vpQuery);

    $allVpIds = array();
    $vpList = array();

    if($vpq->num_rows()) {
        while ($row = $vpq->getrow()) {
            $allVpIds[] = $row['vp_id'];
            $vpList[] = "'" . $row['vp_id'] . "'";
            $vplQuery = "INSERT INTO vivat_cache_vplist (cache_id, vp_id) VALUES ('" . $cacheId . "', '" . $row['vp_id'] . "')";
            $query = new query($mdb, $vplQuery);
        }
    }

    $msg = '';
    $status = '1';

// fill cache_data table with aggregated slot data

    $currDateTime = date('Y-m-d H:i:s', time());

    $insQuery = "INSERT INTO vivat_cache_data (cache_id, vp_id, date, interval_start, interval_end, free_slots)
            (SELECT " . $cacheId . " as cache_id, a.vp_id, a.date as date, 
                    CONCAT(a.date, ' ', CONCAT(a.interval_start_tmp, ':00:00')) as interval_start,
                    CASE 
                        WHEN a.interval_start_tmp = '22' 
                        THEN CONCAT(DATE_ADD(a.date, INTERVAL 1 DAY), ' 00:00:00') 
                        ELSE CONCAT(a.date, ' ', CONCAT( LPAD( (CONVERT(a.interval_start_tmp, UNSIGNED) + 2), 2, 0), ':00:00')) END as interval_end, 
                    COUNT(a.vp_id) as free_slots FROM
                (
                    SELECT dtc.vp_id as vp_id, DATE_FORMAT (s.start_time, '%Y-%m-%d') as date, s.start_time,
                    CASE 
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= '00' AND DATE_FORMAT (s.start_time, '%H' ) < '02' THEN '00'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 2 AND DATE_FORMAT (s.start_time, '%H' ) < '04' THEN '02'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 4 AND DATE_FORMAT (s.start_time, '%H' ) < '06' THEN '04'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 6 AND DATE_FORMAT (s.start_time, '%H' ) < '08' THEN '06'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 8 AND DATE_FORMAT (s.start_time, '%H' ) < '10' THEN '08'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 10 AND DATE_FORMAT (s.start_time, '%H' ) < '12' THEN '10'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 12 AND DATE_FORMAT (s.start_time, '%H' ) < '14' THEN '12'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 14 AND DATE_FORMAT (s.start_time, '%H' ) < '16' THEN '14'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 16 AND DATE_FORMAT (s.start_time, '%H' ) < '18' THEN '16'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 18 AND DATE_FORMAT (s.start_time, '%H' ) < '20' THEN '18'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 20 AND DATE_FORMAT (s.start_time, '%H' ) < '22' THEN '20'
                        WHEN DATE_FORMAT (s.start_time, '%H' ) >= 22 AND DATE_FORMAT (s.start_time, '%H' ) < '24' THEN '22'
                    END interval_start_tmp 
                    -- DATE_FORMAT (s.start_time, '%H' ) AS interval_start, DATE_FORMAT (s.start_time, '%H' )+1 as 1hr_interval_end, 
                    -- COUNT(id) free_slots
                    FROM mod_shedules AS s
                    INNER JOIN mod_doctors_to_clinics AS dtc ON (dtc.c_id = s.clinic_id AND dtc.d_id = s.doctor_id)
                    INNER JOIN mod_doctors AS d ON (d.id = s.doctor_id)
                    WHERE   
                            d.deleted = 0 AND
                            d.enabled = 1 AND
                            s.booked = 0 AND
                            s.locked = 0 AND     
                            dtc.vp_id IN (" . implode(',', $vpList) . ") AND
                            s.start_time >= '" . $currDateTime . "'
                ) a
                GROUP BY a.vp_id, a.date, a.interval_start_tmp)";

    try {

        $insq = new query($mdb, $insQuery);

    } catch (Exception $e) {

        $msg = 'Error: ' . $e->getCode() . ' Message: ' . $e->getMessage();
        $status = '2';
        $error = true;
    }

    $activeVpIds = array();
    $vpIdsToClean = array();

// get vpids, having time slots

    $dbQuery = "SELECT DISTINCT vp_id FROM vivat_cache_data WHERE cache_id = " . $cacheId;
    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {

        while ($row = $query->getrow()) {
            $activeVpIds[] = $row['vp_id'];
        }
    }

// Add special records for vpids having no time data

    foreach ($allVpIds as $vp) {

        if(!in_array($vp, $activeVpIds)) {
            $vpIdsToClean[] = $vp;

            $spDbQuery = "INSERT INTO vivat_cache_data 
                        (cache_id, vp_id, date, interval_start, interval_end, free_slots) 
                        VALUES (".$cacheId.", '".$vp."', '9999-12-31', '9999-12-31 08:00:00', '9999-12-31 10:00:00', 0)";
            $spQuery = new query($mdb, $spDbQuery);
        }
    }

// add to cache_log info that preparing cache data completed

    $totalSlotsQuery = "SELECT COUNT(id) as records, SUM(free_slots) as totalSlots FROM vivat_cache_data";
    $tsq = new query($mdb, $totalSlotsQuery);

    $statArr = $tsq->getrow();
    $records = $statArr['records'] ? $statArr['records'] : '0';
    $totalSlots = $statArr['totalSlots'] ? $statArr['totalSlots'] : '0';

    $logQuery = "UPDATE vivat_cache_log
                    SET
                        generation_end = '" . date('Y-m-d H:i:s', time()) . "',
                        status =  '1',
                        cache_records = " . $records . ",
                        total_free_slots = " . $totalSlots . "
                    WHERE
                        id = " . $cacheId;

    $lq = new query($mdb, $logQuery);

    // Monitoring flags

    if($error) {
        $flag->critical_error($method);
    } elseif ($warning) {
        $flag->warning($method);
    } else {
        $flag->ok($method);
    }

// finishing cron

    if($debug) {
        echo PHP_EOL . '++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
        var_dump(array(
            'vpArray' => $vpList,
            'cacheId' => $cacheId,
            'cacheRecords' => $records,
            'totalSlots' => $totalSlots,
        ));
        echo PHP_EOL . '++++++++++++++++++++++++++++++++++++++++++++' . PHP_EOL;
        echo PHP_EOL . 'Cron finished' . PHP_EOL;
    }

    $data = array(
        'status' => '2',
        'exec_status' => $status,
        'end_time' => date('Y-m-d H:i:s', time()),
        'exec_time' => (time() - $st),
        'error_message' => is_array($msg) ? json_encode($msg) : $msg,
    );

    saveValuesInDb('vaccination_cron_log', $data, $cronLogId);
}

/**
 * @param $slots
 * @param $startIndex
 * @return int
 */
function getTimeOfSequentalSlotsStartingFrom($slots, $startIndex)
{
    if(empty($slots[$startIndex])) {
        return 0;
    }

    $doc = $slots[$startIndex]['doctor_id'];

    $i = $startIndex;
    $availableTime = intval($slots[$i]['interval']);

    while(
        $slots[$i + 1] != null &&
        $slots[$i]['doctor_id'] == $doc &&
        $slots[$i]['end_time'] == $slots[$i + 1]['start_time']
    ) {
        $availableTime += intval($slots[$i + 1]['interval']);
        $i++;
    }

    return $availableTime;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function parseCurrentUrl($url) {
    $r  = "^(?:(?P<scheme>\w+)://)?";
    $r .= "(?P<host>(?:(?P<subdomain>[-\w\.]+)\.)?" . "(?P<domain>\w+\.(?P<extension>\w+)))";
    $r = "!$r!";                                                // Delimiters

    preg_match ( $r, $url, $out );

    return $out;
}

function updateUserProfileLang($lang)
{
    global $mdb, $cfg;

    /** @var array $user */
    $user = getS('user');
    $uid = intval($user['id']);

    $dbQuery = "UPDATE `" . $cfg->getDbTable('profiles', 'self') . "`
								SET `lang` ='" . mres($lang) . "'
								WHERE `id` ='" . $uid . "'";
    $result = new query($mdb, $dbQuery);

    if ($result) {
        return true;
    }
    return false;
}

function getAllWebLanguages()
{
    global $mdb;
    $allowedLanguages = array();
    $dbQuery = "SELECT * FROM ad_languages WHERE enable=1";
    $query = new query($mdb, $dbQuery);
    if ($query->num_rows()) {
        $allowedLanguages = $query->getArray();
    }
    return $allowedLanguages;
}

function catchFatalError($buffer)
{
    $error = error_get_last();
    if ($error['type'] == E_ERROR) {
        header("HTTP/1.0 500 Internal Server Error", true, 500);
        exit;
    }
    return $buffer;
}

function getUserData($userId, $lang = null)
{
    global $mdb;

    if(!$lang) {
        $lang = getDefaultLang();
    }

    $dbQuery = "SELECT p.*, cid.title AS insurance, ci.piearstaId AS insurancePaId, ccd.title AS city, tfa.tfa_key  AS tfa
							FROM `mod_profiles` p
								LEFT JOIN `mod_classificators` ci ON (p.insurance_id = ci.id)
								LEFT JOIN `mod_classificators_info` cid ON (cid.c_id = ci.id AND cid.lang = '" . $lang . "')
								LEFT JOIN `mod_classificators` cc ON (p.city_id = cc.id)
								LEFT JOIN `mod_classificators_info` ccd ON (ccd.c_id = cc.id AND ccd.lang = '" . $lang . "')
								LEFT JOIN mod_tfa tfa ON (tfa.profile_id = p.id)
							WHERE 1
								AND p.`id` = '" . $userId . "' 
							LIMIT 1";

    $query = new query($mdb, $dbQuery);

    $row = null;

    if($query->num_rows()) {
        $row = $query->getrow();
        $row['code'] = $row['person_id'] ? $row['person_id'] : $row['person_number'];
    }

    return $row;
}

function isProfileVerified($data) {

    global $cfg;

    $expired = false;
    $verified = $data['verified_at'] && $data['verification_method'];

    // check expiration
    if($verified) {
        $expiresAfter = $cfg->get('verification_expires_after');
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

function getPaymentMethods($result)
{
    $paymentMethods = [];

    if ($result['success']) {
        $response = json_decode($result['result'], true);
        if (!empty($response['payment_methods'])) {
            foreach ($response['payment_methods'] as $method) {
                $paymentMethods[$method['source']] = $method['display_name'];
            }
        }
    }
    return $paymentMethods;
}

function paymentSuccess($info, $state = 'success')
{
    global $mdb, $cfg;

    // $state param can be success, success_preliminary, success_preliminary_again

    // clear canceled marks from reservation
    // set it's status and sended to 0

    $data = array(
        'status_changed_at' => time(),
        'updated' => time(),
        'cancelled_at' => 'null',
        'cancelled_by' => 'null',
        'sended' => '0',
        'status_reason' => 'Payment success',
        'status' => RESERVATION_WAITS_CONFIRMATION,
    );

    /** @var Reservation $reservation */
    $reservation = loadLibClass('reservation');
    $reservation->setReservation($info['reservationId']);

    /** @var Order $order */
    $order = loadLibClass('order');
    $order->setOrder($info['orderId']);
    $orderData = $order->getOrder();
    $orderStatus = $orderData['status'];

    if(in_array($orderStatus, array(ORDER_STATUS_NEW, ORDER_STATUS_PENDING))) {

        /** @var LockRecord $lockRecord */
        $lockRecord = loadLibClass('lockRecord');
        $lockRecord->setLockRecordByOrderId($info['orderId']);

        // create new transaction or get ID of existing one
        // init transaction object with this ID

        $arrForTransaction = array(
            'orderId' => $info['orderId'],
            'payment_description' => $orderData['payment_description'],
            'payment_nonce' => $orderData['payment_nonce'],
            'response' => $info['response'],
        );

        $trId = createTransaction($arrForTransaction);
        $transaction = null;

        if($trId) {

            /** @var Transaction $transaction */
            $transaction = loadLibClass('transaction');

            $transaction->reInitClass();
            $transaction->setTransaction($trId);
        }

        $userData = getPatientById($order->getOrder()['patient_id'], $mdb);

        $info['serviceType'] = $reservation->getReservation()['service_type'];

        $reservation->updateReservation($reservation->getReservationId(), $data);

        require_once(AD_LIB_FOLDER . 'smarty/smarty.class.php');
        /** @var module $module */
        $module = loadLibClass('module');
        $module::$lang = 'lv';

        /** @var tmpl $tpl */
        $tpl = loadLibClass('tmpl');

        /** @var Insurance $insurance */
        $insurance = loadLibClass('insurance');

        $orderStatus = $state == 'success' ? ORDER_STATUS_PAID : ORDER_STATUS_PRELIMINARY_PAID;
        $transactionStatus = $state == 'success' ? TRANSACTION_STATUS_PAID : TRANSACTION_STATUS_PRELIMINARY_PAID;
        $reservationStatus = RESERVATION_WAITS_CONFIRMATION;
        $reservationStatusReason = $state == 'success' ? 'Payment success' : 'Preliminary paid';

        $info['needPaymentConfirmation'] = true;

        // set appropriate order status

        $order->setStatus($orderStatus, $reservationStatusReason);

        // update a transaction as well

        $updTrData = array(
            'status' => $transactionStatus,
            'error_message' => 'null',
            'error_code' => 'null',
        );

        if (!empty($info['paid_by'])) {
            $updTrData['payment_method'] = $info['paid_by'];
        }

        if (!empty($info['last_four_digits'])) {
            $updTrData['pan'] = $info['last_four_digits'];
        }

        if (!empty($info['stan'])) {
            $updTrData['auth_code'] = $info['stan'];
        }

        $transaction->updateTransaction($trId, $updTrData);


        // update reservation and book slots

        $reservation->setStatus($reservationStatus, $reservationStatusReason);
        $lockRecord->bookSlots();

        // update promo-code usage record if exists

        $promoDbQuery = "UPDATE promo_usage SET status = 1 WHERE reservation_id = " . $reservation->getReservationId();
        doQuery($mdb, $promoDbQuery);

        // get order html
        $orderId = $order->getOrderId();

        $data = getOrderInfoPopupData($orderId, $order, $reservation, $lockRecord, $cfg, $mdb);
        $info['orderId'] = $orderId;

        $info['order_html'] = getOrderHtml($data, $order, $tpl, $module, $mdb);
        $info['orderId'] = $orderId;
        $info['serviceType'] = $reservation->getReservation()['service_type'];

        $lang = !empty($userData['lang']) ? $userData['lang'] : getLang();

        $openRes = openReservation
        (
            $reservation->getReservationId(),
            true,
            $userData,
            $lang,
            $tpl,
            $module
        );

        // we generate invoice (without detailed transaction info)
        generatePdfInvoice($info, $order, $transaction, $insurance, $reservation, $tpl, $module, $cfg, $mdb);

        if($state == 'success') {

            $mailStatus = '0';

            $rd = $reservation->getReservation();

            if($rd['need_approval'] === '0') {
                $mailStatus = '2';
            }

            sendReservationEmail
            (
                openReservation
                (
                    $reservation->getReservationId(),
                    false,
                    $userData,
                    $lang,
                    $tpl,
                    $module
                ),
                $mailStatus,
                $lang
            );

        } elseif($state == 'success_preliminary') {

            sendReservationEmail
            (
                openReservation
                (
                    $reservation->getReservationId(),
                    false,
                    true,
                    $lang,
                    $tpl,
                    $module
                ),
                '9',
                $lang
            );
        }

        // GAPI calendar create event
        $resData = $reservation->getReservation();

        if (!isset($resData['google_calendar_id']) || empty($resData['google_calendar_id'])) {

            require_once(AD_LIB_FOLDER . 'googleApi/googleApi.class.php');
            /** @var googleApi $gApi */
            $gApi = loadLibClass('googleApi');
            $token = $gApi->getDoctorsApiToken($resData['clinic_id'], $resData['doctor_id']);

            if (!empty($token) && isValidJson($token)) {
                $gApi->createEvent(createResArray($reservation->getReservationId(), $resData['profile_id'], 'lv'));
            }
        }

        if (
            $info['serviceType'] == 1 &&
            (empty($resData['consultation_vroom']) && empty($resData['consultation_vroom_doctor'])) &&
            empty($resData['vroom_create_required'])
        ) {

            // here we should only set the flag 'vroom_create_required' to '1'
            // the logic that creates vroom is moved to cronjob 'vrooms_create.php'

            $data = array(
                'vroom_create_required' => 1,
            );

            $reservation->updateReservation($reservation->getReservationId(), $data);
        }

    } elseif ($orderStatus == ORDER_STATUS_PRELIMINARY_PAID) {

        require_once(AD_LIB_FOLDER . 'smarty/smarty.class.php');
        /** @var module $module */
        $module = loadLibClass('module');
        $module::$lang = 'lv';

        /** @var tmpl $tpl */
        $tpl = loadLibClass('tmpl');

        $userData = getPatientById($order->getOrder()['patient_id'], $mdb);

        if($state == 'success') {

            // we've got final success
            // so we need to update all entities to PAID state
            // and to send 0-status email to patient

            $orderStatus = ORDER_STATUS_PAID;
            $transactionStatus = TRANSACTION_STATUS_PAID;
            $reservationStatus = RESERVATION_WAITS_CONFIRMATION;
            $reservationStatusReason = 'Payment success';

            $reservation->setStatus($reservationStatus, $reservationStatusReason);
            $order->setStatus($orderStatus, $reservationStatusReason);

            /** @var Transaction $transaction */
            $transaction = loadLibClass('transaction');

            $transaction->reInitClass();
            $transaction->setTransaction($orderData['transaction_id']);
            $transaction->setStatus($transactionStatus);

            if ($reservation->getReservation()['start'] > date('Y-m-d H:i:s')) {
                sendReservationEmail
                (
                    openReservation
                    (
                        $reservation->getReservationId(),
                        false,
                        $userData,
                        $lang,
                        $tpl,
                        $module
                    ),
                    '11',
                    $lang
                );
            }

        } elseif($state == 'success_preliminary' || $state == 'success_preliminary_again') {

            // we've got preliminary success state or preliminary success state again
            // so we just send an email to patient

            /** @var Transaction $transaction */
            $transaction = loadLibClass('transaction');

            $transaction->reInitClass();
            $transaction->setTransaction($orderData['transaction_id']);

            // send preliminary payment email

            $shouldSendEmail = shouldSendPreliminaryPaymentEmail($transaction->getTransaction(), $reservation->getReservation());

            if ($shouldSendEmail) {

                sendReservationEmail
                (
                    openReservation
                    (
                        $reservation->getReservationId(),
                        false,
                        $userData,
                        $lang,
                        $tpl,
                        $module
                    ),
                    '10',
                    $lang
                );

                $transaction->updateEmailSent();
            }
        }
    }

    return $order->getOrderId();
}

function getPatientById($id, $mdb)
{

    if (empty($id)) {
        return null;
    }

    $dbQuery = "SELECT * FROM mod_profiles WHERE id = " . mres($id) . " LIMIT 1";
    $query = new query($mdb, $dbQuery);

    if ($query->num_rows()) {
        return $query->getRow();
    }

    return null;
}


function getOrderInfoPopupData($orderId, $order, $reservation, $lockRecord, $cfg, $mdb)
{
    global $cfg, $mdb;

    $orderData = $order->getOrder();
    $orderDetails = $order->getOrderDetails();
    $orderInfo = $order->getOrderInfo();

    if (!$orderData || !$orderDetails || !$orderInfo) {
        return false;
    }

    $serviceType = intval($orderDetails[0]['service_type']);

    $orderData['serviceDuration'] = $serviceType == 0 ? $order->getServiceDuration() : 0;

    $data = array_merge($orderData, $orderInfo);

    $reservationData = $reservation->getReservation();

    // get correct doctor data in lv locale

    if ($reservationData['doctor_id']) {

        $docDbQuery = "
                SELECT * FROM mod_doctors_info 
                WHERE
                    lang = 'lv' AND
                    doctor_id = " . $reservationData['doctor_id'];

        $docQuery = new query($mdb, $docDbQuery);

        $docData = $docQuery->getrow();

        if (!empty($docData)) {

            $data['doctor_name'] = $docData['name'];
            $data['doctor_surname'] = $docData['surname'];
        }

    }

    if (!$reservationData['doctor_id']) {
        $data['doctor_id'] = null;
        $data['doctor_name'] = null;
        $data['doctor_surname'] = null;

        $filename = $orderInfo['invoice_filename'];

        // set file path and filename
        $folder = 'profile/invoices/';
        $filepath = AD_SERVER_UPLOAD_FOLDER . $folder;

        if ($filename && file_exists($filepath . $filename)) {
            unlink($filepath . $filename);
        }

        $newData = array();
        $newData['invoice_filename'] = '';

        saveValuesInDb('mod_order_info', $newData, $orderInfo['id']);
    }

    // add lv title for service

    if ($orderDetails[0]['service_id']) {

        $sDbQuery = "
                SELECT ci.title FROM mod_classificators_info ci
                WHERE
                    IF(EXISTS(SELECT id FROM mod_classificators_info ci2 WHERE ci2.c_id = " . $orderDetails[0]['service_id'] . " AND ci2.lang = '" . getLang() . "'), ci.lang = '" . getLang() . "', ci.lang = 'lv') AND 
                    ci.c_id = " . $orderDetails[0]['service_id'];

        $sQuery = new query($mdb, $sDbQuery);

        $sData = $sQuery->getrow();

        if (!empty($sData)) {

            $orderDetails[0]['service_name'] = $sData['title'];
        }
    }

    $data['orderItems'] = array();
    $data['orderItems'] = $orderDetails;

    $data['bb'] = $cfg->get('bb');

    $lockRecord->setLockRecordByOrderId($orderId);
    $lockData = $lockRecord->getLockRecord();

    $data['date'] = $orderData['date'];
    $data['orderId'] = $data['order_id'];

    if ($serviceType == 0) {
        $lockData = $lockRecord->getLockRecord();

        $data['slots'] = $lockData['slots'];
        $data['id'] = $lockData['schedule_id'];
        $data['lockId'] = $lockData['id'];
        $data['reservationId'] = $orderData['reservation_id'];

    } else {

        if (!empty($lockData)) {
            $data['slots'] = $lockData['slots'];
            $data['id'] = $lockData['schedule_id'];
            $data['lockId'] = $lockData['id'];
        }

        $data['reservationId'] = $orderData['reservation_id'];
    }

    $billingSystemCfg = $cfg->get('billing_system');

    $data['banklinks'] = array_filter($billingSystemCfg['banklinks'], function ($bank) {
        return ($bank['active'] == 1);
    });

    $data['oldPaymentType'] = $cfg->get('oldPaymentType');

    return $data;
}


function getOrderHtml($data, $order, $tpl, $module, $mdb)
{

    $dbQuery = "SELECT c.reg_nr, c.zip, cl.title FROM mod_clinics c 
                    LEFT JOIN mod_classificators_info cl ON(c.city = cl.c_id) 
                    WHERE
                        c.id = " . $data['clinic_id'];
    $query = new query($mdb, $dbQuery);

    if ($query->num_rows()) {
        $row = $query->getrow();
        $data['clinic_reg_num'] = $row['reg_nr'];
        $data['clinic_zip'] = $row['zip'];
        $data['clinic_city'] = $row['title'];
    }

    $data['serviceDuration'] = isset($data['serviceDuration']) ? $data['serviceDuration'] : $order->getServiceDuration();

    $patient = array();

    if (!empty($data['person_id']) && !empty($data['person_name']) && !empty($data['person_surname'])) {

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

    $tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/order/');
    $module->setPData($data, "item");
    $html = $tpl->output('order', $module->getPData());
    return $html;
}

function openReservation($id, $setHtml = true, $user, $lang = null, $tpl, $module)
{

    $lang = !empty($lang) ? $lang : getLang();

    $usr = $user ? $user['id'] : null;
    $row = createResArray($id, $usr, $lang);

    if (!empty($row)) {

        $tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');

        $row['cssFolder'] = APP_ROOT . '/' . AD_CSS_SRC_FOLDER;
        $row['imageFolder'] = APP_ROOT . '/' . AD_IMAGE_FOLDER;;

        $module->setPData($row, "item");

        return $row;
    }

    return false;
}

function generatePdfInvoice($info, $order, $transaction, $insurance, $reservation, $tpl, $module, $cfg, $mdb)
{

    // collect order data
    $orderData = $order->getOrder();
    $orderId = $orderData['id'];
    $orderDetails = $order->getOrderDetails();
    $orderInfo = $order->getOrderInfo();

    $serviceType = intval($orderDetails[0]['service_type']);

    // set file path and filename
    $folder = 'profile/invoices/';
    $filepath = AD_SERVER_UPLOAD_FOLDER . $folder;
    // invoice-[userId]-[orderId]-[timestamp].pdf
    $filename = 'invoice-' . $orderData['patient_id'] . '-' . $orderId . '-' . time() . '.pdf';
    $filepath .= $filename;

    // if pdf file exists we delete it first
    //
    if ($orderInfo['invoice_filename'] && $orderInfo['invoice_filename'] != '') {

        $filename = $orderInfo['invoice_filename'];

        if (file_exists($filepath . $filename)) {
            unlink($filepath . $filename);
        }
    }

    // then we generate new one

    // prepare data
    $data = $transactionData = $transaction->getTransaction();

    $paymentReceiver = $cfg->get('paymentReceiver');
    $receiverName = $paymentReceiver['title'];
    $showTitle = $paymentReceiver['showTitle'];
    $receiverLogo = $paymentReceiver['logo'];

    // collect payment data

    $pk = $orderInfo['creator_person_id'] ? $orderInfo['creator_person_id'] : $orderInfo['creator_person_number'];
    $payer = $orderInfo['creator_name'] . ' ' . $orderInfo['creator_surname'];

    if (!empty($info['paid_by'])) {
        $paymentMethod = $info['paid_by'];
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
        substr($paymentMethod, 0, 8) != 'everyPay'
    );

    if (empty($data['pan'])) {
        $data['pan'] = $info['last_four_digits'] ? $info['last_four_digits'] : null;
    }

    if (empty($data['auth_code'])) {
        $data['auth_code'] = $info['stan'] ? $info['stan'] : null;
    }

    $dbQuery = "SELECT * FROM mod_profiles 
                    WHERE
                        id = " . $orderInfo['creator_id'];

    $query = new query($mdb, $dbQuery);

    $userData = $query->getrow();

    if (!empty($userData['insurance_id'])) {

        $insDbQuery = "SELECT * FROM mod_classificators_info 
                            WHERE
                                c_id = " . $userData['insurance_id'];

        $query = new query($mdb, $insDbQuery);

        if ($query->num_rows()) {
            $row = $query->getrow();
            $userData['insurance'] = $row['title'];
        }
    }

    $insurance = $insurance->getInsuranceData($userData, $orderData['clinic_id']);

    $data['insurance'] = $transactionData['payment_method'] == 'insurance';
    $data['insuranceName'] = !empty($insurance['companyName']) ? $insurance['companyName'] : '';
    $data['insuranceNumber'] = !empty($insurance['insuranceNumber']) ? $insurance['insuranceNumber'] : '';

    $data['payer'] = $payer;
    $data['date'] = $transactionData['fulfill_date'] ? $transactionData['fulfill_date'] : $transactionData['updated'];
    $data['pk'] = $pk;

    // we show 6 asterisks and 4 last digits from pan
    if (isset($data['pan'])) {
        $newPan = '******' . substr($data['pan'], -4);
        $data['pan'] = $newPan;
    }

    // get payment html
    $tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/');
    $module->setPData($data, "item");
    $paymentHtml = $tpl->output('payment-data', $module->getPData());

    $data = array_merge($orderData, $orderInfo);
    $data['payment_data'] = $paymentHtml;
    $data['orderItems'] = array();
    $data['orderItems'] = $orderDetails;
    $data['bb'] = $cfg->get('bb');
    $data['date'] = $orderData['date'];
    $data['orderId'] = $data['order_id'];
    $data['reservationId'] = $orderData['reservation_id'];

    $resData = $reservation->getReservation();

    if (!$resData['doctor_id']) {
        $data['doctor_id'] = null;
        $data['doctor_name'] = null;
        $data['doctor_surname'] = null;
    }

    $patient = array();

    if (!empty($data['person_id']) && !empty($data['person_name']) && !empty($data['person_surname'])) {

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
    $tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/order/');
    $module->setPData($data, "item");
    $orderHtml = $tpl->output('to-pdf', $module->getPData());

    $invoiceData = array();

    if ($showTitle) {
        $invoiceData['receiverName'] = $receiverName;
    }

    if ($receiverLogo) {
        $invoiceData['receiverLogo'] = $receiverLogo;
    }

    if (!$receiverLogo && !$showTitle) {
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
        case substr($transactionData['payment_method'], 0, 8) == 'everyPay' :
            $method = gL('profile_rezervation_payment_status_card', 'card');
            break;
        default :
            $method = gL('profile_rezervation_payment_status_bank', 'bank transfer') . ' (' . $transactionData['payment_method'] . ' internetbanka)';
    }

    $invoiceData['payment_status'] = gL('profile_rezervation_payment_status_1', 'Paid by') . ': ' . $method;

    // set css path for wkhtmltopdf
    $invoiceData['cssFolder'] = APP_ROOT . '/' . AD_CSS_SRC_FOLDER;
    $invoiceData['imageFolder'] = APP_ROOT . '/' . AD_IMAGE_FOLDER;

    // template will come from config - ?
    $template = 'invoice-pdf.html';

    // get invoice html
    $tpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/order/');
    $module->setPData($invoiceData, "item");
    $invoiceHtml = $tpl->output($template, $module->getPData());
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
    $pdf->setBinary($cfg->get('wkhtmltopdf'));
    $pdf->addPage($invoiceHtml);
    $result = $pdf->saveAs($filepath);

    if (!$result) {
        if (DEBUG) {
            echo $pdf->getError();
        }
    } else {
        $infData = array(
            'invoice_filename' => $filename,
        );

        $order->setOrderInfo($infData);

        // return filename on success
        return $filename;
    }

}

function paymentFail($info)
{
    global $mdb, $cfg;

    /** @var Reservation $reservation */
    $reservation = loadLibClass('reservation');
    $reservation->setReservation($info['reservationId']);

    /** @var Order $order */
    $order = loadLibClass('order');
    $order->setOrder($info['orderId']);
    $orderData = $order->getOrder();

    // create new transaction or get ID of existing one
    // init transaction object with this ID

    $arrForTransaction = array(
        'orderId' => $info['orderId'],
        'payment_description' => $orderData['payment_description'],
        'payment_nonce' => $orderData['payment_nonce'],
        'response' => $info['response'],
    );

    $trId = createTransaction($arrForTransaction);
    $transaction = null;

    if($trId) {

        /** @var Transaction $transaction */
        $transaction = loadLibClass('transaction');

        $transaction->reInitClass();
        $transaction->setTransaction($trId);
    }

    if (!empty($info['paid_by'])) {
        $transaction->setPaymentMethod($info['paid_by']);
    }

    /** @var LockRecord $lockRecord */
    $lockRecord = loadLibClass('lockRecord');
    $lockRecord->setLockRecordByOrderId($info['orderId']);

    require_once(AD_LIB_FOLDER . 'smarty/smarty.class.php');
    /** @var module $module */
    $module = loadLibClass('module');
    $module::$lang = 'lv';

    /** @var tmpl $tpl */
    $tpl = loadLibClass('tmpl');

    $userData = getPatientById($order->getOrder()['patient_id'], $mdb);

    $info['serviceType'] = $reservation->getReservation()['service_type'];

    // get order html
    $data = getOrderInfoPopupData($order->getOrderId(), $order, $reservation, $lockRecord, $cfg, $mdb);
    $info['order_html'] = getOrderHtml($data, $order, $tpl, $module, $mdb);

    if (strpos($info['backUrl'], '?')) {
        $info['backUrl'] .= '&orderId=' . $order->getOrderId();
    } else {
        $info['backUrl'] .= '?orderId=' . $order->getOrderId();
    }


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

    $errorCode = !empty($info['code']) ? $info['code'] : '';
    $failReason = !empty($info['message']) ? $info['message'] : '';

    $reservation->updateReservation($reservation->getReservationId(), $resData);

    $lang = !empty($userData['lang']) ? $userData['lang'] : getLang();


    if ($order->getStatus() === ORDER_STATUS_PRELIMINARY_PAID) {

        if ($reservation->getReservation()['start'] > date('Y-m-d H:i:s')) {

            sendReservationEmail
            (
                openReservation
                (
                    $reservation->getReservationId(),
                    false,
                    $userData,
                    $lang,
                    $tpl,
                    $module
                ),
                '7',
                $lang
            );

        }
    }

    $order->setStatus(ORDER_STATUS_NON_PAID, 'Payment failed');
    $transaction->setStatus(TRANSACTION_STATUS_NON_PAID, $errorCode, $failReason);

    $reservation->setStatus(RESERVATION_WAITS_PAYMENT);
    $lockRecord->reduceExpirationTime();

    return $order->getOrderId();
}

function calculateAmount($startDate, $serviceType, $servicePrice)
{
    $date = $startDate;
    $twoWeeksBefore = date('Y-m-d H:i:s', strtotime($date. ' - 14 days'));
    $dayBefore = date('Y-m-d H:i:s', strtotime($date. ' - 1 day'));
    $now = date('Y-m-d H:i:s');

    $index = 0;

    if ($startDate > $now){
        if($serviceType == 0){
            if ($twoWeeksBefore > $now){
                $index = 1;
            } else {
                if ($dayBefore >= $now) {
                    $index = 0.5;
                } else {
                    $index = 0.25;
                }
            }
        } else {
            if ($dayBefore >= $now) {
                $index = 1;
            }
        }
    }

    $amountToRefund = $servicePrice * $index;
    return  number_format((float)$amountToRefund, 2);

}

function sendRefundInfoEmailToSupport($reservation, Tmpl $tmplObj)
{
    /** @var config $cfg */
    $cfg = loadLibClass('config');

    /** @var Module $module */
    $module = loadLibClass('module');

    /** @var Tmpl $tmpl */
    $tmpl = $tmplObj;

    $email = $cfg->getData('resMailFrom/lv');
    $supportEmail = $cfg->get('supportEmail');

    $amount = !empty($reservation['amountToRefund']) ? $reservation['amountToRefund'] : '';

    $subject = 'Refund information: refund has been done by EveryPay : '.$amount. ' . Res: ' . $reservation['id'] . '. User: ' . $reservation['profile_id'] . ' Amount to refund: 0.00 Eur';

    // DEBUG
    ob_start();
    pre($reservation);
    $resObj = ob_get_clean();

    $module->setPData($reservation, 'resData');
    $module->setPData($reservation['orderData'], 'orderData');
    $module->setPData($reservation['transactionData'], 'transactionData');
    $module->setPData($resObj, 'reservationObject');

    $oldDir = $tmpl->getTmplDir();
    $tmpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/reservation/');
    $body = $tmpl->output('refundEmail', $module->getPData());
    $tmpl->setTmplDir($oldDir);

    return sendMail($supportEmail, $subject, $body, array(), $email, true);
}

function updateOrderStatus($orderId)
{
    /** @var Order $order */
    $order = loadLibClass('order');
    $order->setOrder($orderId);

    $orderStatus = 7;

    $order->setStatus($orderStatus, 'Status:sent_for_processing|E-mail to support sent');
}

function sendPaymentFailEmailToSupport($data)
{
    global $cfg;

    /** @var Reservation $reservation */
    $reservation = loadLibClass('reservation');
    $reservation->setReservation($data['reservationId']);
    $reservation = $reservation->getReservation();

    /** @var Order $order */
    $order = loadLibClass('order');
    $order->setOrder($data['orderId']);
    $orderDetails = $order->getOrderDetails();
    $userInfo = $order->getOrderInfo();
    $order = $order->getOrder();

    /** @var Transaction $transaction */
    $transaction = loadLibClass('transaction');
    $transaction->setTransaction($data['transactionId']);
    $transaction = $transaction->getTransaction();


    $reservation['title'] = $orderDetails[0]['service_name'];
    $reservation['cancellation_reason'] = 'Payment fail';
    $reservation['pname'] = $userInfo['creator_name'];
    $reservation['psurname'] = $userInfo['creator_surname'];
    $reservation['pcode'] = $userInfo['creator_person_id'];
    $reservation['p_phone'] =  $userInfo['creator_phone'];
    $reservation['email'] =  $userInfo['creator_email'];

    require_once(AD_LIB_FOLDER . 'smarty/smarty.class.php');
    /** @var module $module */
    $module = loadLibClass('module');
    $module::$lang = 'lv';

    /** @var tmpl $tpl */
    $tmpl = loadLibClass('tmpl');

    $email = $cfg->getData('resMailFrom/lv');
    $supportEmail = $cfg->get('supportEmail');

    $subject = 'PAYMENT FAIL :  . Res: ' . $reservation['id'] . '. User: ' . $reservation['profile_id'];

    // DEBUG
    ob_start();
    pre($reservation);
    $resObj = ob_get_clean();


    $module->setPData($reservation, 'resData');
    $module->setPData($order, 'orderData');
    $module->setPData($transaction, 'transactionData');
    $module->setPData($resObj, 'reservationObject');

    $oldDir = $tmpl->getTmplDir();
    $tmpl->setTmplDir(AD_APP_FOLDER . 'out/profile/tmpl/reservation/');
    $body = $tmpl->output('refundEmail', $module->getPData());
    $body = str_replace('Refund request', 'Payment fail', $body);
    $tmpl->setTmplDir($oldDir);

    return sendMail($supportEmail, $subject, $body, array(), $email, true);
}

/**
 * @param $info
 * @param $order
 * @return array
 */
function prepareTransactionData($info, $order)
{
    return array(
        'order_id' => !empty($order['id']) ? $order['id'] : null,
        'payment_uuid' => !empty($order['payment_reference']) ? $order['payment_reference'] : null,
        'payment_url' => !empty($info['payment_url']) ? $info['payment_url'] : null,
        'payment_method' => 'everyPay',
        'payment_description' => !empty($order['payment_description']) ? $order['payment_description'] : null,
        'payment_nonce' => !empty($order['payment_nonce']) ? $order['payment_nonce'] : null,
        'payment_status' => !empty($info['payment_status']) ? $info['payment_status'] : null,
    );
}

/**
 * @param $payment
 * @return array
 */
function setPaymentData($payment)
{
    return [
        'reservationId' => !empty($payment['data']['reservation_id']) ? $payment['data']['reservation_id'] : null,
        'orderId' => !empty($payment['data']['order_id']) ? $payment['data']['order_id'] : null,
        'transactionId' =>  !empty($payment['data']['transaction_id']) ? $payment['data']['transaction_id'] : null,
        'paymentMethod' => 'everyPay',
        'paid_by' => !empty($payment['payment_data']['paid_by']) ? $payment['payment_data']['paid_by'] : null,
        'last_four_digits' => !empty($payment['payment_data']['last_four_digits']) ? $payment['payment_data']['last_four_digits'] : null,
        'stan' => !empty($payment['payment_data']['stan']) ? $payment['payment_data']['stan'] : null,
        'payment_status' => !empty($payment['payment_data']['payment_status']) ? $payment['payment_data']['payment_status'] : null,
        'payment_url' => !empty($payment['payment_data']['payment_url']) ? $payment['payment_data']['payment_url'] : null,
        'code' => !empty($payment['payment_data']['code']) ? $payment['payment_data']['code'] : null,
        'message' => !empty($payment['payment_data']['message']) ?  $payment['payment_data']['message'] : null,
        'cronJob' => true
    ];
}

function paymentUnfinishedState($info)
{
    // this method called when we've got one of the following states from everyPay

    $payment_unfinished_statuses = array(
        'initial',
        'authorised',
        'waiting_for_sca',
        'waiting_for_3ds_response',
    );

    // no one of these states means that payment is complete successfully or not
    // so the only thing we do -- to prolong the waiting time

    /** @var Order $order */
    $order = loadLibClass('order');
    $order->setOrder($info['orderId']);

    /** @var LockRecord $lockRecord */
    $lockRecord = loadLibClass('lockRecord');
    $lockRecord->setLockRecordByOrderId($info['orderId']);
    $lockRecord->prolongExpirationTimeForPaymentInProcess($info['payment_status']);

    return $order->getOrderId();
}

function shouldSendPreliminaryPaymentEmail($transaction, $reservation)
{
    $lastEmailSent = $transaction['email_sent'];

    $emailAfter2 = date('Y-m-d H:i:s',
        strtotime($transaction['created'] . ' + 2 hours'));
    $emailAfter24 = date('Y-m-d H:i:s',
        strtotime($transaction['created'] . ' + 24 hours'));
    $emailAfter48 = date('Y-m-d H:i:s',
        strtotime($transaction['created'] . ' + 48 hours'));
    $emailAfter72 = date('Y-m-d H:i:s',
        strtotime($transaction['created'] . ' + 72 hours'));


    $result = false;

    if ($reservation['start'] > date('Y-m-d H:i:s')) {

        if ($emailAfter2 <= date('Y-m-d H:i:s') && $lastEmailSent < $emailAfter2) {
            $result = true;
        }

        if ($emailAfter24 <= date('Y-m-d H:i:s') && $lastEmailSent < $emailAfter24) {
            $result = true;
        }

        if ($emailAfter48 <= date('Y-m-d H:i:s') && $lastEmailSent < $emailAfter48) {
            $result = true;
        }

        if ($emailAfter72 <= date('Y-m-d H:i:s') && $lastEmailSent < $emailAfter72) {
            $result = true;

        }
    }

    return $result;
}

function getTransactionCreated($transactionId)
{
    /** @var Transaction $transaction */
    $transaction = loadLibClass('transaction');

    $transaction->setTransaction($transactionId);

    return $transaction->getTransaction()['created'];

}

function createTransaction($sessionPaymentInfo)
{
    global $mdb;

    $response = $sessionPaymentInfo['response'];

    $trData = array(
        'order_id' => !empty($sessionPaymentInfo['orderId']) ? $sessionPaymentInfo['orderId'] : null,
        'payment_uuid' => !empty($response['payment_reference']) ? $response['payment_reference'] : null,
        'payment_url' => !empty($response['payment_link']) ? $response['payment_link'] : null,
        'payment_method' => 'everyPay',
        'payment_description' => !empty($sessionPaymentInfo['payment_description']) ? $sessionPaymentInfo['payment_description'] : null,
        'payment_nonce' => !empty($sessionPaymentInfo['payment_nonce']) ? $sessionPaymentInfo['payment_nonce'] : null,
        'payment_status' => !empty($response['payment_state']) ? $response['payment_state'] : null,
    );

    // check for order id existing

    if(empty($trData['order_id'])) {
        // if it is empty -- error!
        return false;
    }

    $trId = null;

    // 1st check transaction ID in the order record

    $dbQuery = "SELECT * FROM mod_orders WHERE id = " . $trData['order_id'];
    $query = new query($mdb, $dbQuery);

    if($query->num_rows()) {
        $orderData = $query->getrow();
        $trId = !empty($orderData['transaction_id']) ? $orderData['transaction_id'] : null;
    }

    // 2nd check whether exists the transaction with given order ID

    if(!$trId) {

        $dbQuery = "SELECT * FROM mod_transactions WHERE order_id = " . $trData['order_id'];
        $query = new query($mdb, $dbQuery);

        if($query->num_rows()) {
            $existingTrData = $query->getrow();
            $trId = $existingTrData['id'];
        }
    }

    // if transaction doesn't exist for this order, we create one,
    // or set existing to the class otherwise

    /** @var Transaction $transaction */
    $transaction = loadLibClass('transaction');

    $transaction->reInitClass();

    if(!empty($trId)) {

        // transaction exists, so just set it and update with given data
        $transaction->setTransaction($trId);
        $transaction->updateTransaction($trId, $trData);

    } else {

        // no transaction for given order ID, so create it from given data
        $trId = $transaction->createTransaction($trData);
    }

    // if we have transaction ID we update order record

    if($trId) {

        $dbQuery = "UPDATE mod_orders SET transaction_id = $trId WHERE id = " . $trData['order_id'];
        doQuery($mdb, $dbQuery);
    }

    // return transaction ID

    return $trId;
}

function getProfileLang($id)
{
    global $mdb;

    $dbQuery = "SELECT lang
							FROM mod_profiles
							WHERE 1
								AND `id` = '" . mres($id) . "'
								AND `deleted` = 0
							LIMIT 1";
    $query = new query($mdb, $dbQuery);

    $lang = $query->getOne();

    if (empty($lang)) {
        return false;
    }

    return $lang;
}