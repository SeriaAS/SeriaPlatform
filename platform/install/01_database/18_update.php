<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_searchindexconfig_column` ADD `sortdirection` ENUM( \'asc\', \'desc\' ) NOT NULL DEFAULT \'asc\';');
?>