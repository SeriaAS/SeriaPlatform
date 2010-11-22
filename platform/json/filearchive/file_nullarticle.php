<?php
	require_once(dirname(__FILE__)."/../common.php");
	
	$fileId = intval($_GET['id']);
	SERIA_Base::pageRequires("login");
	SERIA_Base::pageRequires("javascript");
	
	try {
		if ($fileId) {
			$file = SERIA_File::createObject($fileId);
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
					'filename' => $file->get('filename'),
					'url' => $file->get('url'),
					'preview' => $preview,
					'previewWidth' => $width,
					'previewHeight' => $height
				);

				/*
				 * Create null-article to reference this image.. 
				 */
				$queryObject = new SERIA_ArticleQuery();
				$queryObject->titleIs($file->get('filename'));
				$articles = $queryObject->page(0, 1);
				$fileRef = false;
				foreach ($articles as $article) {
					$files = $article->getFiles();
					foreach ($files as $wfile) {
						if ($file->get('id') == $wfile->get('id')) {
							$fileRef = true;
							break;
						}
					}
					if ($fileRef)
						break;
				}
				if (!$fileRef) {
					$article = SERIA_Base::elevateUser("SERIA_Article::createObject", "SERIA_Image");
					$article->set('title', $file->get('filename'));
					$article->set('image_id', $file->get('id'));
					$article->save();
				}
				SERIA_Lib::publishJSON($object);
			}
		}
	} catch (Exception $exception) {
		SERIA_Lib::publishJSON(array("error" => $exception->getMessage()));
	}
?>
