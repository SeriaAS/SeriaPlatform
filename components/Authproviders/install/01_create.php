<?php

$dataDefs = array(
	'remote VARCHAR(256) PRIMARY KEY',
	'enabled TINYINT(1)'
);
try {
	SERIA_Base::db()->exec('CREATE TABLE {external_authproviders} ('.implode(', ', $dataDefs).') ENGINE=MyISAM');
} catch (PDOException $e) {}

