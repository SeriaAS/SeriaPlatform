<?php

SERIA_Base::db()->exec('DROP TABLE {cache}');
SERIA_Base::db()->exec('CREATE TABLE {cache} (
  `name` varchar(32) NOT NULL,
  `value` mediumblob default NULL,
  `expiry` int(11) default NULL,
  PRIMARY KEY  (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT="You can truncate this at any time. Simple caching without memcached."');
