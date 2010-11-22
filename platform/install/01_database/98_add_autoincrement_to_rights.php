<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_rights` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT ');
?>