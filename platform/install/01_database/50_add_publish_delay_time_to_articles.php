<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_articles` ADD `publish_delay_time` INT UNSIGNED NOT NULL AFTER `pending_publish`;');
?>