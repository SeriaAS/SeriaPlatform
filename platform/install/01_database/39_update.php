<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD `parent_file` INT NOT NULL DEFAULT 0 AFTER `id` ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD INDEX ( `parent_file` )');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD `relation` VARCHAR( 50 ) NOT NULL DEFAULT \'\' AFTER `id` ;');
?>