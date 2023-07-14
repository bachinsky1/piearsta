<?php

class UploadSlots
{
    private $db = null;
    /** @var RequestVivat libRequestVivat */
    private $libRequestVivat = null;
    private $configVivatApi = null;

    public function __construct()
    {
        $this->db = loadLibClass('db');
        $this->libRequestVivat = loadLibClass('RequestVivat', true, '', 'vivat');

        $this->libConfig = loadLibClass('config');

        // Set vivat api config
        $configEnv = $this->libConfig->get('env');
        $configVivatApi = $this->libConfig->get('vivatApi');
        $this->configVivatApi = $configVivatApi[$configEnv];
    }

    /**
     * @param array $params
     * @return array $result
     */
    public function uploadSlots($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("UploadSlots::uploadSlots");
        }

        $result = array(
            '_functionName' => __FUNCTION__,
            '_continue' => true,
            '_error' => '',
            '_functionSuccess' => array(),
            'localData' => array(),
            'uploadAttempts' => 0,
            'uploadAttemptsSuccess' => 0,
            'logData' => array(
                'UploadSlots_top_method' => 'Started. ',
            ),
        );

        // Get cache id

        // try to obtain cache with status = 1 -- ready
        // if unsuccessfully -- take new tries until success or max time for retries exceeded

        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );

            $result = $this->getCacheId($tempParams);

            if(empty($result['localData']['cacheId'])) {

                $cacheId = null;
                $retryCacheIdMaxTime = $this->configVivatApi['calendarUploadSlots']['retryCacheIdMaxTime'];
                $retryCacheIdMaxTimestamp = time() + $retryCacheIdMaxTime;

                sleep(1);

                while(empty($cacheId)) {

                    $result = $this->getCacheId($tempParams);
                    $cacheId = !empty($result['localData']['cacheId']) ? $result['localData']['cacheId'] : null;

                    if(!$cacheId && time() > $retryCacheIdMaxTimestamp) {
                        break;
                    }

                    sleep(1);
                }

                if(empty($cacheId)) {
                    $execStatus = '2';
                    $msg = 'Could not obtain cache id';
                    $result['logData']['UploadSlots_top_method'] .= 'Failed with msg: ' . $result['_error'];
                }

            } else {
                $cacheId = $result['localData']['cacheId'];
            }
        }

        // Create cache upload logs

        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->createCacheUploadLogs($tempParams);
        }

        if($result['_continue'] === false) {
            $execStatus = '2';
            $msg = 'Error: ' . $result['_error'];
            $result['logData']['UploadSlots_top_method'] .= 'Failed with msg: ' . $result['_error'];
        } else {
            $msg = '';
            $result['logData']['UploadSlots_top_method'] .= 'Continue. ';
        }

        // Upload batches loop
        $loopContinue = $result['_continue'];
        $iteration = 1;

        $result['logData']['UploadSlots_loop'] = array();

        // if at least one batch successfully sent -- the final result we set to true
        $finalResult = 2; // can be 1 - success, 2 -fail, 3 - partial success (at least one batch)

        while ($loopContinue === true)
        {
            $result['logData']['UploadSlots_loop'][$iteration] = array();

            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->startNewBatch($tempParams);

            $result['logData']['UploadSlots_loop'][$iteration]['startNewBatch'] = $result['logData']['startNewBatch'];
            unset($result['logData']['startNewBatch']);

            $tempParams = array(
                '_result' => $result,
            );

            $result = $this->getNextBatchVpIds($tempParams);

            // loop is finished

            if(empty($result['currentBatch']['vpIds'])) {
                break;
            }

            $result['logData']['UploadSlots_loop'][$iteration]['getNextBatchVpIds'] = $result['logData']['getNextBatchVpIds'];
            unset($result['logData']['getNextBatchVpIds']);

            $tempParams = array(
                '_result' => $result,
            );

            // check for cacheId is still actual

            if(!$this->checkCacheIdIsActual($cacheId)) {

                $result['logData']['UploadSlots_loop'][$iteration]['message'] = 'New cache id preparing, stop current job at this point.';

                if($finalResult == 1) {
                    $finalResult = 3;
                }

                $msg = 'New cache id preparing, stop current job at this point.';

                $execStatus = '2';

                $this->finalizeBatch($result['currentBatch']['uploadLogsIds'], $msg);

                continue;
            }

            $result = $this->getNextBatchData($tempParams);

            if(empty($result['currentBatch']['data']) && $result['currentBatch']['status'] == 2) {

                $result['logData']['UploadSlots_loop'][$iteration]['message'] = 'New cache id preparing, stop current job at this point.';

                if($finalResult == 1) {
                    $finalResult = 3;
                }

                $msg = 'New cache id preparing, stop current job at this point.';

                $execStatus = '2';

                $this->finalizeBatch($result['currentBatch']['uploadLogsIds'], $msg);

                continue;

            } elseif (empty($result['currentBatch']['data'])) {

                $result['logData']['UploadSlots_loop'][$iteration]['message'] = 'No data for this batch but cache is still valid. Proceed with next batch processing';

                if($finalResult == 1) {
                    $finalResult = 3;
                }

                $msg = '';

                $execStatus = '2';

                $this->finalizeBatch($result['currentBatch']['uploadLogsIds'], $msg);

                continue;
            }

            $result['logData']['UploadSlots_loop'][$iteration]['getNextBatchData'] = $result['logData']['getNextBatchData'];
            unset($result['logData']['getNextBatchData']);

            $tempParams = array(
                '_result' => $result,
            );

            $result = $this->uploadBatchData($tempParams);

            $result['logData']['UploadSlots_loop'][$iteration]['uploadBatchData'] = array(
                'uploadBatchData' => $result['logData']['uploadBatchData'],
                'RequestResult' => $result['logData']['RequestResult'],
            );

            unset($result['logData']['uploadBatchData']);
            unset($result['logData']['RequestResult']);


            // Update upload attempts
            if ($result['_continue'] === true && ! empty($result['currentBatch']['data']))
            {
                $result['uploadAttempts']++;
                if (isset($result['currentBatch']['uploadWasSuccess']) && $result['currentBatch']['uploadWasSuccess'] === true)
                {
                    $result['uploadAttemptsSuccess']++;
                }
            }

            if($result['status'] == '1') {
                $execStatus = '1';
                $msg = '';
                $result['logData']['UploadSlots_loop'][$iteration]['uploadBatchData']['uploadBatchData'] .= 'Success.';
                $finalResult = 1;
            }

            if($execStatus != '1') {
                $execStatus = $result['status'];
                $msg = $result['msg'];
                $result['logData']['UploadSlots_loop'][$iteration]['uploadBatchData']['uploadBatchData'] .= 'Error: ' . $msg;

                if($finalResult == 1) {
                    $finalResult = 3;
                }
            }

            // Stop loop
            if ($result['_continue'] !== true || empty($result['currentBatch']['data']))
            {
                $loopContinue = false;
                $result['loopFinished'] = true;

                if($finalResult == 1 || $finalResult == 3) {

                    // thats ok - we could send at least one batch, so we remove empty loop iteration logging
                    unset($result['logData']['UploadSlots_loop'][$iteration]);

                } else {

                    // no batches uploaded
                    if(empty($result['currentBatch']['data'])) {

                        // we were trying to upload already uploaded cacheId
                        $result['logData']['UploadSlots_loop'][$iteration]['uploadBatchData']['uploadBatchData'] .= ' No data to process.';
                        $finalResult = 1;

                        $msg = 'Error: ' . (!empty($result['_error']) ? $result['_error'] : 'No data to process');
                        $this->finalizeBatch($result['localData']['createdUploadLogsIds'], $msg);

                    } else {

                        // Something wrong!
                        $result['logData']['UploadSlots_loop'][$iteration]['uploadBatchData']['uploadBatchData'] .= ' Something goes wrong!';

                        $msg = 'Error: ' . (!empty($result['_error']) ? $result['_error'] : 'Something goes wrong');
                        $this->finalizeBatch($result['localData']['createdUploadLogsIds'], $msg);
                    }
                }
            }

            $iteration++;
        }

        if($result['_continue'] == false && !empty($result['localData']['createdUploadLogsIds'])) {
            $msg = 'Error: ' . (!empty($result['_error']) ? $result['_error'] : 'unknown error.');
            $this->finalizeBatch($result['localData']['createdUploadLogsIds'], $msg);
        }

        if($finalResult == 1) {

            $result['status'] = '1';
            $result['msg'] = '';

        } elseif ($finalResult == 3) {

            $result['status'] = '1';
            $result['msg'] = 'Partial success';

        } else {

            $result['status'] = $execStatus;
            $result['msg'] = $msg;

            // Log
            if ($this->configVivatApi['calendarUploadSlots']['writelogs'] === 'always'
                || ($this->configVivatApi['calendarUploadSlots']['writelogs'] === 'onFailure') && $result['_continue'] !== true)
            {
                if(isset($result['currentRequest'])) {
                    logDebug('vivat/UploadSlots result ' . json_encode($result['currentRequest']['curlInfo'], JSON_PRETTY_PRINT));

                    $result['msg'] .= ' Curl info: ' . json_encode($result['currentRequest']['curlInfo']);
                }
            }
        }

        // Set function success
        $result['_functionSuccess'][__FUNCTION__] = $result['_continue'];

        // Return
        return $result;
    }

    // ---

    private function getCacheId($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("UploadSlots::getCacheId");
        }

        $result = $params['_result'];
        $result['logData']['getCacheId'] = 'Started. ';

        // Get cache id
        if ($result['_continue'] === true)
        {
            $dbQuery = 'SELECT id FROM vivat_cache_log 
                        WHERE 
                          status = 1 AND 
                          generation_end IS NOT NULL
                        ORDER BY id DESC 
                        LIMIT 1';

            $query = new query($this->db, $dbQuery);

            $row = $query->getrow();

            if (empty($row['id']))
            {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to get cacheId';
                $result['logData']['getCacheId'] .= 'Failed to get cacheId';
            }
            else
            {
                $result['localData']['cacheId'] = $row['id'];
                $result['logData']['getCacheId'] .= 'Success, cacheId = ' . $row['id'];
            }
        }

        // Return
        return $result;
    }

    /**
     * @param $uploadLogIds
     * @param $msg
     */
    private function finalizeBatch($uploadLogIds, $msg) {

        $updDbQuery = "UPDATE vivat_cache_upload_log SET 
                                    end_time = '" . date('Y-m-d H:i:s', time()) . "', 
                                    status = 4, 
                                    error_message = '" . $msg . "'
                                WHERE 
                                    id IN (" . implode(',', $uploadLogIds) . ")";

        doQuery($this->db, $updDbQuery);
    }

    /**
     * @param $cacheId
     * @return bool
     */
    private function checkCacheIdIsActual($cacheId)
    {
        // get latest cacheId
        $dbQuery = "SELECT * FROM vivat_cache_log 
                    ORDER BY id DESC LIMIT 1";
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            $cacheRecord = $query->getrow();
            $newCacheId = $cacheRecord['id'];

            return $newCacheId === $cacheId;
        }

        return false;
    }

    private function createCacheUploadLogs($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("UploadSlots::createCacheUploadLogs");
        }

        $result = $params['_result'];
        $result['logData']['createCacheUploadLogs'] = 'Started. ';

        // Truncate table vivat_cache_upload_log
        if ($result['_continue'] === true && $this->configVivatApi['calendarUploadSlots']['truncateVivatCacheUploadLog'] === true)
        {
            $dbQuery = 'TRUNCATE TABLE vivat_cache_upload_log';
            $query = new query($this->db, $dbQuery);
        }

        // Check whether to process this cache id

        // Get all vp-ids by cache-id
        if ($result['_continue'] === true)
        {
            $dbQuery = 'SELECT DISTINCT vp_id FROM vivat_cache_vplist WHERE cache_id = ' . $result['localData']['cacheId'];
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0)
            {
                $rows = $query->getArray();

                $result['localData']['vpIds'] = array();

                foreach ($rows as $row)
                {
                    $result['localData']['vpIds'][] = $row['vp_id'];
                }
            }
            else
            {
                $result['_continue'] = false;
                $result['_error'] = 'Zero vp ids selected from vivat_cache_vplist';
                $result['logData']['createCacheUploadLogs'] .= 'Error: ' . 'Zero vp ids selected from vivat_cache_vplist';
            }
        }

        // Create cache upload logs
        if ($result['_continue'] === true)
        {
            $culData = array(
                'cache_id' => $result['localData']['cacheId'],
                'rec_num' => 0, // TODO
                'start_time' => date('Y-m-d H:i:s'),
                'status' => '0', // 0/UploadStarted
                'attemts' => '0',
            );

            foreach ($result['localData']['vpIds'] as $vpId)
            {
                $culData['vp_id'] = $vpId;

                $dbQuery = 'SELECT id FROM vivat_cache_upload_log
                    WHERE cache_id = ' . $result['localData']['cacheId'] . ' AND vp_id = "' . $culData['vp_id'] . '" AND status NOT IN (0, 2) LIMIT 1';
                $query = new query($this->db, $dbQuery);
                $tempRow = $query->getrow();

                if ( ! empty($tempRow['id']))
                {
                    continue;
                }

                $result['localData']['createdUploadLogsIds'][] = saveValuesInDb('vivat_cache_upload_log', $culData);
            }

            $result['logData']['createCacheUploadLogs'] .= 'Created log ids: ' . json_encode($result['localData']['createdUploadLogsIds']);
        }

        // Return
        return $result;
    }

    private function getNextBatchVpIds($params)
    {
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("UploadSlots::getNextBatchVpIds");
        }

        $result = $params['_result'];

        $result['logData']['getNextBatchVpIds'] = 'Started. ';

        // Get next batch vp ids - first time upload
        if ($result['_continue'] === true)
        {
            $dbQuery = 'SELECT * FROM vivat_cache_upload_log
                WHERE cache_id = ' . $result['localData']['cacheId'] . ' AND status = 0
                LIMIT ' . $this->configVivatApi['calendarUploadSlots']['vpCountPerBatch'];
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0)
            {
                $result['currentBatch']['uploadLogsRows'] = $query->getArray();
                foreach ($result['currentBatch']['uploadLogsRows'] as $row)
                {
                    $result['currentBatch']['vpIds'][] = $row['vp_id'];
                    $result['currentBatch']['uploadLogsIds'][] = $row['id'];
                }

                $result['logData']['getNextBatchVpIds'] .= 'Collected vps: ' . json_encode($result['currentBatch']['vpIds']);
            }
        }

        // // Get next batch vp ids - re-upload attempts
        if ($result['_continue'] === true && empty($result['currentBatch']['uploadLogsRows']))
        {
            $dbQuery = 'SELECT * FROM vivat_cache_upload_log
                WHERE cache_id = ' . $result['localData']['cacheId'] . ' AND status = 2 AND attemts < ' . $this->configVivatApi['calendarUploadSlots']['maxUploadAttempts'] . '
                LIMIT ' . $this->configVivatApi['calendarUploadSlots']['vpCountPerBatch'];
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0)
            {
                $result['currentBatch']['uploadLogsRows'] = $query->getArray();
                foreach ($result['currentBatch']['uploadLogsRows'] as $row)
                {
                    $result['currentBatch']['vpIds'][] = $row['vp_id'];
                    $result['currentBatch']['uploadLogsIds'][] = $row['id'];
                }

                $result['logData']['getNextBatchVpIds'] .= 'Reloading vps: ' . json_encode($result['currentBatch']['vpIds']);
            }
        }

        // Return
        return $result;
    }

    private function getNextBatchData($params)
    {
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("UploadSlots::getNextBatchData");
        }

        $result = $params['_result'];

        if(empty($result['currentBatch'])) {
            return $result;
        }

        $result['logData']['getNextBatchData'] = 'Started. ';

        // Get next batch data
        if ($result['_continue'] === true & ! empty($result['currentBatch']['vpIds']))
        {

            $dbQuery = 'SELECT * FROM vivat_cache_data
                WHERE cache_id = ' . $result['localData']['cacheId'] . ' AND vp_id IN ("' . implode('","', $result['currentBatch']['vpIds']) . '")';
            $query = new query($this->db, $dbQuery);

            if ($query->num_rows() > 0)
            {
                $result['currentBatch']['data'] = $query->getArray();
                $result['currentBatch']['status'] = 1; // success

            } else {

                $result['currentBatch']['data'] = array();

                if(!$this->checkCacheIdIsActual($result['localData']['cacheId'])) {
                    $result['logData']['getNextBatchData'] .= 'New cache id is preparing';
                    $result['currentBatch']['message'] = 'New cache id is preparing. Stop processing current cache.';
                    $result['currentBatch']['status'] = 2; // cache is expired

                } else {

                    $result['logData']['getNextBatchData'] .= 'No data for batch vp_ids, but current cache is still valid';
                    $result['currentBatch']['message'] = 'No data for batch vp_ids, but current cache is still valid';
                    $result['currentBatch']['status'] = 3; // error -- no data for batch!
                }
            }

            $result['logData']['getNextBatchData'] .= 'Records found: ' . count($result['currentBatch']['data']);
        }

        // Return
        return $result;
    }

    private function uploadBatchData($params)
    {
        // Trace the method
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_tracer("UploadSlots::uploadBatchData");
        }

        $result = $params['_result'];

        $result['logData']['uploadBatchData'] = 'Started .';
        $result['logData']['RequestResult'] = 'Started .';

        $execStatus = null;
        $msg = null;

        // Call vivat
        if ($result['_continue'] === true && isset($result['currentBatch']['data']))
        {
            $tempParams = array(
                '_result' => $result,
                'data' => $result['currentBatch']['data'],
            );
            $result = $this->libRequestVivat->calendarUploadSlots($tempParams);
        }

        // Update upload logs
        if ($result['_continue'] === true && ! empty($result['currentBatch']['data']))
        {
            $execStatus = '2';
            $msg = 'Unknown error';

            $status = null;
            $endTime = ', end_time = "' . date('Y-m-d H:i:s') . '"';

            if ($status === null && $result['currentRequest']['responseCode'] === 200)
            {
                $status = 2; // 2 -- UploadCompleted
                $result['currentBatch']['uploadWasSuccess'] = true;

                $execStatus = '1';
                $msg = '';
                $result['logData']['uploadBatchData'] .= 'Succces';
            }

            // Resend on response codes
            if ($status === null && in_array($result['currentRequest']['responseCode'], $this->configVivatApi['calendarUploadSlots']['resendOnResponseCodes']))
            {
                $status = 3; // 3 -- FailedResend

                if($execStatus != '1') {
                    $execStatus = '2';
                    $msg = 'Failed but to be resend';
                    $result['logData']['uploadBatchData'] .= 'Error: ' . 'Failed but to be resend' . ' HTTPRespCode: ' . $result['currentRequest']['responseCode'];
                }
            }

            // Dont resend on response codes
            if ($status === null && in_array($result['currentRequest']['responseCode'], $this->configVivatApi['calendarUploadSlots']['dontResendOnResponseCodes']))
            {
                $status = 4; // 4 -- FailedNoResend

                if($execStatus != '1') {
                    $execStatus = '2';
                    $msg = 'Failed and not to be resend';
                    $result['logData']['uploadBatchData'] .= 'Error: ' . 'Failed and not to be resend' . ' HTTPRespCode: ' . $result['currentRequest']['responseCode'];
                }
            }

            // Resend on unknown response code
            if ($status === null)
            {
                $status = ($this->configVivatApi['calendarUploadSlots']['resendOnUnknownResponseCode'] === true)
                    ? 3  // 2/FailedResend
                    : 4; // 3/FailedNoResend

                if($execStatus != '1') {
                    $execStatus = '2';
                    $msg = 'Upload error';
                }

                $result['logData']['uploadBatchData'] .= 'Error: ' . 'Unknown response code.' . ' HTTPRespCode: ' . $result['currentRequest']['responseCode'];
            }

            // Update db
            $dbQuery = 'UPDATE vivat_cache_upload_log
                SET status = ' . $status . ', attemts = (attemts + 1), request_id = "' . mres($result['currentRequest']['requestData']['requestId']) . '",
                    rec_num = ' . count($result['currentBatch']['data']) . $endTime . '
                WHERE cache_id = ' . $result['localData']['cacheId'] . '
                    AND id IN (' . implode(',', $result['currentBatch']['uploadLogsIds']) . ')';
            $query = new query($this->db, $dbQuery);

        }

        $result['status'] = $execStatus !== null ? $execStatus : '2';
        $result['msg'] = $msg !== null && $msg !== '' ? $msg : 'Unknown error';

        // Return
        return $result;
    }

    private function startNewBatch($params)
    {
        $result = $params['_result'];

        $result['logData']['startNewBatch'] = 'Started. ';

        if ( ! isset($result['batches']))
        {
            $result['currentBatch'] = array();
            $result['batches'] = array();
        }

        if ( ! empty($result['currentBatch']))
        {
            $result['batches'][] = $result['currentBatch'];
            $result['currentBatch'] = array();
        }

        $result['logData']['startNewBatch'] .= 'Success';

        return $result;
    }
}