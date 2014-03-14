<?php
/**
*	Class that represents a HTTP response, that is the entire result from a HTTP request.
*/
class WebAppRequest {
	const HTTP_OK = 200;

	const HTTP_MOVED_PERMANENTLY = 301;
	const HTTP_FOUND = 302;
	const HTTP_MOVED_TEMPORARILY = 307;

	const HTTP_BAD_REQUEST = 400;
	const HTTP_FORBIDDEN = 403;
	const HTTP_NOT_FOUND = 404;
	const HTTP_GONE = 410;

	const HTTP_INTERNAL_SERVER_ERROR = 500;
	const HTTP_NOT_IMPLEMENTED = 501;
	const HTTP_SERVICE_UNAVAILABLE = 503;

	// Numeric HTTP status code
	public $httpCode = self::HTTP_OK;

	// Either a string or a file handle which will be sent using fpassthru
	public $content;

	/**
	*	Holds the state object for the current request. Properties set on this state object will be merged into the latest state object once the request has finished.
	*/
	public $state;

	/**
	*	Holds the most recent state object, if any.
	*/
	protected static $_latestState;

	// Caching information
	public $caching;

	// Will contain a list of all sub requests of this, that is requests that have been
	// performed by calling WebApp::request() within the exec() method of WebAppRequest.
	public $children = array();

	protected $path;
	protected $queryString;

	public function __construct($url) {
		$url = trim(trim($url), "/");
		$parts = explode("?", $url);
		$path = trim($parts[0], "/");

		$this->path = $path;
		if(isset($parts[1]))
			$this->queryString = $parts[1];
	}

	/**
	*	Execute (or reexecute) the template, and populate the public properties of this object.
	*/
	public function exec() {

		WebApp::debug('WebAppRequest::exec() '.$this->path);

		$webApp = WebApp::$instance;
		$requestInfo = $webApp->resolve($this->path);
		if(!$requestInfo)
			throw new SERIA_Exception('Could not resolve path "'.$this->path.'".');

		$keepState = self::$_latestState;

		$keepGet = serialize($_GET);
		$keepQueryString = NULL;
		if(!empty($_SERVER['QUERY_STRING']))
			$keepQueryString = $_SERVER['QUERY_STRING'];

		foreach($_GET as $k => $v) unset($_GET[$k]);
		if(!empty($this->queryString)) {
			parse_str($this->queryString, $_GET);
		}
		$_SERVER['QUERY_STRING'] = $this->queryString;
		$cache = new SERIA_Cache('WebApp');
		$cacheKey = filemtime($requestInfo[0]).md5(serialize($requestInfo).$_SERVER['QUERY_STRING']);
		$res = $cache->get($cacheKey);
		if($res) {
			self::$_latestState = $this->state = $res['state'];
			$this->content = $res['content'];
			$this->caching = $res['caching'];
			SERIA_ProxyServer::applyState($this->caching);
		} else {
			// Prepare to record proxy state
			$currentProxyState = SERIA_ProxyServer::init();

			// Prepare a state object that can contain cacheable state information from this request
			self::$_latestState = $this->state = new WebAppState();

			// Parse template
			ob_start();
			$this->parseTemplate($requestInfo[0], $this->state);
			$this->content = ob_get_contents();
			ob_end_clean();

			// Record proxy state
			$this->caching = SERIA_ProxyServer::init($currentProxyState);

			// Save cache
			if($this->caching['limiter']==SERIA_ProxyServer::CACHE_PUBLIC && ($ttl = $this->caching['expires'] - time()) > 0) {
				$cache->set($cacheKey, array(
					'state' => $this->state,
					'content' => $this->content,
					'caching' => $this->caching,
				), $ttl);
			}
		}

		$_GET = unserialize($keepGet);
		if($keepQueryString)
			$_SERVER['QUERY_STRING'] = $keepQueryString;

		WebApp::debug($this->caching);

		if($keepState) {
			self::$_latestState = $keepState;
			$this->state->mergeInto(self::$_latestState);
		}
	}

	public function parseTemplate($file, &$state) {
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if(isset(WebApp::$templateHandlers[$ext])) {
			$cb = WebApp::$templateHandlers[$ext];
			if(!is_callable($cb)) throw new SERIA_Exception('Template handler for ".'.$ext.'"-files is not callable.');
			call_user_func($cb, $file, $state);
		} else {
			throw new SERIA_Exception('No template handler registered for ".'.$ext.'"-files ('.$file.').');
		}
	}

	/**
	*	Output the response directly to the browser. This should not be called for sub requests.
	*/
	public function send() {
		switch($this->httpCode) {
			case self::HTTP_OK :
				header("HTTP/1.0 ".$this->httpCode." OK");
				break;
			case self::HTTP_MOVED_PERMANENTLY :
				header("HTTP/1.0 ".$this->httpCode." Moved Permanently");
				break;
			case self::HTTP_FOUND :
				header("HTTP/1.0 ".$this->httpCode." Found");
				break;
			case self::HTTP_MOVED_TEMPORARILY :
				header("HTTP/1.0 ".$this->httpCode." Moved Temporarily");
				break;
			case self::HTTP_BAD_REQUEST :
				header("HTTP/1.0 ".$this->httpCode." Bad Request");
				break;
			case self::HTTP_FORBIDDEN :
				header("HTTP/1.0 ".$this->httpCode." Forbidden");
				break;
			case self::HTTP_NOT_FOUND :
				header("HTTP/1.0 ".$this->httpCode." Not found");
				break;
			case self::HTTP_GONE :
				header("HTTP/1.0 ".$this->httpCode." Gone");
				break;
			case self::HTTP_INTERNAL_SERVER_ERROR :
				header("HTTP/1.0 ".$this->httpCode." Internal Server Error");
				break;
			case self::HTTP_NOT_IMPLEMENTED :
				header("HTTP/1.0 ".$this->httpCode." Not Implemented");
				break;
			case self::HTTP_SERVICE_UNAVAILABLE :
				header("HTTP/1.0 ".$this->httpCode." Service Unavailable");
				break;
		}
		echo $this;
	}

	public function __toString() {
		return $this->content;
	}
}
