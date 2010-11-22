<?php
$dataDefs = array(
	'accessLevel INT(6) DEFAULT 0 NOT NULL'
);

try {
	SERIA_Base::db()->exec('ALTER TABLE {external_authproviders} ADD COLUMN ('.implode(', ', $dataDefs).')');
} catch (PDOException $e) {}

