# PROMO_CODES -- defines promo codes and discounts

CREATE TABLE `promo_codes` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(20) NOT NULL COMMENT 'Promo code user have to enter to get discount',
    `title` varchar(255) NOT NULL COMMENT 'Promo action title',
    `start_datetime` datetime NOT NULL COMMENT 'Promo action valid from datetime',
    `end_datetime` datetime NOT NULL COMMENT 'Promo action valid to datetime',
    `status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Is promo action active or not -- 0 or 1',
    `network_id` int(11) DEFAULT NULL COMMENT 'Reference to ins_networks table which allow to reffer to the set of clinics at once (used in insurance and dc subscriptions as well). Can be null',
    `clinic_id` int(11) DEFAULT NULL COMMENT 'Reference to particular clinic, can be null',
    `services` json NOT NULL COMMENT 'Array of service ids supproted by this promo action in json: [id1,id2,...] or ["*"] -- for all services',
    `discount` decimal(10,2) unsigned NOT NULL DEFAULT 0 COMMENT 'Discount percent applied to standard service prices for this promo action. Can not be more than 100% default is 0. Could be only positive.',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# PROMO_USAGE -- contains info about promo codes usage (report)

CREATE TABLE `promo_usage` (
   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
   `reservation_id` int(11) unsigned NOT NULL COMMENT 'Resevation id cannot be null',
   `profile_id` int(11) unsigned NOT NULL COMMENT 'User profile id cannot be null',
   `promocode_id` int(11) unsigned NOT NULL COMMENT 'Reference to promo_codes table id',
   `status` tinyint(1) unsigned NOT NULL DEFAULT 0 COMMENT 'Whether promocode was entered or paid -- 0 (default - entered) or 1 (paid -- on success only)',
   `service_id` int(11) unsigned NOT NULL COMMENT 'Piearsta internal service id -- just id field from mod_classificators table',
   `discounted_price` decimal(10,2) NOT NULL,
   `regular_price` decimal(10,2) NOT NULL COMMENT 'Standard price for this service',
   `created_at` timestamp NULL DEFAULT NULL,
   `updated_at` timestamp NULL DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
