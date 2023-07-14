<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2019-2020, BlueBridge.
 */

/**
 * Class reservation
 */
class reservation
{
    /** @var config  */
    private $cfg;
    /** @var null | int */
    private $id = null;
    /** @var array  */
    private $reservation = array();
    private $table = null;

    /**
     * reservation constructor.
     * @param null $id
     */
    public function __construct($id = null)
    {
        $this->cfg = loadLibClass('config');
        $this->table = $this->cfg->getDbTable('reservations', 'self');

        if($id) {
            $reservation = $this->getReservationById($id);
            if($reservation) {
                $this->id = $id;
                $this->reservation = $reservation;
            }
        }
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function createReservation($data)
    {
        if($data && is_array($data)) {

            $id = saveValuesInDb($this->table, $data);

            $this->id = $id;
            $this->reservation = $this->getReservationById($id);

            return $id;
        }

        return false;
    }

    /**
     * @param $id
     * @param $data
     * @return bool|string
     */
    public function updateReservation($id, $data)
    {
        saveValuesInDb($this->table, $data, $id);
        $this->reservation = $this->getReservationById($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function setReservation($id)
    {
        $reservation = $this->getReservationById($id);

        if($reservation) {
            $this->id = $id;
            $this->reservation = $reservation;
            return true;
        }

        return false;
    }

    /**
     * @return array|bool|int
     */
    public function getReservation()
    {
        if($this->id) {
            return $this->reservation;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getReservationId()
    {
        return $this->id;
    }

    /**
     * @param $orderId
     * @return array|int|null
     */
    public function setReservationByOrderId($orderId)
    {
        if($orderId) {
            $dbQuery = "SELECT * FROM " . $this->table . " WHERE order_id = " . $orderId;
            $query = new query($this->cfg->db, $dbQuery);

            $reservation = null;

            if($query->num_rows()) {

                $reservation = $query->getrow();
                $reservation['options'] = array();

                $optDbQuery = "SELECT * FROM mod_reservation_options WHERE reservation_id = " . $reservation['id'];
                $optQuery = new query($this->cfg->db, $optDbQuery);

                if($optQuery->num_rows()) {

                    $opts = $optQuery->getrow();

                    if(!empty($opts) && !empty($opts['options'])) {

                        $optsArray = json_decode($opts['options'], true);

                        if(!empty($optsArray) && is_array($optsArray)) {
                            $reservation['options'] = $optsArray;
                        }
                    }
                }
            }

            if($reservation) {
                $this->id = $reservation['id'];
                $this->reservation = $reservation;

                return true;
            }
        }

        return false;
    }

    /**
     * @param $id
     * @return array|bool|int
     */
    private function getReservationById($id)
    {
        if($id) {
            $dbQuery = "SELECT * FROM " . $this->table . " WHERE 1 AND id = " . $id;
            $query = new query($this->cfg->db, $dbQuery);

            if($query->num_rows()) {

                $res = $query->getrow();
                $res['options'] = array();

                $optDbQuery = "SELECT * FROM mod_reservation_options WHERE reservation_id = " . $id;
                $optQuery = new query($this->cfg->db, $optDbQuery);

                if($optQuery->num_rows()) {

                    $opts = $optQuery->getrow();

                    if(!empty($opts) && !empty($opts['options'])) {

                        $optsArray = json_decode($opts['options'], true);

                        if(!empty($optsArray) && is_array($optsArray)) {
                            $res['options'] = $optsArray;
                        }
                    }
                }

                return $res;
            }
        }

        return false;
    }

    /**
     * @param $status
     * @param null $reason
     */
    public function setStatus($status, $reason = null)
    {
        $canceled = '';
        if(in_array($status, array(RESERVATION_ABORTED_BY_USER, RESERVATION_ABORTED_BY_SM))) {
            $canceled = $status == RESERVATION_ABORTED_BY_USER ? " cancelled_by = 'profile'," : " cancelled_by = 'hsp',";
            $canceled .= " cancelled_at = '" . date(PIEARSTA_DT_FORMAT) . "',";
        }

        $dbQuery = "UPDATE " . $this->table . "
                    SET status = " . $status . ",
                        status_changed_at = '" . time() . "',
                        updated = '" . time() . "',
                        " . $canceled . "
                        sended = 0,
                        status_reason = '" . $reason . "'
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->reservation['status'];
    }

    /**
     * delete current reservation
     */
    public function deleteReservation($reason = null)
    {
        $reservationId = $this->id;
        $doctorId = $this->reservation['doctor_id'];
        $hspReservationId = $this->reservation['hsp_reservation_id'];
        $clinicId = $this->reservation['clinic_id'];

        // if non-consistent data set in current object we just return
        // to avoid errors in attempt of uncomplete data processing

        if(!$reservationId || !$doctorId || !$clinicId) {
            return true;
        }

        $terminalId = null;
        $hspDoctorId = null;

        if($reason == '@/deletedByGoogleSync') {
            $this->setStatus(RESERVATION_ABORTED_BY_USER, $reason);
            return true;
        }

        if(!$hspReservationId) {
            deleteFromDbById($this->table, $this->id);
            $this->freeReservationObject();
            $this->deleteReservationOptions($reservationId);
            return true;
        }

        // Sync request to SM -- delete_booking

        if($hspReservationId) {

            // First check if this is EGL reservation

            $clinic = getClinicById($this->reservation['clinic_id']);

            if($clinic['clinic_type'] == 'egl_queue') {

                $uidArr = explode('_', $this->reservation['res_uid']);
                $slotExtId = $uidArr[1];

                $data = array(
                    'soap_method' => 'AppointKill',
                    'UID' => $this->reservation['res_uid'],
                    'Res' => $slotExtId,
                );

                /** @var eglReservation $egl */
                $egl = loadLibClass('eglReservation');

                $res = $egl->deleteAppointment($data);

                if($res['success']) {

                    $this->setStatus(RESERVATION_ABORTED_BY_USER, $reason);

                    $return = array(
                        'deletedOnSm' => true,
                        '$hspReservationId' => $hspReservationId,
                        'freeSlots' => true,
                    );


                } else {

                    $return = array(
                        'deletedOnSm' => false,
                        '$hspReservationId' => $hspReservationId,
                        'freeSlots' => true,
                    );
                }

                if(DEBUG) {
                    $return['debugRequest'] = $res;
                }

                return $return;
            }

            // If this is not EGL reservation we proceed

            /** @var requestSm $requestSmClass */
            $requestSmClass = loadLibClass('requestSm', true, $clinicId);

            $dbQuery = "SELECT hsp_resource_id FROM " . $this->cfg->getDbTable('doctors', 'self') . "
                    WHERE 1 
                        AND id = " . $doctorId;
            $query = new query($this->cfg->db, $dbQuery);

            if($query->num_rows()) {
                $row = $query->getrow();
                $hspDoctorId = $row['hsp_resource_id'];
            }

            $dbQuery = "SELECT terminal_id FROM " . $this->cfg->getDbTable('clinics', 'self') . "
                    WHERE 1 
                        AND id = " . $clinicId;
            $query = new query($this->cfg->db, $dbQuery);

            if($query->num_rows()) {
                $row = $query->getrow();
                $terminalId = $row['terminal_id'];
            }

            $data = array(
                'hsp_doctor_id' => $hspDoctorId,
                'clinic_id' => $terminalId,
                'reservation_id' => $reservationId,
                'hsp_reservation_id' => $hspReservationId,
                'datetime_from' => $this->reservation['start'],
                'datetime_thru' => $this->reservation['end'],
            );

            $res = $requestSmClass->requestSm('deleteReservation', $data);

            if($res && $res['success']) {

                $xml = $res['result'];
                /** @var xml $xmlClass */
                $xmlClass = loadLibClass('xml');

                /** @var array $arr */
                try {
                    $arr = XML2Array::createArray($xml);
                } catch (Exception $e) {
                    if(DEBUG) {
                        $arr = array(
                            'errCode' => $e->getCode(),
                            'errMessage' => $e->getMessage(),
                        );
                    } else {
                        $arr = array();
                    }
                }

                $resRes = $arr;

                if($resRes['response']['statuss']['@attributes']['error'] >= 1) {

                    // record has been deleted on sm so we delete it from our db
                    //deleteFromDbById($this->table, $this->id);
                    //$this->id = null;
                    //$this->reservation = array();

                    $this->setStatus(RESERVATION_ABORTED_BY_USER, $reason);

                    $return = array(
                        'deletedOnSm' => true,
                        '$hspReservationId' => $hspReservationId,
                        'error' => $res['response']['statuss']['@attributes']['error'],
                        'freeSlots' => isset($res['response']['free_slots']) ? $res['response']['free_slots'] == 1 : true,
                        'res' => $resRes,
                    );

                    if(DEBUG) {
                        $return['debugRequest'] = $res;
                    }

                    return $return;
                }
            }

            $reason = $reason ? $reason : '@/toBeDeleted';

            // record delete on sm failed and we set status to canceled with reason to be deleted on sm
            $this->setStatus(RESERVATION_ABORTED_BY_USER, $reason);

            return $res;
        }

        /** @var profileData $pc */
        $pc = &loadLibClass('profileData');
        $pc->getReservationsCount();
    }

    /**
     * @param $data
     * @param null $id
     * @return bool|int|mixed|string
     */
    public function saveReservationOptions($data, $id = null) {

        $resId = $id ? $id : $this->id;

        if(!$resId) {
            $resId = !empty($data['reservation_id']) ? $data['reservation_id'] : null;
        }

        if(!$resId) {
            return false;
        }

        if(empty($data['options']) || !is_array($data['options'])) {
            return false;
        }

        // check if exists

        $optsId = null;

        $optDbQuery = "SELECT * FROM mod_reservation_options WHERE reservation_id = " . $resId;
        $optQuery = new query($this->cfg->db, $optDbQuery);

        if($optQuery->num_rows()) {

            $opts = $optQuery->getrow();
            $optsId = $opts['id'];
        }

        $resOptData = array(
            'reservation_id' => $resId,
            'options' => json_encode($data['options']),
        );

        if($optsId) {
            saveValuesInDb('mod_reservation_options', $resOptData, $optsId);

            return $optsId;
        }

        $newId = saveValuesInDb('mod_reservation_options', $resOptData);

        return $newId;
    }

    public function deleteReservationOptions($id) {

        if(!$id) {
            return false;
        }

        $dbQuery = "DELETE FROM mod_reservation_options WHERE reservation_id = " . mres($id);
        doQuery($this->cfg->db, $dbQuery);

        return true;
    }

    public function freeReservationObject()
    {
        $this->id = null;
        $this->reservation = array();
    }

    /**
     * @param $sheduleId
     * @return bool
     */
    public function isSlotAlreadyBooked($sheduleId)
    {
        // get time from slot
        $slQuery = "SELECT * FROM mod_shedules WHERE id = " . mres($sheduleId);
        $slq = new query($this->cfg->db, $slQuery);
        $slot = $slq->getrow();

        $alreadyBooked = $slot['booked'] == 1;
        $alreadyLocked = $slot['locked'] == 1;

        if($alreadyLocked || $alreadyBooked) {
            return true;
        }

        $lockCheckQuery = "SELECT r.id 
                                FROM `" . $this->cfg->getDbTable('reservations', 'self') . "` AS r
                                WHERE
                                    r.clinic_id = '" . $slot['clinic_id'] . "' AND
                                    r.doctor_id = '" . $slot['doctor_id'] . "' AND
                                    r.start > now() AND
                                    r.status_reason <> '@/toBeDeleted' AND
                                    r.cancelled_at IS NOT NULL AND
                                    r.status IN ( 0, 2, 4, 5 ) AND
                                    (
                                        ( r.start >= '" . $slot['start_time'] . "' AND r.start < '" . $slot['end_time'] . "' ) OR
                                        ( r.end <= '" . $slot['end_time'] . "' AND r.end > '" . $slot['start_time'] . "' ) OR
                                        (r.start >= '" . $slot['start_time'] . "' AND r.end <= '" . $slot['end_time'] . "')
                                    )";

        $query = new query($this->cfg->db, $lockCheckQuery);

        return $query->num_rows() > 0;
    }

    /**
     * @param $sheduleId
     * @param $dc
     * @param $serviceId
     * @return array|int|null
     */
    public function getTimeSlotRelatedInfo($sheduleId, $dc = null, $serviceId = null, $dcServicesList = null)
    {

        /** @var serviceDetails $serviceDetailsClass */
        $serviceDetailsClass = loadLibClass('serviceDetails');

        $currDate = date('Y-m-d H:i:s', time());
        $network = !empty($_SESSION['user']['dcSubscription']['product_network']) ? $_SESSION['user']['dcSubscription']['product_network'] : null;

        $selNetw = ", NULL as network ";
        $joinNetw = "";

        if($network) {
            $selNetw = ", c2n.network_id as network ";
            $joinNetw = " LEFT JOIN ins_clinic_to_networks c2n ON (c2n.clinic_id = c.id AND c2n.network_id = $network AND c2n.start_datetime <= '$currDate' AND c2n.end_datetime > '$currDate') ";
        }

    	$dbQuery = "SELECT
    					s.*,
		    			di.name,
		    			di.surname,
		    			c.id as clinic_id,
		    			c.name as clinic_name,
                        c.additional_data as clinicAdditionalData,
                        cc.phone as clinic_phone,
                        cc.email as clinic_email,
		    			ci.address as clinic_address,
    					c.url as clinic_url,
    					c.payments_enabled as payments_enabled,
		    			d.url as doctor_url,
		    			d.hsp_resource_id as hsp_doctor_id
                        $selNetw
							FROM `" . $this->cfg->getDbTable('shedule', 'self')	 . "` s
    							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self')	 . "` d ON (d.id = s.doctor_id)
    							LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info')	 . "` di ON (d.id = di.doctor_id AND di.lang = '" . getDefaultLang() . "')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self')	 . "` c ON (c.id = s.clinic_id)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info')	 . "` ci ON (c.id = ci.clinic_id AND ci.lang = '" . getLang() . "')		
                                LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts')	 . "` cc ON (c.id = cc.clinic_id AND cc.default = 1)			
                                $joinNetw 	
							WHERE 1
								AND s.id = '" . mres($sheduleId) . "'
								AND (s.booked IS NULL OR s.booked = 0)
								AND s.start_time > '" . date('Y-m-d H:i:s') . "'";
    	$query = new query($this->cfg->db, $dbQuery);

    	$currentInterval = null;

    	if ($query->num_rows()) {

            $row = $query->getrow();

            // clinic additional data

            $row['clinicAdditionalData'] = $row['clinicAdditionalData'] ? json_decode($row['clinicAdditionalData'], true) : null;

            //
            $currentInterval = $row['interval'];

            $availableTimeArray = getAvailableTime($row['clinic_id'], $row['doctor_id'], $row['start_time']);
            $row['availableSlots'] = $availableTimeArray['availableSlots'];
            $availableTime = $availableTimeArray['availableTime'];

            $row['start_time'] = strtotime($row['start_time']);
            $row['start_time_date_month'] = date('d. ', $row['start_time']) . gL("month_" . date('F', $row['start_time']));

            $row['services'] = array();

            // HERE we collect services info
            $allServices = $serviceDetailsClass->getServices($row['clinic_id'], $row['doctor_id'], date(PIEARSTA_DT_FORMAT, $row['start_time']), $dc, $serviceId, $dcServicesList);

            // we show only services with duration <= available time
            foreach ($allServices as $key => $value) {

                if(!$value['length_minutes'] || $value['length_minutes'] == 0) {
                    $value['length_minutes'] = $currentInterval;
                }

                if(intval($value['length_minutes']) <= $availableTime) {
                    $row['services'][] = $value;
                }
            }

            return $row;
        }

    	return null;
    }

    /**
     * @param $slotData
     * @param $userId
     * @return bool
     */
    public function doesAnotherReservationExist($slotData, $userId)
    {
        $anotherPayReservationQuery =   "SELECT * 
                                             FROM ".$this->cfg->getDbTable('reservations', 'self')."
                                             WHERE
                                                start >= '".date('Y-m-d',$slotData['start_time'])." 00:00:00' AND
                                                end <= '".date('Y-m-d',$slotData['start_time'])." 23:59:59' AND
                                                doctor_id = ".$slotData['doctor_id']." AND 
                                                clinic_id = ".$slotData['clinic_id']." AND 
                                                profile_id = ".$userId." AND
                                                status IN ( 0, 2, 5 )
                                            ";
        $anotherQuery = new query($this->cfg->db, $anotherPayReservationQuery);

        return $anotherQuery->num_rows() > 0;
    }

}

?>
