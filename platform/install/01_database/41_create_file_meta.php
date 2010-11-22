<?php
	SERIA_Base::db()->query('CREATE TABLE `'. SERIA_PREFIX . '_file_meta` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`file_id` INT NOT NULL ,
		`key` VARCHAR( 255 ) NOT NULL ,
		`value` MEDIUMBLOB NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_file_meta` ADD INDEX ( `file_id` , `key` )  ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_file_meta` ADD FOREIGN KEY ( `file_id` ) REFERENCES `' . SERIA_PREFIX . '_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>