<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_ftp_urlrewriters` (
		`id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`ftp_server_id` INT NOT NULL ,
		`name` VARCHAR( 100 ) NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_urlrewriters` ADD INDEX `ftp_server_id` ( `ftp_server_id` )  ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_urlrewriters` ADD FOREIGN KEY ( `ftp_server_id` ) REFERENCES `' . SERIA_PREFIX . '_ftp_servers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>