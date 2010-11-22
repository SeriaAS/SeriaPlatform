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
	protected $abort;
	protected $data;
	protected $created;

	/*
	 * session_id() may not work on systemms with a
	 * session handler (Drupal). This class is used in a
	 * library for external login.
	 */
	protected static $sessionStarted = false;

	protected static $destroyed = false;

	/**
	 * 
	 * Creates a state tracking object (based on session and GET-parameters). Loads from
	 * session if the parameters are found, otherwise creates a new state.
	 */
	public function __construct()
	{
		if (!self::$sessionStarted && !session_id())
			session_start();
		if (isset($_GET['auth_id']) && isset($_GET['auth_abort'])) {
			$this->id = $_GET['auth_id'];
			$this->abort = $_GET['auth_abort'];
			if (isset($_SESSION[$this->id])) {
				$this->data = $_SESSION[$this->id];
				$this->created = false;
				return;
			}
		}
		$this->id = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
		$this->abort = SERIA_HTTP_ROOT;
		$this->data = array();
		$this->save();
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
	 * Checks whether there is a state available (by GET-parameters). Returns true for available, and false otherwise.
	 */
	public static function available()
	{
		if (!self::$sessionStarted && !session_id())
			session_start();
		return (isset($_GET['auth_id']) && isset($_GET['auth_abort']) && isset($_SESSION[$_GET['auth_id']]));
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
		unset($_SESSION[$this->id]);
		self::$destroyed = true;
	}

	/**
	 * 
	 * Redirects to the url pointed to by the field specified. Erases the state from session first.
	 * @param string $field
	 */
	public function terminate($field)
	{
		if ($field != 'abort') {
			$url = $this->pop($field);
			if (!$this->exists($field))
				$this->forget();
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
		$_SESSION[$this->id] = $this->data;
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
			$this->abort = $value;
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
	public function push($name, $value)
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
	 * Pops a value from an array. If the value is a singleton it will be returned and unset. Emptied arrays will be automatically unset.
	 * @param string $name
	 * @return mixed
	 */
	public function pop($name)
	{
		if (!$this->exists($name))
			throw new SERIA_Exception('No values to pop from: '.$name);
		$value = $this->get($name);
		if ($value && is_array($value)) {
			$r = array_pop($value);
			$this->set($name, $value);
			return $r;
		}
		$this->clear($name);
		return $value;
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