# Create table to store localized titles for homepage items

CREATE TABLE `mod_homepage_items_titles` (
     `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
     `item_id` INT(10) UNSIGNED NOT NULL,
     `title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
     `lang` VARCHAR(2) NULL DEFAULT NULL COLLATE 'utf8_general_ci',
     PRIMARY KEY (`id`) USING BTREE,
     INDEX `lang` (`lang`) USING BTREE,
     INDEX `item_id` (`item_id`) USING BTREE
)
    COLLATE='utf8_general_ci'
    ENGINE=InnoDB;

