<?php
	/**
	*	Seria LIVE Video Player is live video player for Seria Platform.
	*
	*	@author Joakim Eide and Frode Borli
	*	@version 1.0
	*	@package SERIA_LiveVideoPlayer
	*/
	class SERIA_LiveVideoPlayerManifest {
		const SERIAL = 1;
		const NAME = 'livevideoplayer';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $folders = array(
			'SERIA_LOG_ROOT' => 'SERIA_LiveVideoPlayer',		// used for temporary storing log files before they are added to the database
		);

		public static $menu = array(
			'controlpanel/other/livevideoplayer' => array(
				'title' => 'LiveVideoPlayer configuration',
				'description' => 'Configure your livevideoplayer',
				'page' => 'livevideoplayer/config/index',
			),
		);
	}
