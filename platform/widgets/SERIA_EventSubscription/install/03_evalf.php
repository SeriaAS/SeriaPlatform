<?php

SERIA_Base::db()->exec(
	'ALTER TABLE `'.SERIA_PREFIX.'_widgets_event_subscription` ADD COLUMN (
		rating INT DEFAULT NULL,
		positiveComment TEXT DEFAULT NULL,
		negativeComment TEXT DEFAULT NULL
	)'
);

?>