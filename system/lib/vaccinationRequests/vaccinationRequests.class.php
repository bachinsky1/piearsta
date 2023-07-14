<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2021, BlueBridge Technologies.
 */

// ------------------------------------------------------------------------

/**
 * vaccinationRequests class
 */
class vaccinationRequests
{
    /** @var config  */
    private $cfg;

    /** @var db */
    private $db;

    private $maxRetryCount;

    /**
     * vaccinationRequests constructor.
     */
    public function __construct()
    {

        /** @var db */
        $this->db = loadLibClass('db');

        /** @var config */
        $this->cfg = loadLibClass('config');

        $this->maxRetryCount = $this->cfg->get('vbSmUploadMaxRetryCount');
    }

    public function processFailedBatches()
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("vaccinationRequests::processFailedBatches");
        }

        $result = array(
            'success' => true,
            'error_message' => '',
            'logData' => array(),
        );

        $result['logData']['failed_batches_check'] = array();

        // check for hanging processing status

        $dbQuery = "SELECT * FROM sm_booking_batches  
                            WHERE 
                                  status = 4 
                            ORDER BY start_time ASC";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            $result['logData']['batches'] = array();

            while($row = $query->getrow()) {

                $result['logData']['batches'][$row['batch_id']] = array(
                    'batch_id' => $row['batch_id'],
                );

                $batchData = array(
                    'start_time' => date('Y-m-d H:i:s', time()),
                );

                // check the batch for booking requests presence
                $brDbQuery = "SELECT COUNT(id) FROM vivat_booking_requests WHERE batch_id = " . $row['batch_id'];
                $brQuery = new query($this->db, $brDbQuery);

                $recCount = 0;

                if($brQuery->num_rows()) {
                    $recCount = intval($brQuery->getOne());
                }

                if($recCount > 0) {

                    // there are records in this batch, so set status 3 to retry sending to sm

                    $batchData['status'] = '3';
                    $batchData['attempts'] = '0';

                    $result['logData']['batches'][$row['batch_id']]['records'] = $recCount;
                    $result['logData']['batches'][$row['batch_id']]['message'] = 'Status 3 set to retry sending this batch again';

                } else {

                    // No records in a batch so fail it with special 10 status to never process it again
                    $batchData['status'] = '10';
                    $batchData['error_message'] = 'Contain no records. Failed finally and forever.';

                    $result['logData']['batches'][$row['batch_id']]['records'] = 0;
                    $result['logData']['batches'][$row['batch_id']]['message'] = 'Status 10 set. Contain no records. Failed finally and forever.';
                }

                saveValuesInDb('sm_booking_batches', $batchData, $row['batch_id']);
            }

        } else {
            $result['logData']['failed_batches_check']['message'] = 'No failed batches found.';
        }

        return $result;
    }

    /**
     * @return array
     */
    public function checkAndProcessRetries()
    {

        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("vaccinationRequests::checkAndProcessRetries");
        }

        $result = array(
            'success' => true,
            'error_message' => '',
            'logData' => array(),
        );

        // check for hanging processing status

        $dbQuery = "SELECT * FROM sm_booking_batches  
                            WHERE 
                                  start_time <= '" . date('Y-m-d H:i:s', (time() - $this->cfg->get('vbSmUploadCronMaxExecutionTime') )) . "' AND 
                                  status = 1";

        $query = new query($this->db, $dbQuery);

        $result['logData']['hanging_batches_check'] = array();

        if($query->num_rows()) {

            // we have batches with hanging processing status

            $result['logData']['hanging_batches_check'] = array();

            while ($row = $query->getrow()) {

                $result['logData']['hanging_batches_check'][$row['batch_id']] = array(
                    'batch_id' => $row['batch_id'],
                    'message' => '',
                );

                $data = array(
                    'end_time' => date('Y-m-d H:i:s', time()),
                );

                if(intval($row['attempts']) < intval($this->maxRetryCount)) {

                    // if max attempts not exceeded we set batch for retry...

                    $data['status'] = '3';
                    $result['logData']['hanging_batches_check'][$row['batch_id']]['batch_id'] = $row['batch_id'];
                    $result['logData']['hanging_batches_check'][$row['batch_id']]['message'] = 'To be processed as retry';

                } else {

                    // or fail it finally otherwise

                    $data['status'] = '4';
                    $data['error_message'] = 'Max attempts exceeded, failed finally!';
                    $result['logData']['hanging_batches_check'][$row['batch_id']]['batch_id'] = $row['batch_id'];
                    $result['logData']['hanging_batches_check'][$row['batch_id']]['message'] = 'Max attempts exceeded, failed finally!';
                }

                $brDbQuery = "UPDATE vivat_booking_requests
                                SET
                                    processing_start_datetime = '" . date('Y-m-d H:i:s', time()) . "',
                                    processing_status = 3
                                WHERE
                                    batch_id = " . $row['batch_id'];

                $brQuery = new query($this->db, $brDbQuery);

                saveValuesInDb('sm_booking_batches', $data, $row['id']);
            }

        } else {

            $result['logData']['hanging_batches_check']['message'] = 'No hanging batches found';
        }

        // check for retry

        $dbQuery = "SELECT * FROM sm_booking_batches  
                            WHERE 
                                  status = 3 
                            ORDER BY batch_id ASC";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            // batches to retry found

            $result['logData']['batchesToRetry'] = $query->num_rows();
            $result['logData']['batches'] = array();

            while ($row = $query->getrow()) {

                $result['logData']['batches'][$row['batch_id']] = array(
                    'batch_id' => $row['batch_id'],
                    'message' => '',
                    'records' => 0,
                    'data' => array(),
                    'request' => array(),
                );

                // attempts < " . intval($this->maxRetryCount) . "
                // if attempt >= maxRetry count we update status to 4 -- failed finally

                if(intval($row['attempts']) >= intval($this->maxRetryCount)) {
                    // no such clinic, set this batch to finally failed and continue to next retry batch

                    $batchData = array(
                        'status' => '4',
                        'start_time' => date('Y-m-d H:i:s', time()),
                        'end_time' => date('Y-m-d H:i:s', time()),
                        'error_message' => 'MAX ATTEMPTS EXCEEDED',
                    );

                    saveValuesInDb('sm_booking_batches', $batchData, $row['batch_id']);

                    $result['logData']['batches'][$row['batch_id']]['message'] = 'MAX ATTEMPTS EXCEEDED. Failed finally';

                    continue;
                }

                // get clinic

                $dbQueryClinic = "SELECT * FROM mod_clinics 
                                  WHERE id = " . $row['clinic_id'];

                $clinicQuery = new query($this->db, $dbQueryClinic);

                $clinicData = null;

                if($clinicQuery->num_rows()) {

                    $clinicData = $clinicQuery->getrow();

                } else {

                    // no such clinic, set this batch to finally failed and continue to next retry batch

                    $batchData = array(
                        'status' => '4',
                        'attempts' => "'" . (intval($row['attempts']) + 1) . "'",
                        'start_time' => date('Y-m-d H:i:s', time()),
                        'end_time' => date('Y-m-d H:i:s', time()),
                        'error_message' => 'Error. No such clinic. Failed finally',
                    );

                    saveValuesInDb('sm_booking_batches', $batchData, $row['batch_id']);

                    $result['logData']['batches'][$row['batch_id']]['message'] = 'Error. No such clinic. Failed finally';

                    continue;
                }

                // and get requests data (and skip records with dates in the past)

                $dbQueryRequests = "SELECT * FROM vivat_booking_requests 
                                    WHERE 
                                          appointment_time_from > '".date('Y-m-d H:i:s', time())."' AND 
                                          batch_id = " . $row['batch_id'];

                $queryRequests = new query($this->db, $dbQueryRequests);

                if($queryRequests->num_rows()) {

                    $result['logData']['batches'][$row['batch_id']]['records'] = $queryRequests->num_rows();

                    // update batch record

                    // so update batch status to 1 in progress

                    $batchData = array(
                        'status' => '1',
                        'attempts' => (intval($row['attempts']) + 1),
                        'start_time' => date('Y-m-d H:i:s', time()),
                    );

                    saveValuesInDb('sm_booking_batches', $batchData, $row['batch_id']);

                    // loop to construct request to sm

                    $requestData = array();
                    $bookingRequestsIds = array();

                    while($reqRecord = $queryRequests->getrow()) {

                        $bookingRequestsIds[] = $reqRecord['id'];

                        $reqDataStructured = $this->constructRequest($reqRecord, $clinicData['id'], $row['batch_id']);
//                        $reqDataStructured = $this->constructRequest($row, $clinicData['id']);

                        if(!empty($reqDataStructured)) {

                            $requestData[] = $reqDataStructured;

                        }
                    }

                    $result['logData']['batches'][$row['batch_id']]['data'] = $requestData;

                    // Do processing if we have non-empty request data (there is processing error otherwise)

                    if(!empty($requestData)) {

                        $brDbQuery = "UPDATE vivat_booking_requests 
                                    SET
                                        processing_start_datetime = '" . date('Y-m-d H:i:s', time()) . "',
                                        processing_end_datetime = NULL,
                                        processing_status = 1
                                    WHERE
                                        id IN (" . implode(',', $bookingRequestsIds) . ")";

                        $brQuery = new query($this->db, $brDbQuery);

                        // send request to SM

                        $res = $this->sendToSm($requestData, $clinicData['id'],$bookingRequestsIds);

                        $result['logData']['batches'][$row['batch_id']]['request'] = $res['request'];

                        $recData = array(
                            'end_time' => date('Y-m-d H:i:s', time()),
                        );

                        $brData = array(
                            'processing_end_datetime' => date('Y-m-d H:i:s', time()),
                        );

                        // check response

                        if(isset($res['status']) && $res['status'] == 2) {

                            $result['success'] = true;
                            $result['error_message'] = '';

                            $recData['status'] = '2';

                            $brData['status'] = 2;

                        } elseif (isset($res['status']) && $res['status'] == 3) {

                            if($batchData['attempts'] >= intval($this->maxRetryCount)) {

                                // Attempts exceeded so set batch to finally failed

                                $result['success'] = false;
                                $result['error_message'] = 'MAX ATTEMTS EXCEEDED';

                                $recData['status'] = '4';
                                $recData['error_message'] = 'MAX ATTEMTS EXCEEDED';

                                $brData['status'] = 3;

                            } else {

                                // Next retry attempt is possible on next cron run

                                $result['success'] = false;
                                $result['error_message'] = json_encode(array(
                                    'error_message' => 'To be retried',
                                    'debug' => $res,
                                ));

                                $recData['status'] = '3';

                                $brData['status'] = 3;
                            }

                        } else {

                            // auth or data logical error -- set batch to failed finally

                            $result['success'] = false;
                            $result['error_message'] = json_encode(array(
                                'error_message' => 'Failed finally',
                                'debug' => $res,
                            ));

                            $recData['status'] = '4';

                            $brData['status'] = 3;
                        }

                        if(DEBUG) {
                            var_dump(isset($res['logData']) ? $res['logData'] : $result);
                        }

                        // update batch

                        saveValuesInDb('sm_booking_batches', $recData, $row['batch_id']);

                        // update requests records

                        $brDbQuery = "UPDATE vivat_booking_requests 
                                    SET
                                        processing_end_datetime = '" . $brData['processing_end_datetime'] . "',
                                        processing_status = " . $brData['status'] . "
                                    WHERE
                                        id IN (" . implode(',', $bookingRequestsIds) . ")";

                        $brQuery = new query($this->db, $brDbQuery);

                    } else {

                        // Empty request data!

                        $recData = array(
                            'end_time' => date('Y-m-d H:i:s', time()),
                        );

                        $brData = array(
                            'processing_end_datetime' => date('Y-m-d H:i:s', time()),
                        );

                        $result['success'] = false;

                        $result['error_message'] = json_encode(array(
                            'error_message' => 'Empty request data! Possible processing error or invalid data structure.',
                            'debug' => 'Check booking requests table data!',
                        ));

                        $recData['status'] = '4';
                        $recData['error_message'] = json_encode($result['error_message']);

                        $brData['status'] = 3;

                        $brDbQuery = "UPDATE vivat_booking_requests 
                                    SET
                                        processing_start_datetime = '" . $brData['processing_end_datetime'] . "',
                                        processing_status = " . $brData['status'] . "
                                    WHERE
                                        id IN (" . implode(',', $bookingRequestsIds) . ")";

                        $brQuery = new query($this->db, $brDbQuery);

                        saveValuesInDb('sm_booking_batches', $recData, $row['batch_id']);

                        $result['logData']['batches'][$row['batch_id']]['request'] = 'Empty request data! Possible processing error or invalid data structure.';
                    }

                } else {

                    $recData = array(
                        'end_time' => date('Y-m-d H:i:s', time()),
                    );
                    $recData['status'] = '4';
                    $recData['error_message'] = 'Old batch hanged in retry status - no booking records for this batch. Failed finally';
                    saveValuesInDb('sm_booking_batches', $recData, $row['batch_id']);
                }
            }

        } else {

            $result['logData']['retries'] = 'No batches to retry';
        }

        return $result;
    }

    /**
     * @param $clinicArray
     * @param $excludedArray
     * @param $batchSize
     * @param $cronId
     * @return array
     */
    public function processRequests($clinicArray, $excludedArray, $batchSize, $cronId)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("vaccinationRequests::processRequests");
        }

        $result = array(
            'success' => true,
            'error_message' => '',
            'logData' => array(),
        );

        $result['logData']['dbQueries'] = array();

        // get vpIds if clinicsArray not empty

        $vpIds = array();

        $inc = '';
        $exc = '';

        // add this condition if clinics array non-empty

        if(!empty($clinicArray)) {
            $inc = " AND dtc.c_id IN (" . implode(',', $clinicArray) . ") ";
        }

        // add this condition if excluded non-empty

        if(!empty($excludedArray)) {
            $exc = ' AND dtc.c_id NOT IN (' . implode(',', $excludedArray) . ')';
        }

        $dbQuery = "SELECT DISTINCT dtc.vp_id 
                        FROM mod_doctors_to_clinics dtc
                        WHERE 
                              dtc.vp_id IS NOT NULL
                              " . $inc . " 
                              " . $exc;

        $result['logData']['dbQueries']['selectVpFromDtc'] = $dbQuery;

        $query = new query($this->db, $dbQuery);

        $vpIdsQuoted = array();

        if($query->num_rows()) {

            while ($row = $query->getrow()) {

                if(!in_array($row['vp_id'], $vpIds)) {
                    $vpIds[] = $row['vp_id'];
                    $vpIdsQuoted[] = "'" . $row['vp_id'] . "'";
                }
            }
        }

        // no vps found for given clinics -- stop processing end return

        if(empty($vpIds)) {
            $result['success'] = true;
            $result['result_message'] = 'No vp_ids found for given clinics';

            $result['logData']['message'] =  'No vp_ids found for given clinics';

            return $result;
        }

        // collect vp_ids from vaccination requests

        $dbQuery = "SELECT r.vp_id 
                    FROM vivat_booking_requests r
                    WHERE 
                        r.batch_id IS NULL AND
                        r.processing_status <> 7 AND  
                        r.vp_id IN (" . implode(',', $vpIdsQuoted) . ") 
                    GROUP BY r.vp_id";

        $result['logData']['dbQueries']['selectVpThatHaveData'] = $dbQuery;

        $query = new query($this->db, $dbQuery);

        $vpsFromRequests = array();

        if($query->num_rows()) {

            while ($row = $query->getrow()) {
                $vpsFromRequests[] = $row['vp_id'];
            }

        } else {

            // no requests found for given vps

            $result['success'] = true;
            $result['result_message'] = 'No unprocessed data found';
            $result['logData']['message'] =  'No unprocessed data found';

            return $result;
        }

        $result['logData']['vpIds'] = $vpsFromRequests;

        if(!empty($vpsFromRequests)) {
            $result['logData']['vpsRequests'] = array();
        }

        // loop thru vp ids

        $hasRequests = false;

        $finalResult = null; // can be 1 - success, 2 -fail, 3 - partial success (at least one vp)

        foreach ($vpsFromRequests as $vp) {

            if(!$clinicArray || in_array($vp, $vpIds)) {

                $result['logData']['vpsRequests'][$vp] = array(
                    'records' => 0,
                    'message' => '',
                    'data' => array(),
                    'request' => array(),
                );

                // get clinic data for this vp

                $dbQueryClinic = "SELECT c.* FROM mod_clinics c 
                                  LEFT JOIN mod_doctors_to_clinics dtc ON (c.id = dtc.c_id) 
                                  WHERE
                                    dtc.vp_id = '" . $vp . "' 
                                  LIMIT 1";

                $queryClinic = new query($this->db, $dbQueryClinic);

                $clinicData = null;

                // if clinic matching this vp found -- get data, continue loop otherwise

                if($queryClinic->num_rows()) {
                    $clinicData = $queryClinic->getrow();
                } else {

                    $result['logData']['vpsRequests'][$vp]['message'] = 'No clinics found';

                    continue;
                }

                // get batchSize number of request records for given vp, not included in a batch and in ASC order by
                // request datetime to ensure fifo principle

                $dbQueryBr = "SELECT * FROM vivat_booking_requests 
                            WHERE
                                  batch_id IS NULL AND
                                  vp_id = '" . $vp . "' AND 
                                  processing_status <> 7 
                            ORDER BY queue_id ASC 
                            LIMIT " . intval($batchSize);

                $queryBr = new query($this->db, $dbQueryBr);

                // if records found by criteria

                if($queryBr->num_rows()) {

                    $hasRequests = true;

                    $result['logData']['vpsRequests'][$vp]['records'] = $queryBr->num_rows();

                    // create new batch record

                    $startBatch = microtime(true);
                    
                    $batchPrimaryId = $this->addNewBatch($cronId,$clinicData['id'],$queryBr->num_rows());

                    if(empty($batchPrimaryId)) {
                        $result['logData']['vpsRequests'][$vp]['message'] = 'Batch insertion SQL error. Duplicates found.';
                        continue;
                    }

                    $batchId = $batchPrimaryId;

                    $batchDbData = array(
                        'id' => $batchPrimaryId,
                        'batch_id' => $batchPrimaryId,
                        'cron_id' => $cronId,
                        'clinic_id' => $clinicData['id'],
                        'start_time' => date('Y-m-d H:i:s', time()),
                        'status' => '1',
                        'rec_num' => $queryBr->num_rows(),
                        'attempts' => '0',
                        'error_message' => '',
                    );

                    $result['logData']['vpsRequests'][$vp]['batch_id'] = $batchPrimaryId;
                    $result['logData']['vpsRequests'][$vp]['batchCreated'] = $batchDbData;

                    // loop to construct request to sm

                    $requestsIdsArray = array();

                    $requestData = array();

                    while($row = $queryBr->getrow()) {

                        $requestsIdsArray[] = $row['id'];

                        $reqDataStructured = $this->constructRequest($row, $clinicData['id'], $batchDbData['batch_id']);

                        if(!empty($reqDataStructured)) {

                            $requestData[] = $reqDataStructured;
                        }
                    }

                    $result['logData']['vpsRequests'][$vp]['data'] = $requestData;

                    // set batch_id for all collected request records

                    $setBatchIdDbQuery = "UPDATE vivat_booking_requests 
                                          SET 
                                              batch_id = " . $batchId . ",
                                              processing_status = 1,
                                              processing_start_datetime = '" . date('Y-m-d H:i:s', time()) . "' 
                                          WHERE
                                            id IN (" . implode(',', $requestsIdsArray) . ")";

                    doQuery($this->db, $setBatchIdDbQuery);

                    // send request to SM

                    $res = $this->sendToSm($requestData, $clinicData['id'],$requestsIdsArray);

                    $result['logData']['vpsRequests'][$vp]['request'] = $res['request'];

                    $recData = array(
                        'end_time' => date('Y-m-d H:i:s', time()),
                        'attempts' => '1',
                    );

                    $brData = array(
                        'processing_end_datetime' => date('Y-m-d H:i:s', time()),
                    );

                    // check response

                    if(isset($res['status']) && $res['status'] == 2) {

                        $result['success'] = true;

                        $result['error_message'] = '';

                        $recData['status'] = '2';
                        $brData['status'] = 2;

                        if(!$finalResult) {
                            $finalResult = 1;
                        } elseif ($finalResult == 2) {
                            $finalResult = 3;
                        }

                    } elseif (isset($res['status']) && $res['status'] == 3) {

                        // Retry on next cron run

                        $result['success'] = false;

                        $result['error_message'] = array(
                            'status' => 3,
                            'message' => 'Failed but to be retried',
                            'debug' => $res,
                        );

                        $recData['status'] = '3';
                        $brData['status'] = 3;

                        if(!$finalResult) {
                            $finalResult = 2;
                        } elseif ($finalResult == 1) {
                            $finalResult = 3;
                        }

                    } else {

                        // failed finally

                        $result['success'] = false;

                        $result['error_message'] = array(
                            'status' => 4,
                            'message' => 'Failed finally',
                            'debug' => $res,
                        );

                        $recData['status'] = '4';
                        $brData['status'] = 3;

                        if(!$finalResult) {
                            $finalResult = 2;
                        } elseif ($finalResult == 1) {
                            $finalResult = 3;
                        }
                    }

                    if(DEBUG) {
                        var_dump(isset($res['logData']) ? $res['logData'] : $result);
                    }

                    // update batch

                    saveValuesInDb('sm_booking_batches', $recData, $batchPrimaryId);

                    // update booking requests

                    $brDbQuery = "UPDATE vivat_booking_requests
                                    SET
                                        processing_end_datetime = '" . $brData['processing_end_datetime'] . "',
                                        processing_status = " . $brData['status'] . "
                                    WHERE
                                        id IN (" . implode(',', $requestsIdsArray) . ")";

                    doQuery($this->db, $brDbQuery);

                    $endBatch = microtime(true);

                    if(!empty($batchPrimaryId)) {
                         $upd = "UPDATE sm_booking_batches SET process_time = ".($endBatch - $startBatch)." WHERE id = ".$batchPrimaryId;
                         doQuery($this->db, $upd);
                    }

                } else {

                    $result['success'] = true;
                    $result['result_message'] = 'No unprocessed data found';
                    $result['logData']['message'] =  'No unprocessed data found';

                    $finalResult = 1;
                }
            }
        }

        if(!$hasRequests) {
            $result['success'] = true;
            $result['result_message'] = 'No unprocessed data found';
            $result['logData']['message'] =  'No unprocessed data found';

            $finalResult = 1;
        }

        $result['success'] = ($finalResult == 1 || $finalResult == 3);

        // request records processed

        return $result;
    }

    /**
     * @param $arr
     * @param $clinicId
     * @return array
     */
    private function constructRequest($arr, $clinicId, $batchId)
    {

        $patientData = (!empty($arr['patient_data'])) ? json_decode($arr['patient_data'], true) : null;

        // if no patient data -- return null to exclude this record due to it is invalid

        if(!$patientData) {
            return array();
        }

        $phone = $patientData['phonesList'];
        $email = $patientData['emailsList'];

        // get duration from time slot interval
        $duration = $this->getDuration($arr);

        $requestData = array(
            'piearstaId' => $arr['id'],
            'vaccinationPointId' => $arr['vp_id'],
            'resources' => json_decode($arr['doctors_list'], true),
            'personCode' => $patientData['personCode'],
            'gender' => $patientData['gender'] == 'Male' ? 'V' : 'S',
            'firstName' => $patientData['firstName'],
            'lastName' => $patientData['lastName'],
            'dateOfBirth' => date('Y-m-d', strtotime($patientData['dateOfBirth'])),
            'phone' => $phone,
            'email' => $email,
            'vaccinationType' => !empty($arr['vaccination_type']) ? explode(',', $arr['vaccination_type']) : array(), // new field passed as an array
            'appointmentDate' => $arr['appointment_date'],
            'timeFrom' => $arr['appointment_time_from'],
            'timeTo' => $arr['appointment_time_to'],
            'duration' => $duration,
            'anyOtherTime' => $arr['any_other_time'] == 1,
            'recordId' => $arr['aiis_record_id'],
            'cancelAppointment' => $arr['cancel_appointment'] == 1,
            'queueId' => $arr['queue_id'],
            'DEBUG' => array(
                'piearstaClinicId' => $clinicId,
                'piearstaBatchId' => $batchId,
            ),
        );

        return $requestData;
    }

    /**
     * @param $arr
     * @param $vp
     * @param $clinicId
     * @return array
     */
    private function sendToSm($arr, $clinicId, $requestsIdsArray = null)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("vaccinationRequests::sendToSm");
        }

        /** @var requestSm $requestSm */
        $requestSm = loadLibClass('requestSm', true, $clinicId);

        $result = $requestSm->requestSm('vaccination_booking', $arr, 'json', $clinicId, $requestsIdsArray);

        $resp = array(
            'request' => $result['logData'],
        );

        if (isset($result['httpCode']) && $result['httpCode'] == 200) {

            $resp['success'] = true;
            $resp['httpCode'] = $result['httpCode'];
            $resp['status'] = 2; // success received, the batch sent to SM
            $resp['requestResult'] = 'success received, the batch sent to SM';

        } elseif(isset($result['httpCode']) && ($result['httpCode'] >= 400 && $result['httpCode'] < 500)) {

            $resp['success'] = false;
            $resp['httpCode'] = $result['httpCode'];
            $resp['status'] = 4; // auth or data validation error, we shouldn't retry
            $resp['requestResult'] = 'auth or data validation error, we shouldn\'t retry';

        } else {

            $resp['success'] = false;
            $resp['httpCode'] = @$result['httpCode'];
            $resp['status'] = 3; // technical error, retry possible
            $resp['requestResult'] = 'technical error, retry possible';
        }

        if(DEBUG) {
            $resp['debug'] = $result;
        }

        return $resp;
    }


    private function getDuration($data)
    {
        $duration = 15;

        $startTime = $data['appointment_time_from'];
        $endTime = $data['appointment_time_to'];
        $vpId = $data['vp_id'];

        $dbQuery = "SELECT s.`interval` FROM mod_shedules AS s 
                    INNER JOIN mod_doctors_to_clinics AS dtc ON ( dtc.d_id = s.doctor_id AND dtc.c_id = s.clinic_id )
                    WHERE 
                          dtc.vp_id = '".$vpId."' AND 
                          s.start_time >= '" . $startTime . "' AND 
                          s.end_time <= '" . $endTime . "' 
                    LIMIT 1";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $row = $query->getrow();

            $duration = intval($row['interval']);
        }

        return $duration;
    }

    private function generateBatchGuid()
    {
        $uuid = md5(uniqid(rand(), true));
        $guid = substr($uuid, 0, 8) . "-" .
            substr($uuid, 8, 4) . "-" .
            substr($uuid, 12, 4) . "-" .
            substr($uuid, 16, 4) . "-" .
            substr($uuid, 20, 12);
        return $guid;
    }

    private function randomID($number_of_digits = 8) {
        return substr(number_format(time() * mt_rand(),0,'',''),0,$number_of_digits);
    }

    public function addNewBatch($cronId = 0,$clinicID = false,$recNum = 0)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("vaccinationRequests::addNewBatch");
        }

        if(empty($cronId) || empty($clinicID)) return false;
        $regenerateTries = 3;
        $batchGuid = $this->generateBatchGuid();

        $checkQuery = "SELECT * FROM sm_booking_batches WHERE batch_guid = '".$batchGuid."'";
        $query = new query($this->db, $checkQuery);

        $genSuccess = false;
        if(!empty($query->num_rows())) {
            // check for existing guid, if any, and regenerate is necessary
            while($regenerateTries > 0) {
                $batchGuid = $this->generateBatchGuid();
                $checkQuery = "SELECT * FROM sm_booking_batches WHERE batch_guid = '".$batchGuid."'";
                if(empty($query->num_rows())) {
                    $genSuccess = true;
                    $regenerateTries = 0;
                    break;
                }
                $regenerateTries--;
            }
        } else {
            $genSuccess = true;
        }

        if(!$genSuccess) return false;

        $newBatchID = $this->randomID(11);

        $batchDbData = array(
            'batch_id' => NULL,
            'batch_guid' => $batchGuid,
            'cron_id' => $cronId,
            'clinic_id' => $clinicID,
            'start_time' => date('Y-m-d H:i:s', time()),
            'status' => '1',
            'rec_num' => $recNum,
            'attempts' => '0',
            'error_message' => '',
        );

//        $batchId = saveValuesInDb('sm_booking_batches', $batchDbData);
        saveValuesInDb('sm_booking_batches', $batchDbData);

        $getQuery = "SELECT id FROM sm_booking_batches WHERE batch_guid = '".$batchGuid."'";
        $query = new query($this->db, $getQuery);

        $batchId = null;
        $newBatchId = null;

        if($query->num_rows()) {
            $row = $query->getrow();
            $batchId = $row['id'];
            if(!empty($batchId)) {
                $checkQuery = "SELECT id FROM sm_booking_batches WHERE batch_id = '".$batchId."'";
                $query = new query($this->db, $checkQuery);
                if($query->num_rows()) $newBatchId = ($batchId + 10000);
            }
        }

        if(empty($batchId)) return false;

        if(!empty($newBatchId)) {
            $updSQL = 'UPDATE sm_booking_batches SET id = "'.$newBatchId.'", batch_id = "'.$newBatchId.'" WHERE (id = "'.$batchId.'")';
            $nbQuery = new query($this->db, $updSQL);
            $nbQuery->free();
            return $newBatchId;
        } else {
            $updSQL = 'UPDATE sm_booking_batches SET batch_id = "'.$batchId.'" WHERE (id = "'.$batchId.'")';
            $sbbQuery = new query($this->db, $updSQL);
            $sbbQuery->free();
        }
/*
        $updSQL = 'UPDATE sm_booking_batches SET batch_id = '.$batchId.' WHERE (id = '.$batchId.')';
        $sbbQuery = new query($this->db, $updSQL);
        $sbbQuery->free();
*/
        return $batchId;
    }
}

?>