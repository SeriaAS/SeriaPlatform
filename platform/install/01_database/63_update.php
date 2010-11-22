<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_menu_items ADD type VARCHAR(200) NOT NULL DEFAULT \'\'');