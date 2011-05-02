<?php
SERIA_Base::db()->exec('CREATE TABLE IF NOT EXISTS {sites_aliases} (
	`id` INTEGER NOT NULL,
	`siteId` INTEGER NOT NULL,
	`domain` VARCHAR(100),
	`domainType` ENUM(\'alias\',\'forwarder\'),
	PRIMARY KEY(id),
	INDEX(siteId),
	UNIQUE(domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
