<?php
	/**
	*	Provides internationalization functions for Seria Platform. PHP 5.3 Locale and related classes should be used
	*	whenever possible.
	*	@author Frode Borli
	*	@version 1
	*	@package platform
	*/
	class SERIA_InternationalizationManifest {
		const SERIAL=1;
		const NAME='intl';
		public static $classPaths = array(
			'classes/*.class.php',
		);
	}
