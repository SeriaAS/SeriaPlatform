<?php

	interface SERIA_IVodVideoData {
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
		* @return SERIA_VodVideoData
		*/
		public function getVodVideoData();
	}
