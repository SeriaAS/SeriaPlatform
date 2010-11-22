<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_event_listeners`
		CHANGE `source` `source` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
		CHANGE `target` `target` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ');
?>