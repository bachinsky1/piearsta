<?php

class DownloadAppointmentRequests
{
    private $db = null;
    /** @var RequestVivat libRequestVivat */
    private $libRequestVivat = null;
    private $configVivatApi = null;

    public function __construct()
    {
        $this->db = loadLibClass('db');
        /** @var RequestVivat libRequestVivat */
        $this->libRequestVivat = loadLibClass('RequestVivat', true, '', 'vivat');

        $this->libConfig = loadLibClass('config');

        // Set vivat api config
        $configEnv = $this->libConfig->get('env');
        $configVivatApi = $this->libConfig->get('vivatApi');
        $this->configVivatApi = $configVivatApi[$configEnv];
    }

    /**
     * @param $vpArray
     * @param $excludedArray
     * @param $params
     * @return array|mixed
     */
    public function downloadAppointmentRequests($vpArray, $excludedArray, $params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("DownloadAppointmentRequests::downloadAppointmentRequests");
        }

        $result = array(
            '_functionName' => __FUNCTION__,
            '_continue' => true,
            '_error' => '',
            '_functionSuccess' => array(),
            'totalBookingRequestsRowsInserted' => 0,
            'localData' => array(
                'vpIds' => array(),
                'dateFrom' => null,
                'dateTo' => null,
            ),
            'logData' => array(),
        );

        // Get filters for vivat api
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->getFiltersForVivatApi($vpArray, $excludedArray, $tempParams);
        }

        // Download appointment requests from vivat
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->downloadFromVivat($tempParams);
        }

        // Save appointment requests in db
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );

            $result = $this->saveAppointmentRequestsInDb($tempParams);
        }

        // Log
        if ($this->configVivatApi['appointmentRequests']['writelogs'] === 'always'
            || ($this->configVivatApi['appointmentRequests']['writelogs'] === 'onFailure') && $result['_continue'] !== true)
        {
            logDebug('vivat/DownloadAppointmentRequests result ' . json_encode($result, JSON_PRETTY_PRINT));
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    // ---

    private function getFiltersForVivatApi($vpArray, $excludedArray, $params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("DownloadAppointmentRequests::getFiltersForVivatApi");
        }

        $result = $params['_result'];

        // Note: VVP-54 We should not use any other filter except pageSize
        $result['localData']['vpIds'] = null;
        $result['localData']['dateFrom'] = null;
        $result['localData']['dateTo'] = null;

        // Get vp ids
        if ($result['_continue'] === true)
        {

            $result['logData']['filters'] = array();

            $vpCond = '';

            if(!empty($vpArray)) {
                $vpCond .= ' vp_id IN (' . implode(',', $vpArray) . ') AND ';
            }

            if(!empty($excludedArray)) {
                $vpCond .= ' vp_id NOT IN (' . implode(',', $excludedArray) . ') AND ';
            }

            $dbQuery = 'SELECT DISTINCT vp_id 
                        FROM mod_doctors_to_clinics 
                        WHERE 
                              ' . $vpCond . '
                              vp_id IS NOT NULL';

            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0)
            {
                $rows = $query->getArray();

                $result['localData']['vpIds'] = array();
                $result['localData']['vpIdsDoctors'] = array();
                foreach ($rows as $row)
                {
                    $docQuery = "SELECT d.hsp_resource_id FROM mod_doctors_to_clinics dtc
                                LEFT JOIN mod_doctors d ON (d.id = dtc.d_id)
                                WHERE vp_id = '" . $row['vp_id'] . "'";

                    $dQuery = new query($this->db, $docQuery);

                    if($dQuery->num_rows()) {

                        $result['localData']['vpIdsDoctors'][$row['vp_id']] = array();

                        while ($dIdRow = $dQuery->getrow()) {
                            $result['localData']['vpIdsDoctors'][$row['vp_id']][] = intval($dIdRow['hsp_resource_id']);
                        }
                    }

                    $result['localData']['vpIds'][] = $row['vp_id'];
                }

                $result['logData']['filters'] = $result['localData'];

            } else {
                $result['logData']['filters'] = array(
                    'message' => 'No VP ids by cron criteria!'
                );
            }
        }

        // Set date-from, date-to
//        if ($result['_continue'] === true)
//        {
//            // TODO What interval we should set here?
//            $result['localData']['dateFrom'] = date('Y-m-d') . ' 00:00:00';
//            $result['localData']['dateTo'] = date('Y-m-d') . ' 23:59:59';
//        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    private function downloadFromVivat($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("DownloadAppointmentRequests::downloadFromVivat");
        }

        $result = $params['_result'];

        // Call vivat
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
                'vpIds' => $result['localData']['vpIds'],
                'dateFrom' => $result['localData']['dateFrom'],
                'dateTo' => $result['localData']['dateTo'],
            );
            $result = $this->libRequestVivat->getVaccinationAppointmentRequests($tempParams);

//            var_dump($result);

            $result['logData']['requestResultSummary'] = array();

            // Handle response codes
            if ($result['currentRequest']['responseCode'] === 404)
            {
                $result['status'] = '1';
                $result['_continue'] = false;
                $result['_error'] = '';
                $result['msg'] = $result['_error'];
                $result['result_msg'] = 'Response code is 404. Its mean that there is no appointment requests.';
            }
            else if ($result['currentRequest']['responseCode'] !== 200)
            {
                $result['status'] = '2';
                $result['_continue'] = false;
                $result['_error'] = 'Response code is not 200';
                $result['msg'] = $result['_error'];
            }

            if($result['currentRequest']['responseCode'] == 200) {
                $result['status'] = '1';
                $result['msg'] = '';
            }

            $result['logData']['requestResultSummary'] = array(
                'status' => $result['status'],
                'error' => isset($result['_error']) ? $result['_error'] : '',
                'message' => $result['msg'],
                'result_message' => isset($result['result_msg']) ? $result['result_msg'] : '',
            );

        } else {

            $result['logData']['requestResultSummary'] = array(
                'message' => 'No response data',
            );
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    private function saveAppointmentRequestsInDb($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("DownloadAppointmentRequests::saveAppointmentRequestsInDb");
        }

        $result = $params['_result'];

        $result['logData']['savingToDb'] = array(
            'insertedIds' => array(),
            'totalInserted' => 0,
            'error' => '',
            'failedRecords' => array(),
            'duplicateRecords' => array(),
        );

        // Save in db
        if ($result['_continue'] === true && ! empty($result['currentRequest']['responseDataLocal']))
        {
            $result['currentRequest']['responseDataLocalSuccessInsertCount'] = 0;
            foreach ($result['currentRequest']['responseDataLocal'] as $i => $item)
            {
                $tempRow = $item;
                $tempRow['patient_data'] = json_encode($tempRow['patient_data']);

                $insertId = saveValuesInDb('vivat_booking_requests', $tempRow);

                if ($insertId > 0)
                {
                    $result['currentRequest']['responseDataLocal'][$i]['dbRowId'] = $insertId;
                    $result['currentRequest']['responseDataLocalSuccessInsertCount']++;
                    $result['totalBookingRequestsRowsInserted']++;

                    $result['logData']['savingToDb']['insertedIds'][] = $insertId;
                    $result['logData']['savingToDb']['totalInserted'] = $result['totalBookingRequestsRowsInserted'];

                } else {

                    $result['logData']['savingToDb']['failedRecords'][] = $tempRow;
                }
            }

            if ($result['currentRequest']['responseDataLocalSuccessInsertCount'] !== count($result['currentRequest']['responseDataLocal']))
            {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to insert into db one or more responseDataLocal rows';
                $result['logData']['savingToDb']['error'] = $result['_error'];
            }
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }
}
