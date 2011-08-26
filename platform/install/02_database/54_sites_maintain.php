<?php
	try {
		SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN maintainDate DATETIME');
	} catch (PDOException $e) {
		
	}
