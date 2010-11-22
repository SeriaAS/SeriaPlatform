<?php
	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_files ADD
		`created_date` datetime DEFAULT NULL");
?>
