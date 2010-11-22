<?php if (!defined('FROM_POPUP')) { die(); } ?>
<br />
<?php
	
	// Code for search engine. Creates objects for fileview.php
	$files = array();
	$fa = array();
	if ($type == 'archive' && (isset($_GET['search']) && ($query = $_GET['search']))) {
		$q = new SERIA_ArticleQuery("SERIA_File");
		$q->where($query);
		$q->order("created_date DESC");
		$fa = $q->page(0, 1000);
		foreach ($fa as $f) {
			$files[] = $f->getFile();
			$fa[] = $f;
		}
	} elseif (($type == 'archive' || $type == 'recent') && (isset($_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"]))) {
		$sql = 'SELECT DISTINCT file_id,value FROM '.SERIA_PREFIX.'_file_meta WHERE `key` = \'namespace\' ORDER BY -file_id';
		$rows = SERIA_Base::db()->query($sql)->fetchAll();
		foreach ($rows as $row) {
			if ($row['value'] == $_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"])
				(sizeof($files) <= 10) && $files[$row['file_id']] = true;
		}
		$fa = array();
		foreach ($files as $file_id => $tval) {
			$files[$file_id] = new SERIA_File($file_id);
			/*
			 * We don't yet handle nulls here. This is used
			 * on NUPA. If trouble appears where files are missing an
			 * article, just create in memory articles, or use a maintain
			 * job in NUPA to create articles.
			 */
			$fa[$file_id] = $files[$file_id]->getArticle();
			/* HACK PROPOSAL: if ($fa[$file_id] === false) { temp-admin, create article, fill fields, don't save! } */
		}
	} else {
		$q = new SERIA_ArticleQuery("SERIA_File");
		$q->order("created_date DESC");
		$fa = $q->page(0, 10);
		foreach ($fa as $f) {
			$files[] = $f->getFile();
			$fa[] = $f;
		}
	}


	SERIA_File::fetchThumbnailsForObjects($files);
	
	$only_images = isset($_GET['only_images']);
	$only_videos = isset($_GET['only_videos']);

	$files2 = $files;
	$files = array();
	$fileArticles = array();

	foreach ($files2 as $i => $file) {
		/*
		 * Files that are saved under another namespace should not be shown.
		 * Set the session variable to use this feature.
		 */
		$namespace = $file->getMeta('namespace');
		if (!$_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"] || ($namespace === null || $namespace == $_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"])) {
			if ($only_images) {
				if ($file->isImage()) {
					$files[$file->get('id')] = $file;
					$fileArticles[$file->get('id')] = $fa[$i];
				}
			} elseif ($only_videos) {
				if ($file->isVideo()) {
					$files[$file->get('id')] = $file;
					$fileArticles[$file->get('id')] = $fa[$i];
				}
			} else {
				$files[$file->get('id')] = $file;
				$fileArticles[$file->get('id')] = $fa[$i];
			}
		}
	}
	krsort($files);
	
	echo contents('fileview.php', array('files' => $files, 'icons' => $icons, 'type' => $type, 'multiselect' => $multiselect));
?>
