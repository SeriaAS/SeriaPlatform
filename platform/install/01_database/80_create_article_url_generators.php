<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_article_url_generators` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`articletype` VARCHAR( 255 ) NOT NULL ,
		`baseurl` VARCHAR( 255 ) NOT NULL
	) ENGINE = InnoDB ');

	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_article_url_params` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`urlgenerator_id` INT UNSIGNED NOT NULL ,
		`name` VARCHAR( 255 ) NOT NULL ,
		`value` VARCHAR( 255 ) NOT NULL ,
		`specialvalue` VARCHAR( 255 ) NOT NULL ,
		INDEX ( `urlgenerator_id` )
	) ENGINE = InnoDB ');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_article_url_params` ADD FOREIGN KEY ( `urlgenerator_id` ) REFERENCES `' . SERIA_PREFIX . '_article_url_generators` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>