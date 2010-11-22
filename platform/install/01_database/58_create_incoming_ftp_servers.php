<?php
	SERIA_Base::db()->query(' CREATE TABLE `' . SERIA_PREFIX . '_incoming_ftp_servers` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`hostname` VARCHAR( 255 ) NOT NULL ,
		`port` SMALLINT UNSIGNED NOT NULL ,
		`username` VARCHAR( 255 ) NOT NULL ,
		`password` VARCHAR( 255 ) NOT NULL ,
		`root` VARCHAR( 255 ) NOT NULL
	) ENGINE = InnoDB ');
?>