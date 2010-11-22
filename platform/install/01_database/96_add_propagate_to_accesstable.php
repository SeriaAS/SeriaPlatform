<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` ADD `propagate` TINYINT NOT NULL DEFAULT 0');
?>