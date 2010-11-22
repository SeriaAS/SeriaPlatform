<?php
	require('common.php');
	require('../main.php');
	SERIA_Template::disable();
	SERIA_Base::pageRequires('login');
	
	require('incoming_pathextract.php');
	
	if ((!$ftpServer && file_exists($realPath)) || ($ftpServer && $ftpServer->fileExists($realPath))) {
		$pathHtml = '';
		$pathIter = '';
		$trimmedPath = trim($path, '/');
		
		if ($ftpServer) {
			$ftpServer_id = $ftpServer->id;
		} else {
			$ftpServer_id = 0;
		}
		
		if (strlen($trimmedPath)) {
			$directoryParts = explode('/', $trimmedPath);
			if (sizeof($directoryParts)) {
				foreach ($directoryParts as $directory) {
					$pathIter .= $directory . '/';
					$pathHtml .= '<a href="" onclick="return openDirectory(\'' . addslashes(htmlspecialchars(trim($pathIter, '/\\'))) . '\', ' . $ftpServer_id . ');">' . $directory . '</a>/';
				}
			}
		}

	?>
		<h2><a href="" onclick="return openDirectory('/', <?php echo $ftpServer_id; ?>)">root</a>/<?php echo $pathHtml; ?></h2>
		
		<ul class="clickableIcons">
		
	<?php
		if (!$ftpServer) {
			$dirhandler = opendir($realPath);
			$files = array();
			$directories = array();
			while ($file = readdir($dirhandler)) {
				if (is_file($realPath . '/' . $file)) {
					$files[] = array($file, filesize($realPath . '/' . $file));
				} elseif (is_dir($realPath . '/' . $file)) {
					$directories[] = $file;
				}
			}
		} else {
			list($directories, $files) = $ftpServer->getFileListWithFileSize($path);
		}
		
		$fileid = 0;
		
		$directoryList = '';
		$fileList = '';
		
		foreach ($directories as $directory) {
			if ($directory[0] != '.') {
				$filePath = $realPath . '/' . $directory;
				$filename = $directory;
				if ($ftpServer) {
					$ftpServer_id = $ftpServer->id;
				} else {
					$ftpServer_id = 0;
				}
				$tagArguments = 'href="" onclick="return openDirectory(\'' . addslashes($path . '/' . $filename) . '\', ' . $ftpServer_id . ')"';
				$fileid++;
				require(dirname(__FILE__) . '/directoryicon.php');
				$directoryList .= 'directoryList[\'fileIcon' . $fileid . '\'] = ' . json_encode($path . '/' . $filename) . '; ';
			}
		}
		
		foreach ($files as $fileData) {
			list($file, $filesize) = $fileData;
			$filePath = $realPath . '/' . $file;
			$filename = $file;
			$icon = getIconFromFilename($filename);
			$fileid++;
			$extraClass = 'incomingFileIcon';
			$fileList .= 'incomingFileList[\'fileIcon_' . $type . '_' . $fileid . '\'] = ' . json_encode($filename) . '; ';
			$fileList .= 'incomingFileSize[\'fileIcon_' . $type . '_' . $fileid . '\'] = ' . json_encode($filesize) . '; ';
			$fileList .= 'incomingPathList[\'fileIcon_' . $type . '_' . $fileid . '\'] = ' . json_encode($path) . '; ';
			$fileList .= 'incomingFtpServerList[\'fileIcon_' . $type . '_' . $fileid . '\'] = ' . json_encode($ftpServer_id) . '; ';
			require(dirname(__FILE__) . '/fileicon.php');
		}
	}
?>
</ul>
<div style="clear: both"></div>
<script type="text/javascript">
	var directoryList = {};
	
	$(function() {
		<?php echo $directoryList; ?>
		<?php echo $fileList; ?>
	});

	$('.directoryIcon').bind('click', function() {
		var element = $(this);
		var id = element.attr('id');
		var directoryToOpen = directoryList[id];
		
		openDirectory(directoryToOpen, <?php if ($ftpServer->id) { echo $ftpServer->id; } else { echo 0; } ?>);
	});
	
	$('.incomingFileIcon').bind('click', function() {
		var element = $(this);
		var id = element.attr('id');
		
		selectFile(element);
		
	});
	
	$('.incomingFileIcon').bind('dblclick', function() {
		$(this).trigger('click');
		useFileClick();
	});
</script>
