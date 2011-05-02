<?php

try {
	SERIA_Base::db()->exec('ALTER TABLE {user_meta_value} MODIFY value TEXT');
} catch (PDOException $e) {}
