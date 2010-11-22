<?php
	SERIA_BASE::db()->exec('CREATE TABLE ' . SERIA_PREFIX . '_current_hits (
		id INT AUTO_INCREMENT, 
		ts INT NOT NULL,
		ip VARCHAR(32) character set ascii collate ascii_bin NOT NULL,
		ua VARCHAR(64) character set ascii collate ascii_bin NOT NULL,
		INDEX USING BTREE (ts),
		PRIMARY KEY(id)
	) ENGINE=MEMORY COMMENT=\'Keeps a list of all hits during the last 30 minutes\'');
?>