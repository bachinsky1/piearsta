###
### ADD some indexes on mod_reservations table
###

CREATE INDEX `start` ON mod_reservations (`start`);
CREATE INDEX `end` ON mod_reservations (`end`);
CREATE INDEX `clinic_id` ON mod_reservations (`clinic_id`);
CREATE INDEX `cancelled_at` ON mod_reservations (`cancelled_at`);
CREATE INDEX `cancelled_by` ON mod_reservations (`cancelled_by`);


### Remove depricated index institution_id
ALTER TABLE mod_reservations DROP INDEX institution_id;

#======
