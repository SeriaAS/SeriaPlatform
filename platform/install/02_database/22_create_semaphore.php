<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_semaphores` (
		`id` VARCHAR(20) NOT NULL,
		`createdTime` DATETIME,
		PRIMARY KEY  (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;');
