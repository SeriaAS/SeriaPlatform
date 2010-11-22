<?php
	/**
	*	Table to store system wide parameters.
	*/
	SERIA_Base::db()->exec("CREATE TABLE IF NOT EXISTS ".SERIA_PREFIX."_params (
		name VARCHAR(50),
		value TEXT,
		PRIMARY KEY(name)
	) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");
	
	SERIA_Base::db()->exec('CREATE TABLE IF NOT EXISTS ' . SERIA_PREFIX . '_dbversion (
		id MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
		`key` VARCHAR(255) NOT NULL,
		path TEXT NOT NULL,
		version MEDIUMINT UNSIGNED NOT NULL
	)');
