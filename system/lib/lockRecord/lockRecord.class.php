<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2019, BlueBridge.
 */

/**
 * Class lockRecord
 */
class lockRecord
{
    /** @var config  */
    private $cfg;
    /** @var null | int */
    private $id = null;
    /** @var array  */
    private $lockRecord = array();
    private $table = null;

    /**
     * lockRecord constructor.
     * @param null $id
     */
    public function __construct($id = null)
    {
        $this->cfg = loadLibClass('config');
        $this->table = $this->cfg->getDbTable('shedule', 'lock');

        if($id) {
            $lockRecord = $this->getLockRecordById($id);
            if($lockRecord) {
                $this->id = $id;
                $this->lockRecord = $lockRecord;
            }
        }
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function createLockRecord($data)
    {
        $slotsOk = true;

        if(!empty($data['slots'])) {

            $checkDbQuery = "SELECT * FROM mod_shedules 
                            WHERE
                                id IN (".$data['slots'].")";

            $checkQuery = new query($this->cfg->db, $checkDbQuery);

            if($checkQuery->num_rows()) {

                $allSlots = $checkQuery->getArray();

                foreach ($allSlots as $slot) {

                    if($slot['booked'] > 0 || $slot['locked'] > 0) {
                        $slotsOk = false;
                        break;
                    }
                }

            } else {

                $slotsOk = false;
            }
        }

        if($slotsOk) {

            $id = saveValuesInDb($this->table, $data);
            $this->id = $id;
            $this->lockRecord = $this->getLockRecordById($id);

            if($id) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $id
     * @param $data
     * @return bool|string
     */
    public function updateLockRecord($id, $data)
    {
        saveValuesInDb($this->table, $data, $id);
        $this->lockRecord = $this->getLockRecordById($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function setLockRecord($id)
    {
        if($id) {

            $lockRecord = $this->getLockRecordById($id);

            if($lockRecord) {
                $this->id = $id;
                $this->lockRecord = $lockRecord;

                return true;
            }
        }

        return false;
    }

    /**
     * @return array|bool|int
     */
    public function getLockRecord()
    {
        if($this->id) {
            return $this->lockRecord;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getLockRecordId()
    {
        return $this->id;
    }

    /**
     * @param $reservationId
     * @return bool
     */
    public function setLockRecordByReservationId($reservationId)
    {
        $dbQuery = "SELECT * FROM " . $this->table . " WHERE reservation_id = " .$reservationId;
        $query = new query($this->cfg->db, $dbQuery);

        $lockRecord = null;
        if($query->num_rows()) {
            $lockRecord = $query->getrow();
        }

        if($lockRecord) {
            $this->id = $lockRecord['id'];
            $this->lockRecord = $lockRecord;
            return true;
        }

        return false;
    }

    /**
     * @param $orderId
     * @return array|int|null
     */
    public function setLockRecordByOrderId($orderId)
    {
        $dbQuery = "SELECT * FROM " . $this->table . " WHERE order_id = " .$orderId;
        $query = new query($this->cfg->db, $dbQuery);

        $lockRecord = null;
        if($query->num_rows()) {
            $lockRecord = $query->getrow();
        }

        if($lockRecord) {
            $this->id = $lockRecord['id'];
            $this->lockRecord = $lockRecord;
            return true;
        }

        return false;
    }

    /**
     * @param $scheduleId
     * @return bool
     */
    public function setLockRecordByScheduleId($scheduleId)
    {
        $dbQuery = "SELECT * FROM " . $this->table . " WHERE schedule_id = " . $scheduleId;
        $query = new query($this->cfg->db, $dbQuery);

        $lockRecord = null;

        if($query->num_rows()) {
            $lockRecord = $query->getrow();
        }

        if($lockRecord) {
            $this->id = $lockRecord['id'];
            $this->lockRecord = $lockRecord;

            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @return array|bool|int
     */
    private function getLockRecordById($id)
    {
        if($id) {

            $dbQuery = "SELECT * FROM " . $this->table . " WHERE 1 AND id = " . $id;
            $query = new query($this->cfg->db, $dbQuery);

            if($query->num_rows()) {
                return $query->getrow();
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
        $dbQuery = "UPDATE " . $this->table . "
                    SET status = " . $status . "
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);

        $this->lockRecord['status'] = $status;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->lockRecord['status'];
    }

    /**
     * delete current lock record
     * and unlock slots
     *
     * @param bool $unlockSlots
     */
    public function deleteLockRecord($unlockSlots = true)
    {
        $start = $this->lockRecord['datetime_from'];
        $end = $this->lockRecord['datetime_thru'];
        $doctorId = $this->lockRecord['doctor_id'];
        $clinicId = $this->lockRecord['clinic_id'];

        deleteFromDbById($this->table, $this->id);

        if($unlockSlots) {
            lockSheduleData($start, $end, $doctorId, $clinicId, 0);
        }

        $this->id = null;
        $this->lockRecord = array();
    }

    /**
     * delete current lock record
     * and change slots from locked to booked
     */
    public function bookSlots()
    {
        if(!$this->id) {
            return false;
        }

        $start = $this->lockRecord['datetime_from'];
        $end = $this->lockRecord['datetime_thru'];
        $doctorId = $this->lockRecord['doctor_id'];
        $clinicId = $this->lockRecord['clinic_id'];

        deleteFromDbById($this->table, $this->id);
        bookSheduleData($start, $end, $doctorId, $clinicId, 1);

        $this->id = null;
        $this->lockRecord = array();

        return true;
    }

    public function lockSlots()
    {
        $start = $this->lockRecord['datetime_from'];
        $end = $this->lockRecord['datetime_thru'];
        $doctorId = $this->lockRecord['doctor_id'];
        $clinicId = $this->lockRecord['clinic_id'];

        lockSheduleData($start, $end, $doctorId, $clinicId, 1);
    }

    public function unlockSlots()
    {
        $start = $this->lockRecord['datetime_from'];
        $end = $this->lockRecord['datetime_thru'];
        $doctorId = $this->lockRecord['doctor_id'];
        $clinicId = $this->lockRecord['clinic_id'];

        if(empty($start) || empty($end) || empty($doctorId) || empty($clinicId)) {
            return false;
        }

        lockSheduleData($start, $end, $doctorId, $clinicId, 0);

        $this->lockRecord['slots'] = null;
    }

    public function prolongateExpirationTime()
    {
        $extendBy = intval($this->cfg->get('extend_lock_time_by'));
        $newExpire = date(PIEARSTA_DT_FORMAT, time() + $extendBy);

        $data = array(
            'expire_time' => $newExpire,
        );

        $this->updateLockRecord($this->id, $data);

        return true;
    }

    public function setInTheNameExpirationTime()
    {
        $extendBy = intval($this->cfg->get('shedule_lock_time_in_the_name_of'));
        $newExpire = date(PIEARSTA_DT_FORMAT, time() + $extendBy);

        $data = array(
            'expire_time' => $newExpire,
        );

        $this->updateLockRecord($this->id, $data);

        return true;
    }

    public function reduceExpirationTime()
    {
        $lockTime = intval($this->cfg->get('shedule_lock_time'));

        $newExpire = date(PIEARSTA_DT_FORMAT, (time() + $lockTime));

        $data = array(
            'expire_time' => $newExpire,
        );

        $this->updateLockRecord($this->id, $data);

        return true;
    }

    /**
     * @param $status
     * @return bool
     */
    public function prolongExpirationTimeForPaymentInProcess($status)
    {
        $config = $this->cfg->get('billing_system');
        $everyPayLockTimes = $config['everyPay']['payment_unfinished_statuses_lock_time'];
        $lockTimeByStatus= $everyPayLockTimes[$status];

        if (empty($lockTimeByStatus)){
            $lockTimeByStatus = $everyPayLockTimes['default_lock_time'];
        }

        $lr = $this->lockRecord;

        $newExpire = date('Y-m-d H:i:s', strtotime($lr['lock_time'] . ' + ' . $lockTimeByStatus . ' minutes'));

        $data = array(
            'expire_time' => $newExpire,
        );

        $this->updateLockRecord($this->id, $data);

        return true;
    }

}

?>