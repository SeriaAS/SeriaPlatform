<?php
	$db = SERIA_Base::db();
	$db->exec("CREATE TABLE IF NOT EXISTS {cdn_servers} (id integer primary key, ip varchar(40), UNIQUE(ip)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$db->exec("CREATE TABLE IF NOT EXISTS {cdn_hostnames} (id integer primary key, serverId integer, hostname varchar(100), INDEX(hostname)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	$db->exec("CREATE TABLE IF NOT EXISTS {cdn_bandwidthlog} (id INTEGER PRIMARY KEY, hostnameId INTEGER, logdate DATE, hit_count INTEGER, fetch_count INTEGER, in_bandwidth INTEGER, out_bandwidth INTEGER, INDEX(hostnameId, logdate)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
