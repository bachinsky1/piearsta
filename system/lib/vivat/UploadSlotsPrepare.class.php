<?php

class UploadSlotsPrepare
{
    private $db = null;

    public function __construct()
    {
        $this->db = loadLibClass('db');
    }

    public function test($params)
    {
        $result = array(
            '_continue' => true,
            '_error' => '',
            'data' => array(
                'tableOriginalDoctorsToClinics' => 'mod_doctors_to_clinics',
                'tableOriginalSchedules' => 'mod_shedules',
                'tableCopyDoctorsToClinics' => 'mod_doctors_to_clinics_test_vhisf',
                'tableCopySchedules' => 'mod_shedules_test_vhisf',
                'cacheId' => 1,
                'runSelectCacheDataQuery' => true,
            ),
            'dataTest' => array(),
            'dbQueries' => array(),
        );

        // Prepare test data
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->createTestTables($tempParams);

            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->setTestData($tempParams);

            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->insertTestData($tempParams);
        }

        // Set select-cache-data query
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->setSelectCacheDataQuery($tempParams);
        }

        // Insert cache data
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
            );
            $result = $this->insertCacheData($tempParams);
        }

        // Return
        return $result;
    }

    // ---

    private function createTestTables($params)
    {
        $result = $params['_result'];

        // Create test tables
        if ($result['_continue'] === true)
        {
            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['data']['tableOriginalDoctorsToClinics'],
                'tableCopy' => $result['data']['tableCopyDoctorsToClinics'],
                'copyData' => false,
            );
            $result = $this->db->helperCopyTable($tempParams);

            $tempParams = array(
                '_result' => $result,
                'tableOriginal' => $result['data']['tableOriginalSchedules'],
                'tableCopy' => $result['data']['tableCopySchedules'],
                'copyData' => false,
            );
            $result = $this->db->helperCopyTable($tempParams);
        }

        // Return
        return $result;
    }

    private function setTestData($params)
    {
        $result = $params['_result'];

        // Set doctors to clinics
        if ($result['_continue'] === true)
        {
            $result['dataTest']['doctorsToClinics'] = array(
                array(
                    'd_id' => 1,
                    'c_id' => 1,
                    'vp_id' => 'vpid-01',
                ),
                array(
                    'd_id' => 2,
                    'c_id' => 1,
                    'vp_id' => 'vpid-01',
                ),
                array(
                    'd_id' => 3,
                    'c_id' => 1,
                    'vp_id' => 'vpid-01',
                ),
                array(
                    'd_id' => 4,
                    'c_id' => 1,
                    'vp_id' => 'null',
                ),

                array(
                    'd_id' => 1,
                    'c_id' => 2,
                    'vp_id' => 'vpid-02',
                ),
                array(
                    'd_id' => 2,
                    'c_id' => 2,
                    'vp_id' => 'vpid-02',
                ),
                array(
                    'd_id' => 3,
                    'c_id' => 2,
                    'vp_id' => 'vpid-02',
                ),
                array(
                    'd_id' => 4,
                    'c_id' => 2,
                    'vp_id' => 'null',
                ),
            );
        }

        // Set schedules
        if ($result['_continue'] === true)
        {
            $result['dataTest']['schedules'] = array();

            $dates = array(
                date('Y-m-d', strtotime('+5 days')),
                date('Y-m-d', strtotime('+6 days')),
            );

            foreach ($dates as $date)
            {
                foreach ($result['dataTest']['doctorsToClinics'] as $i => $row)
                {
                    $nextDayDate = date('Y-m-d', strtotime($date) + (60 * 60 * 24));
                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 07:30:00',
                        'end_time' => $date . ' 07:55:00',
                        'booked' => 1,
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 25,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 00:10:00',
                        'end_time' => $date . ' 00:35:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 25,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 07:55:00',
                        'end_time' => $date . ' 08:15:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 20,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 08:00:00',
                        'end_time' => $date . ' 08:15:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 15,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 08:15:00',
                        'end_time' => $date . ' 08:45:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 30,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 08:45:00',
                        'end_time' => $date . ' 09:15:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 30,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 09:45:00',
                        'end_time' => $date . ' 10:15:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 30,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 22:10:00',
                        'end_time' => $date . ' 22:20:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 10,
                    );

                    $result['dataTest']['schedules'][] = array(
                        'doctor_id' => $row['d_id'],
                        'clinic_id' => $row['c_id'],
                        'date' => $date,
                        'start_time' => $date . ' 23:45:00',
                        'end_time' => $nextDayDate . ' 00:20:00',
                        'booked' => '0',
                        'locked' => '0',
                        'payment_type' => 2,
                        'piearstaId' => 'null',
                        'interval' => 35,
                    );
                }
            }
        }

        // Set vp ids
        if ($result['_continue'] === true)
        {
            $result['dataTest']['vpIds'] = array();
            foreach ($result['dataTest']['doctorsToClinics'] as $i => $row)
            {
                if ( ! empty($row['vp_id']) && $row['vp_id'] !== 'null' && ! in_array($row['vp_id'], $result['dataTest']['vpIds']))
                {
                    $result['dataTest']['vpIds'][] = $row['vp_id'];
                }
            }
        }

        // Return
        return $result;
    }

    private function insertTestData($params)
    {
        $result = $params['_result'];

        if ($result['_continue'] === true)
        {
            foreach ($result['dataTest']['doctorsToClinics'] as $row)
            {
                saveValuesInDb($result['data']['tableCopyDoctorsToClinics'], $row);
            }

            foreach ($result['dataTest']['schedules'] as $row)
            {
                saveValuesInDb($result['data']['tableCopySchedules'], $row);
            }
        }

        // Return
        return $result;
    }

    private function setSelectCacheDataQuery($params)
    {
        $result = $params['_result'];

        if ($result['_continue'] === true)
        {
            $currentDateTime = date('Y-m-d H:i:s', time());

            // @see cron/prepareVaccinationSlots.php
            $result['dbQueries']['selectCacheData'] = '
                SELECT ' . $result['data']['cacheId'] . ' as cache_id, dtc.vp_id as vp_id,
                    DATE_FORMAT (s.start_time, "%Y-%m-%d") as date,
                    DATE_FORMAT (s.start_time, "%Y-%m-%d %H:%i:%s") as interval_start,
                    DATE_FORMAT (MAX(s.end_time), "%Y-%m-%d %H:%i:%s") as interval_end,
                    COUNT(s.id) as free_slots
                FROM ' . $result['data']['tableCopySchedules'] . ' AS s
                LEFT JOIN ' . $result['data']['tableCopyDoctorsToClinics'] . ' dtc ON (dtc.c_id = s.clinic_id AND dtc.d_id = s.doctor_id)
                WHERE s.booked=0 AND s.locked = 0
                    AND s.start_time > "' . $currentDateTime . '"
                    AND dtc.vp_id IN ("' . implode('","', $result['dataTest']['vpIds']) . '")
                GROUP BY dtc.vp_id, DATE_FORMAT (s.start_time, "%d.%m.%Y"), hour(s.start_time) DIV 2
                ORDER BY s.clinic_id, s.doctor_id, date ASC, interval_start ASC';

            if ($result['data']['runSelectCacheDataQuery'] === true)
            {
                $query = new query($this->db, $result['dbQueries']['selectCacheData']);

                $result['selectCacheDataRows'] = $query->getArray();
            }

        }

        // Return
        return $result;
    }

    private function insertCacheData($params)
    {
        $result = $params['_result'];

        // Truncate tables
        if ($result['_continue'] === true)
        {
            $result['dbQueries']['truncateCacheVplist'] = 'TRUNCATE TABLE vivat_cache_vplist';
            doQuery($this->db, $result['dbQueries']['truncateCacheVplist']);

            $result['dbQueries']['truncateCacheData'] = 'TRUNCATE TABLE vivat_cache_data';
            doQuery($this->db, $result['dbQueries']['truncateCacheData']);
        }

        // Insert cache vp ids
        if ($result['_continue'] === true)
        {
            foreach ($result['dataTest']['vpIds'] as $vpId)
            {
                $result['dbQueries']['insertVpids'] = array();
                $insertQuery = 'INSERT INTO vivat_cache_vplist (cache_id, vp_id) VALUES ("' . $result['data']['cacheId'] . '", "' . $vpId . '")';
                $result['dbQueries']['insertVpids'][] = $insertQuery;
                $query = new query($this->db, $insertQuery);
            }

        }

        // Insert cache data
        if ($result['_continue'] === true)
        {
            $result['dbQueries']['insertCacheData'] = 'INSERT INTO vivat_cache_data (cache_id, vp_id, `date`, interval_start, interval_end, free_slots)
                (' . $result['dbQueries']['selectCacheData'] . ')';
            $query = new query($this->db, $result['dbQueries']['insertCacheData']);
        }

        // Return
        return $result;
    }
}
