<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_systemstatusmessages`  ENGINE = InnoDB');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_statistics`  ENGINE = InnoDB');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guids`  ENGINE = InnoDB');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_event_listeners`  ENGINE = InnoDB');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_dbversion`  ENGINE = InnoDB');
?>