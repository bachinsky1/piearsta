<?php

require __DIR__ . '../../../vendor/autoload.php';

require_once(dirname(__FILE__) . "/../../system/config/config.cron.php");
require_once(dirname(__FILE__) . "/../../system/func/other.func.php");

$debug = DEBUG;

$faker = Faker\Factory::create();

$a = "CREATE TABLE IF NOT EXISTS `vivat_booking_requests` (
  `id` int(11) unsigned NOT NULL,
  `request_guid` varchar(70) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_datetime` datetime NOT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Iestādes/Sistēmas identetificējoši dati',
  `vp_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Vakcinācijas vietas kods',
  `queue_id` int(11) DEFAULT NULL,
  `cancel_appointment` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 - No, 1 - Yes',
  `aiis_record_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pieraksta identifikators ĀI IS. Tiks padots atcelšanas gadījumās',
  `vaccination_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time_from` datetime NOT NULL,
  `appointment_time_to` datetime NOT NULL,
  `any_other_time` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 - No, 1 - Yes',
  `patient_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `processing_request_id` int(11) DEFAULT NULL,
  `processing_status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0 - Pending',
  `processing_start_datetime` datetime DEFAULT NULL,
  `processing_end_datetime` datetime DEFAULT NULL,
  `doctors_list` text COLLATE utf8mb4_unicode_ci,
  `batch_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vp_id_dielx` (`vp_id`),
  KEY `processing_status_uhsne` (`processing_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";




for($i = 0; $i < 100; $i++) {

    $appTimeFrom = $faker->dateTimeThisDecade->format('Y-m-d H:i:s');
    $appTimeTo = date('Y-m-d H:i:s', (strtotime($appTimeFrom) + 60*60*2));

    $procStartTime = $faker->dateTimeThisDecade->format('Y-m-d H:i:s');
    $procEndTime = date('Y-m-d H:i:s', (strtotime($procStartTime) + 1));

    $reqDateTime = date('Y-m-d H:i:s', (strtotime('2021-03-16 09:00:00') + $i));

    $appDate = date('Y-m-d', strtotime($appTimeFrom));

    $patientData = json_encode(array(
        'personCode' => '030673-13129',
        'firstName' => 'Andrey',
        'lastName' => 'Voroshnin',
        'dateOfBirth' => '1973-06-03T09:15:00.000Z',
        'sex' => 0,
        'personsPhone' => array(
            'phone' => '29906927',
        ),
        'personsEmail' => array(
            'email' => 'aaa@aaa.com'
        ),
    ));

    $doctorList = json_encode(array(8,26));

//    var_dump('here1');

    $fData1 = array(
        'request_guid' => $faker->uuid,
        'request_datetime' => $reqDateTime,
        'source' => 'piearsta',
        'vp_id' => '170020401-31',
        'queue_id' => $faker->randomNumber(),
        'cancel_appointment' => '0',
        'aiis_record_id' => $faker->text(15),
        'vaccination_type' => 'Covid-19',
        'appointment_date' => $appDate,
        'appointment_time_from' => $appTimeFrom,
        'appointment_time_to' => $appTimeTo,
        'any_other_time' => '0',
        'patient_data' => $patientData,
        'processing_request_id' => $faker->randomNumber(),
        'processing_status' => '1',
        'processing_start_datetime' => $procStartTime,
        'processing_end_datetime' => $procEndTime,
        'doctors_list' => $doctorList,
        'batch_id' => 'null',
    );

//    var_dump('here2');
//    var_dump($fData1);

    saveValuesInDb('vivat_booking_requests', $fData1);
}

exit;