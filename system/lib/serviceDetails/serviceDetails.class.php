<?php

/**
 * Class ServiceDetails
 * Author: Andrey Voroshnin
 * Used in service serviceDetails / payment for services operations
 * 04.09.2019
 */
class ServiceDetails {

    /** @var config */
    private $cfg;

    /** @var db  */
    private $db;

    /** @var string */
    private $table;

    /**
     * ServiceDetails constructor.
     */
    public function __construct()
    {
        $this->db = &loadLibClass('db');
        $this->cfg = &loadLibClass('config');
        $this->table = $this->cfg->getDbTable('service', 'details');
    }

    /**
     * @param $clinicId
     * @param $serviceId
     * @param null $doctorId
     * @return array|bool|int
     */
    public function getDetailsByClinicIdAndServiseId($clinicId, $serviceId, $doctorId = null)
    {
        if(!$clinicId || !$serviceId) {
            return false;
        }

        $where = '';

        if($doctorId) {
            $where .= ' AND doctor_id = ' . $doctorId . ' ';
        }

        $dbQuery =  "SELECT * FROM " . $this->cfg->getDbTable('service', 'details') .
                    " WHERE 1 
                        AND clinic_id = " . $clinicId . "
                        AND service_id = " . $serviceId . "
                        " . $where . " 
                        AND deleted = 0";
        $query = new query($this->db, $dbQuery);

        $result = array();
        if($query->num_rows()) {
            while ($row = $query->getrow()) {
                $result[] = $row;
            }
        }

        return $result;
    }

    // This method saves data, received in _service_upload api request

    /**
     * @param array $data
     * @param $clinicId
     * @return array
     */
    public function updateServiceDetails(array $data, $clinicId)
    {
        $response = array();

        if(!isset($data['id']) || $data['id'] == null) {
            $response['success'] = false;
            $response['error'] = 'No service id';
            return $response;
        }

        if(!$clinicId) {
            $response['success'] = false;
            $response['error'] = 'No clinic id';
            return $response;
        }

        // Save data to service details table (update or create if not exist)

        // prepare for insert
        $fields = array();
        $values = array();
        $set = '';

        $set .= ' service_id = ' . $data['id'];
        $set .= ', clinic_id = ' . $clinicId;

        $fields[] = '`service_id`';
        $values[] = $data['id'];
        $fields[] = '`clinic_id`';
        $values[] = $clinicId;

        if(isset($data['doctor_id'])) {
            $fields[] = '`doctor_id`';
            $values[] = $data['doctor_id'];
            $set .= ', doctor_id = ' . $data['doctor_id'];
        }

        if(isset($data['is_active'])) {
            $fields[] = '`is_active`';
            $values[] = $data['is_active'];
            $set .= ', is_active = ' . $data['is_active'];
        }

        if(isset($data['duration'])) {

            if(!$data['duration']) {
                $duration = 'NULL';
            } else {
                $duration = $data['duration'];
            }

            $fields[] = '`duration`';
            $values[] = $duration;
            $set .= ', duration = ' . $duration;
        }

        if(isset($data['price'])) {

            if(!$data['price'] && $data['price'] !== 0 && $data['price'] !== '0') {
                $price = "NULL";
            } else {
                $price = $data['price'];
            }

            $fields[] = '`price`';
            $values[] = $price;
            $set .= ', old_price = price';
            $set .= ', price = ' . $price;
        }

        if(isset($data['description'])) {
            $fields[] = '`service_description`';
            $values[] = "'" . $data['description'] . "'";
            $set .= ", service_description = '" . $data['description'] . "'";
        }

        if(isset($data['specialitates'])) {
            $fields[] = '`specialitates`';
            $values[] = "'" . $data['specialitates'] . "'";
            $set .= ", specialitates = '" . $data['specialitates'] . "'";
        }

        if(isset($data['confirm_template'])) {
            $fields[] = '`confirm_template`';
            $values[] = "'" . $data['confirm_template'] . "'";
            $set .= ", confirm_template = '" . $data['confirm_template'] . "'";
        }

        if(isset($data['notify_template'])) {
            $fields[] = '`notify_template`';
            $values[] = "'" . $data['notify_template'] . "'";
            $set .= ", notify_template = '" . $data['notify_template'] . "'";
        }

        if(isset($data['notify_before_days'])) {
            $fields[] = '`notify_before_days`';
            $values[] = $data['notify_before_days'];
            $set .= ", notify_before_days = " . $data['notify_before_days'];
        }

        if(isset($data['popup_message'])) {
            $fields[] = '`popup_message`';
            $values[] = "'" . mres($data['popup_message']) . "'";
            $set .= ", popup_message = '" . mres($data['popup_message']) . "'";
        }

        $fields = implode(',', $fields);
        $values = implode(',', $values);

        $dbQuery = "INSERT INTO " . $this->table . "
                        (" . $fields . ") 
                        VALUES (" . $values . ")
                        ON DUPLICATE KEY UPDATE" . $set;

        $result = new query($this->db, $dbQuery);

        if($result) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
            $response['error'] = 'Error saving service details';
            $response['result'] = $result;
        }

        return $response;
    }

    /**
     * @param $id
     * @return bool|string
     */
    public function getServiceDescriptionById($id)
    {
        $dbQuery =  "SELECT service_description FROM " . $this->cfg->getDbTable('service', 'details') .
            " WHERE id = " . $id;

        $query = new query($this->db, $dbQuery);
        return $query->getOne();
    }

    // Method used to collect all services data to show in AddReservationPopup and include to services key of data array

    /**
     * @param $clinicId
     * @param $doctorId
     * @param null $slotStartTime
     * @param null $dc
     * @param null $serviceId
     * @return array
     */
    public function getServices($clinicId, $doctorId, $slotStartTime = null, $dc = null, $serviceId = null, $dcServicesList = null)
    {

        $andWhere = '';

        if($dc && $serviceId) {
            $andWhere = ' AND `DocToCl`.`cl_id` = ' . $serviceId . ' ';
        }

        if($dcServicesList) {
            $andWhere = ' AND `DocToCl`.`cl_id` IN (' . $dcServicesList . ') ';
        }

        if ($_POST['webLang']) {
            $lang = $_POST['webLang'];
        } else {
            $lang = getDefaultLang();
        }

        // get services from classifier and join services info specific for given clinic
        //
        $dbQuery = "SELECT
			`DocToCl`.`d_id`, DocToCl.length_minutes,
			`ClInfo`.*,
			Cl.piearstaId as piearstaId,
			sd.id as service_details_id,
			sd.is_active as sd_active,
			sd.service_description as service_description,
			sd.service_type as service_type,
			sd.duration as sd_duration,
			sd.old_price as old_price,
			sd.price as price,
			sd.specialitates as specialitates,
			sd.popup_message as popup_message,
			sd.confirm_template as confirm_template,
			sd.notify_template as notify_template,
			sd.notify_before_days as notify_before_days,
			sd.deleted ,
			rs.id as remote_id,
            transl.title as localized_title
		    FROM
			`" . $this->cfg->getDbTable('doctors', 'classificators') . "` as `DocToCl`
		    LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'self') . "` as `Cl` ON (
			    `Cl`.`id` = `DocToCl`.`cl_id`
			    AND `Cl`.`type` = `DocToCl`.`cl_type`
			    AND `Cl`.`enable` = 1
			)
		    LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'details') . "` as `ClInfo` ON (
			    `ClInfo`.`c_id` = `Cl`.`id`
			    AND `ClInfo`.`lang` = 'lv'
			)
			  LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'details') . "` as `transl` ON (
			    `transl`.`c_id` = `Cl`.`id`
			     AND `transl`.`lang` = '" . mres($lang) . "'
			)
			LEFT JOIN mod_service_details AS sd ON (
			    sd.service_id = Cl.id AND
			    sd.clinic_id = '" . mres($clinicId) . "' AND 
			    sd.doctor_id = 0
			 )
            LEFT JOIN mod_remote_services AS rs ON (rs.service_id = Cl.id) 
		    WHERE 1
		        AND Cl.enable = 1 
                AND (`DocToCl`.`d_id`='" . mres($doctorId) . "')
                AND `DocToCl`.`cl_type` = '4'
                AND (sd.deleted = 0 OR sd.deleted IS NULL)
                " . $andWhere . "		
		    ";
        $query = new query($this->db, $dbQuery);
        $clinicServices =  $query->getArray();

        // Get services info, specific for given doctor
        //
        $servIds = array_filter( array_column($clinicServices, 'c_id') );

        $dbQuery = "SELECT id, service_id, duration, price FROM mod_service_details
                        WHERE
                            is_active = 1 AND 
                            deleted = 0 AND 
                            clinic_id = '" . mres($clinicId) . "' AND 
                            doctor_id = '" . mres($doctorId) . "' AND
                            service_id IN(" . implode(',', $servIds) . ")";
        $query = new query($this->db, $dbQuery);
        $doctorServices = $query->getArray();

        // Merge info from doctor specific services to clinic services
        foreach ($clinicServices as $k => $service) {

            $clinicServices[$k]['isRemote'] = $service['remote_id'] ? true : false;
            $clinicServices[$k]['length_minutes'] = $clinicServices[$k]['sd_duration'] ?
                $clinicServices[$k]['sd_duration'] : $clinicServices[$k]['length_minutes'];

            if (empty($clinicServices[$k]['localized_title'])) {
                $clinicServices[$k]['localized_title'] = $clinicServices[$k]['title'];
            }

            $key = array_search($service['c_id'], array_column($doctorServices, 'service_id'));

            if($key !== false) {

                if($doctorServices[$key]['duration']) {
                    $clinicServices[$k]['length_minutes'] = $doctorServices[$key]['duration'];
                }

                if(is_string($doctorServices[$key]['price'])) {
                    $clinicServices[$k]['price'] = $doctorServices[$key]['price'];
                }
            }
        }

        if($slotStartTime) {

            // Get services info specific for time
            //
            $date = date('Y-m-d', strtotime($slotStartTime));
            $weekDay = date('w', strtotime($slotStartTime));
            $time = date('H:i:s', strtotime($slotStartTime));

            $weekDay = $weekDay == 0 ? 7 : $weekDay;

            $dbQuery = "SELECT * FROM mod_timetable_services 
                        WHERE
                            is_deleted = 0 AND 
                            clinic_id = '" . mres($clinicId) . "' AND 
                            doctor_id = '" . mres($doctorId) . "' AND
                            period_start <= '" . $date . "' AND 
                            period_end >= '" . $date . "' AND 
                            day_number = " . $weekDay . " AND 
                            start_time <= '" . $time . "' AND 
                            end_time > '" . $time . "'
                        ORDER BY order_num 
                        LIMIT 1";

            $query = new query($this->db, $dbQuery);

            if($query->num_rows()) {
                $timeInfo = $query->getrow();
            }

            if(!empty($timeInfo)) {

                if(isset($timeInfo['services']) && substr($timeInfo['services'], 0, 1) == '[') {

                    try {
                        $timeServices = json_decode($timeInfo['services']);
                    } catch (Exception $e) {
                        $timeServices = array();
                    }

                    if(count($timeServices) > 0) {

                        $existingKeys = array();

                        foreach ($timeServices as $k => $tService) {

                            $key = array_search($tService[0], array_column($clinicServices, 'piearstaId'));

                            if($key === false) {
                                $key = array_search($tService[0], array_column($clinicServices, 'c_id'));
                            }

                            if ($key !== false) {

                                $existingKeys[] = $key;

                                if ($tService[1]) {
                                    $clinicServices[$key]['length_minutes'] = $tService[1];
                                }

                                if (is_string($tService[2])) {
                                    $clinicServices[$key]['price'] = $tService[2];
                                }
                            }
                        }

                        // unset not available services
                        foreach ($clinicServices as $k => $s) {

                            if(!in_array($k, $existingKeys)) {

                                unset($clinicServices[$k]);
                            }
                        }
                    }
                }
            }

            // end of timetable services processing

        }

        // clean up repeated services

        $idsCollection = array();
        $servicesCleaned = array();

        foreach ($clinicServices as $k => $service) {

            if(!in_array($service['c_id'], $idsCollection)) {
                $servicesCleaned[] = $service;
                $idsCollection[] = $service['c_id'];
            }
        }

        // return resulting array

        return $servicesCleaned;
    }

    public function getRemoteServicesForTimeInterval($clinicId, $doctorId, $timeInterval, $startingFromSlot = null)
    {

        if ($_POST['webLang']) {
            $lang = $_POST['webLang'];
        } else {
            $lang = getDefaultLang();
        }

        // get services from classifier and join services info specific for given clinic
        //
        $dbQuery = "SELECT
			`DocToCl`.`d_id`, DocToCl.length_minutes,
			`ClInfo`.*,
			Cl.piearstaId as piearstaId,
			sd.id as service_details_id,
			sd.is_active as sd_active,
			sd.service_description as service_description,
			sd.service_type as service_type,
			sd.duration as sd_duration,
			sd.old_price as old_price,
			sd.price as price,
			sd.specialitates as specialitates,
			sd.popup_message as popup_message,
			sd.confirm_template as confirm_template,
			sd.notify_template as notify_template,
			sd.notify_before_days as notify_before_days,
			sd.deleted ,
			rs.id as remote_id,
            transl.title as localized_title
		    FROM
			`" . $this->cfg->getDbTable('doctors', 'classificators') . "` as `DocToCl`
		    LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'self') . "` as `Cl` ON (
			    `Cl`.`id` = `DocToCl`.`cl_id`
			    AND `Cl`.`type` = `DocToCl`.`cl_type`
			    AND `Cl`.`enable` = 1
			)
		    LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'details') . "` as `ClInfo` ON (
			    `ClInfo`.`c_id` = `Cl`.`id`
			    AND `ClInfo`.`lang` = 'lv'
			)
			  LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'details') . "` as `transl` ON (
			    `transl`.`c_id` = `Cl`.`id`
			     AND `transl`.`lang` = '" . mres($lang) . "'
			)
			LEFT JOIN mod_service_details AS sd ON (
			    sd.service_id = Cl.id AND
			    sd.clinic_id = '" . mres($clinicId) . "' AND 
			    sd.doctor_id = 0
			 )
            INNER JOIN mod_remote_services AS rs ON (rs.service_id = Cl.id) 
		    WHERE 1
		        AND Cl.enable = 1 
                AND (`DocToCl`.`d_id`='" . mres($doctorId) . "')
                AND `DocToCl`.`cl_type` = '4'
                AND (sd.deleted = 0 OR sd.deleted IS NULL)
		    ";

        $query = new query($this->db, $dbQuery);

        $clinicServices =  $query->getArray();

        // Get services info, specific for given doctor
        //
        $servIds = array_filter( array_column($clinicServices, 'c_id') );

        $dbQuery = "SELECT id, service_id, duration, price FROM mod_service_details
                        WHERE
                            is_active = 1 AND 
                            deleted = 0 AND 
                            clinic_id = '" . mres($clinicId) . "' AND 
                            doctor_id = '" . mres($doctorId) . "' AND
                            service_id IN(" . implode(',', $servIds) . ")";
        $query = new query($this->db, $dbQuery);
        $doctorServices = $query->getArray();

        // Merge info from doctor specific services to clinic services

        foreach ($clinicServices as $k => $service) {

            $clinicServices[$k]['isRemote'] = $service['remote_id'] ? true : false;
            $clinicServices[$k]['length_minutes'] = $clinicServices[$k]['sd_duration'] ?
                $clinicServices[$k]['sd_duration'] : $clinicServices[$k]['length_minutes'];

            if (empty($clinicServices[$k]['localized_title'])) {
                $clinicServices[$k]['localized_title'] = $clinicServices[$k]['title'];
            }

            $key = array_search($service['c_id'], array_column($doctorServices, 'service_id'));

            if($key !== false) {

                if($doctorServices[$key]['duration']) {
                    $clinicServices[$k]['length_minutes'] = $doctorServices[$key]['duration'];
                }

                if(is_string($doctorServices[$key]['price'])) {
                    $clinicServices[$k]['price'] = $doctorServices[$key]['price'];
                }
            }
        }

        if($timeInterval) {

            // Get services info specific for time

            $intStartTime = strtotime($timeInterval['start']) > strtotime($timeInterval['nbf']) ? $timeInterval['start'] : $timeInterval['nbf'];

            $date = date('Y-m-d', strtotime($timeInterval['start']));
            $weekDay = date('w', strtotime($timeInterval['start']));
            $startTime = date('H:i:s', strtotime($intStartTime));
            $endTime = date('H:i:s', strtotime($timeInterval['end']));

            $stDT = $date . ' ' . $startTime;
            $endDT = $date . ' ' . $endTime;

            $weekDay = $weekDay == 0 ? 7 : $weekDay;

            $dbQuery = "SELECT * FROM mod_timetable_services 
                        WHERE
                            is_deleted = 0 AND 
                            clinic_id = '" . mres($clinicId) . "' AND 
                            doctor_id = '" . mres($doctorId) . "' AND
                            period_start <= '" . $date . "' AND 
                            period_end >= '" . $date . "' AND 
                            day_number = " . $weekDay . " AND 
                            (
                                (start_time >= '" . $startTime . "' AND start_time < '" . $endTime . "') OR 
                                (end_time > '" . $startTime . "' AND end_time < '" . $endTime . "')
                            )
                        ORDER BY order_num 
                        LIMIT 1";

            $query = new query($this->db, $dbQuery);

            if($query->num_rows()) {
                $timeInfo = $query->getrow();
            }

            if(!empty($timeInfo)) {

                if(isset($timeInfo['services']) && substr($timeInfo['services'], 0, 1) == '[') {

                    try {
                        $timeServices = json_decode($timeInfo['services']);
                    } catch (Exception $e) {
                        $timeServices = array();
                    }

                    if(count($timeServices) > 0) {

                        $existingKeys = array();

                        foreach ($timeServices as $k => $tService) {

                            $key = array_search($tService[0], array_column($clinicServices, 'piearstaId'));

                            if ($key !== false) {

                                $existingKeys[] = $key;

                                if ($tService[1]) {
                                    $clinicServices[$key]['length_minutes'] = $tService[1];
                                }

                                if (is_string($tService[2])) {
                                    $clinicServices[$key]['price'] = $tService[2];
                                }
                            }
                        }

                        // unset not available services
                        foreach ($clinicServices as $k => $s) {

                            if(!in_array($k, $existingKeys)) {

                                unset($clinicServices[$k]);
                            }
                        }
                    }
                }
            }

            // end of timetable services processing

            // process schedules for getting duration info from slots if service length is still = 0

            foreach ($clinicServices as $k => $service) {

                if($service['length_minutes'] == 0) {

                    if($startingFromSlot) {

                        $sDbQuery = "
                            SELECT s.interval as duration FROM mod_shedules s 
                            WHERE
                                s.id = $startingFromSlot
                        ";

                    } else {

                        $sDbQuery = "
                            SELECT MIN(s.interval) as duration FROM mod_shedules s 
                            WHERE
                                s.doctor_id = $doctorId AND
                                s.booked = 0 AND 
                                s.locked = 0 AND 
                                s.start_time >= '".$stDT."' AND 
                                s.start_time < '".$endDT."'
                        ";
                    }

                    $sQuery = new query($this->db, $sDbQuery);

                    $row = $sQuery->getrow();
                    $duration = $row['duration'];

                    $clinicServices[$k]['length_minutes'] = $duration;
                }
            }

        }

        // filter by max available time

        $minServiceTime = min(array_column($clinicServices, 'length_minutes'));

        // get all slots in interval

        $sDbQuery = "
            SELECT * FROM mod_shedules s 
            WHERE
                s.doctor_id = $doctorId AND
                s.booked = 0 AND 
                s.locked = 0 AND 
                s.start_time >= '".$stDT."' AND 
                s.start_time < '".$endDT."'
        ";

        $sQuery = new query($this->db, $sDbQuery);
        $allSlots = $sQuery->getArray();

        $availableTimes = array();

        foreach ($allSlots as $slot) {

            $ind = array_search($slot['id'], array_column($allSlots, 'id'));
            $availableTimes[] = getTimeOfSequentalSlotsStartingFrom($allSlots, $ind);
        }

        $minAvailableTime = min($availableTimes);

        foreach ($clinicServices as $k => $service) {

            if($service['length_minutes'] > $minAvailableTime) {
                unset($clinicServices[$k]);
            }
        }

        // return resulting array

        return $clinicServices;
    }

    /**
     * @param $serviceId
     * @param $clinicId
     * @param $doctorId
     * @return array
     */
    public function getServiceDurationByDoctor($serviceId, $clinicId, $doctorId)
    {
        if(!is_array($serviceId)) {
            $serviceId = array($serviceId);
        }

        $result = array();

        $sDetailsClinicResult = array();
        $sDetailsDoctorResult = array();
        $classifResult = array();

        // get values from mod_service_details for clinic
        $dbQuery = "SELECT service_id, doctor_id, duration FROM mod_service_details 
                    WHERE
                        clinic_id = " . $clinicId . " AND 
                        is_active = 1 AND
                        service_id IN (" . implode(',', $serviceId) . ")";

        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            while($row = $query->getrow()) {

                if(!$row['doctor_id']) {

                    $sDetailsClinicResult[$row['service_id']] = $row['duration'];

                } elseif($row['doctor_id'] == $doctorId) {

                    $sDetailsDoctorResult[$row['service_id']] = $row['duration'];
                }
            }
        }

        // get values from classificator

        $dbQuery = "SELECT cl_id as service_id, length_minutes as duration FROM mod_doctors_to_classificators
                    WHERE cl_id IN (" . implode(',', $serviceId) . ")";
        $query = new query($this->db, $dbQuery);

        if($query->num_rows()) {

            while ($row = $query->getrow()) {
                $classifResult[$row['service_id']] = $row['duration'];
            }
        }

        foreach ($serviceId as $sId) {

            if(isset($sDetailsDoctorResult[$sId]) && $sDetailsDoctorResult[$sId]) {

                $result[$sId] = $sDetailsDoctorResult[$sId];

            } elseif (isset($sDetailsClinicResult[$sId]) && $sDetailsClinicResult[$sId]) {

                $result[$sId] = $sDetailsClinicResult[$sId];

            } elseif (isset($classifResult[$sId]) && $classifResult[$sId]) {

                $result[$sId] = $classifResult[$sId];

            } else {

                $result[$sId] = null;
            }
        }

        return $result;
    }

    /**
     * @param array $services
     * @param bool $paymentEnabled
     * @return array
     */
    public function getServiceWarningsAndMessages(array $services, $paymentEnabled = true)
    {
        $customWarnings = array();

        foreach ($services as $key => $service) {

            $service['popup_message'] = trim($service['popup_message']);
            if(isset($service['popup_message']) && is_string($service['popup_message']) && strlen($service['popup_message']) > 0) {

                if($paymentEnabled && isset($service['price']) && $service['price'] > 0) {

                    $price = '<span class="service-price">' . $service['price'] . '</span>';
                    $priceWithoutCorrections = null;

                    if(!empty($service['priceWithoutCorrections'])) {
                        $priceWithoutCorrections = '<span class="service-price">' . $service['priceWithoutCorrections'] . '</span>';
                    }

                    // Для интерактивного показа корректированной или некорректированной цены в попапе
                    // создаем сообщение для некорректированной цены если она установлена в сервисе

                    $customWarnings[$service['c_id']] = str_replace('{PRICE}', $price, $service['popup_message']);

                    if($priceWithoutCorrections) {
                        $customWarnings[$service['c_id'] . '_original'] = str_replace('{PRICE}', $priceWithoutCorrections, $service['popup_message']);
                    }

                } else {
                    // we remove html elements with class='pricing_info'
                    $msgDoc = new DOMDocument();
                    $msgDoc->loadHTML('<?xml encoding="utf-8" ?>' . $service['popup_message']);

                    $els = $msgDoc->getElementsByTagName('*');

                    if(count($els) > 0) {

                        foreach ($els as $k => $el) {

                            if($el->attributes->length > 0) {
                                for($i = 0; $i < $el->attributes->length; $i++) {

                                    if(
                                        $el->attributes->item($i)->name == 'class' &&
                                        $el->attributes->item($i)->value == 'pricing_info'
                                    ) {
                                        $el->parentNode->removeChild($el);
                                    }
                                }
                            }
                        }
                    }

                    $service['popup_message'] = $msgDoc->saveHTML();
                    $price = '<span class="service-price">0.00</span>';
                    $customWarnings[$service['c_id']] = str_replace('{PRICE}', $price, $service['popup_message']);
                }
            }
        }

        return $customWarnings;
    }

    /**
     * @param $doctorListArray
     * @return array
     */
    public function getRemoteServicesForDoctorList($doctorListArray, $timeInterval)
    {
        $dbQuery = "SELECT
            sd.service_id as c_id,
			`DocToCl`.`d_id`, DocToCl.length_minutes,
			Cl.piearstaId as piearstaId,
			sd.id as service_details_id,
			sd.is_active as sd_active,
			sd.service_description as service_description,
			sd.service_type as service_type,
			sd.duration as sd_duration,
			sd.old_price as old_price,
			sd.price as price,
			sd.specialitates as specialitates,
			sd.popup_message as popup_message,
			sd.confirm_template as confirm_template,
			sd.notify_template as notify_template,
			sd.notify_before_days as notify_before_days,
			sd.deleted ,
			rs.id as remote_id
		    FROM
			`" . $this->cfg->getDbTable('doctors', 'classificators') . "` as `DocToCl`
		    LEFT JOIN
			`" . $this->cfg->getDbTable('classificators', 'self') . "` as `Cl` ON (
			    `Cl`.`id` = `DocToCl`.`cl_id`
			    AND `Cl`.`type` = `DocToCl`.`cl_type`
			    AND `Cl`.`enable` = 1
			)
            LEFT JOIN mod_service_details AS sd ON (
			    sd.service_id = Cl.id AND
			    sd.doctor_id IN (".implode(',', $doctorListArray).")
			 )
            INNER JOIN mod_remote_services AS rs ON (rs.service_id = Cl.id) 
		    WHERE 1
		        AND Cl.enable = 1 
                AND (`DocToCl`.`d_id` IN (".implode(',', $doctorListArray)."))
                AND `DocToCl`.`cl_type` = '4'
                AND (sd.deleted = 0 OR sd.deleted IS NULL)
		    ";

        $query = new query($this->db, $dbQuery);

        $clinicServices =  $query->getArray();

        // Get services info, specific for given doctor
        //
        $servIds = array_filter( array_column($clinicServices, 'c_id') );

        $dbQuery = "SELECT id, service_id, duration, price FROM mod_service_details
                        WHERE
                            is_active = 1 AND 
                            deleted = 0 AND 
                            doctor_id IN (".implode(',', $doctorListArray).") AND
                            service_id IN(" . implode(',', $servIds) . ")";

        $query = new query($this->db, $dbQuery);

        $doctorServices = $query->getArray();

        // Merge info from doctor specific services to clinic services
        foreach ($clinicServices as $k => $service) {

            $clinicServices[$k]['isRemote'] = $service['remote_id'] ? true : false;
            $clinicServices[$k]['length_minutes'] = $clinicServices[$k]['sd_duration'] ?
                $clinicServices[$k]['sd_duration'] : $clinicServices[$k]['length_minutes'];

            if (empty($clinicServices[$k]['localized_title'])) {
                $clinicServices[$k]['localized_title'] = $clinicServices[$k]['title'];
            }

            $key = array_search($service['c_id'], array_column($doctorServices, 'service_id'));

            if($key !== false) {

                if($doctorServices[$key]['duration']) {
                    $clinicServices[$k]['length_minutes'] = $doctorServices[$key]['duration'];
                }

                if(is_string($doctorServices[$key]['price'])) {
                    $clinicServices[$k]['price'] = $doctorServices[$key]['price'];
                }
            }
        }

        if($timeInterval) {

            // Get services info specific for time

            $intStartTime = strtotime($timeInterval['start']) > strtotime($timeInterval['nbf']) ? $timeInterval['start'] : $timeInterval['nbf'];

            $date = date('Y-m-d', strtotime($timeInterval['start']));
            $weekDay = date('w', strtotime($timeInterval['start']));
            $startTime = date('H:i:s', strtotime($intStartTime));
            $endTime = date('H:i:s', strtotime($timeInterval['end']));

            $stDT = $date . ' ' . $startTime;
            $endDT = $date . ' ' . $endTime;

            $weekDay = $weekDay == 0 ? 7 : $weekDay;

            $dbQuery = "SELECT * FROM mod_timetable_services 
                        WHERE
                            is_deleted = 0 AND 
                            doctor_id IN (".implode(',', $doctorListArray).") AND
                            period_start <= '" . $date . "' AND 
                            period_end >= '" . $date . "' AND 
                            day_number = " . $weekDay . " AND 
                            (
                                (start_time >= '" . $startTime . "' AND start_time < '" . $endTime . "') OR 
                                (end_time > '" . $startTime . "' AND end_time < '" . $endTime . "')
                            )
                        ORDER BY order_num 
                        LIMIT 1";

            $query = new query($this->db, $dbQuery);

            if($query->num_rows()) {
                $timeInfo = $query->getrow();
            }

            if(!empty($timeInfo)) {

                if(isset($timeInfo['services']) && substr($timeInfo['services'], 0, 1) == '[') {

                    try {
                        $timeServices = json_decode($timeInfo['services']);
                    } catch (Exception $e) {
                        $timeServices = array();
                    }

                    if(count($timeServices) > 0) {

                        $existingKeys = array();

                        foreach ($timeServices as $k => $tService) {

                            $key = array_search($tService[0], array_column($clinicServices, 'piearstaId'));

                            if ($key !== false) {

                                $existingKeys[] = $key;

                                if ($tService[1]) {
                                    $clinicServices[$key]['length_minutes'] = $tService[1];
                                }

                                if (is_string($tService[2])) {
                                    $clinicServices[$key]['price'] = $tService[2];
                                }
                            }
                        }

                        // unset not available services
                        foreach ($clinicServices as $k => $s) {

                            if(!in_array($k, $existingKeys)) {

                                unset($clinicServices[$k]);
                            }
                        }
                    }
                }
            }

            // end of timetable services processing


            // process schedules for getting duration info from slots if service length is still = 0

            foreach ($clinicServices as $k => $service) {

                if($service['length_minutes'] == 0) {

                    $sDbQuery = "
                        SELECT MIN(s.interval) as duration FROM mod_shedules s 
                        WHERE
                            s.doctor_id IN (".implode(',', $doctorListArray).") AND 
                            s.booked = 0 AND 
                            s.locked = 0 AND 
                            s.start_time >= '".$stDT."' AND 
                            s.start_time < '".$endDT."'
                    ";

                    $sQuery = new query($this->db, $sDbQuery);

                    $row = $sQuery->getrow();

                    $clinicServices[$k]['length_minutes'] = $row['duration'];
                }
            }

        }


        // filter by max available time

        $minServiceTime = min(array_column($clinicServices, 'length_minutes'));

        // get all slots in interval

        $sDbQuery = "
            SELECT * FROM mod_shedules s 
            WHERE
                s.doctor_id IN (".implode(',', $doctorListArray).") AND
                s.booked = 0 AND 
                s.locked = 0 AND 
                s.start_time >= '".$stDT."' AND 
                s.start_time < '".$endDT."'
        ";

        $sQuery = new query($this->db, $sDbQuery);
        $allSlots = $sQuery->getArray();

        $availableTimes = array();

        foreach ($allSlots as $slot) {

            $ind = array_search($slot['id'], array_column($allSlots, 'id'));
            $availableTimes[] = getTimeOfSequentalSlotsStartingFrom($allSlots, $ind);
        }

        $minAvailableTime = min($availableTimes);

        foreach ($clinicServices as $k => $service) {

            if($service['length_minutes'] > $minAvailableTime) {
                unset($clinicServices[$k]);
            }
        }

        // return resulting array

        return $clinicServices;
    }

}
?>
