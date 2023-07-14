# add clinic type field to mod_clinics
ALTER TABLE mod_clinics ADD COLUMN clinic_type VARCHAR(50) DEFAULT NULL;

# add address and city fields to mod_doctors
ALTER TABLE mod_doctors
    ADD COLUMN city VARCHAR(50) DEFAULT NULL,
    ADD COLUMN address VARCHAR(255) DEFAULT NULL;

# add slot_ext_id field to mod_shedules table
ALTER TABLE mod_shedules
    ADD COLUMN slot_ext_id VARCHAR(100) DEFAULT NULL;

# add res_uid field to mod_reservations table
ALTER TABLE mod_reservations
    ADD COLUMN res_uid VARCHAR(100) DEFAULT NULL;
