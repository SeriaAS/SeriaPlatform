<?php
	SERIA_Base::db()->exec('ALTER TABLE '.SERIA_PREFIX.'_menu_items CHANGE  parent_id  parent_id INT( 11 ) NULL DEFAULT NULL');
