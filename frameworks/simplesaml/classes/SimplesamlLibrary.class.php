<?php

class SimplesamlLibrary
{
	protected static $hookCallback = false;
	protected static $debugCallback = false;

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

	public static function includePath()
	{
		set_include_path(get_include_path().PATH_SEPARATOR.realpath(dirname(__FILE__).'/../simplesamlphp-1.5.1/lib'));
	}
	public static function autoloader()
	{
		require_once(dirname(__FILE__).'/../simplesamlphp-1.5.1/lib/_autoload.php');
	}
}