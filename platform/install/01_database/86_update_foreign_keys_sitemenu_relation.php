<?php
	do {
		$counter = 0;
		
		$query = SERIA_Base::db()->query('SHOW CREATE TABLE ' . SERIA_PREFIX . '_sitemenu_relation');
		$rows = $query->fetchAll(PDO::FETCH_NUM);
		list($row) = $rows;
		if ($row) {
			$createTable = $row[1];
			if (preg_match('/CONSTRAINT (.*?) FOREIGN KEY \(.*?\) REFERENCES .' . SERIA_PREFIX . '.*?/i', $createTable, $matches)) {
				$key = $matches[1];
				$query = 'ALTER TABLE ' . SERIA_PREFIX . '_sitemenu_relation DROP FOREIGN KEY ' . $key . '';
				SERIA_Base::db()->query($query);
				$counter++;
			}
		} else {
			throw new Exception('1: Show create table failed');
		}
	} while ($counter > 0);
	
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD FOREIGN KEY ( `parent_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (
		`id`
	) ON DELETE CASCADE ;');

	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_sitemenu_relation` ADD FOREIGN KEY ( `child_id` ) REFERENCES `' . SERIA_PREFIX . '_sitemenu` (
		`id`
	) ON DELETE CASCADE ;');
?>