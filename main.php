<?php
/**
 *	This file is the main bootstrap for Seria Platform. Including this file ensures that you have full access
 *	to all libraries and functions in a consistent manner.
 *
 *	This file performs the following tasks:
 *	1. Standardizes the environment to comply with PHP version 5.2.5 default settings.
 *	2. Loads the configuration file (./_config.php)
 *	3. Loads default configuration values for everything not configured in the configuration file.
 *	4. Creates $GLOBALS['seria'] which serves as a namespace used to store certain information relevant for the request.
 *	5. Sets up autoloading of classes.
 *	6. Connects to the database (which it should not, unless the database is accessed - will be fixed)
 *
 *	When including this file, a global variable may or may not already be defined:
 *	       $seria_options = array(
 *       	        'skip_session' => true,		// do not start session; this enables public caching
 *       	        'cache_expire' => 30		// page can be cached up to this amount of MINUTES
 *       	);
 *
 *	This global variable is used by Seria Platform to send the correct headers, and to start only the required
 *	subsystems.
 *
 *	@package SeriaPlatform
 */

// Debugger
function seria_debugger()
{
	declare(ticks=1);
	$GLOBALS['seria']['debug_ticker'] = array();
	register_tick_function('seria_debugger_tick');
}
function seria_debugger_tick()
{
	if(!isset($GLOBALS['seria']['debug_ticker']['fp']))
	{ // open a file
		if(!defined('SERIA_LOG_ROOT')) return;
		$GLOBALS['seria']['debug_ticker']['fp'] = fopen(SERIA_LOG_ROOT.'/seria_debugger.txt', 'w');
	}
	$stack = debug_backtrace();
	fwrite($GLOBALS['seria']['debug_ticker']['fp'], 'File: "'.$stack[0]['file'].'" Line: '.$stack[0]['line']."\n");
}
function seria_debugger_notice($msg)
{
	if(!isset($GLOBALS['seria']['debug_ticker']['fp'])) return;
	$stack = debug_backtrace();
	fwrite($GLOBALS['seria']['debug_ticker']['fp'], 'File: "'.$stack[0]['file'].'" Line: '.$stack[0]['line']."$msg\n");
}

/**
 *	Set default values for $seria_options
 */
if(!isset($seria_options)) $seria_options = array();
if(!isset($seria_options['skip_authentication'])) $seria_options['skip_authentication'] = false;
if(!isset($seria_options['skip_session'])) $seria_options['skip_session'] = false;
if(!isset($seria_options['cache_expire'])) $seria_options['cache_expire'] = 0;

/**
 *	Load compatability layers for older versions of PHP, if the current version of PHP is older than
 *	PHP version 5.3
 */
$dirname = dirname(__FILE__);
if(version_compare(PHP_VERSION, '5.2.5', '<')) { require($dirname.'/platform/compatability/php-5.2.5.php'); }
if(version_compare(PHP_VERSION, '5.3', '<')) { require($dirname.'/platform/compatability/php-5.3.php'); }

/**
 *	Load the configuration file for Seria Platform
 */

if(file_exists($dirname.'/../_config.multisite.php'))
{
	require_once($dirname.'/platform/classes/SERIA_Base.class.php');
	require_once($dirname.'/platform/classes/SERIA_DB.class.php');
	require_once($dirname.'/platform/classes/SERIA_Exception.class.php');
	include($dirname.'/../_config.multisite.php');
	require($dirname.'/includes/multisite.php');
}
else
{
	if(!include($dirname.'/../_config.php'))
		die("$dirname/../_config.php not found.");
}

/**
 *	For constants not defined in the configuration file, config_defaults.php sets default values.
 */
require($dirname.'/platform/config_defaults.php');

/**
 * 	When a page is generated through a proxy server, the proxy server usually adds special headers so that
 *	we can see the user on the other side of the proxy.
 */
if(SERIA_COMPATIBILITY < 3) {
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$_SERVER['HTTP_X_FORWARDED_BY'] = $_SERVER['REMOTE_ADDR'];
		$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	if(isset($_SERVER['HTTP_X_FORWARDED_HOST']))
	{
	        $_SERVER['HTTP_X_FORWARDED_FROM'] = $_SERVER['HTTP_HOST'];
	        $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
	}
}

/**
 *	Seria Platform requires PHP 5.3 or newer
 */
if(SERIA_COMPATIBILITY >= 3 && version_compare(PHP_VERSION, '5.3', '<')) {
	die("Seria Platform requires PHP version 5.3 or newer");
}


/**
 *	The SERIA_Base and SERIA_Hooks classes are used before the autoloader is defined.
 */
require_once(SERIA_ROOT."/seria/platform/classes/SERIA_Base.class.php");
require_once(SERIA_ROOT."/seria/platform/classes/SERIA_Hooks.class.php");
if(SERIA_DEBUG) {
	SERIA_Base::debug('main.php called with uri: '.$_SERVER['REQUEST_URI']);
	SERIA_Base::debug('Autoloading select classes');
}
//require(SERIA_ROOT."/seria/platform/classes/SERIA_NamedObject.class.php");
//require(SERIA_ROOT."/seria/platform/classes/SERIA_EventDispatcher.class.php");
//require(SERIA_ROOT."/seria/platform/classes/SERIA_Application.class.php");
//require(SERIA_ROOT."/seria/platform/classes/SERIA_Applications.class.php");

/**
 *	SERIA_Base handles errors, so we add this as early as possible
 */
set_exception_handler(array("SERIA_Base","exceptionHandler"));
set_error_handler(array("SERIA_Base","errorHandler"), E_ALL);

/**
 *	Special global variable where Seria Platform can store dynamic information
 *	without naming conflicts with other code.
 *	@global array $GLOBALS['seria']
 *	@name $seria
 */
$GLOBALS['seria'] = array(
	'microtime' => microtime(true),
	'classpaths' => array(
		SERIA_ROOT.'/seria/platform/classes/*.class.php',
		// use SERIA_Base::addClassPath('path/to/*.class.php') to add more classpaths.
	),
	'classes' => array(),
);

/**
 *	Platform specific configuration values are interpreted here
 */

if(defined('SERIA_CACHE_BACKEND')) // The SERIA_Cache class location is defined here
{
	$GLOBALS['seria']['classes']['SERIA_Cache'] = 'platform/classes/SERIA_Cache.'.SERIA_CACHE_BACKEND.'.class.php';
}

/**
 *	Deprecated
 *
 *	SERIA_ActiveRecord may pregenerate classes. This adds these classes to the autoloader.
 */
if(SERIA_COMPATIBILITY < 3) {
	if (!SERIA_DEBUG && !SERIA_INSTALL) {
		SERIA_Base::addClassPath(SERIA_DYNAMICCLASS_ROOT . '/*.activerecord.php');
	}
}

/**
 *	Where to search for classes. Modules might add more classpaths. * identifies the classname part.
 */
if(file_exists(SERIA_ROOT.'/classes'))
	$GLOBALS['seria']['classpaths'] = array_merge(array(SERIA_ROOT.'/classes/*.class.php'), $GLOBALS['seria']['classpaths']);

/**
 * 	Report all errors except E_NOTICE (in case php.ini has non-standard configuration.
 */
if(SERIA_DEBUG)
{
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', 1);
}


$GLOBALS['seria']['classmtimes'] = array();
/**
 * Autoloading of classes according to different rules; first looks in SERIA_ROOT/classes,
 * then in SERIA_ROOT/seria/platform/classes, finally SERIA_ActiveRecordInterfaceHandler::autoloadHandler
 * handles autoloading if the class file was not found first. This autoloader also check trought $GLOBALS['seria']['classpaths']
 * for other classes.
 *
 * @param string $class
 * @return boolean
 */
function seria_autoload($class) {
	if(SERIA_DEBUG) SERIA_Base::debug('seria_autoload('.$class.')');
	if(isset($GLOBALS["seria"]["classes"][$class]))
	{
		$GLOBALS['seria']['classmtimes'][$class] = filemtime(SERIA_ROOT.'/seria/'.$GLOBALS["seria"]["classes"][$class]);
		$result = require(SERIA_ROOT.'/seria/'.$GLOBALS["seria"]["classes"][$class]);
		SERIA_Hooks::dispatch('seria_autoload', $class, $filename, $result);
		return $result;
	}

	foreach($GLOBALS["seria"]["classpaths"] as $path) {
		if(file_exists($filename = str_replace("*", $class, $path))) {
			seria_debugger_notice('Autoloading "'.$filename.'"');
			$GLOBALS['seria']['classmtimes'][$class] = filemtime($filename);
			$result = require($filename);
			SERIA_Hooks::dispatch('seria_autoload', $class, $filename, $result);
			return $result;
		}
	}

	// deprecated classes special case
	if(SERIA_COMPATIBILITY < 3) {
		if(file_exists($filename = SERIA_ROOT.'/seria/platform/classes/deprecated/'.$class.'.class.php')) {
			SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::WARNING, _t('Using deprecated class %CLASS%. If the system is updated, the class might become unavailable.', array('CLASS' => $class)));
			$GLOBALS['seria']['classmtimes'][$class] = filemtime($filename);
			return require($filename);
		}
	}

	// Widgets (6 => constant length of word "Widget")
	if(SERIA_COMPATIBILITY < 3) {
		if (($str = substr($class, strlen($class) - 6, 6)) == 'Widget') {
			$name = substr($class, 0, strlen($class) - 6);
			list($widgetClassName, $widgetPath) = SERIA_Widget::loadWidgetClass($name);
			if ($widgetClassName == $class) {
				return true;
			} else {
				throw new SERIA_Exception('Widget ' . $name . '(' . $class . ') not found');
			}
		}
	}

	// ActiveRecord classes
	// Code is provided by activerecord interface handler.
	// "if" expression is to prevent __autoload call loop if
	// SERIA_ActiveRecordInterfaceHandler is not available (it should be
	// available all time)
	if(SERIA_COMPATIBILITY < 3) {
		if ($class != 'SERIA_ActiveRecordInterfaceHandler') {
			try {
				return SERIA_ActiveRecordInterfaceHandler::autoloadHandler($class);
			} catch (Exception $exception) {
				if (SERIA_DEBUG) {
					SERIA_Base::debug('Unable to load Active Record class ' . $class . ': ' . $exception->getMessage());
				}
				throw $exception;
			}
		}
	}
	return false;
}
spl_autoload_register('seria_autoload');


/**
 *	Sometimes PHP configurations use automatically quoting of all in-data. This prevents database
 *	injection attacks. However, since this also can lead to poor progrmming style, we have decided
 *	to always remove injection.
 */
if(SERIA_COMPATIBILITY >= 3) {
	if(function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
		die("Seria Platform does not support magic quotes");
	}
} else if(function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
	if(SERIA_DEBUG) SERIA_Base::debug('<strong>Fallback for GET/POST/COOKIE in effect. Please do not use magic quotes - fake security.</strong>');
	require_once(SERIA_ROOT."/seria/platform/compatability/gpc.php");
}

/**
 *	Includes for a few things that could have been in main.php but is separated for readability.
 */
require(SERIA_ROOT . '/seria/includes/hooks.php');
if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria/includes/hooks.php)');
require(SERIA_ROOT . '/seria/includes/locale.php');
if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria/includes/locale.php)');
require(SERIA_ROOT . '/seria/includes/session.php');
if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria/includes/session.php)');
require(SERIA_ROOT . '/seria/includes/coreComponents.php');
if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria/includes/coreComponents.php)');
require(SERIA_ROOT . '/seria/includes/userComponents.php');
if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria/includes/userComponents.php)');
require(SERIA_ROOT . '/seria/includes/userApplications.php');
if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria/includes/userApplications.php)');
if(file_exists(SERIA_ROOT.'/seria.php'))
{
	require(SERIA_ROOT.'/seria.php');
	if(SERIA_DEBUG) SERIA_Base::debug('main.php:require('.SERIA_ROOT . '/seria.php)');
}



/**
 *	Whenever main.php is included, the SERIA_Template outputHandler will attempt to parse the HTML-
 *	code and inject a copyright tag in the head and more.
 */
//ob_start();
if(SERIA_COMPATIBILITY < 3) {
	ob_start(array('SERIA_Template','outputHandler'));
}

if(SERIA_COMPATIBILITY < 3) {
/**
 * Set javascript variables that may be used by libraries on the client side
 */
	$platformVars = array(
		'HTTP_ROOT' => SERIA_HTTP_ROOT,
		'HTTP_CACHED_ROOT' => SERIA_CACHED_HTTP_ROOT,
		'SERVER_TIMESTAMP' => time(),
		'SESSION_NAME' => session_name(),
		'SESSION_ID' => session_id(),
		'HTTP_REFERER' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '')
	);
	if (!$seria_options['skip_authentication'] && !$seria_options['skip_session']) {
		$platformVars['IS_LOGGED_IN'] = (SERIA_Base::isLoggedIn()?true:false);
		$platformVars['USER_ID'] = (SERIA_Base::user() ? SERIA_Base::user()->get('id') : false);
	} else {
		$platformVars['IS_LOGGED_IN'] = false;
		$platformVars['USER_ID'] = false;
	}
/**
 *	Hook: platform_js_vars
 *
 *	Dispatches the platform_js_vars hook, allowing others to add or modify variables in the SERIA_VARS javascript
 *	variable. The $platformVars variable is passed by reference, so that you can modify it directly from your
 *	hook listener.
 */
	SERIA_Hooks::dispatch('platform_js_vars', $platformVars);
	$platformVarsScript = '<script type="text/javascript">var SERIA_VARS = '.SERIA_Lib::toJSON($platformVars).'</script>';
	SERIA_Template::headPrepend('VARS', $platformVarsScript);
}

/**
 * In case maintain.php is not executed, this extra check is performed:
 * approximately every 100 hits we check if it is more than two minutes since
 * maintain.php was executed. If it was not, we will call it by opening a socket
 * to the server and issuing a GET-request. It will be closed immediately, so that
 * this page request does not take too long to complete.
 *
 * In install or debug mode it is run every approximately 5 hits.
 */
function force_maintain_now()
{
	static $maintained = false;
	if($maintained)
		return;
	$maintained = true;

	register_shutdown_function(create_function('','
		$url = parse_url(SERIA_HTTP_ROOT."/seria/platform/maintain.php");
		$s = fsockopen($_SERVER["SERVER_ADDR"], $_SERVER["SERVER_PORT"], $eNum, $eStr, 1);
		if($s)
		{
			SERIA_Base::setParam("maintain_last_run", time());
			@fwrite($s, "GET ".$url["path"]." HTTP/1.1\r\nHost: ".$url["host"]."\r\nConnection: close\r\n\r\n");
			@fclose($s);
		}
	'));
}

/**
 * Help with installation. If the file SERIA_PRIV_ROOT/SERIA_PLATFORM.php exists, it will be included by config_defaults.php and this file will
 * set some variables in $GLOBALS['seria_install']. If $GLOBALS['seria_install'] is not set, then include the install script here.
 */
if(SERIA_COMPATIBILITY < 3) {
	if(!isset($GLOBALS['seria_install']))
	{
		require_once(SERIA_ROOT.'/seria/includes/install.php');
		die();
	}
}

if(!SERIA_AUTOMAINTAIN_DISABLED && ((((SERIA_INSTALL||SERIA_DEBUG) && mt_rand(0,4)==0) || (mt_rand(0,99)==0)) && basename($_SERVER['SCRIPT_NAME'])!=='maintain.php'))
{
	$lastRun = SERIA_Base::getParam('maintain_last_run');
	if($lastRun < time()-120)
	{ // this will never happen if crontab is properly running every minute
		force_maintain_now();
	}
}

/**
 * Send header about Seria Platform, and hide information about system
 */
header('X-Powered-By: SeriaPlatform/1.0');
header('Server: SeriaPlatform/1.0');

SERIA_Hooks::dispatch(SERIA_PLATFORM_BOOT_COMPLETE_HOOK);

/**
*	Find current script path relative to the configured SERIA_HTTP_ROOT
*/
// seria platform http root url info
$seria_spui = parse_url(rtrim(SERIA_HTTP_ROOT, '/'));

if(empty($seria_spui['path']))
{ // SERIA_HTTP_ROOT is configured as top level on this domain
	$seria_path = $_SERVER['SCRIPT_NAME'];
}
else
{ // SERIA_HTTP_ROOT points to a subfolder on this domain, strip the subfolder path from the current path.
	if(strpos($_SERVER['SCRIPT_NAME'], $seria_spui['path'])===0)
	{
		$seria_path = substr($_SERVER['SCRIPT_NAME'], strlen($seria_spui['path']));
	}
	else
	{ // this script is outside of the configured SERIA_HTTP_ROOT!
		$seria_path = false;
	}
}

if(strtolower($seria_path) === '/index.php' && isset($_GET['route']))
{ // Generate this page trough the router
	// tell all applications and components to setup their routes
	$router = SERIA_Router::instance();
	// try to resolve routes
	try {
		list($callback, $variables) = $router->resolve(trim($_GET['route'], "/\r\n\t ")); // keep this change
		if(is_callable($callback))
		{
			call_user_func($callback, $variables);
		}
		else if($callback===NULL)
		{
			throw new SERIA_Exception('Not found', SERIA_Exception::NOT_FOUND);
		}
		else
		{
			if(SERIA_DEBUG)
				throw new SERIA_Exception('Controller "'.implode("::", $callback).'"not found', SERIA_Exception::NOT_FOUND);
			else
				throw new SERIA_Exception('Not found', SERIA_Exception::NOT_FOUND);
		}
	}
	catch (SERIA_Exception $e)
	{
		if($e->getCode() == SERIA_Exception::NOT_FOUND || $e->getCode() == SERIA_Router::INVALID_ROUTE)
		{
			SERIA_Hooks::dispatchToFirst(SERIA_PlatformHooks::ROUTER_FAILED, trim($_GET['route'], "/\r\n\t ")); // and keep this

			list($callback, $variables) = $router->resolve('errors/404');
			call_user_func($callback, $variables);
		}
		else throw $e;
	}
}
