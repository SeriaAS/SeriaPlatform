<?php

	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_rights ADD
		`view_others_articles` TINYINT(1) DEFAULT 0");

