ALTER TABLE `mod_clinics` ADD COLUMN `async_exchange_enabled` TINYINT(1) unsigned NOT NULL DEFAULT '1' AFTER `enabled`;
UPDATE mod_clinics SET async_exchange_enabled = 1;
