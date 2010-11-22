<?php

class SimplesamlSystem
{
	protected static $started = false;
	protected static $authsources = array();
	protected static $config = array();
	protected static $metadata = array();

	public static function saveSession()
	{
		SERIA_Base::debug('Saving simplesaml session (auto)');
		$session = SimpleSAML_Session::getInstance();
		$session->saveSession();
		foreach ($_SESSION as $nam => $val) {
			if (is_string($val))
				SERIA_Base::debug('SESSION: '.$nam.' => string:'.$val);
			else
				SERIA_Base::debug('SESSION: '.$nam.' => object:'.serialize($val));
		}
	}

	public static function registerAuthsource($name, $params)
	{
		if (self::$started)
			throw new SERIA_Exception('Registering authsource too late. Already running.');
		self::$authsources[$name] = $params;
	}
	public static function registerConfig($name, $params)
	{
		if (self::$started)
			throw new SERIA_Exception('Registering config too late. Already running.');
		self::$config[$name] = $params;
	}
	public static function registerMetadata($type, $data)
	{
		SERIA_Base::debug('Registering metadata type '.$type);
		self::$metadata[$type] = $data;
	}
	public static function getAuthsources()
	{
		SERIA_Base::debug('SimplesamlSystem::getAuthsources()');
		return self::$authsources;
	}
	public static function getConfig()
	{
		SERIA_Base::debug('SimplesamlSystem::getConfig()');
		return self::$config;
	}
	public static function getMetadata($type)
	{
		SERIA_Base::debug('getMetadata('.$type.')');
		if (isset(self::$metadata[$type]))
			return self::$metadata[$type];
		else
			return null;
	}

	public static function log($level, $msg)
	{
		switch ($level) {
			case LOG_ERR:
				$type = 'Error';
				break;
			case LOG_WARNING:
				$type = 'Warning';
				break;
			case LOG_NOTICE:
				$type = 'Notice';
				break;
			case LOG_INFO:
				$type = 'Info';
				break;
			case LOG_DEBUG:
				$type = 'Debug';
				break;
			default:
				$type = 'Log';
		}
		SERIA_Base::debug('SimpleSAML: '.$type.': '.$msg);
	}

	public static function mainConfigurationLoaded($config)
	{
		SERIA_Base::debug('Main SimpleSAML configuration was loaded.');
		if (defined('SIMPLESAML_DONT_VERIFY_HTTPS') && SIMPLESAML_DONT_VERIFY_HTTPS) {
			Auth_Yadis_Yadis::setHttpsVerifyPeer(false);
			SERIA_Base::debug('DANGER: Disabled HTTPS verify!');
		}
	}

	public static function hooks()
	{
		/* Magic hook into the simplesaml configuration */
		SERIA_Hooks::listen('simplesaml.authsources', array('SimplesamlSystem', 'getAuthsources'));
		SERIA_Hooks::listen('simplesaml.config', array('SimplesamlSystem', 'getConfig'));
		SERIA_Hooks::listen('simplesaml.metadata', array('SimplesamlSystem', 'getMetadata'));
		SERIA_Hooks::listen('simplesaml_configuration_loaded', array('SimplesamlSystem', 'mainConfigurationLoaded'));
		SERIA_Base::addFramework('simplesaml');
		SimplesamlLibrary::setDispatchHookCallback(array('SERIA_Hooks', 'dispatch'));
		SimplesamlLibrary::setLogMessageCallback(array('SimplesamlSystem', 'log'));
	}
	public static function autosaveSession()
	{
		static $autosaving = false;

		if (!$autosaving) {
			$autosaving = true;
			SERIA_Hooks::listen(SESSION_CLOSE_HOOK, array('SimplesamlSystem', 'saveSession'));
		}
	}
	public static function start()
	{
		if (self::$started)
			return;
		self::$started = true;
		SimplesamlLibrary::includePath();
		SimplesamlLibrary::autoloader();
		self::autosaveSession();
	}
}