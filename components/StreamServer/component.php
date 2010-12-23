<?php
	/**
	 * 
	 * Component for communication with stream server REST XML API
	 * @author Jon-Eirik Pettersen
	 * @package streamserver
	 *
	 */
	class StreamServerManifest
	{
		const SERIAL = 1;
		const NAME = 'streamserver';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $database = array(
			'creates' => array(
			)
		);
	}
