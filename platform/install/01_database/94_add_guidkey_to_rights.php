<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_rights` ADD `guidkey` VARCHAR( 255 ) NOT NULL , ADD INDEX ( guidkey ) ');
?>