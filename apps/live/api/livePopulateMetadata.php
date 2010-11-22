<?php

	// Used to calculate the length of a presentation given multiple video files

	require_once(dirname(__FILE__)."/../../../main.php");

	if(!$_POST['id'])
		throw new SERIA_Exception('Article id not supplied');

	if(SERIA_Base::isLoggedIn()) {
		$article = SERIA_Article::createObjectFromId(intval($_POST['id']))

		$files = unserialize($article->get("video_files"));

		// $files[0] = serialivecom253_0.flv
		// $files[1] = serialivecom253_1.flv
		// $files[2] = serialivecom253_2.flv

		$outputXML = '<Files>';

		foreach($files as $file) {
			$outputXML.='<File name="'.$file.'" />';
		}
		$outputXML.= '</Files>';

		SERIA_Template::override('text/xml', $outputXML);
	}

