<?php
	SERIA_BASE::db()->exec('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD `thumbnails` TEXT NOT NULL');
	SERIA_BASE::db()->exec('ALTER TABLE `' . SERIA_PREFIX . '_files` ADD `thumbnailNotOnFtp` TINYINT(1) NOT NULL DEFAULT 0');
?>