<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2020, BlueBridge.
 */

/**
 * Class googleApi
 */
class googleApi
{
    /** @var config  */
    private $cfg;

    /** @var Google_Client | null */
    private $client = null;

    /** @var Google_Service_Calendar null  */
    private $service = null;

    /** @var array  */
    private $doctorTokenData = array();

    /** @var null | int */
    private $calendarId = null;

    /**
     * googleApi constructor.
     * @param $stateData
     * @throws Google_Exception
     */
    public function __construct()
    {
        $this->cfg = loadLibClass('config');

        $this->client = new Google_Client();

        $this->client->setApplicationName('Piearsta.lv');
        $this->client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);
        $this->client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        $this->client->setAuthConfig(APP_ROOT . '/credentials.json');
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    /**
     * @param $stateData
     * @return string
     * @throws Google_Exception
     */
    public function getNewAuthUrl($stateData)
    {
        $this->client->setState( base64_encode( json_encode($stateData) ) );

        if(!empty($stateData['doctorData']['notify_email'])) {
            $this->client->setLoginHint($stateData['doctorData']['notify_email']);
        }

        return $this->client->createAuthUrl();
    }

    /**
     * @param $url
     * @param $name
     * @return string
     */
    public function getShortenUrl($url, $name)
    {
        // shorten url
        $cuttlyApiKey = $this->cfg->get('url_shortener_api_key');
        $name = 'piearsta_' . $name;
        $json = file_get_contents('https://cutt.ly/api/api.php?key='.$cuttlyApiKey.'&short='.urlencode($url).'&name='.$name);
        $data = json_decode($json, true);

        if($data['url']['status'] == 7 && !empty($data['url']['shortLink'])) {
            return $data['url']['shortLink'];
        }

        return '';
    }

    /**
     * @return Google_Client|null
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Google_Service_Calendar
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param $clinicId
     * @param $doctorId
     * @return bool | string
     */
    public function getDoctorsApiToken($clinicId, $doctorId)
    {
        $dbQuery = "SELECT * FROM mod_google_access_tokens 
                    WHERE
                        clinic_id = " . mres($clinicId) . " AND 
                        doctor_id = " . mres($doctorId);
        $query = new query($this->cfg->db, $dbQuery);

        if($query->num_rows()) {

            $row = $query->getrow();

            if(!empty($row['token'])) {

                if(!$this->setToken($row['token'], $clinicId, $doctorId)) {
                    // error occurred and token is wrong
                    // we need report piearsta support to setup doctor again
                    //
                    // TODO: Send email to our support
                    //

                    return false;
                }

                $this->service = new Google_Service_Calendar($this->client);

                $this->calendarId = $calendar = 'primary';

                if($row['calendar_title'] != 'primary') {

                    // fetch doctor's calendars
                    $calendars = $this->service->calendarList->listCalendarList();

                    /** @var Google_Service_Calendar_CalendarListEntry $calItem */
                    $calItem = new Google_Service_Calendar_CalendarListEntry();

                    foreach ($calendars as $key => $calItem) {
                        if($calItem->getSummary() == $row['calendar_title']) {
                            $calendar = $row['calendar_title'];
                            $this->calendarId = $calItem->getId();
                            break;
                        }
                    }
                }

                $this->doctorTokenData = array(
                    'clinicId' => $clinicId,
                    'doctorId' => $doctorId,
                    'token' => json_decode($row['token']),
                    'calendarTitle' => $calendar,
                );

                return $row['token'];
            }
        }

        $this->doctorTokenData = array();
        return false;
    }

    /**
     * @return int|null
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * @param null $token
     * @param null $clinicId
     * @param null $doctorId
     * @return bool
     */
    public function setToken($token = null, $clinicId = null, $doctorId = null)
    {
        if(!$token) {
            return false;
        }

        $this->client->setAccessToken($token);

        // If there is no previous token or it's expired.
        if ($this->client->isAccessTokenExpired()) {

            // Refresh the token if possible, else fetch a new one.
            if ($this->client->getRefreshToken()) {

                $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());

                // save new token to db
                if(is_array($newToken) && isset($newToken['access_token'])) {

                    $dbQuery = "UPDATE mod_google_access_tokens 
                                SET
                                    token = '" . json_encode($newToken) . "'
                                WHERE
                                    clinic_id = " . mres($clinicId) . " AND 
                                    doctor_id = " . mres($doctorId);
                    doQuery($this->cfg->db, $dbQuery);

                    return true;
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param $reservation
     * @return array|bool
     */
    public function createEvent($reservation)
    {
        $return = array();

        if(!$reservation) {
            $return['error'] = 'No reservation!';
            return $return;
        }

        // check if api instantiated and set up properly
        if(!$this->service || empty($this->doctorTokenData)) {

            $return['error'] = 'Unproper gapi setup!';
            $return['gapi'] = $this;
            return $return;
        }

        if($reservation['title'] && $reservation['pname'] && $reservation['psurname']) {

            $summary = "PIEARSTA.LV - " . $reservation['title'] . ' (' . $reservation['pname'] . ' ' . $reservation['psurname'] .  ')';

            $creatorPcode = !empty($reservation['pcode']) ? ', pk: ' . $reservation['pcode'] : ', pn: ' . $reservation['pnumber'];
            $patientPcode = !empty($reservation['ppcode']) ? ', pk: ' . $reservation['ppcode'] : ', pn: ' . $reservation['ppnumber'];

            $creator = $reservation['pname'] . ' ' . $reservation['psurname'] . $creatorPcode;

            $patient = !empty($reservation['profile_person_id']) ?
                ($reservation['ppname'] . ' ' . $reservation['ppsurname'] . $patientPcode) : $creator;

            $gender = !empty($reservation['profile_person_id']) ? $reservation['ppgender'] : $reservation['pgender'];
            $gender = gL('form_gender_' . trim($gender), $gender, 'lv');

            $birthDate = !empty($reservation['profile_person_id']) ? $reservation['ppdate_of_birth'] : $reservation['pdate_of_birth'];

            $labels = array(
                'creator' => gL('gapi_label_creator', 'Creator', 'lv'),
                'patient' => gL('gapi_label_patient', 'Patient', 'lv'),
                'gender' => gL('gapi_label_gender', 'Gender', 'lv'),
                'birth' => gL('gapi_label_birth', 'Birth', 'lv'),
                'service' => gL('gapi_label_service', 'Service', 'lv'),
                'paid' => gL('gapi_label_paid', 'Paid', 'lv'),
                'notice' => gL('gapi_label_notice', 'Patient notice', 'lv'),
            );

            $description  = $labels['creator'] . ": " . $creator . PHP_EOL;
            $description .= "-----------------------------------------" . PHP_EOL;
            $description .= "" . PHP_EOL;
            $description .= $labels['patient'] . ": " . $patient . PHP_EOL;
            $description .= "   " . $labels['gender'] . ": " . $gender . PHP_EOL;
            $description .= "   " . $labels['birth'] . ": " . $birthDate . PHP_EOL;
            $description .= "-----------------------------------------" . PHP_EOL;
            $description .= "" . PHP_EOL;
            $description .= $labels['service'] . ": " . $reservation['title'] . PHP_EOL;
            $description .= "-----------------------------------------" . PHP_EOL;

            if(!empty($reservation['service_price'])) {
                $description  .= $labels['paid'] . ": " . $reservation['service_price'] . ' EUR' . PHP_EOL;
                $description .= "-----------------------------------------" . PHP_EOL;
                $description .= "" . PHP_EOL;
            }

            $description .= $labels['notice'] . ":" . PHP_EOL;
            $description .= "" . $reservation['notice'] . PHP_EOL;
            $description .= "-----------------------------------------" . PHP_EOL;
            $description .= "Email: " . $reservation['email'] . PHP_EOL;

            if(!empty($reservation['p_phone'])) {
                $description .= "Tālr.: " . $reservation['p_phone'] . PHP_EOL;
            }


            $newEvent = new Google_Service_Calendar_Event(array(
                'summary' => $summary,
                'description' => $description,
                "extendedProperties" => array(
                    "private" => array(
                        "reservationId" => $reservation['id'],
                        "profile_id" => $reservation['profile_id'],
                        "name" => $reservation['pname'],
                        "surname" => $reservation['psurname'],
                        "profile_person_id" => $reservation['profile_person_id'],
                        "person_name" => $reservation['ppname'],
                        "person_surname" => $reservation['ppsurname'],
                        "service_id" => $reservation['service_id'],
                        "service_price" => $reservation['service_price'],
                        "service_title" => $reservation['title'],
                        "notice" => $reservation['notice'],
                        "email" => $reservation['email'],
                        "phone" => $reservation['p_phone'],
                    ),
                ),
                'end' => array(
                    'dateTime' => date('Y-m-d\\TH:i:s', strtotime($reservation['end'])),
                    'timeZone' => 'Europe/Riga'
                ),
                'start' => array(
                    'dateTime' => date('Y-m-d\\TH:i:s', strtotime($reservation['start'])),
                    'timeZone' => 'Europe/Riga'
                )
            ));

        } else {

            $summary = 'PIEARSTA.LV -- created from SmartMedical system';
            $description = 'No description available';


            $newEvent = new Google_Service_Calendar_Event(array(
                'summary' => $summary,
                'description' => $description,
                "extendedProperties" => array(
                    "private" => array(
                        "reservationId" => $reservation['id'],
                    ),
                ),
                'end' => array(
                    'dateTime' => date('Y-m-d\\TH:i:s', strtotime($reservation['end'])),
                    'timeZone' => 'Europe/Riga'
                ),
                'start' => array(
                    'dateTime' => date('Y-m-d\\TH:i:s', strtotime($reservation['start'])),
                    'timeZone' => 'Europe/Riga'
                )
            ));
        }

        $res = null;

        try {
            $res = $this->service->events->insert($this->calendarId, $newEvent);

            $return['res'] = array(
                'error' => false,
                'res' => $res,
                'newEvent' => $newEvent,
            );

        } catch (Google_Service_Exception $e) {
            //

            $return['res'] = array(
                'error' => true,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'newEvent' => $newEvent,
            );

            return $return;
        }


        if($res instanceof Google_Service_Calendar_Event) {

            $calId = $res->getId();

            $dbQuery = "UPDATE mod_reservations
                        SET
                            google_calendar_id = '" . mres($calId) . "' 
                        WHERE
                            id = " . mres($reservation['id']);
            doQuery($this->cfg->db, $dbQuery);

            $return['res']['eventCreated'] = true;
            $return['res']['calendarEventId'] = $calId;

            return $return;
        }

        return array(
            'error' => 'Unknown error occurred!',
            'res' => $res,
        );
    }


    // get all events from doctor's calendar for given date-time range

    /**
     * @param null|string $start
     * @param null|string $end
     * @return array
     */
    public function getEvents($start = null, $end = null, $showDeleted = false)
    {

        $opts = array(
            'orderBy' => 'startTime',
            'singleEvents' => true,
        );

        if($start) {
            $opts['timeMin'] = $start;
        }

        if($end) {
            $opts['timeMax'] = $end;
        }

        if($showDeleted) {
            $opts['showDeleted'] = true;
        }

        $result = array();

        $events = $this->service->events->listEvents($this->calendarId, $opts);

        if($events->count()) {

            /** @var Google_Service_Calendar_Event $eventObj */
            $eventObj = new Google_Service_Calendar_Event();

            foreach ($events->getItems() as $key => $eventObj) {

                $result[$key] = array(
                    'eventId' => $eventObj->getId(),
                    'status' => $eventObj->getStatus(),
                    'summary' => $eventObj->getSummary(),
                    'description' => $eventObj->getDescription(),
                    'start' => $eventObj->getStart()->getDateTime() ? date(PIEARSTA_DT_FORMAT, strtotime($eventObj->getStart()->getDateTime())) : null,
                    'end' => $eventObj->getEnd()->getDateTime() ? date(PIEARSTA_DT_FORMAT, strtotime($eventObj->getEnd()->getDateTime())) : null,
                    'startDate' => $eventObj->getStart()->getDate(),
                    'endDate' => $eventObj->getEnd()->getDate(),
                );

                if(
                    ($result[$key]['start'] == null && $result[$key]['end'] == null) &&
                    ($result[$key]['startDate'] && $result[$key]['endDate'])
                ) {
                    $result[$key]['fullDayEvent'] = true;
                    $result[$key]['start'] = $result[$key]['startDate'] . ' 00:01:00';
                    $result[$key]['end'] = $result[$key]['startDate'] . ' 23:59:00';
                }

                if($eventObj->getExtendedProperties()) {
                    $result[$key]['extendedProps'] = $eventObj->getExtendedProperties()->getPrivate();
                }
            }
        }

        return $result;
    }

    /**
     * @param $eventId
     * @return bool
     */
    public function removeEvent($eventId)
    {
        if(!$eventId) {
            return false;
        }

        try {
            $this->service->events->delete($this->calendarId, $eventId);
        } catch (Google_Service_Exception $e) {
            //
            return false;
        }

        return true;
    }

}

?>