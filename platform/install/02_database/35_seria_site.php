<?php
try {
	SERIA_Base::db()->exec("CREATE TABLE {sites} (
	  `id` int(11) NOT NULL,
	  `domain` varchar(100) default NULL,
	  `title` varchar(100) default NULL,
	  `created_date` datetime default NULL,
	  `created_by` int(11) default NULL,
	  `is_published` tinyint(1) default NULL,
	  `notes` text,
	  PRIMARY KEY  (`id`),
	  UNIQUE KEY `domain` (`domain`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
} catch (PDOException $e) {}
