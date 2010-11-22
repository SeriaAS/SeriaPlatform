<?php
	SERIA_Base::db()->query('CREATE TABLE ' . SERIA_PREFIX . '_ftp_files (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`ftp_server_id` INT NOT NULL,
		`file_id` INT NOT NULL,
		`status` ENUM( \'ok\', \'failed\' ) NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_files` ADD INDEX ( `ftp_server_id` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_files` ADD INDEX ( `file_id` )');
?>