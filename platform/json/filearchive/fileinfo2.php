<?php
	require_once(dirname(__FILE__)."/../common.php");

	SERIA_Base::pageRequires("login");
	SERIA_Base::pageRequires("javascript");
	SERIA_BASE::viewMode("admin");
	if (isset($_GET['articlesToFiles'])) {
		$ids = explode(',', $_GET['articlesToFiles']);
		$fids = array();
		foreach ($ids as $id) {
			if ($id || $id === 0) {
				$article = SERIA_Article::createObjectFromId($id);
				$file = SERIA_File::createObject($article->get('file_id'));
				$fids[] = $file->get('id');
			}
		}
		SERIA_Lib::publishJSON(array(
			'ids' => implode(',', $fids)
		));
		return;
	}
	$fileId = intval($_GET['id']);
	
	try {
		if ($fileId) {
			try {
				$article = SERIA_Article::createObjectFromId($fileId);
				$file = SERIA_File::createObject($article->get('file_id'));
			} catch (SERIA_NotFoundException $e) {
				$file = SERIA_File::createObject($fileId);
			}
			if ($file->get('id')) {
				$preview = '';
				$previewWidth = 0;
				$previewHeight = 0;
				try {
					list($preview, $width, $height) = $file->getThumbnail(48, 48);
				} catch (Exception $null) {
					$preview = '';
					$previewWidth = 0;
					$previewHeight = 0;
				}
				
				$object = array(
					'id' => $file->get('id'),
					'url' => $file->get('url'),
					'filename' => $file->get('filename'),
					'preview' => $preview,
					'previewWidth' => $width,
					'previewHeight' => $height
				);
				
				SERIA_Lib::publishJSON($object);
			}
		}
	} catch (Exception $exception) {
		SERIA_Lib::publishJSON(array("error" => $exception->getMessage()));
	}
?>
