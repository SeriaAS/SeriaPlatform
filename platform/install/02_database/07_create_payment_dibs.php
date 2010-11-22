<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_payment_dibs` (
		`id` int(10) unsigned NOT NULL auto_increment,
		`currency` varchar(100) NOT NULL,
		`language` varchar(100) NOT NULL,
		`shippingcost` BIGINT NOT NULL,
		`amount` BIGINT NOT NULL,
		ordernumber INT UNSIGNED NOT NULL,
		successurl VARCHAR(255) NOT NULL DEFAULT \'\',
		failureurl VARCHAR(255) NOT NULL DEFAULT \'\',
		PRIMARY KEY  (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8;');
?>