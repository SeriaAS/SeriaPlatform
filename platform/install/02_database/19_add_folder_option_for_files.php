<?php
	try {SERIA_Base::db()->exec('ALTER TABLE '.SERIA_PREFIX.'_files ADD (isFolder tinyint(1) NOT NULL DEFAULT 0)');} catch (PDOException $e) {}
