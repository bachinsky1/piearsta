/*
    From VARCHAR(255) to TEXT since values can be longer then 255
    Database: piearsta_2015
*/
ALTER TABLE `mod_reservations` MODIFY COLUMN `consultation_vroom` TEXT;
ALTER TABLE `mod_reservations` MODIFY COLUMN `consultation_vroom_doctor` TEXT;
ALTER TABLE `mod_reservations` MODIFY COLUMN `vchat_room` TEXT;
ALTER TABLE `mod_reservations` MODIFY COLUMN `vchat_room_doctor` TEXT;

ALTER TABLE `sm_booking_batches` MODIFY COLUMN `batch_id` INT(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_booking_batches` ADD COLUMN `batch_guid` VARCHAR(50) NULL DEFAULT NULL AFTER `batch_id`;
ALTER TABLE `sm_booking_batches` ADD INDEX `batch_guid` (`batch_guid`);

ALTER TABLE `vivat_booking_requests` ADD INDEX `queue_id_index` (`queue_id`);

ALTER TABLE `vivat_booking_requests` ADD COLUMN `sm_response_time` FLOAT UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_booking_batches` ADD COLUMN `process_time` FLOAT UNSIGNED NULL DEFAULT NULL;



=========

# check if all indicies exist on vaccination_cron_log table
# and create ones if not exist

ALTER TABLE `vaccination_cron_log` ADD INDEX `method_index` (`method`);
ALTER TABLE `vaccination_cron_log` ADD INDEX `params_index` (`params`);
ALTER TABLE `vaccination_cron_log` ADD INDEX `status_index` (`status`);
ALTER TABLE `vaccination_cron_log` ADD INDEX `start_time_index` (`start_time`);
ALTER TABLE `vaccination_cron_log` ADD INDEX `expiration_time_index` (`expiration_time`);

ALTER TABLE vivat_booking_requests 
MODIFY COLUMN patient_data json NOT NULL;

ALTER TABLE vivat_booking_requests 
ADD COLUMN pk VARCHAR(20) GENERATED ALWAYS AS (patient_data->>"$.personCode") STORED;

ALTER TABLE vivat_booking_requests ADD INDEX pk_index (pk);

==========

ALTER TABLE `vaccination_cron_log`
    ADD COLUMN `result_message` TEXT NOT NULL DEFAULT '' AFTER `error_message`;

ALTER TABLE `vaccination_cron_log`
    ADD COLUMN `cronjob_id` INT UNSIGNED NULL DEFAULT NULL AFTER `params`,
    ADD INDEX `cronjob_id` (`cronjob_id`);

ALTER TABLE `vaccination_cron_log`
    ADD INDEX `method_cron_id_status` (`method`, `cronjob_id`, `status`);

=========

ALTER TABLE vivat_cache_upload_log ADD error_message TEXT NOT NULL;

=======

ALTER TABLE  `vivat_booking_requests` 
ADD COLUMN `count` 
INT(11) DEFAULT 1;

======

ALTER TABLE `vivat_cache_log` ADD INDEX `generation_end_index` (`generation_end`);
ALTER TABLE `vivat_cache_upload_log` ADD INDEX `end_time_index` (`end_time`);
ALTER TABLE `vivat_booking_requests` ADD INDEX `request_datetime_index` (`request_datetime`);
ALTER TABLE `vivat_auth_tokens` ADD INDEX `expired_at_index` (`expired_at`);
ALTER TABLE `vaccination_cron_log` ADD INDEX `expiration_time_index` (`expiration_time`);
ALTER TABLE `sm_booking_batches` ADD INDEX `end_time_index` (`end_time`);

=======

ALTER TABLE mod_profiles
    DROP INDEX email;

ALTER TABLE mod_profiles
    ADD CONSTRAINT email_enable UNIQUE (email, enable);

========
UPDATE ad_sitedata
SET mlang = 1
WHERE tab LIKE 'Cancellation reasons%';

UPDATE `ad_sitedata_values` adsv
    INNER JOIN `ad_sitedata` ads ON `adsv`.`fid` = `ads`.`id`
    SET adsv.lang = 'lv'
WHERE `ads`.`tab` LIKE 'Cancellation reasons%' AND `ads`.`mlang` = 1;


==========

ALTER TABLE `mod_reservations` ADD COLUMN `show_up` TinyInt(1) NULL DEFAULT NULL COMMENT 'reserved appointment happened ?';


ALTER TABLE `mod_profiles_messages` ADD COLUMN `clinic_id` INT(11) UNSIGNED NULL DEFAULT NULL;