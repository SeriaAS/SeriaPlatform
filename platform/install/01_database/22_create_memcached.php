<?php
	SERIA_Base::db()->query('CREATE TABLE ' . SERIA_PREFIX . '_memcached (
		id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		address VARCHAR( 100 ) NOT NULL ,
		port SMALLINT UNSIGNED NOT NULL DEFAULT 11211,
		weight TINYINT NOT NULL COMMENT \'0-100, lower => higher usage\'
	) ENGINE = InnoDB');
?>