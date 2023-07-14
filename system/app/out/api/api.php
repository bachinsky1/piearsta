<?php

class api extends Module {

    /** @var Api_base api */
	protected $api = false;
	protected $isLogOn = false;

	public function __construct()	{		

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);
		
		$this->noLayout(true);

		$this->api = loadLibClass('api.base');
        ob_start('catchFatalError');
        $paApiCfg = $this->cfg->get('piearsta_api');
        $this->isLogOn = $paApiCfg['log'];

		// Content types allowed: [application/json, text/xml, multipart/form-data, application/x-www-form-urlencoded]
        $headers = getallheaders();

        foreach($headers as $k => $v) $headers[strtolower($k)] = $v;
        $ct = $headers['content-type'];

        // Auth bearer
        $bearer = null;
        if(isset($headers['authorization'])) {

            if(strpos(strtolower($headers['authorization']), 'bearer') !== false) {
                $bearer = trim(explode(' ', $headers['authorization'])[1]);
            }
        }

		$allowedTypes = array('application/json', 'text/xml', 'multipart/form-data', 'application/x-www-form-urlencoded');

		$isAllowed = false;
		$contentType = null;

		foreach ($allowedTypes as $type) {

		    if(strpos($ct, $type) !== false) {
		        $contentType = $type;
		        $isAllowed = true;
		        break;
            }
        }

        $loginResult = null;

		if(DEBUG) {

//            $this->api->log(array(
//                'deb' => array(
//                    'serverRequestUri' => stripslashes($_SERVER['REQUEST_URI']),
//                    'matchSuccess' => strpos(strtolower($_SERVER['REQUEST_URI']), 'user_valid'),
//                    'matchFail' => strpos(strtolower($_SERVER['REQUEST_URI']), 'user_check_failed'),
//                    'contentType' => $ct,
//                    'allowedContentType' => $contentType,
//                ),
//            ));
        }

        // json request
        if(strpos($contentType, 'application/json') !== false) {

            $data = file_get_contents('php://input');

            if(strlen($data) && isValidJson($data)) {

                $decodedParams = json_decode($data, true);

                if($decodedParams['object'] == 'consultation') {

                    // consultations request, because we received requestType marker

                    if ($bearer) {

//                        $_POST['request'] = 'consultation_update';
                        $_POST['json'] = $decodedParams;
                        $loginResult = $this->api->loginConsultations($bearer);
                    }

                } elseif($decodedParams['object'] == 'integrator') {

                    if ($bearer) {
                        $_POST['json'] = $decodedParams;
                        $loginResult = $this->api->loginIntegrator($bearer);
                    }

                } elseif($decodedParams['object'] == 'dc') {

                    if ($bearer) {

                        $_POST['json'] = $decodedParams;
                        $loginResult = $this->api->loginDigitalClinic($bearer);
                    }

                } elseif($decodedParams['object'] == 'pa_json') {

                    // this is object for api methods, that can be called from several applications

                    if($bearer) {

                        $_POST['json'] = $decodedParams;

                        // still needed to know calling application to choose correct login method

                        if($decodedParams['callingApp'] == 'consultation') {

                            $loginResult = $this->api->loginConsultations($bearer);

                        } elseif($decodedParams['callingApp'] == 'dc') {

                            $loginResult = $this->api->loginDigitalClinic($bearer);
                        }
                    }

                } else {

                    // sm request

                    // auth params are inside json
                    $termId = isset($decodedParams['hsp_terminal_id']) ? $decodedParams['hsp_terminal_id'] : null;
                    $hspGuid = isset($decodedParams['hsp_guid']) ? $decodedParams['hsp_guid'] : null;
                    $hspPass = isset($decodedParams['hsp_password']) ? $decodedParams['hsp_password'] : null;

                    if(!$termId || !$hspGuid || !$hspPass) {
                        $this->respond404();
                    }

                    if(isset($decodedParams['json']) && count($decodedParams['json']) < 1) {
                        $decodedParams['json'] = array('emptyData' => true);
                    }

                    $_POST['object'] = isset($decodedParams['object']) ? $decodedParams['object'] : null;
                    $_POST['request'] = isset($decodedParams['request']) ? $decodedParams['request'] : null;
                    $_POST['json'] = isset($decodedParams['json']) ? $decodedParams['json'] : null;

                    $loginResult = $this->api->login($termId, $hspGuid, $hspPass);

                }
            }

        // post request
        } else {

            // billingSystem requests
            if($_SERVER['REQUEST_URI'] == '/api/sDelivery/') {

                $loginResult = $this->api->loginBillingSystem(getP('token'));

            // verification request
            } elseif (
                strpos(strtolower($_SERVER['REQUEST_URI']), 'user_valid') !== false ||
                strpos(strtolower($_SERVER['REQUEST_URI']), 'user_check_failed') !== false
            ) {

                $loginResult = $this->api->loginVerificationGateway($bearer);

            // EveryPay request (contains GET params, so we should move them to POST at loginEveryPay method)
            } elseif (
                strpos($_SERVER['REQUEST_URI'], 'paymentStatusChange') !== false
            ) {

                $loginResult = $this->api->loginEveryPay();

            } else {

                $termId = getGP('hsp_terminal_id');
                $hspGuid = getGP('hsp_guid');
                $hspPass = getGP('hsp_password');

                if(!$termId || !$hspGuid || !$hspPass) {
                    $this->respond404();
                }

                // url encoded request with auth params
                $loginResult = $this->api->login($termId, $hspGuid, $hspPass);
            }

        }
		
		if ($loginResult) {

            if($this->isLogOn) {
                $this->api->log();
            }

		    if(getGP('xml')) {

                if ($this->api->setXml(getGP('xml'))) {

                } else {
                    $this->api->getResult();
                }

            } elseif (getGP('json')) {

                if ($this->api->setJson(getGP('json'))) {

                } else {
                    $this->api->getResult();
                }

            } elseif(count($_POST)) {

		        if($this->api->setPost($_POST)) {

                } else {
                    $this->api->getResult();
                }

            } else {

                if(isset($decodedParams) && isset($decodedParams['json'])) {

                    if ($this->api->setJson($decodedParams['json'])) {

                    } else {
                        $this->api->getResult();
                    }
                }
            }
			
		} else {
			$this->api->getResult(false);
		}
	}

	public function respond404() {
        header("HTTP/1.0 404 Not Found", true, 404);
        exit;
    }

    public function respond500() {
        header("HTTP/1.0 500 Internal Server Error", true, 500);
        exit;
    }


	
	public function action_district() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_ic() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_specialty() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_service() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_city() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_hsp() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_resource_type_doctor() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}
	
	public function action_timeslot() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
			
	}

	public function action_timetable() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
    }
	
	public function action_reservation() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
	}

	public function action_sync_reservations() {

        if(method_exists($this->api, getGP('request'))) {
            $request = getGP('request');
            $this->executeMethod($request);
        } else {
            $this->respond500();
        }
    }


    // // //

    // Vaccination endpoints

    // // //

    public function action_vaccination_createReservation() {
        if(method_exists($this->api, 'vaccination_createReservation')) {
            $this->executeMethod('vaccination_createReservation');
        } else {
            $this->respond500();
        }
    }

    public function action_vaccination_cancelReservation() {
        if(method_exists($this->api, 'vaccination_cancelReservation')) {
            $this->executeMethod('vaccination_cancelReservation');
        } else {
            $this->respond500();
        }
    }



    // // //

    // Endpoints for consultations api

    // // //

    public function action_consultation_update() {

        if(method_exists($this->api, 'consultation_update')) {
            $this->executeMethod('consultation_update');
        } else {
            $this->respond500();
        }
    }

    public function action_consultation_vcrCreated() {

        if(method_exists($this->api, 'consultation_vcrCreated')) {
            $this->executeMethod('consultation_vcrCreated');
        } else {
            $this->respond500();
        }
    }

    public function action_consultation_getDoctor() {

        if(method_exists($this->api, 'consultation_getDoctor')) {
            $this->executeMethod('consultation_getDoctor');
        } else {
            $this->respond500();
        }
    }

    public function action_consultation_getDoctorSchedule() {

        if(method_exists($this->api, 'consultation_getDoctorSchedule')) {
            $this->executeMethod('consultation_getDoctorSchedule');
        } else {
            $this->respond500();
        }
    }

    public function action_getPageContent() {

        if(method_exists($this->api, 'getPageContent')) {
            $this->executeMethod('getPageContent');
        } else {
            $this->respond500();
        }
    }

    public function action_consultation_checkTimeOnPA() {

        if(method_exists($this->api, 'consultation_checkTimeOnPA')) {
            $this->executeMethod('consultation_checkTimeOnPA');
        } else {
            $this->respond500();
        }
    }


    // // //

    // Endpoints for integrator api

    // // //

    public function action_integrator_getServices() {

        if (method_exists ($this->api, 'integrator_getServices')) {
            $this->api->integrator_getServices()->getResult();
        }
    }

    public function action_integrator_clinicUpload() {

        if (method_exists ($this->api, 'integrator_clinicUpload')) {
            $this->api->integrator_clinicUpload()->getResult();
        }
    }

    public function action_integrator_clinicSetStatus() {

        if (method_exists ($this->api, 'integrator_clinicSetStatus')) {
            $this->api->integrator_clinicSetStatus()->getResult();
        }
    }

    public function action_integrator_clinicGroupSetStatus() {

        if (method_exists ($this->api, 'integrator_clinicGroupSetStatus')) {
            $this->api->integrator_clinicGroupSetStatus()->getResult();
        }
    }

    public function action_integrator_saveChangedClinicReservations() {
        if (method_exists ($this->api, 'integrator_saveChangedClinicReservations')) {
            $this->api->integrator_saveChangedClinicReservations()->getResult();
        }
    }

    public function action_integrator_doctorsStatesUpload() {
        if (method_exists ($this->api, 'integrator_doctorsStatesUpload')) {
            $this->api->integrator_doctorsStatesUpload()->getResult();
        }
    }

    public function action_integrator_timeSlotsUpload() {
        if (method_exists ($this->api, 'integrator_timeSlotsUpload')) {
            $this->api->integrator_timeSlotsUpload()->getResult();
        }
    }


    // // //

    // This is actually end-point for billingSystem service delivery

    // // //

    public function action_sDelivery() {

        if(method_exists($this->api, 'service_delivery')) {
            $this->executeMethod('service_delivery');
        } else {
            $this->respond500();
        }
    }



    // // //

    // end-points for verification gateway requests

    // // //

    public function action_user_valid() {

        if(method_exists($this->api, 'verification_success')) {
            $this->executeMethod('verification_success');
        } else {
            $this->respond500();
        }
    }

    public function action_user_check_failed() {

        if(method_exists($this->api, 'verification_fail')) {
            $this->executeMethod('verification_fail');
        } else {
            $this->respond500();
        }
    }

    // // //

    public function action_doctorEmail_confirmEmailCode() {

        if(method_exists($this->api, 'doctorEmail_confirmEmailCode')) {
            $this->executeMethod('doctorEmail_confirmEmailCode');
        } else {
            $this->respond500();
        }
    }


    // // //

    // end-points for DIGITAL CLINIC requests

    // // //

    public function action_dc_findSchedule() {

        if(method_exists($this->api, 'dc_findSchedule')) {
            $this->executeMethod('dc_findSchedule');
        } else {
            $this->respond500();
        }
    }

    public function action_dc_getPaSession() {

        if(method_exists($this->api, 'dc_getPaSession')) {
            $this->executeMethod('dc_getPaSession');
        } else {
            $this->respond500();
        }
    }

    public function action_dc_setLockRecordPrice() {

        if(method_exists($this->api, 'dc_setLockRecordPrice')) {
            $this->executeMethod('dc_setLockRecordPrice');
        } else {
            $this->respond500();
        }
    }

    public function executeMethod($method)
    {
        try {
            $this->api->$method()->getResult();
        } catch (Exception $e) {
            $this->respond500();
        }
    }

    public function action_paymentStatusChange() {
        if(method_exists($this->api, 'paymentStatusChange')) {
            $this->executeMethod('paymentStatusChange');
        } else {
            $this->respond500();
        }
    }

}

