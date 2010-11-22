<?php
	SERIA_Base::db()->query(' CREATE TABLE ' . SERIA_PREFIX . '_searchindex_words (
		`id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
		`length` TINYINT UNSIGNED NOT NULL ,
		`word` VARCHAR( 255 ) NOT NULL
	) ENGINE = InnoDB ');
?>