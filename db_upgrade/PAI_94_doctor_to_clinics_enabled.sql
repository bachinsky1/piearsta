ALTER TABLE mod_doctors_to_clinics ADD COLUMN enabled TINYINT(1) unsigned NOT NULL DEFAULT '1' AFTER c_id;
UPDATE mod_doctors_to_clinics SET enabled = 1;
