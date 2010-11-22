<?php
	/**
	*	Table to store statistics for views
	*/

	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_statistics (
		id INTEGER NOT NULL auto_increment,
		articleId INTEGER NOT NULL,
		ip VARCHAR(50),
		browser VARCHAR(200),
		event_date DATETIME,
		event_weekday INTEGER,
		event_hour INTEGER,
		PRIMARY KEY(id)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci");


	/**
	*	Table to store all timing statistics
	*/
	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_timing_statistics (
	  `id` int(11) NOT NULL auto_increment,
	  `articleId` int(11) NOT NULL,
	  `ip` varchar(50) default NULL,
	  `browser` varchar(200) default NULL,
	  `event_date` datetime default NULL,
	  `event_weekday` int(11) default NULL,
	  `event_hour` int(11) default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci");

	/**
	*	Table that stores all ids that have ever been used with the CMS. Ensures that 
	*	every item has a unique ID, and also makes the CMS work with different database
	*	engines because we do not need auto increment. Next ID is calculated by fetching 
	*	MAX(guid).
	*
	*	This could potentially have been stored by using the "params" table, but we would
	*	not be guaranteed that the id was unique unless we have some fail safe locking mechanism
	*	that works on all databases.
	*/
	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_guids (
		guid INTEGER, 
		PRIMARY KEY(guid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci");

	/**
	*	Users that can access the CMS, for creating and updating articles. Only administrators
	*	can set is_published.
	*/
	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_users (
		id INTEGER,
		first_name VARCHAR(50),
		last_name VARCHAR(50),
		display_name VARCHAR(100),
		username VARCHAR(50),
		password VARCHAR(50),
		email VARCHAR(100),
		is_administrator INTEGER(1) DEFAULT 0,
		enabled INTEGER(1) DEFAULT 1,
		PRIMARY KEY (id)
	) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");
	try
	{
		SERIA_Base::db()->exec("CREATE UNIQUE INDEX ".SERIA_PREFIX."_users_username_idx ON ".SERIA_PREFIX."_users (username)");
	} catch (PDOException $e) {}
	try
	{
		SERIA_Base::db()->exec("CREATE UNIQUE INDEX ".SERIA_PREFIX."_users_email_idx ON ".SERIA_PREFIX."_users (email)");
	} catch (PDOException $e) {}
	try
	{
		SERIA_Base::db()->exec('INSERT INTO ' . SERIA_PREFIX . '_guids (guid) VALUES(1)');
		SERIA_Base::db()->exec("INSERT INTO ".SERIA_PREFIX."_users (id, first_name, last_name, display_name, username, password, email, is_administrator, enabled) VALUES (
			1,
			'Super',
			'User',
			'Administrator',
			'admin',
			'admin123',
			'admin@example.com',
			'1',
			'1'
		)");
	} catch (PDOException $e) {}
	
	try {
		SERIA_Base::db()->exec("CREATE TABLE `".SERIA_PREFIX."_rights` (
			`id` int(11) NOT NULL default '0',
			`type` varchar(30) NOT NULL,
			PRIMARY KEY  (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	} catch (PDOException $e) {}

	SERIA_Base::db()->exec("INSERT IGNORE INTO `".SERIA_PREFIX."_rights` (`id`, `type`) VALUES (1, 'create_article');");
	SERIA_Base::db()->exec("INSERT IGNORE INTO `".SERIA_PREFIX."_rights` (`id`, `type`) VALUES (2, 'publish_article');");
	SERIA_Base::db()->exec("INSERT IGNORE INTO `".SERIA_PREFIX."_rights` (`id`, `type`) VALUES (3, 'edit_others_articles');");
	SERIA_Base::db()->exec("INSERT IGNORE INTO `".SERIA_PREFIX."_rights` (`id`, `type`) VALUES (4, 'delete_others_articles');");
	SERIA_Base::db()->exec("INSERT IGNORE INTO `".SERIA_PREFIX."_rights` (`id`, `type`) VALUES (6, 'edit_categories');");
	SERIA_Base::db()->exec("INSERT IGNORE INTO `".SERIA_PREFIX."_rights` (`id`, `type`) VALUES (7, 'publish_categories');");

	/**
	*	All editable content in this CMS should be stored in this table.
	*/
	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_articles (
		  `id` int(11) NOT NULL default '0',
		  `type` varchar(30) NOT NULL,
		  `content_id` int(11) NOT NULL,
		  `content_language` varchar(10) NOT NULL,
		  `author_name` varchar(100) default NULL,
		  `author_email` varchar(100) default NULL,
		  `author_id` int(11) default NULL,
		  `start_date` datetime default NULL,
		  `end_date` datetime default NULL,
		  `created_date` datetime NOT NULL,
		  `altered_date` datetime NOT NULL,
		  `published_date` datetime default NULL,
		  `rating` float NOT NULL default '0',
		  `score` float NOT NULL default '0',
		  `votes` int(11) NOT NULL default '0',
		  `price` float NOT NULL default '0',
		  `views` int(11) NOT NULL default '0',
		  `is_published` int(1) NOT NULL default '0',
		  `title` varchar(200) NOT NULL,
		  `extra` longblob COMMENT 'Contains serialized version of extra data added by the article class',
		  `ft` mediumtext,
		  `ft_changed_ts` datetime default NULL COMMENT 'If changed without updating ".SERIA_PREFIX."_articles_fts this contains the time of the first change',
		  `tags` text NOT NULL COMMENT 'Tags for this article, one per line',
		  `notes` text,
		  PRIMARY KEY  (`id`),
		  KEY `".SERIA_PREFIX."_articles_content_id_idx` (`content_id`),
		  KEY `author_id` (`author_id`),
		  KEY `changed_ts` (`ft_changed_ts`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

	
	try {
		SERIA_Base::db()->exec("CREATE INDEX ".SERIA_PREFIX."_articles_content_id_idx ON ".SERIA_PREFIX."_articles (content_id)");
	} catch (PDOException $e) {}


	/**
	*	Articles can be added to multiple categories.
	*/
	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_categories (
		id INTEGER,
		article_type VARCHAR(30) NOT NULL,
		name VARCHAR(100) NOT NULL,
		parent_id INTEGER DEFAULT 0 NOT NULL,
		PRIMARY KEY (id)
	) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");
	try {
		SERIA_Base::db()->exec("CREATE INDEX ".SERIA_PREFIX."_categories_parent_id_idx ON ".SERIA_PREFIX."_categories (parent_id)");
	} catch (PDOException $e) {}

	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_articles_categories (
		article_id INTEGER,
		category_id INTEGER,
		PRIMARY KEY (article_id, category_id)
	) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");
	
	SERIA_Base::db()->exec("CREATE TABLE ".SERIA_PREFIX."_user_rights (
		user_id INTEGER NOT NULL,
		right_id INTEGER NOT NULL,
		PRIMARY KEY (user_id,right_id)
	) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");

    
	SERIA_Base::db()->exec("CREATE TABLE `".SERIA_PREFIX."_articles_fts` (
		  `id` int(11) NOT NULL default '0',
		  `ft` mediumtext,
		  PRIMARY KEY  (`id`),
		  FULLTEXT KEY `ft` (`ft`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

	SERIA_Base::db()->exec("CREATE TABLE `".SERIA_PREFIX."_article_categories` (
		  `id` int(11) NOT NULL default '0',
		  `parent_id` int(11) default NULL,
		  `pos` int(11) NOT NULL,
		  `is_published` tinyint(1) NOT NULL default '0',
		  `name` varchar(100) NOT NULL,
		  `short_description` mediumtext NOT NULL,
		  `long_description` mediumtext NOT NULL,
		  `notes` mediumtext NOT NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `parent_id` (`parent_id`,`pos`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

	SERIA_Base::db()->exec("CREATE TABLE `".SERIA_PREFIX."_files` (
		  `id` int(11) NOT NULL,
		  `url` varchar(255) NOT NULL,
		  `filename` varchar(255) NOT NULL,
		  `local_path` varchar(255) NOT NULL,
		  `referrers` int(11) NOT NULL default '0' COMMENT 'Count the number of articles referring this file',
		  `ftp_server_id` int(11) NOT NULL,
		  `ftp_path` varchar(255) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `ftp_server_id` (`ftp_server_id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

	try
	{
		SERIA_Base::db()->exec("ALTER TABLE `".SERIA_PREFIX."_user_rights` ADD FOREIGN KEY ( `user_id` ) REFERENCES `".SERIA_PREFIX."_users` (
			`id` 
		) ON DELETE CASCADE ON UPDATE CASCADE");

		SERIA_Base::db()->exec("ALTER TABLE `".SERIA_PREFIX."_user_rights` ADD FOREIGN KEY ( `right_id` ) REFERENCES `".SERIA_PREFIX."_rights` (
			`id` 
		) ON DELETE CASCADE ON UPDATE CASCADE");

		SERIA_Base::db()->exec("ALTER TABLE `".SERIA_PREFIX."_articles_categories` ADD FOREIGN KEY ( `article_id` ) REFERENCES `".SERIA_PREFIX."_articles` (
			`id` 
		) ON DELETE CASCADE ON UPDATE CASCADE;");
		SERIA_Base::db()->exec("ALTER TABLE `".SERIA_PREFIX."_articles_categories` ADD FOREIGN KEY ( `category_id` ) REFERENCES `".SERIA_PREFIX."_article_categories` (
			`id` 
		) ON DELETE CASCADE ON UPDATE CASCADE;");
	}
	catch (PDOException $e)
	{
	}

	SERIA_Base::db()->exec("CREATE TABLE `".SERIA_PREFIX."_ftp_servers` (
		  `id` int(11) NOT NULL auto_increment,
		  `host` varchar(255) NOT NULL,
		  `port` int(11) NOT NULL,
		  `type` enum('normal','ssl') NOT NULL default 'normal',
		  `pasv` tinyint(1) NOT NULL default '1',
		  `username` varchar(255) NOT NULL,
		  `password` varchar(255) NOT NULL,
		  `active` tinyint(1) NOT NULL default '1',
		  `http_root_url` varchar(200) NOT NULL,
		  `https_root_url` varchar(200) NOT NULL,
		  `file_root` varchar(200) NOT NULL,
		  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8");

	try
	{
		SERIA_Base::db()->exec("ALTER TABLE `".SERIA_PREFIX."_files` ADD FOREIGN KEY ( `ftp_server_id` ) REFERENCES `".SERIA_PREFIX."_ftp_servers` (
			`id` 
		) ON DELETE RESTRICT ON UPDATE CASCADE");
	}
	catch(PDOException $e)
	{
	}

	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_files ADD
		`content_type` varchar(255) NOT NULL DEFAULT 'application/octet-stream'");
	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_files ADD
		`filesize` int(13) NOT NULL DEFAULT 1");
