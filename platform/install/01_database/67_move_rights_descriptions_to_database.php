<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_rights ADD title VARCHAR(255) NOT NULL DEFAULT \'\'');
	
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET title=\'Create articles\' WHERE type=\'create_article\'');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET title=\'Publish articles\' WHERE type=\'publish_article\'');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET title=\'Edit other users articles\' WHERE type=\'edit_others_articles\'');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET title=\'Delete other users articles\' WHERE type=\'delete_others_articles\'');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET title=\'Edit categories\' WHERE type=\'edit_categories\'');
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET title=\'Edit published status for categories\' WHERE type=\'publish_categories\'');
?>