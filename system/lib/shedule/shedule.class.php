<?php
/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		David Akopyan <davids@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		2
 */

// ------------------------------------------------------------------------

class Shedule {

    /** @var object db */
	protected $db;
	/** @var config config */
	protected $cfg;

	/** @var int */
	protected $doctorId;

	/** @var int */
	protected $clinicId;

	public $showOnlyFreeSlots = false;

	/** @var array  */
    private $schedulerFilter = array(
        'paymentFromState'=>null,
        'paymentFromPatient'=>null,
        'remoteServicesOnly'=>null,
        'speciality'=>null,
        'service'=>null,
    );

    /** @var array  */
    private $availableSlots = array();

    /**
     * Shedule constructor.
     */
    public function __construct()
    {
        $this->cfg 	= loadLibClass('config');
        $this->db 	= loadLibClass('db');
        $this->showOnlyFreeSlots = $this->cfg->get('ShowOnlyFreeSlots');
    }

    /**
     * @param $paymentType
     * @param $remoteServicesOnly
     * @param $doctorFilter
     */
    private function parseSchedulerFilter($paymentType,$remoteServicesOnly, $doctorFilter)
    {
        if (empty($paymentType)){
            $this->schedulerFilter['paymentFromState'] = true;
            $this->schedulerFilter['paymentFromPatient'] = true;
        } else {
            $this->schedulerFilter['paymentFromState'] = empty($paymentType[1]) ? false : true;
            $this->schedulerFilter['paymentFromPatient'] = empty($paymentType[2]) ? false : true;
        }

        if (! empty($remoteServicesOnly) && $remoteServicesOnly == 'true'){
            $this->schedulerFilter['remoteServicesOnly'] = true;
        }

        if (! empty($doctorFilter['speciality'])){
            $this->schedulerFilter['speciality'] = $doctorFilter['speciality'];
        }
        if (! empty($doctorFilter['service'])){
            $this->schedulerFilter['service'] = $doctorFilter['service'];
        }
    }

    /**
     * @param $doctorId
     * @param $clinicId
     * @return false|string
     */
    private function getStartTime($doctorId, $clinicId)
    {
        $sql = sprintf(
            "SELECT min_h FROM `%s` WHERE id = %d",
            $this->cfg->getDbTable('doctors', 'self'),
            mres($doctorId)
        );
        $query = new query($this->db, $sql);
        $minH = $query->getOne();

        if (empty($minH)){
            $sql = sprintf(
                "SELECT min_h FROM `%s` WHERE id = %d",
                $this->cfg->getDbTable('clinics', 'self'),
                mres($clinicId)
            );
            $query = new query($this->db, $sql);
            $minH = $query->getOne();
        }

        $minH = $minH ?: 0;

        return date(PIEARSTA_DT_FORMAT, time() + $minH * 3600);
    }

    /**
     * @param $doctorId
     * @param $clinicId
     * @param $periodStartDate
     * @param $periodFinishDate
     * @param $periodStartTime
     * @return array
     */
    private function getAvailableSlots($doctorId, $clinicId, $periodStartDate, $periodFinishDate, $periodStartTime)
    {
        if (empty($doctorId) || empty($clinicId)) {
            return array();
        }

        $sql = sprintf("
SELECT s.* FROM `%s` s 
LEFT JOIN mod_clinics c ON (c.id = s.clinic_id)
WHERE 
    c.enabled = 1   
AND s.doctor_id = %d 
AND s.clinic_id = %d
AND s.`date` >= '%s'
AND s.`date` <= '%s'
AND s.`start_time` > '%s'
AND (s.booked = 0 OR s.booked IS NULL)
AND (s.locked = 0 OR s.locked IS NULL)
ORDER BY s.`start_time` ASC"
            ,$this->cfg->getDbTable('shedule', 'self')
            ,mres($doctorId)
            ,mres($clinicId)
            ,mres($periodStartDate)
            ,mres($periodFinishDate)
            ,mres($periodStartTime)
        );

        $query = new query($this->db, $sql);

        $result = array();

        while ($row = $query->getrow()) {
            $result[$row['date']][] = $row;
        }

        return $result;
    }

    /**
     * @param $doctorId
     * @param $clinicId
     * @param $afterDate
     * @return int
     */
   private function hasAnySlotsAfterDate($doctorId, $clinicId, $afterDate) {

       if (empty($doctorId) || empty($clinicId)) {
           return 0;
       }

       $sql = sprintf("
                SELECT count(*) as cnt FROM `%s`
                WHERE doctor_id = %d
                AND clinic_id = %d
                AND `date` > '%s'"
           ,$this->cfg->getDbTable('shedule', 'self')
           ,mres($doctorId)
           ,mres($clinicId)
           ,mres($afterDate)
       );

       $query = new query($this->db, $sql);
       $row = $query->getrow();
       return (int)$row['cnt'];
   }

    /**
     * @param $doctorId
     * @param $clinicId
     * @param $afterDate
     * @return int
     */
    private function hasAvailableSlotsInFuture($doctorId, $clinicId, $afterDate)
    {
        if (empty($doctorId) || empty($clinicId)) {
            return 0;
        }

        $sql = sprintf("
SELECT count(*) as cnt FROM `%s`
WHERE doctor_id = %d
AND clinic_id = %d
AND `date` > '%s'
AND (booked = 0 OR booked IS NULL)
AND (locked = 0 OR locked IS NULL)"
            ,$this->cfg->getDbTable('shedule', 'self')
            ,mres($doctorId)
            ,mres($clinicId)
            ,mres($afterDate)
        );

        $query = new query($this->db, $sql);
        $row = $query->getrow();
        return (int)$row['cnt'];
    }

    /**
     * @param $clinicId
     * @param $doctorId
     * @param $remoteServicesOnly
     * @param $filterByServiceTitle
     * @return array
     */
    private function getDoctorServicesWithDurationAndFilter($clinicId, $doctorId, $remoteServicesOnly, $filterByServiceTitle)
    {
        $filterByTitle = '';
        if (! empty($filterByServiceTitle)){

            if(is_array($filterByServiceTitle)) {

                $filterByTitle = sprintf("AND d2c.cl_id IN (SELECT c_id FROM `%s` WHERE c_id IN(%s))"
                    ,$this->cfg->getDbTable('classificators', 'details')
                    ,implode(',', $filterByServiceTitle)
                );

            } else {
                $filterByTitle = sprintf("AND d2c.cl_id IN (SELECT c_id FROM `%s` WHERE title = '%s')"
                    ,$this->cfg->getDbTable('classificators', 'details')
                    ,mres($filterByServiceTitle)
                );
            }


        }

        $filterByRemote = '';
        if (! empty($remoteServicesOnly)){
            $filterByRemote = sprintf("AND d2c.cl_id IN (SELECT service_id FROM `%s`)"
                ,'mod_remote_services'
            );
        }

        $sql = sprintf("
SELECT d2c.cl_id as id, cl.piearstaId as piearsta_id, sdc.duration as clinic_duration, sdd.duration as doctor_duration 
FROM `%s` d2c
    LEFT JOIN `%s` cl ON (cl.`id` = `d2c`.`cl_id` AND `cl`.`type` = `d2c`.`cl_type` AND `cl`.`enable` = 1)
    LEFT JOIN `%s` sdc ON (sdc.doctor_id = 0 AND sdc.service_id = `d2c`.`cl_id` AND sdc.clinic_id = %d AND sdc.is_active = 1)
    LEFT JOIN `%s` sdd ON (sdd.doctor_id = %d AND sdd.service_id = `d2c`.`cl_id` AND sdd.clinic_id = %d AND sdd.is_active = 1)
WHERE 1
    AND `d2c`.`d_id` = %d
    AND `d2c`.`cl_type` = '%s'
    %s
    %s
    "
            ,$this->cfg->getDbTable('doctors', 'classificators')
            ,$this->cfg->getDbTable('classificators', 'self')
            ,'mod_service_details'
            ,mres($clinicId)
            ,'mod_service_details'
            ,mres($doctorId)
            ,mres($clinicId)
            ,mres($doctorId)
            ,CLASSIF_SERVICE
            ,$filterByRemote
            ,$filterByTitle
        );

        $query = new query($this->db, $sql);

        $calcDuration = function ($doctor_duration,$clinic_duration) {
            if (! empty($doctor_duration)) {
                return (int)$doctor_duration;
            }
            if (! empty($clinic_duration)) {
                return (int)$clinic_duration;
            }
            return 0;
        };

        $result = array();

        while ($row = $query->getrow()) {

            $result[$row['piearsta_id']] = $calcDuration($row['doctor_duration'],$row['clinic_duration']);
        }

        return $result;
    }

    /**
     * @param $doctorServices
     * @return int
     */
    private function getServiceMinDuration($doctorServices)
    {
        sort($doctorServices);
        return (int)array_shift($doctorServices);
    }

    /**
     * @param $speciality
     * @return array
     */
    private function getDoctorSpecialitiesWithFilter($speciality)
    {
        $speciality = sanitize($speciality);

        if (empty($speciality))
        {
            return array();
        }

        $sql = sprintf("
SELECT c.`code` as `code` FROM `%s` AS c
WHERE `type` = 3
AND c.id IN (SELECT c_id FROM `%s` WHERE title = '%s')
"
            ,$this->cfg->getDbTable('classificators', 'self')
            ,$this->cfg->getDbTable('classificators', 'details')
            ,$speciality
        );
        $query = new query($this->db, $sql);

        $result = array_keys($query->getArray('code'));
        return $result;
    }

    /**
     * @param $doctorId
     * @param $clinicId
     * @param null $periodStart
     * @param null $periodEnd
     * @return array
     */
    private function getTimetable($doctorId,$clinicId, $periodStart = null, $periodEnd = null)
    {

        $periodWhere = '';

        if($periodStart && $periodEnd) {
            $periodWhere .= " AND (
                                    (period_start <= '" . $periodStart . "' AND period_end >= '" . $periodStart . "') OR
                                    (period_start <= '" . $periodEnd . "' AND period_end >= '" . $periodEnd . "') OR
                                    (period_start <= '" . $periodStart . "' AND period_end >= '" . $periodEnd . "') OR
                                    (period_start >= '" . $periodStart . "' AND period_end <= '" . $periodEnd . "')
                                )";
        }


        $sql = sprintf("
SELECT * FROM `%s` 
WHERE is_deleted = 0
AND ( ( services > '') OR (specialties > '') )
AND `doctor_id` = %d
AND `clinic_id` = %d
" . $periodWhere
            ,'mod_timetable_services'
            ,mres($doctorId)
            ,mres($clinicId)
        );

        $query = new query($this->db, $sql);

        $result = $query->getArray();

        return $result;
    }

    /**
     *
     */
    private function addAvailableSlotsMaxTime()
    {
        foreach ($this->availableSlots as $date => $slots) {
            foreach ($slots as $n=>$slot){
                $available = $slot['interval'];
                $endTime = $slot['end_time'];
                foreach ($slots as $nn=>$next){
                    if ($nn <= $n) continue;
                    if ($endTime != $next['start_time']) break;

                    $available += $next['interval'];
                    $endTime = $next['end_time'];
                }
                $this->availableSlots[$date][$n]['availableTime'] = $available;

            }
        }
    }

    /**
     *
     */
    private function addAvailableSlotsTime()
    {
        foreach ($this->availableSlots as $date => $slots) {
            foreach ($slots as $n=>$slot){
                $this->availableSlots[$date][$n]['time_start'] = date("H:i", strtotime($slot['start_time']));
                $this->availableSlots[$date][$n]['time_end'] = date("H:i", strtotime($slot['end_time']));
            }
        }
    }

    /**
     * @param $paymentTypeState
     * @param $paymentTypePatient
     * @return bool
     */
    private function filterAvailableSlotsByPaymentType($paymentTypeState,$paymentTypePatient)
    {
        if (empty($this->availableSlots)){
            return false;
        }
        if ($paymentTypeState == $paymentTypePatient){
            return false;
        }

        foreach ($this->availableSlots as $date => $slots) {
            foreach ($slots as $k=>$slot){
                if ($paymentTypeState && $slot['payment_type'] == 2){
                    unset($this->availableSlots[$date][$k]);
                }
                if ($paymentTypePatient && $slot['payment_type'] == 1){
                    unset($this->availableSlots[$date][$k]);
                }
            }
        }
        return true;
    }

    /**
     * @param $available
     * @param $duration
     * @return bool
     */
    private function checkDuration($available,$duration)
    {
        return $available*1 >= $duration*1;
    }

    /**
     * @param $slot
     * @param $slotTimetable
     * @param $doctorServices
     * @return bool
     */
    private function checkSlotServices($slot,$slotTimetable,$doctorServices, $date = null, $slotIndex = null)
    {
        loadLibClass('logger')->file('services')->addString($slot['date'].' '.$slot['time_start'].' - '.$slot['time_end'])->append();

        $timetableServices = json_decode($slotTimetable['services']);
        $timetableServices = is_array($timetableServices) ? $timetableServices : array();
        loadLibClass('logger')->file('services')->addString($slotTimetable['services'])->append();
        loadLibClass('logger')->file('services')->addString(
            implode(',',array_keys($doctorServices))
        )->append();

        if (empty($timetableServices)){
            loadLibClass('logger')->file('services')->addString('+ timetable services empty')->append();
            return true;
        }

        $timetableDoctorServices = array();
        $servicesInfo = array();

        foreach ($timetableServices as $k=>$service){

            list($serviceId,$serviceDuration,$price) = $service;
            // add timetable info to slot in this->availableSlots

            if($date && $slotIndex !== null && !empty($price)) {
                $servicesInfo[$serviceId] = $price;
            }
            if (isset($doctorServices[$serviceId])){
                $timetableDoctorServices[$serviceId] = $serviceDuration ?: $doctorServices[$serviceId];
            }
        }

        if(!empty($servicesInfo)) {
            $this->availableSlots[$date][$slotIndex]['servicesPriceInfo'] = $servicesInfo;
        }

        if (empty($timetableDoctorServices)){
            loadLibClass('logger')->file('services')->addString('- doctor services empty')->append();
            return false;
        }
        $timetableServiceMinDuration = $this->getServiceMinDuration($timetableDoctorServices);

        if (! $this->checkDuration($slot['availableTime'],$timetableServiceMinDuration)){
            loadLibClass('logger')->file('services')->addArray($slot,'availableTime')->append();
            loadLibClass('logger')->file('services')->addString('- check duration false '.$slot['availableTime'].' < '.$timetableServiceMinDuration)->append();
            return false;
        }

        loadLibClass('logger')->file('services')->addString('+ service ok')->append();

        return true;
    }

    /**
     * @param $slot
     * @param $slotTimetable
     * @param $doctorSpecialities
     * @return bool
     */
    private function checkSlotSpecialities($slot,$slotTimetable,$doctorSpecialities)
    {
        $timetableSpecialities = explode(',',$slotTimetable['specialties']);

        if (empty(array_filter($timetableSpecialities))){
            loadLibClass('logger')->file('services')->addString('+ timetable specialties empty')->append();
            return true;
        }

        $timetableDoctorSpecialities = array();
        foreach ($timetableSpecialities as $k=>$speciality){
            if (in_array($speciality,$doctorSpecialities)){
                $timetableDoctorSpecialities[$speciality] = $speciality;
            }
        }
        if (empty($timetableDoctorSpecialities)){
            return false;
        }
        return true;
    }

    /**
     * @param $date
     * @param $timetableData
     * @return array
     */
    private function timetableFilterByDate($date,$timetableData){
        $wd = date('w', strtotime($date));
        $wd = $wd == 0 ? 7 : $wd;
        $filteredByDayNumber = findByField($timetableData, 'day_number', $wd);
        if (empty($filteredByDayNumber)){
            return array();
        }

        //overlapping period resolving
        $minOrderNum = min(array_column($filteredByDayNumber,'order_num'));
        foreach ($filteredByDayNumber as $k=>$interval){
            if ($interval['order_num'] != $minOrderNum) {
                unset($filteredByDayNumber[$k]);
            }
        }
        return $filteredByDayNumber;
    }

    /**
     * @param $startTime
     * @param $timetableByDate
     * @return array|mixed
     */
    private function timetableFilterByTime($startTime, $timetableByDate){
        $result = array_filter($timetableByDate,function ($v) use ($startTime){
            return ($v['start_time'] <= $startTime) && ($v['end_time'] >= $startTime);
        });

        return $result ? array_shift($result) : array();
    }

    /**
     * @param $doctorServices
     * @param $doctorSpecialities
     * @param $timetableData
     * @param $remoteServicesOnly
     * @param $specialityFilter
     * @param $serviceFilter
     * @return bool
     */
    private function filterAvailableSlots($doctorServices, $doctorSpecialities, $timetableData, $remoteServicesOnly, $specialityFilter, $serviceFilter)
    {
        if (empty($doctorServices)){
            $this->availableSlots = array();
            return true;
        }

        $serviceMinDuration = $this->getServiceMinDuration($doctorServices);

        foreach ($this->availableSlots as $date => $slots) {

            $timetableByDate = $this->timetableFilterByDate($date,$timetableData);

            loadLibClass('logger')->file('filter')->addString('date '.$date)->append();
            loadLibClass('logger')->file('filter')->addArray($timetableByDate,'by date')->append();

            foreach ($slots as $k=>$slot){
                loadLibClass('logger')->file('filter')->addString($slot['time_start'].' - '.$slot['time_end'])->append();

                if (empty($timetableByDate)){
                    if (! $this->checkDuration($slot['availableTime'],$serviceMinDuration)){
                        unset($this->availableSlots[$date][$k]);
                        loadLibClass('logger')->file('filter')->addString('- timetable by date empty & duration is short')->append();
                        continue;
                    }
                }

                $timetableByTime = $this->timetableFilterByTime($slot['time_start'].':00',$timetableByDate);

                loadLibClass('logger')->file('filter')->addArray($timetableByTime,'by time')->append();

                if (empty($timetableByTime)){

                    if (! $this->checkDuration($slot['availableTime'],$serviceMinDuration)){
                        unset($this->availableSlots[$date][$k]);
                        loadLibClass('logger')->file('filter')->addString('- timetable by time empty & duration is short')->append();
                        continue;
                    }
                }

                if ($serviceFilter || $remoteServicesOnly){
                    if (! $this->checkSlotServices(
                        $slot,
                        $timetableByTime,
                        $doctorServices,
                        $date,
                        $k)
                    ){
                        unset($this->availableSlots[$date][$k]);
                        loadLibClass('logger')->file('filter')->addString('- services not allowed')->append();
                        continue;
                    } else {
                        // add services and price info to slot
                    }
                }

                if ($specialityFilter){

                    if (! $this->checkSlotSpecialities(
                        $slot,
                        $timetableByTime,
                        $doctorSpecialities)
                    ){
                        unset($this->availableSlots[$date][$k]);
                        loadLibClass('logger')->file('filter')->addString('- speciality not allowed')->append();
                        continue;
                    }
                }
            }

            if(empty($this->availableSlots[$date])) {
                unset($this->availableSlots[$date]);
            }
        }
    }

    /**
     * @param $doctorServices
     * @param $doctorSpecialities
     * @param $timetableData
     * @param $remoteServicesOnly
     * @param $specialityFilter
     * @param $serviceFilter
     * @return bool|mixed
     */
    private function findFirstAvailableSlot($doctorServices, $doctorSpecialities, $timetableData, $remoteServicesOnly, $specialityFilter, $serviceFilter)
    {
        $serviceMinDuration = $this->getServiceMinDuration($doctorServices);

        $res = array(
            'paid' => null,
            'state' => null,
        );

        foreach ($this->availableSlots as $date => $slots) {

            $timetableByDate = $this->timetableFilterByDate($date,$timetableData);

            foreach ($slots as $k => $slot) {

                if (empty($timetableByDate)){
                    if (! $this->checkDuration($slot['availableTime'],$serviceMinDuration)){
                        continue;
                    }
                }

                $timetableByTime = $this->timetableFilterByTime($slot['time_start'].':00',$timetableByDate);

                if (empty($timetableByTime)){
                    if (! $this->checkDuration($slot['availableTime'],$serviceMinDuration)){
                        continue;
                    }
                }

                if ($serviceFilter || $remoteServicesOnly){
                    if (! $this->checkSlotServices(
                        $slot,
                        $timetableByTime,
                        $doctorServices)
                    ){
                        continue;
                    }
                }
                if ($specialityFilter){
                    if (! $this->checkSlotSpecialities(
                        $slot,
                        $timetableByTime,
                        $doctorSpecialities)
                    ){
                        continue;
                    }
                }

                if(($slot['payment_type'] === '0' || $slot['payment_type'] === '2') &&  $this->schedulerFilter['paymentFromPatient']) {
                    $res['paid'] = $slot;
                }

                if($slot['payment_type'] === '1' && $this->schedulerFilter['paymentFromState']) {
                    $res['state'] = $slot;
                }

                if(!$res['state'] && !$res['paid']) {
                    continue;

                } else {

                    return $res;
                }
            }
        }

        return $res;
    }

    /**
     * @return array
     */
    public function cleanupAvailableSlots()
    {
        return array_filter($this->availableSlots);
    }

    /**
     * @param $afterDate
     * @param $doctorId
     * @param $clinicId
     * @param $doctorServices
     * @param $doctorSpecialities
     * @param $timetableData
     * @return array
     * @throws Exception
     */
    private function getDoctorNearestAvailableSlot($afterDate, $doctorId, $clinicId, $doctorServices, $doctorSpecialities, $timetableData)
    {
        // This is looking for ANY slots after given date

        $overallSlotsAfterDate = $this->hasAnySlotsAfterDate($doctorId, $clinicId, $afterDate);

        if (!$overallSlotsAfterDate
            || empty($doctorServices)){

            return array(
                'status' => 'empty',
                'date' => '',
                'displayDate' => '',
                'afterDate' => $afterDate,
            );
        }

        // This is looking for AVAILABLE (not booked and not locked) slots

        $availableSlotsAfterDate = $this->hasAvailableSlotsInFuture($doctorId, $clinicId, $afterDate);

        if (! $availableSlotsAfterDate ){

            return array(
                'status' => 'busy',
                'date' => '',
                'displayDate' => '',
                'afterDate' => $afterDate,
            );
        }

        $days = $this->cfg->get('nearestSlotLookForward') ? $this->cfg->get('nearestSlotLookForward') : 21;
        $weekDates = $this->getStartAndEndDate($afterDate, $days);
        list($periodStartDate,$periodFinishDate) = array_values($weekDates);

        // Why should we add one more day to period start -- ??? Line commented. Need to be tested carefully
//        $periodStartDate = date('Y-m-d', (strtotime($periodStartDate . '+1 day')));

        $this->availableSlots = $this->getAvailableSlots(
            $doctorId,
            $clinicId,
            $periodStartDate,
            $periodFinishDate,
            $periodStartDate .' 00:00:00'
        );

        if (empty($this->availableSlots)) {

            return array(
                'status' => 'later',
                'date' => $periodFinishDate,
                'displayDate' => date('d.m.Y',strtotime($periodFinishDate)),
                'debug' => array(
                    'periodStartDate' => $periodStartDate,
                    'periodFinishDate' => $periodFinishDate,
                ),
            );
        }

        $this->addAvailableSlotsMaxTime();
        $this->addAvailableSlotsTime();

        $nearestAvailableSlots = $this->findFirstAvailableSlot(
            $doctorServices,
            $doctorSpecialities,
            $timetableData,
            $this->schedulerFilter['remoteServicesOnly'],
            $this->schedulerFilter['speciality'],
            $this->schedulerFilter['service']
        );

        if (!empty($nearestAvailableSlots['paid']) || !empty($nearestAvailableSlots['state'])) {

            $nearestArr = array(
                'paid' => null,
                'state' => null,
            );

            if(!empty($nearestAvailableSlots['paid'])) {
                $nearestArr['paid'] = array(
                    'status' => 'nearest',
                    'date' => $nearestAvailableSlots['paid']['date'],
                    'time' => $nearestAvailableSlots['paid']['start_time'],
                    'displayDate' => date('d.m.Y',strtotime($nearestAvailableSlots['paid']['date'])) . ' ' . date('H:i', strtotime($nearestAvailableSlots['paid']['start_time'])),
                );
            }

            if(!empty($nearestAvailableSlots['state'])) {
                $nearestArr['state'] = array(
                    'status' => 'nearest',
                    'date' => $nearestAvailableSlots['state']['date'],
                    'time' => $nearestAvailableSlots['state']['start_time'],
                    'displayDate' => date('d.m.Y',strtotime($nearestAvailableSlots['state']['date'])) . ' ' . date('H:i', strtotime($nearestAvailableSlots['state']['start_time'])),
                );
            }

            return $nearestArr;
        }


        return array(
            'status' => 'later',
            'date' => $periodFinishDate,
            'displayDate' => date('d.m.Y',strtotime($periodFinishDate))
        );
    }

    /**
     * @param $clinicId
     * @param $doctorId
     * @return int
     */
    private function getMaxD($clinicId, $doctorId)
    {
        // max days
        // first we get from doctor
        // then we get from clinic
        // then we set 90 (default)

        if(!$doctorId || !$clinicId) {
            return 90;
        }

        $maxD = 90;

        $dbQuery = "SELECT max_d FROM mod_clinics WHERE id = " . mres($clinicId);
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $clinicMaxD = intval($query->getrow()['max_d']);

            if($clinicMaxD) {
                $maxD = $clinicMaxD;
            }
        }

        $dbQuery = "SELECT max_d FROM mod_doctors WHERE id = " . mres($doctorId);
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $doctorMaxD = intval($query->getrow()['max_d']);

            if($doctorMaxD) {
                $maxD = $doctorMaxD;
            }
        }

        return $maxD;
    }

    /**
     * @param $startDate
     * @param $days
     * @param $doctorId
     * @param $clinicId
     * @param $doctorFilter
     * @param bool $showEmptyDates
     * @param null $action
     * @return array
     * @throws Exception
     */
    public function getDoctorSchedule($startDate, $days, $doctorId, $clinicId, $doctorFilter, $showEmptyDates = true, $action = null)
    {
        $this->doctorId = $doctorId;
        $this->clinicId = $clinicId;

        if(!$action) {
            $days++;
        }

        loadLibClass('logger')->file('overview')->addString('function getDoctorSchedule')->append();
        loadLibClass('logger')->file('overview')->addArrayFilterEmpty(array(
            'startDate' => $startDate,
            'days' => $days,
            'doctorId' => $doctorId,
            'clinicId' => $clinicId,
        ),'params')->append();

        $this->parseSchedulerFilter(
            getP('payment_type'),
            getP('remote_services'),
            $doctorFilter
        );

        loadLibClass('logger')->file('overview')->addArrayFilterEmpty($this->schedulerFilter,'schedule filter')->append();
        loadLibClass('logger')->file('overview')->addArray($_GET,'schedule filter GET')->append();

        $periodStartTime = $this->getStartTime($doctorId, $clinicId); // current dateTime + minH
        $maxD = $this->getMaxD($clinicId, $doctorId);

        if($showEmptyDates) {

            $weekDates = $this->getStartAndEndDate($startDate, $days);
            list($periodStartDate,$periodFinishDate) = array_values($weekDates);

        } else {

            $periodStartDate = date('Y-m-d', strtotime($periodStartTime));
            $periodFinishDate = date('Y-m-d', strtotime($periodStartDate . '+ ' . $maxD . ' day'));
        }

        if($action) {
            $periodFinishDate = date('Y-m-d', strtotime($periodStartDate . '+ ' . $maxD . ' day'));
        }

        $periodFinishDate = date('Y-m-d', strtotime($periodFinishDate . '- 1 day'));

        $this->availableSlots = $this->getAvailableSlots(
            $doctorId,
            $clinicId,
            $periodStartDate,
            $periodFinishDate,
            $periodStartTime
        );

        loadLibClass('logger')->file('overview')->addArrayFilterEmpty(
            array_map(function ($v){ return count($v);},$this->availableSlots),
            'available slots')->append();

        $this->filterAvailableSlotsByPaymentType(
            $this->schedulerFilter['paymentFromState'],
            $this->schedulerFilter['paymentFromPatient']
        );

        loadLibClass('logger')->file('overview')->addArrayFilterEmpty(
            array_map(function ($v){ return count($v);},$this->availableSlots),
            'filtered by payment type')->append();

        $this->addAvailableSlotsMaxTime();
        $this->addAvailableSlotsTime();

        $doctorServices = $this->getDoctorServicesWithDurationAndFilter($clinicId,$doctorId,$this->schedulerFilter['remoteServicesOnly'],$this->schedulerFilter['service']);
        loadLibClass('logger')->file('overview')->addArray($doctorServices,'doctor services')->append();
        $doctorSpecialities = $this->getDoctorSpecialitiesWithFilter($this->schedulerFilter['speciality']);
        loadLibClass('logger')->file('overview')->addArray($doctorSpecialities,'doctor specialities')->append();
        $timetableData = $this->getTimetable($doctorId,$clinicId, $periodStartDate, $periodFinishDate);

        $this->filterAvailableSlots(
            $doctorServices,
            $doctorSpecialities,
            $timetableData,
            $this->schedulerFilter['remoteServicesOnly'],
            $this->schedulerFilter['speciality'],
            $this->schedulerFilter['service']
        );

        $this->cleanupAvailableSlots();

        $lastDate = null;

        if($action && $showEmptyDates) {

            $allFiltered = array_filter($this->availableSlots);
            $allFiltered = array_keys($allFiltered);
            $lastDate = $allFiltered[count($allFiltered) - 1];

            $endDate = date('Y-m-d', strtotime($startDate . '+' . ($days - 1) . ' day'));

            $this->availableSlots = array_filter($this->availableSlots, function ($k) use($startDate, $endDate) {
                return $k >= date('Y-m-d', strtotime($startDate)) && $k <= date('Y-m-d', strtotime($endDate));
            }, ARRAY_FILTER_USE_KEY);

            unset($allFiltered);
        }

        loadLibClass('logger')->file('overview')->addArrayFilterEmpty(
            array_map(function ($v){ return count($v);},$this->availableSlots),
            'filtered')->append();

        $nearestAvailableSlots = false;

        if (empty($this->availableSlots) && $showEmptyDates){

            $weekDates = $this->getStartAndEndDate($startDate, $days);
            list($periodStartDate,$periodFinishDate) = array_values($weekDates);

            $oldFinishDate = $periodFinishDate;

            // in this case we decrement finish date by one day because the analysis of available nearest slots
            // takes all dates more than passed

            $dto = new DateTime($oldFinishDate);
            $dto->modify('- 1 days');
            $newFinishDate = $dto->format('Y-m-d');

            $nearestAvailableSlots = $this->getDoctorNearestAvailableSlot(
                $newFinishDate,
                $doctorId,
                $clinicId,
                $doctorServices,
                $doctorSpecialities,
                $timetableData
            );
            $this->availableSlots = array();
        }

        // for widget logic we should know the last date with available slots

        if($showEmptyDates) {

            $result = array(
                'slots' => $this->availableSlots,
                'prev' => $periodStartDate > date('Y-m-d'),
                'next' => true,
                'nearest' => $nearestAvailableSlots,
                'action' => $action,
            );

            if($action) {

                $avDays = array_keys($this->availableSlots);
                $dateFrom = $avDays[count($avDays) - 1];
                $next = $lastDate > $dateFrom;

                $result['next'] = isset($next) ? $next : true;
                $result['lastDate'] = isset($lastDate) ? $lastDate : false;
            }

            if($action && empty(array_keys($this->availableSlots)) && !$lastDate) {
                $result['lastDate'] = date('Y-m-d');
            }

        } else {

            // get previous

            $prev = false;
            $next = false;

            $this->availableSlots = array_filter($this->availableSlots);

            $avDays = array_keys($this->availableSlots);
            $lastDate = $avDays[count($avDays) - 1];


            if($action == 'prev') {

                // filter array of timeslots -- get all where date <= startDate, than get from result $days number of elements

                $filteredAvailable = array_filter($this->availableSlots, function ($k) use ($startDate, &$next) {

                    if($k > date('Y-m-d', strtotime($startDate))) {
                        $next = true;
                    }

                    return $k <= date('Y-m-d', strtotime($startDate));

                }, ARRAY_FILTER_USE_KEY );

                $beforeSlice = $filteredAvailable;

                if(count($beforeSlice) <= $days) {

                    $filteredAvailable = array_slice($this->availableSlots, 0, $days);
                    $prev = false;

                } else {

                    $filteredAvailable = array_slice($filteredAvailable, -$days, $days);
                    $prev = true;
                }

            } else {

                $filteredAvailable = array_filter($this->availableSlots, function ($k) use ($startDate, &$prev) {

                    if($k < date('Y-m-d', strtotime($startDate))) {
                        $prev = true;
                    }

                    return $k >= date('Y-m-d', strtotime($startDate));

                }, ARRAY_FILTER_USE_KEY );

                $beforeSlice = $filteredAvailable;

                if(count($beforeSlice) <= $days) {

                    $filteredAvailable = array_slice($this->availableSlots, -$days, $days);
                    $next = false;

                } else {

                    $filteredAvailable = array_slice($filteredAvailable, 0, $days);
                    $next = true;
                }
            }

            $result = array(
                'slots' => $filteredAvailable,
                'prev' => $prev,
                'next' => $next,
                'nearest' => false,
                'lastDate' => $lastDate,
                'debug' => array(
                    'action' => $action,
                    'prev' => $prev,
                    'next' => $next,
                    'startDate' => $startDate,
                    'lastDate' => $lastDate,
                    'availableDays' => $avDays,
                    'filteredDaysBeforeSlice' => $beforeSlice,
                    'filteredDays' => $filteredAvailable,
                ),
            );
        }

        return $result;
    }

    /**
     * @param $date
     * @param $days
     * @param bool $doctorId
     * @param bool $clinicId
     * @param array $filters
     * @param array $doctorFilter
     * @return array
     * @throws Exception
     */
    public function getDoctorShedule($date, $days, $doctorId = false, $clinicId = false, $filters = array(), $doctorFilter = array())
    {
        $where = "";

        if (!empty($filters)) {

            if (isset($filters['payment_type']) && !empty($filters['payment_type']) && sizeof($filters['payment_type'])==1 && key($filters['payment_type']) != '0') {
                // key($filters['payment_type']) == 0 : Valsts & Pacients
                // key($filters['payment_type']) == 1 : Valsts
                // key($filters['payment_type']) == 2 : Pacients
                if (sizeof($filters['payment_type'])==1) {
                    $where .= " AND `payment_type` IN (0,".key($filters['payment_type']).") ";
                }
            }

            if(isset($filters['doctors_filters'])) {
                if(isset($filters['doctors_filters']['main'])) {
                    $doctorFilters = $filters['doctors_filters']['main'];
                } elseif ($filters['doctors_filters']['fast']) {
                    $doctorFilters = $filters['doctors_filters']['fast'];
                }
            }

            $service = null;
            $specialty = null;
            $remote = false;
            $serviceIds = array();
            $specialtyCodes = array();

            // if has filter by service -- collect requested service ids
            if(isset($doctorFilters, $doctorFilters['doctors_filter_services']) || isset($filters['remote_services'])) {

                $servicesArray = array();

                /** @var serviceDetails $sd */
                $sd = loadLibClass('serviceDetails');

                if($doctorFilters['doctors_filter_services'] != 'false') {

                    $service = $doctorFilters['doctors_filter_services'];

                    $serviceQuery = "SELECT c.*, ci.title as title, ci.c_id as service_id FROM mod_classificators c 
                            LEFT JOIN mod_classificators_info ci ON (c.id = ci.c_id)
                            LEFT JOIN mod_doctors_to_classificators d2c ON (d2c.cl_id = ci.c_id)
                            WHERE
                                c.type = 4 AND 
                                ci.title = '" . trim($service) . "' AND 
                                d2c.d_id = " . $doctorId;

                    $serQ = new query($this->db, $serviceQuery);

                    if($serQ->num_rows()) {

                        while ($s = $serQ->getrow()) {
                            $serviceIds[] = $s['piearstaId'] ? $s['piearstaId'] : $s['service_id'];
                        }
                    }


                }

                if($filters['remote_services'] == 'true') {

                    $remote = true;

                    $serviceIds = array();

                    $serviceQuery =     "SELECT c.id, c.piearstaId, d2c.d_id 
                                        FROM mod_remote_services rs 
                                        LEFT JOIN mod_classificators c ON (c.id = rs.service_id)
                                        LEFT JOIN mod_doctors_to_classificators d2c ON (c.id = d2c.cl_id)
                                        WHERE d2c.d_id = " . $doctorId;

                    $serQ = new query($this->db, $serviceQuery);

                    if($serQ->num_rows()) {

                        while ($s = $serQ->getrow()) {
                            $serviceIds[] = $s['piearstaId'] ? $s['piearstaId'] : $s['id'];
                        }
                    }
                }

                if(count($serviceIds) > 0) {
                    $servicesArray = $sd->getServiceDurationByDoctor($serviceIds, $clinicId, $doctorId);
                }
            }

            // if has filter by specialty -- collect requested specialty codes
            if(isset($doctorFilters, $doctorFilters['doctors_filter_specialty'])) {

                if($doctorFilters['doctors_filter_specialty'] != 'false') {
                    $specialty = $doctorFilters['doctors_filter_specialty'];
                    //
                    $specialtyQuery = "SELECT c.*, ci.title as title, ci.c_id as specialty_id FROM mod_classificators c 
                            LEFT JOIN mod_classificators_info ci ON (c.id = ci.c_id)
                            WHERE
                                c.type = 3 AND
                                ci.title = '" . trim($specialty) . "'";
                    $spQ = new query($this->db, $specialtyQuery);

                    if($spQ->num_rows()) {

                        while($spRow = $spQ->getrow()) {
                            $specialtyCodes[] = $spRow['code'];
                        }
                    }
                }
            }

            // else, ja Valsts & Pacients vai neviens nav izvelēts - radas visi
        }

        $ttWhere = array();
        if ($clinicId) {
            $ttWhere[] = "`clinic_id` = '" . mres($clinicId) . "'";
            $where .= " AND `clinic_id` = '" . mres($clinicId) . "' ";
        }

        if ($doctorId) {
            if (is_array($doctorId)) {
                $ttWhere[] .= "`doctor_id` IN (" . implode(",", $doctorId) . ")";
                $where .= " AND `doctor_id` IN (" . implode(",", $doctorId) . ") ";
            } else {
                $ttWhere[] .= "`doctor_id` = '" . mres($doctorId) . "'";
                $where .= " AND `doctor_id` = '" . mres($doctorId) . "' ";
            }
        }
        $ttWhere = $ttWhere ? ' AND '. implode(' AND ',$ttWhere) : '';

        $minH = 0;

        if ($doctorId) {
            $dbQuery = "SELECT min_h
							FROM `" . $this->cfg->getDbTable('doctors', 'self') . "`
							WHERE 1
								AND `id` = '" . mres($doctorId) . "'";
            $query = new query($this->db, $dbQuery);
            $minH = $query->getOne();
        }

        if ($minH > 0) {
            $where .= " AND `start_time` > '" . date(PIEARSTA_DT_FORMAT, time() + $minH * 3600) . "' ";
        } else {
            if ($clinicId) {
                $dbQuery = "SELECT min_h
							FROM `" . $this->cfg->getDbTable('clinics', 'self') . "`
							WHERE 1
								AND `id` = '" . mres($clinicId) . "'";
                $query = new query($this->db, $dbQuery);
                $minH = $query->getOne();
                if ($minH > 0) {
                    $where .= " AND `start_time` > '" . date(PIEARSTA_DT_FORMAT, time() + $minH * 3600) . "' ";
                } else {
                    $where .= " AND `start_time` > '" . date(PIEARSTA_DT_FORMAT, time() + 3600) . "' ";
                }
            } else {
                $where .= " AND `start_time` > '" . date(PIEARSTA_DT_FORMAT, time() + 3600) . "' ";
            }
        }

        $result = array();
        $weekDates = $this->getStartAndEndDate($date, $days);

        if($this->showOnlyFreeSlots) {
            $where .= " AND (booked = 0 OR booked IS NULL) ";
            $where .= " AND (locked = 0 OR locked IS NULL) ";
        }

        $result = array();
        $weekDates = $this->getStartAndEndDate($date, $days);

        if($this->showOnlyFreeSlots) {
            $where .= " AND (booked = 0 OR booked IS NULL) ";
            $where .= " AND (locked = 0 OR locked IS NULL) ";
        }

        // get timeslots

        $dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('shedule', 'self') . "`
							WHERE 1
								AND `date` >= '" . mres($weekDates['week_start']) . "'
								AND `date` <= '" . mres($weekDates['week_end']) . "'"
            . $where .
            "ORDER BY `start_time` ASC";

        $query = new query($this->db, $dbQuery);

        while ($row = $query->getrow()) {

            $row['time_start'] = date("H:i", strtotime($row['start_time']));
            $row['time_end'] = date("H:i", strtotime($row['end_time']));

            if($service || $remote) {
                $row['availableTime'] = getAvailableTime($row['clinic_id'], $row['doctor_id'], $row['start_time'])['availableTime'];
            }

            $result[$row['date']][] = $row;
        }

        // apply timetable filters by service and specialty

        $timetableData = array();

        if($service || $specialty || $remote) {

            $ttsQuery = "SELECT * FROM mod_timetable_services 
                        WHERE
                            is_deleted = 0" . $ttWhere . "";

            $ttsq = new query($this->db, $ttsQuery);

            $timetableData = $ttsq->getArray();

            // apply filter
            $result = $this->applyTimetableFilters($timetableData, $result, $service, $specialty, $servicesArray, $specialtyCodes, $remote);
        }


        //Check next and prev page existence

        $nearestLookForwardDays = $this->cfg->get('nearestSlotLookForward') ? $this->cfg->get('nearestSlotLookForward') : 21;

        $period = array();
        $period[] = "`date` > '" . mres($weekDates['week_end']) . "'";

        if ($remote){
            $period[] = "`start_time` <= '" . date(PIEARSTA_DT_FORMAT, time() + $nearestLookForwardDays * 86400) . "'";
        }

        $period = ' AND '. implode(' AND ',$period);

        $next = false;
        $dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('shedule', 'self') . "`
							WHERE 1 " . $period . $where;
        $query = new query($this->db, $dbQuery);

        if ($query->num_rows() > 0) {

            if($specialty || $service || $remote || (!$specialty && !$service && !$remote)) {

                $nextArray = array();

                while($nextAnalizeRow = $query->getrow()) {

                    $nextAnalizeRow['time_start'] = date("H:i", strtotime($nextAnalizeRow['start_time']));
                    $nextAnalizeRow['time_end'] = date("H:i", strtotime($nextAnalizeRow['end_time']));

                    if($service || $remote) {
                        $nextAnalizeRow['availableTime'] = getAvailableTime($nextAnalizeRow['clinic_id'], $nextAnalizeRow['doctor_id'], $nextAnalizeRow['start_time'])['availableTime'];
                    }

                    $nextArray[$nextAnalizeRow['date']][] = $nextAnalizeRow;
                }

                $nextArray = $this->applyTimetableFilters($timetableData, $nextArray, $service, $specialty, $servicesArray, $specialtyCodes, $remote);

                if(count($nextArray)) {

                    foreach ($nextArray as $el) {

                        if(is_array($el) && count($el)) {

                            $next = true;
                            break;
                        }
                    }
                }

            } else {

                $next = true;
            }
        }

        $prev = false;

        $dbQuery = "SELECT *
							FROM `" . $this->cfg->getDbTable('shedule', 'self') . "`
							WHERE 1
								AND `date` < '" . mres($weekDates['week_start']) . "'
								AND `date` >= '" . date("Y-m-d") . "'"
            . $where;
        $query = new query($this->db, $dbQuery);

        if ($query->num_rows() > 0) {

            if($specialty || $service || $remote) {

                $prevArray = array();

                while($prevAnalizeRow = $query->getrow()) {

                    $prevAnalizeRow['time_start'] = date("H:i", strtotime($prevAnalizeRow['start_time']));
                    $prevAnalizeRow['time_end'] = date("H:i", strtotime($prevAnalizeRow['end_time']));

                    if($service || $remote) {
                        $prevAnalizeRow['availableTime'] = getAvailableTime($prevAnalizeRow['clinic_id'], $prevAnalizeRow['doctor_id'], $prevAnalizeRow['start_time'])['availableTime'];
                    }

                    $prevArray[$prevAnalizeRow['date']][] = $prevAnalizeRow;
                }

                $prevArray = $this->applyTimetableFilters($timetableData, $prevArray, $service, $specialty, $servicesArray, $specialtyCodes, $remote);

                if(count($prevArray)) {

                    foreach ($prevArray as $el) {

                        if(is_array($el) && count($el)) {

                            $prev = true;
                            break;
                        }
                    }
                }

            } else {

                $prev = true;
            }
        }

        // get nearest free slot

        // we use nextArray here (already filtered)
        $nearest = $nextArray;

        if(is_array($nearest)) {

            $res = false;

            if(count($nearest)) {

                $nearest = array_slice($nearest, 0, 1);

                foreach ($nearest as $date => $tsArr) {

                    if(count($tsArr) > 0 && is_array($tsArr[0]) && count($tsArr[0]) > 0) {
                        $res = $tsArr[0]['date'];
                        break;
                    }
                }
            }

            $nearest = $res;
        }

        return array('data' => $result, 'prev' => $prev, 'next' => $next, 'nearest' => $nearest);
    }

    /**
     * @param $timetableData
     * @param $arrayOfTs
     * @param null $service
     * @param null $specialty
     * @param array $servicesArray
     * @param array $specialtyCodes
     * @param bool $remote
     * @return mixed
     */
    private function applyTimetableFilters($timetableData, $arrayOfTs, $service = null, $specialty = null, $servicesArray = array(), $specialtyCodes = array(), $remote = false)
    {
        // if no appropriate filters received, we do nothing and return passed array of timeslots
        if(!$service && !$specialty && !$remote) {
            return $arrayOfTs;
        }

        // no timetable data, so we apply only services duration filter
        if( ( $service || $remote ) &&
            ( !is_array($timetableData) || count($timetableData) < 1)
        ) {

            foreach ($arrayOfTs as $date => $data) {

                if(is_array($data) && count($data) > 0) {

                    // loop thru timeSlots
                    foreach ($data as $key => $timeSlot) {

                        foreach ($servicesArray as $servId => $duration) {

                            // if no service duration data, we set it equal to timeslot interval
                            $thisServiceDuration = $duration ? intval($duration) : intval($timeSlot['interval']);

                            // if this service suitable by duration...
                            if($thisServiceDuration <= $timeSlot['availableTime']) {

                                // analize next slots for services available

                                $overallTime = 0;
                                $prevSlot = $timeSlot;

                                for($i = $key; $i < count($data); $i++) {

                                    if(
                                        $data[$i]['time_start'] <= $prevSlot['time_end'] &&
                                        $data[$i]['locked'] == 0 &&
                                        $data[$i]['booked'] == 0
                                    ) {

                                        $overallTime += $data[$i]['interval'];
                                    }

                                    $prevSlot = $data[$i];
                                }

                                // timeslots provide enough time for this service, so we include it to temporary array
                                if($thisServiceDuration > $overallTime) {
                                    unset($data[$key]);
                                }
                            }
                        }
                    }

                    if(count($data) < 1) {
                        unset($arrayOfTs[$date]);
                    }
                }
            }

            return $arrayOfTs;
        }

        // // //
        // timetableData not empty

        // cycle to collect  available services for ts
        if($service || $remote) {

            foreach ($arrayOfTs as $date => $data) {

                // get timeTableServicesData for day of the week
                $wd = date('w', strtotime($date));
                $wd = $wd == 0 ? 7 : $wd;
                $filteredByDayNumber = findByField($timetableData, 'day_number', $wd);

                if(is_array($data) && count($data) > 0) {
                    // loop thru timeSlots
                    foreach ($data as $key => $timeSlot) {

                        $sTime = $timeSlot['time_start'] . ':00';
                        $eTime = $timeSlot['time_end'] . ':00';

                        $stTimeFiltered = filterByTime($filteredByDayNumber, $sTime, $eTime);

                        if(count($stTimeFiltered) < 1) {
                            $timeSlot['services'] = 'all';
                            $data[$key] = $timeSlot;
                            continue;
                        }

                        $slotServices = array();

                        foreach ($stTimeFiltered as $ttRecord) {

                            if($ttRecord['services']) {

                                $servOverridesArrayRaw = json_decode($ttRecord['services']);

                                if($servOverridesArrayRaw && count($servOverridesArrayRaw)) {

                                    foreach ($servOverridesArrayRaw as $servOverride) {

                                        $slotServices[] = $servOverride[0];
                                    }
                                }

                            } else {

                                $slotServices = 'all';
                                break;
                            }
                        }

                        $timeSlot['services'] = $slotServices;

                        $data[$key] = $timeSlot;
                    }
                }

                $arrayOfTs[$date] = $data;
            }
        }

        // now another cycle to decide, which timeslots to exclude from timetable

        foreach ($arrayOfTs as $date => $data) {

            // get timeTableServicesData for day of the week
            $wd = date('w', strtotime($date));
            $wd = $wd == 0 ? 7 : $wd;
            $filteredByDayNumber = findByField($timetableData, 'day_number', $wd);

            if(is_array($data) && count($data) > 0) {

                // loop thru timeSlots
                foreach ($data as $key => $timeSlot) {

                    $sTime = $timeSlot['time_start'] . ':00';
                    $eTime = $timeSlot['time_end'] . ':00';

                    // get timeTableServicesData for given slot's time interval
                    $stTimeFiltered = filterByTime($filteredByDayNumber, $sTime, $eTime);

                    if($service || $remote) {

                        // collect services, suitable by duration and timeslot's available time

                        $tempServicesIds = array();

                        foreach ($servicesArray as $servId => $duration) {

                            // perform further check only if slot contains suitable services
                            if(
                                ( is_array($timeSlot['services']) && in_array($servId, $timeSlot['services']) ) ||
                                $timeSlot['services'] == 'all'
                            ) {

                                // if no service duration data, we set it equal to timeslot interval
                                $thisServiceDuration = $duration ? intval($duration) : intval($timeSlot['interval']);

                                // if this service suitable by duration...
                                if($thisServiceDuration <= $timeSlot['availableTime']) {

                                    // analize next slots for services available

                                    $overallTime = 0;
                                    $prevSlot = $timeSlot;

                                    for($i = $key; $i < count($data); $i++) {

                                        if(
                                            $data[$i]['time_start'] <= $prevSlot['time_end'] &&
                                            $data[$i]['locked'] == 0 &&
                                            $data[$i]['booked'] == 0
                                        ) {

                                            if(
                                                ( is_array($data[$i]['services']) && in_array($servId, $data[$i]['services']) ) ||
                                                $data[$i]['services'] == 'all'
                                            ) {
                                                $overallTime += $data[$i]['interval'];
                                            }
                                        }

                                        $prevSlot = $data[$i];
                                    }

                                    // timeslots provide enough time for this service, so we include it to temporary array
                                    if($thisServiceDuration <= $overallTime) {
                                        $tempServicesIds[] = $servId;
                                    }
                                }
                            }
                        }
                    }

                    // we have no timetable records for this time interval
                    if(count($stTimeFiltered) < 1) {

                        // if we have no suitable services for this timeslot, we set excludeTs to true
                        $excludeTs = count($tempServicesIds) < 1;

                        // we have timetable services data
                    } else {

                        $servOverridesArray = array();
                        $specOverridesArray = array();

                        foreach ($stTimeFiltered as $ttRecord) {

                            $skipCheck = true;
                            if(($service || $remote) && $ttRecord['services']) {
                                $skipCheck = false;
                            }

                            if($skipCheck && $specialty && $ttRecord['specialties']) {
                                $skipCheck = false;
                            }

                            if($skipCheck) {
                                continue;
                            }

                            if($ttRecord['services'] && ($service || $remote) ) {

                                $servOverridesArrayRaw = json_decode($ttRecord['services']);

                                if($servOverridesArrayRaw && count($servOverridesArrayRaw)) {

                                    foreach ($servOverridesArrayRaw as $servOverride) {

                                        $servOverridesArray[] = $servOverride[0];
                                    }
                                }
                            }

                            if($ttRecord['specialties'] && $specialty) {

                                $specOverridesArray = explode(',', $ttRecord['specialties']);
                            }
                        }

                        // whether to exclude this timeSlot?
                        $excludeTs = true;

                        if($service || $remote) {
                            $hasServices = array_intersect($servOverridesArray, $tempServicesIds);
                        }

                        if($specialty) {
                            $hasSpecialties = array_intersect($specOverridesArray, $specialtyCodes);
                        }

                        if ($service && $specialty) {
                            if(
                                (count($hasServices) || $ttRecord['services'] == '') &&
                                (count($hasSpecialties) || $ttRecord['specialties'] == '')
                            ) {
                                $excludeTs = false;
                            }
                        } elseif ($service) {
                            if(count($hasServices) || $ttRecord['services'] == '') {
                                $excludeTs = false;
                            }
                        } elseif ($specialty) {
                            if(count($hasSpecialties) || $ttRecord['specialties'] == '') {
                                $excludeTs = false;
                            }
                        }

                        if($excludeTs && $remote) {

                            if(count($hasServices) || $ttRecord['services'] == '') {
                                $excludeTs = false;
                            }
                        }
                    }

                    // exclude ts and exclude date if it has no slots
                    if($excludeTs) {

                        unset($arrayOfTs[$date][$key]);

                        if(count($arrayOfTs[$date]) < 1) {

                            unset($arrayOfTs[$date]);
                        }
                    }
                }
            }
        }

        return $arrayOfTs;
    }

    /**
     * @param bool $doctorId
     * @param bool $clinicId
     * @return bool|string
     */
    public function getLastDate($doctorId = false, $clinicId = false)
    {
        $where = "";

        if ($clinicId) {
            $where .= " AND `clinic_id` = '" . mres($clinicId) . "' ";
        }

        if ($doctorId) {
            if (is_array($doctorId)) {
                $where .= " AND `doctor_id` IN (" . implode(",", $doctorId) . ") ";
            } else {
                $where .= " AND `doctor_id` = '" . mres($doctorId) . "' ";
            }
        }

        if($this->showOnlyFreeSlots) {
            $where .= " AND (booked = 0 OR booked IS NULL) ";
            $where .= " AND (locked = 0 OR locked IS NULL) ";
        }

        $dbQuery = "SELECT `start_time`
                        FROM `" . $this->cfg->getDbTable('shedule', 'self') . "`
                        WHERE 1 "
                        . $where .
                    "ORDER BY `start_time` DESC";
        $query = new query($this->db, $dbQuery);
        return $query->getOne();
    }

    /**
     * @param $date
     * @param int $days
     * @return mixed
     * @throws Exception
     */
    public function getStartAndEndDate($date, $days = 13)
    {
        $dto = new DateTime($date);
        $ret['week_start'] = $dto->format('Y-m-d');
        $dto->modify('+' . $days . ' days');
        $ret['week_end'] = $dto->format('Y-m-d');
        return $ret;
    }

    /**
     * @param $date
     * @param int $days
     * @return array
     * @throws Exception
     */
    public function getMonthDays($date, $days = 13)
    {
        $result = array();
        $dto = new DateTime($date);
        $result[gL('month_' . $dto->format('F'))] = 1;

        for ($i = 1; $i <= $days; $i++) {
            $dto->modify('+1 days');
            if (isset($result[gL('month_' . $dto->format('F'))])) {
                $result[gL('month_' . $dto->format('F'))]++;
            } else {
                $result[gL('month_' . $dto->format('F'))] = 1;
            }
        }

        return $result;
    }

    /**
     * @param $date
     * @param int $days
     * @return array
     * @throws Exception
     */
    public function     getWeekDays($date, $days = 13)
    {
        $result = array();
        $dto = new DateTime($date);
        $result[] = array(
            'full' => $dto->format('Y-m-d'),
            'd' => $dto->format('d'),
            'wd' => gL($dto->format('D')),
            'm' => $dto->format('m'),
            'wdn' => $dto->format('N'),
        );
        for ($i = 1; $i <= $days; $i++) {
            $dto->modify('+1 days');
            $result[] = array(
                'full' => $dto->format('Y-m-d'),
                'd' => $dto->format('d'),
                'wd' => gL($dto->format('D')),
                'm' => $dto->format('m'),
                'wdn' => $dto->format('N'),
            );

        }

        return $result;
    }

}

?>
