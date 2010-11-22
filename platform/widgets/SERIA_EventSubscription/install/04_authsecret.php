<?php

SERIA_Base::db()->exec(
	'ALTER TABLE `'.SERIA_PREFIX.'_widgets_event_subscription` ADD COLUMN (
		authsecret VARCHAR(20) DEFAULT NULL
	)'
);

?>