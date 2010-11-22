<?php
	require_once(dirname(__FILE__)."/../main.php");
	if(!SERIA_Base::user())
		die('Not logged in');

	SERIA_Template::disable();
	$again = 10;
	while(ob_end_clean() && $again--);
	
	$file_id = $_GET['file_id'];
	if (!$file_id || !is_numeric($file_id)) {
		die('Unsupported file Id');
	}
	
	$file = SERIA_File::createObject($file_id);

	if (!$file) {
		die('File not found');
	}
	
	$path = $file->get('localPath');

	if($fp = fopen($path, 'rb'))
	{
		if(ini_get('zlib.output_compression'))
	  		ini_set('zlib.output_compression', 'Off');
		$ctype = SERIA_Lib::getContentType($path);
		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false); // required for certain browsers 
		header("Content-Type: $ctype");
		header("Content-Disposition: attachment; filename=\"".basename($path)."\";" );
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: ".filesize($path));

		fpassthru($fp);
	}
