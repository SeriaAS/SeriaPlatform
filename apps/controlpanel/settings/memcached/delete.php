<?php
	require_once(dirname(__FILE__)."/../common.php");
	SERIA_Base::pageRequires("admin");
	
	if (($id = $_GET['id']) && is_numeric($id)) {
		$server = SERIA_MemcacheServers::find($id);
		if (!$server) {
			SERIA_HtmlFlash::error(_t('The requested memcached server was not found'));
		} else {
			$server->delete();
			SERIA_HtmlFlash::notice(_t('The memcached server was successfully deleted'));
		}
	} else {
		SERIA_HtmlFlash::error(_t('Unknown ID'));
	}
	
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/settings/memcached/');
?>