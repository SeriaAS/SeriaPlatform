<?php
	$db = SERIA_Base::db();

	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (name VARCHAR(30))');
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (ownerId INTEGER)');
	$db->exec('ALTER TABLE {cdn_servers} ADD INDEX ownerId_idx (ownerId, name)');
	$db->exec('ALTER TABLE {cdn_hostnames} ADD COLUMN (ownerId INTEGER)');
	$db->exec('ALTER TABLE {cdn_hostnames} ADD INDEX ownerId_idx (ownerId)');
