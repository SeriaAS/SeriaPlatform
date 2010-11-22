<?php

$dataDefs = array(
	'token VARCHAR(128) PRIMARY KEY',
	'status INT(11)',
	'timeout DATETIME',
	'uid INT(11)',
	'code VARCHAR(128)'
);
try {
	SERIA_Base::db()->exec('CREATE TABLE {authprovider_token_tracking} ('.implode(', ', $dataDefs).') ENGINE=InnoDB');
} catch (PDOException $e) {}
