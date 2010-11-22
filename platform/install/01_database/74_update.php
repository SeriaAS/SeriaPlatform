<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_authplugins` ADD `autoadd` TINYINT NOT NULL DEFAULT 0 COMMENT \'Auto add users from this module to local database if not existent\';');
?>