<?php
	/**
	*	Component for working with multisite support in Seria Platform.
	*
	*	@author Frode Børli
	*	@version 1.0
	*	@package seriaplatform
	*/
	class SERIA_MultisiteManifest
	{
		const SERIAL = 6;
		const NAME = 'multisite';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $database = array(
			'creates' => array(
				'CREATE TABLE {sites} (
					`id` int(11) NOT NULL,
					`domain` varchar(100) default NULL,
					`title` varchar(100) default NULL,
					`created_date` datetime default NULL,
					`created_by` int(11) default NULL,
					`is_published` tinyint(1) default NULL,
					`notes` text,
					`dbName` varchar(100),
					`timezone` varchar(100) default \'Europe/Oslo\',
					`currency` varchar(100) default \'EUR\',
					`errorMail` varchar(100) default \'errors@example.com\',
					PRIMARY KEY  (`id`),
					UNIQUE KEY `domain` (`domain`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
				'CREATE TABLE {sites_aliases} (
					`id` INTEGER NOT NULL,
					`siteId` INTEGER NOT NULL,
					`domain` VARCHAR(100),
					`domainType` ENUM(\'alias\',\'forwarder\'),
					PRIMARY KEY(id),
					INDEX(siteId),
					UNIQUE(domain)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',
			),
		);
	}
