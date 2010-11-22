<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_user_groups` (
		`id` INT UNSIGNED NOT NULL ,
		`name` VARCHAR( 255 ) NOT NULL ,
		PRIMARY KEY ( `id` )
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_user_group` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`user_id` INT NOT NULL ,
		`user_group_id` INT UNSIGNED NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group` ADD INDEX `user_id` ( `user_id` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group` ADD INDEX `user_group_id` ( `user_group_id` ) ');

	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group` ADD FOREIGN KEY ( `user_id` ) REFERENCES `' . SERIA_PREFIX . '_users` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group` ADD FOREIGN KEY ( `user_group_id` ) REFERENCES `' . SERIA_PREFIX . '_user_groups` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>