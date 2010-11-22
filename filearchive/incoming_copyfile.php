<?php
	require('common.php');
	require('../main.php');
	SERIA_Template::disable();
	SERIA_Base::pageRequires('login');
	
	require('incoming_pathextract.php');
	
	$filename = $_GET['filename'];
	$ftpServer_id = $_GET['ftp_server_id'];
	
	set_time_limit(600);
	
	if ($ftpServer) {
		$i = '';
		do {
			$filePath = SERIA_TMP_ROOT . '/' . $i . basename($filename);
			$i++;
		} while (file_exists($filePath));
		
		$ftpServer->downloadFile($realPath . '/' . $filename, $filePath);
	} else {
		$filePath = $realPath . '/' . basename($filename);
		if (substr(realpath($filePath), 0, strlen($realPath)) != $realPath) {
			die('Not inside real path');
		}
	}
	
	if (file_exists($filePath) && is_file($filePath)) {
		// This line will check if the file is contained inside the incoming path (after resolving any symlinks) 
		if (true) {
			$file = SERIA_File::copyFileToObject($filePath);
			$previewUrl = '';
			$previewWidth = 0;
			$previewHeight = 0;
			try {
				list($previewUrl, $previewWidth, $previewHeight) = $file->getThumbnail(48, 48, array('transparent_fill'));
			} catch (Exception $null) {}
			
			SERIA_Lib::publishJSON(array(
				'file_id' => $file->get('id'),
				'filename' => $file->get('filename'),
				'previewUrl' => $previewUrl,
				'previewHeight' => $previewHeight,
				'previewWidth' => $previewWidth
			));
			
			die();
		}
	}
	SERIA_Lib::publishJSON(array('error' => 1));
?>