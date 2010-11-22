<?php
	$db = SERIA_Base::db();
	
	$db->query('CREATE TABLE `' . SERIA_PREFIX . '_widgetdata` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`widget_id` INT UNSIGNED NOT NULL ,
		`key` VARCHAR( 255 ) NOT NULL ,
		`value` BLOB NOT NULL ,
		INDEX (widget_id),
		UNIQUE (widget_id, `key`)
	) ENGINE = InnoDB');
	
	$db->query('ALTER TABLE `' . SERIA_PREFIX . '_widgetdata` ADD FOREIGN KEY ( `widget_id` ) REFERENCES `' . SERIA_PREFIX .'_widgets` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>