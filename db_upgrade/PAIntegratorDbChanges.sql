###

ALTER TABLE `mod_clinics` ADD COLUMN `enabled` TINYINT(1) unsigned NOT NULL DEFAULT '1' AFTER `payments_enabled`;

### FOLLOWING RUN ONLY ONCE BEFORE PAI FIRST START
UPDATE mod_clinics SET enabled = 1;
