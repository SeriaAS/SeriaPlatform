<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu` ADD `ispublished` TINYINT NOT NULL DEFAULT 0 AFTER `title`');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu` ADD INDEX ( `ispublished` ) ;');
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD `inheritpublishstatus` TINYINT NOT NULL DEFAULT 1;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD INDEX ( `inheritpublishstatus` ) ;');
?>