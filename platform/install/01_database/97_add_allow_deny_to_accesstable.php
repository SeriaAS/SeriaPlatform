<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD `access` ENUM( \'allow\', \'deny\' ) NOT NULL DEFAULT \'allow\'');
?>