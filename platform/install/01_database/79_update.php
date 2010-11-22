<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_memcaches` CHANGE `disableduntil` `disableduntil` INT UNSIGNED NOT NULL DEFAULT 0 ');
?>