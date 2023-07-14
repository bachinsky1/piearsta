#!/usr/bin/php-cgi
<?php

// Check 'hanging' reservations (created more than 24 h ago and not containing hsp_reservation_id, that means that no data exchange with clinic took place)

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");


/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

global $mdb;

$hours_waiting_for_hsp_reservation_id = $cfg->get('hours_waiting_for_hsp_reservation_id');
$hours_max_to_analyze_hanging_reservations = $cfg->get('hours_max_to_analyze_hanging_reservations');

$time = time() - ($hours_waiting_for_hsp_reservation_id*60*60);
$time2 = time() - ($hours_max_to_analyze_hanging_reservations*60*60);

$dbQuery = "SELECT id FROM mod_reservations
            WHERE
                created < $time AND
                created >= $time2 AND
                status IN (0,2) AND
                hsp_reservation_id IS NULL AND 
                no_hsp_message_sent = '0'";

$query = new query($mdb, $dbQuery);

$count = $query->num_rows();

if($count) {

    /** @var Mails $mailsClass */
    $mailsClass = loadLibClass('mails');

    while ($res = $query->getrow()) {

        $resId = $res['id'];
        $row = $mailsClass->getReservationData(array('reservationId' => $resId));

        $lang = getProfileLang($row['profile_id']);

        $subject = $cfg->getData('resMailSubject_NoHspReservation/' . $lang);

        $resLink = $cfg->get('cron_piearstaUrl') . $lang . '/profils/mani-pieraksti/?openRes=' . $resId;

        $qrParams = array(
            'chs' => '300x300',
            'cht' => 'qr',
            'choe' => 'UTF-8',
            'chl' => $resLink,
        );

        $qrSrc = 'https://chart.googleapis.com/chart?' . http_build_query($qrParams);

        // Send notification

        $isPaidSlot = false;

        if ($row['payment_type'] == 1) {
            $message = gL('profile_reservation_payment_type_country_info_text1', '', $lang);
        } elseif ($row['payment_type'] == 2) {
            $isPaidSlot = true;
            $message = gL('profile_reservation_payment_type_pay_info_text1', '', $lang);
        } else {
            $isPaidSlot = true;
            $message = gL('profile_reservation_payment_type_country_pay_info_text1', '', $lang);
        }

        $template = $isPaidSlot ? $cfg->getData('resMailBody_NoHspReservation_paid/' . $lang) : $cfg->getData('resMailBody_NoHspReservation_free/' . $lang);

        $row['clinic_citytitle'] = isset($row['clinic_citytitle']) && $row['clinic_citytitle'] ? $row['clinic_citytitle'] . ', ' : '';

        $row['clinic_address'] = $row['clinic_address'] ?
            $row['clinic_citytitle'] . $row['clinic_address'] :
            '';

        $from = 'pieraksti@piearsta.lv';
        $to = '';
        $usrData = null;

        // keys we use in template ...
        $keys = array(
            '{status}',
            '{start_time}',
            '{service_name}',
            '{doctor_name}',
            '{clinic_name}',
            '{clinic_address}',
            '{clinic_phone}',
            '{clinic_email}',
            '{notice}',
            '{message}',
            '{qr_src}',
            '{res_link}',
        );

        // ... and their values
        // get them as in profile.class openReservation method
        //

        $status = $row['status'] == 4 ? $row['status_before_archive'] : $row['status'];

        $values = array(
            gL('profile_reservation_status_' . $status, '', $lang), // {status}
            date("d.m.Y H:i", strtotime($row['start'])), //{start_time}
            $row['title'], // {service_name}
            $row['name'] . ' ' . $row['surname'], // {doctor_name}
            $row['clinic_name'], // {clinic_name}
            $row['clinic_address'], // {clinic_address}
            $row['clinic_phone'], // {clinic_phone}
            $row['clinic_email'], // {clinic_email}
            $row['notice'], // {notice}
            $message, // {message}
            $qrSrc, // qr code for reservation link
            $resLink, // reservation link
        );

        $body = str_replace($keys, $values, $template);

        // get user data
        $usrQuery = "SELECT email FROM mod_profiles
                        WHERE
                            id = " . $row['profile_id'] . " AND
                            enable = 1 AND 
                            deleted = 0";
        $usrQ = new query($mdb, $usrQuery);

        if($usrQ->num_rows()) {

            $usrData = $usrQ->getrow();
            $to = $usrData['email'];

            sendMail($to, $subject, $body, array(), $from);
            addMessageToProfile($row['profile_id'], $subject, $body);

            // add reservation id to update list
            $resIds[] = $row['id'];

            $dbUpdQuery = "UPDATE mod_reservations 
                            SET no_hsp_message_sent = '1' 
                            WHERE id = $resId";
            doQuery($mdb, $dbUpdQuery);
        }
    }
}

if($debug) {
    echo $count . ' "hanging" rereservations  found.' . PHP_EOL;
    echo 'Cron finished' . PHP_EOL;
}

exit;

?>