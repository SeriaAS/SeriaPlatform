<?php

define('LOAD_SIMPLESAMLPHP_PATH', realpath(dirname(dirname(__FILE__))).'/simplesamlphp-'.LOAD_SIMPLESAMLPHP_VERSION);

class SimplesamlLibrary
{
	const UNHANDLED_ERROR_HOOK = 'SimplesamlLibrary::UNHANDLED_ERROR_HOOK';

	protected static $hookCallback = false;
	protected static $debugCallback = false;

	public static function getSimplesamlphpPath()
	{
		return LOAD_SIMPLESAMLPHP_PATH;
	}

	public static function setDispatchHookCallback($callback)
	{
		self::$hookCallback = $callback;
	}
	public static function dispatchHookArray($hookName, $params)
	{
		if (!self::$hookCallback)
			return array();
		array_unshift($params, $hookName);
		return call_user_func_array(self::$hookCallback, $params);
	}
	public static function dispatchHook($hookName)
	{
		$params = func_get_args();
		$hookName = array_shift($params);
		return self::dispatchHookArray($hookName, $params);
	}

	public static function setLogMessageCallback($callback)
	{
		self::$debugCallback = $callback;
	}
	public static function logger($loglevel,$debugmsg)
	{
		if (self::$debugCallback) {
			call_user_func(self::$debugCallback, $loglevel, $debugmsg);
			return true;
		} else
			return false;
	}

	/**
	 *
	 * If you call this method the SimpleSAML_Error_Error unhandled error will
	 * be captured here and submitted as a Seria Platform hook
	 * SimplesamlLibrary::UNHANDLED_ERROR_HOOK.
	 */
	public static function captureUnhandledError()
	{
		require(dirname(__FILE__).'/SimpleSAML_Error_Error.class.php');
	}

	public static function includePath()
	{
		set_include_path(get_include_path().PATH_SEPARATOR.LOAD_SIMPLESAMLPHP_PATH.'/lib');
	}
	public static function autoloader()
	{
		require_once(LOAD_SIMPLESAMLPHP_PATH.'/lib/_autoload.php');
	}
}