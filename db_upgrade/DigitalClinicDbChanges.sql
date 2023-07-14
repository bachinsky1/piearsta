============
http://gitlab.bb-tech.eu/Developers/piearsta/merge_requests/605

ALTER TABLE `mod_doctors` ADD COLUMN `is_hidden_on_piearsta` TinyInt(1) DEFAULT 0;
ALTER TABLE `mod_doctors` ADD COLUMN `is_available_on_digital_clinic` TinyInt(1) DEFAULT 0;

============

CREATE TABLE `mod_reservation_options` (
   `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
   `reservation_id` INT(11) UNSIGNED NOT NULL,
   `options` JSON NULL DEFAULT NULL,
   PRIMARY KEY (`id`),
   INDEX `reservation_id` (`reservation_id`)
)
ENGINE=InnoDB;

============

ALTER TABLE `mod_users_sessions`
    ADD COLUMN `session_key` VARCHAR(128) NOT NULL DEFAULT '' AFTER `ip_address`;

=============

CREATE TABLE `mod_doctor_languages` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `doctor_id` INT(11) UNSIGNED NOT NULL,
    `language_code` VARCHAR(2) NOT NULL DEFAULT 'lv',
    PRIMARY KEY (`id`),
    INDEX `doctor_id` (`doctor_id`),
    INDEX `language_code` (`language_code`)
)
    ENGINE=InnoDB;

=====================

ALTER TABLE `mod_shedules_lock`
    ADD COLUMN `dc_price` DECIMAL(10,2) UNSIGNED NULL DEFAULT NULL AFTER `status`;

ALTER TABLE `mod_shedules_lock`
    ADD COLUMN `dc_duration` INT(4) UNSIGNED NULL DEFAULT NULL AFTER `dc_price`;

=====================

ALTER TABLE mod_reservations ADD COLUMN made_by_profile_id INT(11) DEFAULT NULL AFTER profile_id;

ALTER TABLE mod_reservations MODIFY COLUMN status tinyint(4) DEFAULT 0 NOT NULL COMMENT '0: Gaida apstiprinājumu, 1: Noraidīts, 2: Aktīvs, 3: Atcelts, 4: Arhīvā; 5: Gaida apmaksu, 6: Unfinished reservation;';

INSERT INTO ad_sitedata (name,tab,block,title,`type`,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailBody_6','Reservation','Email body(Status: Unfinished Reservation)','','textarea',1,0,0,NULL,NULL,'');

INSERT INTO ad_sitedata (name,tab,block,title,`type`,mlang,mcountry,required,validation,callback,sort) VALUES
    ('resMailSubject_6','Reservation','Subject(status: Unfinished Reservation)','','text',1,0,0,NULL,NULL,'');

=====================

ALTER TABLE `mod_doctors` ADD COLUMN `serves_ages` TinyInt(1) DEFAULT '0' COMMENT '0 - both (default), 1 - adults, 2 - kids';
