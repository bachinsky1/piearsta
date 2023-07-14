#!/usr/bin/php-cgi
<?php

define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

/**
 * This cron intended to get event records from doctors google calendars
 * and to implement sync with piearsta reservations
 */

$localDebug = true;

define('APP_ROOT', dirname(__FILE__) . '/..');

// Bootstrap Piearsta.lv framework
require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

/** @var array $gApiCfg */
$gApiCfg = $cfg->get('g_api');
$gApiSecret = $gApiCfg['secret'];

$debug = DEBUG;

// Init vendor libs (google api client, etc ...)
require __DIR__ . '/../vendor/autoload.php';

/** @var googleApi $gApi */
$gApi = loadLibClass('googleApi');


// get doctors, which are set up to sync google calendars

$dbQuery = "SELECT g.*, c.max_d as clinic_max_d, d.max_d as doctor_max_d FROM mod_google_access_tokens g 
            LEFT JOIN mod_doctors d ON (d.id = g.doctor_id) 
            LEFT JOIN mod_clinics c ON (c.id = g.clinic_id) 
            LEFT JOIN mod_doctors_to_clinics d2c ON (d2c.d_id = d.id) 
            WHERE
                g.token <> '' AND 
                g.token IS NOT NULL AND
                d.deleted = 0 AND 
                d.enabled = 1";

$query = new query($mdb, $dbQuery);

$docArr = array();

if($query->num_rows()) {

    $docArr = $query->getArray();

    foreach ($docArr as $doc) {

        $doc['clinic_max_d'] = !empty($doc['clinic_max_d']) ? intval($doc['clinic_max_d']) : null;
        $doc['doctor_max_d'] = !empty($doc['doctor_max_d']) ? intval($doc['doctor_max_d']) : null;
        $maxD = $doc['doctor_max_d'] ? $doc['doctor_max_d'] : ($doc['clinic_max_d'] ? $doc['clinic_max_d'] : 90);

        $start = date('Y-m-d\\TH:i:s\\Z', time());
        $end = date('Y-m-d\\TH:i:s\\Z', time() + ($maxD * 24 * 60 * 60) );

        $events = array();

        // init client with doctor token
        $token = $gApi->getDoctorsApiToken($doc['clinic_id'], $doc['doctor_id']);

        if($token && isValidJson($token)) {

            // get all events for time range, including deleted

            /** @var array $events */
            $events = $gApi->getEvents($start, $end, true);

            // process events if any
            if(is_array($events) && count($events) > 0) {

                $cancelledEvents = findByField($events, 'status', 'cancelled');
                $activeEvents = findByField($events, 'status', 'cancelled', true);

                if($debug) {
                    echo PHP_EOL . 'Reservation events found: ' . count($events) . PHP_EOL;
                    echo '   active: ' . count($activeEvents) . PHP_EOL;
                    echo '   cancelled: ' . count($cancelledEvents) . PHP_EOL . PHP_EOL;
                }

                /** @var reservation $res */
                $res = loadLibClass('reservation');

                $currDate = null;
                $nextDate = null;

                foreach ($events as $key => $ev) {

                    // get dates

                    $currDate = dateFromDatetime($ev['start']);

                    if($key == (count($events) - 1)) {
                        $nextDate = null;
                    } else {
                        $nextDate = dateFromDatetime($events[$key + 1]['start']);
                    }

                    // process event

                    if($ev['status'] == 'cancelled') {

                        // if cancelled event has related piearsta reservation, we call deleteReservation
                        // to set it's status to cancelled with special reason

                        if(isset($ev['extendedProps']) && !empty($ev['extendedProps']['reservationId'])) {

                            $res->setReservation($ev['extendedProps']['reservationId']);

                            $currRes = $res->getReservation();

                            if($currRes['id'] && $currRes['start'] && $currRes['end'] && !in_array($currRes['status'], array(1, 3))) {

                                if($debug) {
                                    echo 'Delete reservation: ' . $ev['summary'] . ' (' . $ev['start'] . ')' . PHP_EOL;
                                }

                                $res->deleteReservation('@/deletedByGoogleSync');

                                // send email to user
                                $reservation = createResArray($currRes['id'], $currRes['profile_id'], 'lv');
                                sendReservationEmail($reservation, '1');
                            }
                        }

                    } else {

                        // if active event has no related piearsta reservation,
                        // we create it with notice and status reason

                        if(!isset($ev['extendedProps']) || !isset($ev['extendedProps']['reservationId'])) {

                            if($debug) {
                                echo 'Create reservation: ' . $ev['summary'] . ' (' . $ev['start'] . ')' . PHP_EOL;
                            }

                            // create reservation
                            $res->freeReservationObject();

                            $notice = (empty($ev['summary']) && empty($ev['description'])) ?
                                gL('gapi_notice_imported', 'Imported from doctor\'s google calendar', 'lv') :
                                '';

                            if(!empty($ev['summary'])) {
                                $notice .= gL('gapi_summary_label', 'Summary', 'lv') . ': ' . $ev['summary'];
                            }

                            if(!empty($ev['description'])) {
                                $notice .= !empty($ev['summary']) ? ('; ' . PHP_EOL) : '';
                                $notice .= gL('gapi_description_label', 'Description', 'lv') . ': ' . $ev['description'];
                            }

                            $data = array(
                                'status' => '0',
                                'status_changed_at' => time(),
                                'status_reason' => '@/createdFromGoogleEvent',
                                'status_before_archive' => '0',
                                'created' => time(),
                                'clinic_id' => $doc['clinic_id'],
                                'doctor_id' => $doc['doctor_id'],
                                'notice' => $notice,
                                'start' => $ev['start'],
                                'end' => $ev['end'],
                                'google_calendar_id' => $ev['eventId'],
                            );

                            $resId = $res->createReservation($data);

                            if($resId) {

                                bookSlotsByTimeRange($doc['clinic_id'], $doc['doctor_id'], $ev['start'], $ev['end']);

                                // update google event with some piearsta reservation info

                                /** @var Google_Service_Calendar_Event $eventObj */
                                $eventObj = $gApi->getService()->events->get($gApi->getCalendarId(), $ev['eventId']);

                                /** @var Google_Service_Calendar_EventExtendedProperties $extProps */
                                $extProps = new Google_Service_Calendar_EventExtendedProperties();
                                $extProps->setPrivate(array(
                                        'reservationId' => $resId,
                                ));

                                $eventObj->setExtendedProperties($extProps);
                                $updatedEvent = $gApi->getService()->events->update($gApi->getCalendarId(), $ev['eventId'], $eventObj);
                            }

                        } else {

                            $res->setReservation($ev['extendedProps']['reservationId']);
                            $resData = $res->getReservation();

                            // if reservation event was restored from recycle bin -- we need to restore cancelled reservation on piearsta

                            if($resData['status'] == 3 && $resData['status_reason'] == '@/deletedByGoogleSync') {

                                if($debug) {
                                    echo 'Restore reservation: ' . $ev['summary'] . ' (' . $ev['start'] . ')' . PHP_EOL;
                                }

                                $restoreData = array(
                                    'status' => '0',
                                    'status_reason' => $resData['profile_id'] ? 'null' : '@/createdFromGoogleEvent',
                                    'status_before_archive' => '0',
                                    'status_changed_at' => time(),
                                    'updated' => time(),
                                    'cancelled_at' => 'null',
                                    'cancelled_by' => 'null',
                                    'sended' => '0',
                                );

                                $res->updateReservation($res->getReservationId(), $restoreData);
                            }
                        }
                    }


                    // if nextDate differs from current
                    // we freeSlots for current date
                    // and book slots based on existing reservations

                    if(
                        ( $nextDate && $nextDate != $currDate ) ||
                        !$nextDate
                    ) {
                        freeSlots($currDate, $doc['clinic_id'], $doc['doctor_id']);
                        refreshDaySlots($currDate, $doc['clinic_id'], $doc['doctor_id']);
                    }
                }
            } else {

                if($debug) {
                    echo PHP_EOL . '0 reservation events found.' . PHP_EOL;
                }
            }
        } else {

            if($debug) {
                echo PHP_EOL . 'Wrong api token.' . PHP_EOL;
            }
        }
    }
} else {

    if($debug) {
        echo PHP_EOL . 'No doctors found.' . PHP_EOL;
    }
}

if($debug) {
    echo '' . PHP_EOL;
    echo 'Script finished.' . PHP_EOL;
}

exit;

?>