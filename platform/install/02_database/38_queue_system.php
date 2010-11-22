<?php
	SERIA_Base::db()->exec("CREATE TABLE IF NOT EXISTS {queue} (
	  `id` varchar(50) NOT NULL,
	  `title` varchar(100) default NULL,
	  `description` varchar(256) default NULL,
	  PRIMARY KEY  (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	SERIA_Base::db()->exec("CREATE TABLE IF NOT EXISTS {queue_task} (
	  `id` int(11) NOT NULL,
	  `uniq_id` varchar(50) UNIQUE default NULL,
	  `state` int(11) NOT NULL default 0,
	  `reason` varchar(100) default NULL,
	  `description` varchar(256) default NULL,
	  `data` blob NOT NULL,
	  `priority` int(11) NOT NULL default 0,
	  `queue` varchar(50) NOT NULL,
	  `expires` int(11) default NULL,
	  PRIMARY KEY  (`id`),
	  FOREIGN KEY (`queue`) REFERENCES {queue} (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");