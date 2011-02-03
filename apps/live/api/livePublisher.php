<?php
	require_once(dirname(__FILE__)."/../../../main.php");

	function signalPageReload($article)
	{
		file_put_contents(dirname(__FILE__).'/../../../../files/reloadsignal.'.$article->get('id').'.txt', mt_rand(0,99999999));
	}
	function saveArticle($article) {
		SERIA_Base::elevateUser(array($article, 'save'));
	}
	function handleData($article, $data) {
		$locale = SERIA_Locale::getLocale();
		$article->writable();
		foreach($data as $field => $value) {
			$value = htmlspecialchars($value);
			if($field == "date") {
				$article->set("event_date",$value);
			}
			switch($field) {
				case "description" :
				case "title" :
				case "date" :
				case "fms" :
				case "company_url" :
				case "application_name" : 
				case "streamname" : 
				case "is_published" :
				case "speaker_image_id" :
				case "foils_id" :
				case "background_image_id" :
				case "company_logo_id" :
				case "company_title" :
				case "company_description" :
				case "speaker_title" :
				case "speaker_description" :
				case "recording" :
				case "broadcast_method" :
				case "status" :
					$article->set($field, $value);
					break;
				default : 
					break;
			}
		}
	}
	function outputArticle($article) {
		try {
			$companyLogo = SERIA_File::createObject($article->get("company_logo_id"))->getThumbnailUrl(110,75);
		} catch(Exception $e) {}
		try {
			$speakerImage = SERIA_File::createObject($article->get("speaker_image_id"))->getThumbnailUrl(110,75);
		} catch(Exception $e) {}

		try {
			$backgroundImage = SERIA_File::createObject($article->get("background_image_id"))->getThumbnailUrl(110,75);
		} catch(Exception $e) {

		}

		if($foil_file_id = $article->get("foils_id")) {

	                $foils_file = SERIA_File::createObject($article->get("foils_id"));
	                $foilsFilename = $foils_file->get("filename");
			$foilsFilename = $foils_file->get("url");
			$foils = generateFoils($foils_file);
			if(!is_array($foils))
				$foilConvertStatus = $foils;
			$foilsXML = '';
			foreach($foils as $foil)
			{
				$foilsXML .= '<Foil id="'.$foil->get("id").'"><url>'.$foil->getThumbnailURL(1024,768).'</url></Foil>';
			}
		}

		$articleXML .= "<Article>
					<Id>".$article->get("id")."</Id>
					<Title>".$article->get("title")."</Title>
					<Description>".$article->get("description")."</Description>
                                        <Date>".$article->get("date")."</Date>
					<Published>".$article->get("is_published")."</Published>
					<Recording>".$article->get("recording")."</Recording>
					<FMS>".$article->get("fms")."</FMS>
					<PublishPoint>".$article->get("publish_point")."</PublishPoint>
					<StreamName>".$article->get("streamname")."</StreamName>
					<ApplicationName>".$article->get("application_name")."</ApplicationName>
					<Status>".$article->get("status")."</Status>
					<SpeakerImageID>".$article->get("speaker_image_id")."</SpeakerImageID>
					<SpeakerImageURL>".$speakerImage."</SpeakerImageURL>
					<BackgroundImageID>".$article->get("background_image_id")."</BackgroundImageID>
					<BackgroundImageURL>".$backgroundImage."</BackgroundImageURL>
					<CurrentServerTime>".time()."</CurrentServerTime>
					<SecretKey>".LiveAPI::generateKey($article->get("id"))."</SecretKey>
					<BroadcastMethod>".$article->get("broadcast_method")."</BroadcastMethod>
					<FoilsID>".$article->get("foils_id")."</FoilsID>
					<FoilsFilename>".$foilsFilename."</FoilsFilename>
					<CompanyLogoID>".$article->get("company_logo_id")."</CompanyLogoID>
					<CompanyLogoURL>".$companyLogo."</CompanyLogoURL>
					<CompanyURL>".$article->get("company_url")."</CompanyURL>
					<SpeakerTitle>".$article->get("speaker_title")."</SpeakerTitle>
					<SpeakerDescription>".$article->get("speaker_description")."</SpeakerDescription>
					<CompanyTitle>".$article->get("company_title")."</CompanyTitle>
					<CompanyDescription>".$article->get("company_description")."</CompanyDescription>
					<Foils>".$foilsXML."</Foils>
					<FoilsStatus>".$foilConvertStatus."</FoilsStatus>
			</Article>";
		SERIA_Template::override('text/xml', $articleXML);
	}

	function finishArticle($article) {
		$curr_token = intval(SERIA_Base::getParam('token'));
		$streamVars = array(
			'key' => md5(($curr_token+1).STREAM_API_SERIALIVE_KEY),
			'host' => STREAM_API_SERIALIVE_DOMAIN,
			'token' => ($curr_token+1),
			'publish_point' => $article->get("publish_point"),
			'stream_name' => $article->get("streamname")
		);
			
		SERIA_Base::setParam('token', intval($curr_token+1));

		$files_serialized = file_get_contents(STREAM_API_SERIALIVE_URL."/finish.php?key=".$streamVars['key']."&host=".$streamVars['host']."&token=".$streamVars['token']."&publishPoint=".$streamVars['publish_point']."&streamName=".$streamVars['stream_name'], 'rb');
		// files_serialized should be an array
		// $files_serialized[0] => array('filename' => $filename, 'duration' => $duration); duration in ms.
		// as a representation of the saved files for the presentation.
	
		$article->writable();
		$article->set("video_files", $files_serialized);
	}

	function deleteStream($article)
	{
		$token = getNextToken();
		$streamVars = array(
			'key' => md5(($token).STREAM_API_SERIALIVE_KEY),
			'host' => STREAM_API_SERIALIVE_DOMAIN,
			'token' => $token,
			'streamName' => $article->get("publish_point")
		);		
		$streamname = file_get_contents(STREAM_API_SERIALIVE_URL."/delete_stream.php?key=".$streamVars['key']."&host=".$streamVars['host']."&token=".$streamVars['token']."&streamName=".$streamVars['streamName']);
	}

	function createStream()
	{
		$token = getNextToken();
		$streamVars = array(
			'key' => md5(($token).STREAM_API_SERIALIVE_KEY),
			'host' => STREAM_API_SERIALIVE_DOMAIN,
			'token' => ($token)
		);
		$publish_point = file_get_contents(STREAM_API_SERIALIVE_URL."/create_stream.php?key=".$streamVars['key']."&host=".$streamVars['host']."&token=".$streamVars['token']);
		
		$stream['publish_point'] = $publish_point;
		$stream['stream_name'] = $streamVars['host'].$streamVars['token'];

		return $stream;

	}

	function getNextToken()
	{
		$currToken = intval(SERIA_Base::getParam('token'));
		$nextToken = $currToken+1;
		SERIA_Base::setParam('token', intval($nextToken));

		return $nextToken;
	}

	function publishArticle($article) {
		// read the file array, post it as XML, make it avaliable to flash to ns.play to get metadata and populate them afterwards.
	}

	function resetArticle($article) {
		deleteStream($article);
		$article->writable();
		$stream = createStream();
		$article->set('publish_point', $stream['publish_point']);
		$article->set('streamname', $stream['stream_name']);
	}

	function outputError($code, $desc) {
		$errorXML = "<Error>
				<ErrorCode>".$code."</ErrorCode>
				<ErrorDescription>".$desc."</ErrorDescription>
			</Error>";

		SERIA_Template::override('text/xml',$errorXML);

		throw new SERIA_Exception($code.', '.$desc);
	}

	if(SERIA_Base::isLoggedIn()) {

		if(!$_POST) {
			outputError('41', 'No data received');
			throw new SERIA_Exception('No data received');
			die();
		}
		if($articleId = $_POST["id"]) {
			try {
				$article = SERIA_Article::createObjectFromId($articleId);
			} catch(SERIA_Exception $e) {
				outputError('0', 'Article not found');
				throw $e;
				die();
			}
		} else {
			// Creating a new article - must create a stream point for this article
			$article = SERIA_Article::createObject("SERIA_Live");
			
	                $stream = createStream();

			// If we did not receive a publish point or the publish point starts with an _ (I'm using this for error codes) - -die.
			if(!$stream['publish_point'] || $stream['publish_point'][0] == '_') {
				outputError('499', 'Unable to create publish point for presentation');
				die();
			}
			$article->writable();
			$article->set('publish_point', $stream['publish_point']);
			$article->set('streamname', $stream['stream_name']);
		}

		switch($_POST["operation"]) {
			case "save" :
				$oldStatus = $article->get('status');
				handleData($article, $_POST);
				if($oldStatus != $article->get('status'))
				{
					if(($oldStatus == 'not_initiated' || $oldStatus == 'reset') && $article->get('status')=='preparing')
						signalPageReload($article);
				}
				saveArticle($article);
				break;
			case "reset" :
				handleData($article, $_POST);
				resetArticle($article);
				saveArticle($article);
				break;
			case "finish" :
				handleData($article, $_POST);
				finishArticle($article);
				saveArticle($article);
				break;
			case "publish" :
				handleData($article, $_POST);
				publishArticle($article);
				saveArticle($article);
				break;
			default :
				outputArticle($article);
				die();
		}
		outputArticle($article);
		die();
	} else {
		outputError('42', 'Not logged in or session expired');
		die();
	}

	function generateFoils($foil_file)
	{
		return $foil_file->convertTo('ppt2png');
	}
