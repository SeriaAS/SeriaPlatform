<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_memcaches ADD enabled TINYINT NOT NULL DEFAULT 2');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_memcaches SET enabled=1 WHERE disabled=0');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_memcaches SET enabled=0 WHERE disabled != 0');
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_memcaches DROP COLUMN disabled');
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_memcaches ADD INDEX (enabled)');
?>