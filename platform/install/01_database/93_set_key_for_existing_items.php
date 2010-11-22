<?php
	function updateGuid($id, $key) {
		$id = (int) $id;
		$key = SERIA_Base::db()->quote($key . ':' . $id);
		SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_guids SET `key`=' . $key . ' WHERE guid=' . $id);
	}

	$db = SERIA_Base::db();
	
	$articles = $db->query('SELECT id from ' . SERIA_PREFIX . '_articles')->fetchAll(PDO::FETCH_NUM);
	foreach ($articles as $article) {
		list($id) = $article;
		updateGuid($id, 'article');
	}
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_users')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		updateGuid($id, 'user');
	}
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_user_groups')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		updateGuid($id, 'usergroup');
	}
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_widgets')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		updateGuid($id, 'widget');
	}
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_files')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		updateGuid($id, 'file');
	}
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_sitemenu')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		updateGuid($id, 'menu');
	}
	
	$items = $db->query('SELECT id from ' . SERIA_PREFIX . '_event_listeners')->fetchAll(PDO::FETCH_NUM);
	foreach ($items as $item) {
		list($id) = $item;
		updateGuid($id, 'eventlistener');
	}
	
?>