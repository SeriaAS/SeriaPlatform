<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_authplugin_autoaddgroup` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`authplugin_id` SMALLINT UNSIGNED NOT NULL ,
		`usergroup_id` INT UNSIGNED NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugin_autoaddgroup` ADD INDEX ( `authplugin_id` )  ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugin_autoaddgroup` ADD UNIQUE `usergroup_id` ( `usergroup_id` , `authplugin_id` ) ');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugin_autoaddgroup` ADD FOREIGN KEY ( `authplugin_id` ) REFERENCES `' . SERIA_PREFIX . '_authplugins` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE');

	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugin_autoaddgroup` ADD FOREIGN KEY ( `usergroup_id` ) REFERENCES `' . SERIA_PREFIX . '_user_groups` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>