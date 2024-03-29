<?php
/**
*	WebApp handler class
*
*	Create the following bootstrap.php and make sure that all missing URL requests are sent to bootstrap.php
*
*	bootstrap.php
*	=============
*	    <?php
*	        require('seria/platform.php');
*		WebApp::bootstrap($pathToViews[, $secondaryViews[, ...]]);
*
*	$pathToViews is a simple file system path.
*	$secondaryViews is a path to a secondary template, that will be looked into if the first path does not resolve.
*
*	The secondaryViews will NOT recurse into subdirectories, as that could potentially lead to security issues. If you want to include sub directories
*	from a secondary view folder, you denote them with /path/to/view/./subfolder - which means that /subfolder will be resolved.
*
*	Internals
*	---------
*
*	Internally in the WebApp and the WebAppRequest class, a request hierarchy is maintained. One instance of the WebApp class is able to manage a hierarchy of sub
*	requests.
*
*	By calling the static methods of WebApp::request() you will automatically 
*
*	File resolving
*	--------------
*
*	The algorithm to resolve a URL to a file system file is as follows:
*
*	1. Check directly if the path exists by adding ".php". If not found, try adding "/index.php". Return the path to the file.
*	2. If the file was not found, folders named "_" works like wildcards. The path /users/123/profile will resolve to /users/_/profile.php or /users/_/profile/index.php
*
*	In the case of wildcard folders, the value of the wildcard is available in the $_GET['_'] superglobal. That is; $_GET['_'][0] is the value of the first wildcard.
*/
class WebApp {

	public static $_debugLog;
	public static function debug($msg) {
		self::$_debugLog[] = $msg;
	}

	/**
	*	Holds the currently executing WebApp instance. This instance is created by calling WebApp::bootstrap(), and the value is immediately released after
	*	WebApp::bootstrap() finishes.
	*/
	public static $instance = NULL;

	/**
	*	Holds the currently executing WebAppRequest instance. This instance is automatically stored and overwritten by the WebAppRequest constructor, and reset
	*	once the WebAppRequest constructor finishes.
	*/
	public $currentRequest = NULL;

	protected $_viewPaths;

	public static $templateHandlers = array(
		'php' => array('WebApp', 'phpTemplate'),
		'md' => array('WebApp', 'markdown'),
	);

	/**
	*	Holds a list of the currently requested templates. Prevents infinite loops.
	*/
	public $_templateStack = array();

	/**
	*	Specify a view root, or multiple view roots as an array.
	*/
	public static function bootstrap($views) {
		ignore_user_abort(TRUE);
		set_time_limit(10);
		self::debug("WebApp::bootstrap()");
		if(SERIA_COMPATIBILITY < 4) throw new SERIA_Exception("Seria WebApp will only work when SERIA_COMPATIBILITY >= 4, currently ".SERIA_COMPATIBILITY.".");
		SERIA_ProxyServer::override();
		if(self::$instance) throw new SERIA_Exception("You can't call WebApp::bootstrap() from within another WebApp::bootstrap().");

		if($_SERVER['REQUEST_METHOD']!=='GET' && $_SERVER['REQUEST_METHOD']!=='HEAD')
			SERIA_ProxyServer::noCache();

		if(is_array($views))
			self::$instance = new WebApp($views);
		else
			self::$instance = new WebApp(array($views));

		if(isset($_SERVER['REQUEST_URI']))
			self::$instance->_request($_SERVER['REQUEST_URI'])->send();
		else
			self::$instance->_request('/')->send();

		SERIA_ProxyServer::commit();
		self::$instance = NULL;
	}

	public static function request($url) {
		if(!self::$instance) throw new SERIA_Exception("You can't call WebApp::request() without a current WebApp::bootstrap().");
		self::debug("WebApp::request($url)");
		return self::$instance->_request($url);
	}

	protected function _request($url) {
		$request = new WebAppRequest($url);

		// Remember the old request
		$oldRequest = $this->currentRequest;

		// This request is now the current request
		$this->currentRequest = $request;

		// Do the work
		try {
			$request->exec();
		} catch (WebApp_Exception $e) {
			// An internal error happened, so we should try to make sensible results for the end user
			switch($e->getCode()) {
				case SERIA_Exception::NOT_FOUND :
					$request = new WebAppRequestFake($e->getMessage(), $e->getCode());
					break;
				default :
					$request = new WebAppRequestFake('Exception: '.$e->getMessage(), 200);
					break;
			}
		}

		// Set back the current request
		$this->currentRequest = $oldRequest;
		return $request;
	}

	/**
	*	Accepts a path in the form of /path/to/resource?with=arguments. Returns the template file, or NULL if no template file was found.
	*/
	public function resolve($path) {
		if(!is_string($path)) throw new SERIA_Exception("\$path is a required argument");

		// Validate the path. We only allow alphanumeric characters, . (dot), _ (underscore) and / (slash).
		if(trim($path, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./_-')!=='')
			throw new SERIA_Exception('Illegal characters in URL "'.$path.'"', SERIA_Exception::UNSUPPORTED);

		// Now that we are certain that the string does not contain illegal characters - we need to prevent certain use cases
		if(strpos($path, './')!==FALSE || strpos($path, '/.')!==FALSE || strpos($path, '/_/')!==FALSE)
			throw new SERIA_Exception('URLs cannot contain . (dot) or _ (underscore) as a path component', SERIA_Exception::UNSUPPORTED);

		$res = self::_resolve($this->_viewPaths[0], $path);
		if($res === NULL) {
			// The path does not resolve, so we should try to resolve it using one of the secondary paths
			$max = sizeof($this->_viewPaths);
			for($i = 1; $i < $max; $i++) {
				if($res = self::_resolve($this->_viewPaths[$i], $path, TRUE)) break;
				var_dump($this->_viewPaths[$i]);
			}
		}
		return $res;
	}

	/**
	*	Resolves $path='/user/123/profile' to $root/user/_/profile.php, etc.
	*/
	protected static $_fsCacheIsDir = array();
	protected static $_fsCacheIsFile = array();
	protected static function _isDir($path) {
		if(isset(self::$_fsCacheIsDir[$path]))
			return self::$_fsCacheIsDir[$path];
		return self::$_fsCacheIsDir[$path] = is_dir($path);
	}
	protected static function _isFile($path) {
		if(isset(self::$_fsCacheIsFile[$path]))
			return self::$_fsCacheIsFile[$path];
		return self::$_fsCacheIsFile[$path] = is_file($path);
	}

	/**
	*	@param $exactRoot	If true, will not recurse
	*/
	public function _resolve($root, $path, $exactRoot=FALSE) {
		$exts = array_keys(self::$templateHandlers);

		$path = explode("/", trim($path,'/'));
		$current = _sp_realpath($root);

		$allVars = array();
		while($part = array_shift($path)) {
			if(self::_isDir($tmp = $current.'/'.$part)) {
				$current = $tmp;
			} else if(self::_isDir($tmp = $current.'/_')) {
				$current = $tmp;
				$allVars[] = $part;
			} else {
				$allVars[] = $part;
				// No more resolving can be done, so we consume any remaining parts as extra vars.
				while($part = array_shift($path))
					$allVars[] = $part;
			}
		}

		$allVarsSize = sizeof($allVars);
		$filename = pathinfo($current, PATHINFO_FILENAME);
		if($filename === '_' && $allVarsSize === 1) {
			// Special case where a catch all
			$tmpD = dirname($current);
			$tmpF = array_shift($allVars);
			foreach($exts as $ext) {
				if(self::_isFile($res = $tmpD.'/'.$tmpF.'.'.$ext))
					return array($res, $allVars);
			}
			array_unshift($allVars, $tmpF);
		} else if($allVarsSize>=1) {
			// Special case for file match
			$tmpF = array_shift($allVars);
			foreach($exts as $ext) {
				if(self::_isFile($res = $current.'/'.$tmpF.'.'.$ext))
					return array($res, $allVars);
			}
			array_unshift($allVars, $tmpF);

		}

		foreach($exts as $ext) {
			if(self::_isFile($res = $current.'/index.'.$ext)) {
				return array($res, $allVars);
			}
		}
		foreach($exts as $ext) {
			if(self::_isFile($res = $current.'/default.'.$ext)) {
				return array($res, $allVars);
			}
		}
		return NULL;
	}

	public function __construct(array $viewPaths) {
		$this->_viewPaths = $viewPaths;
	}

	public static function phpTemplate($path, &$state) {
		self::debug("require($path);");
		require($path);
	}

	public static function markdown($path, &$state) {
		// Markdown supports multiple existing markdown parsers
		if(class_exists('\Michelf\MarkdownExtra')) {
			echo \Michelf\MarkdownExtra::defaultTransform(file_get_contents($path));
		} else {
			// Fallback to the default and simple Parsedown library.
			$source = file_get_contents($path);
			$parsedown = new Parsedown();
			echo $parsedown->parse($source);
		}
	}
}
