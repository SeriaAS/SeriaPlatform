<?php if (!defined('FROM_POPUP')) { die(); } ?>
<?php
	$path =& $_SESSION['path'];
	if ($_GET['path']) {
		$path = $_GET['path'];
	}
	if (!$path) {
		$path = '/';
	}
	
	if ($path[0] != '/') {
		$path = '/' . $path;
	}
	
	$useFtp = false;
	$ftpServer_id = 0;
	try {
		if ($ftpServer_id = $_GET['ftp_server_id']) {
			$useFtp = true;
			$ftpServer = SERIA_IncomingFtpServers::find($ftpServer_id);
			if ($ftpServer) {
				$ftpServer->connect();
			}
		} else {
			$ftpServer_id = 0;
		}
	} catch (Exception $exception) {
		$ftpServer = false;
		die('FTP server ' . $ftpServer->host . ' unavailable: ' . $exception->getMessage());
	}
	
	if (!$ftpServer) {
		$incomingDirectory = SERIA_FILE_INCOMING_ROOT;
	} else {
		$incomingDirectory = '/';
	}
	
	$path = preg_replace('/\/+/', '/', $path);
	$path = rtrim($path, '/');
	if (!strlen($path)) {
		$path = '/';
	}
	
	if (!$ftpServer) {
		$realPath = realpath($incomingDirectory . '/' . $path);
		$realIncomingDirectory = realpath($incomingDirectory);
		
		if (substr($realPath, 0, strlen($realIncomingPath)) != $realIncomingPath) {
			die('Path is not inside incoming directory');
		}
	} else {
		$parts = explode('/', $path);
		foreach ($parts as $part) {
			if ($part == '..') {
				die('');
			}
		}
		$realPath = $path;
		$realIncomingDirectory = '/';
	}
	
	if ((!$ftpServer && !file_exists($realPath)) || ($ftpServer && !$ftpServer->fileExists($path))) {
		include('incoming_directorynotfound.php');
	}
?>