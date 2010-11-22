<?php
	require('../main.php');
	SERIA_Template::disable();
	define('FROM_POPUP', true);
	
	$type = 'incoming';
	
	$ftp_server_id = $_GET['ftp_server_id'];
	if ($ftp_server_id) {
		$incomingServer = SERIA_IncomingFtpServers::find($ftp_server_id);
		if (!$incomingServer) {
			die('Server not found');
		}
		$type .= '_' . $ftp_server_id;
	} else {
		
	}
	require('incoming.php');
?>