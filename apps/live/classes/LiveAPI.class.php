<?php

class LiveAPI implements SERIA_RPCServer {
	public static function rpc_recordEvent($articleId, $eventType, $eventValue)
	{
		return LiveAPI::recordEvent($articleId, $eventType, $eventValue);
	}

	public static function rpc_getServerTimestamp()
	{
		return time();
	}

	private static function recordEvent($articleId, $eventType, $eventValue, $ts = 0)
	{
		LiveAPI::authenticate();

		if($ts === 0)
			$servertimestamp = time();
		else
			$servertimestamp = $ts;

		$article = LiveAPI::createArticle($articleId);
		$eventArray = $article->recordEvent($eventType, $eventValue, $servertimestamp);

		// r.id, r.value, r.type, r.preview_url, r.ts, r.relativeTs

		$event = array(
			'event_servertimestamp' => $servertimestamp,
			'event_value' => $eventArray['event_value'],
			'event_type' => $eventArray['event_type'],
			'id' => $eventArray['id'],
		);

		return $event;
	}

	/**
	*	Generates an embedcode with an event_key for onetime use.
	*	if an article is keyProtected=1 the serialiveclient will
	* 	check if keyProtected=1, if it is, check for $param['eventKey']
	*	if $param['eventKey'] is set it will remove it from the database,
	*	thus an objects embed code will only work one time.
	*/

	public static function rpc_getOneTimeEmbedCode($articleId, $width, $height) 
	{
		RPCServer::requireAuthentication();
		$db = SERIA_Base::db();

		$eventKey = md5(time().$articleId.$width.$height.'j04k');

		$res = $db->exec('INSERT INTO serialive_eventkeys VALUES(:articleId, :eventkey)',array(
			'articleId' => $articleId,
			'eventkey' => $eventKey));

		if(!$res) {
			throw new SERIA_Exception('Unable to generate event key for embed code');
		} else {
			return '<object width="'.$width.'" height="'.$height.'">
					<param name="allowFullscreen" value="true" />
					<param name="movie" value="'.SERIA_HTTP_ROOT.'/serialiveclient.swf" />
					<param name="allowscriptaccess" values="always" /><param name="quality" value="high" />
					<param name="flashvars" value="id='.$articleId.'&eventKey='.$eventKey.'" />
					<embed src="'.SERIA_HTTP_ROOT.'/serialiveclient.swf?id='.$articleId.'&eventKey='.$eventKey.'"
						quality="high"
						width="'.$width.'"
						height="'.$height.'"
						name="main"
						align="middle"
						play="true"
						loop="false"
						allowFullscreen="true"
						allowScriptAccess="always"
						type="application/x-shockwave-flash"
						pluginspage="http://www.adobe.com/go/getflashplayer">
					</embed>
				</object>';
		}
	}

	public static function rpc_addChapter($articleId, $chapterId, $chapterName)
	{
		$article = LiveAPI::createArticle($articleId);
		$article->writable();
		
		$chapters = unserialize($article->get("chapters"));
	
		$chapters[] = array('id' => $chapterId, 'name' => $chapterName);

		$article->set("chapters", serialize($chapters));
		SERIA_Base::elevateUser(array($article, 'save'));

		return true;
	}

	public static function rpc_updateChapter($articleId, $chapterId, $chapterName)
	{
		$article = LiveAPI::createArticle($articleId);
		$article->writable();
		
		$chapters = unserialize($article->get("chapters"));

		foreach($chapters as $item => $chapter)
		{
			if($chapter['id'] == $chapterId)
				$chapters[$item]['name'] = $chapterName;

			$num++;
		}

		$article->set("chapters", serialize($chapters));
		SERIA_Base::elevateUser(array($article, 'save'));
		
		return true;

	}

	public static function rpc_deleteChapter($articleId, $chapterId)
	{
		$article = LiveAPI::createArticle($articleId);
		$article->writable();
		
		$chapters = unserialize($article->get("chapters"));

		foreach($chapters as $item => $chapter)
		{
			if($chapter['id'] == $chapterId)
				unset($chapters[$item]);

			$num++;
		}

		$article->set("chapters", serialize($chapters));
		SERIA_Base::elevateUser(array($article, 'save'));
		
		return true;
	}

	public static function rpc_getChapters($articleId)
	{
		$article = LiveAPI::createArticle($articleId);
		
		return unserialize($article->get("chapters"));

	}

	public static function rpc_insertEventAfter($articleId, $type, $ts)
	{
		LiveAPI::authenticate();
	
		$event = LiveAPI::recordEvent($articleId, $type, 0, $ts);

		if($event['event_type'] == 'SET_FOIL') {
			$event['priority'] = 2;
		} else if($event['event_type'] == 'START_RECORDING') {
			$event['priority'] = 0;
		} else if($event['event_type'] == 'STOP_RECORDING') {
			$event['priority'] = 3;
		} else if($event['event_type'] == 'CHAPTER') {
			$event['priority'] = 1;
		} else if($event['event_type'] == 'RECORDING_SKIP') {
			$event['priority'] = -1;
		}

		return $event;
	}

	public static function rpc_deleteEvent($articleId, $eventId)
	{
		$db = SERIA_Base::db();
	
		$res = $db->exec('DELETE FROM serialive_events WHERE id=?', array($eventId));

		return $res;
	}

	public static function rpc_saveEvent($articleId, $id, $event_type, $event_value, $ts)
	{
		$db = SERIA_Base::db();
		$res = $db->exec('UPDATE serialive_events SET event_value=:event_value, ts=:ts WHERE id=:id', array('id' => $id, 'ts' => date('Y-m-d H:i:s', $ts), 'event_value' => $event_value));

		$r = array();
		$r['ts'] = $ts;
		$r['event_value'] = $event_value;
		$r['event_type'] = $event_type;
		if($event_type == 'SET_FOIL') {
			$foil = SERIA_Meta::load('Slide', $event_value);
			//$foil = SERIA_File::createObject($event_value);
			//$r['preview_url'] = $foil->getThumbnailURL(1400,1280);
			$r['preview_url'] = $foil->getHttpUrl();
		}

		return $r;
	} 

	private static function authenticate()
	{
		if(!SERIA_Base::isLoggedIn())
			throw new SERIA_Exception('User not authenticated.');

		SERIA_Base::viewMode('admin');
		return true;
	}

	public static function rpc_getConverterInformation($fileId) {
		if(SERIA_Base::isLoggedIn()){
			if(!isset($fileId))
				throw new SERIA_Exception('Must provide file_id for information regarding conversion status');

			$info = PowerpointConverterJobInformation::createFromFileId($fileId);

			$r['status'] = 'OK';
			$r['progress'] = $info->get("progress");
			$r['description'] = $info->get("description");
		} else {
			$r['status'] = 'FAILED';
			$r['progress'] = 'Ukjent feil';
			$r['description'] = 'Ikke logget inn';
		}

		return $r;
	}

	public static function rpc_hello($articleId)
	{
		LiveAPI::authenticate();

		if($articleId) {
			$article = LiveAPI::createArticle($articleId);
			$article->writable();
			$article->set("lock_time", time());
			SERIA_Base::elevateUser(array($article, 'save'));
		}
		return true;
	}

	public static function generateFoilsFromPPT($pptFile)
	{
		$foils = null;
		try {
			$foils = $pptFile->convertTo('ppt2png');
		} catch(SERIA_Exception $e) {

		}

		return $foils;
	}

	public static function rpc_getFoilStatus($articleId) {
		LiveAPI::authenticate();
		if($articleId) {
			$article = LiveAPI::createArticle($articleId);
			try {
				if($slides = $article->getSlides('default', 800)) {
					//$foils = SERIA_File::createObject($foilsId)->convertTo('Ziptoimages');
					//$foils_file = SERIA_File::createObject($foilsId);
					//$foils = LiveAPI::generateFoilsFromPPT($foils_file);
			
					$slidesArray = array();
					foreach($slides as $slide) {
						$slideData['id'] = $slide["id"];
						$slideData['url'] = $slide->getHttpUrl();
						$slidesArray[] = $slideData;
					}
					return $slidesArray;
				} else {
					return "No slides found";
				}
			} catch(SERIA_Exception $e) {
				if($e->getCode() == SERIA_Exception::NOT_FOUND)
					return "Error: ".$e->getMessage();

				throw $e;
			}
		} else {
			return "No article found";
		}
	}

	public static function rpc_getCustomers()
	{
		if(SERIA_Base::isLoggedIn()) {
			$customers = array();

			$customerQuery = SERIA_Meta::all('PresentationCompany');
			while($currentCustomer = $customerQuery->next()) {
				$customers[] = array("id" =>$currentCustomer->get("id"),
							"name" => $currentCustomer->get("name"));
			}

			$producerUser = new SERIA_MetaQuery('ProducerUser');
			$producerUser->where('user='.SERIA_Base::user()->get("id"));

			$productionCompany = $producerUser->current()->get("producer");

			$selfObject["id"] = $productionCompany->get("id");
			$selfObject["name"] = $productionCompany->get("name");
//joakim
			array_unshift($customers, $selfObject);
			return $customers;
		}
		else {
			return 'NOT LOGGED IN';
		}
	}
	public static function verifyKey($articleId, $key) {
		return true;
	}

	public static function generateKey($id)
	{
		$db = SERIA_Base::db();

		if($id)
			$res = $db->query('SELECT secretkey from serialive_keys where id=:id', array('id' => $id))->fetch(PDO::FETCH_COLUMN,0);

		if(!$res)
		{
			$secretkey = md5(time().'Joakim');
			$db->exec('INSERT INTO serialive_keys(id, secretkey) VALUES(:id, :secretkey)', array(
				'id' => $id,
				'secretkey' => $secretkey
			));

			return $secretkey;
		}
		return $res;
	}

	public static function createStream()
	{

		$token = LiveAPI::getNextToken();
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

	public static function getNextToken()
	{
		$currToken = intval(SERIA_Base::getParam('token'));
		$nextToken = $currToken+1;
		SERIA_Base::setParam('token', intval($nextToken));

		return $nextToken;
	}
	static function deleteStream($article)
	{
		$token = LiveAPI::getNextToken();
		$streamVars = array(
			'key' => md5(($token).STREAM_API_SERIALIVE_KEY),
			'host' => STREAM_API_SERIALIVE_DOMAIN,
			'token' => $token,
			'streamName' => $article->get("publish_point")
		);		
		$streamname = file_get_contents(STREAM_API_SERIALIVE_URL."/delete_stream.php?key=".$streamVars['key']."&host=".$streamVars['host']."&token=".$streamVars['token']."&streamName=".$streamVars['streamName']);
	}
	static function resetArticle($article) {
		LiveAPI::deleteStream($article);
		SERIA_Base::db()->exec('DELETE FROM serialive_encoder_delay WHERE id=:article_id', array('article_id' => $article->get("id")));
		$article->writable();
		$stream = LiveAPI::createStream();
		$article->set('status', 'not_initiated');
		$article->set('application_name', 'serialive');
		$article->set('publish_point', $stream['publish_point']);
		$article->set('streamname', $stream['stream_name']);
		$article->set('video_files', null);
		$article->set('uploaded_video_file_id', null);
		$article->set('current_foil', null);
		$article->resetEvents();
		return $article;
	}
	static function finishArticle($article) {
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
	
		/*
			JOAKIM

			if(file_exists(thefile_with_stop_recording))
				// check if we need to push stop recording into the database at any time
				// validate all the serialized videofiles to make sure we're not missing anything

		*/


		$article->writable();
		$article->set("status", "finished");
		$article->set("application_name", "serialive_vod");
		$article->set("video_files", $files_serialized);
		return $article;
	}

	public static function rpc_setField($articleId, $fieldName, $fieldValue)
	{
		$article = LiveAPI::createArticle($articleId);
		$article->writable();
		$article->set($fieldName, $fieldValue);
		return SERIA_Base::elevateUser(array($article, 'save'));
	}

	public static function createArticle($articleId) {
		if($articleId) {
			try {
				if(SERIA_Base::isLoggedIn()) {
					SERIA_Base::viewMode('admin');
				}
				$article = SERIA_Article::createObjectFromId($articleId);
			} catch(SERIA_Exception $e) {
				if($e->getMessage() == 'Unknown id')
					throw new SERIA_Exception('Unable to locate article with id: '.$articleId);
				else
					throw $e;
			}
		} else {
			$article = SERIA_Article::createObject("SERIA_Live");

			$stream = LiveAPI::createStream();

			// If we did not receive a publish point or the publish point starts with an _ (I'm using this for error codes) - -die.
			if(!$stream['publish_point'] || $stream['publish_point'][0] == '_') {
				throw new SERIA_Exception('Encountered an error while trying to generate streampoint');
			}
			$article->writable();
			$article->set('producerId', ProducerUser::getCurrent()->getProducer()->get("id"));
			$article->set('publish_point', $stream['publish_point']);
			$article->set('streamname', $stream['stream_name']);
		}
		return $article;
	}



	public static function rpc_save($args) {
		LiveAPI::authenticate();
		$array = json_decode($args, true);
		if(!sizeof($array))
			throw new SERIA_Exception('No data supplied');

		$article = LiveAPI::createArticle($array['id']);
		$locale = SERIA_Locale::getLocale();
		$article->writable();
		foreach($array as $field => $val) {
			$value = htmlspecialchars($val);
			if($field == "date") {
				$article->set("event_date",$value);
			}
			switch($field) {
				case "quality" :
					$article->set("quality", serialize($val));
					break;
				case "fms" :
					$article->set("fms", SERIALIVE_FMS);
					break;
				case "description" :
				case "title" :
				case "keyProtected" :
				case "date" :
				case "fms" :
				case "company_url" :
				case "application_name" :
				case "streamname" :
				case "is_published" :
				case "customer_id" :
				case "pausetext" :
				case "speaker_image_id" :
				case "foils_id" :
				case "background_image_id" :
				case "not_publically_available" :
				case "not_publically_available_infotext" :
				case "company_logo_id" :
				case "company_title" :
				case "company_description" :
				case "speaker_title" :
				case "speaker_description" :
				case "broadcast_method" :
				case "uploaded_video_file_id" : 
				case "status" :
				case "requires_registration" :
				case "requires_password" :
				case "presentation_password" :
				case "requires_emailcheck" :
				case "requires_authentication" :
				case "emailcheck_domain" :
				case "recording_skip" : 
					$article->set($field, $value);
					break;
				default :
					break;
			}
		}


		if(!$article->get("quality")) {
			// Quality has not been set, lets set all the defaults.
			$quality_defaults = array(
				'preview_framerate' => 25,
				'preview_width' => 640,
				'preview_height' => 480,
				'encode_format' => 'H.264',
				'encode_datarate' => 450,
				'encode_width' => 640,
				'encode_height' => 480,
				'auto_adjust' => false,
				'auto_adjust_maxbuffersize' => 1,
				'auto_adjust_dropframes' => false,
				'auto_adjust_degradequality' => false
			);
			$article->set("quality", serialize($quality_defaults));
		}

		if(SERIA_Base::elevateUser(array($article, 'save'))) {
			force_maintain_now();
			return self::rpc_fetchArticle($article->get("id"));
		}
		else {
			throw new SERIA_Exception('Unable to obtain priviliges to save article');
		}
	}

	public static function rpc_changeStatus($articleId, $status) {
		$article = LiveAPI::createArticle($articleId);
		$article->writable();
		switch($status) {
			case 'reset' :
				LiveAPI::resetArticle($article);
				break;
			case 'finish' :
				LiveAPI::finishArticle($article);
				break;
			default :
				$article->set("status", $status);
				break;
		}


		SERIA_Base::elevateUser(array($article, 'save'));

		return $article;
	}

	public static function rpc_getArticleInformation($id) {
		if(!$id)
			throw new SERIA_Exception('Invalid ID given, unable to create article from id '.intval($id));

//		$article = LiveAPI::createArticle($id);
		$article = SERIA_Article::createObjectFromId($id);

		$fields['requires_authentication'] = $article->get("requires_authentication");
		$fields['requires_registration'] = $article->get("requires_registration");
		$fields['requires_password'] = $article->get("requires_password");

		if($article->get("background_image_id")) {
			try {
				$file = SERIA_File::createObject($article->get("background_image_id"));;
				$fields['backgroundImage'] = $file->getThumbnailURL(1600,1200);
				$fields['backgroundWidth'] = $file->getMeta("image_width");
				$fields['backgroundHeight'] = $file->getMeta("image_height");
			} catch(SERIA_Exception $e) {
				$fields['backgroundImage'] = false;
			}
		}

		$fields['event_date'] = $article->get("event_date");
		$fields['not_publically_available'] = $article->get("not_publically_available");
		$fields['not_publically_available_infotext'] = $article->get("not_publically_available_infotext");

		$fields['isViewable'] = $article->isViewable();

		return $fields;
	}

	public static function rpc_registerFlashError($id, $error)
	{
		$counter = new SERIA_Counter("SERIA_FlashErrors");

		$counter->add(array($error, $id.'_errors', $id.'_'.$error), 1);

		return true;
	}

	public static function rpc_registerBlockHour($id, $blockNumber)
	{
		$article = SERIA_Article::createObjectFromId($id);

		// Presentation is finished, no need to count live viewers.
		if(($article->get("status") == "finished") || ($article->get("status") == "published"))
			return false;

/*
		session_start();

		if($_SESSION["block".$blockNumber] == "registered") {
			// Do nothing, we already registered
			$registerBlockView = false;
		} else {
			// Register viewer
			$registerBlockView = true;
			$_SESSION["block".$blockNumber] = "registered";
		}
*/
		$counter = new SERIA_Counter("SERIA_LiveArticleBlockHours");

//		if($registerBlockView) {
			// Register as value-click
		$counter->add(array(
				'total',
				'total_Year_'.date("Y"),
				'total_YearMonth_'.date("Y-m"),
				'total_YearMonthDate_'.date("Y-m-d"),
				'total_Year_Customer_'.date("Y").'_{'.$article->get("customer_id").'}',
				'total_YearMonth_Customer_'.date("Y-m").'_{'.$article->get("customer_id").'}',
				'total_YearMonthDate_Customer_'.date("Y-m-d").'_{'.$article->get("customer_id").'}',
				'total_Hour_'.date("H"),
				'billable_webcastviewerhour_'.$blockNumber."{".$article->get("id")."}",
				'blockhours', 'blocknumber_'.$blockNumber
			), 1
		);
//		} else {
			// Register as a duplicate (could be nice to have incase something goes wrong !
//			$counter->add(array('unbillable_blockhours', 'unbillable_blocknumber_'.$blockNumber), 1);
//		}

		return true;
	}

	public static function rpc_fetchArticle($id, $secretKey='', $secureAccessToken='') {
		session_start();
//		$article = LiveAPI::createArticle($id);
		$article = SERIA_Article::createObjectFromId($id);

		if(!$article->isViewable())
			throw new SERIA_Exception('Access denied', SERIA_Exception::ACCESS_DENIED);

		$article->countView();

		if(!$article->isViewable()) {
			if($article->get("background_image_id")) {
				try {
					$file = SERIA_File::createObject($article->get("background_image_id"));;
					$backgroundImageLarge = $file->getThumbnailURL(1600,1200);
					$backgroundImage = $file->getThumbnailURL(110,75);
					$fields['backgroundWidth'] = $file->getMeta("image_width");
					$fields['backgroundHeight'] = $file->getMeta("image_height");
				} catch(SERIA_Exception $e) {
					$backgroundImage = false;
					$backgroundImageLarge = false;
				}
				$fields['background_image_url'] = $backgroundImage;
				$fields['background_image_large_url'] = $backgroundImageLarge;
			}

			$fields['not_publically_available'] = true;
			$fields['not_publically_available_infotext'] = $article->get("not_publically_available_infotext");
			return $fields;
		}


		// JOAKIM

		if(!SERIA_Base::isAdministrator() && !(SERIA_Base::isLoggedIn() && (SERIA_Base::user()->get("id") == $article->get("author_id")))) {
			if(!(LiveAPI::verifyKey($article->get("id"), $secretKey)))
				throw new SERIA_Exception('Unable to fetch presentation, make sure you are logged in and you have the rights to the presentation in question');
		}

		$fields = $article->toArray();
		$fields['isLoggedIn'] = SERIA_Base::isLoggedIn();
		$fields['serverTime'] = time();
		$fields['id'] = $article->get("id");
		$fields['article_id'] = $article->get("id");
		$fields['is_live'] = !($isFinished = ($article->get("status") == 'published' || $article->get("status") == 'finished'));
		$files = unserialize($article->get('video_files'));
		if(sizeof($files)) {
			$fields['video_files'] = $files;
			$fields['video_files_duration_total'] = LiveAPI::generateDuration($files);
		}

		$videoFileId = $article->get("uploaded_video_file_id");
		if($videoFileId) {
			try {
				$videoFile = SERIA_File::createObject($videoFileId);
				$fields['uploaded_video_file_id'] = $videoFile->get("id");
				$fields['uploaded_video_file_url'] = $videoFile->get("filename");
			} catch(SERIA_Exception $e) {
				$fields['uploaded_video_file_id'] = 0;
				$fields['uploaded_video_file_url'] = 'Kunne ikke finne videofil: '.$e->getMessage();
			}
		}
		if($article->get("background_image_id")) {
			try {
				$file = SERIA_File::createObject($article->get("background_image_id"));;
				$backgroundImageLarge = $file->getThumbnailURL(1600,1200);
				$backgroundImage = $file->getThumbnailURL(110,75);
				$fields['backgroundWidth'] = $file->getMeta("image_width");
				$fields['backgroundHeight'] = $file->getMeta("image_height");
			} catch(SERIA_Exception $e) {
				$backgroundImage = false;
				$backgroundImageLarge = false;
			}
			$fields['background_image_url'] = $backgroundImage;
			$fields['background_image_large_url'] = $backgroundImageLarge;
		}

		if($article->get("company_logo_id")) {
			try {
				$companyLogo = SERIA_File::createObject($article->get("company_logo_id"))->getThumbnailUrl(110,75);
			} catch(SERIA_Exception $e) {
				$companyLogo = false;
			}
			$fields['company_logo_url'] = $companyLogo;
		}
		if($article->get("speaker_image_id")) {
			try {
				$speakerImage = SERIA_File::createObject($article->get("speaker_image_id"))->getThumbnailUrl(110,75);
			} catch(SERIA_Exception $e) {
				$speakerImage = false;
			}
			$fields['speaker_image_url'] = $speakerImage;
		}
//		if($foil_file_id = $article->get("foils_id")) {
		try {
			if($slides = $article->getSlides('default', 800)) {
				$fields['hasfoils'] = true;
				try {
					//$foils_file = SERIA_File::createObject($article->get("foils_id"));
					//$foilsURL = $foils_file->get("url");
	
					//$foils = LiveAPI::generateFoilsFromPPT($foils_file);
					//if(!is_array($foils)) {
					//	$foils = false;
					//} else {
						$foilsArray = array();
						$foilsUrl = array();
						foreach($slides as $slide) {
							$foilData['id'] = $slide["id"];
							$foilData['url'] = $slide->getHttpUrl();
							$foilsUrl[$slide["id"]] = $foilData['url'];
							$foilsArray[] = $foilData;
						}
						$fields['foils'] = $foilsArray;
					//}
				} catch(SERIA_Exception $e) {
					$foilsURL = false;
					$fields['foils_error'] = $e->getMessage();
				}
				//$fields['foils_filename'] = $foils_file->get("filename");
				$fields['foils_filename'] = 'Foils attached';
				if($fields['current_foil']) {
					$current_foil = SERIA_Meta::load('Slide', $article->get("current_foil"));

					$currentFoilObject = array();
					$currentFoilObject['id'] = $current_foil["id"];
					$currentFoilObject['url'] = $current_foil->getHttpUrl();

					$fields['current_foil'] = $currentFoilObject;
				}
			}
		} catch(SERIA_Exception $e) {
			if($e->getCode() !== SERIA_Exception::NOT_FOUND)
				throw $e;
			$fields['foils'] = false;
		}


		// Presentation is live - unset all foils!
		if(!$isFinished) {
			if(!SERIA_Base::isLoggedIn()) {
				unset($fields['foils']);
				unset($fields['foils_filename']);
			}
		}


		$fields['chapters'] = unserialize($article->get("chapters"));


		$events = $article->getEvents();

		if(is_array($events)) {
			foreach($events as $event)
			{
				$event['value'] = $event['event_value'];
				$event['time'] = $event['ts'];
				$event['type'] = $event['event_type'];
				if($event['event_type'] == 'SET_FOIL') {
					$event['url'] = $foilsUrl[$event['event_value']];
				}
				$events_array[] = $event;
			}
		}

		$fields['events'] = $events_array;


		$fields['secret_key'] = LiveAPI::generateKey($article->get("id"));

		try {

		$timing_information = self::getEncoderDelay($id);
//JOakim
		$fields['current_server_time'] = $timing_information['current_server_timestamp'];
		$fields['encoder_delay'] = $timing_information['encoder_delay'];

		} catch(SERIA_Exception $e) {
			// Article may not be saved?
		}

		if(!(SERIA_Base::isLoggedIn() && SERIA_Base::user()->get("id") == $article->get("author_id")) && !SERIA_Base::isAdministrator())
		{
			// Unset fields that should not be public.

			unset($fields['publish_point']);
			$fields['publish_point'] = 'hidden';
		}

		if(SERIA_Base::isLoggedIn() && (SERIA_Base::user()->get("id") == $article->get("author_id"))) {
			// Logged in and got access, no need to verify streamtoken
		} else if($fields['requires_registration'] && $fields['requires_authentication']) {
			if(!isset($secureAccessToken)) {
				$fields['streamname'] = '';
			} else if(isset($secureAccessToken) && !LiveAPI::verifyToken($article->get("id"), $secureAccessToken)) {
				$fields['streamname'] = '';
				$fields['streamname_error'] = "Failed to validate token, unable to play presentation";
			}
		}

		return $fields;
	}

	private static function userHasRegistered($presentationId) {
		session_start();
		return $_SESSION['registration'][$presentationId];
	}

	private static function sendPresentationByMail($article, $email, $name) {
		$accesstoken = LiveAPI::createToken($article->get("id"));
		mail($email, 'Hello '.$name, 'Hello watch your presentation yes please with token : '.SERIA_HTTP_ROOT.'/watch.php?id='.$article->get("id").'&amp;token='.$accesstoken);
	}

	public static function rpc_sessionTest()
	{
		session_start();
		if(!isset($_SESSION['sessionTest']))
			 $_SESSION['sessionTest'] = 0;
		return $_SESSION['sessionTest']++;
	}

	public static function rpc_requestAccessToken($articleId, $name, $email, $password) {
		$emailSent = 0;
		$article = SERIA_Article::createObjectFromId($articleId);
		if(!$article->get("requires_authentication")) {
			throw new SERIA_Exception("Requested accesstoken for public article");
		}
		if($article->get("requires_password")) {
			if(empty($password))
				$errors['password'] = 'No password specified';
			if($password == $article->get("presentation_password"))
				$passwordInputCorrect = true;
			if(!($password == $article->get("presentation_password")))
				$errors['password'] = 'Specified password was incorrect';
		}
		if($article->get("requires_registration")) {
			if(empty($name)) {
				$errors['name'] = 'Name must be provided';
			}
			if(empty($email)) {
				$errors['email'] = 'E-Mail must be provided';
			}
			if(!empty($name) && !empty($email)) {
				$article = SERIA_Article::createObjectFromId($articleId);
				if($article->get("requires_emailcheck")) {
					$emailDomain = substr($email, strpos($email, '@')+1);
					if($emailDomain == $article->get("emailcheck_domain")) {
						LiveAPI::sendPresentationByMail($article, $email, $name);
						$emailSent = 1;
					} else {
						$errors['email'] = 'Unable to grant your email address access to this presentation';
					}
				}
			}
		} else {
			// Article doesn't require registration
			// But name and email could be supplied by the flash due to flash cookies.
			// So lets check them for emptieness!
			if(empty($name))
				$name = 'Not required';
			if(empty($email))
				$email = 'notrequired@notrequired.com';
		}
		if(sizeof($errors)) {
			$requestToken = false;
		} else {

			$registeredViewer = new RegisteredViewer();

			$registeredViewer->set('name', $name);
			$registeredViewer->set('email', $email);
			$registeredViewer->set('usedPassword', $article->get("requires_password") ? 1 : 0);
			$registeredViewer->set('sentAsEmail', $emailSent);


			$registeredViewer->set('presentationId', $article->get("id"));

			try {
				SERIA_Meta::save($registeredViewer);
				$accessToken = LiveAPI::createToken($article->get("id"));
				$registeredViewer->set('accessToken', $accessToken);
				SERIA_Meta::save($registeredViewer);
			} catch (SERIA_ValidationException $e) {
				$errors['registering_viewer'] = $e->getMessage();
			}
		}

//$errors = SERIA_Meta::validate($registeredViewer);

		return array(
			'emailSent' => $emailSent,
			'accessToken' => $accessToken,
			'errors' => $errors,
		);
	}

	public static function createToken($articleId) {
		$randomToken = LiveAPI::generateRandomString(24);

		$token = new AccessToken();
		$token->set('token', $randomToken);
		$token->set('used', 0);
		$token->set('presentationId', $articleId);
		SERIA_Meta::save($token);

		return $randomToken;
	}
	public static function verifyToken($presentationId, $secureAccessToken) {
		$mq = new SERIA_MetaQuery('AccessToken');
		$mq->where('token="'.$secureAccessToken.'"');
		$mq->where('presentationId='.$presentationId);

		if($mq->count() == 1) {
			$token = $mq->current();
			$token->set('used', 1);
			return SERIA_Meta::save($token);
		} else if($mq->count() > 1) {
			throw new SERIA_Exception('More than one hit to a single access token');
		}
		return false;
	}

	public static function generateRandomString($length=6,$level=2){
		list($usec, $sec) = explode(' ', microtime());
		srand((float) $sec + ((float) $usec * 100000));

		$validchars[1] = "0123456789abcdfghjkmnpqrstvwxyz";
		$validchars[2] = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$validchars[3] = "0123456789_!@#$%&*()-=+/abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_!@#$%&*()-=+/";

		$token = "";
		$counter = 0;

		while ($counter < $length) {
			$actChar = substr($validchars[$level], rand(0, strlen($validchars[$level])-1), 1);

			// All character must be different
			if (!strstr($token, $actChar)) {
				$token .= $actChar;
				$counter++;
			}
		}
		return $token;
	}

	public static function validateRegistration($articleId, $name, $email) {

	}

	private static function generateFoilsUrl($article) {
		//if($foil_file_id = $article->get("foils_id")) {
			try {
//				$foils_file = SERIA_File::createObject($article->get("foils_id"));
//				$foilsURL = $foils_file->get("url");

				$slides = $article->getSlides('default', 800);;

				$slidesUrl = array();
				foreach($slides as $slide) {
					$slidesUrl[$slide->get('id')] = $slide->getHttpUrl();
				}
				return $slidesUrl;
			} catch(SERIA_Exception $e) {
				return false;
			}
		//}
		//return false;
	}

	public static function getEvents($article)
	{
		return self::rpc_getEvents($article->get("id"));
	}

	public static function rpc_getEvents($articleId) {
		$article = LiveAPI::createArticle($articleId);
		$events = $article->getEvents();
		$foilsUrl = LiveAPI::generateFoilsUrl($article);

		$startTime = -1;

		if(is_array($events)) {
			foreach($events as $event)
			{
				if($startTime >= $event['ts'] || $startTime == -1)
					$startTime = $event['ts'];

				$event['value'] = $event['event_value'];
				$event['time'] = $event['ts'];
				$event['type'] = $event['event_type'];
				if($event['event_type'] == 'SET_FOIL') {
					$event['url'] = $foilsUrl[$event['event_value']];
					$event['priority'] = 2;
				} else if($event['event_type'] == 'START_RECORDING') {
					$event['priority'] = 0;
				} else if($event['event_type'] == 'STOP_RECORDING') {
					$event['priority'] = 3;
				} else if($event['event_type'] == 'CHAPTER') {
					$event['priority'] = 1;
				} else if($event['event_type'] == 'RECORDING_SKIP') {
					$event['priority'] = -1;
				}
				$events_array[$event['time'].$event['priority'].md5($event['id'])] = $event;
			}
		}
		ksort($events_array);

		$events = array();
		foreach($events_array as $key => $event)
			$events[] = $event;

		for($i=0;$i<sizeof($events);$i++) {
			if($events[$i]['event_type'] == 'STOP_RECORDING' && !empty($events[$i+1]) && $events[$i+1]['event_type'] == 'START_RECORDING') {
				// We have a recording break. Represent as a single event.
				$events[$i]['event_type'] = 'RECORDING_BREAK';
				$events[$i]['type'] = 'RECORDING_BREAK';
				$events[$i]['duration'] = $events[$i+1]['time'] - $events[$i]['time'];
				unset($events[$i+1]);
			}
		}

		$eventsTmp = array();
		foreach($events as $key => $event)
			$eventsTmp[] = $event;



		return array('events' => $eventsTmp, 'startTime' => $startTime);
	}

	private static function generateDuration($files) {
		$videoFilesCount = sizeof($files);

		if(sizeof($files))
		{
			$totalDuration = 0;
			if(is_array($files))
			{
				foreach($files as $file)
				{
					$totalDuration+=$file['duration'];
				}
			}
		}
		else
		{
			throw new Exception('Unable to process filedata for article.');
		}

		return $totalDuration;
	}

	public static function setEncoderDelay($articleId, $delay) {
		try {
			$currentDelay = LiveAPI::getEncoderDelay($articleId);
		} catch(SERIA_Exception $e) {
			$currentDelay['encoder_delay'] = false;
		}
		if($currentDelay['encoder_delay'])
			SERIA_Base::db()->exec('UPDATE serialive_encoder_delay SET delay=:delay WHERE id=:id', array('id' => $articleId, 'delay' => $delay));
		else
			SERIA_Base::db()->exec('INSERT INTO serialive_encoder_delay(id, delay) VALUES(:id, :delay)', array('id' => $articleId, 'delay' => $delay));

	}

	public static function getEncoderDelay($articleId) {
		$encoder_delay = SERIA_Base::db()->query('SELECT delay FROM serialive_encoder_delay WHERE id=?', array($articleId))->fetch(PDO::FETCH_COLUMN,0);
		if($encoder_delay === false)
			throw new SERIA_Exception('Unable to fetch delay between Flash Media Encoder and server running foils, please generate XML');
		$res['encoder_delay'] = $encoder_delay;
		$res['current_server_timestamp'] = time();
		return $res;
	}

	public static function rpc_getEncoderDelay($articleId) {
		return self::getEncoderDelay($articleId);
	}

	public static function rpc_setChapter($articleId, $chapterName) {
		if(SERIA_Base::isLoggedIn()) {
			$article = LiveAPI::createArticle($articleId);

			$article->writable();
			$article->set('current_chapter', $chapterName);
			SERIA_Base::elevateUser(array($article, 'save'));
			return true;
		} else {
			throw new SERIA_Exception('Not logged in.');
		}
	}

	public static function rpc_setFoil($articleId, $foilId) {
		if(SERIA_Base::isLoggedIn()) {
			$article = SERIA_Article::createObjectFromId($articleId);

			$article->writable();
			$article->set('current_foil', $foilId);
			SERIA_Base::elevateUser(array($article, 'save'));
			return true;
		} else {
			throw new SERIA_Exception('Not logged in.');
		}
	}
}
