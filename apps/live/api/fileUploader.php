<?php
	require_once(dirname(__FILE__)."/../../../main.php");

	$label = array_shift(array_keys($_FILES));

	if(!isset($_FILES)) {
		die("No file was uploaded");
	}

	//TODO: SJEKK ADMIN ELLER EIER AV FILEN ELLER HAS RIGHT EDIT OTHERS ARTICLES (se apps/publisher/articles.php)
	if(SERIA_Base::isLoggedIn()) {
		$file = new SERIA_File($_FILES[$label]['tmp_name'], $_FILES[$label]['name']);
		$url = "";
		switch($label)
		{
			case 'foils_id' :
				$field = $label;
				$url = $file->get("filename");
				break;
			case 'uploaded_video_file_id' :
				$field = $label;
				$url = $file->get("filename");
				break;
			case 'background_image_id' :
			case 'speaker_image_id' :
			case 'company_logo_id' :
				$url = $file->getThumbnailURL(115,75);
				$field = $label;
				break;
			default: die('Unknown label: '.$label);
		}

		$data = array('fileid' => $file->get("id"),'label' => $label, 'url' => $url);

		SERIA_Lib::publishJSON($data);
	
	} else {
		
		SERIA_Template::override('text/html', "not logged in or restricted access.");
	}
