<?php
	SERIA_Base::db()->query('DROP TABLE IF EXISTS `' . SERIA_PREFIX.'_menu_items`');
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX.'_menu_items` (
  	`id` int(11) unsigned NOT NULL auto_increment,
  	`link` tinytext NOT NULL,
  	`parent_id` int(11) unsigned NOT NULL default 0,
 	`pos` int(11) NOT NULL default 0,
 	`is_published` tinyint(1) NOT NULL default 0,
 	`name` varchar(100) NOT NULL,
  	`notes` mediumtext NOT NULL,
  	PRIMARY KEY  (`id`),
	UNIQUE KEY `parent_id` (`parent_id`,`name`)
	) ENGINE=InnoDB');