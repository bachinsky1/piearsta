<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2021, BlueBridge.
 */

/**
 * class insurance
 */
class insurance
{
    /** @var config  */
    private $cfg;

    /** @var null | RequestSm */
    private $smRequest = null;

    /** @var array */
    private $smApiErrors;

    /** @var xml */
    private $xmlClass;

    /**
     * transaction constructor.
     * @param null $id
     */
    public function __construct()
    {
        $this->cfg = loadLibClass('config');
        $this->smApiErrors = $this->cfg->get('insuranceDcPaymentsErrors');
        $this->xmlClass = loadLibClass('xml');
    }

    /**
     * @param $userData
     * @param null $clinicId
     * @return array|null
     */
    public function getInsuranceData($userData, $clinicId = null)
    {
        $insurance = null;

        if(
            !empty($userData['insurance']) &&
            !empty($userData['insurance_id']) &&
            !empty($userData['insurance_number'])
        ) {

            if(!empty($clinicId)) {

                $dbQuery = "SELECT * FROM mod_clinics_to_classificators 
                            WHERE
                                clinic_id = " . $clinicId . " AND 
                                cl_type = 5 AND 
                                cl_id = " . $userData['insurance_id'];

                $query = new query($this->cfg->db, $dbQuery);

                if($query->num_rows() < 1) {
                    return $insurance;
                }
            }

            $startDate = $userData['insurance_start_date'] ? $userData['insurance_start_date'] : '2021-01-01 00:00:00';
            $expirationDate = $userData['insurance_end_date'] ? $userData['insurance_end_date'] : '9999-12-31 23:59:59';

            $insurance = array(
                'companyId' => $userData['insurance_id'],
                'companyPaId' => $userData['insurancePaId'],
                'companyName' => $userData['insurance'],
                'insuranceNumber' => $userData['insurance_number'],
                'startDate' => $startDate,
                'expirationDate' => $expirationDate,
                'notStarted' => $startDate > date('Y-m-d H:i:s', time()),
                'expired' => $expirationDate < date('Y-m-d H:i:s', time()),
            );
        }

        return $insurance;
    }

    /**
     * @param $data
     * @param $userData
     * @return array
     */
    public function insuranceCheck($data, $userData)
    {
        // construct request data

        $paymentUuid = getUUID();

        // get piearstaId of service

        $dbQuery = "SELECT * FROM mod_classificators 
                    WHERE
                        id = " . $data['serviceData']['c_id'];

        $query = new query($this->cfg->db, $dbQuery);

        /** @var array $serv */
        $serv = $query->getrow();

        $servicePiearstaId = $serv['piearstaId'];

        $requestData = array(
            'request_id' => $paymentUuid,
            'request_date' => date('Y-m-d H:i:s', time()),
            'patient_person_code' => $userData['code'],
            'patient_first_name' => $userData['name'],
            'patient_last_name' => $userData['surname'],
            'patient_birth_date' => $userData['date_of_birth'],
            'patient_gender' => $userData['gender'],
            'patient_citizenship' => $userData['country'],
            'ic_id' => $data['insuranceCompanyPAId'],
            'policy_card_number' => $data['insuranceNumber'],
            'doctor_id' => $data['hsp_resource_id'],
            'service_id' => $servicePiearstaId,
            'requested_amount' => $data['insurancePrice'],
        );

        // SM request

        /** @var RequestSm $smRequest */
        $this->smRequest = loadLibClass('requestSm');
        $smRequestResultRaw = $this->smRequest->requestSm('insurancePayment', $requestData, 'json', $data['clinic_id']);

        $checkResult = $this->processSmResult($smRequestResultRaw);
        $checkResult['servicePiearstaId'] = $servicePiearstaId;

        return array(
            'success' => true,
            'result' => $checkResult,
            'requestResult' => $smRequestResultRaw,
        );
    }

    /**
     * @param $orderId
     * @return array
     */
    public function cancelInsurancePayment($orderId)
    {
        $success = true;
        $message = 'Successfully cancelled';

        /** @var Order $order */
        $order = loadLibClass('order');
        $order->setOrder($orderId);
        $orderData = $order->getOrder();
        $orderDetails = $order->getOrderDetails();

        if(empty($orderData)) {
            $success = false;
            $message = 'Order ' . $orderId . ' not found';
        }

        $transactionId = $orderData['transaction_id'];

        if(empty($transactionId)) {
            $success = false;
            $message = 'Transaction for order ' . $orderId . ' not found';
        }

        /** @var transaction $transaction */
        $transaction = loadLibClass('transaction');
        $transaction->setTransaction($transactionId);
        $transactionData = $transaction->getTransaction();

        if(empty($transactionData)) {
            $success = false;
            $message = 'Transaction ' . $transactionId . ' not found';
        }

        // prepare request data for cancel_insurance_claim request

        // get user

        $patientData = getProfileById($orderData['patient_id']);

        // get ic

        $icDbQuery = "SELECT * FROM mod_classificators 
                        WHERE id = " . $patientData['insurance_id'];

        $icQuery = new query($this->cfg->db, $icDbQuery);

        $icData = $icQuery->getrow();
        $icId = $icData['piearstaId'];

        // get service
        $servDbQuery = "SELECT * FROM mod_classificators 
                        WHERE id = " . $orderDetails[0]['service_id'];

        $servQuery = new query($this->cfg->db, $servDbQuery);

        $servData = $servQuery->getrow();


        $servId = $servData['piearstaId'];

        $requestData = array(
            'request_id' => $transactionData['payment_uuid'],
            'request_date' => date('Y-m-d H:i:s', time()),
            'patient_person_code' => !empty($patientData['person_id']) ? $patientData['person_id'] : $patientData['person_number'],
            'ic_id' => $icId,
            'policy_card_number' => $transactionData['insurance_policy'],
            'service_id' => $servId,
            'requested_amount' => $orderDetails['price'],
            'transaction_id' => $transactionId,
        );

        /** @var RequestSm $smRequest */
        $this->smRequest = loadLibClass('requestSm');
        $smRequestResultRaw = $this->smRequest->requestSm('insuranceCancelPayment', $requestData, 'json', $orderData['clinic_id']);
        $checkResult = $this->processSmResult($smRequestResultRaw);
        $requestSuccess = $checkResult['success'];

        if(!$requestSuccess) {
            $message = 'Insurance payment cancellation failed: ' . $checkResult['message'];
        }

        $success = $checkResult['httpCode'] == 200 && $checkResult['status'] == 'accepted';

        // Stub for success DEBUG -- TODO: to be removed after api development

        // // //
//        $success = true;
        // // //

        if(!$success) {
            $message = 'Insurance cancelation failed';
        }

        $response = array(
            'success' => $success,
            'message' => $message,
        );

        $result = array(
            'method' => 'insuranceCancelPayment',
            'success' => $success,
            'result' => $response,
        );

        if(DEBUG) {
            $result['debug'] = array(
                'data' => $requestData,
                'checkResult' => $message,
                'smCheck' => $checkResult,
            );
        }

        return $result;
    }

    /**
     * @param $smResult
     * @return array
     */
    private function processSmResult($smResult)
    {
        // init result as unsuccessful

        $debug = $smResult['debug'];
        unset($smResult['debug']);

        $success = false;
        $policeOK = false;
        $compensation = null;
        $checkDatetime = null;
        $service = null;
        $message = null;

        if($smResult['httpCode'] == 200) {

            if(
                !empty($smResult['parsedResult'])
            ) {

                $success = true;
                $error = $smResult['parsedResult']['error'];
                $compensation = $smResult['parsedResult']['compensation'];
                $checkDatetime = $smResult['parsedResult']['checkDatetime'];
                $service = $smResult['parsedResult']['service'];

                // DEBUG
//                $error = 5;

                if($error == 0) {

                    $insResponse = 'insurance_checked';
                    $policeOK = true;

                } elseif ($error == 1) {

                    $insResponse = 'insurance_police_error';

                } elseif ($error == 3) {

                    $insResponse = 'insurance_police_expired';

                } else {

                    $insResponse = 'connection_error';
                }

                $message = $smResult['parsedResult']['errorDescription'];
            }

        } else {

            $insResponse = 'connection_error';
        }

        // DEBUG (Stub)

//        $success = true;
//        $policeOK = true;
//        $compensation = '4.50';
//        $checkDatetime = '2022-03-11 08:55:00';
//        $service = '3.1.1.1';
//
//        $insResponse = 'connection_error';
//        $insResponse = 'insurance_police_error';
//        $insResponse = 'insurance_checked';
        // END of Stub

        $result = array(
            'success' => $success,
            'status' => $insResponse,
            'policeOK' => $policeOK,
            'coverage' => $compensation,
            'checkDatetime' => $checkDatetime,
            'service' => $service,
            'message' => $message,
        );

        // if debug is on, we add request result to returned array

        if(DEBUG) {
            $result['smResult'] = $smResult;
        }

        return $result;
    }

    /**
     * @param $code
     * @return mixed|string
     */
    private function getErrorMessage($code)
    {
        return !empty($this->smApiErrors[$code]) ? $this->smApiErrors[$code] : ('Unknown error, code: ' . $code);
    }

    private function parseXml($xml)
    {

        try {

            $result = XML2Array::createArray($xml);

        } catch (Exception $e) {

            $result = array(
                'error' => array(
                    'code' => $e->getCode(),
                ),
            );
        }

        return $result;
    }
}

?>