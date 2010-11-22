<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_articles` ADD FOREIGN KEY ( `id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`guid`
	) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_categories` ADD FOREIGN KEY ( `id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`guid`
	) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_event_listeners` ADD FOREIGN KEY ( `id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`guid`
	) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD FOREIGN KEY ( `id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`guid`
	) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_users` ADD FOREIGN KEY ( `id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (
		`guid`
	) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
?>