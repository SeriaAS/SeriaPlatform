<?php
	// This script is modified to revert any previously modifications done by this script, but not finished as there
	// is installations where this script has crashed.

	// Update script has failed on some installations creating multiple unique keys. delete those keys if they exist
	for ($i = 0; $i < 100; $i++) {
		$query = 'ALTER TABLE ' . SERIA_PREFIX . '_user_rights DROP KEY user_id' . (($i == 0)?'':'_'.$i);
		try {
			SERIA_Base::db()->query($query);
		} catch (Exception $null) {}
	}

	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_user_rights ADD UNIQUE (user_id, right_id)');
	try {
		SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX  . '_user_rights DROP PRIMARY KEY');
	} catch (Exception $null) {}
	
	try {
		SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_user_rights DROP COLUMN id');
	} catch (Exception $null) {}
	
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_user_rights ADD id INT UNSIGNED NOT NULL FIRST');
	
	try {
		SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_user_rights DROP PRIMARY KEY');
	} catch (Exception $null) {}
	
	// Create ids for existing records to prevent duplicate values when creating primary key
	$query = 'SELECT * FROM ' . SERIA_PREFIX . '_user_rights';
	$result = SERIA_Base::db()->query($query)->fetchAll(PDO::FETCH_ASSOC);
	$id = 1;
	foreach ($result as $row) {
		$query = 'UPDATE ' . SERIA_PREFIX . '_user_rights SET id=' . $id++ . ' WHERE user_id=' . (int) $row['user_id'] . ' AND right_id=' . (int) $row['right_id'];
		SERIA_Base::db()->query($query);
	}
		
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_user_rights ADD PRIMARY KEY (id)');
	
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_user_rights CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT');
?>