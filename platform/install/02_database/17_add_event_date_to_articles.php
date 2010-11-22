<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_articles ADD COLUMN event_date datetime');
?>
