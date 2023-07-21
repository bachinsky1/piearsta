<?php


class profile extends Module {
	
	/**
	 * Class constructor
	 */
	public function __construct($setTmplFolder = true)	{

		parent :: __construct();
		$this->name = get_class($this);
		$this->getModuleId(true);

		require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');

		/** @var profileData module */
		$this->module = new profileData();
		
		if ($setTmplFolder) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
		}

		$xhrRequest = false;
		if ($this->getNoLayout()) {
			$xhrRequest = true;
			$this->setPData(date('Y') + 1, 'fromthisyear');
			$this->loadLabels('', true);
		}

		if($this->module->isLogged()) {

			$this->setPData($this->module->getUserData(), 'userData');
			$this->module->getProfilePersons();

			$sessionTimeout = $this->cfg->get('sessionTimeout');
			$sessionTimeoutWarnBefore = $this->cfg->get('sessionTimeoutWarnBefore');
			$this->setPData(array(
				'sessionTimeout' => $sessionTimeout,
				'sessionTimeoutWarnBefore' => $sessionTimeoutWarnBefore,
			), 'sessionTimeouts');

			if(!$xhrRequest) {
				$oldTmplFolder = $this->tpl->getTmplDir();
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$sessionTimeoutPopup = base64_encode($this->tpl->output('session-timeout-popup', $this->getPData()));
				$this->tpl->setTmplDir($oldTmplFolder);
				$this->setPData($sessionTimeoutPopup, 'sessionTimeoutPopup');
			}
		}
	}
	
	/**
	 * Modules process function
	 * This function runs auto from Module class
	 */
	public function run() {
		// $this->addCSSFile('jquery-ui.min');
		// $this->addCSSFile('jquery-ui.structure.min');
		$this->addJSFile('time-select-widget');

		$dashboardPage = $this->module->getDashboardPage();

		$this->setPData($dashboardPage, "dashboardPage");

		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

//		pre($_SESSION['user']);
//		pre($_SESSION['dmss_auth']);
//		pre($this->module->getUserData());

		// Arstiem page
		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_arstiem_page'))) {

			if($this->cfg->get('arstiem')['active']) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
				$this->setPData(false, "showHelLine");

				$this->module->arstiemForm();
			} else {
				redirect('/');
			}
		}
        
		// Analyze whether we received e-ID verification fail or success rezults
		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_edit_page'))) {

			// we start analyze only if we came here from eparaksts server
			if(strpos($_SERVER['HTTP_REFERER'], 'eidas.eparaksts.lv') !== false) {

				if(isset($_GET['attempt_id'])) {

                    $_SESSION['attempt_id'] = $_GET['attempt_id'];

					header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
                }
			}

			if(isset($_SESSION['attempt_id']) && !isset($_GET['attempt_id'])) {

				// get verification result popup
				$html = $this->tpl->output('accreditation-popup', $this->getPData());
				$this->setPData($html, 'verificationResultPopup');
				$this->setPData($_SESSION['attempt_id'], 'verificationAttemptId');
				unset($_SESSION['attempt_id']);
			}
		}

		// Pieasaki arstu page
		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_pieasaki_arstu_page'))) {

			if($this->cfg->get('pieasakiArstu')['active']) {
				if ($this->module->isLogged()) {
					$this->setPData(false, "showHelLine");
					$this->module->piesakiArstuForm();
				} else {
					$url = getLM($this->cfg->getData('mirros_signin_page')) . '?url=' . curPageURL();
					redirect($url);
				}
			} else {
				redirect('/');
			}
		}

		// Page agree-terms
		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_agree_terms_page'))) {

			// we process this request only if user logged, else redirect to homepage
			if ($this->module->isLogged() && $_SESSION['isChecking']) {
				// Set custom header & footer without any conditions, because this page is always modal
				// temporary set tmplDir to content to load CUSTOM_HEADER & CUSTOM_FOOTER
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
				$this->tpl->assign("CUSTOM_HEADER", $this->tpl->output('modal-pages-header', $this->getPData()));
				$this->tpl->assign("CUSTOM_FOOTER", $this->tpl->output('modal-pages-footer', $this->getPData()));
				// set tmplDir back to this module
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->agreeTermsForm();
			} else {
				redirect('/');
			}
		}

		// Payment cancel page
		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_payment_cancel_page'),'',	$this->getLang())) {

			if ($this->module->isLogged() && isset($_SESSION['PaymentInfo'])) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/payment/');
				$this->module->paymentCancel($_SESSION['PaymentInfo']);
			} else {
				redirect('/');
			}
		}

		// Payment success page

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_payment_success_page'),'',	$this->getLang())) {

			if ($this->module->isLogged() && isset($_SESSION['PaymentInfo'])) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/payment/');
				$this->module->paymentSuccess($_SESSION['PaymentInfo']);
			} else {
				redirect('/');
			}
		}

		// Payment failed page

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_payment_fail_page'),'',	$this->getLang())) {

			if ($this->module->isLogged() && isset($_SESSION['PaymentInfo'])) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/payment/');
				$this->module->paymentFail($_SESSION['PaymentInfo']);
			} else {
				redirect('/');
			}
		}


		// Payment pending page

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_payment_pending_page'),'',	$this->getLang())) {

			if ($this->module->isLogged() && isset($_SESSION['PaymentInfo'])) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/payment/');
				$this->module->paymentPending($_SESSION['PaymentInfo']);
			} else {
				redirect('/');
			}
		}


		// DMSS Auth page

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_dmss_auth_page'))) {

			$this->module->dmssAuth();
		}

		// Registration Start page

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_registration_start_page'))) {

			$dcReturn = getG('dcReturn');

			if(!empty($dcReturn) && is_string($dcReturn)) {

				$_SESSION['dcReturn'] = $dcReturn;
			}

			if ($this->module->isLogged()) {

				$url = getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang());
				if(!empty($_SESSION['dcReturn'])) {

					$url = urldecode($_SESSION['dcReturn']);
					unset($_SESSION['dcReturn']);
				}

				redirect($url);

			} else {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-clean';
				$tpl["tpl"]["footer"] = null;

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				
				$this->module->registrationStart();
			}
		}

		// Payment in process

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_payment_in_process_page'),'',	$this->getLang())) {

			if ($this->module->isLogged() && isset($_SESSION['PaymentInfo'])) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/payment/');
				$this->module->paymentInProcess($_SESSION['PaymentInfo']);
			} else {
				redirect('/');
			}
		}


		// other routes:

		if ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_edit_page'))) {

			if ($this->module->isLogged()) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				if(isset($_SESSION['showSuccessMsg'])) {
					unset($_SESSION['showSuccessMsg']);
					$this->setPData(true, 'showSuccessMsg');
				}

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->module->edit();

				// Set custom header & footer to make page modal, when user asked to confirm personal data
				if($_SESSION['isChecking']) {

					$this->setPData(true, 'isCheckingPersonalData');

					// temporary set tmplDir to content to load CUSTOM_HEADER & CUSTOM_FOOTER
					$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
					$this->tpl->assign("CUSTOM_HEADER", $this->tpl->output('modal-pages-header', $this->getPData()));
					$this->tpl->assign("CUSTOM_FOOTER", $this->tpl->output('modal-pages-footer', $this->getPData()));
					// set tmplDir back to this module
					$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				}

				$this->setPData('edit.html', 'template');

				if(!empty($_SESSION['verification_failed_message'])) {
					$this->setPData(true, 'verification_failed_message');
					unset($_SESSION['verification_failed_message']);
				}

				if(!empty($_SESSION['verification_mismatch_message'])) {
					$this->setPData(true, 'verification_mismatch_message');
					unset($_SESSION['verification_mismatch_message']);
				}

				if(!empty($_SESSION['verification_success_message'])) {
					$this->setPData(true, 'verification_success_message');
					unset($_SESSION['verification_success_message']);
				}

				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));

			} else {

				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_change_password_page'))) {

			if ($this->module->isLogged()) {
				$this->addJSFile('string-similarity');
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->setPData('change-password.html', 'template');
				
				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));
			} else {
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}
		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_orders_page'))) {

			// Orders page -- list of paid reservations and offline services (?)

			if ($this->module->isLogged()) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->module->getProfileOrders();

				$this->setPData('orders.html', 'template');

				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));

			} else {
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_signin_page'))) {

			$dcReturn = getG('dcReturn');

			if(!empty($dcReturn) && is_string($dcReturn)) {
				$_SESSION['dcReturn'] = $dcReturn;
			}

			if ($this->module->isLogged()) {

				$url = getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang());

				if(!empty($_SESSION['dcReturn'])) {
					$url = urldecode($_SESSION['dcReturn']);
					unset($_SESSION['dcReturn']);
				}

				redirect($url);

			} else {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-clean';
				// $tpl["tpl"]["footer"] = 'footer-clean';
//				$tpl["tpl"]["header"] = null;
				$tpl["tpl"]["footer"] = null;

                $tpl["url"] = @getGP('url');

				if($tpl["url"]) {
					$_SESSION['url'] = $tpl['url'];
				}

				$_SESSION['schedule_id'] = @getGP('schedule_id');
				$_SESSION['cons_doctor_id'] = @getGP('cons_doctor_id');
				$_SESSION['cons_clinic_id'] = @getGP('cons_clinic_id');
				$_SESSION['cdata'] = @getGP('cdata');

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				// Set VroomId
				$vroomId = getG('vroomid');
				if ( ! empty($vroomId)) {
					$this->setPData($vroomId, 'vroomId');
					$_SESSION['vroomId'] = $vroomId;
				}

				$this->module->loginForm();
			}
			
		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_forgotpassword_page'))) {
			
			if ($this->module->isLogged()) {
				redirect(getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang()));
			} else {
				
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-clean';
//				$tpl["tpl"]["footer"] = 'footer-clean';
//				$tpl["tpl"]["header"] = null;
				$tpl["tpl"]["footer"] = null;
				
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				
				$this->tpl->assign("MODULE_CONTENT", $this->tpl->output('password-recovery', $this->getPData()));
			}
			
		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_signup_page'))) {

			$dcReturn = getG('dcReturn');

			if(!empty($dcReturn) && is_string($dcReturn)) {
				$_SESSION['dcReturn'] = $dcReturn;
			}

			if ($this->module->isLogged()) {

				$url = getLM($this->cfg->getData('mirros_default_profile_page'), '', $this->getLang());

				if(!empty($_SESSION['dcReturn'])) {
					$url = urldecode($_SESSION['dcReturn']);
					unset($_SESSION['dcReturn']);
				}

				redirect($url);

			} else {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-clean';
//				$tpl["tpl"]["footer"] = 'footer-clean';
//				$tpl["tpl"]["header"] = null;
				$tpl["tpl"]["footer"] = null;
				
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				
				$this->module->registrationForm();
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_logout_page'))) {

			$this->module->logout();

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_default_profile_page'))) {

			$dcReturn = getG('dcReturn');

			if(!empty($dcReturn) && is_string($dcReturn)) {
				$_SESSION['dcReturn'] = $dcReturn;
			}

			if (getG('email') && getG('hash')) {
				$this->module->activateProfile(getG('email'), getG('hash'));
			}
			
			if ($this->module->isLogged()) {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				$templateName = 'dashboard';

				// Set custom header & footer to make page modal, when user have not activated his account
				if($_SESSION['profileActivationRequired']) {
					// temporary set tmplDir to content to load CUSTOM_HEADER & CUSTOM_FOOTER
					$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
					$this->tpl->assign("CUSTOM_HEADER", $this->tpl->output('modal-pages-header', $this->getPData()));
					$this->tpl->assign("CUSTOM_FOOTER", $this->tpl->output('modal-pages-footer', $this->getPData()));
					// set tmplDir back to this module
					$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
					$templateName = 'registration-confirm';
				} else {
					$this->tpl->assign("CUSTOM_HEADER", null);
					$this->tpl->assign("CUSTOM_FOOTER", null);
				}

				$this->module->collectUserData($this->module->getUserId());

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				if($_SESSION['justActivated']) {
					$this->setPData(true, "justActivated");
					unset($_SESSION['justActivated']);
				}

				if(!empty($_SESSION['dcReturn']) && is_string($_SESSION['dcReturn'])) {
					$this->setPData($_SESSION['dcReturn'], "dcReturn");
					unset($_SESSION['dcReturn']);
				}
				
				$this->module->getNearestReservation();
				$this->module->getProfileDoctors();

				if($templateName == 'registration-confirm') {

					$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('registration-confirm', $this->getPData()));

				} else {

					$this->setPData($templateName . '.html', 'template');
					$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));
				}

			} else {

				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}
			
		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_coupons_page'))) {
			
			if ($this->module->isLogged()) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
			
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->setPData('coupons.html', 'template');
			
				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));

			} else {

				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_messages_page'))) {

			if ($this->module->isLogged()) {
				$this->addJSFile('item_list');
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
			
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				$this->module->getMessages();

				$this->setPData('messages.html', 'template');

				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));
			} else {
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}
		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_doctors_page'))) {
			
			if ($this->module->isLogged()) {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
			
				$this->module->getProfileDoctors();

				$this->setPData('doctors.html', 'template');
			
				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));
			} else {
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}
		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_persons_page'))) {
			
			if ($this->module->isLogged()) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
			
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->setPData('persons.html', 'template');
			
				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));

			} else {

				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_persons_add_page'))) {
			
			if ($this->module->isLogged()) {
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
			
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
			
				$this->module->getProfilePersonsAddForm();

				$this->setPData('persons-add.html', 'template');
			
				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));

			} else {

				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_reservations_page'))) {
			
			if ($this->module->isLogged()) {
				$this->addJSFile('item_list');
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
			
				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");
				
				$this->module->getProfileReservations();

				$this->setPData('reservations.html', 'template');
			
				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));

			} else {

                $_SESSION['redirectTo'] = getCurrentUrl();
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_consultations_page'))) {

			if ($this->module->isLogged()) {
				$this->addJSFile('item_list');
				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->module->getProfileConsultations();

				$this->setPData('consultations.html', 'template');

				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('profilePageLayout', $this->getPData()));
			} else {
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} elseif ($this->getCData("id") == getMirror($this->cfg->getData('mirros_profile_subscription_page'))) {

			if ($this->module->isLogged()) {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';

				$this->setPData($tpl, "tpl");
				$this->setPData(false, "showHelLine");

				$this->module->showSubscriptionPage();

//				$this->tpl->assign("PROFILE_TEMPLATE", $this->tpl->output('subscription', $this->getPData()));

			} else {
				redirect(getLM($this->cfg->getData('mirros_signin_page')));
			}

		} else {
			$this->module->defaultAction = true;
			if ($this->module->isLogged()) {

				$tpl = $this->getPData('tpl');
				$tpl["tpl"]["header"] = 'header-profile';
					
				$this->setPData($tpl, "tpl");
			}
		}
			
	}

	public function action_sessionPing() {

		if($this->module->isLogged()) {
			jsonSend(array(
				'logged' => true,
				'session_maxlifetime' => ini_get('session.gc_maxlifetime'),
			));
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_login() {

		$this->module->loginForm();	
	
		jsonSend($this->module->getReturn());
	}

	public function action_logout() {
		$this->module->logout(false);
		jsonSend($this->module->getReturn());
	}
	
	public function action_register() {
	
		$this->module->registrationForm();
	
		jsonSend($this->module->getReturn());
	}

	public function action_registrationCancel() {
		if(getP('userId')) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->registrationCancel(getP('userId'));
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('errors' => array(
				'msg' => 'No user id passed.'
			)));
		}
	}

	public function action_registrationCancelConfirm() {
		if(getP('userId')) {
			$this->module->registrationCancelConfirm(getP('userId'));
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('errors' => array(
				'msg' => 'No user id passed.'
			)));
		}
	}
	
	public function action_addPerson() {
		if ($this->module->isLogged()) {
			$this->module->getProfilePersonsAddForm();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_save() {
		if ($this->module->isLogged()) {
			$this->module->edit();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_moreReservations() {
		if ($this->module->isLogged()) {
			$this->module->moreReservations();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_moreConsultations() {
		if ($this->module->isLogged()) {
			$this->module->moreConsultations();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_moreMessages() {
		if ($this->module->isLogged()) {
			$this->module->moreMessages();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_openMessage() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->openMessage();
				
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_deleteMessageConfirm() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->deleteMessageConfirm();
	
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_deleteMessage() {
		if ($this->module->isLogged()) {
			$this->module->deleteMessage();
		
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_deletePersonConfirm() {
		if ($this->module->isLogged()) {
			$this->module->deletePersonConfirm();
		
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_deletePerson() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->deletePerson();
		
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_deleteProfile() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->deleteProfile();
		
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_resendActivationLink() {
		if ($this->module->isLogged()) {
			$this->module->resendActivationLink();
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_deleteProfileConfirm() {
		if ($this->module->isLogged()) {
			$this->module->deleteProfileConfirm();
		
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_removeDoctor() {
		if ($this->module->isLogged()) {
			$this->module->removeDoctor();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_changePassword() {
		$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
		jsonSend($this->module->getChangePasswordForm());
	}
	
	public function action_setNewPassword() {
		if ($this->module->isLogged()) {
			$this->module->setNewPassword();
			
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_passwordRecovery() {
		jsonSend($this->module->passwordRecovery(getP('email')));
	}
	
	public function action_pdfCoupon() {
		if ($this->module->isLogged()) {
			if (getP('couponId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->pdfCoupon(getP('couponId'));
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_subscribe() {
		if (getP('subscribe_email')) {

			$this->module->subscribe(getP('subscribe_email'));
				
			jsonSend($this->module->getReturn());
		}
	}
	
	public function action_openReservation() {
		if ($this->module->isLogged()) {
			if (getP('reservationId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->openReservation(getP('reservationId'));
					
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_openConsultation() {
		if ($this->module->isLogged()) {
			if (getP('consultationId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->openConsultation(getP('consultationId'));

				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

    public function action_openOrder() {
        if ($this->module->isLogged()) {
            if (getP('orderId')) {
                $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
                $this->module->openOrder();

                jsonSend($this->module->getReturn());
            }
        } else {
            jsonSend(array('logged_off' => true));
        }
    }

	public function action_openInvoice() {
		if ($this->module->isLogged()) {
			if (getP('orderId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->openInvoice();

				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_cancelReservationPopup() {
		if ($this->module->isLogged()) {
			if (getP('reservationId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->cancelReservationPopup(getP('reservationId'));
					
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	/**
	 * @throws Exception
	 */
	public function action_cancelReservation() {

		if ($this->module->isLogged()) {
			if (getP('reservationId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->cancelReservation(getP('reservationId'));
					
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_backToReservationPopup() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->backToReservationPopup();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_performPayment() {
		if ($this->module->isLogged()) {
			$this->module->performPayment();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_getTransaction() {

		if ($this->module->isLogged()) {
			$this->module->getTransaction();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_getServiceDescription() {
		if ($this->module->isLogged()) {
			if (getP('descriptionId')) {
				$this->module->getServiceDescription(getP('descriptionId'));
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_addReservationPopup() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');


			$scheduleId = getP('sheduleId');
			$serviceId = getP('serviceId');
			$dc = getP('dc');

			$this->module->addReservationPopup(
				$scheduleId,
				$serviceId,
				null,
				null,
				null,
				null,
				null,
				$dc
			);

			jsonSend($this->module->getReturn());

		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_cancelAddReservation() {

		if(isset($_SESSION['blockPaymentCancel']) && $_SESSION['blockPaymentCancel']) {
			return false;
		}

		if ($this->module->isLogged()) {
			$this->module->cancelAddReservation();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}
	
	public function action_addReservation() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->addReservation();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_addConsultation() {
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->addConsultation();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_showOrderDetailsPopup() {

		if ($this->module->isLogged() && getP('orderId')) {

			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

			$orderId = $_SESSION['orderId'];
			unset($_SESSION['orderId']);

			if(empty($orderId)) {
				$orderId = mres(getP('orderId'));
			}

			$this->module->showOrderDetailsPopup($orderId);
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	public function action_setLockStatus() {
		if ($this->module->isLogged()) {
			if (getP('status')) {

				$reservationId = null;
				$hspReservationId = null;

				if(getP('reservationId')) {
					$reservationId = getP('reservationId');
				}

				if(getP('hspReservationId')) {
					$hspReservationId = getP('hspReservationId');
				}

				$this->module->setLockStatus(getP('lockId'), getP('status'), $reservationId, $hspReservationId);
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_checkSM() {
		if ($this->module->isLogged()) {

			$lockId = getP('lockId') ? getP('lockId') : null;
			$reservationId = getP('reservationId') ? getP('reservationId') : null;

			if($lockId && $reservationId) {
				$this->module->checkSM();
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_clearReservationData() {
        if ($this->module->isLogged()) {

            $this->module->clearReservationData();
            jsonSend($this->module->getReturn());
        } else {
            jsonSend(array('logged_off' => true));
        }
    }
	
	public function action_pdfReservation() {
		if ($this->module->isLogged()) {
			if (getP('reservationId')) {
				$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
				$this->module->pdfReservation(getP('reservationId'));
				jsonSend($this->module->getReturn());
			}
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_agreeTermsSave() {
		if ($this->module->isLogged()) {
			$this->module->agreeTermsForm();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_setRequestedOrder() {
		if ($this->module->isLogged()) {
			$this->module->setRequestedOrder(getP('orderId'));
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_invoice_pdf() {
		if ($this->module->isLogged()) {
			$this->module->openPdfInvoice();
		} else {
			echo '<h1>Error!</h1>';
			exit;
		}
	}

	public function action_arstiemAddDoctor() {

		if($this->cfg->get('arstiem')['active']) {
			$this->module->arstiemAddDoctor();
			jsonSend($this->module->getReturn());
		}
	}

	public function action_piesakiArstuAddDoctor() {

		if($this->cfg->get('pieasakiArstu')['active']) {
			if ($this->module->isLogged()) {
				$this->module->piesakiArstuAddDoctor();
				jsonSend($this->module->getReturn());
			} else {
				jsonSend(array('logged_off' => true));
			}
		}
	}

	public function action_countryAutocomplete()
	{
		if (getGP('q')) {

			jsonSend($this->module->countryAutocomplete(getGP('q')));
		}
	}

	public function action_accreditationShowPopup()
	{
		$this->module->accreditationShowPopup();
		jsonSend($this->module->getReturn());
	}

	public function action_accreditationStart()
	{
		$this->module->accreditationStart();
		jsonSend($this->module->getReturn());
	}

	public function action_verificationCanceled()
	{
		$this->module->verificationCanceled();
		jsonSend($this->module->getReturn());
	}

	public function action_verificationTimeout()
	{
		$this->module->verificationTimeout();
		jsonSend($this->module->getReturn());
	}

	public function action_checkVerificationResult()
	{
		$this->module->checkVerificationResult();
		jsonSend($this->module->getReturn());
	}

	public function action_verificationChangeProfile()
	{
		$this->module->verificationChangeProfile();
		jsonSend($this->module->getReturn());
	}

	public function action_isManiDatiAvailable()
	{
		if ($this->module->isLogged()) {
			$this->module->isManiDatiAvailable();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_vroom()
	{
		if ($this->module->isLogged()) {
			$this->module->enterVroom();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_finishReservationPopup()
	{
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->finishReservationPopup(getP('resId'));
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	public function action_finishReservation()
	{
		if ($this->module->isLogged()) {
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->finishReservation(trim(mres(getP('resId'))));
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

	// insurance methods

	public function action_showInsuranceEditForm() {

		if ($this->module->isLogged()) {

			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

			$this->module->showInsuranceEditForm();
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	public function action_showInsuranceFirst() {

		if ($this->module->isLogged()) {

			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

			$this->module->showInsuranceStart();
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	public function action_saveInsuranceData() {

		if ($this->module->isLogged()) {

			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

			$this->module->saveInsuranceData();
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	public function action_checkInsurance() {

		if ($this->module->isLogged()) {

			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');

			$this->module->checkInsurance();
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	// TFA methods

	public function action_showTfaConfigurePopup()
	{
		$this->module->showTfaConfigurePopup();
		jsonSend($this->module->getReturn());
	}

	public function action_showTfaConfigurePopupCodeInput()
	{
		$this->module->showTfaConfigurePopupCodeInput();
		jsonSend($this->module->getReturn());
	}

	public function action_tfaConfigureCheckCode()
	{
		$this->module->tfaConfigureCheckCode();
		jsonSend($this->module->getReturn());
	}

	public function action_tfaRemoveCode()
	{
		$this->module->tfaRemoveCode();
		jsonSend($this->module->getReturn());
	}

	public function action_tfaShowAuthPopup()
	{
		$this->module->tfaShowAuthPopup();
		jsonSend($this->module->getReturn());
	}

	public function action_tfaCheckAuth()
	{
		$this->module->tfaCheckAuth();
		jsonSend($this->module->getReturn());
    }

	// PROMO CODES

	public function action_checkPromoCode() {

		if ($this->module->isLogged()) {

			$this->module->checkPromoCode();
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	public function action_finishFreeReservation()
	{
		if ($this->module->isLogged()) {

			$resId = mres(getP('reservationId'));
			$this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/');
			$this->module->finishReservation($resId);
			jsonSend($this->module->getReturn());

		} else {

			jsonSend(array('logged_off' => true));
		}
	}

	// DEBUG route
	public function action_testPdfInvoice() {
		if ($this->module->isLogged()) {
			$this->module->testPdfInvoice();
			jsonSend($this->module->getReturn());
		} else {
			jsonSend(array('logged_off' => true));
		}
	}

}
?>