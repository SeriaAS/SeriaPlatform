<?php
	SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN timezone VARCHAR(100) DEFAULT "Europe/Oslo"');
	SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN currency VARCHAR(100) DEFAULT "EUR"');
	SERIA_Base::db()->exec('ALTER TABLE {sites} ADD COLUMN errorMail VARCHAR(100) DEFAULT "errors@example.com"');
