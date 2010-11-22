<?php
	class SERIA_RPCClient
	{
		protected $hostname, $baseUrl, $url, $clientId, $key, $className;
		protected $framework = null;
		protected $IV = null;
		protected $browser = null;

		protected function __construct()
		{
		}

		protected function init($hostname, $className, $clientId=false, $key=false)
		{
			$this->hostname = $hostname;
			$this->baseUrl = 'http://'.$hostname;
			$this->url = $this->baseUrl.'/seria/platform/rpc/index.php';
			$this->key = $key;
			$this->clientId = $clientId;
			$this->className = $className;
		}

		public function getHostname()
		{
			return $this->hostname;
		}
		public function getBaseUrl()
		{
			return $this->baseUrl;
		}

		/*
		 * XXX - Wrapper for now..
		 */
		private function http_get($url)
		{
			if ($this->browser === null)
				$this->browser = new SERIA_WebBrowser();
			$this->browser->navigateTo($url);
			$data = $this->browser->fetchAll();
			if ($this->browser->responseCode != 200)
				throw new SERIA_Exception(_t('HTTP request failed (url=%URL%, code=%CODE%)', array('URL' => $url, 'CODE' => $this->browser->responseCode)));
			return $data;
		}
		private function http_post($url, $fields=array(), $files=array())
		{
			if ($this->browser === null)
				$this->browser = new SERIA_WebBrowser();
			$this->browser->navigateTo($url, true);
			$this->browser->postFields($fields);
			foreach ($files as $nam => $file)
				$this->browser->postFile($nam, $file);
			$data = $this->browser->fetchAll();
			if ($this->browser->responseCode != 200)
				throw new SERIA_Exception(_t('HTTP request failed (url=%URL%, code=%CODE%)', array('URL' => $url, 'CODE' => $this->browser->responseCode)));
			return $data;
		}

		protected function signRequest($request)
		{
			$msghash = sha1($request, true);
			$authcode = SERIA_CryptoBlowfish::encrypt($this->key, base64_encode($msghash), 'cbc', SERIA_CryptoBlowfish::createIVFromData('cbc', $this->IV));
			$this->IV = sha1($this->IV, true);
			return base64_encode($authcode);
		}
		protected function handshake()
		{
			/*
			 * Send the request for a session key:
			 */
			$request_random = sha1(mt_rand().mt_rand().mt_rand().mt_rand(), true);
			$request_random_hash = sha1($request_random, true);
			$IV = sha1($request_random_hash, true);
			$request_random .= $request_random_hash;
			$handshake = base64_encode(SERIA_CryptoBlowfish::encrypt($this->key, $request_random, 'ecb'));
			$handshake_resp = $this->http_get($this->url.'?client_id='.urlencode($this->clientId).'&handshake='.urlencode($handshake));
			$handshake_resp = base64_decode($handshake_resp);
			$handshake_resp = SERIA_CryptoBlowfish::decrypt($this->key, $handshake_resp, 'cbc', SERIA_CryptoBlowfish::createIVFromData('cbc', $IV));
			parse_str(base64_decode($handshake_resp), $handshake_resp);
			if (!isset($handshake_resp['sessionKey']) || !isset($handshake_resp['IV']))
				throw new Exception('RPC authentication handshake failed!');
			$handshake_resp['sessionKey'] = base64_decode($handshake_resp['sessionKey']);
			$handshake_resp['IV'] = base64_decode($handshake_resp['IV']);
			$this->key = $handshake_resp['sessionKey'];
			$this->sessionId = $handshake_resp['sessionId'];
			$this->IV = $handshake_resp['IV'];
		}
		public function connectToService($serviceName, $className, $hostname=null, $client_id=null, $client_key=null)
		{
			if ($hostname === null || $client_id === null || $client_key === null) {
				$servdb = SERIA_Base::db()->query('SELECT * FROM {rpc_remote_services} WHERE service = :service', array(':service' => $serviceName))->fetch(PDO::FETCH_ASSOC);
				if (!$servdb) {
					SERIA_Base::db()->insert('{rpc_remote_services}', array('service'), array('service' => $serviceName));
					return false;
				}
				if ($hostname === null)
					$hostname = $servdb['hostname'];
				if ($client_id === null)
					$client_id = $servdb['client_id'];
				if ($client_key === null)
					$client_key = $servdb['client_key'];
				if (!$hostname || !$client_id || !$client_key)
					return false;
			}

			$this->init($hostname, $className, $client_id, $client_key);
			return true;
		}
		public static function connect($serviceName, $className, $hostname=null, $client_id=null, $client_key=null)
		{
			$obj = new self();
			if ($obj->connectToService($serviceName, $className, $hostname, $client_id, $client_key) === false) {
				throw new SERIA_AccessDeniedException('This RPC service can not be authenticated. Programmers MUST handle the SERIA_AccessDeniedException exception when starting RPC, or the required database rows for authentication will not be created automatically! In short, do not let this transaction roll back!');
			}
			return $obj;
		}
		public function loadFramework($framework)
		{
			$this->framework = $framework;
		}
		
		protected function do__call($function, $args, $files=array(), $auth_attempted=false)
		{
			$params = array(
				'c' => $this->className,
				'm' => $function,
				'e' => 'php',
			);
			if ($this->framework !== null)
				$params['framework'] = $this->framework;
			foreach ($args as $arg)
				$params[] = $arg;
			if ($this->IV !== null) {
				$sigreq = http_build_query($params, '', '&');
				$params['sig'] = $this->signRequest($sigreq);
			}
			$result = $this->http_post($this->url, $params, $files);
			do {
				while (substr($result, 0, 1) == ' ') {
					$result = substr($result, 1);
				}
				if (substr($result, 0, 3) == '<br') {
					/* PHP-notice/whatever detected */
					$result = substr($result, 3);
					$pos = strpos($result, '<b>Notice</b>');
					if ($pos !== false) {
						$pos += 13;
						$result = substr($result, $pos);
						$pos = strpos($result, 'in <b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some notice. (expected filename)');
						$msg = substr($result, 0, $pos);
						$pos += 6;
						$result = substr($result, $pos);
						$pos = strpos($result, '</b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some notice. (expected filename end)');
						$filename = substr($result, 0, $pos);
						$pos += 4;
						$result = substr($result, $pos);
						$pos = strpos($result, 'on line <b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some notice. (expected line number)');
						$result = substr($result, $pos);
						$pos = strpos($result, '</b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some notice. (expected line number end)');
						$line = substr($result, 0, $pos);
						$pos += 4;
						do {
							if (strlen($result) <= 0)
								throw new SERIA_Exception('RPC server babbled about some notice. (No EOL)');
							$ch = substr($result, 0, 1);
							$result = substr($result, 1);
						} while ($ch != '\n' && $ch != '\r');
						while (strlen($result) > 0) {
							$ch = substr($result, 0, 1);
							if ($ch != '\n' && $ch != '\r')
								break;
							$result = substr($result, 1);
						}
						throw new Exception('RPC server notice: '.$msg.' (filename='.$filename.', line='.$line.')');
					} else if (($pos = strpos($result, 'error</b>:')) !== false) {
						$pos += 10;
						$result = substr($result, $pos);
						$pos = strpos($result, 'in <b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some error. (expected filename)');
						$msg = substr($result, 0, $pos);
						$pos += 6;
						$result = substr($result, $pos);
						$pos = strpos($result, '</b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some error. (expected filename end)');
						$filename = substr($result, 0, $pos);
						$pos += 4;
						$result = substr($result, $pos);
						$pos = strpos($result, 'on line <b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some error. (expected line number)');
						$result = substr($result, $pos);
						$pos = strpos($result, '</b>');
						if ($pos === false)
							throw new SERIA_Exception('RPC server babbled about some error. (expected line number end)');
						$line = substr($result, 0, $pos);
						throw new Exception('RPC server error: '.$msg.' ('.$filename.':'.$line.')');
					} else
						throw new SERIA_Exception('RPC server babbled about something. ('.htmlspecialchars(substr($result, 0, 50)).'...)');
				} else
					break;
			} while (true);
			/*if (strpos($result, '<b>Notice</b>') !== false)
				throw new Exception('Possible RPC babble.');*/
			$result = unserialize($result);
			if (isset($result['authenticated'])) {
				/*
				 * Our session has been approved. Don't sign further requests.
				 */
				$this->key = null;
				$this->IV = null;
			}
			if (isset($result['error'])) {
				if (isset($result['please_authenticate'])) {
					/* Protect against recursive failure */
					if ($auth_attempted)
						throw new SERIA_Exception(_t('Failed to authenticate RPC call.'));
					/*
					 * Authenticate and then retry the request..
					 */
					$this->handshake();
					return $this->do__call($function, $args, $files, true); /* Recursion protection on */
				}
				$exc = new $result['class']($result['error'], $result['code']);
				throw $exc;
			}
			if (isset($result['return']))
				return unserialize($result['return']);
		}
		function __call($function, $args)
		{
			return $this->do__call($function, $args);
		}
		public function callWithFileAttachments($method, $args, $files)
		{
			return $this->do__call($method, $args, $files);
		}
	}
