<?php
try {
	SERIA_Base::db()->exec('ALTER TABLE {external_authproviders} CHANGE enabled system_enabled TINYINT(1)');
} catch (PDOException $e) {}
