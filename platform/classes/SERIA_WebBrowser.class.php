<?php

	class SERIA_SilentlyAbortTask extends SERIA_Exception
	{
	}

	class SERIA_WebBrowser
	{
		const INSTANCE_HOOK = 'SERIA_WebBrowser::__construct';

		// options
		public $supportCookies = true;
		public $sendReferrer = true;
		public $convertToUTF8 = true;
		public $userAgent = 'SERIA/WebBrowser 1.0';
		public $accept = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
		public $acceptLanguage = 'en-us,en;q=0.5';
		public $acceptCharset = '*';
		public $customRequestHeaders = array();
		public $requestDataTimeout = false; /* No data received for this amount of seconds will fail the request */
		public $followRedirect = true;
		/**
		 *
		 * Fill this array with username/password data as follows:
		 * array(
		 *   'hostname' => array(
		 *     'username' => 'username',
		 *     'password' => 'password'
		 *   ), repeat for each host...
		 * )
		 * @var array
		 */
		public $authentication = array();

		// automatically maintained
		public $cookies = array();
		public $movedPermanently = array();
		public $url = 'about:blank';
		public $host = null;
		public $path = null;
		public $responseHttpVersion = null;
		public $responseCode = null;
		public $responseString = null;
		public $requestHeaders = null;
		public $responseHeaders = null;
		public $trailerHeaders = null;
		public $method = null;

		// internal
		protected $authenticationRequested = array();
		protected $nextRequest = array();
		protected $currentRequest = false;
		protected $socket = null;
		protected $ipCache = array();
		protected $buffer = null;
		protected $buffer_eof = false;
		// http protocol internal
		protected $httpTransferCoding = null;
		protected $httpContentLength;
		// transfer internal tracking
		protected $transferLength;
		protected $chunkLength;
		protected $chunkReadLength;

		function __construct()
		{
			SERIA_Hooks::dispatch(self::INSTANCE_HOOK, $this);
		}

		function __sleep()
		{
			return array(
				'supportCookies',
				'sendReferrer',
				'convertToUTF8',
				'userAgent',
				'accept',
				'acceptLanguage',
				'acceptCharset',
				'cookies',
				'url',
				'responseHttpVersion',
				'responseCode',
				'responseString',
				'requestHeaders',
				'responseHeaders',
				'trailerHeaders'
			);
		}
		function __wakeup()
		{
		}

		public static function headerNameFilter($name)
		{
			$name = strtolower($name);
			$parts = explode('-', $name);
			foreach ($parts as &$part)
				$part = strtoupper($part[0]).substr($part, 1);
			return implode('-', $parts);
		}

		/**
		*	@param string $url	Expects a complete URL with hostname
		*/
		public function navigateTo($url, $post=false, $ip=NULL, $port=NULL)
		{
			SERIA_Base::debug('(SERIA_WebBrowser)->navigateTo('.$url.', ...)');

			if (isset($this->movedPermanently[$url])) {
				SERIA_Base::debug('Cached moved permanently: '.$url.' => '.$this->movedPermanently[$url]);
				$url = $this->movedPermanently[$url];
			}

			$p = parse_url($url);
			if(empty($p['path'])) $p['path'] = '/';
			if(!isset($p['host']) || !isset($p['path']))
				throw new SERIA_Exception('Insufficient url: '.$url);


			$url = $p["scheme"]."://".rawurlencode($p["host"]);
			$parts = explode("/", substr($p["path"],1));

			foreach ($parts as $part) $url .= "/".rawurlencode($part);
			if ($p["query"]) $url .= "?".$p["query"];

			$p = parse_url($url);

			/*
			 * Fix query-params without urlencoding
			 */
			$p['query'] = rawurlencode($p['query']);
			$p['query'] = str_replace(array('%26', '%3D'), array('&', '='), $p['query']);
			$p['query'] = str_replace('%25', '%', $p['query']);

			$this->nextRequest = array(
				'host' => $p['host'],
				'path' => $p['path'].($p['query']?'?'.$p['query']:''),
				'headers' => array(
					'Host' => $p['host'],
					'User-Agent' => $this->userAgent,
					'Accept' => $this->accept,
					'Accept-Language' => $this->acceptLanguage,
					'Accept-Encoding' => $this->acceptEncoding,
					'Accept-Charset' => $this->acceptCharset,
					'Connection' => 'close',
				) + $this->customRequestHeaders,
			);
			if (isset($this->authenticationRequested[$p['host']]) &&
			    $this->authenticationRequested[$p['host']] &&
			    isset($this->authentication[$p['host']]) &&
			    isset($this->authentication[$p['host']]['username']) &&
			    isset($this->authentication[$p['host']]['password'])) {
				switch (strtolower($this->authenticationRequested[$p['host']])) {
					case 'basic':
						$username = $this->authentication[$p['host']]['username'];
						$password = $this->authentication[$p['host']]['password'];
						$auth = base64_encode($username.':'.$password);
						$this->nextRequest['headers']['Authorization'] = 'Basic '.$auth;
						break;
					default:
						throw new SERIA_Exception('Authentication type '.$this->authenticationRequested[$p['host']].' is not supported.');
				}
			}

			if($ip)
				$this->nextRequest['ip'] = $ip;

			$this->nextRequest['secure'] = false;
			switch($p['scheme'])
			{
				case 'http' : 
					$this->nextRequest['port'] = 80;
					$this->nextRequest['transport'] = false;
					break;
				case 'https':
					$this->nextRequest['port'] = 443;
					$this->nextRequest['transport'] = 'ssl';
					$this->nextRequest['secure'] = true;
					break;
				default :
					throw new SERIA_Exception('Unsupported scheme '.$p['scheme']);
					break;
			}

			if ($this->supportCookies) {
				$cookieSpec = array('$Version="1"');
				foreach ($this->cookies as $cookie) {
					/*
					 * Subdomain of check
					 */
					if (isset($cookie['attrs']['domain']) &&
					    $p['host'] != $cookie['attrs']['domain'] &&
					    substr($p['host'], -strlen($cookie['attrs']['domain'])) != $cookie['attrs']['domain'])
						continue;
					/*
					 * Subpath of check
					 */
					if (isset($cookie['attrs']['path']) &&
					    $p['path'] != $cookie['attrs']['path'] &&
					    (substr($p['path'], 0, strlen($cookie['attrs']['path'])) != $cookie['attrs']['path'] ||
					     (substr($cookie['attrs']['path'], -1) != '/' &&
					      substr($p['path'], strlen($cookie['attrs']['path']), 1) != '/')))
						continue;
					/*
					 * Secure transport check
					 */
					if (isset($cookie['attrs']['secure']) && $cookie['attrs']['secure'] && !$this->nextRequest['secure'])
						continue;
					if (strpos($cookie['value'], '"') !== false)
						continue;
					$cookieSpec[] = $cookie['name'].'='.$cookie['value'].'';
					if (strpos($cookie['attrs']['path'], '"') === false)
						$cookieSpec[] = '$Path='.$cookie['attrs']['path'].'';
					if (strpos($cookie['attrs']['domain'], '"') === false)
						$cookieSpec[] = '$Domain='.$cookie['attrs']['domain'].'';
				}
				if (count($cookieSpec) > 1)
					$this->nextRequest['headers']['Cookie'] = implode('; ', $cookieSpec);
			}

			if($port)
			{
				$this->nextRequest['port'] = $port;
			}
			else if(isset($p['port']))
			{
				$this->nextRequest['port'] = $p['port'];
			}

			if($this->sendReferrer)
			{
				$op = parse_url($this->url);
				if(isset($op['host']) && isset($p['host']) && $op['host']==$p['host'])
					$this->nextRequest['headers']['Referer'] = $this->url;
			}

			if ((!is_string($post) && $post !== false) || strtolower($post) == 'post')
			{
				$this->method = 'POST';
				$this->nextRequest['method'] = 'POST';
				if (!is_string($post) && is_array($post))
					$this->nextRequest['postFields'] = $post;
				else
					$this->nextRequest['postFields'] = array();
				$this->nextRequest['postFiles'] = array(); /* SERIA_File[] */
			} else if ($post === false || strtolower($post) == 'get') {
				$this->method = 'GET';
				$this->nextRequest['method'] = 'GET';
			} else if (strtolower($post) == 'head') {
				$this->method = 'HEAD';
				$this->nextRequest['method'] = 'HEAD';
			} else
				throw new SERIA_Exception('Specify method as either boolean (false=>get, true=>post), array=>post, or string (get or post)');
			
			/*
			 * Reset:
			 */
			if ($this->socket !== null) {
				fclose($this->socket);
				$this->socket = null;
			}
			$this->url = $url;
			$this->buffer = null;
			$this->buffer_eof = false;
			$this->responseHeaders = null;
			$this->trailerHeaders = null;
			/*
			 * HTTP-reset
			 */
			$this->httpTransferCoding = null;
		}

		/**
		 *
		 * Get data about the next request.
		 */
		public function getNextRequest()
		{
			return $this->nextRequest;
		}
		/**
		 *
		 * Get data about the current request.
		 */
		public function getCurrentRequest()
		{
			return $this->currentRequest;
		}
		/**
		 *
		 * Restart current request (abort+restart).
		 */
		public function redoCurrentRequest()
		{
			/*
			 * Check whether this is a post request:
			 */
			if (isset($this->currentRequest['postFields']) && $this->currentRequest['postFields'])
				$post = $this->currentRequest['postFields'];
			else {
				if (isset($this->currentRequest['postFiles']) && $this->currentRequest['postFiles'])
					$post = true;
				else
					$post = false;
			}
			if (isset($this->currentRequest['postFiles']) && $this->currentRequest['postFiles'])
				$postFiles = $this->currentRequest['postFiles'];
			else
				$postFiles = array();
			$ip = false;
			if (isset($this->currentRequest['ip']))
				$ip = $this->currentRequest['ip'];
			$port = $this->currentRequest['port'];
			$this->navigateTo($this->url, $post, $ip, $port);
			foreach ($postFiles as $name => $file)
				$this->postFile($name, $file);
		}
		public function postField($name, $value)
		{
			if (!$this->nextRequest)
				throw new SERIA_Exception('Call navigateTo(..) before posting fields (also you can pass an array to navigateTo(..))');
			if ($this->nextRequest['method'] !== 'POST')
				throw new SERIA_Exception('The request is not a post.');
			$this->nextRequest['postFields'][$name] = $value;
		}
		public function postFields($fields)
		{
			foreach ($fields as $nam => $val)
				self::postField($nam, $val);
		}
		/**
		 * Post a file to the remote server.
		 *
		 * @param $name The fieldname, not the filename.
		 * @param $file A SERIA_File object.
		 * @return unknown_type
		 */
		public function postFile($name, SERIA_File $file)
		{
			if (!$this->nextRequest)
				throw new SERIA_Exception('Call navigateTo(..) before posting fields (also you can pass an array to navigateTo(..))');
			if ($this->nextRequest['method'] !== 'POST')
				throw new SERIA_Exception('The request is not a post.');
			$this->nextRequest['postFiles'][$name] = $file;
		}

		protected function _handleHeaders($dontRestartAfterRedirect)
		{
			foreach ($this->responseHeaders as $header => $values) {
				if (!is_array($values))
					$values = array($values);
				foreach ($values as $value) {
					try {
						switch ($header) {
							case 'Set-Cookie':
							case 'Set-Cookie2':
								if (!$this->supportCookies)
									break;
								$semi = explode(';', $value);
								if (count($semi) <= 0 || !$semi[0])
									throw new SERIA_SilentlyAbortTask('No cookie token');
								$bisq = trim(array_shift($semi));
								$splm = strpos($bisq, '=');
								if (!$splm)
									throw new SERIA_SilentlyAbortTask('Bad cookie.');
								$c_name = substr($bisq, 0, $splm);
								$c_value = substr($bisq, $splm + 1);
								$cookie = array(
									'raw' => $value,
									'name' => $c_name,
									'value' => $c_value,
									'attrs' => array()
								);
								foreach ($semi as $attr_raw) {
									try {
										$attr_raw = trim($attr_raw);
										$splm = strpos($attr_raw, '=');
										if ($splm) {
											$a_name = strtolower(substr($attr_raw, 0, $splm));
											$a_value = substr($attr_raw, $splm + 1);
										} else {
											$a_name = strtolower($attr_raw);
											$a_value = null;
										}
										switch ($a_name) {
											case 'comment': /* Set-cookie2 */
											case 'commenturl': /* Set-cookie2 */
											case 'discard': /* Set-cookie2 */
											case 'domain':
											case 'max-age':
											case 'path':
											case 'port': /* Set-cookie2 */
											case 'expires': /* Non-standard */
												if ($a_value !== null)
													$cookie['attrs'][$a_name] = $a_value;
												else
													throw new SERIA_SilentlyAbortTask('Attr spec fail.');
												break;
											case 'secure':
												$cookie['attrs']['secure'] = true;
											default:
												throw new SERIA_SilentlyAbortTask('Attr name is non-standard.');
										}
									} catch (SERIA_SilentlyAbortTask $e) {
									}
								}
								if (!isset($cookie['attrs']['domain']))
									$cookie['attrs']['domain'] = $this->host;
								else {
									/*
									 * Check the domain
									 */
									$domc = explode('.', $cookie['attrs']['domain']);
									/*
									 * No double dots:
									 */
									array_shift($domc);
									foreach ($domc as $domp) {
										if (!$domp)
											$cookie = null;
									}
									/*
									 * At least two domain name parts (ie. not just .com) :
									 */
									if ($cookie !== null && count($domc) < 2)
										$cookie = null;
									/*
									 * Subdomain of check:
									 */
									if ($cookie !== null &&
									    isset($cookie['attrs']['domain']) &&
									    $this->host != $cookie['attrs']['domain'] &&
									    (substr($this->host, -strlen($cookie['attrs']['domain'])) != $cookie['attrs']['domain'] ||
									     $cookie['attrs']['domain'][0] != '.'))
										$cookie = null; /* Discarded due to domain */
								}
								if (!isset($cookie['attrs']['path'])) {
									$pos = strrpos($this->path, '/');
									if ($pos !== false) {
										$cookie['attrs']['path'] = substr($this->path, $pos);
									} else
										$cookie['attrs']['path'] = '/';
									/*
									 * Subpath of check:
									 */
								} else if ($this->path != $cookie['attrs']['path'] &&
								           (substr($this->path, 0, strlen($cookie['attrs']['path'])) != $cookie['attrs']['path'] ||
								            (substr($cookie['attrs']['path'], -1) != '/' &&
								             substr($this->path, strlen($cookie['attrs']['path']), 1) != '/')))
									$cookie = null;
								if ($cookie !== null) {
									foreach ($this->cookies as $nam => $val) {
										if ($val['name'] == $cookie['name'])
											unset($this->cookies[$nam]);
									}
									$this->cookies[] =& $cookie;
									unset($cookie); /* Drop our reference */
								}
								break;
						}
					} catch (SERIA_SilentlyAbortTask $e) {
					}
				}
			}
			switch ($this->responseCode) {
				case 301:
				case 302:
				case 303:
				case 307:
					if ($this->followRedirect()) {
						if (!$dontRestartAfterRedirect)
							$this->fetchHeaders();
						else
							$this->send();
					}
					break;
				case 401:
					if (isset($this->currentRequest['headers']['WWW-Authenticate']))
						throw new SERIA_Exception('Failed www-authentication for '.$this->currentRequest['host']);
					if (isset($this->authentication[$this->currentRequest['host']]) &&
					    isset($this->authentication[$this->currentRequest['host']]['username']) &&
					    isset($this->authentication[$this->currentRequest['host']]['password'])) {
						if (isset($this->responseHeaders['Www-Authenticate'])) {
							$auth = $this->responseHeaders['Www-Authenticate'];
							$auth = preg_split("/[\s]+/", $auth);
							$this->authenticationRequested[$this->currentRequest['host']] = array_shift($auth);
							$this->redoCurrentRequest();
							$this->fetchHeaders();
						} else
							throw new SERIA_Exception('401 response requires a WWW-Authenticate header!');
					}
					break;
			}
		}

		protected function connect($request)
		{
			if(isset($this->nextRequest['ip']))
				$transport = $this->nextRequest['ip'];
			else
				$transport = $this->getHostByName($this->nextRequest['host']);

			if (isset($this->nextRequest['transport']) && $this->nextRequest['transport'] !== false) {
				SERIA_Base::debug('Opening connection to '.$tranport.' with transport '.$this->nextRequest['transport']);
				$transport = $this->nextRequest['transport'].'://'.$transport;
			}
			if(!($this->socket = fsockopen($transport, $this->nextRequest['port'], $errno, $errstr, 30))) {
				$this->socket = null;
				throw new SERIA_Exception('SERIA_WebBrowser could not connect: '.$errno.': '.$errstr);
			}

			return true;
		}

		public function getHostByName($host)
		{
			if(isset($this->ipCache[$host]))
				return $this->ipCache[$host];
			return $this->ipCache[$host] = gethostbyname($host);
		}

		protected function doPost()
		{
			if ($this->nextRequest['postFiles']) {
				$post = SERIA_MIMEEncoder::createMultipartPost($this->nextRequest['postFields'], $this->nextRequest['postFiles'], "\r\n", $boundary);
				$contentType = 'multipart/form-data; boundary='.$boundary;
			} else {
				$contentType = 'application/x-www-form-urlencoded';
				$post = http_build_query($this->nextRequest['postFields'], '', '&');
			}
			$this->nextRequest['headers']['Content-Type'] = $contentType;
			$this->nextRequest['headers']['Content-Length'] = strlen($post);
			return $post;
		}
		public function send()
		{
			SERIA_Base::debug('(SERIA_WebBrowser)->send(): '.$this->nextRequest['method'].' '.$this->nextRequest['host'].':'.$this->nextRequest['port']);
			if ($this->nextRequest['method'] == 'POST')
				$postdata = $this->doPost();
			else
				$postdata = '';

			if(!$this->connect($this->nextRequest))
				throw new SERIA_Exception('Unable to connect to server');

			$this->requestHeaders = $this->nextRequest['headers'];
			$this->host = $this->nextRequest['host'];
			$this->path = $this->nextRequest['path'];

			$l = $this->nextRequest['method']." ".$this->nextRequest['path']." HTTP/1.1\r\n";
			foreach($this->nextRequest['headers'] as $header => $value)
				$l .= $header.': '.$value."\r\n";
			$l .= "\r\n";
			fputs($this->socket, $l);
			if ($postdata)
				fputs($this->socket, $postdata);

			$this->currentRequest = $this->nextRequest;
			$this->nextRequest = false;

			if ($this->requestDataTimeout !== false) {
				/*
				 * Set to non-blocking. This timeout fires if there is no data received for this amount of time.
				 */
				stream_set_blocking($this->socket, 0);
			}

			$this->buffer = '';
			$this->buffer_eof = false;
		}

		public function getSocket()
		{
			return $this->socket;
		}

		protected function getDataInBuffer()
		{
			return $this->buffer;
		}
		/**
		 * Note that this function does not advance the stream pointer. Returns data.
		 *
		 * @param unknown_type $bytes
		 * @return unknown_type
		 */
		protected function bufferedFetchOnce($bytes=4096)
		{
			if ($this->buffer === null)
				$this->send();
			$buflen = strlen($this->buffer);
			if ($bytes <= 0) {
				if ($bytes == 0)
					return '';
				else
					$bytes = $buflen - $bytes; /* Negative: Read additional bytes rather than specified */
			}
			if ($buflen < $bytes && !$this->buffer_eof && !feof($this->socket)) {
				if ($this->requestDataTimeout !== false) {
					$read = array($this->socket);
					$write = NULL;
					$exc = NULL;
					$status = stream_select($read, $write, $exc, $this->requestDataTimeout);
					if ($status === 0)
						throw new SERIA_TimeoutException(_t('Timeout on data read from remote host.'));
					else if (!$status)
						throw new SERIA_Exception('stream_select failed.');
				}
				$data = fread($this->socket, $bytes - $buflen);
				if ($data === false)
					throw new SERIA_Exception(_t('Error while reading from remote host.'));
				$len = strlen($data);
				SERIA_Base::debug('Buffer fill for socket '.$this->socket.': '.$len.' bytes.');
				$buflen += $len;
				$this->buffer .= $data;
			}
			if (!$this->buffer_eof && feof($this->socket)) {
				SERIA_Base::debug('End of file on socket '.$this->socket.': closing.');
				$this->buffer_eof = true;
				fclose($this->socket);
				$this->socket = null;
			}
			if ($buflen <= $bytes)
				return $this->buffer;
			else
				return substr($this->buffer, 0, $bytes);
		}
		/**
		 * Advance the stream pointer by the specified amount of bytes. Returns number of bytes advanced, or false on error.
		 *
		 * @param unknown_type $bytes
		 * @return unknown_type
		 */
		protected function consumeBuffer($bytes)
		{
			SERIA_Base::debug('Consuming '.$bytes.' on socket '.$this->socket.'.');
			$buflen = strlen($this->buffer);
			if ($bytes < 0 || $bytes > $buflen)
				throw new SERIA_Exception('Bad number of bytes to consume. (Run bufferedFetch first, do not consume more than read)');
			if ($bytes == $buflen) {
				/* The quickest case */
				$this->buffer = '';
				return;
			} else if ($bytes == 0)
				return;
			else
				$this->buffer = substr($this->buffer, $bytes);
		}

		/**
		 * Fetches raw data. External use is discouraged (for other purposes than debugging)
		 *
		 * @param unknown_type $bytes
		 * @return unknown_type
		 */
		public function _fetch($bytes)
		{
			$data = $this->bufferedFetchOnce($bytes);
			if (strlen($data) == 0) {
				if ($this->buffer_eof)
					return false;
				return '';
			}
			$this->consumeBuffer(strlen($data));
			return $data;
		}
		/**
		 * Fetches raw data. External use is discouraged (for other purposes than debugging)
		 *
		 * @param unknown_type $bytes
		 * @return unknown_type
		 */
		public function _fetchAll()
		{			
			$data = '';
			while (($chunk = $this->_fetch(4096)) !== false)
				$data .= $chunk;
			return $data;
		}

		protected function _fetchHeaders()
		{
			$headers = array();
			while (($ln = $this->fetchLine()) !== false) {
				if ($ln == '') /* Header terminator */
					break;
				/* Consume header line */
				$delim = strpos($ln, ': ');
				if ($delim === false)
					continue;
				$nam = self::headerNameFilter(substr($ln, 0, $delim));
				$val = substr($ln, $delim + 2);
				if (!isset($headers[$nam]))
					$headers[$nam] = $val;
				else {
					if (is_array($headers[$nam]))
						$headers[$nam][] = $val;
					else
						$headers[$nam] = array($headers[$nam], $val);
				}
			}
			return $headers;
		}
		public function fetchHeaders($dontRestartAfterRedirect=false)
		{
			if ($this->responseHeaders !== null)
				return $this->responseHeaders;
			/*
			 * HTTP response
			 */
			$httpResp = $this->fetchLine();
			if (!$httpResp)
				throw new SERIA_Exception(_t('SERIA_WebBrowser expected a HTTP response code.'));
			if (strpos($httpResp, 'HTTP/') !== 0)
				throw new SERIA_Exception(_t('HTTP protocol violation in response line. (Protocol identifier)'));
			$httpResp = substr($httpResp, 5);
			$httpResp = str_replace("\t", ' ', $httpResp);
			$pos = strpos($httpResp, ' ');
			if (!$pos)
				throw new SERIA_Exception(_t('HTTP protocol violation in response line. (Protocol version)'));
			$this->responseHttpVersion = substr($httpResp, 0, $pos);
			$httpResp = ltrim(substr($httpResp, $pos));
			$pos = strpos($httpResp, ' ');
			if ($pos !== 3)
				throw new SERIA_Exception(_t('HTTP protocol violation in response line. (Expected resp code XXX)'));
			$this->responseCode = substr($httpResp, 0, $pos);
			$this->responseString = ltrim(substr($httpResp, $pos));
			/*
			 * HTTP Rresponse headers
			 */
			$this->responseHeaders = $this->_fetchHeaders();
			$this->_handleHeaders($dontRestartAfterRedirect);
			return $this->responseHeaders;
		}

		public function fetchAll($cacheControl=false)
		{
			$cacheControl = ($cacheControl && $this->nextRequest['method'] == 'GET');

			$data = $this->fetch();

			if ($data !== false) {
				while (($chunk = $this->fetch()) !== false) {
					$data .= $chunk;
				}
			}

			return $data;
		}

		protected function fetchChunkData($bytes,$dontWaitForMore)
		{
			$data = '';
			$missing = $bytes;
			while ($this->chunkLength !== null && $missing > 0) {
				$remaining = $this->chunkLength - $this->chunkReadLength;
				$xfer = $remaining;
				if ($xfer > $missing)
					$xfer = $missing;
				$blk = $this->bufferedFetchOnce($xfer);
				if ($xfer != 0 && strlen($blk) == 0 && $this->buffer_eof)
					throw new SERIA_Exception(_t('SERIA_Webbrowser lost the connection while receiving chunked data.'));
				$this->consumeBuffer(strlen($blk));
				$data .= $blk;
				$this->chunkReadLength += strlen($blk);
				$missing -= strlen($blk);
				if ($this->chunkReadLength >= $this->chunkLength) {
					if ($this->chunkReadLength > $this->chunkLength)
						throw new Exception('Buffer overrun.');
					$this->chunkLength = null;
					/* Consume the CRLF */
					$crlf = $this->bufferedFetchOnce(2);
					if ($crlf !== "\r\n")
						throw new SERIA_Exception(_t('SERIA_WebBrowser expects to receive CRLF after chunk data.'));
					$this->consumeBuffer(2); /* CRLF */
				}
				if ($dontWaitForMore)
					return $data;
			}
			if ($missing < 0)
				throw new Exception('Buffer overrun.');
			return $data;
		}

		public function fetch($bytes=4096,$dontWaitForMore=false)
		{
			if ($bytes <= 0)
				throw new SERIA_Exception('Please read something at least (bytes<=0).');
			$headers = $this->fetchHeaders($dontWaitForMore);
			if ($this->currentRequest['method'] == 'HEAD')
				return false; /* There must be no body in response to a head */
			if ($dontWaitForMore && $headers === null && !$this->buffer_eof)
				return '';

			if ($this->httpTransferCoding === null) {
				if (isset($headers['Transfer-Encoding']))
					$this->httpTransferCoding = $headers['Transfer-Encoding'];
				else
					$this->httpTransferCoding = false;
				if (isset($headers['Content-Length']))
					$this->httpContentLength = $headers['Content-Length'];
				else
					$this->httpContentLength = false;
				$this->transferLength = 0;
				$this->chunkLength = null; 
			}
			if (isset($headers['Connection'])) {
				$connectionType = $headers['Connection'];
				if ($connectionType != 'keepalive')
					$connectionType = false;
			} else
				$connectionType = false;
			switch ($this->httpTransferCoding) {
				case false:
					$xfer = $bytes;
					if ($this->httpContentLength != false) {
						if ($xfer > ($this->httpContentLength - $this->transferLength))
							$xfer = $this->httpContentLength - $this->transferLength;
						if ($xfer < 0)
							throw new SERIA_Exception('Overflow!');
						if ($xfer == 0 && $bytes > 0) {
							if ($connectionType === false && $this->socket !== null) /* close */ {
								/*
								 * Explicitly close the socket if we have received all the data and connection=close
								 * (This is not essential for the operation of SERIA_WebBrowser)
								 */
								SERIA_Base::debug('All data have been received, closing..');
								$this->buffer_eof = true;
								fclose($this->socket);
								$this->socket = null;
							}
							return false;
						}
						else if ($xfer == 0)
							return '';
					}
					$data = $this->bufferedFetchOnce($xfer);
					while (!$dontWaitForMore && strlen($data) < $xfer && !$this->buffer_eof)
						$data = $this->bufferedFetchOnce($xfer);
					$len = strlen($data);
					$this->consumeBuffer($len);
					$this->transferLength += $len;
					if ($this->httpContentLength == false && $len == 0 && $xfer > 0 && $this->buffer_eof)
						$this->httpContentLength = $this->transferLength;
					return $data;
				case 'chunked':
					if ($this->chunkLength === 0) /* Marks EOF */
						return false;
					/* Continue previous chunk */
					$data = $this->fetchChunkData($bytes, $dontWaitForMore);
					$missing = $bytes - strlen($data);
					if ($missing == 0 || (strlen($data) > 0 && $dontWaitForMore)) {
						$this->transferLength += strlen($data);
						return $data;
					}
					do {
						$chunkprefix = $this->fetchLine(16384, true);
						if (strlen($chunkprefix) == 0)
							throw new SERIA_Exception(_t('SERIA_WebBrowser expected another chunk.'));
						$chunksize = intval($chunkprefix, 16);
						$this->chunkReadLength = 0;
						$this->chunkLength = $chunksize;
						$blk = $this->fetchChunkData($missing, $dontWaitForMore);
						$missing -= strlen($blk);
						$data .= $blk;
					} while (!$dontWaitForMore && $chunksize > 0 && $missing > 0);
					if ($missing < 0)
						throw new Exception('Buffer overrun.');
					$this->transferLength += strlen($data);
					if ($chunksize == 0) { /* End of chunks has been passed */
						/* Get the trailing headers regardless of dont-wait */
						$this->trailingHeaders = $this->_fetchHeaders();
						$this->httpContentLength = $this->transferLength;
						$this->chunkLength = 0; /* Marks the EOF */
						if (strlen($data) == 0) {
							if ($connectionType === false && $this->socket !== null) /* close */ {
								/*
								 * Explicitly close the socket if we have received all the data and connection=close
								 * (This is not essential for the operation of SERIA_WebBrowser)
								 */
								SERIA_Base::debug('All data have been received, closing (chunked)..');
								$this->buffer_eof = true;
								fclose($this->socket);
								$this->socket = null;
							}
							return false; /* EOF */
						}
					}
					return $data;
					break;
				default:
					throw new SERIA_Exception(_t('SERIA_WebBrowser does not support Transfer-Encoding='.$headers['Transfer-Encoding']));
			}
		}
		protected function followRedirect()
		{
			/* EOF: Follow the redirect.. */
			if (!$this->followRedirect)
				return false; /* disabled */
			if (!isset($this->responseHeaders['Location']) || !is_string($this->responseHeaders['Location'])) {
				SERIA_Base::debug('HTTP protocol violation: No location header with response code: '.$this->responseCode);
				return false;
			}
			$redirectPoint = $this->url;
			$location = $this->responseHeaders['Location'];

			if(strpos($location, '://')===false)
			{ // handling illegal redirects (Location: /path)
				$pi = parse_url($this->url);
				$location = $pi['scheme'].'://'.$pi['host'].$location;
			}

			if ($this->responseCode == 301 || $this->responseCode == 307) {
				/*
				 * Check whether this is a post request:
				 */
				if (isset($this->currentRequest['postFields']) && $this->currentRequest['postFields'])
					$post = $this->currentRequest['postFields'];
				else {
					if (isset($this->currentRequest['postFiles']) && $this->currentRequest['postFiles'])
						$post = true;
					else
						$post = false;
				}
				if (isset($this->currentRequest['postFiles']) && $this->currentRequest['postFiles'])
					$postFiles = $this->currentRequest['postFiles'];
				else
					$postFiles = array();
			} else {
				$post = false;
				$postFiles = array();
			}
			$ip = false;
			$port = false;
			$from = parse_url($redirectPoint);
			$to = parse_url($location);
			if ($from['scheme'] == $to['scheme'] && $from['host'] == $to['host'] && $from['port'] == $to['port']) {
				if (isset($this->currentRequest['ip']))
					$ip = $this->currentRequest['ip'];
				$port = $this->currentRequest['port'];
			}
			$this->navigateTo($location, $post, $ip, $port);
			foreach ($postFiles as $name => $file)
				$this->postFile($name, $file);
			if ($this->responseCode == 301)
				$this->movedPermanently[$redirectPoint] = $location;
			return true;
		}

		public function fetchLine($maxlen=false, $maxlen_exception=false)
		{
			if ($maxlen !== false && $maxlen < 0)
				throw new SERIA_Exception('Maxlen is negative');
			$data = $this->getDataInBuffer();
			if ($maxlen !== false && $maxlen == 0)
				return '';
			while(($pos = strpos($data, "\r\n"))===false && ($maxlen === false || strlen($data) < $maxlen) && !$this->buffer_eof)
				$data = $this->bufferedFetchOnce(-4096);
			$pos = strpos($data, "\r\n");
			if ($pos !== false) {
				if ($maxlen !== false && $pos > $maxlen) {
					if ($maxlen_exception)
						throw new SERIA_Exception(_t('Max line length exceeded.'));
					$this->consumeBuffer($maxlen);
					return substr($data, 0, $maxlen);
				}
				$this->consumeBuffer($pos+2);
			} else {
				$pos = strlen($data);
				if ($pos == 0)
					return false; /* EOF */
				if ($maxlen !== false && $pos > $maxlen) {
					if ($maxlen_exception)
						throw new SERIA_Exception(_t('Max line length exceeded.'));
					$this->consumeBuffer($maxlen);
					return substr($data, 0, $maxlen);
				}
				$this->consumeBuffer($pos);
			}
			return substr($data, 0, $pos);
		}

		/**
		 * Drop-in replacement for file_get_contents.
		 * @param string $url		The url to fetch
		 * @param int $dataTimeout	The maximum number of seconds to wait before failing
		 * @return string		HTML
		 */
		public static function fetchUrlContents($url, $dataTimeout=false)
		{
			try {
				$browser = new SERIA_WebBrowser();
				$browser->requestDataTimeout = $dataTimeout;
				$browser->navigateTo($url);
				$data = $browser->fetchAll();
				if ($browser->responseCode != 200)
					throw new SERIA_Exception(_t('HTTP request failed (url=%URL%, code=%CODE%)', array('URL' => $url, 'CODE' => $browser->responseCode)));
				return $data;
			}
			catch (Exception $e) 
			{
				return false; // file_get_contents returns boolean false on error
			}
		}
	}
