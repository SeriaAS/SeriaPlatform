<?php

	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_widgets` DROP KEY `guid`');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_widgets` ADD INDEX ( `guid` )');

?>