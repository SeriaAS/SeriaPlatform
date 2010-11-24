<?php
try {
        SERIA_Base::db()->query('CREATE TABLE {property_list} (
  `owner` varchar(90) NOT NULL DEFAULT \'\',
  `name` varchar(48) NOT NULL DEFAULT \'\',
  `className` varchar(40) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`owner`,`name`),
  KEY `className` (`className`,`name`,`value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
} catch (PDOException $e) {}
