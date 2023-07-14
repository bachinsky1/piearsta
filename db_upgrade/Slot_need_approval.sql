# Adds need_approval field to mod_shedules table
ALTER TABLE mod_shedules_temp ADD COLUMN need_approval TINYINT(1) DEFAULT NULL;
ALTER TABLE mod_shedules ADD COLUMN need_approval TINYINT(1) DEFAULT NULL;

# Adds need_approval field to mod_reservations table
ALTER TABLE mod_reservations ADD COLUMN need_approval TINYINT(1) DEFAULT NULL;

# Adds no_hsp_message_sent field to mod_reservations table
ALTER TABLE mod_reservations ADD COLUMN no_hsp_message_sent TINYINT(1) NOT NULL DEFAULT 0;
UPDATE mod_reservations SET no_hsp_message_sent = 0;