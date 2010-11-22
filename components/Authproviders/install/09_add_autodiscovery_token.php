<?php

$dataDefs = array(
	'autodiscovery_id VARCHAR(128)',
);

try {
	SERIA_Base::db()->exec('ALTER TABLE {authprovider_token_tracking} ADD COLUMN ('.implode(', ', $dataDefs).')');
} catch (PDOException $e) {}
