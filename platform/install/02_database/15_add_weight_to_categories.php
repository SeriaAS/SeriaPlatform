<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_article_categories ADD COLUMN weight INT NOT NULL DEFAULT 0');
?>
