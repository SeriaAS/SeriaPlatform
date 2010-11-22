<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_comet_channels` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`key` VARCHAR( 50 ) NOT NULL ,
		`name` VARCHAR( 255 ) NOT NULL
	) ENGINE = InnoDB CHARACTER SET utf8');
	
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_comet_subscribers` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		channel_id INT UNSIGNED NOT NULL,
		`lastupdate` INT UNSIGNED NOT NULL ,
		`key` VARCHAR( 50 ) NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_comet_messages` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`time` INT UNSIGNED NOT NULL ,
		`channel_id` INT UNSIGNED NOT NULL ,
		`key` VARCHAR( 50 ) NOT NULL ,
		`message` BLOB NOT NULL
	) ENGINE = InnoDB CHARACTER SET utf8');
?>