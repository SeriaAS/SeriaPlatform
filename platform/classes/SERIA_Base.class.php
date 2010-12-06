<?php
	class SERIA_Base
	{
		/* HOOKS */
		const LOGIN_HOOK = 'beforeLogin';
		const AFTER_LOGIN_HOOK = 'loggedIn';
		const LOGOUT_HOOK = 'logout';
		const AFTER_LOGOUT_HOOK = 'loggedOut';
		const INSTALL_HOOK = 'SeriaPlatformInstallation';	// accepts one parameter which is the log array. This log must be modified by adding an array. Check /seria/include/install.php for structure.
		const DEBUG_HOOK = 'SERIA_Base::debug';			// Dispatches a shook for each debug message. Parameters ($timestamp, $message).
		const REDIRECT_DEBUG_HOOK = 'SERIA_Base::redirectDebug'; // Dispatches if SERIA_DEBUG and SERIA_REDIRECT_DEBUG evaluates true.
		const DISPLAY_ERROR_HOOK = 'SERIA_Base::displayErrorPage';

		/* THESE HOOKS ARE DEPRECATED! THEY DO NOT HAVE THE REQUIRED _HOOK SUFFIX. UPDATE YOUR CODE!!! */
		const BEFORE_LOGIN = 'beforeLogin';		// DEPRECATED, DELETE AFTER 1st of july 2010
		const LOGGED_IN = 'loggedIn';			// DEPRECATED, DELETE AFTER 1st of july 2010
		const LOGOUT = 'logout';			// DEPRECATED, DELETE AFTER 1st of july 2010
		const LOGGED_OUT = 'loggedOut';			// DEPRECATED, DELETE AFTER 1st of july 2010

		static $requestError = false;
		static $requestErrorTrace = false;
		static $isElevated = 0;
		static $_develHelp = array();
/*
	Debug messages should not be stored here. I've been working on down-building SERIA_Base, and duplicate storage of debug messages is memory hog - even if only for debug mode. The debug hook should
	give you the information you need.

		protected static $debugMessages = array();
*/

		protected static $_help = array();
		/**
		*       When in debug mode, help texts can be provided by special features used when generating the page.
		*       Add them using this function.
		*	@param string $key		Unique identifier to prevent duplicates
		*	@param string $title		The name of this help chapter
		*	@param string $html		HTML ready to display to developer
		*/
		static function develHelp($key, $title, $html)
		{
		        if(!SERIA_DEBUG) return;
		        self::$_help[] = array('title' => $title, 'html' => $html);
		}

		static function getDevelHelp()
		{
			return self::$_help;
		}

		static function displayErrorPage($httpErrorCode, $title=false, $message=false, $die=true, $extraHTML=false)
		{
			static $enableSafeMode = false; /* Disable all unneccesary things at recursion or subsequent calls */
			$safeMode = $enableSafeMode;
			$enableSafeMode = true;

			$title || $title = _t('An error occurred');
			$message || $message = _t('There was a problem handling your request. Please contact the site administrator.');
			if(class_exists('SERIA_ProxyServer')) // must work before autoloader comes into action
				SERIA_ProxyServer::noCache();

			try {
				if (!$safeMode && class_exists('SERIA_Hooks'))
					SERIA_Hooks::dispatch(SERIA_Base::DISPLAY_ERROR_HOOK, $httpErrorCode, $title, $message, $die, $extraHTML);
			} catch (Exception $e) {
			}

			if(!class_exists("SERIA_Gui"))
			{
				$c = "<h1>Early error encountered</h1>
				<h2>".$title."</h2>
				<div>".$message."</div>";

				if($die) {
					header('HTTP/1.1 '.$httpErrorCode.' '.$title);
					echo $c;
					die();
				} else {
					echo $c;
				}
			}
			else
			{
			        $gui = new SERIA_Gui($title);
				$gui->activeMenuItem('controlpanel');
				$gui->topMenu('Reload', "location.href=location.href;");
				$c = "<h1 class='legend'>$title</h1><div class='flashMessages'><p class='flashError'>$message</p></div>";
				if($extraHTML!==false)
					$c .= $extraHTML;

			        $gui->contents($c);
				if($die) {
					header('HTTP/1.1 '.$httpErrorCode.' '.$title);
				        echo $gui->output();
					die();
				}
			}
		}

		/**
		*	Prevent the page from being cached ever - unless there is a bug in a proxy server, ofcourse.
		*/
		static function preventCaching()
		{
			SERIA_ProxyServer::noCache();
		}

/*
	Memory hog to store debug messages here
		public static function getDebugLog()
		{
			return self::$debugMessages;
		}
*/
		static function debug($message)
		{
			static $recursive = false;
			if(SERIA_DEBUG)
			{
				if (!$recursive) {
					$ts = time();
/*
	Memory hog to store debug messages twice. Dispatching a hook, like below means no need for storing debug messages here.
					self::$debugMessages[] = array(
						$ts,
						$message
					);
*/
					$recursive = true;
					if (class_exists('SERIA_Hooks')) SERIA_Hooks::dispatch(SERIA_Base::DEBUG_HOOK, $ts, $message);
					$recursive = false;
				}

//TODO: Remove this. Use SERIA_Base::DEBUG_HOOK, somehow.
				if(class_exists('SERIA_Template')) SERIA_Template::debugMessage($message);
			}
		}

		static function url($extra=false)
		{
			if($extra===false) $extra = array();

			$url = $_SERVER["SCRIPT_NAME"];

			$params = array();
//			$params = $_GET;
//			foreach($params as $k => $v)
//				if($extra[$k]) unset($params[$k]);

			$parts = array();
//			foreach($params as $k => $v)
//				$parts[] = rawurlencode($k)."=".rawurlencode($v);

			foreach($extra as $k => $v)
				$parts[] = rawurlencode($k)."=".rawurlencode($v);

			if(sizeof($parts)>0)
				return $url."?".implode("&", $parts);

			return $url;
		}

		static function redirectTo($url)
		{
			if (SERIA_DEBUG && defined('SERIA_REDIRECT_DEBUG') && SERIA_REDIRECT_DEBUG)
				SERIA_Hooks::dispatchToFirst(SERIA_Base::REDIRECT_DEBUG_HOOK, $url);
			if(strpos($url, "#")!==false)
			{
				SERIA_Template::disable();
				while(ob_end_clean());
				die("<html><head></head><body><script type='text/javascript'>top.location.href=\"".str_replace('"','\"',$url)."\";</script></body></html>");
			}
			else
			{
				if ((strpos($url, '://') === false) && ($url[0] == '/')) {
					$url = SERIA_HTTP_ROOT . $url;
				}
				header("Location: ".$url);
				die();
			}
		}

		static function pageRequires($f)
		{
			if(!file_exists(SERIA_ROOT."/seria/platform/page_requires/$f.php"))
				throw new SERIA_Exception("Unknown page requirement '$f'.");
			require_once(SERIA_ROOT."/seria/platform/page_requires/$f.php");
		}

		static function page($gui, $f, $params=array())
		{
			if(!file_exists(SERIA_ROOT."/seria/platform/pages/$f.php"))
				throw new SERIA_Exception("Unknown pages '$f'.");
			require_once(SERIA_ROOT."/seria/platform/pages/$f.php");
		}

		static function db($setDB=false)
		{
			static $db = false;

			if($db === false)
			{
				$db = new SERIA_DB(SERIA_DB_DSN, SERIA_DB_USER, SERIA_DB_PASSWORD);

				// start a transaction if updates require so
				$db->beginTransaction(true);
			}

			return $db;
		}

		static function closeDB()
		{
			try {
				if(SERIA_Base::$requestError===false)
				{ // no unhandled errors happened, so we can commit to the database
					try {
						SERIA_Base::db()->commit();
					} catch (Exception $e) {
						SERIA_Base::debug('SERIA_Base::closeDB(): Commit failed: '.$e->getMessage());
					}
				}
				else
				{
					try
					{
						if(is_object(SERIA_Base::$requestError) && is_a(SERIA_Base::$requestError, 'Exception'))
							$message = SERIA_Base::$requestError->getMessage();
						else
							$message = SERIA_Base::$requestError;
						if (SERIA_Base::$requestErrorTrace)
							$trace = SERIA_Base::$requestErrorTrace;
						else
							$trace = '';

						if(SERIA_ERROR_EMAIL && (!SERIA_DEBUG && !SERIA_INSTALL))
						{
							try {
								mail(SERIA_ERROR_EMAIL, "Seria Platform (".SERIA_HTTP_ROOT.") error", "Error environment:
						
	URL: http".(isset($_SERVER["HTTPS"])?"s":"")."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."
	REFERRER: ".$_SERVER["HTTP_REFERER"]."
	POST-DATA: ".var_export($_POST, true)."
	SESSION-DATA: ".var_export($_POST, true)."
	COOKIE-DATA: ".var_export($_COOKIE, true)."
	
	Errormessage:
	
$message

	Backtrace:

$trace");
							} catch (Exception $e) { 
								echo "Unable to send e-mail error report: ".$e->getMessage()."<br>\n".SERIA_Base::$requestError; 
							}
						}
					} 
					catch (Exception $e) 
					{
						SERIA_Base::debug('Error reporting failed: '.$e->getMessage());
					}
					SERIA_Base::db()->rollBack();
				}
			} catch (Exception $exception) {
				if (SERIA_DEBUG) {
					echo $exception->__tostring();
				}
			}
		}

		static function exceptionHandler($e)
		{ // unhandled exception
			SERIA_Base::$requestError = $e;
			try {
				SERIA_Base::$requestErrorTrace = $e->getTraceAsString();
			} catch (Exception $e) {
				SERIA_Base::$requestErrorTrace = 'No backtrace available.';
			}
			

			static $debugIndex=0;
			SERIA_Base::debug("UNHANDLED EXCEPTION ".$e->getMessage()."(".$e->getCode().")");
		        if(SERIA_DEBUG || SERIA_INSTALL)
		        {
		                SERIA_Template::debugMessage('<span onclick="jQuery(\'#debugMessage'.$debugIndex.'\').slideDown(400);">+</span><strong>'.$e->getMessage().'</strong><div id="debugMessage'.$debugIndex.'" style="display: none; padding: 20px;">Line: <strong>'.$e->getLine().'</strong> File: <strong>'.$e->getFile().'</strong>'.$e->getTraceAsString().'</div>');
				$debugIndex++;
		        }


			if(SERIA_DEBUG || SERIA_INSTALL || (SERIA_Base::user() && SERIA_Base::user()->isAdministrator()))
			{
				switch(get_class($e))
				{
					case "SERIA_ValidationException" :
						$v = '<table class="grid"><thead><tr><th style="width:150px;">'._t('Field').'</th><th>'._t('Message').'</th></tr></thead><tbody>';
						$errors = $e->getValidationErrors();
						foreach($errors as $field => $message)
							$v .= '<tr><td>'.htmlspecialchars($field).'</td><td>'.$message.'</td></tr>';
						$v .= '</tbody></table>';
						SERIA_Base::displayErrorPage('400', 'Validation error', $e->getMessage(), true, $v);
						break;

					case "PDOException" :
						if($e->getCode() === '42S02')
						{
							SERIA_Base::displayErrorPage('503', 'Database table not found', 'A database table was not found. This error occurs when the the maintain.php script has not been run after a fresh install or after upgrading the Seria Platform.');
						}
						else if($e->getCode() === 'HY000' && strpos($e->getMessage(), 'Errcode: 28')!==false)
						{
							SERIA_Base::displayErrorPage('503', 'Unable to write to database', 'Unable to write to the database.');
						}
					default :
						SERIA_Base::displayErrorPage('500', 'Unhandled error/exception',"<strong>ERROR: ".get_class($e)."(".$e->getMessage().") in (".$e->getFile().":".$e->getLine().")</strong><br><br><strong>Request URI: ".$_SERVER["REQUEST_URI"]."</strong><br><br><i>".nl2br($e->getTraceAsString())."</i><br>");
						break;
				}
			}
			else
			{
				SERIA_Base::displayErrorPage('500', 'An error was encountered', 'No further description is available');
			}
		}

		private static $errorHandlerMode = 'errorPage';
		static function errorHandler($errno, $errstr, $errfile, $errline)
		{
			switch($errno) {
				case E_NOTICE :
				case E_WARNING :
				case E_USER_WARNING :
				case E_USER_NOTICE : 
					if (defined('SERIA_DEBUG') && SERIA_DEBUG) {
						if(class_exists('SERIA_Template'))
							SERIA_Template::debugMessage("<strong>PHP NOTICE/WARNING: ".$errstr." on line ".$errline." in ".$errfile."</strong>");
						return true;
					}
					break;
				default :
					switch (self::$errorHandlerMode) {
						case 'errorPage':
							try {
								ob_start();
								debug_print_backtrace();
								SERIA_Base::$requestErrorTrace = ob_get_clean();
							} catch (Exception $e) {
								SERIA_Base::$requestErrorTrace = 'No backtrace available.';
							}
							SERIA_Base::$requestError = true;
							SERIA_Base::displayErrorPage('503', 'Error handled',"$errstr $errno $errfile $errline");
							break;
						case 'exception':
							throw new SERIA_Exception('Error: '.$errstr.' ('.$errno.') at '.$errfile.':'.$errline);
					}
					break;
			}
		}

		/**
		 * Set the error handler mode to either: show error page or throw exception. 
		 * @param string $mode Either "errorPage" (default) or "exception" 
		 * @return string
		 */
		public static function setErrorHandlerMode($mode)
		{
			switch ($mode) {
				case 'errorPage':
				case 'exception':
					$retv = self::$errorHandlerMode;
					self::$errorHandlerMode = $mode;
					return $retv;
				default:
					throw new SERIA_Exception('Invalid argument.');
			}
		}
                
		static function hasRight($rightName)
		{
			if(($user = SERIA_Base::user()) && ($user->hasRight($rightName) || self::isAdministrator()))
				return true;

			if (self::isElevated())
				return true;

			return false;
		}

		/**
		 * @param boolean $temporaryGrant 
		 * 	If true, then the next call to isAdministrator will return true
		 */
		static function isAdministrator($temporaryGrant=false)
		{
			if($temporaryGrant)
				throw new SERIA_Exception('Temporary granting of administrator privileges have been disabled. Use SERIA_Base::elevateUser($callback).');
			if ($user = SERIA_Base::user()) {
				return $user->isAdministrator();
			}

			
			return false;
		}

		static function isGuest()
		{
			if($user = SERIA_Base::user()) {
				return $user->isGuest();
			}

			return false;
		}

		/**
		 * @return true if the user has temporarily elevated privileges.
		 */
		static function isElevated()
		{
			return SERIA_Base::$isElevated > 0 ? true : false;
		}

		/**
		 * @param $callback The function to call with elevated privileges.
		 * @param[] $args Optional function arguments.
		 */
		static function elevateUser()
		{
			$args = func_get_args();
			$callback = array_shift($args);
			SERIA_Base::$isElevated++;
			$result = call_user_func_array($callback, $args);
			SERIA_Base::$isElevated--;
			return $result;
		}

		static function site()
		{
			static $site = false;
			if($site !== false)
				return $site;

			$site = new SERIA_Site();
		}

		/**
		 * This method blocks access to seria/* administration pages for the eventually
		 * logged in user. Call this before logging the user in for blocking access.
		 */
		public static function blockSystemAccess($blocking=true)
		{
			if ($blocking) {
				if (!session_id())
					session_start();
				SERIA_Base::debug('Blocking system access.');
				$_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED'] = true;
			} else {
				SERIA_Base::debug('Removing system access blocking.');
				$_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED'] = false;
			}
		}
		/**
		 * Returns whether the user has access to system features (seria/*).
		 *
		 * @return unknown_type
		 */
		public static function hasSystemAccess()
		{
			if (self::isLoggedIn() && (!isset($_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED']) || !$_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED'])) {
				$user = SERIA_Base::user();
				if (!$user)
					return false;
				return ($user->get('guestAccount') == 0 ? true : false);
			}
			return false;
		}

		/**
		 * @param $setUser
		 *	SERIA_User object: logs the user in.
		 *	NULL: logs the user out.
		 *	false: returns the currently logged in user.
		 * @return SERIA_User
		 * @return boolean
		 */
		static function user($setUser=false)
		{
			static $user = false;

			SERIA_Hooks::dispatch('SERIA_Base::user', $setUser);

			if(isset($seria_options["skip_session"]))
				throw new SERIA_Exception('Session was skipped trough $seria_options');

			if($setUser===false)
			{ // return the currently logged in user
				if(!session_id()) // no session started, thus no user is logged in
				{
					return false;
				}
				if($user === false && isset($_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX])) // fetch the user object and cache it
				{
					try
					{
						$user = SERIA_Fluent::load('SERIA_User', $_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX]);
					}
					catch (SERIA_NotFoundException $e)
					{
						unset($_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX]);
					}
					catch (PDOException $e)
					{
						if(isset($_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX]))
						{
							unset($_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX]);
							return false;
						}
						throw $e;
					}
				}
				return $user;
			}
			else if($setUser===NULL)
			{ // log out the current user
				if($prevuser = SERIA_Base::user())
				{ // a user is logged in
					SERIA_Hooks::dispatch(SERIA_Base::LOGOUT, $prevuser);
					$user = false; /* Clear login */
					unset($_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX]);
					if (isset($_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED']))
						unset($_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED']);
					SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::NOTICE, _t('%USER%@%IP%: Logout.', array('USER' => $prevuser->get('username'), 'IP' => $_SERVER['REMOTE_ADDR'])), 'security');
					SERIA_Hooks::dispatch(SERIA_Base::LOGGED_OUT, $prevuser);
					return true;
				}
				else
				{ // no user is logged in
					return false;
				}
			}
			else if(is_a($setUser, 'SERIA_User'))
			{ // setting the current user
				if(!session_id())
					session_start();
				SERIA_Hooks::dispatch(SERIA_Base::BEFORE_LOGIN, $setUser);
				if ($setUser->get('guestAccount'))
					$_SESSION['USER_LOGIN_SYSTEM_ACCESS_BLOCKED'] = true;
				$user = $setUser;
				$_SESSION[SERIA_PREFIX.'_USERID'.SERIA_SESSION_SUFFIX] = $user->get('id');
				SERIA_SystemStatus::publishMessage(SERIA_SystemStatus::NOTICE, _t('%USER%@%IP%: Login.', array('USER' => $user->get('username'), 'IP' => $_SERVER['REMOTE_ADDR'])), 'security');
				SERIA_Hooks::dispatch(SERIA_Base::LOGGED_IN, $user);
				return true;
			}
			throw new Exception('Illegal argument.');
		}

		static function userId()
		{
			$u = self::user();
			if($u) return $u->get('id');
			return false;
		}

		static function isLoggedIn()
		{
			return SERIA_Base::user() ? true : false;
		}

		/**
		*	This methods fetches a value from the database and works at all times, as long as the database have been correctly configured.
		*	Performance is very important for this function, so it should use memcached directly if configured.
		*
		*	@param string $name
		*	@return string
		*/
		static function getParam($name)
		{
			try {
				$res = SERIA_Base::db()->query($sql = "SELECT value FROM {params} WHERE name=:name", array('name' => $name))->fetch(PDO::FETCH_COLUMN, 0);
				return (string) $res;
			} catch (PDOException $e) {
				if ($e->getCode() == '42S02')
				{ // table does not exist, so no value can exist - return NULL
					return NULL;
				}
				throw $e;
			}
		}

		/**
		*	Delete or unset a param
		*	@param string $name
		*	@return boolean
		*/
		static function unsetParam($name)
		{
			try {
				return SERIA_Base::db()->exec('DELETE FROM {params} WHERE name=:name', array('name' => $name), true);
			}
			catch (PDOException $e)
			{
				if($e->getCode() == '42S02')
					return false;
				throw $e;
			}
		}

		/**
		*	Saves a value to the database persistently. It works at all times, also before the platform have been properly installed,
		*	as long as the database exists.
		*
		*	@param string $name
		*	@param string $value
		*	@return boolean
		*/
		static function setParam($name, $value)
		{
			$value = (string) $value;
			SERIA_Base::debug('SERIA_Base::setParam('.$name.','.substr($value,0,50).')');
			$sql = "INSERT INTO {params} (name, value) VALUES (:name, :value) ON DUPLICATE KEY UPDATE value=:value2";
			$data = array('name' => $name, 'value' => $value, 'value2' => $value);
			try {
				$res = (SERIA_Base::db()->exec($sql, $data, true) ? true : false);
/*
echo $sql;
var_dump($data);
echo "SETPARAM $name:";
var_dump($res);
echo "<br>";
*/
				return $res;
			} catch (PDOException $e) {
				if($e->getCode() == '42S02')
				{ // the table does not exist, create it
					self::db()->exec('CREATE TABLE {params} (name VARCHAR(100) PRIMARY KEY, value TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8');
					return (SERIA_Base::db()->exec($sql, $data, true) ? true : false);
				}
				throw $e;
			}
		}

		/**
		 * Set a param if the param has not been set before.
		 *
		 * @param string $name
		 * @param string $value
		 * @return boolean
		 */
		static function insertParam($name, $value)
		{
			$value = (string) $value;
			$sql = "INSERT INTO {params} (name, value) VALUES (:name, :value)";
			$data = array('name' => $name, 'value' => $value);
			try {
				return (SERIA_Base::db()->exec($sql, $data, true) ? true : false);
			} catch (PDOException $e) {
				if($e->getCode() == '42S02')
				{ // the table does not exist, create it
					self::db()->exec('CREATE TABLE {params} (name VARCHAR(100) PRIMARY KEY, value TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8');
					return (SERIA_Base::db()->exec($sql, $data, true) ? true : false);
				}
				else if($e->getCode() == '23000')
				{
					return false;
				}
				throw $e;
			}
		}

		/**
		*	Deprecated. See SERIA_Base::insertParam()
		*/
		static function setParamIfNotExists($name, $value)
		{
			SERIA_Base::debug('Using SERIA_Base::setParamIfNotExists(), which is deprecated in favor of SERIA_Base::insertParam()');
			return self::insertParam($name, $value);
		}

		/**
		 * Overwrite a param if it is equal to a specific value
		 *
		 * @param string $name
		 * @param string $value
		 * @param string $oldValue
		 * @return boolean
		 */
		static function replaceParam($name, $value, $oldValue)
		{
			$value = (string) $value;
			$oldValue = (string) $oldValue;
			$sql = 'UPDATE {params} SET value = :value WHERE name = :name AND value = :compare';
			$data = array('name' => $name, 'value' => $value, 'compare' => $oldValue);
			try {
				return (SERIA_Base::db()->exec($sql, $data, true) ? true : false);
			} catch (PDOException $e) {
				if($e->getCode() == '42S02')
				{ // the table does not exist, create it
					self::db()->exec('CREATE TABLE {params} (name VARCHAR(100) PRIMARY KEY, value TEXT) ENGINE=InnoDB DEFAULT CHARSET=utf8');
					return (SERIA_Base::db()->exec($sql, $data, true) ? true : false);
				}
				throw $e;
			}
		}

		/**
		*	Deprecated. See SERIA_Base::replaceParam()
		*/
		static function setParamIfEqualTo($name, $value, $oldValue)
		{
			SERIA_Base::debug('Using SERIA_Base::setParamIfEqualTo(), which is deprecated in favor of SERIA_Base::replaceParam()');
			return self::replaceParam($name, $value, $oldValue);
		}

		static function guid($key = '')
		{
			$maxTries = 10;
			$guid = 1 + SERIA_Base::db()->query("SELECT MAX(guid) FROM {guids}", array())->fetch(PDO::FETCH_COLUMN, 0);
			while($maxTries--)
			{
				try {
					$retv = SERIA_Base::db()->exec("INSERT INTO {guids} (guid, `key`) VALUES (:guid, :key)", array('guid' => $guid, 'key' => $key));
					if($retv)
						return $guid;
					$guid += ($maxTries>5 ? 1 : mt_rand(0, 4));
				} catch (PDOException $e) {
					$retv = 0;
				}
			}

			throw new SERIA_Exception("Unable to create a new GUID (".$key.").");
		}

		/**
		* Viewmode is used by APIs to determine which articles to return from searches and other queries.
		*
		* @param string $mode
		* 	possible values "admin", "public"
		*/
		static function viewMode($mode=false)
		{
			static $currentMode = "public";

			switch($mode)
			{
				case false : return $currentMode;
				case "admin" :
				case "public" : return $currentMode = $mode;
				default : throw new SERIA_Exception("Unsupported view mode '$mode'.");
			}
		}

		/**
		*	Add classpath to __autoload
		*/
		static function addClassPath($path, $debug=false)
		{
			$GLOBALS["seria"]["classpaths"][] = $path;
		}

		static function addEarlyClassPath($path)
		{
			array_unshift($GLOBALS['seria']['classpaths'], $path);
		}

		public static function addFramework($name)
		{
			require_once(SERIA_ROOT.'/seria/frameworks/'.$name.'/inc.php');
		}

		static function async($callback /* $args... */)
		{
			$args = func_get_args();
			call_user_func_array(array('SERIA_Async','call'), $args);
		}

		/**
		*	This menthod processes manifest classes that are declared
		*	for each component and application. It updates the database structure
		*	and logs which tables are created by which component, so that uninstallation
		*	is possible in the future.
		*/
		static function processManifests($namespace, array $classNames)
		{
			sort($classNames);
			$hash = '';
			$versions = array();
			$reflectors = array();
			foreach($classNames as $className)
			{
				$reflector = $reflectors[$className] = new ReflectionClass($className);
				$serial = $reflector->getConstant('SERIAL');

				if(!$serial)
					throw new SERIA_Exception('The manifest class "'.$className.'" does not specify the SERIAL constant as a positive integer.');

				$hash .= '('.$serial.')';
			}

			$currentHash = self::getParam('manifestversions:'.$namespace);
			if($currentHash !== $hash)
				$changed = true; // must check if the $hash have changed
			else
				$changed = false;


			if($changed)
			{
				SERIA_Base::debug('One or more "'.$namespace.'"-manifests have changed. Performing update.');

				// validate the manifest classes
				foreach($reflectors as $reflector)
				{
					// validate doc comment
					$docComment = $reflector->getDocComment();
					if($docComment===false)
						throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" does not specify a doc comment (/** */) which is required.');

					// keywords to look for in doc-comment:
					$keywords = array('@author','@version','@package');
					foreach($keywords as $keyword)
						if(strpos($docComment, $keyword)===false)
							throw new SERIA_Exception('The doc comment (/** */) for the class "'.$reflector->getName().'" does not specify the "'.$keyword.'" keyword. Must use the following keywords: '.implode(", ", $keywords));

					// validate constants
					$constants = $reflector->getConstants();
					foreach($constants as $c => $v)
					{
						// REQUIRED CONSTANT
						if($c == 'SERIAL')
						{ // hooks must end with _HOOK and contain a value identical to its token name in PHP
						}
						else if(strtoupper($c) != $c)
						{
							throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the constant "'.$c.'" which does not follow guidelines (must be uppercase).');
						}
						else if(substr($c, -5) == '_HOOK' && $v == $reflector->getName()."::".$c)
						{ // all other constants are illegal for the manifest class
						}
						else
						{
							throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the constant "'.$c.'" which does not follow guidelines (only a few constants are allowed here).');
						}
					}

					// validate methods
					$methods = $reflector->getMethods();
					foreach($methods as $method)
					{
						if($method->isStatic() && $method->isPublic())
						{ // public static methods
							if($method->getName() == 'beforeUpgrade' && $method->getNumberOfRequiredParameters()==2);
							else if($method->getName() == 'afterUpgrade' && $method->getNumberOfRequiredParameters()==2);
							else
								throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the method "'.$method->getName().'" which does not follow guidelines.');
						}
						else
							throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the method "'.$method->getName().'" which does not follow guidelines (only a few methods are allowed here).');
						$docComment = $method->getDocComment();
						if($docComment === false)
						{
							throw new SERIA_Exception('Manifest class "'.$reflector->getName().'" defines the method "'.$method->getName().'" which does not have a doc comment.');
						}
					}
				}

				/**
				*	Algorithm to prevent accessing site and concurrent updates while manifests are being processed:
				*
				*	* Try to lock manifestprocessing
				*	* If not able to lock manifestprocessing, check if more than 60 seconds since locked and try to lock manifestprocessing again
				*	* If manifestprocessing not locked, wait 1 seconds and see if it finished - die if not.
				*/
				$locked = self::insertParam('manifestprocessing', time());
				if($locked && $locked < time()-10)
				{
					$locked = self::replaceParam('manifestprocessing', time(), $locked);
				}
				if($locked)
				{
					sleep(1);
					$locked = self::getParam('manifestprocessing');
					if($locked) // still locked, die to prevent processes filling up
						self::displayErrorPage('500', 'Site is being updated', 'An update to the website is being processed, and this is taking more time than expected. Please try again by pressing the reload button.');
					else // manifests have been processed
						return;
				}

				// database connection will be required
				$db = SERIA_Base::db();

				// manifest processing must happen, even on empty databases - so we create the tables here. Should give no performance penalty, since they are not called
				// unless manifests have chagned.

				/**
				*	Record known manifests, so that we can uninstall them if the class is deleted
				*/
				$db->exec("CREATE TABLE IF NOT EXISTS {manifests} (name VARCHAR(100) PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8");
				/**
				*	Record database tables created by manifests, so that they can be deleted later, and we can detect if other manifests use the same table name
				*/
				$db->exec("CREATE TABLE IF NOT EXISTS {manifests_tables} (name VARCHAR(100) PRIMARY KEY, manifestName VARCHAR(100)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
				/**
				*	Record folders created by manifests, so that they can be deleted later
				*/
				$db->exec("CREATE TABLE IF NOT EXISTS {manifests_folders} (id INTEGER PRIMARY KEY AUTO_INCREMENT, pathName VARCHAR(100), path VARCHAR(100), manifestName VARCHAR(100)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
				/**
				*	Record updates of manifest serial numbers, so that we simply can keep track and possibly identify sources of problems
				*/
				$db->exec("CREATE TABLE IF NOT EXISTS {manifests_logs} (id INTEGER PRIMARY KEY AUTO_INCREMENT, category VARCHAR(20), title VARCHAR(100), info BLOB, manifestName VARCHAR(100), serial INTEGER, targetSerial INTEGER) ENGINE=InnoDB DEFAULT CHARSET=utf8");


				// Safety measure: make sure that manifestversions for this namespace must be updated some time, even if this fails
				self::unsetParam('manifestversions:'.$namespace);

				foreach($reflectors as $className => $reflection)
				{
					$newVersion = $reflection->getConstant('SERIAL');
					$installedVersion = self::getParam('manifest:'.$className.':serial');
					if($installedVersion===NULL) $installedVersion = 0;

					if($newVersion>$installedVersion)
					{
/**
*	Synchronize database definition
*/
						try {
							$database = $reflection->getStaticPropertyValue('database');
							// perform custom alter tables and drop tables for this manifest
							if($installedVersion>0)
							{ // do not drop or alter tables for new manifests
								for($i = $installedVersion+1; $i <= $newVersion; $i++)
								{
									if(isset($database['drops']) && isset($database['drops'][$i]))
									{ // there are drop table instructions for this version
										foreach($database['drops'][$i] as $tableName)
										{
											$owner = $db->query("SELECT * FROM {manifests_tables} WHERE name=? AND manifestName=?", array(
												$tableName, $className
											))->fetch(PDO::FETCH_ASSOC);
											if($owner)
											{
//TODO: dangerous! Consider renaming the table temporarily
												$db->exec($statement = 'DROP TABLE '.$tableName, NULL, true);
												$db->exec("DELETE FROM {manifests_tables} WHERE name=? AND manifestName=?", array(
													$tableName,
													$className
												), true);
												$db->exec('INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetSerial) VALUES (?,?,?,?)', array(
													'drop',
													'Dropped the database table "'.$tableName.'".',
													$className,
													$i,
													$newVersion,
													serialize(array('sql' => $statement)),
												), true);
											}
											else throw new SERIA_Exception('The manifest class "'.$className.'" tried to drop the table "'.$tableName.'" which it does not own!');
										}
									}
									if(isset($database['alters']) && isset($database['alters']))
									{ // there are alter table instructions for this version
										foreach($database['alters'][$i] as $statement)
										{
											$owner = $db->query("SELECT * FROM {manifests_tables} WHERE name=? AND manifestName=?", array(
												$tableName, $className
											))->fetch(PDO::FETCH_ASSOC);
											if($owner)
											{
												$db->exec($statement, NULL, false);
												$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetSerial, info) VALUES (?,?,?)", array(
													'alter',
													'Altered the database table "'.$tableName.'".',
													$className,
													$i,
													$newVersion,
													serialize(array('sql' => $statement)),
												), true);
											}
											else throw new SERIA_Exception('The manifest class "'.$className.'" tried to alter the table "'.$tableName.'" which it does not own!');
										}
									}
								}
							}
							else
							{ // this is a new manifest
								self::setParam('manifest:'.$className.':serial', $newVersion);
								$db->exec("INSERT INTO {manifests} (name) VALUES (?)", array($className));
								$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetserial) VALUES (?,?,?,?,?)", array(
									'install',
									'First installation of the "'.$className.'" manifest.', 
									$className,
									$newVersion,
									$newVersion,
								));

							}
//todo check altered table type!
							// synchronize table definitions with actual database
							if(isset($database['creates']))
							{
								foreach($database['creates'] as $create)
								{
									// create temporary table,
									preg_match_all('|{([a-zA-Z0-9_]+)}|m', $create, $matches);
									$matchCount = sizeof($matches[0]);
									if($matchCount === 0)
									{ //TODO: Parse SQL using SERIA_DB::sqlTokenize and support create table statements without {} around table names
										throw new SERIA_Exception('The create statement "'.$create.'" in the manifest class "'.$className.'" does not enclose its table names in curly brackets {} so I can\'t perform synchronization.');
									}
									else if($matchCount > 1)
									{ // Unlikely scenario
										throw new SERIA_Exception('The create statement "'.$create.'" in the manifest class "'.$className.'" contains multiple tables within curly brackets, and I do not know how to process it.');
									}
									$sql = str_replace($matches[0][0], '{tmp_manifest}', $create);
									$pos = stripos($sql, 'TABLE');
									$sql = substr($sql, 0, $pos).' TEMPORARY '.substr($sql, $pos);
									$db->exec($sql);
									// compare table with the current table,
									try {
										$specOriginal = $db->getColumnSpec($matches[0][0]);
									} catch (PDOException $e) {
										if($e->getCode()=="42S02")
										{ // table does not exist, so we accept the create statement directly
											$db->exec('DROP TABLE {tmp_manifest}');
											$db->exec($create);
											$db->exec("INSERT INTO {manifests_tables} (name, manifestName) VALUES (?,?)", array(
												$matches[0][0],
												$className,
											), true);
											$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
												'install',
												'Created table "'.$matches[0][0].'"',
												$className,
												serialize(array('sql' => $create)),
												$newVersion,
												$newVersion
											));
											continue;
										}
										throw $e;
									}
									$specNew = $db->getColumnSpec('{tmp_manifest}');

									$dropped = array();
									$added = array();
									// are all columns in original also in new?
									foreach($specOriginal as $column => $spec)
									{
										if(!isset($specNew[$column]))
										{ // column seems to be dropped, but we can't be sure yet
											$dropped[] = $column;
										}
									}
									// are all columns in new, also in original
									foreach($specNew as $column => $spec)
									{
										if(!isset($specOriginal[$column]))
										{
											$added[] = $column;
										}
									}

									// alter the current table to match the temporary table,
									if(sizeof($dropped)>0 && sizeof($added)>0)
									{ // columns may have been renamed, or dropped and added. We don't know.
										throw new SERIA_Exception('Unable to detect if changes to database schema is renaming of fields or adding and removing fields. Create alter statements for manifest class "'.$className.'".');
									}
									else if(sizeof($dropped)>0)
									{ // columns have been dropped
										foreach($dropped as $column)
										{
											$db->exec($statement = 'ALTER TABLE '.$matches[0][0].' DROP COLUMN '.$column);
											$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
												'alter',
												'Dropped column "'.$column.'" from the table "'.$matches[0][0].'"',
												$className,
												serialize(array('sql' => $statement)),
												$newVersion,
												$newVersion
											));
										}
									}
									else if(sizeof($added)>0)
									{
										foreach($added as $column)
										{
											$spec = $specNew[$column];

											$coldef = $column.' '.$spec['type'];

											if(!empty($spec['length']))
												$coldef .= '('.$spec['length'].')';
											if(!$spec['null'])
												$coldef .= ' NOT NULL';
											if($spec['default']!==NULL)
												$coldef .= ' DEFAULT '.$db->quote($spec['default']);
											else
												$coldef .= ' DEFAULT NULL';

											$db->exec($statement = 'ALTER TABLE '.$matches[0][0].' ADD COLUMN '.$coldef);
											$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
												'added',
												'Added the column "'.$column.'" to the table "'.$matches[0][0].'"',
												$className,
												serialize(array('sql' => $statement)),
												$newVersion,
												$newVersion,
											));
										}
									}

									// updated altered columns
									foreach($specNew as $column => $spec)
									{
										if(isset($specOriginal[$column]))
										{
											$identical = true;
											foreach($specOriginal[$column] as $fieldName => $fieldValue)
											{
												if(!isset($specNew[$column][$fieldName]) || $specNew[$column][$fieldName] != $fieldValue)
												{
													// NULL === NULL returns false, so we check extra for this.
													if(!($fieldName=='default' && $fieldValue===NULL && $specNew[$column][$fieldName]===NULL))
														$identical = false;
												}
											}
											if(!$identical)
											{
												$sql = 'ALTER TABLE '.$matches[0][0].' MODIFY COLUMN '.$column.' '.$spec['type'];
												if(!empty($spec['length']))
													$sql .= '('.$spec['length'].')';
												if(!$spec['null'])
													$sql .= ' NOT NULL';
												if($spec['default']!==NULL)
													$sql .= ' DEFAULT '.$db->quote($spec['default']);
												else
													$sql .= ' DEFAULT NULL';
												$db->exec($sql);
												$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
													'altered',
													'Altered column "'.$column.'" in the table "'.$matches[0][0].'"',
													$className,
													serialize(array('sql' => $sql)),
													$newVersion,
													$newVersion,
												));
											}
										}
									}

									// check if the indexes have changed
									$originalIdx = self::_manifestBuildSqlIndexStatements($db->query('SHOW INDEXES FROM '.$matches[0][0])->fetchAll(PDO::FETCH_ASSOC));
									$newIdx = self::_manifestBuildSqlIndexStatements($db->query('SHOW INDEXES FROM {tmp_manifest}')->fetchAll(PDO::FETCH_ASSOC));

									$added = array();
									$dropped = array();
									$modified = array();
									foreach($newIdx as $name => $sql)
									{
										if(!isset($originalIdx[$name]))
											$added[] = $name;
										else if($originalIdx[$name]!=$sql)
											$modified[] = $name;
									}
									foreach($originalIdx as $name => $sql)
									{
										if(!isset($newIdx[$name]))
											$dropped[] = $name;
									}

									foreach($dropped as $name)
									{
										$db->exec($sql = 'ALTER TABLE '.$matches[0][0].' DROP INDEX '.$name);
										$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
											'drop_index',
											'Dropped the index "'.$name.'" from the table "'.$matches[0][0].'"',
											$className,
											serialize(array('sql' => $sql)),
											$newVersion,
											$newVersion,
										), true);

									}

									foreach($added as $name)
									{
										$db->exec($sql = 'ALTER TABLE '.$matches[0][0].' ADD '.$newIdx[$name]);
										$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
											'added_index',
											'Added the index "'.$name.'" to the table "'.$matches[0][0].'"',
											$className,
											serialize(array('sql' => $sql)),
											$newVersion,
											$newVersion,
										), true);
									}

									foreach($modified as $name)
									{
										$db->exec('ALTER TABLE '.$matches[0][0].' DROP INDEX '.$name);
										$db->exec($statement = 'ALTER TABLE '.$matches[0][0].' ADD '.$newIdx[$name]);
										$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, info, serial, targetSerial) VALUES (?,?,?,?,?,?)", array(
											'alter_index',
											'Altered the index "'.$name.'" in the table "'.$matches[0][0].'"',
											$className,
											serialize(array('sql' => $statement)),
											$newVersion,
											$newVersion,
										), true);

									}

									// drop the temporary table
									$db->exec('DROP TABLE {tmp_manifest}');
								}
							}
							$db->exec("INSERT INTO {manifests_logs} (category, title, manifestName, serial, targetSerial) VALUES (?,?,?,?,?)", array(
								'update_manifest',
								'Synchronized the manifest for "'.$className.'"',
								$className,
								$newVersion,
								$newVersion,
							), true);
							self::debug('Updated manifest for "'.$className.'" to version '.$newVersion);
							self::setParam('manifest:'.$className.':serial', $newVersion);
						}
						catch (ReflectionException $e)
						{
						}
/**
*	Synchronize folder definitions
*/

					}
					else if($newVersion<$installedVersion)
					{
						throw new SERIA_Exception('Downgrading serial number for manifest class "'.$className.'" is not supported.');
					}
				}
				// stop others from processing manifests
				self::setParam('manifestversions:'.$namespace, $hash);
				// release lock
				self::setParam('manifestprocessing', 0);
			}
		}

		/**
		*	Accepts all rows from the mysql SHOW INDEXES FROM {table} and
		*	returns an associative array of $indexName => $indexDef for use
		*	in ALTER TABLE {table} ADD $indexDef
		*	@param array $indexDef	Array of associative rows from SHOW INDEXES FROM {table}
		*/
		protected static function _manifestBuildSqlIndexStatements(array $indexDef)
		{
// currently no support for fulltext or spatial indexes.
// no support for foreign keys, and anyway we do not encourage using them
			$result = array();

			$keyNames = array();
			foreach($indexDef as $row)
				$keyNames[$row['Key_name']] = $row['Key_name'];
			foreach($keyNames as $keyName)
			{
				if($keyName === 'PRIMARY')
				{
					$sql = 'PRIMARY KEY';
				}
				else
				{ // UNIQUE OR INDEX?
					foreach($indexDef as $row)
					{
						if($row['Key_name'] === $keyName)
						{
							if($row['Non_unique'])
								$sql = 'INDEX';
							else
								$sql = 'UNIQUE INDEX';
							break;
						}
					}

					$sql .= ' '.$keyName;
				}

				// columns
				$colNames = array();
				foreach($indexDef as $row)
				{
					if($row['Key_name'] === $keyName)
					{
						$colNames[$row['Seq_in_index']] = $row['Column_name'].($row['Sub_part']!==NULL?'('.$row['Sub_part'].')':'').' '.($row['Collation']=='A'?'ASC':'DESC');
					}
				}
				ksort($colNames);
				$sql .= ' ('.implode(",", $colNames).')';

				// index type
				foreach($indexDef as $row)
				{
					if($row['Key_name'] === $keyName)
					{
						$sql .= ' USING '.$row['Index_type'];
						break;
					}
				}

				$result[$keyName] = $sql;
			}
			return $result;
		}
	}

