<?php
	/**
	*	Seria VOD Video Player is vod video player for Seria Platform.
	*
	*	@author Joakim Eide and Frode Borli
	*	@version 1.0
	*	@package SERIA_VodVideoPlayer
	*/
	class SERIA_VodVideoPlayerManifest {
		const SERIAL = 1;
		const NAME = 'vodvideoplayer';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $folders = array(
			'SERIA_LOG_ROOT' => 'SERIA_VodVideoPlayer',		// used for temporary storing log files before they are added to the database
		);

		public static $menu = array(
			'controlpanel/other/vodvideoplayer' => array(
				'title' => 'VodVideoPlayer configuration',
				'description' => 'Configure your vodvideoplayer',
				'page' => 'vodvideoplayer/config/index',
			),
		);
	}
