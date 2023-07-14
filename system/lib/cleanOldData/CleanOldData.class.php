<?php

class CleanOldData
{
    private $libConfig = null;
    private $libDb = null;

    /**
     * CleanOldData constructor.
     */
    public function __construct()
    {
        // Load libs
        $this->libConfig = loadLibClass('config');
        $this->libDb = loadLibClass('db');

        // Set config
        $configEnv = $this->libConfig->get('env');
        $configArr = $this->libConfig->get('cleanOldData');
        $this->configArr = $configArr[$configEnv];
    }

    public function deleteSchedulesOlderThen()
    {
        $result = array(
            '_continue' => true,
            '_error' => '',
            'config' => array(
                'deleteOlderThenNDays' => $this->configArr['schedules']['deleteOlderThenNDays'],
                'debugUseTableCopy' => $this->configArr['schedules']['debugUseTableCopy'],
                'tableSchedules' => 'mod_shedules',
                'tableSchedulesTemp' => 'mod_shedules_temp',
            ),
            'data' => array(
                'tableSchedules' => 'mod_shedules',
                'tableSchedulesTemp' => 'mod_shedules_temp',
                'queryDeleteSchedules' => '',
                'queryDeleteSchedulesTemp' => '',
                'affectedRowsDeleteSchedules' => 0,
                'affectedRowsDeleteSchedulesTemp' => 0,
            ),
        );

        // Create table copy
        if ($result['_continue'] === true && $result['config']['debugUseTableCopy'] === true)
        {
            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableSchedules'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableSchedules'] = $result['currentCopyTable']['tableCopy'];

            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableSchedulesTemp'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableSchedulesTemp'] = $result['currentCopyTable']['tableCopy'];
        }

        // Delete
        if ($result['_continue'] === true)
        {
            $queryWherePart = '`date` < "' . date('Y-m-d', strtotime('-' . $result['config']['deleteOlderThenNDays'] . ' days')) . '"';

            $result['data']['queryDeleteSchedules'] = 'DELETE FROM ' . $result['data']['tableSchedules'] . ' WHERE ' . $queryWherePart;
            $query = new query($this->libDb, $result['data']['queryDeleteSchedules']);
            $result['data']['affectedRowsDeleteSchedules'] = $query->affected_rows();
            $query->free();

            $result['data']['queryDeleteSchedulesTemp'] = 'DELETE FROM ' . $result['data']['tableSchedulesTemp'] . ' WHERE ' . $queryWherePart;
            $query = new query($this->libDb, $result['data']['queryDeleteSchedulesTemp']);
            $result['data']['affectedRowsDeleteSchedulesTemp'] = $query->affected_rows();
            $query->free();
        }

        // Return
        return $result;
    }

    public function deleteReservationsOlderThenWithoutProfileId()
    {
        $result = array(
            '_continue' => true,
            '_error' => '',
            'config' => array(
                'deleteOlderThenNDays' => $this->configArr['reservations']['deleteOlderThenNDays'],
                'debugUseTableCopy' => $this->configArr['reservations']['debugUseTableCopy'],
                'tableReservations' => 'mod_reservations',
            ),
            'data' => array(
                'tableReservations' => 'mod_reservations',
                'queryDeleteReservations' => '',
                'affectedRowsDeleteReservations' => 0,
            ),
        );

        // Create table copy
        if ($result['_continue'] === true && $result['config']['debugUseTableCopy'] === true)
        {
            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableReservations'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableReservations'] = $result['currentCopyTable']['tableCopy'];
        }

        // Delete
        if ($result['_continue'] === true)
        {
            $queryWherePart = '(profile_id IS NULL OR profile_id = 0 OR profile_id = "")
                AND `end` < "' . date('Y-m-d H:i:s', strtotime('-' . $result['config']['deleteOlderThenNDays'] . ' days')) . '"';

            $result['data']['queryDeleteReservations'] = 'DELETE FROM ' . $result['data']['tableReservations'] . ' WHERE ' . $queryWherePart;
            $query = new query($this->libDb, $result['data']['queryDeleteReservations']);
            $result['data']['affectedRowsDeleteReservations'] = $query->affected_rows();
            $query->free();
        }

        // Return
        return $result;
    }

    /**
     * TEMP | Query performance - correct slots booking state
     *
     * @param array $params
     * @return array $result
     */
    public function qpCorrectSlotsBookingState($params)
    {
        $result = array(
            '_continue' => true,
            '_error' => '',
            'config' => array(
                'tableSchedules' => 'mod_shedules',
                'tableReservations' => 'mod_reservations',
                'debugUseTableCopy' => $params['debugUseTableCopy'],
                'debugUseAllRowsNotJustUpcoming' => $params['debugUseAllRowsNotJustUpcoming'],
            ),
            'data' => array(
                'clinicId' => $params['clinicId'],
                'doctorId' => $params['doctorId'],
            ),
        );

        // Create test tables
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableSchedules'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableSchedules'] = $result['currentCopyTable']['tableCopy'];

            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableReservations'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableReservations'] = $result['currentCopyTable']['tableCopy'];
        }

        // Old query
        if ($result['_continue'] === true)
        {
            $queryPartStartTime = ' AND s.start_time >= NOW() ';
            $queryPartStartTime2 = ' AND r.start >= NOW() ';
            if ($result['config']['debugUseAllRowsNotJustUpcoming'] === true)
            {
                $queryPartStartTime = '';
                $queryPartStartTime2 = '';
            }

            $result['data']['oldQueryStartTime'] = microtime(true);

            $result['data']['oldQuery'] = '
                UPDATE ' . $result['data']['tableSchedules'] . ' s SET s.booked = 1, s.locked = 0 
                WHERE s.clinic_id = ' . (int) $result['data']['clinicId'] . '
                    AND s.doctor_id = ' . (int) $result['data']['doctorId'] . '
                    ' . $queryPartStartTime . '
                    AND EXISTS ( 
                        SELECT r.id FROM ' . $result['data']['tableReservations'] . ' r
                        WHERE r.clinic_id = ' . (int) $result['data']['clinicId'] . '
                            AND r.doctor_id = ' . (int) $result['data']['doctorId'] . '
                            ' . $queryPartStartTime2 . '
                            AND r.`status` IN ( 0, 2 )
                            AND
                                (
                                    ( r.start >= s.start_time AND r.start < s.end_time )
                                    OR ( r.end <= s.end_time AND r.end > s.start_time )
                                    OR (r.start >= s.start_time AND r.end <= s.end_time)
                                    OR (r.start <= s.start_time AND r.end >= s.end_time)
                               )
                    )';
            $result['data']['oldQueryAffectedRows'] = doQuery($this->libDb, $result['data']['oldQuery']);

            $result['data']['oldQueryEndTime'] = microtime(true);
            $result['data']['oldQueryExecutionTime'] = $result['data']['oldQueryEndTime'] - $result['data']['oldQueryStartTime'];
        }

        // Recreate test tables
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableSchedules'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableSchedules'] = $result['currentCopyTable']['tableCopy'];

            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['config']['tableReservations'],
            );
            $result = $this->helperCopyTable($tempParams);
            $result['data']['tableReservations'] = $result['currentCopyTable']['tableCopy'];
        }

        // New query
        if ($result['_continue'] === true)
        {
            $result['data']['newQueryStartTime'] = microtime(true);

            // Get schedule ids
            $result['data']['newQuerySelect'] = '
                SELECT r.start, r.end, s.start_time, s.end_time, s.id AS scheduleId
                FROM ' . $result['data']['tableReservations'] . ' AS r
                LEFT JOIN ' . $result['data']['tableSchedules'] . ' AS s ON r.clinic_id = s.clinic_id AND r.doctor_id = s.doctor_id
                WHERE r.clinic_id = ' . (int) $result['data']['clinicId'] . '
                    AND r.doctor_id = ' . (int) $result['data']['doctorId'] . '
                    ' . $queryPartStartTime . '
                    AND r.`status` IN ( 0, 2 )
                    AND
                    (
                        ( r.start >= s.start_time AND r.start < s.end_time )
                        OR ( r.end <= s.end_time AND r.end > s.start_time )
                        OR (r.start >= s.start_time AND r.end <= s.end_time)
                        OR (r.start <= s.start_time AND r.end >= s.end_time)
                    )';
            $query = new query($this->libDb, $result['data']['newQuerySelect']);
            $scheduleIds = array();
            if ($query->num_rows() > 0)
            {
                $rows = $query->getArray();
                foreach ($rows as $row)
                {
                    $scheduleIds[] = (int) $row['scheduleId'];
                }
            }

            // Update schedules
            $result['data']['newQueryAffectedRows'] = 0;
            if ( ! empty($scheduleIds))
            {
                $result['data']['newQueryUpdate'] = '
                    UPDATE ' . $result['data']['tableSchedules'] . ' s SET s.booked = 1, s.locked = 0 
                    WHERE id IN (' . implode(',', $scheduleIds) . ')';
                $result['data']['newQueryAffectedRows'] = doQuery($this->libDb, $result['data']['newQueryUpdate']);
            }

            $result['data']['newQueryEndTime'] = microtime(true);
            $result['data']['newQueryExecutionTime'] = $result['data']['newQueryEndTime'] - $result['data']['newQueryStartTime'];

            $result['data']['newQueryIsFasterBy'] = round(($result['data']['oldQueryExecutionTime'] / $result['data']['newQueryExecutionTime']), 2);
        }

        // Return
        return $result;
    }

    // ---

    /**
     * @param array $params
     *      array _result
     *      string tableOriginal
     * @return array $result
     */
    private function helperCopyTable($params)
    {
        $result = $params['_result'];

        if ( ! isset($result['currentCopyTable']))
        {
            $result['currentCopyTable'] = array();
            $result['copyTables'] = array();
        }

        if ( ! empty($result['currentCopyTable']))
        {
            $result['copyTables'][] = $result['currentCopyTable'];
            $result['currentCopyTable'] = array();
        }

        $result['currentCopyTable']['tableOriginal'] = $params['tableOriginal'];
        $result['currentCopyTable']['tableCopy'] = $this->helperGetCopyTableName($result['currentCopyTable']['tableOriginal']);

        // Delete table copy
        if ($result['_continue'] === true)
        {
            $result['currentCopyTable']['queryDropTableCopy'] = 'DROP TABLE IF EXISTS ' . $result['currentCopyTable']['tableCopy'];
            $query = new query($this->libDb, $result['currentCopyTable']['queryDropTableCopy']);

            if ($query->result !== true)
            {
                $result['_continue'] = false;
            }
        }

        // Create table copy
        if ($result['_continue'] === true)
        {
            $result['currentCopyTable']['queryCreateTableCopy'] = 'CREATE TABLE ' . $result['currentCopyTable']['tableCopy']
                . ' LIKE ' . $result['currentCopyTable']['tableOriginal'];
            $query = new query($this->libDb, $result['currentCopyTable']['queryCreateTableCopy']);

            if ($query->result !== true)
            {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to create table copy';
            }
        }

        // Copy data
        if ($result['_continue'] === true)
        {
            $result['currentCopyTable']['queryCopyData'] = 'INSERT ' . $result['currentCopyTable']['tableCopy']
                . ' SELECT * FROM ' . $result['currentCopyTable']['tableOriginal'];
            $query = new query($this->libDb, $result['currentCopyTable']['queryCopyData']);

            if ($query->result !== true)
            {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to copy data';
            }
        }

        // Return
        return $result;
    }

    /**
     * @param string $tableNameOriginal
     * @return string $tableNameCopy
     */
    private function helperGetCopyTableName($tableNameOriginal)
    {
        return $tableNameOriginal . '_copy_edfrtg';
    }
}