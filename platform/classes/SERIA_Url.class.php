<?php
	/**
	*	Class simplifies working with urls in Seria Platform.
	*/
	class SERIA_Url
	{
		protected $_url;

		const RELATIVE_TO_NONE = 0;
		const RELATIVE_TO_SCHEME = 1;
		const RELATIVE_TO_HOST = 2;

		/**
		* Provide the class with an absolute URL. Relative URLs have not been tested, and probably does not work yet.
		* @param string $url	An absolute URL
		*/
		public function __construct($url)
		{ 
			if(!is_string($url) && (!is_object($url) || get_class($url)!='SERIA_Url')) throw new Exception("Must receive a string");
			if(is_object($url)) $this->_url = ''.$url;
			else {
				if(strpos($url, "//")===0) $url = 'http:'.(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS'])!='off' ? 's' : '').$url;
				$this->_url = $url;
			}
		}

        /**
         * Sign the URL using an arbitrary key that must be shared among those that will be able to sign urls.
         * You may wish to look into SERIA_Fluent::all('SERIA_RpcClientKey') to find keys. It will also be wise
         * to add an "expires" parameter that states a time stamp after which the URL should not work. Of course,
         * you can add an application identifier to the url as well - and use that application identifier to figure
         * out which key to use when checking that the URL is signed.
         *
         * @param string $key           The key to use for signing the URL
         * @param string $paramName     The optional parameter name to use for signing the url. Default is 'sign'
         * @param string $algorithm     Alternative algorithm to use, will use sha1 if unspecified.
         * @return SERIA_Url
         */
		public function sign($key, $paramName='sign', $algorithm='sha1') {
			$parsed = parse_url($this->_url);
			$identifier = $parsed['path'];
			if(!empty($parsed['query'])) $identifier .= '?'.$parsed['query'];
			$hash = hash_hmac($algorithm, $identifier, $key);
			if(isset($parsed['query'])) $parsed['query'] .= '&'.$paramName.'='.$hash;
			else $parsed['query'] = $paramName.'='.$hash;
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		* Sign the provided URL
		*/
		public function quickSign() {
			$this->sign(SERIA_SALT);
			return $this;
		}

		/**
		* Check that the current URL is signed using quickSign()
		*/
		public static function quickCheck() {
			return SERIA_Url::current()->isSigned(SERIA_SALT);
		}

        /**
         * Check that the URL is signed using the specified key. Look at the comment for self::sign()
         *
         * @param string $key           The key to use for signing the URL
         * @param string $paramName     The optional parameter name to use for signing the url. Default is 'sign'
         * @param string $algorithm     Alternative algorithm to use, will use sha1 if unspecified.
         * @return bool
         */
		public function isSigned($key, $paramName='sign', $algorithm='sha1') {
			$parsed = parse_url($this->_url);
			if(empty($parsed['query']))
				return FALSE;
			self::parse_str($parsed['query'], $query);
			if(empty($query[$paramName])) return FALSE;
			$identifier = $parsed['path'].'?'.$parsed['query'];
			if(strpos($identifier, '&'.$paramName.'=')!==FALSE)
				$identifier = str_replace('&'.$paramName.'='.$query[$paramName], '', $identifier);
			else
				$identifier = str_replace('?'.$paramName.'='.$query[$paramName], '', $identifier);
//die($identifier);
//die($query[$paramName]."===".hash_hmac("sha1", "/?route=seria%2Fvideoplayer%2Fstrobeframe&objectKey=3", "468b2b2069008ab5c7d15aad9ac88822"));
//die($query[$paramName]."===".hash_hmac($algorithm, $identifier, $key));
			return $query[$paramName] === hash_hmac($algorithm, $identifier, $key);
		}

        /**
         * An implementation of parse_str that takes into account magic quotes.
         *
         * @param $str
         * @param $query
         */
        public static function parse_str($str, &$query)
		{
			parse_str($str, $query);

			if (get_magic_quotes_gpc()) {
				/*
				 * Need to stripslashes if magic quotes are enabled. (parse_str) 
				 */
				$process = array(&$query);
				while (list($key, $val) = each($process)) {
					foreach ($val as $k => $v) {
						unset($process[$key][$k]);
						if (is_array($v)) {
							$process[$key][stripslashes($k)] = $v;
							$process[] = &$process[$key][stripslashes($k)];
						} else {
							$process[$key][stripslashes($k)] = stripslashes($v);
						}
					}
				}
				unset($process);
			}
		}

		/**
		 *	Set the fragment part of the query (after the #)
         *
		 *	@param mixed $value		A string or an array to insert as fragment.
		 *	@return SERIA_Url
		 */
		public function setFragment($value)
		{
			$parsed = parse_url($this->_url);
			$parsed['fragment'] = $value; 
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 *	Remove the fragment part of the query (after the #). This removes the entire fragment.
         *
		 *	@return SERIA_Url
		 */
		public function unsetFragment()
		{
			$parsed = parse_url($this->_url);
			unset($parsed['fragment']);
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 * Get the url fragment.
		 *
		 * @return mixed
		 */
		public function getFragment()
		{
			return parse_url($this->_url, PHP_URL_FRAGMENT);
		}

		/**
		 *	Set the entire query (from the ? until the fragment #)
         *
		 *	@param mixed $value		A string or an array to insert as fragment.
		 *	@return SERIA_Url
		 */
		public function setQuery($value)
		{
			$parsed = parse_url($this->_url);
			$parsed['query'] = $value; 
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 *	Get the entire query part of the url (from the ? until the fragment #)
         *
		 *	@return string
		 */
		public function getQuery()
		{
			return parse_url($this->_url, PHP_URL_QUERY);
		}


		/**
		 *	Add or replace a part of the query string
         *
		 *	@param string $param		The name of the parameter to change
		 *	@param mixed $value		A string or an array to insert as value.
		 *	@return SERIA_Url
		 */
		public function setParam($param, $value)
		{
			$parsed = parse_url($this->_url);
			if(empty($parsed['query']))
				$query = array();
			else
				self::parse_str($parsed['query'], $query);

			$query[$param] = $value;

			$parsed['query'] = http_build_query($query, '', '&');
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 *	Remove a parameter from the query string
         *
		 *	@param string $param		The name of the parameter to remove
		 *	@return SERIA_Url
		 */
		public function unsetParam($param)
		{
			$parsed = parse_url($this->_url);
			if(empty($parsed['query']))
				return new SERIA_Url($this->_url);
			self::parse_str($parsed['query'], $query);
			unset($query[$param]);

			$parsed['query'] = http_build_query($query, '', '&');
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 *	Removes all parameters from the query string
         *
		 *	@return SERIA_Url
		 */
		public function clearParams()
		{
			$parsed = parse_url($this->_url);
			self::parse_str($parsed['query'], $query);

			foreach($query as $key => $val) {
				$this->unsetParam($key);
			}

			return $this;
		}

        /**
         * Alias of unsetParam
         *
         * @param string $param
         * @return SERIA_Url
         */
		public function removeParam($param) { return $this->unsetParam($param); }

		/**
		 *	Set the hostname of the url
         *
		 *	@param mixed $value		A string or an array to insert as value.
		 *	@return SERIA_Url
		 */
		public function setHost($value)
		{
			$parsed = parse_url($this->_url);
			$parsed['host'] = $value;
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 * Get the hostname of the url
		 * @return mixed
		 */
		public function getHost()
		{
			return parse_url($this->_url, PHP_URL_HOST);
		}

		/**
		 * Get the user of the url
		 * @return mixed
		 */
		public function getUser()
		{
			return parse_url($this->_url, PHP_URL_USER);
		}

		/**
		 * Get the password of the url
		 * @return mixed
		 */
		public function getPassword()
		{
			return parse_url($this->_url, PHP_URL_PASS);
		}

		/**
		 *	Set the scheme of the url (http/https/rtmp etc)
         *
		 *	@param mixed $value		A string or an array to insert as value.
		 *	@return SERIA_Url
		 */
		public function setScheme($value)
		{
			$parsed = parse_url($this->_url);
			$parsed['scheme'] = $value;
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

		/**
		 *	Set the path of the url
         *
		 *	@param string $value		A string to insert as value.
		 *	@return SERIA_Url
		 */
		public function setPath($value)
		{
			$parsed = parse_url($this->_url);
			$parsed['path'] = $value;
			$this->_url = self::buildUrl($parsed);
			return $this;
		}

        /**
         * Returns the path section of the URL up until the query parameter and fragment
         *
         * @return mixed
         */
        public function getPath() {
			$parsed = parse_url($this->_url);
			return $parsed['path'];
		}

		/**
		 *	Navigate up one folder. Unsets all query params and fragment.
         *
		 *	@return SERIA_Url
		 */
		public function parent()
		{
			$parsed = parse_url($this->_url);
			if(empty($parsed['path'])) 
				$parsed['path'] = "/";
			else
				$parsed['path'] = dirname($parsed['path']);
			unset($parsed['query']);

			$this->_url = self::buildUrl($parsed);
			$this->unsetFragment();
			return $this;
		}

		/**
		 *	Navigate to root path. Unsets all query params and fragment.
         *
		 *	@return SERIA_Url
		 */
		public function root()
		{
			$parsed = parse_url($this->_url);
			$parsed['path'] = "/";
			unset($parsed['query']);
			$this->_url = self::buildUrl($parsed);
			$this->unsetFragment();
			return $this;
		}
		

		/**
		 *	Parse out the value from the query string and return it.
         *
		 *	@param string $param		The name of the parameter to remove
		 *	@return mixed
		 */
		public function getParam($param)
		{
			$parsed = parse_url($this->_url);
			if(empty($parsed['query']))
				return NULL;

			self::parse_str($parsed['query'], $parts);
			if(!isset($parts[$param]))
				return NULL;
			return $parts[$param];
		}

		/**
		 * Get the url relative to another url or two predefined formats.
		 *
		 * @param $relativeTo mixed|SERIA_Url The url that the resulting string is going to be relative to
		 */
		public function getRelativeTo($relativeTo=self::RELATIVE_TO_NONE)
		{
			$buildUrl = '';
			if (((int) $relativeTo) === $relativeTo) {
				switch ($relativeTo) {
					case self::RELATIVE_TO_NONE:
						$parsed = parse_url($this->_url);
						return static::buildUrl($parsed);
					case self::RELATIVE_TO_SCHEME:
						$hostPart = $this->getHost();
						if (($userPart = $this->getUser())) {
							if (($passPart = $this->getPassword()))
								$userPart = $userPart.':'.$passPart;
							$hostPart = $userPart.'@'.$hostPart;
						}
						$buildUrl .= '//'.$hostPart;
					case self::RELATIVE_TO_HOST:
						$buildUrl .= $this->getPath();
						if (($query = $this->getQuery()))
							$buildUrl .= '?'.$query;
						if (($fragment = $this->getFragment()))
							$buildUrl .= '#'.$fragment;
						return $buildUrl;
				}
			}
			if (!($relativeTo instanceof SERIA_Url) && is_string($relativeTo))
				$relativeTo = new SERIA_Url($relativeTo);
			else if ($relativeTo instanceof SERIA_Url)
				$relativeTo = new SERIA_Url($relativeTo->__toString());
			else
				throw new SERIA_Exception('Invalid argument, expected url!', SERIA_Exception::INCORRECT_USAGE);
			$relativeTo->clearParams();
			$relativeTo->unsetFragment();
			$pathRelative = $relativeTo->getPath();
			if (!$pathRelative)
				$pathRelative = '/';
			$relativeTo->setPath('/');
			$relativeTo = $relativeTo->__toString();
			$url = $this->getRelativeTo(self::RELATIVE_TO_NONE);
			if (strpos($url, $relativeTo) === 0) {
				$relWithPath = substr($url, strlen($relativeTo));
				if (!$relWithPath || $relWithPath[0] != '/')
					$relWithPath = '/'.$relWithPath;
				$pathLen = strlen($pathRelative);
				while ($pathRelative[$pathLen - 1] == '/')
					$pathLen--;
				if ($pathLen == 0)
					return $relWithPath;
				$pathRelative = substr($pathRelative, 0, $pathLen);
				if ($pathRelative == $relWithPath) {
					return '';
				} else if (strpos($relWithPath, $pathRelative) === 0 && in_array($relWithPath[$pathLen], array('/', '?', '#'))) {
					/* Simple removal of the path relative to */
					$relWithPath = substr($relWithPath, $pathLen);
					while ($relWithPath && $relWithPath[0] == '/')
						$relWithPath = substr($relWithPath, 1);
					return $relWithPath;
				}
				return $relWithPath;
			}
			/*
			 * Host-part did not match..
			 * Return full absolute url..
			 */
			return $url;
		}

		/**
		 *	When echoing this class, the URL will be displayed.
		 */
		public function __toString()
		{
			return $this->_url;
		}


        /**
         * Return the URL for the current page, optionally add request parameters
         *
         * @return SERIA_Url        Absolute URL
         */
		public static function current()
		{
			$ru = $_SERVER['REQUEST_URI'];
			if(($protocolmark = strpos($ru, '://'))!==false)
			{ // workaround for a bug where REQUEST_URI is a complete url
				$protocol = substr($ru, 0, $protocolmark);
				/*
				 * Case:
				 *  request_uri = '/whatever/?c=http://whatever/'
				 * Then we will be ending up here with protocol = '/whatever/?c=http'.
				 * This and similar uris will be caught by the next check. 
				 */
				if (strpos($protocol, '/') === false) {
					$pi = parse_url($ru);
					$ru = $pi['path'];
				}
			}
			return new SERIA_Url('http'.(self::https() ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$ru);
		}

		/**
		 *	Returns true if this request was performed with https
         *
		 *	@return boolean
		 */
		public static function https()
		{
			if(empty($_SERVER['HTTPS']))
				return false;
			if(strtolower($_SERVER['HTTPS'])=='off') // ISAPI with IIS sets this to 'off'.
				return false;
			return true;
		}

        /**
         * Builds a complete URL from a parsed URL, according to parse_url().
         *
         * @param array $parsed
         * @return string
         */
        public static function buildUrl(array $parsed)
		{
			if(empty($parsed['scheme'])) $parsed['scheme'] = 'http';
			if(empty($parsed['host'])) $parsed['host'] = $_SERVER['HTTP_HOST'];

			$result = $parsed['scheme'].'://';

			if(!empty($parsed['user']))
			{
				if(!empty($parsed['pass']))
					$result .= $parsed['user'].':'.$parsed['pass'].'@';
				else
					$result .= $parsed['user'].':'.$parsed['pass'].'@';
			}

			$result .= $parsed['host'];
			if(!empty($parsed['port']))
				$result .= ':'.$parsed['port'];

			if(!empty($parsed['path']))
				$result .= $parsed['path'];

			if(!empty($parsed['query']))
				$result .= '?'.$parsed['query'];
			if(!empty($parsed['fragment']))
				$result .= '#'.$parsed['fragment'];

			return $result;
		}
	}
