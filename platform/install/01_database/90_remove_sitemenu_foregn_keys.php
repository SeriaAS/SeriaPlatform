<?php

	// remove all foreign keys to allow changing column type to INT SIGNED
	$tables = array(
		'_sitemenu',
		'_sitemenu_article',
		'_sitemenu_relation',
		'_sitemenu_url'
	);
	foreach ($tables as $table) {
		do {
			$counter = 0;
			
			$query = SERIA_Base::db()->query('SHOW CREATE TABLE ' . SERIA_PREFIX . $table);
			$rows = $query->fetchAll(PDO::FETCH_NUM);
			list($row) = $rows;
			if ($row) {
				$createTable = $row[1];
				if (preg_match('/CONSTRAINT (.*?) FOREIGN KEY \(.*?\) REFERENCES .' . SERIA_PREFIX . '.*?/i', $createTable, $matches)) {
					$key = $matches[1];
					$query = 'ALTER TABLE ' . SERIA_PREFIX . $table . ' DROP FOREIGN KEY ' . $key . '';
					SERIA_Base::db()->query($query);
					$counter++;
				}
			} else {
				throw new Exception('1: Show create table failed');
			}
		} while ($counter > 0);
	}
?>