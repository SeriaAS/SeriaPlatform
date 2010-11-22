<?php
	// Set all column types that refers to guid table to matching element type
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu` CHANGE `id` `id` INT NOT NULL');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` CHANGE `sitemenu_id` `sitemenu_id` INT NOT NULL');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` CHANGE `parent_id` `parent_id` INT NOT NULL');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` CHANGE `child_id` `child_id` INT NOT NULL');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_url` CHANGE `sitemenu_id` `sitemenu_id` INT NOT NULL');
	
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu` ADD FOREIGN KEY ( `id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (`guid`) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD FOREIGN KEY ( `sitemenu_id` ) REFERENCES `' . SERIA_PREFIX . '_guids` (`guid`) ON DELETE RESTRICT ON UPDATE RESTRICT ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_article` ADD FOREIGN KEY ( `article_id` ) REFERENCES `' . SERIA_PREFIX . '_articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD FOREIGN KEY ( `parent_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD FOREIGN KEY ( `child_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ');
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_url` ADD FOREIGN KEY ( `sitemenu_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>