<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_systemstatusmessages` (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
		`level` TINYINT NOT NULL ,
		`time` INT UNSIGNED NOT NULL ,
		`key` VARCHAR( 255 ) NOT NULL ,
		`message` TEXT NOT NULL ,
		`status` TINYINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY ( `id` )
	) ENGINE = InnoDB');
?>