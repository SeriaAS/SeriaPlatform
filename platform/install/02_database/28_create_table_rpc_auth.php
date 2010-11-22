<?php
try {
SERIA_Base::db()->exec('CREATE TABLE {rpc_remote_services} (
  `service` VARCHAR(120) NOT NULL,
  `hostname` VARCHAR(60) DEFAULT NULL,
  `client_id` INT DEFAULT NULL,
  `client_key` VARCHAR(60) DEFAULT NULL,
  PRIMARY KEY  (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
} catch (PDOException $e) {}
try {
SERIA_Base::db()->exec('CREATE TABLE {rpc_clients} (
  `client_id` INT NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `client_key` VARCHAR(60) NOT NULL,
  PRIMARY KEY  (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
} catch (PDOException $e) {}
