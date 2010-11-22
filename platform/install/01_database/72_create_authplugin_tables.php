<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_authplugins` (
		`id` SMALLINT UNSIGNED NOT NULL DEFAULT \'0\',
		`name` VARCHAR( 255 ) NOT NULL ,
		`enabled` TINYINT NOT NULL DEFAULT \'0\',
		PRIMARY KEY ( `id` )
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugins` ADD INDEX `id` ( `enabled` , `id` ) ');
	
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_authplugin_settings` (
		`id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`authplugin_id` SMALLINT UNSIGNED NOT NULL ,
		`key` VARCHAR( 255 ) NOT NULL ,
		`value` TEXT NOT NULL ,
		INDEX ( `authplugin_id` )
	) ENGINE = InnoDB ');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugin_settings` ADD FOREIGN KEY ( `authplugin_id` ) REFERENCES `' . SERIA_PREFIX . '_authplugins` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
	
	SERIA_Base::db()->query('INSERT INTO `' . SERIA_PREFIX . '_authplugins`
		(`id`, `name`, `enabled`)
		VALUES (\'1\', \'localDatabase\', \'1\');');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_users` ADD `authplugin_id` SMALLINT UNSIGNED NOT NULL DEFAULT 1;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_users` ADD INDEX ( `authplugin_id` ) ;');
	
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_users SET authplugin_id=1');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_users` ADD FOREIGN KEY ( `authplugin_id` ) REFERENCES `' . SERIA_PREFIX . '_authplugins` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>