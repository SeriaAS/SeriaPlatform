<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_file_transcode_queue` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`file_id` INT NOT NULL DEFAULT \'0\',
		`transcoder` VARCHAR( 100 ) NOT NULL DEFAULT \'\',
		`status` TINYINT NOT NULL DEFAULT \'0\'
	) ENGINE = InnoDB');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_file_transcode_queue` ADD INDEX ( `file_id` ) ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_file_transcode_queue` ADD FOREIGN KEY ( `file_id` ) REFERENCES `' . SERIA_PREFIX . '_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_file_transcode_queue` ADD `arguments` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT \'\'');
?>