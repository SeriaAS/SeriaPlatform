<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_rights` ADD UNIQUE (
			`type` ( 30 ) ,
			`guidkey` ( 255 )
		)');
?>