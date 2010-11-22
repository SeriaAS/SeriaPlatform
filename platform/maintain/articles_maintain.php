<?php
	function articles_maintain() {
		SERIA_Base::viewMode('admin');
		
		$db = SERIA_Base::db();
		
		$query = 'SELECT id FROM ' . SERIA_PREFIX . '_articles WHERE pending_publish = 1 AND is_published = 0 ORDER BY RAND() LIMIT 10';
		$articleIds = $db->query($query)->fetchAll(PDO::FETCH_NUM);
		$count = 0;
		foreach ($articleIds as $id) {
			list($id) = $id;
			try {
				$article = SERIA_Article::createObjectFromId($id);
				$article->writable(true);
				$article->updatePublishStatus();
				try {
					$article->save();
				} catch (Exception $null) {}
			} catch (SERIA_Exception $e) {
				$compare = "No such article type";
				if (substr($e->getMessage(), 0, strlen($compare)) != $compare)
					throw $e;
			}
			$count++;
		}
		
		return 'Checked publish status for ' . $count . ' articles';
	}
?>