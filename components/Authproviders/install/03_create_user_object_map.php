<?php

$dataDefs = array(
	'user_id INT(11) PRIMARY KEY',
	'authprovider_id VARCHAR(128)',
	'FOREIGN KEY (user_id) REFERENCES {users} (id) ON DELETE CASCADE'
);
try {
	SERIA_Base::db()->exec('CREATE TABLE {authproviders_user_mapping} ('.implode(', ', $dataDefs).') ENGINE=InnoDB');
} catch (PDOException $e) {}
