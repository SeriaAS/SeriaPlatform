<?php
	$db = SERIA_Base::db();

try {
	$userId = $db->query("SELECT id FROM {users} WHERE is_administrator=1 ORDER BY id LIMIT 1")->fetch(PDO::FETCH_COLUMN, 0);
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (createdBy INTEGER NOT NULL)');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (createdDate DATETIME NOT NULL)');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (modifiedBy INTEGER NOT NULL)');
} catch (PDOException $e) {}
try {
	$db->exec('ALTER TABLE {cdn_servers} ADD COLUMN (modifiedDate DATETIME NOT NULL)');
} catch (PDOException $e) {}
try {
	$db->exec('UPDATE {cdn_servers} SET createdDate=NOW(), modifiedDate=NOW(), createdBy=:userId1, createdDate=:userId2', array('userId1' => $userId, 'userId2' => $userId));
} catch (PDOException $e) {}
