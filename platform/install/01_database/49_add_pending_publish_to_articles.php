<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_articles ADD pending_publish TINYINT(1) NOT NULL DEFAULT 0 AFTER is_published');
?>