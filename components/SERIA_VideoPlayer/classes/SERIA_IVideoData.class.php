<?php

	interface SERIA_IVideoData {
		/**
		* Array(
		*	'videoSources' => array(
		*		array(
		*			'type' => '',
		*			'url' => '',
		*			'streamName' => '',
		*			'bitrate' => '',
		*			'weight' => 0,
		*		),
		*		array()..
		*  	),
		*	'thumbnail' => array(
		*		'large' => '...',
		*		'small' => '...',
		*	),
		*  );
		*
		* @return Array
		*/
		public function getVideoData();
	}
