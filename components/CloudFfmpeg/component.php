<?php
	/**
	*	Component for managing FFMpeg processes in cloud using Seria Cloud Ffmpeg REST API
	*
	*	@author Jon-Eirik Pettersen
	*	@version 0.1
	*	@package 
	*/
	class CloudFfmpegManifest
	{
		const SERIAL = 1;
		const NAME = 'cloudffmpeg';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $database = array(
			'creates' => array(
			)
		);
	}
