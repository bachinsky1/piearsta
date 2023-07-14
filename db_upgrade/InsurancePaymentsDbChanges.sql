####

ALTER TABLE `mod_profiles` ADD COLUMN `insurance_start_date` DATETIME NULL DEFAULT NULL AFTER `insurance_number`;
ALTER TABLE `mod_profiles` ADD COLUMN `insurance_end_date` DATETIME NULL DEFAULT NULL AFTER `insurance_start_date`;

####

ALTER TABLE mod_transactions
    ADD COLUMN insurance_company VARCHAR(100) NULL DEFAULT NULL AFTER payment_method;

ALTER TABLE mod_transactions
    ADD COLUMN insurance_policy VARCHAR(100) NULL DEFAULT NULL AFTER insurance_company;

ALTER TABLE mod_transactions
    ADD COLUMN dc_card VARCHAR(100) NULL DEFAULT NULL AFTER insurance_policy;

####

### PIEARSTA-377

# DC SUBSCRIPTIONS -- customers (organisations which have agreement for subscriptions for their employees)
CREATE TABLE `ins_customers` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `title` varchar(255) NOT NULL,
     `notes` varchar(255) DEFAULT '',
     `registred_at` datetime DEFAULT NULL,
     `created_at` timestamp NULL DEFAULT NULL,
     `updated_at` timestamp NULL DEFAULT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# DC SUBSCRIPTIONS -- agreements
CREATE TABLE `ins_customer_agreements` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `customer_id` int(11) unsigned NOT NULL,
   `agreement_number` varchar(255) NOT NULL,
   `start_datetime` datetime NOT NULL,
   `end_datetime` datetime NOT NULL,
   `notes` varchar(255) DEFAULT '',
   `created_at` timestamp NULL DEFAULT NULL,
   `updated_at` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# DC SUBSCRIPTIONS -- subscribtions
CREATE TABLE `ins_subscriptions` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `profile_id` int(11) unsigned NOT NULL,
   `customer_id` int(11) unsigned DEFAULT NULL,
   `agreement_id` int(11) unsigned DEFAULT NULL,
   `product_id` int(11) unsigned NOT NULL,
   `start_datetime` datetime NOT NULL,
   `end_datetime` datetime NOT NULL,
   `pay_thru_date` date NULL DEFAULT NULL,
   `created_at` timestamp NULL DEFAULT NULL,
   `updated_at` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# DC SUBSCRIPTIONS & INSURANCE --clinic networks allow to group clinics
CREATE TABLE `ins_networks` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `notes` varchar(255) DEFAULT NULL,
    `start_datetime` datetime NOT NULL,
    `end_datetime` datetime NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# DC SUBSCRIPTIONS & INSURANCE -- Reference table clinic -> network
CREATE TABLE `ins_clinic_to_networks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `network_id` int(11) unsigned NOT NULL,
  `clinic_id` int(11) unsigned NOT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# DC SUBSCRIPTIONS -- special prices for subscribers by clinics or by clinic groups
CREATE TABLE `ins_network_clinic_special_prices` (
     `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
     `network_id` int(11) unsigned DEFAULT NULL,
     `clinic_id` int(11) DEFAULT NULL,
     `service_id` int(11) unsigned NOT NULL,
     `streetPrice` decimal(10,2) NOT NULL,
     `price` decimal(10,2) NOT NULL,
     `start_datetime` datetime NOT NULL,
     `end_datetime` datetime NOT NULL,
     `created_at` timestamp NULL DEFAULT NULL,
     `updated_at` timestamp NULL DEFAULT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# DC SUBSCRIPTIONS -- products
CREATE TABLE `ins_products` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `network_id` int(11) unsigned DEFAULT NULL,
    `clinic_id` int(11) DEFAULT NULL,
    `start_datetime` datetime NOT NULL,
    `end_datetime` datetime NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# INSURANCE -- the table for insurance special prices, involved networks table to allow set price for clinic group
CREATE TABLE `ins_insurance_special_prices` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `comp_id` int(11) unsigned NOT NULL,
    `comp_title` varchar(255) NOT NULL,
    `network_id` int(11) DEFAULT NULL,
    `clinic_id` int(11) DEFAULT NULL,
    `service_id` int(11) NOT NULL,
    `service_title` varchar(255) NOT NULL,
    `street_price` decimal(10,2) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `start_datetime` datetime NOT NULL,
    `end_datetime` datetime NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

#
CREATE TABLE `ins_min_allowed_copay_percentage` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `network_id` int(11) DEFAULT NULL,
    `clinic_id` int(11) DEFAULT NULL,
    `service_id` int(11) DEFAULT NULL,
    `min_copay_pcnt` decimal(5,2) DEFAULT '0.00',
    `start_datetime` datetime NOT NULL,
    `end_datetime` datetime NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE mod_shedules_lock
    ADD COLUMN additional_data json DEFAULT NULL COMMENT 'special info on rezervation for example insurace police check result' AFTER dc_duration;

ALTER TABLE mod_clinics
    ADD COLUMN additional_data json DEFAULT NULL COMMENT 'special info on clinic for example is clinic works with insurace polices' AFTER enabled;

# Add DC doctor flag column to mod_doctors
ALTER TABLE mod_doctors ADD COLUMN dc_doctor tinyint(1) NOT NULL DEFAULT 0 AFTER serves_ages;

# We decided to collect stats on subscription usage in promo_usage table, so we extend the table with subscription_id field
ALTER TABLE promo_usage ADD COLUMN subscription_id INT(11) UNSIGNED DEFAULT NULL COMMENT 'If user book slot and has active subscription, then we use this table to store subscription usage as well, promocode_id is null in this case' AFTER promocode_id;
# And set default NULL to promocode_id field
ALTER TABLE promo_usage MODIFY COLUMN promocode_id INT(11) UNSIGNED DEFAULT NULL;

# Allow nullable network id in ins_network_clinic_special_prices
ALTER TABLE ins_network_clinic_special_prices MODIFY COLUMN network_id INT(11) UNSIGNED DEFAULT NULL;

