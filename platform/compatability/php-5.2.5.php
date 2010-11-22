<?php
/**
 * This file is always included when PHP-version is less than 5.2.5. It is NOT included if php version is exactly 5.2.5 or higher.
 */

if(!function_exists("sys_get_temp_dir"))
{
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
		if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
		if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
		$tempfile=tempnam(uniqid(rand(),TRUE),'');
		if (file_exists($tempfile)) {
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}
		include_once(dirname(__FILE__)."/../classes/SERIA_Exception.class.php");
		throw new SERIA_Exception("Unable to locate temporary directory");
	}
}

if(!function_exists('spl_autoload_register'))
{
	if(!isset($GLOBALS['seria']))
		$GLOBALS['seria'] = array();
	$GLOBALS['seria']['spl_autoload_register'] = array();

	if(function_exists('__autoload'))
	{
		include_once(dirname(__FILE__)."/../classes/SERIA_Exception.class.php");
		throw new SERIA_Exception("__autoload function exists. Please use spl_autoload_register('my_autoloader') instead.");
	}

	function __autoload($className)
	{
		spl_autoload_call($className);
	}

	function spl_autoload_register($callback)
	{
		$GLOBALS['seria']['spl_autoload_register'][] = $callback;
	}

	function spl_autoload_call($className)
	{
		foreach($GLOBALS['seria']['spl_autoload_register'] as $callback)
		{
			call_user_func($callback, $className);
			if(class_exists($className, false))
				return;
		}
	}
}
