<?php
	/**
	*	Seria Video Player is a multiple format generic video player for Seria Platform. It provides a player for many different
	*	formats and devices.
	*
	*	@author Joakim Eide and Frode Borli
	*	@version 1.0
	*	@package SERIA_VideoPlayer
	*/
	class SERIA_VideoPlayerManifest {
		const SERIAL = 10;
		const NAME = 'videoplayer';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $folders = array(
			'SERIA_LOG_ROOT' => 'SERIA_VideoPlayer',		// used for temporary storing log files before they are added to the database
		);

		public static $menu = array(
			'controlpanel/other/videoplayer' => array(
				'title' => 'VideoPlayer configuration',
				'description' => 'Configure your videoplayer',
				'page' => 'videoplayer/config/index',
			),
		);
	}

	SERIA_Hooks::listen(SERIA_MAINTAIN_5_MINUTES_HOOK, array('SERIA_AnonymousVideoVisitorStats', 'maintainTemporaryVideoStats'));
