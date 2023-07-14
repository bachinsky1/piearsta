#!/usr/bin/php-cgi
<?php

require_once(dirname(__FILE__) . "/../vendor/autoload.php");
define("PIEARSTA_DT_FORMAT", 'Y-m-d H:i:s');

// This cron is for unfinished payments check using EveryPay platform

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../system/func/other.func.php");

/** @var config $cfg */
$cfg = loadLibClass('config');

/** @var BillingSystem $billingSystem */
$billingSystem = loadLibClass('billingSystem');

$debug = $cfg->get('debug');

$checkLimit = $cfg->get('payment_check_limit');

$checkingTime = $cfg->get('reservation_checking_time');

error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));


// // //
// FOR DEBUG (here we can emulate any response status of payment)
// // //

$localDebug = $cfg->get('cron_payment_debug');
$emulateStatus = $cfg->get('emulate_payment_state');

// // //


// 1. Find all records where reservation status is 0 or 5
$time = strtotime('-30 minutes');

if($localDebug) {
    $time = strtotime('-1 minutes');
}

$dbQuery = "SELECT r.id AS reservation_id,
       r.created AS reservation_created, 
       r.start AS start,
       o.status AS order_status, 
       o.payment_reference AS payment_reference, 
       o.id AS order_id, 
       o.transaction_id AS transaction_id
                FROM " . $cfg->getDbTable('reservations', 'self') . " r 
                LEFT JOIN " . $cfg->getDbTable('orders', 'self') . " o ON (r.id = o.reservation_id) 
                    WHERE 1
                    AND (r.status = " . RESERVATION_WAITS_PAYMENT . " OR r.status = " . RESERVATION_WAITS_CONFIRMATION. ") 
                    AND ((o.status =  '" . ORDER_STATUS_PENDING . "' AND o.payment_reference  <> '' AND o.payment_reference IS NOT NULL) OR (o.status = '" . ORDER_STATUS_PRELIMINARY_PAID . "' AND o.payment_reference  <> '' AND o.payment_reference IS NOT NULL))  
                    AND r.created <=  '" . $time . "'
                    ORDER BY r.start
                    LIMIT " . $checkLimit;

$query = new query($mdb, $dbQuery);

// 2. Collect records where payment_reference is not empty,
//only everyPay payments have payment references

$dataCollected = array();
$orderIds = array();

if ($query->num_rows()) {

    if ($debug) {
        echo PHP_EOL . PHP_EOL . '****Cron started****' . PHP_EOL;
        echo $query->num_rows() . " records found\n";
    }

    while ($row = $query->getrow()) {

        $dataCollected[] = $row;

    }
} else {

    if ($debug) {
        echo PHP_EOL . PHP_EOL . '****Cron started****' . PHP_EOL;
        echo PHP_EOL . "=== No lock records found. ===" . PHP_EOL;
    }
}

// 3. We send requests to EveryPay for each of record to check payment status
// if status settled we store card number, auth code and used method if we receive such information
//and scenario => paymentSuccess, if status unfinished than paymentInProcess, otherwise paymentFail scenario

// these arrays are for collecting reservations with ORDER_STATUS_PENDING
$paymentPendingSuccess = array(); // for those reservations we obtained 'settled' state
$paymentPendingSuccessInProcess = array(); // for those reservations we obtained 'sent_for_processing' state
$paymentPendingInProcess = array(); // for those reservations we obtained one of the unfinished states
$paymentPendingFail = array(); // for those reservations we obtained failed state

// these arrays are for collecting reservations with ORDER_STATUS_PRELIMINARY_PAID
$preliminaryPaymentSuccess = array(); // for those reservations we obtained 'settled' state
$preliminaryPaymentPendingSuccessInProcess = array(); // for those reservations we obtained 'sent_for_processing' state
$preliminaryPaymentInProcess = array(); // for those reservations we obtained one of the unfinished states
$preliminaryPaymentFail = array(); // for those reservations we obtained failed state
$preliminaryPaymentInProcessExpired = array(); // for those reservations with already expired lock records

if (!empty($dataCollected)) {

    // get payment methods
    $billingSystem->setEveryPayConfig();
    $result = $billingSystem->requestEveryPay('get_payment_methods');
    $paymentMethods = getPaymentMethods($result);

    foreach ($dataCollected as $paymentToCheck) {

        // for each collected reservation we make the request to everyPay to check payment state

        $billingSystem->setPaymentReference($paymentToCheck['payment_reference']);
        $result = $billingSystem->requestEveryPay('payments_check');
        $response = json_decode($result['result'], true);

        // debug emulate status
        if($localDebug && !empty($emulateStatus)) {
            $response['payment_state'] = $emulateStatus;
        }

        $data = array(
            'paid_by' => null,
            'last_four_digits' => null,
            'stan' => null,
            'message' => null,
            'code' => null,
            'payment_status' => null,
            'payment_url' => null
        );

        if (!empty($paymentMethods) && !empty($response['payment_method'])) {
            if (!empty($paymentMethods[$response['payment_method']])) {
                $prefix = '';
                if ($response['payment_method'] == 'card') {
                    $prefix = 'everyPay/';
                }
                $data['paid_by'] = $prefix . $paymentMethods[$response['payment_method']];
            }
        }

        if ($result['success']) {

            $everyPayConfig = $cfg->get('billing_system')['everyPay'];
            $paymentsUnfinishedStatuses = $everyPayConfig['payment_unfinished_statuses'];

            if (!empty($response['payment_state']) &&
                $paymentToCheck['payment_reference'] == $response['payment_reference']) {


                // add to constructed data array some payment details if received

                if (!empty($response['cc_details']['last_four_digits'])) {
                    $data['last_four_digits'] = $response['cc_details']['last_four_digits'];
                }

                if (!empty($response['stan'])) {
                    $data['stan'] = $response['stan'];
                }

                if (!empty($response['payment_link'])) {
                    $data['payment_url'] = $response['payment_link'];
                }

                $data['payment_status'] = $response['payment_state'];


                // collect reservations to separate arrays in dependency of everyPay response and order states

                if ($paymentToCheck['order_status'] == ORDER_STATUS_PENDING) {

                    // check if order is in pending status, that means that no final states or sent_for_processing state had been received before

                    if ($response['payment_state'] == 'settled') {

                        $paymentPendingSuccess[] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];

                    } elseif($response['payment_state'] == 'sent_for_processing') {

                        $paymentPendingSuccessInProcess[] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];

                    } elseif(in_array($response['payment_state'], $paymentsUnfinishedStatuses)) {

                        $paymentPendingInProcess[] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];

                    } else {

                        if (!empty($response['processing_error'])) {

                            if (!empty($response['processing_error']['code'])) {
                                $data['code'] = $response['processing_error']['code'];
                            }
                            if (!empty($response['processing_error']['message'])) {
                                $data['message'] = $response['processing_error']['message'];
                            }
                        }

                        $paymentPendingFail[$paymentToCheck['order_id']] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];
                    }


                } else {

                    // order in preliminary paid status, that means that earlier had been obtained sent_for_processing state

                    $expired = false;

                    if (!empty($paymentToCheck['transaction_id'])) {

                        // first check lock record for expiration

                        $paymentToCheck['transaction_created'] =
                            getTransactionCreated($paymentToCheck['transaction_id']);

                        $config = $cfg->get('billing_system');
                        $everyPayLockTimes = $config['everyPay']['payment_unfinished_statuses_lock_time'];
                        $lockTimeByStatus = $everyPayLockTimes[$response['payment_state']];

                        if (empty($lockTimeByStatus)) {
                            $lockTimeByStatus = $everyPayLockTimes['default_lock_time'];
                        }

                        $transactionExpiration = date('Y-m-d H:i:s', strtotime($paymentToCheck['transaction_created'] . ' + ' . $lockTimeByStatus . ' minutes'));

                        $expired = $transactionExpiration <= date('Y-m-d H:i:s');
                    }


                    if ($response['payment_state'] == 'settled') {

                        $preliminaryPaymentSuccess[] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];

                    } elseif($response['payment_state'] == 'sent_for_processing') {

                        $preliminaryPaymentPendingSuccessInProcess[] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];

                    } elseif (in_array($response['payment_state'], $paymentsUnfinishedStatuses)) {

                        if ($expired) {

                            $preliminaryPaymentInProcessExpired[] = [
                                'payment_data' => $data,
                                'data' => $paymentToCheck,
                                'response' => $response,
                            ];

                        } else {

                            $preliminaryPaymentInProcess[] = [
                                'payment_data' => $data,
                                'data' => $paymentToCheck,
                                'response' => $response,
                            ];
                        }

                    } else {

                        $preliminaryPaymentFail[] = [
                            'payment_data' => $data,
                            'data' => $paymentToCheck,
                            'response' => $response,
                        ];
                    }

                }

            } else {

                $paymentPendingFail[] = [
                    'payment_data' => $data,
                    'data' => $paymentToCheck,
                    'response' => $response,
                ];
            }
        }
    }
}


// // //

//  Process collected arrays

// // //


// Received from EveryPay that payment settled for pending orders

$updatedSuccessPendingPayments = [];
if (!empty($paymentPendingSuccess)) {

    foreach ($paymentPendingSuccess as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedSuccessPendingPayments[] = paymentSuccess($data, 'success');
    }
}

// Received from EveryPay that payment has special 'sent_for_processing' state for pending orders

$updatedPaymentPendingSuccessInProcess = [];
if (!empty($paymentPendingSuccessInProcess)) {

    foreach ($paymentPendingSuccessInProcess as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPaymentPendingSuccessInProcess[] = paymentSuccess($data, 'success_preliminary');
    }
}

// Received from EveryPay that payment is unfinished for pending orders

$updatedPendingPaymentsInProcess = [];
if (!empty($paymentPendingInProcess)) {

    foreach ($paymentPendingInProcess as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPendingPaymentsInProcess[] = paymentUnfinishedState($data);
    }
}

// Received from EveryPay that payment failed for pending orders

$updatedFailedPendingOrderIds = [];
if (!empty($paymentPendingFail)) {

    foreach ($paymentPendingFail as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedFailedPendingOrderIds[] = paymentFail($data);
    }
}

// Received from EveryPay that payment is done for preliminary paid orders

$updatedPreliminaryPaymentSuccess = [];
if (!empty($preliminaryPaymentSuccess)) {

    foreach ($preliminaryPaymentSuccess as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPreliminaryPaymentSuccess[] = paymentSuccess($data, 'success');
    }
}

// Received from EveryPay that payment is still in special 'sent_for_processing' state

$updatedPreliminaryPaymentPendingSuccessInProcess = [];
if (!empty($preliminaryPaymentPendingSuccessInProcess)) {

    foreach ($preliminaryPaymentPendingSuccessInProcess as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPreliminaryPaymentPendingSuccessInProcess[] = paymentSuccess($data, 'success_preliminary_again');;
    }
}

// Received from EveryPay that payment is still unfinished

$updatedPreliminaryPaymentInProcess = [];
if (!empty($preliminaryPaymentInProcess)) {

    foreach ($preliminaryPaymentInProcess as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPreliminaryPaymentInProcess[] = paymentUnfinishedState($data);
    }
}

// Received from EveryPay that payment is still unfinished but expiration time (set in config) has come

$updatedPreliminaryPaymentInProcessExpired = [];
if (!empty($preliminaryPaymentInProcessExpired)) {

    foreach ($preliminaryPaymentInProcessExpired as $payment) {
        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPreliminaryPaymentInProcessExpired[] = paymentFail($data);
        sendPaymentFailEmailToSupport($data);
    }
}

// Received from EveryPay that payment failed for preliminary paid orders

$updatedPreliminaryPaymentFail = [];
if (!empty($preliminaryPaymentFail)) {

    foreach ($preliminaryPaymentFail as $payment) {

        $data = setPaymentData($payment);
        $data['response'] = $payment['response'];
        $updatedPreliminaryPaymentFail[] = paymentFail($data);
        sendPaymentFailEmailToSupport($data);
    }
}

// finalize cron job

// debug output

if ($debug) {
    echo PHP_EOL . "Data collected:" . PHP_EOL;
    print_r($dataCollected);

    echo PHP_EOL . "****PAYMENT PENDING ORDERS****:" . PHP_EOL;

    echo PHP_EOL . "Successfully paid and sent to paymentSuccess function:" . PHP_EOL;
    print_r($paymentPendingSuccess);
    echo PHP_EOL . "Success payments ==> updated order ids:" . PHP_EOL;
    print_r($updatedSuccessPendingPayments);

    echo PHP_EOL . "Preliminary paid and sent to paymentInProcess function:" . PHP_EOL;
    print_r($paymentPendingSuccessInProcess);
    echo PHP_EOL . "Preliminary paid ==> updated order ids:" . PHP_EOL;
    print_r($updatedPaymentPendingSuccessInProcess);

    echo PHP_EOL . "Payments in process, sent to paymentUnfinishedState function:" . PHP_EOL;
    print_r($paymentPendingInProcess);
    echo PHP_EOL . "Payment in process ==> updated order ids:" . PHP_EOL;
    print_r($updatedPendingPaymentsInProcess);

    echo PHP_EOL . "Payments have not been done and sent to paymentFail function:" . PHP_EOL;
    print_r($paymentPendingFail);
    echo PHP_EOL . "Payment fail ==> updated order ids:" . PHP_EOL;
    print_r($updatedFailedPendingOrderIds);


    echo PHP_EOL . "*****************************:" . PHP_EOL;
    echo PHP_EOL . "-----------------------------:" . PHP_EOL;

    echo PHP_EOL . "****PRELIMINARY PAID ORDERS****:" . PHP_EOL;

    echo PHP_EOL . "Successfully paid and sent to updateToPaymentSuccess function:" . PHP_EOL;
    print_r($preliminaryPaymentSuccess);
    echo PHP_EOL . "Success payments ==> updated order ids:" . PHP_EOL;
    print_r($updatedPreliminaryPaymentSuccess);

    echo PHP_EOL . "Still payment in process (preliminary paid), sent to paymentStillInProcess function:" . PHP_EOL;
    print_r($preliminaryPaymentPendingSuccessInProcess);
    echo PHP_EOL . "Still payment in process ==> updated order ids:" . PHP_EOL;
    print_r($updatedPreliminaryPaymentPendingSuccessInProcess);

    echo PHP_EOL . "Payment is unfinished, sent to paymentUnfinishedState function:" . PHP_EOL;
    print_r($preliminaryPaymentInProcess);
    echo PHP_EOL . "Payment is unfinished ==> updated order ids:" . PHP_EOL;
    print_r($updatedPreliminaryPaymentInProcess);


    echo PHP_EOL . "Expired payment in process, sent to paymentFail function:" . PHP_EOL;
    print_r($preliminaryPaymentInProcessExpired);
    echo PHP_EOL . "Expired payment in process ==> updated order ids:" . PHP_EOL;
    print_r($updatedPreliminaryPaymentInProcessExpired);

    echo PHP_EOL . "Payments have not been done and email to support was sent:" . PHP_EOL;
    print_r($preliminaryPaymentFail);

    echo PHP_EOL . "Payment fail ==> order ids:" . PHP_EOL;
    print_r($updatedPreliminaryPaymentFail);

    echo PHP_EOL . "*****************************:" . PHP_EOL;
}

if ($debug) {
    echo PHP_EOL . PHP_EOL . '****Cron finished****' . PHP_EOL;
}

exit;

?>


