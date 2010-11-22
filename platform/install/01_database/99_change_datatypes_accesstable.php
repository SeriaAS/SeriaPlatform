<?php
	$tables = array(
		'_guid_accesstable'
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


	$db = SERIA_Base::db();
	
	$query = 'SELECT id, access_guid, object_guid FROM ' . SERIA_PREFIX . '_guid_accesstable';
	foreach ($db->query($query)->fetchAll(PDO::FETCH_NUM) as $row) {
		list($id, $access_guid, $object_guid) = $row;
		if (!is_numeric($access_guid) && !is_numeric($object_guid)) {
			list($null, $access_id) = explode(':', $access_guid);
			list($null, $object_id) = explode(':', $object_guid);
			
			$query = 'UPDATE ' . SERIA_PREFIX . '_guid_accesstable SET access_guid=' . $access_id . ', object_guid=' . $object_id . ' WHERE id=' . $id;
			$db->query($query);
		}
	}
	
	$query = 'ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` CHANGE access_guid access_guid INT NOT NULL, CHANGE object_guid object_guid INT NOT NULL';
	$db->query($query); 
	
	$query = 'ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable`
		  ADD CONSTRAINT `' . SERIA_PREFIX . '_guid_accesstable_right` FOREIGN KEY (`right_id`) REFERENCES `' . SERIA_PREFIX . '_rights` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `' . SERIA_PREFIX . '_guid_accesstable_access_guid` FOREIGN KEY (`access_guid`) REFERENCES `' . SERIA_PREFIX . '_guids` (`guid`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `' . SERIA_PREFIX . '_guid_accesstable_object_guid` FOREIGN KEY (`object_guid`) REFERENCES `' . SERIA_PREFIX . '_guids` (`guid`) ON DELETE CASCADE ON UPDATE CASCADE;';
	
	$db->query($query);
?>