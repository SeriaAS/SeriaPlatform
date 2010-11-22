<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_user_group_rights` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`right_id` INT NOT NULL ,
		`user_group_id` INT UNSIGNED NOT NULL
	) ENGINE = InnoDB ');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group_rights` ADD UNIQUE (
		`user_group_id`,
		`right_id`
	)');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group_rights` ADD INDEX ( `right_id` )');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group_rights` ADD FOREIGN KEY ( `right_id` ) REFERENCES `' . SERIA_PREFIX . '_rights` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_user_group_rights` ADD FOREIGN KEY ( `user_group_id` ) REFERENCES `' . SERIA_PREFIX . '_user_groups` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>