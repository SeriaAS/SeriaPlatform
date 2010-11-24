<?php
	$db = SERIA_Base::db();

try {
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (name VARCHAR(30))');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (ownerId INTEGER)');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_servers} ADD INDEX ownerId_idx (ownerId, name)');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_hostnames} ADD COLUMN (ownerId INTEGER)');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_hostnames} ADD INDEX ownerId_idx (ownerId)');
} catch (PDOException $e) {}
