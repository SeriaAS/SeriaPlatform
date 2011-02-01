<?php

	/**
	*	Presentations are presentation objects held in SeriaLive
	*/

	class Presentation extends SERIA_MetaObject {
		public static function Meta($instance=NULL) {
			return array(
				'table' => '{presentations}',
				'displayField' => 'title',
				'fields' => array(
					/**
					* Presentation settings
					*
					'title' => array('title required', _t("Title")),
					'description' => array('text', _t("Description")),
					'protectionMethod' => array('', _t("Protection method")),
					'producer' => array('Producer required', _t("Producer")),
					'presentationCompany' => array('PresentationCompany required', _t("Presentation company")),
					/**
					* Streaming related settings
					*
					'rtmpUrl' => array('rtmp_url', _("Flash streaming url")),
					'rtmpUrlBackup' => array('rtmp_url', _t("Backup flash streaming url")),
					'smartphoneUrl' => array('url', _t("Smartphone stream url")),
					//'applicationName' => .. (depreacte, put in rtmpUrl, rtmpUrlBackup?)
					'streamPoint' => array('StreamPoint required', _t("Stream point")),
					'qualitySettings' => array('quality_settings', _t("Quality settings")), // Used by standard encoder
					'broadcastMethod' => array('integer', _t("Broadcast method")),
					/**
					* Broadcasting related settings:
					*
					'presentationStatus' => array('text', _t("Presentation status")),
					'pauseText' => array('text', _t("Pausetext")),
					'currentFoil' => array('integer', _t("Current foil")),
					'curentChapter' => array('integer', _t("Current chapter")),
					'chapters' => array('chapters', _t("Chapters")),
					/**
					* Files before/after presentation
					*
					'logoImage' => array('file', _t("Upload logo")),
					'backgroundImage' => array('file', _t("Upload background image")),
					'powerpointFoils' => array('files', _t("Powerpoint foils")),
					'downloadableFiles' => array('files', _t("Downloadable files")),
					'uploadedVideoFile' => array('file', _t("Uploaded video file")), // Make plural?
					*/
				)
			);
		}

		public function editAction()
		{
			$form = SERIA_Meta::editAction('edit_presentation', $this, array(
				'title',
				'description',
			));

			return $form;
		}
	}
