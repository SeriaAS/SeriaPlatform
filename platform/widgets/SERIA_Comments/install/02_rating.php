<?php

SERIA_Base::db()->exec(
	'ALTER TABLE `'.SERIA_PREFIX.'_widgets_comments` ADD COLUMN rating INT DEFAULT 0'
);

?>