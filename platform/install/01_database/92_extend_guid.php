<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guids` ADD `key` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL');
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_guid_accesstable` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`access_guid` VARCHAR( 255 ) NOT NULL COMMENT \'The object to assign right/permission for\',
		`object_guid` VARCHAR( 255 ) NOT NULL COMMENT \'The object the permission is granted to\',
		`right_id` INT NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD UNIQUE `object_guid` ( `object_guid` , `access_guid` ) ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD INDEX ( `access_guid` ( 255 ) , `object_guid` ( 255 ) ) ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD INDEX ( `right_id` ) ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guids` ADD INDEX ( `key` ( 255 ) ) ');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD FOREIGN KEY ( `right_id` ) REFERENCES `' . SERIA_PREFIX . '_rights` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
	
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` CHANGE `access_guid` `access_guid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT \'The object to assign right/permission for\'');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` CHANGE `object_guid` `object_guid` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT \'The object the permission is granted to\'');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD FOREIGN KEY ( `access_guid` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`key`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD FOREIGN KEY ( `object_guid` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`key`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>