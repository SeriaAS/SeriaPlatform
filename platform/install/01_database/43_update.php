<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD `meta_update` DATETIME NOT NULL DEFAULT \'0000-00-00\' AFTER `updated_at`');
?>