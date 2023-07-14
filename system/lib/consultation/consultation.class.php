<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2020, BlueBridge.
 */

/**
 * Class consultation
 */
class consultation
{
    /** @var config  */
    private $cfg;
    /** @var null | int */
    private $id = null;
    /** @var array  */
    private $consultation = array();
    private $table = null;

    /**
     * consultation constructor.
     * @param null $id
     */
    public function __construct($id = null)
    {
        $this->cfg = loadLibClass('config');
        $this->mails = loadLibClass('mails');
        $this->table = $this->cfg->getDbTable('reservations', 'self');

        if($id) {
            $consultation = $this->getConsultationById($id);
            if($consultation) {
                $this->id = $id;
                $this->consultation = $consultation;
            }
        }
    }

    /**
     * @param $id
     * @param $data
     * @return bool|string
     */
    public function updateConsultation($id, $data)
    {
        saveValuesInDb($this->table, $data, $id);
        $this->consultation = $this->getConsultationById($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function setConsultation($id)
    {
        $consultation = $this->getConsultationById($id);

        if($consultation) {
            $this->id = $id;
            $this->consultation = $consultation;
            return true;
        }

        return false;
    }

    /**
     * @return array|bool|int
     */
    public function getConsultation()
    {
        if($this->id) {
            return $this->consultation;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getConsultationId()
    {
        return $this->id;
    }

    /**
     * @param $orderId
     * @return array|int|null
     */
    public function setConsultationByOrderId($orderId)
    {
        if($orderId) {
            $dbQuery = "SELECT * FROM " . $this->table . " WHERE order_id = " . $orderId;
            $query = new query($this->cfg->db, $dbQuery);

            $cons = null;

            if($query->num_rows()) {

                $cons = $query->getrow();
                $cons['options'] = array();

                $optDbQuery = "SELECT * FROM mod_reservation_options WHERE reservation_id = " . $cons['id'];
                $optQuery = new query($this->cfg->db, $optDbQuery);

                if($optQuery->num_rows()) {

                    $opts = $optQuery->getrow();

                    if(!empty($opts) && !empty($opts['options'])) {

                        $optsArray = json_decode($opts['options'], true);

                        if(!empty($optsArray) && is_array($optsArray)) {
                            $cons['options'] = $optsArray;
                        }
                    }
                }

                $this->id = $cons['id'];
                $this->consultation = $cons;

                return true;
            }
        }

        return false;
    }

    /**
     * @param $id
     * @return array|bool|int
     */
    private function getConsultationById($id)
    {
        if($id) {

            $dbQuery = "SELECT * FROM " . $this->table . " WHERE 1 AND id = " . $id;
            $query = new query($this->cfg->db, $dbQuery);

            if($query->num_rows()) {

                $cons = $query->getrow();
                $cons['options'] = array();

                $optDbQuery = "SELECT * FROM mod_reservation_options WHERE reservation_id = " . $id;
                $optQuery = new query($this->cfg->db, $optDbQuery);

                if($optQuery->num_rows()) {

                    $opts = $optQuery->getrow();

                    if(!empty($opts) && !empty($opts['options'])) {

                        $optsArray = json_decode($opts['options'], true);

                        if(!empty($optsArray) && is_array($optsArray)) {
                            $cons['options'] = $optsArray;
                        }
                    }
                }

                return $cons;
            }
        }
        return false;
    }


    /**
     * @param $consId
     * @return array
     */
    public function createVroom($consId)
    {

        // request to vroom ( konsultacijas )

        $this->setConsultation($consId);
        $consData = $this->getConsultation();

        $response = $this->changeVroom($consData, 'createVroom');

        return $response;
    }

    /**
     * @param $consId
     * @return mixed
     */
    public function confirmVroom($consId)
    {
        $this->setConsultation($consId);
        $consData = $this->getConsultation();

        if(empty($consData['doctor_id'])) {
            return false;
        }

        $data = array(
            'paReservationId' => $consId,
            'consultationStart' => $consData['start'],
            'consultationEnd' => $consData['end'],
        );

        /** @var Vroom $vroomObj */
        $vroomObj = loadLibClass('vroom');
        $response = $vroomObj->requestApi('confirmVroom', $data);
        $result = json_decode($response['result'], true);

        return $result;
    }

    /**
     * @param $consId
     * @param $status
     * @param string $reason
     * @return array|bool|float|int|mixed|stdClass|string|null
     */
    public function cancelVroom($consId, $status, $reason = 'Cancelled from PA')
    {
        $consData = array(
            'paReservationId' => $consId,
            'status' => $status,
            'reason' => $reason,
        );

        /** @var Vroom $vroomObj */
        $vroomObj = loadLibClass('vroom');
        $response = $vroomObj->requestApi('cancelVroom', $consData);
        $result = json_decode($response['result'], true);

        return array(
            'responseFromCons' => $response,
            'cancelVroom' => 'completed',
        );
    }

    /**
     * @param $consId
     * @return array
     */
    public function updateVroom($consId)
    {
        // make cons array like in create vroom (with doctor and patient data)
        // the consultation can be moved to another doctor and we should handle this

        $this->setConsultation($consId);
        $consData = $this->getConsultation();
        $result = $this->changeVroom($consData, 'updateVroom');

        return $result;
    }

    /**
     * $method could be 'createVroom' or 'updateVroom'
     *
     * @param $consData
     * @param string $method
     * @param null $newData
     * @return array
     */
    private function changeVroom($consData, $method = 'createVroom')
    {
        $consId = $consData['id'];
        $userData = getUserData($consData['profile_id']);
        $lang = !empty($userData['lang']) ? $userData['lang'] : 'lv';
        $consArray = createResArray($consId, $userData['id'], $lang);

        if(empty($consData) || empty($consData['doctor_id'])) {
            return [];
        }

        // service data

        $dbQuery = "SELECT ci.title 
                    FROM mod_classificators_info ci 
                    LEFT JOIN mod_service_details sd ON (
                            sd.service_id = ci.c_id AND 
                            IF(EXISTS(SELECT id FROM mod_classificators_info ci2 WHERE ci2.c_id = sd.service_id AND ci2.lang = '".$lang."'), ci.lang = '".$lang."', ci.lang = 'lv')
                        ) 
                    WHERE 
                        ci.c_id = " . $consData['service_id'] . " AND
                        sd.clinic_id = " . $consData['clinic_id'] . " AND 
                        sd.doctor_id = " . $consData['doctor_id'];

        $query = new query($this->cfg->db, $dbQuery);

        $serviceData = $query->getArray();

        $docData = getDoctorById($consData['doctor_id']);
        $clincData = getClinicById($consData['clinic_id']);

        $doctorsVroomsListStringId = base64_encode(
            json_encode(array(
                'clinicId' => $consArray['clinic_id'],
                'doctorId' => $consArray['doctor_id'],
                'userType' => 'doctor'
            ))
        );

        $data = array(
            'user' => $userData,
            'cons' => $consArray,
            'serviceData' => $serviceData,
            'doctorData' => $docData,
            'clinicData' => $clincData,
            'piearstaUrl' => $this->cfg->get('cron_piearstaUrl'),
            'consultation_vroom_list_doctor' => 'd_' . $doctorsVroomsListStringId,
        );

        /** @var Vroom $vroomObj */
        $vroomObj = loadLibClass('vroom');

        $response = $vroomObj->requestApi($method, $data);
        $result = json_decode($response['result'], true);

        // Log success or full response
        if (isset($response['success']) && $response['success'] && isset($result['VroomId'])) {
            $logData = array(
                'ConsultationId' => $this->id,
                'VroomId' => $result['VroomId'],
            );
            logDebug('RequestApi createVroom Success.' . json_encode(array($logData), JSON_PRETTY_PRINT));
        } else {
            logDebug('Not success or not set vroomid');
            logDebug('RequestApi createVroom response: ' . json_encode($response, JSON_PRETTY_PRINT));
        }

        if(isset($result['VroomId'])) {

            $stringId = base64_encode(
                json_encode(array(
                    'userId' => $consArray['profile_id'],
                    'clinicId' => $consArray['clinic_id'],
                    'doctorId' => $consArray['doctor_id'],
                    'vroomId' => $result['VroomId'],
                    'userType' => 'patient'
                ))
            );

            $doctorStringId = base64_encode(
                json_encode(array(
                    'userId' => $consArray['profile_id'],
                    'clinicId' => $consArray['clinic_id'],
                    'doctorId' => $consArray['doctor_id'],
                    'vroomId' => $result['VroomId'],
                    'userType' => 'doctor'
                ))
            );

            $resData = array(
                'consultation_vroom' => 'vr_' . $stringId,
                'consultation_vroom_doctor' => 'vr_' . $doctorStringId,
                'sended' => '0',
            );

            $this->updateConsultation($this->id, $resData);

            // Check if values was set in db
            if ($resData['consultation_vroom'] !== $this->consultation['consultation_vroom']
                || $resData['consultation_vroom_doctor'] !== $this->consultation['consultation_vroom_doctor']) {

                $logData = array(
                    'updateData' => $resData,
                    'consultationAfterUpdate' => $this->consultation,
                );
                logDebug('Consultation update vroom values dont match: ' . json_encode($logData, JSON_PRETTY_PRINT));
            }

            // Send email and message to patient
            $tempData = array(
                'reservationId' => $this->id,
                'vroom' => $result['vroomData'],
            );

            $params = array(
                'siteDataName' => 'mailPatientVroomCreated',
                'data' => $tempData,
                'sendMessageToPatient' => true,
                'lang' => $lang,
            );

            if (isset($userData['piearstaUrl']) && !empty($userData['piearstaUrl'])){
                $params['piearstaUrl'] = $userData['piearstaUrl'];
                unset($userData['piearstaUrl']);
            }

            $this->mails->send($params);

            // Send email to doctor
            $params = array(
                'siteDataName' => 'mailDoctorVroomCreated',
                'data' => $tempData,
            );

            $this->mails->send($params);
        }

        return $response;
    }

    /**
     * @param int $vroomId
     * @param int $userId
     * @return array $result
     *      string $redirectUrl
     */
    public function checkVroomIdAndGetRedirectUrl($vroomId, $userId) {

        $result = array(
            'redirectUrl' => '',
            'continue' => true,
            'systemError' => '',
            'systemData' => array(
                'vroomId' => $vroomId,
                'userId' => $userId,
                'decodeResult' => null,
            ),
        );

        // Decode (userId, clinicId, doctorId, vroomId, userType=patient|doctor)
        $vroomData = json_decode(base64_decode($vroomId), true);
        $result['systemData']['decodeResult'] = $vroomData;

        // Check user type, user id
        if ($result['continue'] && empty($vroomData)) {
            $result['systemError'] = 'Failed to decode vroomId';
            $result['continue'] = false;
        }

        if ($result['continue'] && $vroomData['userType'] !== 'patient') {
            $result['systemError'] = 'User type is not patient';
            $result['continue'] = false;
        }

        if ($result['continue'] && (int) $vroomData['userId'] !== (int) $userId) {
            $result['systemError'] = 'Vroom user Id dont match user id';
            $result['continue'] = false;
        }

        // Set redirect url
        if ($result['continue']) {
            $vroomCfg = $this->cfg->get('vroom');
            $env = $this->cfg->get('env');
            $result['redirectUrl'] = $vroomCfg[$env . 'BaseUrl'] . 'vr_' . $vroomId;
        }

        // Log if needed
        if ( ! empty($result['systemError'])) {
            $logData = json_encode($result, JSON_PRETTY_PRINT);
            logDebug('lib/consultation checkVroomIdAndGetRedirectUrl() ' . $logData);
        }

        return $result;
    }
}

?>
