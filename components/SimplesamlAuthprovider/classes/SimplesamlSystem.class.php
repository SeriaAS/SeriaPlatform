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

	public static function simplesamlUnhandledError($error)
	{
		$state = false;
		$url = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/components/SimplesamlAuthprovider/pages/loginFailed.php');
		try {
			$stateId = $_SERVER['X_SERIA_PLATFORM_STATE_ID'];
			$state = new SERIA_AuthenticationState($stateId);
			if ($error instanceof Exception) {
				/*
				 * This will be handled normally as an unhandled exception in Seria Platform.
				 * The trick is to create the instance of the SERIA_AuthenticationState object
				 * first, because it will be ::available() after that.
				 */
				SERIA_Base::exceptionHandler($error);
				die();
			}
			$url = $state->stampUrl($url);
		} catch (SERIA_Exception $e) {
		}
		$code = $error->getErrorCode();
		if (is_array($code))
			$code = array_shift($code);
		if ($code)
			$url->setParam('errorCode', $code);
		if (!$state && SERIA_DEBUG) {
			throw new SERIA_Exception('Error at '.$error->getFile().':'.$error->getLine().': '.$code);
		}
		SERIA_Base::redirectTo($url->__toString());
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
		SERIA_Hooks::listen(SimplesamlLibrary::UNHANDLED_ERROR_HOOK, array('SimplesamlSystem', 'simplesamlUnhandledError'));
		SimplesamlLibrary::setDispatchHookCallback(array('SERIA_Hooks', 'dispatch'));
		SimplesamlLibrary::setLogMessageCallback(array('SimplesamlSystem', 'log'));
		SimplesamlLibrary::captureUnhandledError();
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