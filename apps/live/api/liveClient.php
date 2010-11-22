<?php
	$seria_options = array("cache_expire" => 0, "skip_session" => true);

	require_once(dirname(__FILE__)."/../../../main.php");
if(!$_POST["id"])
	$_POST["id"] = $_GET["id"];

	if($_POST) {
		try {
			$article = SERIA_Article::createObjectFromId($_POST["id"]);
			$article->countView();
		} catch(SERIA_Exception $e) {
			echo "<Article><Id>0</Id><Title>".$e->getMessage()."</Title></Article>";
		}

		try {
			$speakerImage = SERIA_File::createObject($article->get("speaker_image_id"))->getThumbnailURL(1280,720);
		} catch(SERIA_Exception $e) {
			// SET NO IMAGE URL HERE
			$speakerImage = 'NULL';
		}
		try {
			$companyLogo = SERIA_File::createObject($article->get("company_logo_id"))->getThumbnailURL(1600,1200);
		} catch(SERIA_Exception $e) {
			// SET NO COMPANY LOGO URL HERE
			$companyLogo = 'NULL';
		}

		try {
			$file = SERIA_File::createObject($article->get("background_image_id"));;
			$backgroundImage = $file->getThumbnailURL(1600,1200);
			$backgroundWidth = $file->getMeta("image_width");
			$backgroundHeight = $file->getMeta("image_height");
		} catch(SERIA_Exception $e) {
			$backgroundImage = 'NULL';
		}

		if($foils_file_id = $article->get("foils_id"))
		{
			$foils_id = SERIA_File::createObject($article->get("foils_id"));
	
			$foils = generateFoils($foils_id);
		
			$foilsXML = '';
			if(is_array($foils))
			{
				foreach($foils as $foil)
				{
					$url = $foil->getThumbnailURL(1024,768);
					$foilsXML .='<Foil id="'.$foil->get("id").'" url="'.$url.'" />';
					$foilEvents[$foil->get("id")] = $url;
				}
			} else { $foilsXML.= ''; }
		}
		$eventXML = '';
		$events = $article->getEvents();

		if(is_array($events))			
		foreach($events as $event)
		{
			//Array ( [id] => 4786 [event_type] => SET_FOIL [event_value] => 4746 [ts] => 1269938233 ) 1
			$eventXML .= '<Event
					value="'.$event['event_value'].'"
					time="'.$event['ts'].'"
					type="'.$event['event_type'].'"
					'.($event['event_type'] == 'SET_FOIL' ? 'url="'.$foilEvents[$event['event_value']].'"' : '').'
				/>';
		}



		// If the article is published, generate totalduration and list files
		if($article->get("status") == 'published' || $article->get("status") == 'finished')
		{
			$files = unserialize($article->get("video_files"));
			$videoFilesCount = sizeof($files);
			$fileXML = '';
			if(sizeof($files))
			{
				$totalDuration = 0;
				$fileXML = '';
				if(is_array($files))
				{
					foreach($files as $file)
					{
						$fileXML.= '<File name="'.$file['filename'].'" duration="'.$file['duration'].'" />';
						$totalDuration+=$file['duration'];
					}
				} else {

				}
				$fileXML.= '';
			}
			else
			{
				throw new Exception('Unable to process filedata for article.');
			}
		}

//					<PublishPoint>".$article->get("publish_point")."</PublishPoint>
		//

		$articleXML .= "<Article>
					<Id>".$article->get("id")."</Id>
					<Title>".$article->get("title")."</Title>
					<Description>".$article->get("description")."</Description>
					<Date>".$article->get("date")."</Date>
					<Published>".$article->get("is_published")."</Published>
					<FMS>".$article->get("fms")."</FMS>
					<ApplicationName>".$article->get("application_name")."</ApplicationName>
					<StreamName>".$article->get("streamname")."</StreamName>
					<SpeakerImageURL>".$speakerImage."</SpeakerImageURL>
					<Status>".(($article->get("status") == "finished" && ((SERIA_Base::isLoggedIn() ? (SERIA_Base::user()->get("id")==$article->get("author_id")) : false))) ? 'published' : $article->get("status"))."</Status>
					<SpeakerTitle>".$article->get("speaker_title")."</SpeakerTitle>
					<CompanyTitle>".$article->get("company_title")."</CompanyTitle>
					<CompanyDescription>".$article->get("company_description")."</CompanyDescription>
					<SpeakerDescription>".$article->get("speaker_description")."</SpeakerDescription>
					<CompanyLogoURL>".$companyLogo."</CompanyLogoURL>
					<CompanyURL>".$article->get("company_url")."</CompanyURL>
					<BackgroundImageURL>".$backgroundImage."</BackgroundImageURL>
					<BackgroundWidth>".$backgroundWidth."</BackgroundWidth>
					<BackgroundHeight>".$backgroundHeight."</BackgroundHeight>
					<Foils>".$foilsXML."</Foils>
					<Events>".$eventXML."</Events>
					<Chapters>".$chapters."</Chapters>
					<VideoFiles ".($videoFilesCount ? "filecount='".$videoFilesCount."'" : "")." ".($totalDuration ? "duration_total='".$totalDuration."'" : "").">".$fileXML."</VideoFiles>
				</Article>";
		

		SERIA_Template::override('text/xml', $articleXML);
	}

	function generateFoils($foil_file)
	{
		return $foil_file->convertTo('ppt2png');
	}
