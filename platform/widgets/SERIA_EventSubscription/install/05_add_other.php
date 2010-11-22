<?php
SERIA_Base::db()->exec(
	'ALTER TABLE `'.SERIA_PREFIX.'_widgets_event_subscription` ADD COLUMN (
		otherInfo TEXT DEFAULT NULL
	)'
);
