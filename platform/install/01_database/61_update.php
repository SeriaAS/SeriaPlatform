<?php
	SERIA_Base::db()->query('ALTER TABLE `'.SERIA_PREFIX.'_files` ADD INDEX ( `updated_at` , `referrers` )  ');
?>
