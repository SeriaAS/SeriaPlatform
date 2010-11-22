<?php
	$query = SERIA_Base::db()->query('SHOW CREATE TABLE ' . SERIA_PREFIX . '_files');
	$rows = $query->fetchAll(PDO::FETCH_NUM);
	list($row) = $rows;
	if ($row) {
		$createTable = $row[1];
		if (preg_match('/CONSTRAINT .(.*?). FOREIGN KEY \(.ftp_server_id.\) REFERENCES .' . SERIA_PREFIX . '_ftp_servers/i', $createTable, $matches)) {
			$key = $matches[1];
			$query = 'ALTER TABLE ' . SERIA_PREFIX . '_files DROP FOREIGN KEY `' . $key . '`';
			SERIA_Base::db()->query($query);
			
			SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_files DROP ftp_server_id, DROP ftp_path, DROP thumbnails, DROP thumbnailNotOnFtp');
		} else {
			throw new Exception('2: Show create table failed');
		}
	} else {
		throw new Exception('1: Show create table failed');
	}
?>