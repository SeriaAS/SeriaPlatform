<?php
	SERIA_Base::db()->query('
	CREATE TABLE `' . SERIA_PREFIX . '_ftp_server_filetypes` (
		`id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`ftp_server_id` INT NOT NULL ,
		`pattern` VARCHAR( 255 ) NOT NULL ,
		INDEX ( `ftp_server_id` )
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_server_filetypes` ADD FOREIGN KEY ( `ftp_server_id` ) REFERENCES `' . SERIA_PREFIX . '_ftp_servers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_server_filetypes` CHANGE `pattern` `pattern` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL');
?>