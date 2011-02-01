<?php
	class SERIA_LiveArticle extends SERIA_Article
	{
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


			"published" => "string",
			"files_metadata" => "string",
			"files_metadata_populated" => "string",
			"recording_skip" => "string",
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
	}
