<?php

SERIA_Base::db()->exec('CREATE TABLE IF NOT EXISTS `serialive_events` (
  `id` int(11) NOT NULL,
  `article_id` int(11) default NULL,
  `event_type` varchar(30) default NULL,
  `event_value` blob,
  `ts` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1');

SERIA_Base::db()->exec('CREATE TABLE IF NOT EXISTS `serialive_keys` (
  `id` int(11) NOT NULL default \'0\',
  `secretkey` varchar(32) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1');
