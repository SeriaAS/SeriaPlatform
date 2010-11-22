<?php
	SERIA_Base::db()->query('CREATE TABLE `' . SERIA_PREFIX . '_widgets` (
		`id` INT UNSIGNED NOT NULL ,
		`type` VARCHAR( 255 ) NOT NULL ,
		`guid` VARCHAR( 255 ) NOT NULL
	) ENGINE = InnoDB');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_widgets` ADD PRIMARY KEY ( `id` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_widgets` ADD INDEX ( `type` , `guid` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_widgets` ADD UNIQUE (`guid`)');
?>