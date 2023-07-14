<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2019, BlueBridge.
 */

/**
 * Class transaction
 */
class transaction
{
    /** @var config  */
    private $cfg;
    private $id = null;
    /** @var array  */
    private $transaction = array();
    private $table = null;

    /**
     * transaction constructor.
     * @param null $id
     */
    public function __construct($id = null)
    {
        $this->cfg = loadLibClass('config');
        $this->table = $this->cfg->getDbTable('transactions', 'self');

        if($id) {
            $transaction = $this->getTransactionById($id);
            if($transaction) {
                $this->id = $id;
                $this->transaction = $transaction;
            }
        }
    }

    /**
     * @param $data
     * @return bool|string
     */
    public function createTransaction($data)
    {
        $trId = saveValuesInDb($this->table, $data);

        if(!empty($trId)) {

            $this->setTransaction($trId);
        }

        return $trId;
    }

    /**
     * @param $id
     * @param $data
     * @return bool|string
     */
    public function updateTransaction($id, $data)
    {
        saveValuesInDb($this->table, $data, $id);
        $this->transaction = $this->getTransactionById($this->id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function setTransaction($id)
    {
        $transaction = $this->getTransactionById($id);

        if($transaction) {
            $this->id = $id;
            $this->transaction = $transaction;
            return true;
        }

        return false;
    }

    /**
     * @return array|bool|int
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * @return null|int
     */
    public function getTransactionId()
    {
        return $this->id;
    }

    /**
     * @param $orderId
     * @return array|int|null
     */
    public function setTransactionByOrderId($orderId)
    {
        $dbQuery = "SELECT * FROM " . $this->table . " WHERE order_id = " .$orderId;
        $query = new query($this->cfg->db, $dbQuery);

        $transaction = null;
        // order has only one transaction
        if($query->num_rows() == 1) {
            $transaction = $query->getrow();
        // order has MORE than one transaction
        } elseif ($query->num_rows() > 1) {
            /** @var order $order */
            $order = loadLibClass('order');
            $order->setOrder($orderId);
            $orderData = $order->getOrder();
            $trIdFromOrder = $orderData['transaction_id'];

            if($trIdFromOrder) {
                $dbQuery = "SELECT * FROM " . $this->table . " WHERE id = " . $trIdFromOrder;
                $query = new query($this->cfg->db, $dbQuery);
                if($query->num_rows()) {
                    $transaction = $query->getrow();
                }
            }
        }

        if($transaction) {
            $this->id = $transaction['id'];
            $this->transaction = $transaction;
            return true;
        }

        return false;
    }

    /**
     * @param $uuid
     * @return bool
     */
    public function getTransactionByUuid($uuid)
    {
        $dbQuery = "SELECT * FROM " . $this->table . " WHERE payment_uuid = '" .$uuid . "'";
        $query = new query($this->cfg->db, $dbQuery);

        $transaction = null;
        $this->id = null;
        $this->transaction = null;

        if($query->num_rows()) {
            $transaction = $query->getrow();
        }

        if($transaction) {
            $this->id = $transaction['id'];
            $this->transaction = $transaction;
            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @return array|bool|int
     */
    private function getTransactionById($id)
    {
        $dbQuery = "SELECT * FROM " . $this->table . " WHERE 1 AND id = " . $id;
        $query = new query($this->cfg->db, $dbQuery);

        if($query->num_rows()) {
            return $query->getrow();
        }
        return false;
    }

    /**
     * @param $status
     * @param null $errCode
     * @param null $errMessage
     */
    public function setStatus($status, $errCode = null, $errMessage = null)
    {
        $set = " SET status = " . $status;

        if($errCode) {
            $set .= ", error_code = " . $errCode;
        }

        if($errMessage) {
            $set .= ", error_message = '" . $errMessage . "'";
        }

        $dbQuery = "UPDATE " . $this->table . $set . "
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->transaction['status'];
    }

    public function getError()
    {
        return array(
            'code' => $this->transaction['error_code'],
            'message' => $this->transaction['error_message'],
        );
    }

    /**
     * @param $method
     */
    public function setPaymentMethod($method)
    {
        $set = " SET payment_method = '" . $method . "'";

        $dbQuery = "UPDATE " . $this->table . $set . "
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @param $number
     */
    public function setCardNumber($number)
    {
        $set = " SET pan = '" . $number . "'";

        $dbQuery = "UPDATE " . $this->table . $set . "
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @param $code
     */
    public function setAuthCode($code)
    {
        $set = " SET auth_code = '" . $code . "'";

        $dbQuery = "UPDATE " . $this->table . $set . "
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @param $amount
     */
    public function setRefundAmount($amount)
    {
        $dbQuery = "UPDATE " . $this->table . "
         SET refunded_amount = '" . $amount . "'
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @param $response
     */
    public function saveEveryPayResponse($response)
    {
        $dbQuery = "UPDATE " . $this->table . "
         SET request_response = '" . json_encode($response) . "'
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    /**
     * @param $status
     */
    public function setPaymentStatus($status)
    {
        $dbQuery = "UPDATE " . $this->table . "
         SET payment_status = '" . $status . "',
         updated = '" . date('Y-m-d H:i:s') . "'
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    public function updateEmailSent()
    {
        $dbQuery = "UPDATE " . $this->table . "
         SET email_sent = '" . date('Y-m-d H:i:s') . "'
                    WHERE 1
                        AND id = " . $this->id;

        doQuery($this->cfg->db, $dbQuery);
    }

    public function reInitClass()
    {
        $this->transaction = array();
        $this->id = null;
    }

}

?>