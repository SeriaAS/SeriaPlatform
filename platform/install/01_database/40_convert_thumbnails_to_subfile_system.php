<?php
	// This script will convert old thumbnail system to new sub file thumbnail system without changing URLs

	$db = SERIA_Base::db();
	
	$query = 'SELECT id, thumbnails FROM ' . SERIA_PREFIX . '_files WHERE parent_file = 0';
	$result = $db->query($query);
	$rows = $result->fetchAll(PDO::FETCH_NUM);
	foreach ($rows as $row) {
		list($id, $thumbnails) = $row;
		
		if (strlen($thumbnails) && is_array($thumbnails = unserialize($thumbnails)) && (sizeof($thumbnails))) {
			foreach ($thumbnails as $key => $thumbnail) {
				$localPath = $thumbnail['localPath'];
				$filename = $thumbnail['filename'];
				$key = 'thumb_' . $key;
				try{
					$query = 'INSERT INTO ' . SERIA_PREFIX . '_files
						(id, filename, created_date, filesize, content_type, updated_at, parent_file, relation, referrers)
						
						VALUES (
								'.$db->quote(SERIA_Base::guid()).', 
								'.$db->quote($filename).', 
								NOW(), 
								'.$db->quote(filesize($localPath)).',
								'.$db->quote(SERIA_Lib::getContentType($localPath)).',
								NOW(),
								' . $db->quote($id) . ',
								' . $db->quote($key) . ',
								1)';
					$db->query($query);
				}
				catch(Exception $null)
				{
				}
			}
			$query = 'UPDATE ' . SERIA_PREFIX . '_files SET thumbnails=\'\' WHERE id=' . $id;
			$db->query($query);
		}
	}
?>
