<?php
	require_once(dirname(__FILE__)."/../common.php");
	
	$fileId = intval($_GET['id']);
	SERIA_Base::pageRequires("login");
	SERIA_Base::pageRequires("javascript");
	SERIA_BASE::viewMode("admin");
	
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
