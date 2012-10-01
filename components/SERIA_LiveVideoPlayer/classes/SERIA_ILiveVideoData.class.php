<?php

	interface SERIA_ILiveVideoData {
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
		* @return SERIA_LiveVideoData
		*/
		public function getLiveVideoData();
	}
