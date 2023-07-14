<?php

require_once(dirname(__FILE__) . "/../system/config/config.cron.php");

if (php_sapi_name() == 'cli') {
	
	$xmlLib = loadLibClass('xml');
	$cfg = loadLibClass('config');
	
	$host = "http://www.piearsta.lv";
	$defaultLang = 'lv';
	
	if (isset($argv[1])) {
		if ($argv[1] == 'cities') {
			
			$xmlLib->loadSimple($host . '/cron/migration/cities.xml')->loadSimpleData('classificator');
			if (count($xmlLib->getImport()) > 0) {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('classificators', 'self') . "`
							WHERE 1
								AND `type` = '" . CLASSIF_CITY . "'";
				new query($mdb, $dbQuery);

				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
					
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `enable` = '1' ";
					$dbData[] = " `sort` = '" . $cnt . "' ";
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `type` = '" . CLASSIF_CITY . "' ";
					$dbData[] = " `gcoords` = '" . mres($xmlLib->getElement($data, 'gcoords')) . "' ";
					//$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
						
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'self') . "` SET " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
					
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'details') . "` SET
											`c_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`title` = '" . mres(mres($xmlLib->getElement($data, 'name'))) . "'";
					new query($mdb, $dbQuery);
					
				}
			}
			
			
		} elseif ($argv[1] == 'districts') {
			
			$xmlLib->loadSimple($host . '/cron/migration/districts.xml')->loadSimpleData('classificator');
			if (count($xmlLib->getImport()) > 0) {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('classificators', 'self') . "`
							WHERE 1
								AND `type` = '" . CLASSIF_DISTRICT . "'";
				new query($mdb, $dbQuery);
			
				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
						
					$dbData[] = " `enable` = '1' ";
					$dbData[] = " `sort` = '" . $cnt . "' ";
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `type` = '" . CLASSIF_DISTRICT . "' ";
					$dbData[] = " `parent_id` = '" . mres($xmlLib->getElement($data, 'parent_id')) . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					//$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
			
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'self') . "` SET " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
						
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'details') . "` SET
											`c_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`title` = '" . mres(mres($xmlLib->getElement($data, 'name'))) . "'";
					new query($mdb, $dbQuery);
						
				}
			}
			
		} elseif ($argv[1] == 'ics') {
			
			$xmlLib->loadSimple($host . '/cron/migration/ics.xml')->loadSimpleData('classificator');
			if (count($xmlLib->getImport()) > 0) {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('classificators', 'self') . "`
							WHERE 1
								AND `type` = '" . CLASSIF_IC . "'";
				new query($mdb, $dbQuery);
			
				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
						
					$dbData[] = " `enable` = '1' ";
					$dbData[] = " `sort` = '" . $cnt . "' ";
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `type` = '" . CLASSIF_IC . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					//$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
						
			
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'self') . "` SET " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
						
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'details') . "` SET
											`c_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`title` = '" . mres(mres($xmlLib->getElement($data, 'name'))) . "'";
					new query($mdb, $dbQuery);
						
				}
			}
			
		} elseif ($argv[1] == 'specialities') {
			
			$xmlLib->loadSimple($host . '/cron/migration/specialities.xml')->loadSimpleData('classificator');
			if (count($xmlLib->getImport()) > 0) {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('classificators', 'self') . "`
							WHERE 1
								AND `type` = '" . CLASSIF_SPECIALTY . "'";
				new query($mdb, $dbQuery);
			
				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
						
					$dbData[] = " `enable` = '1' ";
					$dbData[] = " `sort` = '" . $cnt . "' ";
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `type` = '" . CLASSIF_SPECIALTY . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `code` = '" . mres($xmlLib->getElement($data, 'code')) . "' ";
					//$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
			
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'self') . "` SET " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
						
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'details') . "` SET
											`c_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`title` = '" . mres(mres($xmlLib->getElement($data, 'name'))) . "',
											`description` = '" . mres(mres($xmlLib->getElement($data, 'description'))) . "'";
					new query($mdb, $dbQuery);
						
				}
			}
			
		} elseif ($argv[1] == 'services') {
			
			$xmlLib->loadSimple($host . '/cron/migration/services.xml')->loadSimpleData('classificator');
			if (count($xmlLib->getImport()) > 0) {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('classificators', 'self') . "`
							WHERE 1
								AND `type` = '" . CLASSIF_SERVICE . "'";
				new query($mdb, $dbQuery);
			
				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
						
					$dbData[] = " `enable` = '1' ";
					$dbData[] = " `sort` = '" . $cnt . "' ";
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `type` = '" . CLASSIF_SERVICE . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `parent_id` = '" . mres($xmlLib->getElement($data, 'parent_id')) . "' ";
					//$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
			
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'self') . "` SET " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
						
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('classificators', 'details') . "` SET
											`c_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`title` = '" . mres(mres($xmlLib->getElement($data, 'name'))) . "'";
					new query($mdb, $dbQuery);
						
				}
			}
		} elseif ($argv[1] == 'clinics') {
			
			$xmlLib->loadSimple($host . '/cron/migration/clinics.xml')->loadSimpleData('clinica');
			if (count($xmlLib->getImport()) > 0) {
				
				if (!empty($argv[2]) && $argv[2] == 'yes') {
					$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('clinics', 'self') . "`
							WHERE 1 AND `local` != '1'";
					new query($mdb, $dbQuery);
				}
				
			
				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
					
					//$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `terminal_id` = '" . mres($xmlLib->getElement($data, 'terminal_id')) . "' ";
					$dbData[] = " `client_id` = '" . mres($xmlLib->getElement($data, 'client_id')) . "' ";
					
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `updated` = '" . time() . "' ";
					
					$dbData[] = " `city` = " . getClIdByTitle($xmlLib->getElement($data, 'city'), CLASSIF_CITY) . " ";
					$dbData[] = " `district` = " . getClIdByTitle($xmlLib->getElement($data, 'district'), CLASSIF_DISTRICT) . " ";
					
					$dbData[] = " `name` = '" . mres($xmlLib->getElement($data, 'name')) . "' ";
					$dbData[] = " `reg_nr` = '" . mres($xmlLib->getElement($data, 'registration_number')) . "' ";
					$dbData[] = " `logo` = '" . mres(copyImageAndGetFileName($xmlLib->getElement($data, 'logo'), array('clinics/', 'clinics/list/', 'clinics/open/'))) . "' ";
					$dbData[] = " `work_time` = '" . mres($xmlLib->getElement($data, 'work_time')) . "' ";
					$dbData[] = " `zip` = '" . mres($xmlLib->getElement($data, 'zip')) . "' ";
					$dbData[] = " `lat` = '" . mres($xmlLib->getElement($data, 'lat')) . "' ";
					$dbData[] = " `lng` = '" . mres($xmlLib->getElement($data, 'lng')) . "' ";
					$dbData[] = " `login` = '" . mres($xmlLib->getElement($data, 'login')) . "' ";
					$dbData[] = " `password` = '" . mres($xmlLib->getElement($data, 'password')) . "' ";
					$dbData[] = " `url` = '" . mres(convertUrl($xmlLib->getElement($data, 'name')) . '-' . $xmlLib->getElement($data, 'client_id')) . "' ";
					$dbData[] = " `type` = '" . mres($xmlLib->getElement($data, 'type')) . "' ";
					
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('clinics', 'self') . "` SET " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
					
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('clinics', 'info') . "` SET
											`clinic_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`address` = '" . mres($xmlLib->getElement($data, 'address')) . "',
											`description` = '" . mres($xmlLib->getElement($data, 'description')) . "',
											`keywords` = '" . mres($xmlLib->getElement($data, 'keywords')) . "'";
					new query($mdb, $dbQuery);
					
					
					if (isset($data->contacts, $data->contacts->contact)) {
						$default = false;
						foreach ($data->contacts->contact AS $contact) {
							
							if (!$default) {
								$data = array(
									'clinic_id' => $id,
									'email' => $xmlLib->getElement($contact, 'email'),
									'phone' => $xmlLib->getElement($contact, 'phones'),
									'default' => 1,
								);
								$default = true;
							} else {
								$data = array(
									'clinic_id' => $id,
									'email' => $xmlLib->getElement($contact, 'email'),
									'phone' => $xmlLib->getElement($contact, 'phones'),
									'default' => '0',
								);
							}
							
							$contactId = saveValuesInDb($cfg->getDbTable('clinics', 'contacts'), $data);
								
							$data = array(
								'clinic_contact_id' => $contactId,
								'lang' => $defaultLang,
								'name' => $xmlLib->getElement($contact->name, 'lv'),
							);
							
							saveValuesInDb($cfg->getDbTable('clinics', 'contacts_info'), $data);
							
						}
					}	
				}
			}
		} elseif ($argv[1] == 'clients') {
			
			$xmlLib->loadSimple($host . '/cron/migration/clients.xml')->loadSimpleData('client');
			
			if (count($xmlLib->getImport()) > 0) {
				
				if (!empty($argv[2]) && $argv[2] == 'yes') {
					$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('profiles', 'self') . "`
							WHERE 1 AND `piearstaId` != '' AND `piearstaId` != '0'";
					new query($mdb, $dbQuery);
				}
				
				$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('doctors', 'self') . "`
							WHERE 1 AND `piearstaId` != ''";
				$query = new query($mdb, $dbQuery);
				$doctors = array();
				while($query->getrow()) {
					$doctors[$query->field('piearstaId')] = $query->field('id');
				}
				$query->free();
					
				$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('clinics', 'self') . "`
							WHERE 1 AND `piearstaId` != ''";
				$query = new query($mdb, $dbQuery);
				$clinics = array();
				while($query->getrow()) {
					$clinics[$query->field('piearstaId')] = $query->field('id');
				}
				$query->free();
				
			
				$cnt = 0;
				echo "Total count: ". count($xmlLib->getImport())  . PHP_EOL;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					
					$dbData = array();
					
					$personId = $xmlLib->getElement($data, 'person_code');
					if ($personId && strpos($personId, "-") === false) {
						$personId = substr($personId, 0, 6) . '-' . substr($personId, 6);
					}

					$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `updated` = '" . time() . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `email` = '" . mres($xmlLib->getElement($data, 'email')) . "' ";
					$dbData[] = " `password` = '" . mres($xmlLib->getElement($data, 'password')) . "' ";
					$dbData[] = " `person_id` = '" . mres($personId) . "' ";
					$dbData[] = " `name` = '" . mres($xmlLib->getElement($data, 'name')) . "' ";
					$dbData[] = " `surname` = '" . mres($xmlLib->getElement($data, 'surname')) . "' ";
					$dbData[] = " `phone` = '" . mres($xmlLib->getElement($data, 'phone')) . "' ";
					$dbData[] = " `resident` = '1' ";
					$dbData[] = " `gender` = '" . mres($xmlLib->getElement($data, 'gender')) . "' ";
					$dbData[] = " `enable` = '" . ($xmlLib->getElement($data, 'enabled') == 'true' ? '1' : '0') . "' ";
					$dbData[] = " `deleted` = '" . ($xmlLib->getElement($data, 'deleted') == 'false' ? '0' : '1') . "' ";
					$dbData[] = " `deleted_at` = '" . strtotime($xmlLib->getElement($data, 'deleted_at')) . "' ";
					$dbData[] = " `hash_confirm` = '" . ($xmlLib->getElement($data, 'confirmed') == '1' ? '' : md5(time() . $xmlLib->getElement($data, 'piearstaId'))) . "' ";
					$dbData[] = " `created` = '" . strtotime($xmlLib->getElement($data, 'created')) . "' ";
					$dbData[] = " `insurance_number` = '" . mres($xmlLib->getElement($data, 'insurance_number')) . "' ";
					$dbData[] = " `insurance_id` = '" . mres(getClIdByPiearstaId($xmlLib->getElement($data, 'insurance_id'), CLASSIF_IC)) . "' ";
					$dbData[] = " `city_id` = '" . mres(getClIdByPiearstaId($xmlLib->getElement($data, 'city_id'), CLASSIF_CITY)) . "' ";
					$dbData[] = " `district_id` = '" . mres(getClIdByPiearstaId($xmlLib->getElement($data, 'district_id'), CLASSIF_DISTRICT)) . "' ";
					$dbData[] = " `person_number` = '" . mres($xmlLib->getElement($data, 'person_number')) . "' ";
					$dbData[] = " `date_of_birth` = '" . mres($xmlLib->getElement($data, 'date_of_birth')) . "' ";
					$dbData[] = " `email_notifications` = '" . ($xmlLib->getElement($data, 'email_notifications') == 'true' ? '1' : '0') . "' ";
					$dbData[] = " `sms_notifications` = '" . ($xmlLib->getElement($data, 'sms_notifications') == 'true' ? '1' : '0') . "' ";
					
					$dbQuery = "REPLACE INTO `" . $cfg->getDbTable('profiles', 'self') . "` SET " . implode(',', $dbData); 
									//" ON DUPLICATE KEY UPDATE " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					$id = $mdb->get_insert_id();
					
					if (isset($data->messages, $data->messages->message)) {
						
						foreach ($data->messages->message AS $message) {
							$dbData = array();
							$dbData['profile_id'] = $id;
							$dbData['message'] = $xmlLib->getElement($message, 'body');
							$dbData['subject'] = $xmlLib->getElement($message, 'subject');
							$dbData['created'] = strtotime($xmlLib->getElement($message, 'created'));
							$dbData['readed'] = strtotime($xmlLib->getElement($message, 'readed'));
							
							saveValuesInDb($cfg->getDbTable('profiles', 'messages'), $dbData);
						}
					}

					if (isset($data->doctors, $data->doctors->doctor)) {
						
						foreach ($data->doctors->doctor AS $doctor) {
							
							
							if (isset($doctors[mres($xmlLib->getElement($doctor, 'id'))]) && isset($clinics[mres($xmlLib->getElement($doctor, 'clinic_id'))])) {
								$dbData = array();
								$dbData['profile_id'] = $id;
								
								$dbData['doctor_id'] = ($xmlLib->getElement($doctor, 'id') && isset($doctors[mres($xmlLib->getElement($doctor, 'id'))]) ? $doctors[mres($xmlLib->getElement($doctor, 'id'))] : null);
								$dbData['clinic_id'] = ($xmlLib->getElement($doctor, 'clinic_id') && isset($clinics[mres($xmlLib->getElement($doctor, 'clinic_id'))]) ? $clinics[mres($xmlLib->getElement($doctor, 'clinic_id'))] : null);
								
								saveValuesInDb($cfg->getDbTable('profiles', 'doctors'), $dbData);
							}
							
						}
					}
				}
			}
		} elseif ($argv[1] == 'shedules') {
			
			if (!empty($argv[2]) && $argv[2] == 'yes') {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('shedule', 'self') . "`
							WHERE 1";
				new query($mdb, $dbQuery);
			}
			
			$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('doctors', 'self') . "`
							WHERE 1";
			$query = new query($mdb, $dbQuery);
			$doctors = array();
			while($query->getrow()) {
				$doctors[$query->field('piearstaId')] = $query->field('id');
			}
			$query->free();
				
			$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('clinics', 'self') . "`
							WHERE 1";
			$query = new query($mdb, $dbQuery);
			$clinics = array();
			while($query->getrow()) {
				$clinics[$query->field('piearstaId')] = $query->field('id');
			}
			$query->free();
			
			$cnt = 0;
			$files = glob(AD_SRV_ROOT.'/cron/migration/shedules*');
			foreach ($files AS $file) {
				
				$xmlLib->loadSimple($host . '/cron/migration/' . basename($file))->loadSimpleData('shedule');
				if (count($xmlLib->getImport()) > 0) {

					foreach ($xmlLib->getImport() AS $data) {
						$dbData = array();
						
// 						if ($xmlLib->getElement($data, 'date') == '2015-06-15' && $xmlLib->getElement($data, 'doctor_id') == 135) {
// 							var_dump($data);
// 						}
// 						continue;
						
						echo ++$cnt . PHP_EOL;
						
						
						if ($xmlLib->getElement($data, 'is_holiday') == 'TRUE' || $xmlLib->getElement($data, 'is_resource_holiday') == 'TRUE') {
							$dbData[] = " `booked` = '1' ";
						}
						
						$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
						$dbData[] = " `doctor_id` = " . ($xmlLib->getElement($data, 'doctor_id') && isset($doctors[mres($xmlLib->getElement($data, 'doctor_id'))]) ? $doctors[mres($xmlLib->getElement($data, 'doctor_id'))] : null) . " ";
						$dbData[] = " `clinic_id` = " .  getDefaultDoctorClinic(mres($xmlLib->getElement($data, 'doctor_id'))) . " ";
						$dbData[] = " `date` = '" . mres($xmlLib->getElement($data, 'date')) . "' ";
						$dbData[] = " `start_time` = '" . mres($xmlLib->getElement($data, 'date') . ' ' . $xmlLib->getElement($data, 'start_time')) . "' ";
						$dbData[] = " `end_time` = '" . mres($xmlLib->getElement($data, 'date') . ' ' . $xmlLib->getElement($data, 'end_time')) . "' ";
						$dbData[] = " `interval` = '" . mres($xmlLib->getElement($data, 'interval')) . "' ";
						
						$dbQuery = "INSERT INTO `" . $cfg->getDbTable('shedule', 'self') . "` SET " . implode(',', $dbData);
						new query($mdb, $dbQuery);
					}	
				}	
			}
		} elseif ($argv[1] == 'doctors') {
			
			$xmlLib->loadSimple($host . '/cron/migration/doctors.xml')->loadSimpleData('doctor');
			if (count($xmlLib->getImport()) > 0) {
				
				if (!empty($argv[2]) && $argv[2] == 'yes') {
					$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('doctors', 'self') . "`
							WHERE 1";
					new query($mdb, $dbQuery);
				}
				
			
				$cnt = 0;
				foreach ($xmlLib->getImport() AS $data) {
					echo ++$cnt . PHP_EOL;
					$dbData = array();
						
					$dbData[] = " `created` = '" . time() . "' ";
					$dbData[] = " `updated` = '" . time() . "' ";
					$dbData[] = " `id` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					$dbData[] = " `piearstaId` = '" . mres($xmlLib->getElement($data, 'piearstaId')) . "' ";
					
					$dbData[] = " `hsp_resource_id` = '" . $xmlLib->getElement($data, 'doctorid') . "' ";
					
					$dbData[] = " `photo` = '" . mres(copyImageAndGetFileName($xmlLib->getElement($data, 'photo'), array('doctors/', 'doctors/list/', 'doctors/open/'))) . "' ";
					$dbData[] = " `phone` = '" . mres($xmlLib->getElement($data, 'phone')) . "' ";
					$dbData[] = " `email` = '" . mres($xmlLib->getElement($data, 'email')) . "' ";
					
					$dbData[] = " `enabled` = '" . ($xmlLib->getElement($data, 'enabled') == 'TRUE' ? '1' : '0') . "' ";
					$dbData[] = " `deleted` = '" . ($xmlLib->getElement($data, 'deleted') == 'TRUE' ? '1' : '0') . "' ";
					$dbData[] = " `url` = '" . mres(convertUrl($xmlLib->getElement($data, 'name') . '-' . $xmlLib->getElement($data, 'surname')) . '-' . $xmlLib->getElement($data, 'piearstaId')) . "' ";
					
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('doctors', 'self') . "` SET " . implode(',', $dbData) .
									" ON DUPLICATE KEY UPDATE " . implode(',', $dbData);
					new query($mdb, $dbQuery);
					//$id = $mdb->get_insert_id();
					$id = $xmlLib->getElement($data, 'piearstaId');
					
					$dbQuery = "INSERT INTO `" . $cfg->getDbTable('doctors', 'info') . "` SET
											`doctor_id` = '" . $id . "',
											`lang` = '" . $defaultLang . "',
											`name` = '" . mres($xmlLib->getElement($data, 'name')) . "',
											`surname` = '" . mres($xmlLib->getElement($data, 'surname')) . "',
											`description` = '" . mres($xmlLib->getElement($data, 'about')) . "'";
					new query($mdb, $dbQuery);
					
					if (isset($data->clinics, $data->clinics->clinic)) {
					
						foreach ($data->clinics->clinic AS $info) {
							$dbData = array();
							$dbData['d_id'] = $id;
							$dbData['c_id'] = getClinicById($info);
					
							saveValuesInDb($cfg->getDbTable('doctors', 'clinics'), $dbData);
						}
					}
					
					if (isset($data->services, $data->services->service)) {
							
						foreach ($data->services->service AS $info) {
							$dbData = array();
							$dbData['d_id'] = $id;
							$dbData['cl_type'] = CLASSIF_SERVICE;
							$dbData['cl_id'] = getClIdByPiearstaId($info, CLASSIF_SERVICE);
							
							saveValuesInDb($cfg->getDbTable('doctors', 'classificators'), $dbData);
						}
					}
					
					if (isset($data->specialities, $data->specialities->speciality)) {
							
						foreach ($data->specialities->speciality AS $info) {
							$dbData = array();
							$dbData['d_id'] = $id;
							$dbData['cl_type'] = CLASSIF_SPECIALTY;
							$dbData['cl_id'] = getClIdByPiearstaId($info, CLASSIF_SPECIALTY);
							
							saveValuesInDb($cfg->getDbTable('doctors', 'classificators'), $dbData);
						}
					}
						
				}
			}
		} elseif ($argv[1] == 'reservations') {
			
			if (!empty($argv[2]) && $argv[2] == 'yes') {
				$dbQuery = "DELETE
							FROM `" . $cfg->getDbTable('reservations', 'self') . "`
							WHERE 1";
				$query = new query($mdb, $dbQuery);
				$query->free();
			}
			
			$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('doctors', 'self') . "`
							WHERE 1";
			$query = new query($mdb, $dbQuery);
			$doctors = array();
			while($query->getrow()) {
				$doctors[$query->field('piearstaId')] = $query->field('id');
			}
			$query->free();
			
			$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('clinics', 'self') . "`
							WHERE 1";
			$query = new query($mdb, $dbQuery);
			$clinics = array();
			while($query->getrow()) {
				$clinics[$query->field('piearstaId')] = $query->field('id');
			}
			$query->free();
			
			$dbQuery = "SELECT id, piearstaId
							FROM `" . $cfg->getDbTable('profiles', 'self') . "`
							WHERE 1";
			$query = new query($mdb, $dbQuery);
			$profiles = array();
			while($query->getrow()) {
				$profiles[$query->field('piearstaId')] = $query->field('id');
			}
			$query->free();
			
			$cnt = 0;
			$files = glob(AD_SRV_ROOT.'/cron/migration/reservations*');
			foreach ($files AS $file) {
				
				$xmlLib->loadSimple($host . '/cron/migration/' . basename($file))->loadSimpleData('reservation');
				if (count($xmlLib->getImport()) > 0) {
					
					$dbQuery = "";
					$counter = count($xmlLib->getImport());
					foreach ($xmlLib->getImport() AS $data) {
						
						$dbData = array();
						
// 						if ($xmlLib->getElement($data, 'piearstaId') == 1422242) {
// 							//var_dump($data);
// 						}
						
// 						if ($xmlLib->getElement($data, 'doctor_id') != 135) {
// 							continue;
// 						}
						
// 						if ($xmlLib->getElement($data, 'start') != '2015-06-16 12:25:00') {
// 							continue;
// 						}
						
						//INSERT INTO mod_reservations (`status`,`id`,`piearstaId`,`hsp_reservation_id`,`doctor_id`,`clinic_id`,`profile_id`,`start`,`end`,`created`,`notice`,`service_id) VALUES ('2','1422242','1422242','164199','135','1105','1','2015-06-17 14:40:00','2015-06-17 15:05:00','1425654567','created through API for resource_id: 135 hsp_id: 61-83 at: 2015-03-06 17:09:27','')
						
						//continue;
						//var_dump($data);
						
						echo ++$cnt . PHP_EOL;
						
						$book = false;
						if ($xmlLib->getElement($data, 'status') == 'none') {
							$dbData['status'] = '0';
							$book = true;
						} elseif ($xmlLib->getElement($data, 'status') == 'confirmed') {
							$dbData['status'] = 2;
							$book = true;
						} elseif ($xmlLib->getElement($data, 'status') == 'canceled') {
							$dbData['status'] = 1;
						} elseif ($xmlLib->getElement($data, 'status') == 'out of timeslot') {
							$dbData['status'] = 4;
						} else {
							$dbData['status'] = 4;
						}
						
						$dbData['id'] = $xmlLib->getElement($data, 'piearstaId');
						$dbData['piearstaId'] = $xmlLib->getElement($data, 'piearstaId');
						//$dbData['resource_id'] = $xmlLib->getElement($data, 'resource_id');
						$dbData['hsp_reservation_id'] = $xmlLib->getElement($data, 'hsp_reservation_id');
						//$dbData['hsp_id'] = $xmlLib->getElement($data, 'hsp_id');

						$dbData['doctor_id'] = ($xmlLib->getElement($data, 'doctor_id') && isset($doctors[mres($xmlLib->getElement($data, 'doctor_id'))]) ? $doctors[mres($xmlLib->getElement($data, 'doctor_id'))] : 'null');
						$dbData['clinic_id'] = ($xmlLib->getElement($data, 'clinic_id') && isset($clinics[mres($xmlLib->getElement($data, 'clinic_id'))]) ? $clinics[mres($xmlLib->getElement($data, 'clinic_id'))] : 'null');
						if ($xmlLib->getElement($data, 'client_id') && isset($profiles[mres($xmlLib->getElement($data, 'client_id'))])) {
							$dbData['profile_id'] =  $profiles[mres($xmlLib->getElement($data, 'client_id'))];
						}
						
						$dbData['start'] = $xmlLib->getElement($data, 'start');
						$dbData['end'] = $xmlLib->getElement($data, 'end');					
						
						$dbData['created'] = strtotime($xmlLib->getElement($data, 'created'));
						$dbData['notice'] = $xmlLib->getElement($data, 'notice');
						
						$dbData['service_id'] = getClIdByPiearstaId(mres($xmlLib->getElement($data, 'service_id')), CLASSIF_SERVICE);

						if (isset($doctors[mres($xmlLib->getElement($data, 'doctor_id'))]) && isset($clinics[mres($xmlLib->getElement($data, 'clinic_id'))])) {
							if ($book) {
								bookShedule($xmlLib->getElement($data, 'start'), $xmlLib->getElement($data, 'end'), $doctors[mres($xmlLib->getElement($data, 'doctor_id'))], $clinics[mres($xmlLib->getElement($data, 'clinic_id'))]);
							}
							
						}
						
						if (!empty($argv[3]) && $argv[3] == 'yes') {
							
							if ($cnt == 1) {
								$dbQuery = $dbQueryStart = saveValuesInDb($cfg->getDbTable('reservations', 'self'), $dbData, '', false, true);
							} else {
								$dbQuery .= saveValuesInDb($cfg->getDbTable('reservations', 'self'), $dbData, '', true);
							}
							
							if ($cnt % 1000 == 0) {
								
								$query = new query($mdb, $dbQuery);
								$query->free();
								$dbQuery = $dbQueryStart;
							}
							
						} else {
							//$dbQuery = "INSERT INTO `" . $cfg->getDbTable('reservations', 'self') . "` SET " . implode(',', $dbData) . ";";
							//$query = new query($mdb, $dbQuery);
							//$query->free();
							
							saveValuesInDb($cfg->getDbTable('reservations', 'self'), $dbData);
						}

					}	
					
					if ($dbQuery != "") {
						$query = new query($mdb, $dbQuery);
						$query->free();
						$dbQuery = "";
					}
				}	
			}
		}
	}	
}

function copyImageAndGetFileName($link, $folder)
{ 

	if ($link != '') {
		if (is_array($folder)) {
			
			foreach ($folder AS $f) {
				copy($link, AD_SERVER_UPLOAD_FOLDER . $f . basename($link));
			}
			
		} else {
			copy($link, AD_SERVER_UPLOAD_FOLDER . $folder . basename($link));
		}
		
		return basename($link);
	}
	
}

function _getDoctorById($id) {
	global $mdb, $cfg;
	
	if ($id == '') {
		return false;
	}
	
	$dbQuery = "SELECT id 
							FROM `" . $cfg->getDbTable('doctors', 'self') . "`
							WHERE 1
								AND `piearstaId` = '" . mres($id) . "'
							LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		return $query->getOne();
	}
	
	return null;
}

function getDefaultDoctorClinic($id) {
	global $mdb, $cfg;
	
	$dbQuery = "SELECT id
							FROM `" . $cfg->getDbTable('doctors', 'self') . "`
							WHERE 1
								AND `piearstaId` = '" . mres($id) . "'
							LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		$doctorId = $query->getOne();
		
		$dbQuery = "SELECT c_id
							FROM `" . $cfg->getDbTable('doctors', 'clinics') . "`
							WHERE 1
								AND `d_id` = '" . mres($doctorId) . "'
							LIMIT 1";
		$query = new query($mdb, $dbQuery);
		if ($query->num_rows()) {
			return $query->getOne();
		}
		
		return false;
	}
	
	return null;
}

function _getClinicById($id) {
	global $mdb, $cfg;
	
	if ($id == '') {
		return false;
	}
	
	$dbQuery = "SELECT id
							FROM `" . $cfg->getDbTable('clinics', 'self') . "`
							WHERE 1
								AND `piearstaId` = '" . mres($id) . "'
							LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		return $query->getOne();
	}

	return null;
}

function getProfileById($id) {
	global $mdb, $cfg;
	
	if ($id == '') {
		return false;
	}

	$dbQuery = "SELECT *
							FROM `" . $cfg->getDbTable('profiles', 'self') . "`
							WHERE 1
								AND `piearstaId` = '" . mres($id) . "'
							LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		return $query->getrow();
	}

	return null;
}

function bookShedule($start, $end, $doctorId, $clinicId) {
	global $mdb, $cfg;
	
	$ids = array();
	
	$date = date("Y-m-d", strtotime($start));
	
	$dbQuery = "SELECT start_time FROM `" . $cfg->getDbTable('shedule', 'self') . "`
						WHERE 1
							AND `clinic_id` = '" . mres($clinicId) . "'
							AND `doctor_id` = '" . mres($doctorId) . "' 
							AND `start_time` <= '" . mres($start) . "'
							AND `date` = '" . mres($date) . "'
						ORDER BY `start_time` DESC			
						LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		$startTime =  $query->getOne();
	}
	

	$dbQuery = "SELECT end_time FROM `" . $cfg->getDbTable('shedule', 'self') . "`
					WHERE 1
						AND `clinic_id` = '" . mres($clinicId) . "'
						AND `doctor_id` = '" . mres($doctorId) . "'
						AND `end_time` >= '" . mres($end) . "'
						AND `date` = '" . mres($date) . "'		
					ORDER BY `end_time` ASC
					LIMIT 1";
	$query = new query($mdb, $dbQuery);
	while ($query->getrow()) {
		$endTime = $query->getOne();
	}

	
	
	if (isset($startTime, $endTime)) {
		$dbQuery = "UPDATE `" . $cfg->getDbTable('shedule', 'self') . "`
								SET `booked` = 1
								WHERE 1
									AND `start_time` >= '" . mres($startTime) . "'
									AND `end_time` <= '" . mres($endTime) . "'
									AND `clinic_id` = '" . mres($clinicId) . "'
									AND `doctor_id` = '" . mres($doctorId) . "'";
		new query($mdb, $dbQuery);
	} elseif (isset($startTime) && !isset($endTime)) {
		$dbQuery = "UPDATE `" . $cfg->getDbTable('shedule', 'self') . "`
								SET `booked` = 1
								WHERE 1
									AND `start_time` >= '" . mres($startTime) . "'
									AND `clinic_id` = '" . mres($clinicId) . "'
									AND `doctor_id` = '" . mres($doctorId) . "'
									AND `date` = '" . mres($date) . "'";
		new query($mdb, $dbQuery);
	}
	
}

function getClIdByTitle($title, $type)
{
	global $mdb, $cfg;
	
	if (empty($title)) {
		return 'null';
	}
	
	$dbQuery = "SELECT c.id
							FROM `" . $cfg->getDbTable('classificators', 'self') . "` c
								LEFT JOIN `" . $cfg->getDbTable('classificators', 'details') . "` cd ON (c.id = cd.c_id)
							WHERE 1
								AND `c`.`type` = '" . mres($type) . "'
								AND `cd`.`title` = '" . mres($title) . "'
							LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		return $query->getOne();
	}

	return 'null';
}

function getClIdByPiearstaId($id, $type)
{
	global $mdb, $cfg;

	$dbQuery = "SELECT id
							FROM `" . $cfg->getDbTable('classificators', 'self') . "`
							WHERE 1
								AND `type` = '" . mres($type) . "'
								AND `piearstaId` = '" . mres($id) . "'
							LIMIT 1";
	$query = new query($mdb, $dbQuery);
	if ($query->num_rows()) {
		return $query->getOne();
	}

	return null;
}
