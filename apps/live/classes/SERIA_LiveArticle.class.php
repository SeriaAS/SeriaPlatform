<?php
	class SERIA_LiveArticle extends SERIA_Article
	{
		public function getProducer()
		{
			return SERIA_Meta::load('Producer', $this->get('producerId'));
		}

		/**
		*	Check if this article can be viewed, for example by administrator or the owner
		*/
		public function isViewable() {
			// everybody can watch
			if(!$this->get('not_publically_available'))
				return true;

			// admin always
			if(SERIA_Base::isAdministrator()) return true;

			// company
			if(SERIA_Base::isLoggedIn()) {
				if(ProducerUser::getCurrent()->getProducer() == $this->getProducer())
					return true;
			}
			return false;
		}

		public static function rpc_getEmbedCode($id, $width, $height) {
			$article = SERIA_Article::createObjectFromId($id);
			return $article->getEmbedCode($width, $height);
		}
		public function getEmbedCode($width, $height) {
// WARNING!!!! EMBEDCODE IS ALSO CREATED FROM serialiveplayer.mxml!!!!
			return
			'<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="'.$width.'" height="'.$height.'">'.
				'<param name="movie" value="'.SERIA_HTTP_ROOT.'/serialiveclient.swf"></param>'.
				'<param name="allowFullscreen" value="true"></param>'.
				'<param name="allowscriptaccess" value="always"></param>'.
				'<param name="flashvars" value="id='.$this->get('id').'&httpRoot='.rawurlencode(SERIA_HTTP_ROOT).'"></param>'.
				'<!--[if !IE]>-->'.
					'<object type="application/x-shockwave-flash" data="'.SERIA_HTTP_ROOT.'/serialiveclient.swf" '.
						'width="'.$width.'" height="'.$height.'">'.
						'<param name="flashvars" value="id='.$this->get('id').'&httpRoot='.rawurlencode(SERIA_HTTP_ROOT).'"></param>'.
						'<param name="allowscriptaccess" value="always"></param>'.
						'<param name="allowFullscreen" value="true"></param>'.
					'</object>'.
				'<!--<![endif]-->'.
				'</object>';
		}

		static function getTypeName()
		{
			return _t("Webcast");
		}
		static function getTypeDescription()
		{
			return _t("Create a new webcast");
		}
		
		protected $type = "SERIA_Live";

		protected $fields = array(
			"title" => "string",
			"description" => "string",
			"date" => "string",
			"company_url" => "string",
			"company_title" => "string",
			"company_description" => "string",
			"company_logo_id" => "file",
			"fms" => "string", // ie. stream.serialive.com (rtmp:// is handled by the app)
			"fms_backup" => "string",
			"streamname" => "string", // ie: serialivecom187
			"publish_point" => "string", // ie: serialivecom187_f3kTBp3 (secret)
			"application_name" => "string",
			"background_image_id" => "file",
			"foils_id" => "file",
			"keyProtected" => "boolean",
			"broadcast_method" => "string",
			"lock_time" => "string",
			"customer_id" => "string",
			"recording" => "string",
			"status" => "string",
			"preview_image_id" => "file",
			"speaker_image_id" => "file",
			"speaker_title" => "string",
			"speaker_description" => "string",
			"speaker_image" => "file",
			"pausetext" => "string",
			"current_foil" => "string",
			"current_chapter" => "string",
			"chapters" => "string", // seiralized array
			"quality" => "string", // serialize an array into the quality string to have it represented when creating an XML.
/*
$quality_defaults = array(
                                'preview_framerate' => 25,
                                'preview_width' => 640,
                                'preview_height' => 480,
                                'encode_format' => 'VP6',
                                'encode_datarate' => 450,
                                'encode_width' => 640,
                                'encode_height' => 480,
                                'auto_adjust' => false,
                                'auto_adjust_maxbuffersize' => 1,
                                'auto_adjust_dropframes' => false,
                                'auto_adjust_degradequality' => false
                        );
*/
			"uploaded_video_file_id" => "string",
			"video_files" => "string",
			"protection_methods" => "string",

			"calculatedBlockHours" => "boolean",


			"requires_authentication" => "string",

			"requires_registration" => "string",

			"requires_password" => "string",
			"presentation_password" => "string",

			"requires_emailcheck" => "string",
			"emailcheck_domain" => "string",


			"not_publically_available" => "string", // Used to define wether or not users have access to see this presentation when not logged in or not administrator
			"not_publically_available_infotext" => "string", // String delivered to users attempting to see the presentation before it is publically available.
			"published" => "string",
			"files_metadata" => "string",
			"files_metadata_populated" => "string",
			"recording_skip" => "string",
			"producerId" => "integer",
		);

		protected $fulltextFields = array(
			"customer_id" => "customer_id",
		);

		function validateData() {
			parent::validateData();

			return $this;
		}

		function recordEvent($event_type, $event_value, $serverTimestamp)
		{
			$guid = SERIA_Base::guid();
			$res = SERIA_Base::db()->exec('INSERT INTO serialive_events(id, article_id, event_type, event_value, ts) VALUES(:id, :article_id, :event_type, :event_value, :event_timestamp)', array(
				'id' => $guid,
				'article_id' => $this->get("id"),
				'event_type' => $event_type,
				'event_timestamp' => date('Y-m-d H:i:s', $serverTimestamp),
				'event_value' => $event_value)
			);
			return SERIA_Base::db()->query('SELECT * FROM serialive_events WHERE id=?', array($guid))->fetch(PDO::FETCH_ASSOC);
		}

		function toArray()
		{
			$array = array();
			foreach($this->fields as $field => $type) {
				$array[$field] = $this->get($field);
			}
			if(defined('SERIALIVE_FMS')) {
				$array['fms'] = SERIALIVE_FMS;
			}

			return $array;
		}

		function resetEvents()
		{
			$res = SERIA_Base::db()->exec('DELETE FROM serialive_events WHERE article_id='.$this->get("id"));

			return $res;
		}

		function getEvents($startTime=false, $endTime=false, $eventType=false)
		{
			if(!$this->get("id"))
				throw new SERIA_Exception('Article is not saved, cannot obtain events');

			if($eventType===false)
				$res = SERIA_Base::db()->query('SELECT id, event_type, event_value, unix_timestamp(ts) as ts from serialive_events where article_id=:article_id',array('article_id' => $this->get("id")))->fetchAll(PDO::FETCH_ASSOC);
			else
				$res = SERIA_Base::db()->query('SELECT id, event_type, event_value, unix_timestamp(ts) as ts from serialive_events where article_id=:article_id AND event_type=:event_type',array('event_type' => $eventType,'article_id' => $this->get("id")))->fetchAll(PDO::FETCH_ASSOC);

			if($startTime !== false) {
				foreach($res as $num => $row)
				{
					if($row['ts']<$startTime || $row['ts']>$endTime)
					{
						unset($res[$num]);
					}
				}

			} else {
				foreach($res as $num => $row)
				{
					if($row['ts']<$this->get("date"))
					{
						//unset($res[$num]);
					}
				}
			}
			return $res;
		}

		function recieveData($data, $prefix="")
		{
			$this->set("company_description", $data["company_description"]);
		}

		function getAbstract()
		{
			$res = array(
				"guid" => "Webcast:".$this->get("id"),
				"title" => $this->get("title"),
				"description" => $this->get("description")
			);

			return $res;
		}

		function getSeriaForm($_POST)
		{
			$form = new SERIA_LiveArticleForm($this);
			$form->fields = $this->fields;

			if($form->receive($_POST))
			{
				header("Location: ".SERIA_HTTP_ROOT."/seria/apps/live/edit.php?id=".$form->article->get("id"));
			}
			return $form->output(dirname(__FILE__).'/../templates/live_form.php');		
		}

		function isFinished()
		{
			return ($this->get("status") == 'finished' || $this->get("status") == 'published');
		}

		function getForm($prefix = false, $errors = array())
		{

			//$resultForm = $this->getSeriaForm();


/*
			$resultForm = "<h1>"._t("Title")."</h1>";
			$resultForm.= "<input type=\"text\" name='".$prefix."_title' style='width: 470px;' value='".htmlspecialchars($this->get("title"))."'>";
			
			$resultForm.= "<h1>"._t("Description")."</h1>";
			$resultForm.= "<textarea name='".$prefix."_description' style='width: 470px;'>".htmlspecialchars($this->get("description"))."</textarea>";

			"title" => "string",
			"description" => "string",
			"date" => "string",
			"fms" => "string", // ie. stream.serialive.com (rtmp:// is handled by the app)
			"streamname" => "string", // ie: serialivecom187
			"publish_point" => "string", // ie: serialivecom187_f3kTBp3 (secret)
			"application_name" => "string",
			"lock_time" => "string",
			"recording" => "string",
			"status" => "string",
			"preview_image_id" => "file",
			"speaker_image_id" => "file",
			"speaker_title" => "string",
			"speaker_description" => "string",
			"pause_image_id" => "file",
			"company_logo_id" => "file",
			"company_title" => "string",
			"company_description" => "string",
*/
			return $resultForm;
		}

		/**
		*	Import a set of slides from a file, such as a PDF. All slides imported are availeble as jpegs until deleted.
		*/
		public function importSlides($name, $path, $originalName=NULL)
		{
			$slideFile = SlideFile::create($this, $name, $path, $originalName);
			return $slideFile;
		}

		/**
		*	Delete all slides attached
		*/
		public function deleteSlides() {
			$slideFiles = SERIA_Meta::all('SlideFile')->where('webcastId=:articleId', array(
				'articleId' => $this->get('id'),
			));
			foreach($slideFiles as $slideFile)
				SERIA_Meta::delete($slideFile);
			return true;
		}

		public function getSlides($name, $width)
		{
			$slideFile = SERIA_Meta::all('SlideFile')->where('webcastId=:articleId AND name=:name', array(
				'name' => $name,
				'articleId' => $this->get('id'),
			))->current();
			if(!$slideFile) {
				// Fallback to old slide solution
				if($foilFileId = $this->get("foils_id")) {
					$currentSlide = false;
					$mask = umask(0);

					$foilFile = SERIA_File::createObject($foilFileId);
					$slides = $foilFile->convertTo('ppt2png');

					// create paths
					if(!file_exists(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres'))
						mkdir(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres', 0777, true);

					// make SlideFile from $foilFile
					$targetName = $foilFile->get('filename');
					$pi = pathinfo($targetName);
					$i = 1;
					while(file_exists(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$targetName))
						$targetName = $pi['filename'].'-'.($i++).'.'.$pi['extension'];

					file_put_contents(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$targetName, file_get_contents($foilFile->get('url')));

					$slideFile = new SlideFile();
					$slideFile->set('name', 'default');
					$slideFile->set('webcastId', $this->get('id'));
					$slideFile->set('originalName', $foilFile->get('filename'));
					$slideFile->set('path', 'serialive/'.$this->get('id').'/pres/'.$targetName);
					SERIA_Meta::save($slideFile);
					// make Slide from $slides

					if(!file_exists(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$slideFile->get('id')))
						mkdir(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$slideFile->get('id'), 0777, true);

					$slidesMap = array();
					foreach($slides as $slide)
					{
						$slidesMap[] = array('url' => $slide->get('url'), 'id' => $slide->get('id'));
					}

					$i = 1;
					foreach($slidesMap as $slideInfo)
					{
						$url = $slideInfo['url'];
						$formerSlideId = $slideInfo['id'];
						$filename = $i.'-original'.substr($url, strrpos($url, '.'));
						file_put_contents(SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$slideFile->get('id').'/'.$filename, file_get_contents($url));
						foreach(array(150,400,800,1280) as $width)
						{
							shell_exec('/usr/bin/convert '.SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$slideFile->get('id').'/'.$filename.' -resize '.$width.' -quality 80 '.SERIA_FILES_ROOT.'/serialive/'.$this->get('id').'/pres/'.$slideFile->get('id').'/'.$i.'-'.$width.'.jpg');
							$slide = new Slide();
							$slide->set('width', $width);
							$slide->set('slideFile', $slideFile);
							$slide->set('path', 'serialive/'.$this->get('id').'/pres/'.$slideFile->get('id').'/'.$i.'-'.$width.'.jpg');
							$slide->set('num', $i);
							SERIA_Meta::save($slide);

							if($width == 800)
							{
								SERIA_Base::db()->exec('UPDATE serialive_events SET event_value=:slideId WHERE event_type=:eventType AND event_value=:eventValue', $values = array(
									'slideId' => $slide->get('id'),
									'eventType' => 'SET_FOIL',
									'eventValue' => $formerSlideId,
								));
							}
							if($i == 1 && $width == 800)
								$currentSlide = $slide->get('id');
						}
						$i++;
					}
					SERIA_Base::elevateUser(array($this, 'writable'));
					$this->set('current_foil', $currentSlide);
					SERIA_Base::elevateUser(array($this, 'save'));
					umask($mask);
				}
				else
					throw new SERIA_Exception('No slides found', SERIA_Exception::NOT_FOUND);
			}

			return $slideFile->getSlides($width);
		}
	}
