<?php
	require_once(dirname(__FILE__)."/../common.php");
	SERIA_Base::pageRequires("admin");
	
	if (($id = $_GET['id']) && is_numeric($id)) {
		$object = SERIA_FtpFiletypes::find($id);
		if (!$object) {
			SERIA_HtmlFlash::error(_t('The requested record was not found'));
		} else {
			$object->delete();
			SERIA_HtmlFlash::notice(_t('The record was successfully deleted'));
		}
	} else {
		SERIA_HtmlFlash::error(_t('Unknown ID'));
	}
	
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/ftpservers/addserver.php?edit=' . (int) $_GET['ftp_server_id'] . '#filetypes');
?>