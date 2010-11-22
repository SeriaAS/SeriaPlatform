<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_memcaches` ADD `disableduntil` TINYINT NOT NULL DEFAULT 0,
	                                                                      ADD `disabled` TINYINT NOT NULL DEFAULT 0;');
?>