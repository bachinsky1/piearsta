<?php

/**
 * @author		Andrey Voroshnin
 * @copyright	Copyright (c) 2022, BlueBridge.
 */

/**
 * class dcSubscription
 */
class dcSubscription
{
    /** @var config  */
    private $cfg;

    /** @var array|null */
    private $subsription = null;

    /**
     * subscription constructor
     */
    public function __construct()
    {
        $this->cfg = loadLibClass('config');
    }

    /**
     * @param $userId
     * @return array|null
     */
    public function init($userId)
    {
        $currDateTime = date('Y-m-d H:i:s', time());
        $currDate = date('Y-m-d', time());

        // we only use subscriptions and products, the rest of tables -- customers etc are for reports only

        $dbQuery = "
            SELECT s.id,
                   s.profile_id,
                   s.customer_id,
                   s.agreement_id,
                   s.start_datetime,
                   s.end_datetime,
                   s.pay_thru_date,
                   p.title as productTitle,
                   p.clinic_id as product_clinic, 
                   p.network_id as product_network 
            FROM ins_subscriptions s
            LEFT JOIN ins_products p ON (p.id = s.product_id) 
            WHERE
                s.profile_id = ".$userId." AND 
                s.start_datetime <= '$currDateTime' AND 
                s.end_datetime >= '$currDateTime' AND 
                p.start_datetime <= '$currDateTime' AND 
                p.end_datetime >= '$currDateTime' AND 
                s.pay_thru_date >= '$currDate'
        ";

        $query = new query($this->cfg->db, $dbQuery);

        if($query->num_rows()) {
            $this->subsription = $query->getRow();
        } else {
            $this->subsription = null;
        }

        return $this->getSubscriptionInfo();
    }

    /**
     * @return array|null
     */
    public function getSubscriptionInfo()
    {
        return $this->subsription;
    }

}

?>