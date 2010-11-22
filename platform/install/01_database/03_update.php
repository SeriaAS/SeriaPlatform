<?php
	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_rights ADD
		`enabled` TINYINT(1) DEFAULT 1");
