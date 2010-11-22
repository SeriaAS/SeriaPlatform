<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_systemstatusmessages` ADD `category` ENUM( \'system\', \'security\', \'performance\', \'content\' ) NOT NULL DEFAULT \'system\';');
?>