<?php
	SERIA_Base::db()->query('CREATE TABLE ' . SERIA_PREFIX . '_sitemenu (
			`id` INT UNSIGNED NOT NULL PRIMARY KEY ,
			`position` INT NOT NULL,
			`name` VARCHAR( 255 ) NOT NULL,
			`title` VARCHAR( 255 ) NOT NULL
		) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_sitemenu_relation` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`parent_id` INT UNSIGNED NOT NULL ,
			`child_id` INT UNSIGNED NOT NULL
		) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_sitemenu_article` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`sitemenu_id` INT UNSIGNED NOT NULL ,
			`article_id` INT NOT NULL ,
			`url` VARCHAR( 255 ) NOT NULL
		) ENGINE = InnoDB ');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu` ADD `relationtype` VARCHAR( 255 ) NOT NULL AFTER `id` ;');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu` ADD INDEX ( `relationtype` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD INDEX ( `sitemenu_id` )  ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD INDEX ( `article_id` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD INDEX ( `parent_id` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD INDEX ( `child_id` )');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD FOREIGN KEY ( `sitemenu_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (
			`id`
		) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD FOREIGN KEY ( `article_id` ) REFERENCES `' . SERIA_PREFIX . '_articles` (
			`id`
		) ON DELETE CASCADE ON UPDATE CASCADE ;');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD FOREIGN KEY ( `parent_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (
			`id`
		) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD FOREIGN KEY ( `child_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (
			`id`
		) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
?>