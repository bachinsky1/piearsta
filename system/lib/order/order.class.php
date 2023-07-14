<?php

/**
 * Order class
 * works with current order, when user creates new reservation
 */
class Order
{
    public $id;
    public $orderInfoId;
    public $currentStatus;
    public $order;
    public $serviceDuration;

    public $db;
    public $cfg;
    public $logger;

    public function __construct($orderData = null)
    {
        $this->db = &loadLibClass('db');
        $this->cfg = &loadLibClass('config');
        $this->logger = &loadLibClass('logDb');

        if($orderData) {
            $this->createOrder($orderData);
        }
    }

    /**
     * @return array|int|null
     */
    public function getOrder()
    {
        $dbQuery =  "SELECT * FROM " . $this->cfg->getDbTable('orders', 'self') .
                    " WHERE id = " . $this->id;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return null;
    }

    /**
     * @param $trId
     * @return array|int|null
     */
    public function getOrderByTransactionId($trId)
    {
        $dbQuery = "SELECT * FROM " . $this->cfg->getDbTable('orders', 'self') . "
                    WHERE
                        transaction_id = " . $trId;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            $order = $query->getrow();

            $this->id = $order['id'];
            $this->setOrder($this->id);
            return $order;
        }

        $this->id = null;
        $this->orderInfoId = null;
        $this->currentStatus = null;
        $this->order = null;

        return null;
    }

    /**
     * @return string|null
     */
    public function getServiceDuration()
    {
        $orderDetails = $this->getOrderDetails();
        return $orderDetails[0]['service_duration'];

//        $doctorId = $this->order['doctor_id'];
//        $orderDetails = $this->getOrderDetails();
//        $serviceId = $orderDetails[0]['service_id'];
//        $dbQuery = "SELECT length_minutes FROM " . $this->cfg->getDbTable('doctors', 'classificators') . "
//                    WHERE 1
//                        AND d_id = " . $doctorId . "
//                        AND cl_id = " . $serviceId . "
//                     LIMIT 1";
//        $query = new query($this->db, $dbQuery);
//        if($query->num_rows()) {
//            /** @var string $duration */
//            $duration = $query->getrow()['length_minutes'];
//
//            if(!$duration || $duration == 0) {
//                $resQuery = "SELECT * FROM " . $this->cfg->getDbTable('reservations', 'self') . " WHERE id = " . $this->order['reservation_id'];
//                $q = new query($this->db, $resQuery);
//                if($q->num_rows()) {
//                    $res = $q->getrow();
//                    $start = date_create($res['start']);
//                    $end = date_create($res['end']);
//                    $diff = date_diff($end, $start);
//                    $duration = $diff->i;
//                } else {
//                    $duration = 0;
//                }
//            }
//
//            $this->serviceDuration = $duration;
//            return $duration;
//        }
//        $this->serviceDuration = null;
//        return null;
    }

    // Creates new order
    public function createOrder($orderData) {
        // create new order
        $this->id = saveValuesInDb($this->cfg->getDbTable('orders', 'self'), $orderData);
        $this->setStatus(ORDER_STATUS_NEW);
        // create new order info
        $dbData = array();
        $dbData['order_id'] = $this->id;
        $this->orderInfoId = saveValuesInDb($this->cfg->getDbTable('orders', 'info'), $dbData);
        $this->order = $this->getOrder();
    }

    // Sets current order in class instance -- id, currentStatus and orderInfoId
    public function setOrder($id) {
       $this->id = $id;
       $this->order = $order = $this->getOrder();
       $this->currentStatus = $order['status'];
       $orderInfo = $this->getOrderInfo();
       $this->orderInfoId = $orderInfo['id'];
    }

    // Updates order with given data
    public function updateOrder($data)
    {
        // log update order
        $this->logger->log('orders', array(
            'order_id' => $this->id,
            'activity' => 1,
        ));
        saveValuesInDb($this->cfg->getDbTable('orders', 'self'), $data, $this->id);
        $this->order = $this->getOrder();
    }

    // Completely deletes order and related order_info and order_details
    public function deleteOrder() {
        $dbQuery =  "DELETE FROM " . $this->cfg->getDbTable('orders', 'details') .
            " WHERE order_id = " . $this->id;
        doQuery($this->db, $dbQuery);
        $dbQuery =  "DELETE FROM " . $this->cfg->getDbTable('orders', 'info') .
            " WHERE order_id = " . $this->id;
        doQuery($this->db, $dbQuery);
        $dbQuery =  "DELETE FROM " . $this->cfg->getDbTable('orders', 'self') .
            " WHERE id = " . $this->id;
        doQuery($this->db, $dbQuery);
        $this->id = null;
        $this->order = null;
        $this->orderInfoId = null;
        $this->currentStatus = null;
    }

    /**
     * @return array|int|null
     */
    public function getOrderInfo()
    {
        $dbQuery =  "SELECT * FROM " . $this->cfg->getDbTable('orders', 'info') .
            " WHERE order_id = " . $this->id;
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return null;
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function setOrderInfo($data)
    {
        return saveValuesInDb($this->cfg->getDbTable('orders', 'info'), $data, $this->orderInfoId);
    }

    /**
     * @return array|null
     */
    public function getOrderDetails()
    {
        $dbQuery =  "SELECT * FROM " . $this->cfg->getDbTable('orders', 'details') .
            " WHERE order_id = " . $this->id;
        $query = new query($this->db, $dbQuery);

        $result = array();

        if($query->num_rows()) {

            while($row = $query->getrow()) {
                $result[] = $row;
            }
            return $result;
        }

        return null;
    }

    /**
     * @param $data
     * @param null $id
     * @return bool|string
     */
    public function setOrderDetails($data, $id = null)
    {
        // if id passed, we update existing record
        if($id) {
            return saveValuesInDb($this->cfg->getDbTable('orders', 'details'), $data, $id);
        }

        // no id passed - we create new one
        $data['order_id'] = isset($data['order_id']) ? $data['order_id'] : $this->id;
        return saveValuesInDb($this->cfg->getDbTable('orders', 'details'), $data);
    }

    /**
     * @return null
     */
    public function getOrderId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->currentStatus;
    }

    /*
     *  *** SET STATUSES ***
     */

    /**
     * @param $status
     * @param $reason
     */
   public function setStatus($status, $reason = null)
   {
       $this->changeStatus($status, $reason);
   }

    /**
     * @param string $status_reason
     */
    public function cancelOrder($status_reason = '')
    {
        $this->changeStatus(ORDER_STATUS_CANCELED, $status_reason);

        // log update order
        $this->logger->log('orders', array(
            'order_id' => $this->id,
            'activity' => 5,
            'status' => 3,
            'status_reason' => $status_reason,
        ));
    }

    /**
     * @param int $status
     * @param string $status_reason
     */
    private function changeStatus($status = 0, $status_reason = null)
    {

        $data = array();
        $data['status'] = strval($status);

        if($status_reason) {
            $data['status_reason'] = $status_reason;
        }

        $data['status_datetime'] = date(PIEARSTA_DT_FORMAT, time());
        $result = saveValuesInDb($this->cfg->getDbTable('orders', 'self'), $data, $this->id);
        $this->currentStatus = $status;
    }

}

?>