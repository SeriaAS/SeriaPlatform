<?php
	$db = SERIA_Base::db();
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_article_categories')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		$key = SERIA_Base::db()->quote('category:' . $id);
		SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_guids SET `key`=' . $key . ' WHERE guid=' . $id);
	}
?>