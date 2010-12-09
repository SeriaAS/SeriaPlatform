<?php
	// Default configuration files. All values must check if local file
	// have defined the value before modifying.

	// The description of the config values are in _config.php.sample
	defined('SERIA_HTTP_ROOT') || define('SERIA_HTTP_ROOT', 'http'.($_SERVER['HTTPS']?'s':'').'://'.$_SERVER['HOST_NAME']);
	defined('SERIA_ROOT') || define('SERIA_ROOT', realpath(dirname(__FILE__) . '/../..'));
	defined('SERIA_TEMPLATE_ROOT') || define('SERIA_TEMPLATE_ROOT', SERIA_ROOT.'/templates');
	defined('SERIA_TEMPLATE_HTTP_ROOT') || define('SERIA_TEMPLATE_HTTP_ROOT', SERIA_HTTP_ROOT.'/templates');
	defined('SERIA_DEBUG') || define('SERIA_DEBUG', 0);
	defined('SERIA_ERROR_EMAIL') || define('SERIA_ERROR_EMAIL', 'error@seria.no');
	defined('SERIA_EMAIL_FROM') || define('SERIA_EMAIL_FROM', 'seria_platform@'.$_SERVER['HTTP_HOST']);
	defined('SERIA_PREFIX') || define('SERIA_PREFIX', 'seria');
	defined('SERIA_FILES_DELAY') || define('SERIA_FILES_DELAY', 0);

	/*DEPRECATED*/ defined('SERIA_CACHED_HTTP_ROOT') || define('SERIA_CACHED_HTTP_ROOT', SERIA_HTTP_ROOT);
	
	defined('SERIA_DB_HOST') || define('SERIA_DB_HOST', 'localhost');		// only required when PDO is not installed (fallback to mysqli)
	defined('SERIA_DB_NAME') || define('SERIA_DB_NAME', 'seriaplatform');	// only required when PDO is not installed (fallback to mysqli)
	defined('SERIA_DB_USER') || define('SERIA_DB_USER', '');
	defined('SERIA_DB_PASSWORD') || define('SERIA_DB_PASSWORD', '');
	defined('SERIA_DB_PORT') || define('SERIA_DB_PORT', '3306');
	defined('SERIA_DB_TYPE') || define('SERIA_DB_TYPE', 'mysql');
	/*DEPRECATED*/ defined('SERIA_DB_DSN') || define('SERIA_DB_DSN', SERIA_DB_TYPE.':host='.SERIA_DB_HOST.';port='.(defined('SERIA_DB_PORT')?SERIA_DB_PORT:'3306').';dbname='.SERIA_DB_NAME);
	
	defined('SERIA_TIMEZONE') || define('SERIA_TIMEZONE', 'Europe/Oslo');

	/**
	*	Only SERIA_FILES_ROOT and SERIA_FILES_HTTP_ROOT is needed in config.
	*/
	if(defined('SERIA_FILES_ROOT') && defined('SERIA_FILES_HTTP_ROOT'))
	{ // all ok, make SERIA_UPLOAD_ROOT available for backward compatability
		if(defined('SERIA_UPLOAD_ROOT') || defined('SERIA_UPLOAD_HTTP_ROOT'))
			throw new Exception('Neither SERIA_UPLOAD_ROOT or SERIA_UPLOAD_HTTP_ROOT must be declared when you have defined SERIA_FILES_ROOT in _config.php');

		// REQUIRED CONSTANTS
		defined('SERIA_PRIV_ROOT') || define('SERIA_PRIV_ROOT', SERIA_FILES_ROOT.'/priv');
		defined('SERIA_DYN_ROOT') || define('SERIA_DYN_ROOT', SERIA_FILES_ROOT.'/dyn');
		defined('SERIA_DYN_HTTP_ROOT') || define('SERIA_DYN_HTTP_ROOT', SERIA_FILES_HTTP_ROOT.'/dyn');
		defined('SERIA_TMP_ROOT') || define('SERIA_TMP_ROOT', SERIA_PRIV_ROOT.'/tmp');
		defined('SERIA_CACHE_ROOT') || define('SERIA_CACHE_ROOT', SERIA_TMP_ROOT.'/cache');
		defined('SERIA_LOG_ROOT') || define('SERIA_LOG_ROOT', SERIA_PRIV_ROOT.'/logs');

		/**
		*	Make sure that we do not have a combination of new and old file structure.
		*/
		$illegals = array('SERIA_UPLOAD_ROOT', 'SERIA_UPLOAD_HTTP_ROOT', 'SERIA_DYNAMICCLASS_ROOT', 'SERIA_FILE_INCOMING_ROOT');
		foreach($illegals as $null)
			if(defined($null)) throw new Exception('Deprecated config constant '.$null.' is defined. Can\'t be combined with SERIA_FILES_ROOT. Use symlinks instead.');

		// BACKWARD COMPATABILITY
		define('SERIA_UPLOAD_ROOT', SERIA_FILES_ROOT);
		define('SERIA_UPLOAD_HTTP_ROOT', SERIA_FILES_HTTP_ROOT);
		define('SERIA_DYNAMICCLASS_ROOT', SERIA_PRIV_ROOT.'/activerecord');
		define('SERIA_FILE_INCOMING_ROOT', SERIA_PRIV_ROOT.'/incoming');

	}
	else if(defined('SERIA_FILES_ROOT') || defined('SERIA_FILES_HTTP_ROOT'))
	{
		throw new Exception('Both SERIA_FILES_ROOT and SERIA_FILES_HTTP_ROOT must be defined in _config.php');
	}
	else
	{ // most likely an old config which we must make compatible

		// Old config allowed SERIA_UPLOAD_ROOT and SERIA_UPLOAD_HTTP_ROOT to be automatically defined.
		defined('SERIA_UPLOAD_ROOT') || define('SERIA_UPLOAD_ROOT', SERIA_ROOT . '/files');
		defined('SERIA_UPLOAD_HTTP_ROOT') || define('SERIA_UPLOAD_HTTP_ROOT', SERIA_HTTP_ROOT . '/files');
		define('SERIA_FILES_ROOT', SERIA_UPLOAD_ROOT);
		define('SERIA_FILES_HTTP_ROOT', SERIA_UPLOAD_HTTP_ROOT);

		define('SERIA_PRIV_ROOT', SERIA_UPLOAD_ROOT.'/priv');
		defined('SERIA_DYN_ROOT') || define('SERIA_DYN_ROOT', SERIA_FILES_ROOT.'/dyn');
		defined('SERIA_DYN_HTTP_ROOT') || define('SERIA_DYN_HTTP_ROOT', SERIA_FILES_HTTP_ROOT.'/dyn');

		defined('SERIA_TMP_ROOT') || define('SERIA_TMP_ROOT', SERIA_PRIV_ROOT.'/tmp');
		defined('SERIA_CACHE_ROOT') || define('SERIA_CACHE_ROOT', SERIA_TMP_ROOT.'/cache');
		defined('SERIA_LOG_ROOT') || define('SERIA_LOG_ROOT', SERIA_PRIV_ROOT.'/logs');

		// BACKWARD COMPATABILITY
		defined('SERIA_DYNAMICCLASS_ROOT') || define('SERIA_DYNAMICCLASS_ROOT', SERIA_PRIV_ROOT.'/activerecord');
		defined('SERIA_FILE_INCOMING_ROOT') || define('SERIA_FILE_INCOMING_ROOT', SERIA_PRIV_ROOT.'/incoming');
	}

	/*DEPRECATED*/ defined('SERIA_STATIC_FTP') || define('SERIA_STATIC_FTP', 0);
	
	defined('SERIA_XHTML') || define('SERIA_XHTML', 1);

	if(!defined('SERIA_DOCTYPE'))
	{
		if(SERIA_XHTML)
			define('SERIA_DOCTYPE', 'XHTML  1.0 Transitional');
		else
			define('SERIA_DOCTYPE', 'HTML 4.01 Transitional');
	}
	
	/*DEPRECATED*/ defined('SERIA_SESSION_TTL') || define('SERIA_SESSION_TTL', 3600);
	
	
	//TODO: DISABLED AS LONG AS pages/forgotten.php IS A SKELETON
	/*DEPRECATED*/ defined('SERIA_FORGOT_PASSWORD_ENABLED') || define('SERIA_FORGOT_PASSWORD_ENABLED', 0);
	/*DEPRECATED*/ defined('SERIA_ARTICLE_TYPES') || define('SERIA_ARTICLE_TYPES', '');
	
	defined('SERIA_GUI_TEMPLATE') || define('SERIA_GUI_TEMPLATE', dirname(__FILE__).'/templates/seria/seria.php');
	defined('SERIA_CLI_TEMPLATE') || define('SERIA_CLI_TEMPLATE', dirname(__FILE__).'/templates/seria_cli/seria.php');
	
	defined('SERIA_NOCACHE') || define('SERIA_NOCACHE', 0);

	defined('SERIA_LOCALE') || define('SERIA_LOCALE', '');
	defined('SERIA_LOCALE_PLATFORMTIME') || define('SERIA_LOCALE_PLATFORMTIME', false);

	defined('SERIA_AUTOMAINTAIN_DISABLED') || define('SERIA_AUTOMAINTAIN_DISABLED', 0);
	
	defined('SERIA_PAYMENT_PROVIDER') || define('SERIA_PAYMENT_PROVIDER', false);

	defined('SERIA_SALT') || define('SERIA_SALT', md5(SERIA_DB_DSN.'I love Seria!'));

	defined('SERIA_LANG_PATH') || define('SERIA_LANG_PATH', SERIA_ROOT.'/seria_lang');

	defined('SERIA_TRANSLATION_APPLICATION_ENABLE') || define('SERIA_TRANSLATION_APPLICATION_ENABLE', 0);

	defined('SERIA_USER_AGENT') || define('SERIA_USER_AGENT', 'Seria Platform/1.0 ('.$_SERVER['HTTP_HOST'].')');

	defined('SERIA_CURRENCY') || define('SERIA_CURRENCY', 'EUR');	// ISO 4217: http://www.iso.org/iso/support/currency_codes_list-1.htm

	defined('SERIA_VALIDATE_EMAIL_HOST') || define('SERIA_VALIDATE_EMAIL_HOST', false);


	if(!file_exists(SERIA_PRIV_ROOT.'/SERIA_PLATFORM.php'))
	{
		if(defined('SERIA_INSTALL'))
		{
			require_once(SERIA_ROOT.'/seria/platform/classes/SERIA_Base.class.php');
			SERIA_Base::displayErrorPage('500', 'Configuration error', 'Please undefine the SERIA_INSTALL constant in your configuration file. The SERIA_INSTALL configuration value is deprecated.');
		}

		define('SERIA_INSTALL', 1);
	}
	else
	{
		require_once(SERIA_PRIV_ROOT.'/SERIA_PLATFORM.php');
		if($GLOBALS['seria_install']['timestamp']>time()-60*10)
		{ // INSTALL MODE IS AUTOMATICALLY ENABLED FOR TEN MINUTES AFTER INITIAL RUN OF THE INSTALL SCRIPT
			define('SERIA_INSTALL', 1);
		}
		else
		{
			define('SERIA_INSTALL', 0);
		}
	}

	if(defined('SERIA_SWITCHED_DB_NAME'))
		define('SERIA_SESSION_SUFFIX', SERIA_SWITCHED_DB_NAME);
	else
		define('SERIA_SESSION_SUFFIX', SERIA_DB_NAME);


	defined('SERIA_COMPATIBILITY') || define('SERIA_COMPATIBILITY', 1);
	defined('SERIA_SHOW_SERIA_INFORMATION') || define('SERIA_SHOW_SERIA_INFORMATION', 1); // Displaying information about Seria in the source code
