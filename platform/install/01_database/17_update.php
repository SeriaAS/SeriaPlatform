<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_searchindexconfig` ADD `keycolumn` VARCHAR( 255 ) NOT NULL ;');
?>