<?php
	require_once(dirname(__FILE__)."/../../../main.php");

	$label = array_shift(array_keys($_FILES));

	if(!isset($_FILES)) {
		die("No file was uploaded");
	}

	//TODO: SJEKK ADMIN ELLER EIER AV FILEN ELLER HAS RIGHT EDIT OTHERS ARTICLES (se apps/publisher/articles.php)
	if(SERIA_Base::isLoggedIn()) {

		// $_POST['presentationId'] is the ID of the presentation, if any
		// $_POST['temporaryId'] is a unique persistent temporary ID, if folder doesn't exist, put files here while we wait for a presentation ID.

		if(isset($_POST['presentationId']))
		{
			$presentationId = intval($_POST['presentationId']);
			$tmp_uniqid = false;
			$rootPath = SERIA_FILES_ROOT.'/serialive/'.$presentationId;
		}
		else if(isset($_POST['temporaryId']))
		{
			$tmp_uniqid = $_POST['temporaryId'];
			$rootPath = SERIA_TMP_ROOT.'/serialive/'.$tmp_uniqid;
		}
		else
		{
			throw new SERIA_Exception('fileUploader requires either presentation ID or temporary ID');
		}

		$newFilename = SERIA_Sanitize::filename($_FILES[$label]['name']);

		switch($label)
		{
			case 'foils_id' :
				try {
					$webcast = SERIA_Article::createObjectFromId($_POST['presentationId']);
//					$webcast->deleteSlides();
					$result = $webcast->importSlides('default-'.date("YmdHis"), $_FILES[$label]['tmp_name'], $_FILES[$label]['name']);
					SERIA_Lib::publishJSON(
						array('slides' => LiveAPI::rpc_getFoilStatus($webcast->get('id')))
					);
					die();

				} catch (SERIA_Exception $e) {
					SERIA_Lib::publishJSON(array('error' => _t("Unable to add another slide set")));
					die();
				}

/*
				$field = $label;
				$m = umask(0);

				$slideFile = 

				if(!file_exists($rootPath.'/pres'))
					mkdir($rootPath.'/pres', 0777, true);
				move_uploaded_file($_FILES[$label]['tmp_name'], $rootPath.'/pres/'.$newFilename);
				chmod($rootPath.'/'.$newFilename, 0644);
				$url = _t('File uploaded successfully');
				$pi = pathinfo($newFilename);
				mkdir($rootPath.'/pres/'.$pi['filename'], 0777, true);
				$slideFile = SlideFile::createFromFile();
*/
				break;
			case 'uploaded_video_file_id' :
				$field = $label;
				$file = new SERIA_File($_FILES[$label]['tmp_name'], $newFilename);
				$url = $file->get("filename");
				$fileid = $file->get("id");
				break;
			case 'background_image_id' :
			case 'speaker_image_id' :
			case 'company_logo_id' :
				$file = new SERIA_File($_FILES[$label]['tmp_name'], $newFilename);
				$url = $file->getThumbnailURL(115,75);
				$field = $label;
				$fileid = $file->get("id");
				break;
			default: die('Unknown label: '.$label);
		}

		$data = array('fileid' => $fileid,'label' => $label, 'url' => $url);

		SERIA_Lib::publishJSON($data);

	} else {

		SERIA_Template::override('text/html', "not logged in or restricted access.");
	}
