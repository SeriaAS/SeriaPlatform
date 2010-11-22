<?php
	$fileId = intval($_GET['id']);
	
	require_once(dirname(__FILE__)."/../main.php");
	
	$gui = new SERIA_Gui(_t('File archive'));
	
	SERIA_Base::pageRequires("login");
	SERIA_Base::pageRequires("javascript");
	SERIA_Base::viewMode("admin");
	SERIA_Template::disable();
	
	$file = SERIA_File::createObject($fileId);
	if ($file->get('id')) {
		require_once('filepreview.html.php');
	} else {
	}
?>
