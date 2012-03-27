<?php

/**
 * 
 * This tracks the state of a particular login process. All entry points to the process
 * should create an object of this class and initialize 'continue' and 'abort' urls, and
 * this state should follow the login process untill terminated by an abort or continue.
 *
 * Important! All entry points must be marked by a comment with this exact phrase:
 *   Authentication state entry point (ASEP)
 * This is to ensure that we can easily issue automatic text searches through the code
 * to find all entry points (where states are initially created).
 *
 * @author Jan-Espen
 *
 */
class SERIA_AuthenticationState
{
	protected $id;
	protected $abort = false;
	protected $data;
	protected $created;

	/**
	 * session_id() may not work on systemms with a
	 * session handler (Drupal). This class is used in a
	 * library for external login.
	 */
	protected static $sessionStarted = false;
	/**
	 * Use file-based storage.
	 * @var boolean
	 */
	protected static $fileBasedStorage = false;

	protected static $destroyed = false;

	/**
	 *
	 * Clean up tempfiles for authstate.
	 */
	protected static function cleanStateFiles()
	{
		$timelim = time()-36000;
		$path = sys_get_temp_dir().'/SERIA_Authproviders/state/';
		$sfdir = opendir($path);
		while (($filename = readdir($sfdir))) {
			$filepath = $path.$filename;

			$data = file_get_contents($filepath);
			$pre = 'authstatefile:';
			$post = ':statefile;';
			if (substr($data, 0, strlen($pre)) != $pre)
				continue; /* Bad statefile */
			if (substr($data, -strlen($post)) != $post)
				continue;
			$seridata = substr($data, strlen($pre), -strlen($post));
			$seridata = unserialize($seridata);
			if ($seridata['time'] < $timelim)
				unlink($filepath);
		}
	}
	/**
	 *
	 * Get the filename of a state-id.
	 * @param string $id
	 * @return string Filename
	 * @throws SERIA_Exception
	 */
	protected static function idToStateFilename($id)
	{
		/*
		 * Find the file..
		 */
		/* Remove the random part of the filename */
		$filename = substr($id, 0, -16);
		$characterCheck = preg_split('//', $filename, -1, PREG_SPLIT_NO_EMPTY);
		$validChar = preg_split('//', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-+', -1, PREG_SPLIT_NO_EMPTY);
		foreach ($characterCheck as $char) {
			if (!in_array($char, $validChar))
				throw new SERIA_Exception('Bad authstate id: '.$id);
		}
		$path = sys_get_temp_dir().'/SERIA_Authproviders/state/'.$filename;
		if (file_exists($path))
			return $path;
		else
			return null;
	}
	/**
	 *
	 * Write the state-file.
	 * @param string $id
	 * @param array $data
	 */
	protected static function writeStateFile($id, $data)
	{
		$seridata = array(
			'time' => time(),
			'id' => $id,
			'data' => $data
		);
		$serialized = 'authstatefile:'.serialize($seridata).':statefile;';
		$path = self::idToStateFilename($id);
		if ($path === null) {
			/*
			 * OOps
			 */
			SERIA_Base::debug('Lost the authentication state file');
			return;
		}
		file_put_contents($path, $serialized);
		self::cleanStateFiles();
	}
	/**
	 *
	 * Read the state file.
	 * @param string $id
	 * @return array
	 */
	protected static function readStateFile($id)
	{
		$path = self::idToStateFilename($id);
		if ($path === null)
			return null;
		$data = file_get_contents($path);
		$pre = 'authstatefile:';
		$post = ':statefile;';
		if (substr($data, 0, strlen($pre)) != $pre)
			return null; /* Bad statefile */
		if (substr($data, -strlen($post)) != $post)
			return null;
		$seridata = substr($data, strlen($pre), -strlen($post));
		$seridata = unserialize($seridata);
		if ($seridata['id'] == $id)
			return $seridata['data'];
		else
			return null;
	}
	/**
	 *
	 * Delete the state file.
	 * @param string $id
	 */
	protected static function deleteStateFile($id)
	{
		if (self::readStateFile($id) !== null) {
			$path = self::idToStateFilename($id);
			unlink($path);
		}
	}
	/**
	 *
	 * Create a new state file.
	 * @return string State id.
	 * @throws SERIA_Exception
	 */
	protected static function createStateFile()
	{
		$path = sys_get_temp_dir().'/SERIA_Authproviders/state/';
		if (!is_dir($path)) {
			mkdir($path, 0755, true);
		}
		$filename = tempnam($path, 'state');
		if (strpos($filename, $path) === 0) {
			$idpart = substr($filename, strlen($path));
			$id = $idpart.substr(sha1(mt_rand().mt_rand().mt_rand()), 0, 16);
			self::writeStateFile($id, array());
			return $id;
		} else
			throw new SERIA_Exception('Fails to create tempoary file in the specified directory!');
	}
	/**
	 *
	 * Create a new state id.
	 * @return string State id.
	 */
	protected static function createState()
	{
		if (!self::$fileBasedStorage) {
			return sha1(mt_rand().mt_rand().mt_rand().mt_rand());
		} else
			return self::createStateFile();
	}
	/**
	 *
	 * Get the state.
	 * @param string $id
	 * @return array State data.
	 */
	protected static function getState($id)
	{
		if (!self::$fileBasedStorage) {
			if (!self::$sessionStarted && !session_id())
				session_start();
			if (isset($_SESSION[$id]))
				return $_SESSION[$id];
			else
				return null;
		} else
			return self::readStateFile($id);
	}
	/**
	 *
	 * Forget the state.
	 * @param string $id
	 */
	protected static function forgetState($id)
	{
		if (!self::$fileBasedStorage) {
			unset($_SESSION[$id]);
		} else
			self::deleteStateFile($id);
	}
	/**
	 *
	 * Save the state.
	 * @param string $id
	 * @param array $data
	 */
	protected static function saveState($id, $data)
	{
		if (!self::$fileBasedStorage) {
			if (!session_id())
				session_start();
			$_SESSION[$id] = $data;
		} else
			self::writeStateFile($id, $data);
	}

	/**
	 * 
	 * Creates a state tracking object (based on session and GET-parameters). Loads from
	 * session if the parameters are found, otherwise creates a new state.
	 */
	public function __construct($stateId=null)
	{
		if ($stateId === null) {
			if (isset($_GET['auth_abort'])) {
				$this->abort = $_GET['auth_abort'];
				if (isset($_GET['auth_id'])) {
					$this->id = $_GET['auth_id'];
					$this->data = self::getState($this->id);
					if ($this->data !== null) {
						$this->created = false;
						return;
					}
				}
			}
		} else {
			$this->id = $stateId;
			$this->data = self::getState($this->id);
			if ($this->data === null)
				throw new SERIA_Exception('State was not found', SERIA_Exception::NOT_FOUND);
			if (isset($this->data['abort']))
				$this->abort = $this->data['abort'];
			$this->created = false;
			$_GET['auth_id'] = $this->id;
			$_GET['auth_abort'] = $this->abort;
			return;
		}
		$this->id = self::createState();
		$this->data = array();
		if (!$this->abort)
			$this->set('abort', SERIA_HTTP_ROOT); /* Implicit save */
		else
			$this->set('abort', $this->abort); /* Implicit save */
		$this->created = true;
		/*
		 * Cheating..
		 */
		$_GET['auth_id'] = $this->id;
		$_GET['auth_abort'] = $this->abort;
		self::$destroyed = false;
	}

	/**
	 *
	 * Don't call session_start even though session_id returns false. (Drupal-mode)
	 */
	public static function dontCallSessionStart()
	{
		self::$sessionStarted = true;
	}
	/**
	 *
	 * Use file based (tempfile) storage instead of session.
	 */
	public static function useFileBasedStorage()
	{
		self::$fileBasedStorage = true;
	}

	/**
	 *
	 * Checks whether there is a state available (by GET-parameters). Returns true for available, and false otherwise.
	 */
	public static function available()
	{
		return (isset($_GET['auth_id']) && isset($_GET['auth_abort']) && self::getState($_GET['auth_id']) !== null);
	}
	/**
	 *
	 * Guarantees that the state was created from found GET-parameters. If the state has
	 * been lost the user will be redirected to abort-page.
	 */
	public function assert()
	{
		if ($this->created) {
			if (!isset($_GET['auth_id']) || !isset($_GET['auth_abort']) || self::$destroyed)
				throw new SERIA_Exception('It looks like I lost the state tracking.');
			$this->terminate('abort');
		}
	}

	/**
	 *
	 * Destroys the state.
	 */
	public function forget()
	{
		self::forgetState($this->id);
		self::$destroyed = true;
	}

	/**
	 * 
	 * Redirects to the url pointed to by the field specified (the first url pushed to it, final destination). Erases the state from session first.
	 * @param string $field
	 */
	public function terminate($field)
	{
		SERIA_ProxyServer::noCache();
		if ($field != 'abort') {
			$url = $this->popRaw($field);
			while ($this->exists($field)) {
				if ($url && !is_string($url) && is_array($url)) {
					if ($url[0] == 'terminateHook') {
						$url = $url[1];
						SERIA_Base::redirectTo($url);
					} else
						throw new SERIA_Exception('Unknown type: '.$url[0]);
				}
				$url = $this->pop($field);
			}
			$this->forget();
			if ($url && !is_string($url) && is_array($url)) {
				if ($url[0] == 'terminateHook') {
					$url = $url[1];
					SERIA_Base::redirectTo($url);
				} else
					throw new SERIA_Exception('Unknown type: '.$url[0]);
			}
		} else
			$url = $this->get($field);
		SERIA_Base::redirectTo($url);
		die();
	}
	/**
	 * 
	 * Redirects to the specified url (can be either a SERIA_Url or a string)
	 * @param mixed $url
	 */
	public function redirectTo($url)
	{
		if (!($url instanceof SERIA_Url))
			$url = new SERIA_Url($url);
		$url = $this->stampUrl($url);
		SERIA_Base::redirectTo($url->__toString());
		die();
	}

	protected function save()
	{
		self::saveState($this->id, $this->data);
	}
	/**
	 * 
	 * Set a field value.
	 * @param string $name
	 * @param mixed $value
	 */
	public function set($name, $value)
	{
		SERIA_Base::debug('Set value: '.$name.' = '.serialize($value));
		if ($name == 'abort')
			$this->abort = self::shortenUrl($value);
		$this->data[$name] = $value;
		$this->save();
	}
	/**
	 * 
	 * Get a field value.
	 * @param string $name
	 * @return mixed
	 */
	public function get($name)
	{
		switch ($name) {
			case 'abort':
				return $this->abort;
			case 'id':
				return $this->id;
			default:
				if (!isset($this->data[$name]))
					throw new SERIA_Exception('Reading unitialized value from state: '.$name);
				SERIA_Base::debug('Get value: '.$name.' = '.serialize($this->data[$name]));
				return $this->data[$name];
		}
	}
	/**
	 *
	 * Unsets the value.
	 * @param string $name
	 */
	public function clear($name)
	{
		switch ($name) {
			case 'abort':
			case 'id':
				throw new SERIA_Exception('Can\'t unset special value: '.$name);
			default:
				if (!isset($this->data[$name]))
					throw new SERIA_Exception('Unsetting unitialized value from state: '.$name);
				unset($this->data[$name]);
				$this->save();
		}
	}

	/**
	 *
	 * Tests whether this field is set.
	 * @param string $name
	 * @return boolean
	 */
	public function exists($name)
	{
		switch ($name) {
			case 'abort':
			case 'id':
				return true;
			default:
				if (isset($this->data[$name])) {
					SERIA_Base::debug('State '.$name.' is set. Returning true..');
					return true;
				} else {
					SERIA_Base::debug('State '.$name.' is set. Returning false..');
					return false;
				}
		}
	}

	public static function shortenUrl($url)
	{
		$protocolHost = SERIA_Url::current()->__toString();
		if (strpos($protocolHost, 'https://') === 0)
			$protocol = 'https';
		else if (strpos($protocolHost, 'http://') === 0)
			$protocol = 'http';
		else
			return $url;
		$host = substr($protocolHost, strlen($protocol) + 3);
		$len = strpos($host, '/');
		if (!$len)
			return $url;
		$host = substr($host, 0, $len);
		$prefix = $protocol.'://'.$host.'/';
		if (strpos($url, $prefix) === 0) {
			$url = substr($url, strlen($prefix));
			return '/'.$url;
		}
		return $url;
	}

	/**
	 * 
	 * Put the state parameters on an url. (Can be either SERIA_Url or string)
	 * @param mixed $url
	 * @return mixed
	 */
	public function stampUrl($url)
	{
		$cp = $url;
		if (!($url instanceof SERIA_Url)) {
			$cp = new SERIA_Url($url);
			$rString = true;
		} else
			$rString = false;
		$httproot = new SERIA_Url(SERIA_HTTP_ROOT);
		if ($httproot->getHost() == $cp->getHost()) {
			$cp->setParam('auth_id', $this->id);
			$cp->setParam('auth_abort', $this->abort);
		} else
			SERIA_Base::debug('WARNING: TRIED TO SET AUTH-STATE ON EXTERNAL URL!');
		if ($rString)
			$cp = $cp->__toString();
		return $cp;
	}

	/**
	 *
	 * Pushes values into an array. If the value is a singleton from before it is automatically placed as the first array element. Redirects can be chained by pushing new urls.
	 * @param string $name
	 * @param mixed $value
	 */
	protected function pushRaw($name, $value)
	{
		SERIA_Base::debug('Push value for '.$name.': '.$value);
		if (!$this->exists($name)) {
			$this->set($name, array($value));
			return;
		}
		$prev = $this->get($name);
		if (is_array($prev)) {
			array_push($prev, $value);
			$this->set($name, $prev);
			return;
		}
		$value = array($prev, $value);
		$this->set($name, $value);
	}
	/**
	 *
	 * Pushes values into an array. If the value is a singleton from before it is automatically placed as the first array element. Redirects can be chained by pushing new urls.
	 * @param string $name
	 * @param mixed $value
	 */
	public function push($name, $value)
	{
		if (is_array($value) && !is_string($value))
			throw new SERIA_Exception('Not allowed to push an array');
		$this->pushRaw($name, $value);
	}
	/**
	 *
	 * Pushes a terminate hook url to the array. This url stops a terminate and redirects to the url, otherwise all urls except the last is skipped.
	 * @param string $name
	 */
	public function pushTerminateHook($name, $value)
	{
		if (!is_string($value))
			throw new SERIA_Exception('Terminate hook url is required to be a string.');
		$this->pushRaw($name, array('terminateHook', $value));
	}

	/**
	 *
	 * Pops a value from an array. If the value is a singleton it will be returned and unset. Emptied arrays will be automatically unset.
	 * @param string $name
	 * @return mixed
	 */
	protected function popRaw($name)
	{
		if (!$this->exists($name))
			throw new SERIA_Exception('No values to pop from: '.$name);
		$value = $this->get($name);
		if ($value && is_array($value)) {
			$r = array_pop($value);
			if ($value)
				$this->set($name, $value);
			else
				$this->clear($name);
			return $r;
		}
		$this->clear($name);
		return $value;
	}
	/**
	 *
	 * Pops a value from an array. If the value is a singleton it will be returned and unset. Emptied arrays will be automatically unset.
	 * @param string $name
	 * @return mixed
	 */
	public function pop($name)
	{
		$data = $this->popRaw($name);
		if ($data && is_array($data) && !is_string($data)) {
			if (isset($data[0])) {
				if ($data[0] == 'terminateHook')
					return $data[1];
				else
					throw new SERIA_Exception('Unknown data type: '.$data[0]);
			} else
				throw new SERIA_Exception('No data type');
		}
		return $data;
	}

	/**
	 *
	 * Get the first value from an array, or get the only value if it is not an array.
	 * @param string $name
	 * @return mixed
	 */
	public function getFirst($name)
	{
		$value = $this->get($name);
		if ($value && is_array($value)) {
			$keys = array_keys($value);
			return $value[array_shift($keys)];
		}
		return $value;
	}
	/**
	 *
	 * Get the last value from an array, or get the only value if it is not an array.
	 * @param string $name
	 * @return mixed
	 */
	public function getLast($name)
	{
		$value = $this->get($name);
		if ($value && is_array($value)) {
			$keys = array_keys($value);
			return $value[array_pop($keys)];
		}
		return $value;
	}
}