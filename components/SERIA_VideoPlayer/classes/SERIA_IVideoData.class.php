<?php

	interface SERIA_IVideoData {
		/**
		* Array(
		*	'sources' => array(
		*		array(
		*			'type' => '',
		*			'url' => '',
		*			'streamName' => '',
		*			'bitrate' => '',
		*		),
		*		array()..
		*  	),
		*  );
		*
		* @return SERIA_VideoData
		*/
		public function getVideoData();
	}
