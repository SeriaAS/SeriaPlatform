<?php
$dataDefs = array(
	'guestAccount TINYINT(1) DEFAULT 0 NOT NULL'
);

SERIA_Base::db()->exec('ALTER TABLE {users} ADD COLUMN ('.implode(', ', $dataDefs).')');
