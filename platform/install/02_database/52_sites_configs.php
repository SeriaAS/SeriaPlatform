<?php
	try {
		SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN timezone VARCHAR(100) DEFAULT "Europe/Oslo"');
	} catch (PDOException $e) {
		if ($e->getCode() != '42S21') /* If not duplicate column */
			throw $e; /* throw further */
	}
	try {
		SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN currency VARCHAR(100) DEFAULT "EUR"');
	} catch (PDOException $e) {
		if ($e->getCode() != '42S21') /* If not duplicate column */
			throw $e; /* throw further */
	}
	try {
		SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN errorMail VARCHAR(100) DEFAULT "errors@example.com"');
	} catch (PDOException $e) {
		if ($e->getCode() != '42S21') /* If not duplicate column */
			throw $e; /* throw further */
	}
