<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_ftp_server_limits` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`ftp_server_id` INT NOT NULL ,
		`maxfilesize` INT UNSIGNED NOT NULL COMMENT \'In kB\',
		`maxstorageusage` INT UNSIGNED NOT NULL COMMENT \'In MB\',
		`maxfilecount` INT UNSIGNED NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_server_limits` ADD UNIQUE (`ftp_server_id`)');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_server_limits` ADD FOREIGN KEY ( `ftp_server_id` ) REFERENCES `' . SERIA_PREFIX . '_ftp_servers` (
		`id`
	) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>