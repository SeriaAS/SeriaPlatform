<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_sitemenu_url` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`sitemenu_id` INT UNSIGNED NOT NULL ,
		`url` VARCHAR( 255 ) NOT NULL ,
		INDEX ( `sitemenu_id` )
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_url` ADD FOREIGN KEY ( `sitemenu_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (
			`id`
		) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>