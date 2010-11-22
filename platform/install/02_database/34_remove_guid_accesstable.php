<?php
	
	SERIA_Base::db()->exec("DROP TABLE IF EXISTS {guid_accesstable}");
	SERIA_Base::db()->exec("DROP TABLE IF EXISTS {user_group}");
	SERIA_Base::db()->exec("DROP TABLE IF EXISTS {user_rights}");
	SERIA_Base::db()->exec("DROP TABLE IF EXISTS {menu_items}");
