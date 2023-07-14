<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2021, BlueBridge.
 */

/**
 * class paymentResponse
 *
 * Emulates initiate payment response from billingSystem
 */
class paymentResponse
{
    public $payment_url = null;
    public $error = false;
    public $message = false;
    public $payment_uuid = '';
    public $payment_id = '';
    public $insuranceCompany = null;
    public $insurancePolicy = null;
    public $dcCard = null;

    /**
     * @param $success
     * @param $url
     */
    public function setResponse($success, $url, $paymentUuid = null, $paymentId = null)
    {
        $this->payment_url = $url;
        $this->error = !$success;

        if($paymentUuid) {
            $this->payment_uuid = $paymentUuid;
        }

        if($paymentId) {
            $this->payment_id = $paymentId;
        }
    }

    /**
     * @param $id
     */
    public function setPaymentId($id)
    {
        $this->payment_id = $id;
    }

    /**
     * @param $uuid
     */
    public function setPaymentUuid($uuid)
    {
        $this->payment_uuid = $uuid;
    }

    /**
     * @param $insurancePolicy
     */
    public function setInsurancePolicy($insurancePolicy)
    {
        $this->insurancePolicy = $insurancePolicy;
    }

    /**
     * @param $dcCard
     */
    public function setDcCard($dcCard)
    {
        $this->dcCard = $dcCard;
    }

    /**
     * @param $insuranceCompany
     */
    public function setInsuranceCompany($insuranceCompany)
    {
        $this->insuranceCompany = $insuranceCompany;
    }

    /**
     * @param string $message
     */
    public function setError($message = 'Unknown error')
    {
        $this->error = true;
        $this->message = $message;
    }
}

?>