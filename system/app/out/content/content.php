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

/**
 * CMS content module
 * Default module in cms. Generate content, load templates and show it
 * Be careful, if wanna edit it.
 * 21.04.2008
 */
class content extends Module {

    /**
     * Class constructor
     */
    public function __construct() {

        parent :: __construct();
        $this->name = get_class($this);
        $this->getModuleId();

        $this->setPData(isset($_SESSION['user']) && isset($_SESSION['user']['id']) && $_SESSION['user']['id'], 'isLoggedUser');
        $this->setPData($this->cfg->get('ShowOnlyFreeSlots'), 'ShowOnlyFreeSlots');

        require_once(AD_APP_FOLDER . $this->app . '/' . $this->name . '/inc/' . $this->name . '.class.php');
        $this->module = new contentData();

        $xhrRequest = false;

        if ($this->getNoLayout()) {

            $xhrRequest = true;
            $this->setPData(date('Y') + 1, 'fromthisyear');
            $this->loadLabels('', true);
        }

        if(!$xhrRequest || getP('ajax_search')) {
            $this->setCData($this->module->checkPageUrl($this->getUrlDir()));
        }

        if ($this->cfg->get('mirrors')) {
            $this->module->loadMirrors();
        }

        if(!$xhrRequest) {

            $env = $this->cfg->get('env');

            $this->setPData($env, 'env');

            $this->loadLabels('', true);

            $tpl = array();
            $tpl["dir"] = AD_APP_FOLDER . $this->app . '/' . $this->name . '/tmpl/';
            $tpl["tpl"]["head"] = "head";
            $tpl["tpl"]["body"] = $this->getCData("template") ? $this->getCData("template") : '';
            $tpl["tpl"]["footer"] = "footer";
            $tpl["tpl"]["header"] = "header";
            $tpl["tpl"]["main"] = "main";

            $this->setPData($tpl, "tpl");

            $verification = array(
                'gatewayTimeout' => $this->cfg->get('verification_gateway_timeout'),
                'smartidTimeout' => $this->cfg->get('verification_smartid_timeout'),
                'allowedCountries' => json_encode($this->cfg->get('verifiableCountries')),
            );

            $this->setPData($verification, 'verification');

            $maniDatiUrl = $this->cfg->get('maniDatiUrl');
            $maniDatiCheckInterval = $this->cfg->get('maniDatiCheckInterval');
            $this->setPData($maniDatiUrl, 'maniDatiUrl');
            $this->setPData($maniDatiCheckInterval, 'maniDatiCheckInterval');

            $timeSelectWidget = array(
                'header' => gL('time_select_widget_header', 'Выберите время:'),
            );

            $this->setPData($timeSelectWidget, 'timeSelectWidget');

            // allows to switch verification controls on/off
            $verificationEnabled = $this->cfg->get('profileVerificationEnabled');
            $this->setPData($verificationEnabled, 'verificationEnabled');

            $piesakiArstu = $this->cfg->get('pieasakiArstu');
            $this->setPData($piesakiArstu, "piesakiArstu");

            if(isset($_SESSION['popupMessage']) && $_SESSION['popupMessage']) {
                $this->setPData(base64_encode($_SESSION['popupMessage']), 'popupMessage');
                unset($_SESSION['popupMessage']);
            }


            // Set VroomId
            $vroomId = getG('vroomid');
            if ( ! empty($vroomId)) {
                $this->setPData($vroomId, 'vroomId');
            }
        }
    }

    /**
     * Modules process function
     * This function runs auto from Module class
     */
    public function run() {
    	global $config;

        // Creating all menus on page
        $this->module->displayPageMenus();

        $data = array();
        $data["lang"] = getG('lang') ? getG('lang') : ($_SESSION['userLang'] ? $_SESSION['userLang'] : $this->getLang());
        $data["country"] = $this->getCountry();
        $data["pageTitle"] = $this->getCData("page_title") ? $this->getCData("page_title") : $this->getCData("title") ." - ". gL("defaultPageTitle");
        $data["pageDescription"] = $this->getCData("description") ? $this->getCData("description") : gL("defaultPageDescription");
        $data["pageKeywords"] = $this->getCData("keywords") ? $this->getCData("keywords") : gL("defaultPageKeywords");
        $data["title"] = $this->getCData("title") ? $this->getCData("title") : '';
        $data["full_title"] = $this->getCData("full_title") ? $this->getCData("full_title") : '';
        $data["html"] = $this->getCData("content") ? $this->getCData("content") : '';
        $data["image"] = $this->getCData("image") ? $this->getCData("image") : '';
        $data["image_alt"] = $this->getCData("image_alt") ? $this->getCData("image_alt") : '';
        $data["mirror_id"] = $this->getCData("mirror_id") ? $this->getCData("mirror_id") : '';
        $data["url"] = '/' . makeUrlWithLangInTheEnd($this->getUrlDir(), true);
        $data["url_orig"] = $this->uri->orig_uri;
        $data["pagePath"] = $this->module->getPagePath();
        $data["jsArray"] = array('jquery', 'jquery.mobile.custom', 'jquery.dotdotdot.min', 'jquery-ui.min', 'functions', 'js', 'lib/jquery.selectric.min', 'profile', 'profile-edit', 'tfa');
        $data["cssArray"] = array('extra', 'font-awesome.min', 'lib/selectric', 'jquery-ui.min', 'css');
        $data["env"] = $this->cfg->get('env');
        $urlForClass = '/' . makeUrlWithLangInTheEnd($this->getUrlDir(), true);
        $data['pageClass'] = trim($urlForClass, '/');
        $data['cspNonce'] = CSP_NONCE;
        $data['isLoggedUser'] = isset($_SESSION['user']) && isset($_SESSION['user']['id']) && $_SESSION['user']['id']; 
        // Make Critical CSS path

        $uri = $_SERVER['REQUEST_URI'];
        $last_segment = basename(parse_url($uri, PHP_URL_PATH));

        $lang_codes = ['lv', 'ru', 'en'];

        if (in_array($last_segment, $lang_codes)) {
            $last_segment = 'default';
        }

        $criticalCss = "/css/$last_segment.css";
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $criticalCss;

        if (!file_exists($fullPath)) {
            $criticalCss = '/css/default.css';
        }

        $data['criticalCssPath'] = $criticalCss;
        
        // Make Critical CSS path. End

        $this->setPData(curPostUrl(), "curPostUrl");
        // var_dump($data);
        $this->setPData($data, "web");
        $this->setPData($this->getCData(), "content");

        $this->setPData(getBackLink(), "backLink");
        $this->setPData(curPageURL(), "curPageUrl");
        $this->setPData(curPageURL2(), "curPageUrl2");
        
        $this->setPData(date('Y') + 1, 'fromthisyear');
        
        $mainpageId = getMirror(getDefaultPageId());
        $this->setPData($mainpageId, "mainpageId");
        $this->setPData($this->cfg->get('dcUrl'), "dcUrl");
        $this->setPData($this->getCData("id"), "curpageId");
        $this->setPData(true, "showHelLine");

        $currentLang =  $data["lang"];
        $this->setPData($currentLang, "currentLang");
        $this->setPData(getAllWebLanguages(), "allWebLanguages");


        $url = makeUrlWithLangInTheEnd(ltrim(curPostUrl(), '/'), true);
        $this->setPData($url, "currentUrl");
        if (isset($_SESSION['user']) && !empty($_SESSION['user']['lang'])){
            $switcherLanguage = $_SESSION['user']['lang'];
        } else {
            $switcherLanguage = getDefaultLang();
        }

        $url = explode('/',$data['url']);
        $lang = $url[1];
        if ($switcherLanguage != $lang){
            $switcherLanguage = $lang;
        }

        $this->setPData($switcherLanguage, "switcherLanguage");

        if(getG("page")){
    		$p=(int)getG("page");
    		if($p<1){
    			$p=1;
    		}
    		$_GET['page']=$p;
    	}
        if(getG("page")==1){
            redirect(getLM($this->getCData("id")));
        }

        if($_SESSION['isChecking'] || $_SESSION['profileActivationRequired']) {
            $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
            $this->tpl->assign("CUSTOM_HEADER", $this->tpl->output('modal-pages-header', $this->getPData()));
            $this->tpl->assign("CUSTOM_FOOTER", $this->tpl->output('modal-pages-footer', $this->getPData()));
        }
		
        if (
            $mainpageId == $this->getCData("id") ||
            strpos($_SERVER['REQUEST_URI'], '/home') > -1 ||
            strpos($_SERVER['REQUEST_URI'], '/mans-profils') > -1
        ) {

            $this->setPData($this->module->getDoctorsCount(), "doctorsCount");
        	$this->setPData($this->module->getRandomReview(), "randomReview");
        	$this->setPData($this->module->getProfilesCount(), "profilesCount");
        	$this->setPData($this->module->getAnnouncement(), "announcement");


        	/** @var cl $cl */
        	$cl = loadLibClass('cl');
        	$homePageItems = $cl->getHomepageItems($this->cfg->getData('clMoreCount'));

        	if(count($homePageItems) > 0) {

        	    if(isset($homePageItems[9])) {
                    $clinicsItems = $homePageItems[9];
                } else {
                    $clinicsItems = null;
                }


                $classifItems = $homePageItems;

                if(isset($classifItems[9])) {
                    unset($classifItems[9]);
                }

                // if user is logged in and his city is in items array we move it to the top of cities array

                if(isset($classifItems[1])) {

                    if(
                        isset($_SESSION['user']) &&
                        isset($_SESSION['user']['city_id']) &&
                        $_SESSION['user']['city_id'])
                    {
                        $cityList = $classifItems[1];
                        $key = array_search($_SESSION['user']['city_id'], array_column($cityList, 'original_id'));

                        if($key) {
                            move_to_top($cityList, $key);
                        }

                        $classifItems[1] = $cityList;
                        unset($cityList);
                    };

                }

                if(isset($classifItems[3])) {

                    $showMore = isset($classifItems[3]['showMore']) && $classifItems[3]['showMore'];

                    if($showMore) {
                        unset($classifItems[3]['showMore']);
                    }

                    if(count($homePageItems[3]) > 0) {
                        $classifItems[3] = unique_key($homePageItems[3],'title');
                    } else {
                        $classifItems[3] = null;
                    }

                    if($showMore) {
                        $classifItems[3]['showMore'] = true;
                    }
                }

                $clConfig = array(
                    'types' => $config['classificators_types'],
                    'keys' => $config['classificators_filter_keys'],
                );

                if(!isset($classifItems[1])) {
                    $classifItems[1] = null;
                }

                if(!isset($classifItems[3])) {
                    $classifItems[3] = null;
                }

                if(!isset($classifItems[5])) {
                    $classifItems[5] = null;
                }

                $this->setPData($clConfig, "clConfig");
                $this->setPData($classifItems, "cl");
                $this->setPData($clinicsItems, "clinicsList");

            }
        }

        // handler for unfinished reservation sess param

        if(!empty($_SESSION['finish_res'])) {
            // set page variable to call js method authomatically
            $this->setPData($_SESSION['finish_res'], "finishReservation");
            unset($_SESSION['finish_res']);
        }

        // Cookies policy page shows without standard header/footer as modal page

        if (in_array('sikdatnu-politika', $url)) {

            $this->setPData(true, 'hideLinks');
            $this->setPData(true, 'hideConsentMask');
            $this->tpl->setTmplDir(AD_APP_FOLDER . $this->app . '/' . 'content' . '/tmpl/');
            $this->tpl->assign("AFTER_CONTENT", $this->tpl->output('cookie_declaration', $this->getPData()));
            $this->tpl->assign("CUSTOM_HEADER", $this->tpl->output('modal-pages-header', $this->getPData()));
            $this->tpl->assign("CUSTOM_FOOTER", $this->tpl->output('modal-pages-footer', $this->getPData()));
        }

        $maintenanceWarning = defined('MAINTENANCE_WARNING');
        $this->setPData($maintenanceWarning, "maintenanceWarning");
    }

    //
    public function action_getPageContent() {

        $this->module->getPageContent();
        jsonSend($this->module->getReturn());
    }

    public function action_dmssLink() {

        $this->module->dmssLink();
        jsonSend($this->module->getReturn());
    }
}

?>