<?php

$dataDefs = array(
	'name VARCHAR(100)',
	'owner INT(11)',
	'value VARCHAR(255)',
	'timestamp DATETIME',
	'PRIMARY KEY (name, owner)'
);
try {
	SERIA_Base::db()->exec('CREATE TABLE {user_meta_value} ('.implode(', ', $dataDefs).') ENGINE=InnoDB');
} catch (PDOException $e) {}
