<?php
  // this should NOT rename the seria_property_list table that was previously defined
  // in update 18. That would be catastrophic for multiple installations in a single database.

  SERIA_Base::db()->exec('CREATE TABLE IF NOT EXISTS {property_list} (
  `owner` varchar(90) NOT NULL DEFAULT \'\',
  `name` varchar(48) NOT NULL DEFAULT \'\',
  `className` varchar(40) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`owner`,`name`),
  KEY `className` (`className`,`name`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
