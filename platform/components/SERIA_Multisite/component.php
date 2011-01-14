<?php
	/**
	*	Component for working with multisite support in Seria Platform.
	*
	*	@author Frode Børli
	*	@version 1.0
	*	@package seriaplatform
	*/
	class SERIA_MultisiteManifest
	{
		const SERIAL = 7;
		const NAME = 'multisite';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $metaClasses = array(
			'SERIA_Site',
			'SERIA_SiteAlias',
		);

		public static $database = array(
			'drops' => array(
			),
		);
	}
