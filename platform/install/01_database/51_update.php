<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_users` ADD `password_change_required` tinyint(1) NOT NULL DEFAULT 0 AFTER `enabled`;');
?>