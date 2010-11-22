<?php
	require(dirname(__FILE__)."/../common.php");
	SERIA_Template::disable();
	$_GET['fmt'] = 'json';

	if(!isset($_FILES) || sizeof($_FILES)===0)
		seria_api_error(1, 'No files was uploaded.');

	if(!isset($_FILES['Filedata']))
		seria_api_error(2, 'File upload field "file" was not specified.');

//TODO: Sjekk om brukeren har rett til å laste opp en fil.
	if(SERIA_Base::isLoggedIn()) {

		$file = new SERIA_File($_FILES['Filedata']['tmp_name'], $_FILES['Filedata']['name']);

		$_GET['id'] = $file->get('id');

		require('getInstanceInfo.php');
	} else {
		die("Not logged in or restricted access.");
	}
