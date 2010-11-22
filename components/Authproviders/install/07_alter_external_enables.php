<?php
$dataDefs = array(
	'guest_enabled TINYINT(1)',
	'auto_enabled TINYINT(1)'
);

try {
	SERIA_Base::db()->exec('ALTER TABLE {external_authproviders} ADD COLUMN ('.implode(', ', $dataDefs).')');
} catch (PDOException $e) {}
