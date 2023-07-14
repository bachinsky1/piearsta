#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// Watchdog sends notifications about non-confirmed reservations

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

$debug = DEBUG;

// reminder timeouts from config
$reminderConfirmationTime = $cfg->get('reminder_confirmation_time');
$reminderWarnBefore = $cfg->get('reminder_warn_before');
$limit = $cfg->get('reservations_notifications_limit');


// 1. Collect reservation non confirmed in reminder_confirmation_time
// and less than reminder_warn_before time left until booked time

$where = '';

$maxConfirmationTime = time() - $reminderConfirmationTime;
$remindStartTime = null;

if($reminderWarnBefore) {
    $remindStartTime = date(PIEARSTA_DT_FORMAT, (time() + intval($reminderWarnBefore)));
    $where .= " AND r.start < '" . $remindStartTime . "'";
}

$lang = getDefaultLang(1);

$dbQuery =  "SELECT 
                    r.id,
                    r.hsp_reservation_id,
                    r.profile_id,
                    r.shedule_id,    			
                    r.start,
                    r.end,
                    r.profile_person_id,
                    r.doctor_id,
                    r.clinic_id,
                    r.status_reason,
                    r.status_changed_at,
                    r.created,
                    r.notice,
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
                    cld.title,
                    r.status,
                    r.status_before_archive,
                    r.payment_type,
                    pp.name as ppname,
                    pp.surname as ppsurname,
                    p.email,
                    dtcl.length_minutes	
            FROM " . $cfg->getDbTable('reservations', 'self') . " r
            LEFT JOIN `" . $cfg->getDbTable('doctors', 'self')	 . "` d ON (r.doctor_id = d.id)
            LEFT JOIN `" . $cfg->getDbTable('doctors', 'info')	 . "` di ON (d.id = di.doctor_id AND di.lang = '" . $lang . "')
            LEFT JOIN `" . $cfg->getDbTable('doctors', 'classificators')	 . "` dtcl ON (d.id = dtcl.d_id AND r.service_id = dtcl.cl_id)		
            LEFT JOIN `" . $cfg->getDbTable('clinics', 'self')	 . "` c ON (r.clinic_id = c.id)
            LEFT JOIN `" . $cfg->getDbTable('clinics', 'info')	 . "` ci ON (c.id = ci.clinic_id AND ci.lang = '" . $lang . "')
            LEFT JOIN `" . $cfg->getDbTable('clinics', 'contacts')	 . "` cc ON (cc.clinic_id = c.id AND cc.default = 1)
            LEFT JOIN `" . $cfg->getDbTable('classificators', 'details')	 . "` cld ON (r.service_id = cld.c_id)
            LEFT JOIN `" . $cfg->getDbTable('profiles', 'persons')	 . "` pp ON (r.profile_person_id = pp.id)
            LEFT JOIN `" . $cfg->getDbTable('profiles', 'self')	 . "` p ON (r.profile_id = p.id)
            WHERE
                r.profile_id IS NOT NULL AND
                (
                    r.status = 0 OR
                    (r.status = 4 AND r.status_before_archive = 0)
                ) AND
                r.confirmed_at IS NULL AND
                r.cancelled_at IS NULL AND
                r.notification_sent = 0 AND
                r.created <=  " . $maxConfirmationTime . " AND
                r.start > '" . date(PIEARSTA_DT_FORMAT, time()) . "' 
                " . $where . " 
            LIMIT " . $limit;

$query = new query($mdb, $dbQuery);

$resIds = array();

if($query->num_rows()) {

    $subject = $cfg->getData('noConfirmationSubject/lv');
    $template = $cfg->getData('noConfirmationBody/lv');

    if(DEBUG) {
        echo PHP_EOL;
        echo $query->num_rows() . " records found." . PHP_EOL;
        echo PHP_EOL;
    }

    while ($row = $query->getrow()) {

        // Send notification

        if ($row['payment_type'] == 1) {
            $message = gL('profile_reservation_payment_type_country_info_text1', '', 'lv');
        } elseif ($row['payment_type'] == 2) {
            $message = gL('profile_reservation_payment_type_pay_info_text1', '', 'lv');
        } else {
            $message = gL('profile_reservation_payment_type_country_pay_info_text1', '', 'lv');
        }

        $row['clinic_citytitle'] = isset($row['clinic_citytitle']) && $row['clinic_citytitle'] ? $row['clinic_citytitle'] . ', ' : '';

        $row['clinic_address'] = $row['clinic_address'] ?
            '<p>Adrese: ' . $row['clinic_citytitle'] . $row['clinic_address'] . '</p>' :
            '';

        $row['clinic_phone'] = $row['clinic_phone'] ?
            '<p>Tālrunis: ' . $row['clinic_phone'] . '</p>' :
            '';

        $row['clinic_email'] = $row['clinic_email'] ?
            '<p>E-pasts: ' . $row['clinic_email'] . '</p>' :
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
        );

        // ... and their values
        // get them as in profile.class openReservation method
        //

        $status = $row['status'] == 4 ? $row['status_before_archive'] : $row['status'];

        $values = array(
            gL('profile_reservation_status_' . $status, '', 'lv'), // {status}
            date("d.m.Y H:i", strtotime($row['start'])), //{start_time}
            $row['title'], // {service_name}
            $row['name'] . ' ' . $row['surname'], // {doctor_name}
            $row['clinic_name'], // {clinic_name}
            $row['clinic_address'], // {clinic_address}
            $row['clinic_phone'], // {clinic_phone}
            $row['clinic_email'], // {clinic_email}
            $row['notice'], // {notice}
            $message, // {message}
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
            addMessageToProfile($row['profile_id'], $subject, $body, $row['clinic_id']);

            // add reservation id to update list
            $resIds[] = $row['id'];
        }
    }

} else {

    if($debug) {
        echo PHP_EOL;
        echo "No records found." . PHP_EOL;
        echo PHP_EOL;
        echo 'Cron finished' . PHP_EOL;
        echo PHP_EOL;
    }
    exit;
}

// Set notification_sent to 1 for processed reservations

if(count($resIds) > 0) {

    $dbQuery = "UPDATE mod_reservations
            SET notification_sent = 1
            WHERE id IN (" . implode(',', $resIds) . ")";
    doQuery($mdb, $dbQuery);

}

// debug output
if($debug) {
    echo PHP_EOL;
    echo "Reservations processed:" . PHP_EOL;
    print_r($resIds);
    echo PHP_EOL;
    echo 'Cron finished' . PHP_EOL;
    echo PHP_EOL;
}

exit;

?>