<?php
	require_once(dirname(__FILE__)."/../common.php");
	SERIA_Base::pageRequires("admin");
	
	if (($id = $_GET['id']) && is_numeric($id)) {
		$ftpServer = SERIA_IncomingFtpServers::find($id);
		if (!$ftpServer) {
			SERIA_HtmlFlash::error(_t('The requested FTP server was not found'));
		} else {
			$ftpServer->delete();
			SERIA_HtmlFlash::notice(_t('The FTP server was successfully deleted'));
		}
	} else {
		SERIA_HtmlFlash::error(_t('Unknown ID'));
	}
	
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/incomingftpservers/');
?>