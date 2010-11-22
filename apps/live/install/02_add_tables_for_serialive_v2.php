<?php

	SERIA_Base::db()->exec("CREATE TABLE IF NOT EXISTS `serialive_keys` (
  `id` int(11) NOT NULL default '0',
  `secretkey` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

	SERIA_Base::db()->exec("CREATE TABLE IF NOT EXISTS `serialive_eventkeys` (
  `id` int(11) NOT NULL default '0',
  `eventkey` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

	SERIA_Base::db()->exec("CREATE TABLE IF NOT EXISTS `serialive_encoder_delay` (
  `id` int(11) NOT NULL default '0',
  `delay` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");
