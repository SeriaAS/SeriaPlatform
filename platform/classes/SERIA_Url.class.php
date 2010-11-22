<?php
	/**
	*	Class simplifies working with urls in Seria Platform.
	*/
	class SERIA_Url
	{
		protected $_url;

		/**
		*	Provide the class with an absolute URL. Relative URLs have not been tested, and probably does not work yet.
		*	@param string $url	An absolute URL
		*/
		public function __construct($url)
		{
			$this->_url = $url;
		}

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
		*	Remove the fragment part of the query (after the #)
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
		*	Set the entire query (from the ? until the fragment #)
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
		*	@return string
		*/
		public function getQuery($value)
		{
			return parse_url($this->_url, PHP_URL_QUERY);
		}


		/**
		*	Add or replace a part of the query string
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
		*	Alias of unsetParam
		*/
		public function removeParam($param) { return $this->unsetParam($param); }

		/**
		*	Set the hostname of the url
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
		*	Set the scheme of the url (http/https/rtmp etc)
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
		*	Navigate up one folder. Unsets all query params and fragment.
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
		*	@param string $param		The name of the parameter to remove
		*	@return SERIA_Url
		*/
		public function getParam($param)
		{
			$parsed = parse_url($this->_url);
			if(empty($parsed['query']))
				return NULL;

			self::parse_str($parsed['query'], $parts);
			if(!isset($parsed[$param]))
				return NULL;
			return $parts[$param];
		}

		/**
		*	When echoing this class, the URL will be displayed.
		*/
		public function __toString()
		{
			return $this->_url;
		}


		/**
		*	Return the URL for the current page, optionally add request parameters
		*
		*	@param array $query	GET-parameters to add to the current URL
		*	@return string		Absolute URL
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
