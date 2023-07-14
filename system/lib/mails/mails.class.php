<?php

/**
 * Class Mails
 */
class Mails
{
    /**
     * @var bool|object
     */
    private $cfg;

    /**
     * @var string
     */
    private $env;

    /**
     * @var string[]
     */
    private $supportedTemplatesLangs = ['lv', 'ru', 'en'];

    /**
     * @var string
     */
    private $defaultTemplatesLang = 'lv';

    /**
     * Mail constructor.
     */
    public function __construct()
    {
        $this->cfg = loadLibClass('config');
        $this->env = $this->cfg->get('env');
        $this->db = loadLibClass('db');
    }

    /**
     * @param $params
     *      string siteDataName
     *      [string to]
     *      [array data]
     *      [bool sendMessageToPatient = false]
     *      [int patientId = 0]
     *      [bool _writeToLogs = false]
     *      [bool _sendEmail = true]
     * @return array $result
     *      bool emailSent
     */
    public function send($params)
    {
        $result = array(
            'emailSent' => false,
            'messageToPatientSent' => false,
            '_debug' => array(
                'continue' => true,
                'error' => '',
                'originalParams' => $params,
                'finalParams' => array(),
            ),
            'dataFromSiteData' => array(),
        );

        if ( ! isset($params['sendMessageToPatient']))
        {
            $params['sendMessageToPatient'] = false;
        }

        if ( ! isset($params['_writeToLogs']))
        {
            $params['_writeToLogs'] = false;
        }

        if ( ! isset($params['_sendEmail']))
        {
            $params['_sendEmail'] = true;
        }

        if ( ! isset($params['data']))
        {
            $params['data'] = array();
        }

        if ( ! isset($params['data']['_partials']))
        {
            $params['data']['_partials'] = array();
        }

        // Prepare params and data
        $methodName = 'prepare_' . $params['siteDataName'];
        if (method_exists($this, $methodName))
        {
            $params = $this->$methodName($params);
        }

        // Check lang
//        if ( ! in_array($params['lang'], $this->supportedTemplatesLangs))
//        {
//            $params['lang'] = $this->defaultTemplatesLang;
//        }

        $result['_debug']['finalParams'] = $params;

        // Get data
        $tempParams = array(
            'siteDataName' => $params['siteDataName'],
            'replaceData' => $params['data'],
            'lang' => $params['lang'],
        );
        $data = $this->getDataFromSiteData($tempParams);
        $result['dataFromSiteData'] = $data;
        $result['_debug']['finalParams']['from'] = $data['from'];
        if ($data['_debug']['continue'] !== true)
        {
            $result['_debug']['continue'] = false;
        }

        if(empty($params['to'])) {
            $result['_debug']['continue'] = false;
        }

        // Send email
        if ($result['_debug']['continue'] === true && $params['_sendEmail'] === true)
        {
            $result['emailSent'] = sendMail(
                $params['to'],
                $data['subject'],
                $data['body'],
                array(),
                $data['from'],
                true
            );

            if ($result['emailSent'] !== true)
            {
                $result['_debug']['continue'] = false;
                $result['_debug']['error'] = 'Failed to send email';
            }
        }

        // Send message to patient
        if ($result['_debug']['continue'] === true && $params['sendMessageToPatient'] === true)
        {
            if (empty($params['patientId']))
            {
                $result['_debug']['continue'] = false;
                $result['_debug']['error'] = 'Patient id is not set';
            }

            $clinicId = null;
            if (!empty($params['data']['clinic_id']))
            {
                $clinicId = $params['data']['clinic_id'];
            }
            if ($result['_debug']['continue'] === true)
            {
                addMessageToProfile($params['patientId'], $data['subject'], $data['body'], $clinicId);
                $result['messageToPatientSent'] = true;
            }
        }

        // Logs
        if ($this->env === 'dev' && $params['_writeToLogs'] === true)
        {
            logDebug('Mails send() result ' . json_encode($result, JSON_PRETTY_PRINT));
        }

        // Return
        return $result;
    }

    /**
     * @param $params
     *      string siteDataName
     *      [array replaceData]
     *      [string lang = lv]
     * @return array
     */
    public function getDataFromSiteData($params)
    {
        $result = array(
            'from' => '',
            'subject' => '',
            'body' => '',
            '_debug' => array(
                'continue' => true,
                'error' => '',
            ),
        );

        // Get site data group
        if ($result['_debug']['continue'] === true)
        {
            $siteDataGroup = $this->getSiteDataGroup($params['siteDataName']);
            if (empty($siteDataGroup))
            {
                $result['_debug']['continue'] = false;
                $result['_debug']['error'] = 'Site data group not found';
            }
        }

        // Get site data values
        if ($result['_debug']['continue'] === true)
        {
            $result['from'] = getTranslation($this->cfg, $siteDataGroup['from']['name'], $params['lang']);
            $result['subject'] = getTranslation($this->cfg, $siteDataGroup['subject']['name'], $params['lang']);
            $result['body'] = getTranslation($this->cfg, $siteDataGroup['body']['name'], $params['lang']);

            if (empty($result['from']) || empty($result['subject']) || empty($result['body']))
            {
                $result['_debug']['continue'] = false;
                $result['_debug']['error'] = 'From, subject or body is empty';
            }
        }

        // Replace data
        if ($result['_debug']['continue'] === true && ! empty($params['replaceData']))
        {
            // Partials
            if ( ! empty($params['replaceData']['_partials']))
            {
                $keys = array();
                $values = array();
                foreach ($params['replaceData']['_partials'] as $key => $value)
                {
                    $keys[] = '{' . $key . '}';
                    $values[] = $value;
                }

                $result['body'] = str_replace($keys, $values, $result['body']);

                unset($params['replaceData']['_partials']);
            }

            // Data
            $keys = array();
            $values = array();
            foreach ($params['replaceData'] as $key => $value)
            {
                $keys[] = '{' . $key . '}';
                $values[] = $value;
            }

            $result['body'] = str_replace($keys, $values, $result['body']);
        }

        return $result;
    }

    /**
     * @param string $name
     *      Name or _all
     * @return array|null
     */
    public function getSiteDataGroup($name)
    {
        $groups = array(
            'mailVroomShared' => array(
                'from' => array(
                    'name' => 'mailVroomFrom',
                    'tab' => 'Vrooms',
                    'block' => 'Email from',
                    'title' => '',
                    'type' => 'text',
                    'mlang' => 1,
                    'mcountry' => '0',
                    'required' => '0',
                    'validation' => 'null',
                    'callback' => 'null',
                    'sort' => '',
                ),
            ),
        );

        $groups['mailPatientVroomCreated'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailPatientSubjectVroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Subject (patient vroom created)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailPatientBodyVroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Body (patient vroom created)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
        );

        $groups['mailPatientVcroomCreated'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailPatientSubjectVcroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Subject (patient vcroom created)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailPatientBodyVcroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Body (patient vcroom created)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
        );

        $groups['mailPatientVroomConsTypeChanged'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailPatientSubjectVroomConsTypeChanged',
                'tab' => 'Vrooms',
                'block' => 'Subject (patient consultation type changed)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailPatientBodyVroomConsTypeChanged',
                'tab' => 'Vrooms',
                'block' => 'Body (patient consultation type changed)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'partialNewOtherType' => array(
                'name' => 'mailPatientPartialNewOtherType',
                'tab' => 'Vrooms',
                'block' => 'Partial cons type changed {partial_newOtherType}',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
                '_value' => array(
                    'lang' => 'lv',
                    'value' => '<span>New consultation other type: {otherType}</span>',
                    'country' => '0',
                ),
            ),
            'partialOldOtherType' => array(
                'name' => 'mailPatientPartialOldOtherType',
                'tab' => 'Vrooms',
                'block' => 'Partial cons type changed {partial_oldOtherType}',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
                '_value' => array(
                    'lang' => 'lv',
                    'value' => '<span>Old consultation other type: {oldOtherType}</span>',
                    'country' => '0',
                ),
            ),
        );

        $groups['mailDoctorVroomCreated'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailDoctorSubjectVroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Subject (doctor vroom created)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailDoctorBodyVroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Body (doctor vroom created)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
        );

        $groups['mailDoctorVroomCanceledByPatient'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailDoctorSubjectVroomCanceledByPatient',
                'tab' => 'Reservation',
                'block' => 'Subject (doctor reservation canceled by patient)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailDoctorBodyVroomCanceledByPatient',
                'tab' => 'Reservation',
                'block' => 'Body (doctor reservation canceled by patient)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
        );

        $groups['mailDoctorVcroomCreated'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailDoctorSubjectVcroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Subject (doctor vcroom created)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailDoctorBodyVcroomCreated',
                'tab' => 'Vrooms',
                'block' => 'Body (doctor vcroom created)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
        );

        $groups['mailDoctorConfirmEmailCode'] = array(
            'from' => $groups['mailVroomShared']['from'],
            'subject' => array(
                'name' => 'mailDoctorSubjectConfirmEmailCode',
                'tab' => 'Vrooms',
                'block' => 'Subject (doctor confirm email code)',
                'title' => '',
                'type' => 'text',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
            'body' => array(
                'name' => 'mailDoctorBodyConfirmEmailCode',
                'tab' => 'Vrooms',
                'block' => 'Body (doctor confirm email code)',
                'title' => '',
                'type' => 'textarea',
                'mlang' => 1,
                'mcountry' => '0',
                'required' => '0',
                'validation' => 'null',
                'callback' => 'null',
                'sort' => '',
            ),
        );

        if ($name === '_all')
        {
            return $groups;
        }

        if ( ! empty($name) && isset($groups[$name]))
        {
            return $groups[$name];
        }

        return null;
    }

    /**
     *
     */
    public function insertMissingSiteDataGroups()
    {
        if ($this->env !== 'dev')
        {
            return;
        }

        $result = array(
            '_debug' => array(
                'insert' => array(),
            ),
        );

        $siteDataTable = 'ad_sitedata';
        $siteDataValuesTable = 'ad_sitedata_values';

        $groups = $this->getSiteDataGroup('_all');

        foreach ($groups as $name => $data)
        {
            foreach ($data as $emailField => $emailFieldDbData)
            {
                $dbQuery = 'SELECT `id` FROM ' . $siteDataTable . ' WHERE `name` = "' . $emailFieldDbData['name'] . '"';
                $query = new query($this->cfg->db, $dbQuery);
                if ($query->num_rows() > 0)
                {
                    continue;
                }

                $valueData = ( ! empty($emailFieldDbData['_value'])) ? $emailFieldDbData['_value'] : null;
                unset($emailFieldDbData['_value']);
                $rowId = saveValuesInDb($siteDataTable, $emailFieldDbData);

                $debug = array(
                    'insertData' => $emailFieldDbData,
                    'insertRowId' => $rowId,
                    'error' => '',
                );

                if (empty($rowId))
                {
                    $debug['error'] = 'Failed to insert';
                }

                if ( ! empty($rowId))
                {
                    $valuesData = array(
                        'fid' => $rowId,
                        'lang' => 'lv', // TODO
                        'value' => (isset($valueData['value'])) ? $valueData['value'] : '',
                        'country' => '0',
                    );

                    $rowIdValues = saveValuesInDb($siteDataValuesTable, $valuesData);
                }

                $result['_debug']['insert'][] = $debug;
            }
        }

        // Log
        logDebug('lib/mails insertMissingSiteDataGroups result ' . json_encode($result, JSON_PRETTY_PRINT));

        return $result;
    }

    /**
     * @param array $params
     *      string siteDataGroupName
     *      [int reservationId]
     * @return array|null
     */
    public function getTestData($params)
    {
        $testData = array();

        if ( ! empty($params['reservationId']))
        {
            $extraData = array(
                'vroom' => array(
                    'doctor' => array(
                        'email' => 'doctoremail@test.111',
                        'locale' => 'lv',
                        'profile' => array(
                            'consEmail' => 'doctorconsemail@test.222',
                        ),
                    ),
                ),
            );
            $testData = array_merge($testData, $extraData);
        }

        if ($params['siteDataGroupName'] === 'mailPatientVroomConsTypeChanged')
        {
            $extraData = array(
                'oldType' => 'other',
                'oldOtherType' => 'Test old other type',
                'consType' => 'mobile',
                'otherType' => '',
            );
            $testData = array_merge($testData, $extraData);
        }
        else if ($params['siteDataGroupName'] === 'mailDoctorConfirmEmailCode')
        {
            $extraData = array(
                'user' => array(
                    'email' => 'doctoremail@test.111',
                    'locale' => 'lv',
                    'profile' => array(
                        'consEmail' => 'doctorconsemail@test.222',
                        'emailConfCode' => '123456',
                    ),
                ),
            );
            $testData = array_merge($testData, $extraData);
        }

        return $testData;
    }

    /**
     * @param array $params
     *      int $reservationId
     *      string lang
     *      [bool prepare = false]
     * @return array|bool
     */
    public function getReservationData($params)
    {

        if(!empty($params['reservationId'])) {
            $resCond = " r.id = '" . mres($params['reservationId']) . "'";
        } elseif (!empty($params['hspResId'])) {
            $resCond = " r.hsp_reservation_id = '" . mres($params['hspResId']) . "'";

            if(!empty($params['clinicId'])) {
                $resCond .= " AND r.clinic_id = '" . $params['clinicId'] . "' ";
            } else {
                return false;
            }
        }

        $lang = !empty($params['lang']) ? $params['lang'] : 'lv';

        $dbQuery = "SELECT
    					r.id,
						r.profile_id,
						r.shedule_id,
						r.notice,
    					r.start,
    					r.end,
    					r.profile_person_id,
    					r.doctor_id,
    					r.clinic_id,
		    			r.status_reason,
		    			r.status_changed_at,
		    			r.service_price,
                        r.confirmed_at,
                        r.need_approval,
		    			di.name,
		    			di.surname,
		    			di.notify_phone as doctor_phone,
		    			di.notify_email as doctor_notify_email,
    					d.url as doctor_url,
		    			d.email as doctor_email,
		    			c.name as clinic_name,
    					cc.phone as clinic_phone,
						cc.email as clinic_email,
		    			ci.address as clinic_address,
    					c.url as clinic_url,
                        cldCity.title as clinic_citytitle,
		    			cld.title,
		    			r.status,
    					r.payment_type,
    					r.service_type,
    					r.consultation_vroom,
    					r.consultation_vroom_doctor,       
    					r.vchat_room,
    					r.vchat_room_doctor,
    					pp.name as ppname,
    					pp.surname as ppsurname,
						p.email,
						p.name as pname,
						p.surname as psurname,
						p.person_id as pcode,
						p.phone as p_phone,
						p.lang as p_lang
							FROM `" . $this->cfg->getDbTable('reservations', 'self')	 . "` r
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'self')	 . "` d ON (r.doctor_id = d.id)
								LEFT JOIN `" . $this->cfg->getDbTable('doctors', 'info')	 . "` di ON (d.id = di.doctor_id AND di.lang = 'lv')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'self')	 . "` c ON (r.clinic_id = c.id)
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'info')	 . "` ci ON (c.id = ci.clinic_id AND ci.lang = 'lv')
								LEFT JOIN `" . $this->cfg->getDbTable('clinics', 'contacts')	 . "` cc ON (cc.clinic_id = c.id AND cc.default = 1)
								LEFT JOIN mod_classificators_info cldCity ON (cldCity.c_id = c.city)
								LEFT JOIN `mod_classificators_info` cld ON (
                                    r.service_id = cld.c_id
                                    AND IF(EXISTS(SELECT id FROM mod_classificators_info ci2 WHERE ci2.c_id = r.service_id AND ci2.lang = '".$lang."'), cld.lang = '".$lang."', cld.lang = 'lv')
								)
								LEFT JOIN `" . $this->cfg->getDbTable('profiles', 'persons')	 . "` pp ON (r.profile_person_id = pp.id)
								LEFT JOIN `" . $this->cfg->getDbTable('profiles', 'self')	 . "` p ON (r.profile_id = p.id)
							WHERE 1
								AND" . $resCond;

        $query = new query($this->db, $dbQuery);

        if ( ! $query->num_rows())
        {
            return false;
        }

        $row = $query->getrow();

        // Prepare
        if (isset($params['prepare']) && $params['prepare'] === true)
        {
            $row['start_raw'] = $row['start'];
            $row['start'] = (empty($row['start']))
                ? gL('profile_reservation_start_time_not_set', 'Laiks nav norādīts', 'lv')
                : date("d.m.Y H:i", strtotime($row['start']));
        }

        return $row;
    }

    // ---

    private function prepare_mailPatientVroomShared($params)
    {

        $lang = !empty($params['lang']) ? $params['lang'] : $this->defaultTemplatesLang;
        // Merge in reservation data
        $tempParams = array(
            'reservationId' => $params['data']['reservationId'],
            'lang' => $lang,
            'prepare' => true,
        );

        $params['data'] = array_merge($params['data'], $this->getReservationData($tempParams));

        // Set email to
        if (empty($params['to']))
        {
            $params['to'] = $params['data']['email'];
        }

        // Set patient id
        $params['patientId'] = $params['data']['profile_id'];

        // Set patient vroom url
        $params['data']['patientVroomUrl'] = '';
        if ( ! empty($params['data']['consultation_vroom']))
        {
            $baseUrl = $this->cfg->get('vr_cron_piearstaUrl');

            // cron job
            if (!empty($params['vr_cron_piearstaUrl'])){
                $baseUrl = $params['vr_cron_piearstaUrl'];
                unset($params['vr_cron_piearstaUrl']);
            }

            $signinUrlPart = trim(getLM($this->cfg->getData('mirros_signin_page') , '', $lang), '/');
            $stringId = substr($params['data']['consultation_vroom'], 3);
            $params['data']['patientVroomUrl'] = $baseUrl . $signinUrlPart . '/?vroomid=' .  $stringId;
        }

        // Set lang
        $params['lang'] = (! empty($params['data']['p_lang'])) ?
            $params['data']['p_lang'] : ($params['lang'] ? $params['lang'] : 'lv');

        // Return
        return $params;
    }

    private function prepare_mailPatientVroomCreated($params)
    {
        $params = $this->prepare_mailPatientVroomShared($params);

        // Return
        return $params;
    }

    private function prepare_mailPatientVcroomCreated($params)
    {
        $params = $this->prepare_mailPatientVroomShared($params);

        return $params;
    }

    private function prepare_mailPatientVroomConsTypeChanged($params)
    {
        $params = $this->prepare_mailPatientVroomShared($params);

        $params['data']['_partials']['partial_newOtherType'] = '';
        if ($params['data']['consType'] === 'other')
        {
            $params['data']['_partials']['partial_newOtherType'] = getTranslation($this->cfg, 'mailPatientPartialNewOtherType', $params['lang']);
        }

        $params['data']['_partials']['partial_oldOtherType'] = '';
        if ($params['data']['oldType'] === 'other')
        {
            $params['data']['_partials']['partial_oldOtherType'] = getTranslation($this->cfg, 'mailPatientPartialOldOtherType', $params['lang']);
        }

        return $params;
    }

    // ---

    // TODO: сомнительный способ выбора языка для мейла...

    private function prepare_mailDoctorVroomShared($params)
    {
        // Set lang
        $params['lang'] = (isset($params['data']['vroom']['doctor']['locale'])) ? $params['data']['vroom']['doctor']['locale'] : $this->defaultTemplatesLang;

        // Merge in reservation data
        $tempParams = array(
            'reservationId' => $params['data']['reservationId'],
            'lang' => $params['lang'],
            'prepare' => true,
        );
        $params['data'] = array_merge($params['data'], $this->getReservationData($tempParams));

        // Set email to
        if (empty($params['to']))
        {
            $params['data']['doctor_email_tmp_user'] = $e1 = ( ! empty($params['data']['vroom']['doctor']['email']))
                ? $params['data']['vroom']['doctor']['email'] : '' ;
            $params['data']['doctor_email_tmp_prof_cons'] = $e2 = ( ! empty($params['data']['vroom']['doctor']['profile']['consEmail']))
                ? $params['data']['vroom']['doctor']['profile']['consEmail'] : '' ;

            $params['to'] = ( ! empty($e2)) ? $e2 : (( ! empty($e1)) ? $e1 : '');
        }

        // Set doctor vroom url
        $params['data']['doctorVroomUrl'] = '';
        if ( ! empty($params['data']['consultation_vroom_doctor']))
        {
            $tempVroomConfig = $this->cfg->get('vroom');
            $params['data']['doctorVroomUrl'] = $tempVroomConfig[$this->env . 'BaseUrl'] . $params['data']['consultation_vroom_doctor'];
        }

        // Return
        return $params;
    }

    private function prepare_mailDoctorVroomCreated($params)
    {
        $params = $this->prepare_mailDoctorVroomShared($params);

        return $params;
    }

    private function prepare_mailDoctorVroomCanceledByPatient($params)
    {
        $params = $this->prepare_mailDoctorVroomShared($params);

        return $params;
    }

    private function prepare_mailDoctorVcroomCreated($params)
    {
        $params = $this->prepare_mailDoctorVroomShared($params);

        return $params;
    }

    private function prepare_mailDoctorConfirmEmailCode($params)
    {
        // Set email to
        if (empty($params['to']))
        {
            $params['to'] = $params['data']['user']['profile']['consEmail'];
        }

        // Set lang
        $params['lang'] = (isset($params['data']['user']['locale'])) ? $params['data']['user']['locale'] : 'lv';

        // Set email confirm code
        $params['data']['emailConfirmCode'] = $params['data']['user']['profile']['emailConfCode'];

        // Return
        return $params;
    }
}