<?php
/**
 * Memory table used for caching state information
 */
	SERIA_BASE::db()->exec('CREATE TABLE ' . SERIA_PREFIX . '_cache (
		name VARCHAR(32) PRIMARY KEY, 
		value VARCHAR(255),
		expiry INT,
		INDEX USING btree (expiry)
	) ENGINE=MEMORY COMMENT=\'Keeps a list of all hits during the last 30 minutes\'');
?>