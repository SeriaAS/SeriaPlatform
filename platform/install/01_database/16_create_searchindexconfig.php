<?php
	SERIA_Base::db()->query(' CREATE TABLE ' . SERIA_PREFIX . '_searchindexconfig (
								`id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`tablename` VARCHAR( 255 ) NOT NULL ,
								`timestampcolumn` VARCHAR( 255 ) NOT NULL
							) ENGINE = InnoDB ');
	SERIA_Base::db()->query('CREATE TABLE ' . SERIA_PREFIX . '_searchindexconfig_column (
								`id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
								`config_id` SMALLINT UNSIGNED NOT NULL ,
								`indexable` TINYINT NOT NULL DEFAULT \'1\',
								`sortindex` TINYINT NOT NULL DEFAULT \'0\' COMMENT \'> 0 = column is used for sorting\',
								`name` VARCHAR( 255 ) NOT NULL
							) ENGINE = InnoDB;');
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_searchindexconfig_column ADD INDEX `config_id` ( `config_id` )  ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_searchindexconfig_column` ADD FOREIGN KEY ( `config_id` ) REFERENCES ' . SERIA_PREFIX . '_searchindexconfig (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>