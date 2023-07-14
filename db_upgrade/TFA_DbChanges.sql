###
### DB changes for Two-facktor Authentication
###


# create table to store tfa keys (obtained in TFA-configuration process)

CREATE TABLE `mod_tfa` (
   `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
   `profile_id` INT(11) UNSIGNED NOT NULL,
   `tfa_key` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`) USING BTREE,
   UNIQUE KEY(`profile_id`) USING BTREE
)
COMMENT='2-facktor authentication data'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;

#======

