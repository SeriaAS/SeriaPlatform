<?php
	try {
		SERIA_Base::db()->exec('ALTER TABLE {sites} ADD column dbName VARCHAR(100)');
	} catch (PDOException $e) {
		if ($e->getCode() != '42S21') /* If not duplicate column */
			throw $e; /* throw further */
	}
